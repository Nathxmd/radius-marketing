<?php
/**
 * referral-resync-all.php
 *
 * Push ulang (resync) SEMUA staff aktif ke aplikasi eksternal.
 *
 * Dipakai:
 *  - Otomatis oleh halaman admin "Pengaturan Promo" setelah menyimpan
 *    perubahan (termasuk harga per cabang untuk klinik).
 *  - Manual lewat tombol "Resync Semua Aktif" di halaman Daftar Staff.
 *  - CLI: php referral-resync-all.php
 *
 * @return array ['total' => int, 'success' => int, 'failed' => int]
 */
function resyncAllReferrals(PDO $pdo): array
{
    require_once __DIR__ . '/referral-push.php';

    $stmt = $pdo->query(
        "SELECT id, name, referral_code, branch_id, status
         FROM staff
         WHERE status = 'aktif'
         ORDER BY id"
    );
    $staffList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total   = count($staffList);
    $success = 0;
    $failed  = 0;

    foreach ($staffList as $staff) {
        if (pushReferralToExternalApp($pdo, $staff)) {
            $success++;
        } else {
            $failed++;
        }
    }

    return ['total' => $total, 'success' => $success, 'failed' => $failed];
}

// Saat dijalankan langsung sebagai script CLI, eksekusi + cetak ringkasan.
if (PHP_SAPI === 'cli' && (realpath($argv[0] ?? '') === __FILE__)) {
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/app.php';

    $result = resyncAllReferrals($pdo);
    echo '[' . date('Y-m-d H:i:s') . '] Resync: '
       . $result['total'] . ' total, '
       . $result['success'] . ' berhasil, '
       . $result['failed'] . ' gagal.' . PHP_EOL;
    exit($result['failed'] > 0 ? 1 : 0);
}