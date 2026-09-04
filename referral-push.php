<?php
/**
 * referral-push.php
 *
 * Mengirim (push) data kode referral staff ke aplikasi eksternal setiap kali
 * staff ditambah/diedit. Setiap percobaan push dicatat ke referral_push_logs
 * supaya bisa di-retry manual/otomatis kalau gagal.
 *
 * KONFIGURASI (sesuaikan / pindahkan ke file config terpisah):
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
if (!defined('EXTERNAL_APP_PUSH_URL') || !defined('REFERRAL_SHARED_SECRET')) {
    throw new RuntimeException('Konfigurasi referral belum dimuat');
}

/**
 * Push satu data staff ke aplikasi eksternal.
 *
 * @param PDO $pdo
 * @param array $staff ['id'=>, 'name'=>, 'referral_code'=>, 'branch_id'=>, 'status'=>]
 * @return bool true jika berhasil (HTTP 2xx), false jika gagal
 */
function pushReferralToExternalApp(PDO $pdo, array $staff): bool
{
    $payload = json_encode([
        'event' => 'referral.sync',
        'data'  => [
            'staff_id'      => $staff['id'],
            'name'          => $staff['name'],
            'referral_code' => $staff['referral_code'],
            'branch_id'     => $staff['branch_id'],
            'status'        => $staff['status'], // 'aktif' | 'nonaktif'
        ],
        'timestamp' => date('c'),
    ]);

    // Tanda tangani payload supaya aplikasi eksternal bisa verifikasi ini benar dari kita
    if (!$payload || EXTERNAL_APP_PUSH_URL === '' || REFERRAL_SHARED_SECRET === '') {
        $error = 'Konfigurasi push referral belum lengkap';
        $response = null;
        $httpCode = 0;
        $success = false;
    } else {
        $signature = hash_hmac('sha256', $payload, REFERRAL_SHARED_SECRET);

        $ch = curl_init(EXTERNAL_APP_PUSH_URL);
        curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-Api-Key: ' . EXTERNAL_APP_API_KEY,
            'X-Signature: ' . $signature,
        ],
        ]);

        if (defined('CURL_CAINFO') && CURL_CAINFO !== '') curl_setopt($ch, CURLOPT_CAINFO, CURL_CAINFO);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        $success = ($httpCode >= 200 && $httpCode < 300);
    }

    // Log setiap percobaan push -> penting untuk debugging & basis retry
    $stmt = $pdo->prepare(
        "INSERT INTO referral_push_logs (staff_id, payload, http_status, response_body, success, error_message, created_at)
         VALUES (:staff_id, :payload, :http_status, :response_body, :success, :error_message, NOW())"
    );
    $stmt->execute([
        'staff_id'      => $staff['id'],
        'payload'       => $payload,
        'http_status'   => $httpCode,
        'response_body' => $response ?: null,
        'success'       => $success ? 1 : 0,
        'error_message' => $error ?: null,
    ]);

    if (!$success) {
        error_log("Push referral gagal untuk staff_id={$staff['id']}: HTTP {$httpCode} - {$error}");
    }

    return $success;
}

/**
 * CONTOH PEMAKAIAN — panggil ini setelah INSERT/UPDATE staff berhasil.
 *
 * $staff = [
 *     'id' => $newStaffId,
 *     'name' => $_POST['name'],
 *     'referral_code' => $generatedCode,
 *     'branch_id' => $_POST['branch_id'],
 *     'status' => 'aktif',
 * ];
 * pushReferralToExternalApp($pdo, $staff);
 *
 * PENTING: jangan biarkan proses simpan staff GAGAL total hanya karena push
 * ke aplikasi eksternal gagal/timeout. Simpan staff dulu ke DB kita, baru push
 * -- kalau push gagal, itu ter-log dan bisa di-retry belakangan (lihat
 * referral-push-retry.php), user (admin) tidak perlu tahu/terganggu saat itu juga.
 */
