-- Jalankan sekali jika tabel staff sudah ada tetapi belum memiliki employee_code.
-- Migration ini aman dijalankan ulang pada MySQL 8/MariaDB.

SET @column_exists = (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'staff'
      AND column_name = 'employee_code'
);

SET @add_column_sql = IF(
    @column_exists = 0,
    'ALTER TABLE staff ADD COLUMN employee_code VARCHAR(60) NULL AFTER name',
    'SELECT 1'
);

PREPARE add_column_statement FROM @add_column_sql;
EXECUTE add_column_statement;
DEALLOCATE PREPARE add_column_statement;

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'staff'
      AND column_name = 'employee_code'
      AND non_unique = 0
);

SET @add_index_sql = IF(
    @index_exists = 0,
    'ALTER TABLE staff ADD UNIQUE KEY uq_staff_employee_code (employee_code)',
    'SELECT 1'
);

PREPARE add_index_statement FROM @add_index_sql;
EXECUTE add_index_statement;
DEALLOCATE PREPARE add_index_statement;
