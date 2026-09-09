<?php

namespace App\Controllers;

class WebhookController {

    /**
     * Terima event status referral dari aplikasi eksternal via webhook.
     *
     * Event:
     *  - used             -> insert registrations (lead)
     *  - enrolled         -> update registrations + catat promo_redemptions (daycare)
     *  - visit_completed  -> catat promo_redemptions parent klinik per kunjungan
     *  - cancelled        -> batal
     *
     * Keamanan: verifikasi X-Signature (HMAC-SHA256 body), idempotency via event_id.
     */
    public function referralStatus() {
        global $pdo;

        $raw = file_get_contents('php://input');
        if (strlen($raw) > 1048576) {
            http_response_code(413);
            echo json_encode(['status' => 'error', 'message' => 'Payload too large']);
            return;
        }

        $payload   = json_decode($raw, true);
        $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
        $valid     = REFERRAL_SHARED_SECRET !== ''
            && $signature !== ''
            && hash_equals(hash_hmac('sha256', $raw, REFERRAL_SHARED_SECRET), $signature);

        $eventId = is_array($payload) ? trim((string) ($payload['event_id'] ?? '')) : '';
        $code    = is_array($payload) ? strtoupper(trim((string) ($payload['referral_code'] ?? ''))) : '';
        $type    = is_array($payload) ? trim((string) ($payload['event_type'] ?? '')) : '';
        $phone   = is_array($payload) ? sanitizeParentPhone($payload['parent_phone'] ?? null) : null;
        $source  = 'external_app:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        // Log SETIAP request masuk (valid maupun tidak).
        $log = function ($processed, $error) use ($pdo, $source, $raw, $valid, $eventId, $code, $type) {
            $stmt = $pdo->prepare(
                "INSERT INTO webhook_logs (source, raw_payload, signature_valid, event_id, referral_code, event_type, processed, error_message)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $source, $raw, $valid ? 1 : 0,
                $eventId ?: null, $code ?: null, $type ?: null,
                $processed ? 1 : 0, $error,
            ]);
        };

        header('Content-Type: application/json');

        // 1) Signature wajib valid.
        if (!$valid) {
            $log(false, 'Signature tidak valid');
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
            return;
        }

        // 2) Validasi struktur umum.
        $allowedTypes = ['used', 'enrolled', 'visit_completed', 'cancelled'];
        if ($eventId === ''
            || !validReferralCode($code)
            || !in_array($type, $allowedTypes, true)
            || ($type !== 'used' && !$phone)) {
            $log(false, 'Field wajib/format payload tidak valid');
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid payload']);
            return;
        }

        // 3) Idempotency: event_id sudah diproses -> skip.
        try {
            $check = $pdo->prepare('SELECT id FROM webhook_logs WHERE event_id = ? AND processed = 1 LIMIT 1');
            $check->execute([$eventId]);
            if ($check->fetch()) {
                $log(true, 'Already processed');
                echo json_encode(['status' => 'ok', 'message' => 'Already processed']);
                return;
            }

            // 4) Promo type hanya daycare/klinik.
            $promoType = strtolower(trim((string) ($payload['promo_type'] ?? '')));
            if (!in_array($promoType, ['daycare', 'klinik'], true)) {
                $log(false, 'promo_type harus daycare atau klinik');
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid promo_type']);
                return;
            }

            // 5) Resolve staff berdasarkan kode referral aktif.
            $staffStmt = $pdo->prepare("SELECT id FROM staff WHERE referral_code = ? AND status = 'aktif' LIMIT 1");
            $staffStmt->execute([$code]);
            $staff = $staffStmt->fetch();
            if (!$staff) {
                $log(false, 'Kode referral tidak ditemukan/tidak aktif');
                echo json_encode(['status' => 'ok', 'message' => 'Referral code not found, logged for review']);
                return;
            }

            $pdo->beginTransaction();

            if ($type === 'used') {
                $this->handleUsed($pdo, $payload, $staff, $code, $phone, $promoType, $eventId);
            } elseif ($type === 'enrolled') {
                $this->handleEnrolled($pdo, $payload, $code, $phone, $promoType);
            } elseif ($type === 'visit_completed') {
                $result = $this->handleVisitCompleted($pdo, $payload, $code, $phone, $promoType);
                if ($result !== null) {
                    $pdo->rollBack();
                    $log(false, $result['message']);
                    http_response_code(422);
                    echo json_encode(['status' => 'error', 'message' => $result['message']]);
                    return;
                }
            } else { // cancelled
                $this->handleCancelled($pdo, $code, $phone);
            }

            $log(true, null);
            $pdo->prepare('UPDATE webhook_logs SET processed = 1 WHERE event_id = ? AND processed = 0 ORDER BY id DESC LIMIT 1')
                ->execute([$eventId]);
            $pdo->commit();

            echo json_encode(['status' => 'ok']);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[Webhook] ' . $e->getMessage());
            $log(false, $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Internal error']);
        }
    }

    /**
     * Event "used": lead masuk -> INSERT registrations baru.
     */
    private function handleUsed($pdo, array $payload, array $staff, string $code, ?string $phone, string $promoType, string $eventId): void {
        $branch = filter_var($payload['branch_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        if ($branch) {
            $branchCheck = $pdo->prepare('SELECT id FROM branches WHERE id = ?');
            $branchCheck->execute([$branch]);
            if (!$branchCheck->fetch()) {
                $branch = null;
            }
        }

        // staff_commission_status: klinik -> 'tidak_ada'; daycare -> 'belum_ada'.
        $commission = $promoType === 'klinik' ? 'tidak_ada' : 'belum_ada';

        $stmt = $pdo->prepare(
            "INSERT INTO registrations
                (external_event_id, staff_id, referral_code_used, parent_name, parent_phone, child_name,
                 branch_id_target, promo_type, enrollment_status, staff_commission_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'lead', ?)"
        );
        $stmt->execute([
            $eventId,
            $staff['id'],
            $code,
            trim((string) ($payload['parent_name'] ?? '')) ?: null,
            $phone,
            trim((string) ($payload['child_name'] ?? '')) ?: null,
            $branch,
            $promoType,
            $commission,
        ]);
    }

    /**
     * Event "enrolled": update registrasi jadi enrolled.
     * Daycare -> catat 3 bulan promo parent + 1 komisi staff (pending).
     * Klinik  -> tidak ada komisi staff, tidak ada redemption parent di sini.
     */
    private function handleEnrolled($pdo, array $payload, string $code, ?string $phone, string $promoType): void {
        $stmt = $pdo->prepare(
            "UPDATE registrations
             SET enrollment_status = 'enrolled', updated_at = NOW()
             WHERE referral_code_used = ? AND parent_phone = ?
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$code, $phone]);

        // Ambil registrasi yang baru diupdate.
        $regStmt = $pdo->prepare(
            "SELECT r.id, r.promo_type
             FROM registrations r
             WHERE r.referral_code_used = ? AND r.parent_phone = ?
             ORDER BY r.created_at DESC, r.id DESC LIMIT 1"
        );
        $regStmt->execute([$code, $phone]);
        $reg = $regStmt->fetch();
        if (!$reg) {
            throw new \RuntimeException('Enrolled: registrasi tidak ditemukan');
        }

        if ($promoType === 'daycare') {
            $this->recordDaycareRedemptions($pdo, $reg['id']);
        }
        // klinik: staff_commission_status tetap 'tidak_ada', tidak buat redemption apapun.
    }

    /**
     * Catat redemption daycare:
     *  - staff: one_time, period NULL, value 250000, status 'tercatat' + komisi pending
     *  - parent: 3x recurring_monthly period 1,2,3 masing-masing 500000
     */
    private function recordDaycareRedemptions($pdo, int $registrationId): void {
        // Staff commission.
        $staffRule = $pdo->prepare(
            "SELECT r.value FROM promo_rules r
             JOIN promos p ON p.id = r.promo_id
             WHERE p.type = 'daycare' AND r.beneficiary = 'staff' AND r.branch_id IS NULL
             LIMIT 1"
        );
        $staffRule->execute();
        $staffVal = $staffRule->fetchColumn();
        $staffVal = $staffVal !== false ? (float) $staffVal : 0;

        $red = $pdo->prepare(
            "INSERT INTO promo_redemptions (registration_id, beneficiary, period_or_visit_number, value_applied, status)
             VALUES (?, ?, ?, ?, 'tercatat')"
        );
        $red->execute([$registrationId, 'staff', null, $staffVal]);

        $pdo->prepare("UPDATE registrations SET staff_commission_status = 'pending', updated_at = NOW() WHERE id = ?")
            ->execute([$registrationId]);

        // Parent 3 bulan.
        $parentRule = $pdo->prepare(
            "SELECT r.value, r.duration_count FROM promo_rules r
             JOIN promos p ON p.id = r.promo_id
             WHERE p.type = 'daycare' AND r.beneficiary = 'parent' AND r.branch_id IS NULL
             LIMIT 1"
        );
        $parentRule->execute();
        $parentRow = $parentRule->fetch();
        $parentVal    = $parentRow ? (float) $parentRow['value'] : 0;
        $duration     = $parentRow && $parentRow['duration_count'] ? (int) $parentRow['duration_count'] : 3;

        for ($month = 1; $month <= $duration; $month++) {
            $red->execute([$registrationId, 'parent', $month, $parentVal]);
        }
    }

    /**
     * Event "visit_completed" (khusus klinik): catat redemption parent per kunjungan.
     *
     * Harga diambil STRICT dari rule cabang target registrasi. Jika cabang
     * belum dikonfigurasi -> return array pesan error (response 422), tanpa
     * insert redemption, tanpa fallback ke rule cabang lain/global.
     */
    private function handleVisitCompleted($pdo, array $payload, string $code, ?string $phone, string $promoType): ?array {
        // Hanya klinik.
        if ($promoType !== 'klinik') {
            return ['message' => 'visit_completed hanya berlaku untuk promo_type klinik'];
        }

        $visitNumber = filter_var($payload['visit_number'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($visitNumber === false || $visitNumber === null) {
            return ['message' => 'visit_number wajib berupa integer >= 1'];
        }

        // Ambil registrasi terkait.
        $regStmt = $pdo->prepare(
            "SELECT r.id, r.branch_id_target
             FROM registrations r
             WHERE r.referral_code_used = ? AND r.parent_phone = ? AND r.promo_type = 'klinik'
             ORDER BY r.created_at DESC, r.id DESC LIMIT 1"
        );
        $regStmt->execute([$code, $phone]);
        $reg = $regStmt->fetch();
        if (!$reg) {
            return ['message' => 'Registrasi klinik tidak ditemukan untuk referral_code + parent_phone ini'];
        }

        if (!$reg['branch_id_target']) {
            return ['message' => 'Registrasi klinik tidak memiliki branch_id_target; harga promo klinik tidak bisa ditentukan'];
        }

        // Rule STRICT per cabang: promo=klinik, parent, branch_id TEPAT. Tanpa fallback.
        $ruleStmt = $pdo->prepare(
            "SELECT r.value, r.visit_range_start, r.visit_range_end
             FROM promo_rules r
             JOIN promos p ON p.id = r.promo_id
             WHERE p.type = 'klinik' AND r.beneficiary = 'parent'
               AND r.branch_id = ?
             LIMIT 1"
        );
        $ruleStmt->execute([(int) $reg['branch_id_target']]);
        $rule = $ruleStmt->fetch();

        // Cabang belum dikonfigurasi -> 422.
        if (!$rule) {
            return ['message' => "harga promo klinik belum diatur untuk cabang ini (branch_id={$reg['branch_id_target']})"];
        }

        $value = 0.0;
        if ($visitNumber >= (int) $rule['visit_range_start'] && $visitNumber <= (int) $rule['visit_range_end']) {
            $value = (float) $rule['value'];
        }
        // Di luar range -> tetap insert untuk histori, value 0 (harga normal).

        $red = $pdo->prepare(
            "INSERT INTO promo_redemptions (registration_id, beneficiary, period_or_visit_number, value_applied, status)
             VALUES (?, 'parent', ?, ?, 'tercatat')"
        );
        $red->execute([$reg['id'], $visitNumber, $value]);

        return null;
    }

    /**
     * Event "cancelled": batal.
     * staff_commission_status jadi 'batal' HANYA jika sebelumnya bukan 'tidak_ada'.
     * Tidak menghapus promo_redemptions yang sudah ada.
     */
    private function handleCancelled($pdo, string $code, ?string $phone): void {
        $regStmt = $pdo->prepare(
            "SELECT r.id, r.staff_commission_status
             FROM registrations r
             WHERE r.referral_code_used = ? AND r.parent_phone = ?
             ORDER BY r.created_at DESC, r.id DESC LIMIT 1"
        );
        $regStmt->execute([$code, $phone]);
        $reg = $regStmt->fetch();
        if (!$reg) {
            throw new \RuntimeException('Cancelled: registrasi tidak ditemukan');
        }

        $newStatus = $reg['staff_commission_status'] === 'tidak_ada' ? 'tidak_ada' : 'batal';
        $pdo->prepare("UPDATE registrations SET enrollment_status = 'batal', staff_commission_status = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$newStatus, $reg['id']]);
    }
}