-- ============================================================
-- commercial_insights_module.sql
-- Modul: Insight Area Komersial/Perkantoran (Overpass API / OpenStreetMap)
--
-- Tambahkan/migrasikan skema di atas database radius_sistem yang sudah
-- berjalan. Aman dijalankan ulang (idempotent).
--
-- Satu cabang = satu baris insight (UNIQUE branch_id) yang di-replace setiap
-- re-sync; row ini dihapus otomatis saat cabangnya dihapus (ON DELETE CASCADE).
--
-- fetch_status:
--   'berhasil' = Overpass merespons & ada POI
--   'kosong'   = Overpass merespons tapi area belum terpetakan di OSM (bukan error)
--   'gagal'    = request Overpass gagal (timeout/down) — angka & daftar dari sync
--                terakhir yang berhasil sengaja dipertahankan, UI hanya menampilkan
--                pesan gagal + tombol retry selama status ini.
-- ============================================================

CREATE TABLE IF NOT EXISTS commercial_insights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch_id INT NOT NULL,
    jumlah_kantor INT NOT NULL DEFAULT 0,
    ada_area_komersial TINYINT(1) NOT NULL DEFAULT 0,
    ada_area_industri TINYINT(1) NOT NULL DEFAULT 0,
    daftar_kantor_bernama JSON NULL,
    fetch_status ENUM('berhasil', 'gagal', 'kosong') NOT NULL DEFAULT 'gagal',
    last_synced_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_commercial_insight_branch (branch_id),
    CONSTRAINT fk_commercial_insights_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
