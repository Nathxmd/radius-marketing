-- Jalankan sekali pada database radius_sistem yang sudah berjalan.
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    employee_code VARCHAR(60) NULL UNIQUE,
    role VARCHAR(80) NOT NULL DEFAULT 'staff',
    branch_id INT NULL,
    referral_code VARCHAR(40) NOT NULL UNIQUE,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_staff_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    external_event_id VARCHAR(100) NOT NULL UNIQUE,
    staff_id INT NULL,
    referral_code_used VARCHAR(40) NOT NULL,
    parent_name VARCHAR(150), parent_phone VARCHAR(30), child_name VARCHAR(150),
    branch_id_target INT NULL,
    enrollment_status ENUM('lead', 'enrolled', 'batal') NOT NULL DEFAULT 'lead',
    commission_status ENUM('belum_ada', 'pending', 'disetujui', 'dibayar', 'batal') NOT NULL DEFAULT 'belum_ada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reg_staff FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL,
    CONSTRAINT fk_reg_branch FOREIGN KEY (branch_id_target) REFERENCES branches(id) ON DELETE SET NULL,
    INDEX idx_regerral_code (referral_code_used), INDEX idx_reg_parent_phone (parent_phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS referral_push_logs (
    id INT AUTO_INCREMENT PRIMARY KEY, staff_id INT NOT NULL, payload TEXT NOT NULL,
    http_status SMALLINT NULL, response_body TEXT, success TINYINT(1) NOT NULL DEFAULT 0,
    error_message TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_push_staff FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE,
    INDEX idx_push_staff_created (staff_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS webhook_logs (
    id INT AUTO_INCREMENT PRIMARY KEY, source VARCHAR(80) NOT NULL, raw_payload TEXT NOT NULL,
    signature_valid TINYINT(1) NOT NULL DEFAULT 0, event_id VARCHAR(100), referral_code VARCHAR(40),
    event_type VARCHAR(30), processed TINYINT(1) NOT NULL DEFAULT 0, error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_webhook_event (event_id),
    INDEX idx_webhook_created (created_at), INDEX idx_webhook_signature (signature_valid, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS referral_settings (
    id TINYINT PRIMARY KEY, reward_type ENUM('persen', 'nominal') NOT NULL DEFAULT 'nominal',
    discount_value_parent DECIMAL(12,2) NOT NULL DEFAULT 0,
    commission_value_staff DECIMAL(12,2) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO referral_settings (id) VALUES (1) ON DUPLICATE KEY UPDATE id = id;
