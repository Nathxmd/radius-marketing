-- Jalankan sekali pada database existing yang sudah memiliki tabel staff.
-- MySQL 8 / MariaDB.
ALTER TABLE staff ADD COLUMN employee_code VARCHAR(60) NULL UNIQUE AFTER name;
