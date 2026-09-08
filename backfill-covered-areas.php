<?php
/**
 * backfill-covered-areas.php
 *
 * Mengisi (backfill) tabel covered_areas — daftar kelurahan/kecamatan dalam
 * radius 5 km — untuk cabang yang SUDAH punya koordinat tapi belum pernah
 * dihitung (mis. cabang ditambahkan langsung lewat SQL sehingga melewati
 * BranchController::store() yang otomatis menghitung saat create).
 *
 * Pemakaian:
 *   php backfill-covered-areas.php          # isi cabang yang masih kosong saja
 *   php backfill-covered-areas.php all      # hitung ulang SEMUA cabang berkoordinat
 *
 * Detail error dicatat ke error_log oleh geo-helpers (queryBIGWilayah).
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/Helpers/geo-helpers.php';

$force = ($argv[1] ?? '') === 'all';

if ($force) {
    $sql = "SELECT id, name, latitude, longitude FROM branches
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL
            ORDER BY id";
} else {
    $sql = "SELECT id, name, latitude, longitude FROM branches
            WHERE latitude IS NOT NULL AND longitude IS NOT NULL
              AND NOT EXISTS (SELECT 1 FROM covered_areas ca WHERE ca.branch_id = branches.id)
            ORDER BY id";
}

$branches = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo '[' . date('Y-m-d H:i:s') . "] Backfill covered_areas: " . count($branches)
    . ' cabang diproses (' . ($force ? 'mode: all' : 'mode: hanya yang kosong') . ")\n";

$insertStmt = $pdo->prepare(
    "INSERT INTO covered_areas (branch_id, area_name, area_type, kecamatan, kabupaten_kota, provinsi, distance_km, source)
     VALUES (?, ?, ?, ?, ?, ?, ?, 'polygon')"
);
$deleteStmt = $pdo->prepare("DELETE FROM covered_areas WHERE branch_id = ?");

$successCount = 0;
$failCount = 0;

foreach ($branches as $branch) {
    $branchName = $branch['name'] . ' (id ' . $branch['id'] . ')';
    $wilayah = getWilayahDalamRadius(
        (float) $branch['latitude'],
        (float) $branch['longitude'],
        5000,
        true
    );

    if (empty($wilayah)) {
        echo "  [GAGAL] {$branchName} — BIG Geoservice tidak mengembalikan data. Lihat error_log.\n";
        $failCount++;
        sleep(1);
        continue;
    }

    // Hapus data lama dulu hanya jika mode all dan hasil baru valid,
    // supaya data lama tidak hilang saat layanan sedang error.
    if ($force) {
        $deleteStmt->execute([$branch['id']]);
    }

    foreach ($wilayah as $area) {
        $insertStmt->execute([
            $branch['id'],
            $area['area_name'],
            $area['area_type'],
            $area['kecamatan'] ?? null,
            $area['kabupaten_kota'] ?? null,
            $area['provinsi'] ?? null,
            $area['distance_km'] ?? null,
        ]);
    }

    echo "  [OK] {$branchName} — " . count($wilayah) . " wilayah\n";
    $successCount++;
    sleep(1); // sopan ke BIG Geoservice (tidak ada rate limit resmi)
}

echo '[' . date('Y-m-d H:i:s') . "] Selesai: {$successCount} berhasil, {$failCount} gagal.\n";
exit($failCount > 0 ? 1 : 0);
