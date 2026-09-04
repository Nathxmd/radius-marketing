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

    public function index() { view('referral/index', ['staff' => $this->staff->all()]); }

    public function create() { view('referral/create', ['branches' => $this->branches->getAll()]); }

    public function store() {
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? 'staff');
        $branchId = (int) ($_POST['branch_id'] ?? 0);
        $status = ($_POST['status'] ?? 'aktif') === 'nonaktif' ? 'nonaktif' : 'aktif';
        if ($name === '') { flash('error', 'Nama staff wajib diisi'); redirect('referral/create'); return; }
        try {
            $code = generateReferralCode($this->pdo, $name);
            $id = $this->staff->create(['name' => $name, 'role' => $role ?: 'staff', 'branch_id' => $branchId, 'referral_code' => $code, 'status' => $status]);
            $this->push($id);
            flash('success', "Staff berhasil ditambahkan dengan kode {$code}");
            redirect('referral');
        } catch (\Throwable $e) { error_log('[Referral] create: ' . $e->getMessage()); flash('error', 'Staff gagal disimpan'); redirect('referral/create'); }
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