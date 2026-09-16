<?php

namespace App\Models;

use PDO;

class CommercialInsight {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getByBranch(int $branchId): ?array {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM commercial_insights WHERE branch_id = ? LIMIT 1");
            $stmt->execute([$branchId]);
            $row = $stmt->fetch();
        } catch (\PDOException $e) {
            // Tabel belum ada — biasanya karena database/commercial_insights_module.sql
            // belum dijalankan di server. Halaman detail cabang tetap bisa dibuka
            // (card insight tampil sebagai "belum ada data"), dan penyebabnya
            // tercatat di error_log.
            error_log("[CommercialInsight] gagal membaca branch_id={$branchId}: " . $e->getMessage());
            return null;
        }
        return $row ?: null;
    }

    /**
     * Simpan hasil fetch yang berhasil (status 'berhasil' atau 'kosong').
     * Replace seluruh angka + daftar kantor, sesuai pola satu baris per cabang.
     */
    public function saveResult(int $branchId, string $status, array $ringkasan): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO commercial_insights
                (branch_id, jumlah_kantor, ada_area_komersial, ada_area_industri, daftar_kantor_bernama, fetch_status, last_synced_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                jumlah_kantor = VALUES(jumlah_kantor),
                ada_area_komersial = VALUES(ada_area_komersial),
                ada_area_industri = VALUES(ada_area_industri),
                daftar_kantor_bernama = VALUES(daftar_kantor_bernama),
                fetch_status = VALUES(fetch_status),
                last_synced_at = NOW()
        ");
        $stmt->execute([
            $branchId,
            (int) ($ringkasan["jumlah_kantor"] ?? 0),
            !empty($ringkasan["ada_area_komersial"]) ? 1 : 0,
            !empty($ringkasan["ada_area_industri"]) ? 1 : 0,
            json_encode(array_values($ringkasan["daftar_kantor_bernama"] ?? []), JSON_UNESCAPED_UNICODE),
            $status,
        ]);
    }

    /**
     * Tandai bahwa fetch ke Overpass gagal (timeout / layanan down).
     *
     * Angka & daftar kantor dari sync terakhir yang berhasil SENGAJA tidak dihapus,
     * supaya data lama tidak hilang hanya karena Overpass sedang tidak bisa diakses.
     * Selama fetch_status='gagal', UI tidak menampilkan angka lama itu — hanya pesan
     * gagal + tombol retry (lihat views/branch/detail.php).
     */
    public function markFailed(int $branchId): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO commercial_insights (branch_id, fetch_status, last_synced_at)
            VALUES (?, 'gagal', NOW())
            ON DUPLICATE KEY UPDATE fetch_status = 'gagal', last_synced_at = NOW()
        ");
        $stmt->execute([$branchId]);
    }
}
