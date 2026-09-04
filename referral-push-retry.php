<?php
/**
 * referral-push-retry.php
 *
 * Jalankan via cron (misal setiap 15 menit) untuk retry push yang gagal.
 * Contoh crontab: (asumsi PHP CLI tersedia di server)
 *   /15 * * * * php /path/to/referral-push-retry.php >> /var/log/referral-retry.log 2>&1
 */

require_once __DIR__ . '/referral-push.php';

// Ambil staff yang percobaan push TERAKHIRnya gagal, belum ada percobaan sukses SETELAHNYA
$stmt = $pdo->query("
    SELECT s.id, s.name, s.referral_code, s.branch_id, s.status
    FROM staff s
    WHERE s.status = 'aktif' AND s.id IN (
        SELECT staff_id FROM referral_push_logs rpl1
        WHERE rpl1.created_at = (
            SELECT MAX(created_at) FROM referral_push_logs rpl2
            WHERE rpl2.staff_id = rpl1.staff_id
        )
        AND rpl1.success = 0
    )
");

$staffList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$successCount = 0;
$failCount    = 0;

foreach ($staffList as $staff) {
    $ok = pushReferralToExternalApp($pdo, $staff);
    if ($ok) {
        $successCount++;
    } else {
        $failCount++;
    }
    usleep(300000); // jeda 0.3 detik antar request, jangan banjiri server eksternal
}

echo "[" . date('Y-m-d H:i:s') . "] Retry selesai: {$successCount} berhasil, {$failCount} masih gagal.\n";

// SARAN: kalau $failCount tetap tinggi terus-menerus, tambahkan notifikasi
// (email/Slack) ke tim IT supaya ada yang cek kenapa aplikasi eksternal
// terus menolak/tidak merespons.
