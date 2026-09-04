-- Data cabang Rumah Biru.
-- Jalankan sekali setelah database utama tersedia. Script aman dijalankan ulang.

-- Selaraskan cabang Cawang 1 yang sudah ada berdasarkan alamatnya.
UPDATE branches
SET name = 'Rumah Biru Cawang 1',
    address = 'Jl. O Kavling No.18, RT.10/RW.14, Kb. Baru, Kec. Tebet, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12830',
    city = 'Jakarta Selatan',
    latitude = -6.24048477,
    longitude = 106.85928719,
    geocoding_status = 'manual'
WHERE address LIKE 'Jl. O Kavling No.18%';

INSERT INTO branches (name, address, city, latitude, longitude, geocoding_status)
SELECT 'Rumah Biru Cawang 2',
       'Jl. O Kavling No.10, RT.9/RW.14, Kb. Baru, Kec. Tebet, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12830',
       'Jakarta Selatan', -6.24121231, 106.85903252, 'manual'
WHERE NOT EXISTS (SELECT 1 FROM branches WHERE name = 'Rumah Biru Cawang 2');

INSERT INTO branches (name, address, city, latitude, longitude, geocoding_status)
SELECT 'Rumah Biru Tebet',
       'Komplek Jepang, Jl. Tebet Dalam IV H No.15, RT.20/RW.1, Tebet Bar., Kec. Tebet, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12810',
       'Jakarta Selatan', -6.22851919, 106.85133443, 'manual'
WHERE NOT EXISTS (SELECT 1 FROM branches WHERE name = 'Rumah Biru Tebet');

INSERT INTO branches (name, address, city, latitude, longitude, geocoding_status)
SELECT 'Rumah Biru Pasar Minggu',
       'Komplek Perkantoran PT Adhi Karya, Jl. Raya Pasar Minggu No.KM. 18, RT.13/RW.1, Ps. Minggu, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12510',
       'Jakarta Selatan', -6.26580966, 106.84562976, 'manual'
WHERE NOT EXISTS (SELECT 1 FROM branches WHERE name = 'Rumah Biru Pasar Minggu');
