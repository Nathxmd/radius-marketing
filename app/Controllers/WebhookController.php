<?php

namespace App\Controllers;

class WebhookController {
    public function referralStatus() {
        global $pdo;
        $raw = file_get_contents('php://input');
        if (strlen($raw) > 1048576) { http_response_code(413); echo json_encode(['status' => 'error', 'message' => 'Payload too large']); return; }
        $payload = json_decode($raw, true);
        $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
        $valid = REFERRAL_SHARED_SECRET !== '' && $signature !== '' && hash_equals(hash_hmac('sha256', $raw, REFERRAL_SHARED_SECRET), $signature);
        $eventId = is_array($payload) ? trim((string) ($payload['event_id'] ?? '')) : '';
        $code = is_array($payload) ? strtoupper(trim((string) ($payload['referral_code'] ?? ''))) : '';
        $type = is_array($payload) ? trim((string) ($payload['event_type'] ?? '')) : '';
        $phone = is_array($payload) ? sanitizeParentPhone($payload['parent_phone'] ?? null) : null;
        $source = 'external_app:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $log = function ($processed, $error) use ($pdo, $source, $raw, $valid, $eventId, $code, $type) { $stmt = $pdo->prepare("INSERT INTO webhook_logs (source, raw_payload, signature_valid, event_id, referral_code, event_type, processed, error_message) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"); $stmt->execute([$source, $raw, $valid ? 1 : 0, $eventId ?: null, $code ?: null, $type ?: null, $processed ? 1 : 0, $error]); };
        header('Content-Type: application/json');
        if (!$valid) { $log(false, 'Signature tidak valid'); $stmt = $pdo->prepare("SELECT COUNT(*) FROM webhook_logs WHERE signature_valid = 0 AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND source = ?"); $stmt->execute([$source]); if ((int) $stmt->fetchColumn() >= 5) error_log('[Webhook] Banyak signature invalid dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')); http_response_code(401); echo json_encode(['status' => 'error', 'message' => 'Invalid signature']); return; }
        if ($eventId === '' || !validReferralCode($code) || !in_array($type, ['used', 'enrolled', 'cancelled'], true) || (($type !== 'used') && !$phone)) { $log(false, 'Field wajib atau format payload tidak valid'); http_response_code(400); echo json_encode(['status' => 'error', 'message' => 'Invalid payload']); return; }
        try {
            $check = $pdo->prepare('SELECT id FROM webhook_logs WHERE event_id = ? AND processed = 1 LIMIT 1'); $check->execute([$eventId]); if ($check->fetch()) { $log(true, 'Already processed'); echo json_encode(['status' => 'ok', 'message' => 'Already processed']); return; }
            $staffStmt = $pdo->prepare("SELECT id FROM staff WHERE referral_code = ? AND status = 'aktif' LIMIT 1"); $staffStmt->execute([$code]); $staff = $staffStmt->fetch(); if (!$staff) { $log(false, 'Kode referral tidak ditemukan/tidak aktif'); echo json_encode(['status' => 'ok', 'message' => 'Referral code not found, logged for review']); return; }
            $pdo->beginTransaction();
            if ($type === 'used') { $stmt = $pdo->prepare("INSERT INTO registrations (external_event_id, staff_id, referral_code_used, parent_name, parent_phone, child_name, branch_id_target, enrollment_status, commission_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'lead', 'belum_ada')"); $branch = filter_var($payload['branch_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null; if ($branch) { $branchCheck = $pdo->prepare('SELECT id FROM branches WHERE id = ?'); $branchCheck->execute([$branch]); if (!$branchCheck->fetch()) $branch = null; } $stmt->execute([$eventId, $staff['id'], $code, trim((string) ($payload['parent_name'] ?? '')) ?: null, $phone, trim((string) ($payload['child_name'] ?? '')) ?: null, $branch]); }
            else { $status = $type === 'enrolled' ? 'enrolled' : 'batal'; $commission = $type === 'enrolled' ? 'pending' : 'batal'; $stmt = $pdo->prepare("UPDATE registrations SET enrollment_status = ?, commission_status = ?, updated_at = NOW() WHERE referral_code_used = ? AND parent_phone = ? ORDER BY created_at DESC LIMIT 1"); $stmt->execute([$status, $commission, $code, $phone]); }
            $log(true, null); $pdo->prepare('UPDATE webhook_logs SET processed = 1 WHERE event_id = ? AND processed = 0 ORDER BY id DESC LIMIT 1')->execute([$eventId]); $pdo->commit(); echo json_encode(['status' => 'ok']);
        } catch (\Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); error_log('[Webhook] ' . $e->getMessage()); $log(false, $e->getMessage()); http_response_code(500); echo json_encode(['status' => 'error', 'message' => 'Internal error']); }
    }
}