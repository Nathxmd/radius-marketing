<?php
/**
 * referral-push-trigger.php — trigger push keluar via HTTP (sekali pakai).
 *
 * Memanggil resyncAllReferrals() (push semua staff aktif ke aplikasi eksternal).
 *
 * Keamanan: WAJIB header X-Signature = hex(hmac_sha256(RawBody, REFERRAL_SHARED_SECRET)).
 * Untuk GET/POST tanpa body, gunakan signature dari string kosong.
 *
 * Contoh (PowerShell):
 *   $secret = "<REFERRAL_SHARED_SECRET>"
 *   $sig = [System.BitConverter]::ToString((New-Object System.Security.Cryptography.HMACSHA256).
 *        ([System.Text.Encoding]::UTF8.GetBytes($secret)).
 *        ComputeHash([System.Text.Encoding]::UTF8.GetBytes(''))).Replace('-','').ToLower()
 *   curl.exe -s "https://radiusmarketing.rumahbiru.id/referral-push-trigger.php" -H "X-Signature: $sig"
 *
 * Setelah selesai testing, HAPUS file ini dari server.
 */
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';

$raw = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';

if (REFERRAL_SHARED_SECRET === '' || $signature === '') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Missing signature']);
    return;
}

$valid = hash_equals(
    hash_hmac('sha256', $raw, REFERRAL_SHARED_SECRET),
    $signature
);

if (!$valid) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
    return;
}

require_once __DIR__ . '/referral-resync-all.php';
$result = resyncAllReferrals($pdo);

echo json_encode([
    'status'  => 'ok',
    'message' => 'Push referral selesai',
    'total'   => $result['total'],
    'success' => $result['success'],
    'failed'  => $result['failed'],
]);