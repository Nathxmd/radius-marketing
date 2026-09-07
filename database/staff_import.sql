-- Import staff/guru dari teachers.csv.
-- Kode referral dibuat unik untuk tiap staff. Integrasi aplikasi luar belum dipanggil.
-- Jalankan setelah staff_employee_code_migration.sql.

INSERT INTO staff (name, employee_code, role, referral_code, status)
VALUES
('Rendi Suryadi','EMP-1','guru','REND-0001','aktif'),
('Rita Rosanti','EMP-2','guru','RITA-0002','aktif'),
('Jeehan Humaira','EMP-4','guru','JEEH-0004','aktif'),
('Yolandari','EMP-18','guru','YOLA-0018','aktif'),
('Rangga Satria Wardhana','EMP-32','guru','RANG-0032','aktif'),
('Rini Indriani','EMP-50','guru','RINI-0050','aktif'),
('Erna Rahayuningsih','EMP-54','guru','ERNA-0054','aktif'),
('Widya Salsabila Hamidah','EMP-58','guru','WIDY-0058','aktif'),
('Mici Ardila','EMP-61','guru','MICI-0061','aktif'),
('Jesikaratna Sari','EMP-39','guru','JESI-0039','aktif'),
('Irin','EMP-62','guru','IRIN-0062','aktif'),
('Nurkholis Majid','EMP-63','guru','NURK-0063','aktif'),
('Denisa Nur Rizki','EMP-65','guru','DENI-0065','aktif'),
('Yuli Yanti','EMP-73','guru','YULI-0073','aktif'),
('Marthila Wahyu Novita','EMP-74','guru','MART-0074','aktif'),
('Lintang Hari Tanhanasashi Purnama','EMP-76','guru','LINT-0076','aktif'),
('Diki Aryanto','EMP-82','guru','DIKI-0082','aktif'),
('Thariq Aulia Al Hakim','EMP-84','guru','THAR-0084','aktif'),
('Maulana Riksa','EMP-88','guru','MAUL-0088','aktif'),
('Nina Irvani','EMP-89','guru','NINA-0089','aktif'),
('Andri Susanto','EMP-95','guru','ANDR-0095','aktif'),
('Luthvinia Alawiah','EMP-96','guru','LUTH-0096','aktif'),
('Nella Aulia Asyadah','EMP-104','guru','NELL-0104','aktif'),
('Adistiyani','EMP-107','guru','ADIS-0107','aktif'),
('Bagas Dany Aradhana','EMP-108','guru','BAGA-0108','aktif'),
('Isna Fatihatul Mujahidah','EMP-37','guru','ISNA-0037','aktif'),
('Arissa Zahwa Ramadhani','EMP-118','guru','ARIS-0118','aktif'),
('Muthia Syafwa Nabillah','EMP-121','guru','MUTH-0121','aktif'),
('Dani','EMP-122','guru','DANI-0122','aktif'),
('Hanifah Nuraviantari Putri Hendarto','EMP-124','guru','HANI-0124','aktif'),
('Naja Eiko Khalisa','EMP-7','guru','NAJA-0007','aktif'),
('Ratu Balqis Fatya Pebrinda Lubis','EMP-128','guru','RATU-0128','aktif'),
('Hikmah Nuraini','EMP-130','guru','HIKM-0130','aktif'),
('Bayu Aji Prihantoro','EMP-132','guru','BAYU-0132','aktif'),
('Ade Sekar Fabry Lianda','EMP-134','guru','ADE-0134','aktif'),
('Alia Aprilina','EMP-135','guru','ALIA-0135','aktif'),
('Yuni Adelia','EMP-136','guru','YUNI-0136','aktif'),
('Destiana Praditya Ningrum','EMP-137','guru','DEST-0137','aktif'),
('Liena Asma " Abiedatul Mufiedah','EMP-138','guru','LIEN-0138','aktif'),
('Najwa Nuraini','EMP-139','guru','NAJW-0139','aktif'),
('Nikita Pramodya Az - Zahra','EMP-140','guru','NIKI-0140','aktif'),
('Novilla Viandra','EMP-142','guru','NOVI-0142','aktif'),
('Nur Annisa','EMP-143','guru','NURA-0143','aktif'),
('Rahmania Fionna','EMP-144','guru','RAHM-0144','aktif'),
('Nathan Mahesa Dewanto','EMP-145','guru','NATH-0145','aktif'),
('Aulia Shafira','EMP-146','guru','AULI-0146','aktif'),
('Salfira Salsabila','EMP-147','guru','SALF-0147','aktif'),
('Valerie Nathaviana Ndun','EMP-148','guru','VALE-0148','aktif')
ON DUPLICATE KEY UPDATE name = VALUES(name), role = VALUES(role), status = VALUES(status);

INSERT INTO staff (name, employee_code, role, referral_code, status)
SELECT 'Danisyana Ichwan', NULL, 'guru', 'DANI-0033', 'aktif'
WHERE NOT EXISTS (SELECT 1 FROM staff WHERE name = 'Danisyana Ichwan');
