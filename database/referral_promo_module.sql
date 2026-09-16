-- ============================================================
-- referral_promo_module.sql
-- Modul: Kode Referral Karyawan dengan Promo Daycare & Klinik
-- Tambahkan/migrasikan skema di atas database radius_sistem yang
-- sudah berjalan (tabel staff, registrations, branches telah ada).
--
-- Semua migrasi memakai prepared ALTER/INSERT yang aman dijalankan
-- ulang (idempotent) pada MySQL 8/MariaDB.
-- ============================================================

-- ------------------------------------------------------------
-- 1) registrations: tambah promo_type + staff_commission_status
--    Kolom lama `commission_status` tetap dibiarkan (backward compat).
-- ------------------------------------------------------------
SET @col_promo_type = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'registrations'
      AND column_name = 'promo_type'
);
SET @sql_promo_type = IF(@col_promo_type = 0,
    "ALTER TABLE registrations ADD COLUMN promo_type ENUM('daycare','klinik') NULL AFTER branch_id_target",
    'SELECT 1'
);
PREPARE st FROM @sql_promo_type; EXECUTE st; DEALLOCATE PREPARE st;

SET @col_staff_commission = (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'registrations'
      AND column_name = 'staff_commission_status'
);
SET @sql_staff_commission = IF(@col_staff_commission = 0,
    "ALTER TABLE registrations ADD COLUMN staff_commission_status ENUM('belum_ada','tidak_ada','pending','disetujui','dibayar') NOT NULL DEFAULT 'belum_ada' AFTER commission_status",
    'SELECT 1'
);
PREPARE st FROM @sql_staff_commission; EXECUTE st; DEALLOCATE PREPARE st;

-- ------------------------------------------------------------
-- 2) promos  (jenis promo; seed daycare + klinik)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS promos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('daycare','klinik') NOT NULL UNIQUE,
    active TINYINT(1) NOT NULL DEFAULT 1,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO promos (type, active, description) VALUES
    ('daycare', 1, 'Promo daycare: diskon parent + komisi staff'),
    ('klinik', 1, 'Promo klinik: harga spesial per kunjungan, tidak ada komisi staff')
ON DUPLICATE KEY UPDATE type = VALUES(type);

-- ------------------------------------------------------------
-- 3) promo_rules  (aturan per promo, per penerima, opsional per cabang)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS promo_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    promo_id INT NOT NULL,
    beneficiary ENUM('parent','staff') NOT NULL,
    rule_type ENUM('recurring_monthly','one_time','per_visit_tiered') NOT NULL,
    value DECIMAL(12,2) NOT NULL DEFAULT 0,
    duration_count INT NULL,
    visit_range_start INT NULL,
    visit_range_end INT NULL,
    branch_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_promo_rules_promo FOREIGN KEY (promo_id) REFERENCES promos(id) ON DELETE CASCADE,
    CONSTRAINT fk_promo_rules_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
    UNIQUE KEY uq_promo_rule (promo_id, beneficiary, rule_type, branch_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed: cari id branches "Bekasi" dan "Kemang" dari tabel branches yang ada.
-- Daycare -> global (branch_id NULL). Idempotent via NOT EXISTS (NULL unique
-- di MySQL tidak menangkap duplikat, jadi NULL branch_id butuh penjagaan manual).
INSERT INTO promo_rules (promo_id, beneficiary, rule_type, value, duration_count, visit_range_start, visit_range_end, branch_id)
SELECT p.id, 'parent', 'recurring_monthly', 500000, 3, NULL, NULL, NULL
FROM promos p
WHERE p.type = 'daycare'
  AND NOT EXISTS (
    SELECT 1 FROM promo_rules r
    WHERE r.promo_id = p.id AND r.beneficiary = 'parent' AND r.branch_id IS NULL
  );

INSERT INTO promo_rules (promo_id, beneficiary, rule_type, value, duration_count, visit_range_start, visit_range_end, branch_id)
SELECT p.id, 'staff', 'one_time', 250000, NULL, NULL, NULL, NULL
FROM promos p
WHERE p.type = 'daycare'
  AND NOT EXISTS (
    SELECT 1 FROM promo_rules r
    WHERE r.promo_id = p.id AND r.beneficiary = 'staff' AND r.branch_id IS NULL
  );

-- Klinik -> per_visit_tiered, override per cabang (Bekasi = 150000, Kemang = 250000).
-- SENG AJA TIDAK ADA rule staff untuk klinik.
-- Pencocokan cabang cek ATAU name ATAU city (nama cabang "Daycare Harapan Indah"
-- punya kata "Bekasi" di kolom city, bukan name).
--
-- Validasi: kalau cabang indikasi tidak ketemu sama sekali, migrasi WAJIB
-- gagal & berhenti (error jelas), JANGAN diam-diam skip. SIGNAL tidak didukung
-- di script top-level, jadi pakai PREPARE/EXECUTE statement sengaja-invalid.
-- ---------------------------------------------------------------------------
SET @bekasi_found = (
    SELECT COUNT(*) FROM branches
    WHERE LOWER(name) LIKE '%bekasi%' OR LOWER(city) LIKE '%bekasi%'
);
SET @bekasi_check = IF(
    IFNULL(@bekasi_found, 0) > 0,
    'SELECT 1',
    'SELECT provoke_error_abort @x'
);
PREPARE s_bekasi FROM @bekasi_check;
EXECUTE s_bekasi;
DEALLOCATE PREPARE s_bekasi;

INSERT INTO promo_rules (promo_id, beneficiary, rule_type, value, duration_count, visit_range_start, visit_range_end, branch_id)
SELECT p.id, 'parent', 'per_visit_tiered', 150000, NULL, 1, 4, b.id
FROM promos p CROSS JOIN branches b
WHERE p.type = 'klinik' AND (LOWER(b.name) LIKE '%bekasi%' OR LOWER(b.city) LIKE '%bekasi%')
  AND NOT EXISTS (
    SELECT 1 FROM promo_rules r
    WHERE r.promo_id = p.id AND r.beneficiary = 'parent' AND r.branch_id = b.id
  );

SET @kemang_found = (
    SELECT COUNT(*) FROM branches
    WHERE LOWER(name) LIKE '%kemang%' OR LOWER(city) LIKE '%kemang%'
);
SET @kemang_check = IF(
    IFNULL(@kemang_found, 0) > 0,
    'SELECT 1',
    'SELECT provoke_error_abort @x'
);
PREPARE s_kemang FROM @kemang_check;
EXECUTE s_kemang;
DEALLOCATE PREPARE s_kemang;

INSERT INTO promo_rules (promo_id, beneficiary, rule_type, value, duration_count, visit_range_start, visit_range_end, branch_id)
SELECT p.id, 'parent', 'per_visit_tiered', 250000, NULL, 1, 4, b.id
FROM promos p CROSS JOIN branches b
WHERE p.type = 'klinik' AND (LOWER(b.name) LIKE '%kemang%' OR LOWER(b.city) LIKE '%kemang%')
  AND NOT EXISTS (
    SELECT 1 FROM promo_rules r
    WHERE r.promo_id = p.id AND r.beneficiary = 'parent' AND r.branch_id = b.id
  );

-- ------------------------------------------------------------
-- 4) promo_redemptions  (manfaat promo yang diterapkan/dipicu)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS promo_redemptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    beneficiary ENUM('parent','staff') NOT NULL,
    period_or_visit_number INT NULL,
    value_applied DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('tercatat','disetujui','dibayar') NOT NULL DEFAULT 'tercatat',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_redemptions_reg FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
    INDEX idx_redemptions_reg (registration_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;