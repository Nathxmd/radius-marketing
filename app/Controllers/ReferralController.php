<?php

namespace App\Controllers;

use App\Models\Referral;
use App\Models\Staff;
use App\Models\Branch;

class ReferralController {
    private $pdo;
    private $staff;
    private $referral;
    private $branches;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->staff = new Staff($pdo);
        $this->referral = new Referral($pdo);
        $this->branches = new Branch($pdo);
    }

    public function index() {
        $search = trim($_GET['search'] ?? '');
        $perPage = 20;
        $total = $this->staff->paginate($search, 1, 0)['total'];
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, (int) ($_GET['page'] ?? 1)), $totalPages);
        $result = $this->staff->paginate($search, $perPage, ($page - 1) * $perPage);
        view('referral/index', [
            'staff' => $result['rows'],
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $result['total'],
            'totalPages' => $totalPages,
        ]);
    }

    public function create() { view('referral/create', ['branches' => $this->branches->getAll()]); }

    public function bulkCreate() { view('referral/bulk'); }

    public function store() {
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? 'staff');
        $branchId = (int) ($_POST['branch_id'] ?? 0);
        $status = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';
        if ($name === '') { flash('error', 'Nama staff wajib diisi'); redirect('referral/create'); return; }
        try {
            $code = generateReferralCode($this->pdo, $name);
            $id = $this->staff->create(['name' => $name, 'employee_code' => trim($_POST['employee_code'] ?? ''), 'role' => $role ?: 'staff', 'branch_id' => $branchId, 'referral_code' => $code, 'status' => $status]);
            $this->push($id);
            flash('success', "Staff berhasil ditambahkan dengan kode {$code}");
            redirect('referral');
        } catch (\Throwable $e) { error_log('[Referral] create: ' . $e->getMessage()); flash('error', 'Staff gagal disimpan'); redirect('referral/create'); }
    }

    public function bulkStore() {
        $file = $_FILES['staff_csv'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            flash('error', 'File CSV wajib dipilih');
            redirect('referral/bulk');
            return;
        }

        $handle = fopen($file['tmp_name'], 'rb');
        $header = $handle ? fgetcsv($handle) : false;
        if (!$handle || !$header || count($header) < 2) {
            if ($handle) fclose($handle);
            flash('error', 'Format CSV harus memiliki kolom name dan employeeCode');
            redirect('referral/bulk');
            return;
        }

        $header = array_map(function ($value) { return strtolower(trim((string) $value)); }, $header);
        $nameIndex = array_search('name', $header, true);
        $codeIndex = array_search('employeecode', $header, true);
        if ($nameIndex === false || $codeIndex === false) {
            fclose($handle);
            flash('error', 'Header CSV harus bernama name,employeeCode');
            redirect('referral/bulk');
            return;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $rowNumber = 1;
        try {
            $this->pdo->beginTransaction();
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $name = trim((string) ($row[$nameIndex] ?? ''));
                $employeeCode = normalizeEmployeeCode($row[$codeIndex] ?? '');
                if ($name === '') { $skipped++; continue; }

                $existing = $employeeCode ? $this->staff->findByEmployeeCode($employeeCode) : $this->staff->findByName($name);
                if ($existing) {
                    $this->staff->updateEmployee((int) $existing['id'], $name, $employeeCode ?: ($existing['employee_code'] ?? null));
                    $updated++;
                    continue;
                }

                $referralCode = generateReferralCode($this->pdo, $name);
                $this->staff->create(['name' => $name, 'employee_code' => $employeeCode, 'role' => 'guru', 'branch_id' => 0, 'referral_code' => $referralCode, 'status' => 'aktif']);
                $created++;
            }
            fclose($handle);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            if (is_resource($handle)) fclose($handle);
            error_log('[Referral] bulk import: ' . $e->getMessage());
            flash('error', 'Bulk import gagal: ' . $e->getMessage());
            redirect('referral/bulk');
            return;
        }

        $message = "Import selesai: {$created} staff baru, {$updated} diperbarui";
        if ($skipped > 0) $message .= ", {$skipped} baris dilewati";
        flash('success', $message);
        redirect('referral');
    }

    public function edit(int $id) {
        $staff = $this->staff->find($id);
        if (!$staff) { flash('error', 'Staff tidak ditemukan'); redirect('referral'); return; }
        view('referral/edit', ['staff' => $staff, 'branches' => $this->branches->getAll()]);
    }

    public function update(int $id) {
        $old = $this->staff->find($id);
        if (!$old) { flash('error', 'Staff tidak ditemukan'); redirect('referral'); return; }
        $data = ['name' => trim($_POST['name'] ?? ''), 'role' => trim($_POST['role'] ?? 'staff'), 'branch_id' => (int) ($_POST['branch_id'] ?? 0), 'referral_code' => strtoupper(trim($_POST['referral_code'] ?? '')), 'status' => ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif'];
        if ($data['name'] === '' || !validReferralCode($data['referral_code']) || $this->staff->existsCode($data['referral_code'], $id)) { flash('error', 'Nama atau kode referral tidak valid/sudah dipakai'); redirect("referral/edit/{$id}"); return; }
        try { $this->staff->update($id, $data); if ($old['name'] !== $data['name'] || $old['referral_code'] !== $data['referral_code'] || (int) $old['branch_id'] !== $data['branch_id'] || $old['status'] !== $data['status']) $this->push($id); flash('success', 'Data staff berhasil diupdate'); redirect('referral'); }
        catch (\Throwable $e) { error_log('[Referral] update: ' . $e->getMessage()); flash('error', 'Data staff gagal diupdate'); redirect("referral/edit/{$id}"); }
    }

    public function regenerate(int $id) {
        $staff = $this->staff->find($id);
        if (!$staff) { flash('error', 'Staff tidak ditemukan'); redirect('referral'); return; }
        try { $code = generateReferralCode($this->pdo, $staff['name']); $this->staff->update($id, ['name' => $staff['name'], 'role' => $staff['role'], 'branch_id' => $staff['branch_id'], 'referral_code' => $code, 'status' => $staff['status']]); $this->push($id); flash('success', "Kode berhasil diganti menjadi {$code}"); }
        catch (\Throwable $e) { error_log('[Referral] regenerate: ' . $e->getMessage()); flash('error', 'Kode referral gagal diganti'); }
        redirect("referral/edit/{$id}");
    }

    public function detail(int $id) { $staff = $this->staff->find($id); if (!$staff) { flash('error', 'Staff tidak ditemukan'); redirect('referral'); return; } view('referral/detail', ['staff' => $staff, 'registrations' => $this->staff->registrations($id)]); }

    public function commission() { $id = (int) ($_POST['registration_id'] ?? 0); $status = $_POST['status'] ?? ''; if (in_array($status, ['disetujui', 'dibayar'], true)) $this->referral->updateCommission($id, $status); redirect('referral/' . (int) ($_POST['staff_id'] ?? 0)); }

    public function logs() { $type = in_array($_GET['type'] ?? 'all', ['push', 'webhook'], true) ? $_GET['type'] : 'all'; view('referral/logs', ['type' => $type, 'pushLogs' => $type === 'webhook' ? [] : $this->referral->logs('push'), 'webhookLogs' => $type === 'push' ? [] : $this->referral->logs('webhook')]); }

    public function settings() { view('referral/settings', ['setting' => $this->referral->setting()]); }

    public function saveSettings() { $type = in_array($_POST['reward_type'] ?? '', ['persen', 'nominal'], true) ? $_POST['reward_type'] : 'nominal'; $discount = max(0, (float) ($_POST['discount_value_parent'] ?? 0)); $commission = max(0, (float) ($_POST['commission_value_staff'] ?? 0)); $this->referral->saveSetting($type, $discount, $commission); flash('success', 'Pengaturan reward berhasil disimpan'); redirect('referral/settings'); }

    public function resync() { require_once __DIR__ . '/../../referral-push.php'; $count = 0; foreach ($this->staff->all() as $staff) { if ($staff['status'] === 'aktif') { pushReferralToExternalApp($this->pdo, $staff); $count++; } } flash('success', "Resync {$count} staff aktif selesai. Periksa log untuk hasilnya."); redirect('referral'); }

    private function push(int $id): void { require_once __DIR__ . '/../../referral-push.php'; $staff = $this->staff->find($id); if ($staff) pushReferralToExternalApp($this->pdo, $staff); }
}