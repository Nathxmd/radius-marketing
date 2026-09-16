# Product Requirements Document
## Sistem Analisis Radius Cabang & Target Pasar (MVP)

| | |
|---|---|
| **Status** | Draft |
| **Versi** | 0.2 — stack open-source/gratis (tanpa Google API berbayar) |
| **Tanggal** | 27 Agustus 2026 |
| **Pemilik dokumen** | Tim IT |
| **Pemangku kepentingan** | Tim Marketing, Manajemen Operasional |

---

## 1. Ringkasan

Perusahaan mengoperasikan banyak cabang daycare. Tim marketing saat ini menentukan target market dan strategi iklan tanpa alat bantu sistematis untuk memahami wilayah di sekitar tiap cabang. Sistem ini membangun aplikasi internal berbasis web yang menghitung dan memvisualisasikan radius 5km dari tiap cabang, sehingga tim marketing dapat menentukan wilayah cakupan target dan mengambil keputusan media iklan berbasis data lokasi.

MVP ini fokus HANYA pada penentuan radius 5km dan wilayah cakupannya untuk target market. Pemetaan data kompetitor (termasuk harga) adalah fase berikutnya dan TIDAK termasuk dalam MVP ini — lihat bagian 12.

**Perubahan pada versi 0.2:** sistem dibangun menggunakan stack peta & geocoding open-source/gratis (Nominatim, Leaflet.js, tile OpenStreetMap, data batas wilayah dari BIG/OSM), bukan Google Maps Platform berbayar. Keputusan ini diambil karena kebutuhan MVP (radius + nama wilayah) sepenuhnya bisa dipenuhi tanpa API berbayar, dan scraping/crawling data dari Google Maps tidak dipilih karena melanggar Terms of Service Google serta tidak menyelesaikan kebutuhan inti (data harga kompetitor tetap harus manual, terlepas dari sumber datanya).

---

## 2. Latar belakang & masalah

- Penentuan target marketing per cabang saat ini dilakukan manual/intuitif dan tidak konsisten antar cabang.
- Tidak ada cara cepat bagi tim marketing untuk melihat wilayah mana saja (kelurahan/perumahan) yang termasuk dalam jangkauan realistis satu cabang (5km).
- Data cabang (alamat, koordinat) tersebar dan belum terpusat dalam satu sistem yang bisa diakses & divisualisasikan.
- Tanpa titik referensi wilayah yang jelas, pemilihan channel iklan (geotargeting Instagram/FB Ads, banner lokasi, kerja sama komunitas) menjadi kurang terarah.

---

## 3. Tujuan (Goals)

- Tim marketing dapat melihat radius 5km dari cabang manapun dalam bentuk peta interaktif.
- Sistem menampilkan daftar wilayah administratif (kelurahan/kecamatan) yang tercakup dalam radius tersebut.
- Data cabang terpusat, mudah ditambah/diedit oleh admin, dan bisa diakses seluruh tim marketing yang berwenang (dengan login).
- Mengurangi waktu riset manual tim marketing untuk memahami area target per cabang.

### 3.1 Non-tujuan (Out of scope untuk MVP ini)

- Pemetaan & pencatatan data kompetitor daycare (nama, rating, harga) — fase 2.
- Rekomendasi otomatis channel iklan atau budget iklan.
- Analisis demografi penduduk (kepadatan, tingkat pendapatan) — dipertimbangkan sebagai fase lanjutan bila ada sumber data yang tersedia.
- Integrasi langsung ke platform iklan (Meta Ads Manager, Google Ads).

---

## 4. Target pengguna

| Peran | Kebutuhan utama |
|---|---|
| Admin IT / Superadmin | Kelola data cabang (tambah/edit/hapus), kelola akun user, atur hak akses |
| Staff Marketing | Lihat peta radius per cabang, lihat daftar wilayah cakupan, catat insight target market |
| Manajemen / Kepala Cabang | Lihat ringkasan cakupan wilayah tiap cabang (read-only) |

---

## 5. User stories (MVP)

- Sebagai admin, saya bisa menambahkan cabang baru dengan mengisi nama dan alamat, lalu sistem otomatis mengonversi alamat menjadi koordinat (geocoding).
- Sebagai staff marketing, saya bisa memilih satu cabang dan melihat peta dengan lingkaran radius 5km di sekitarnya.
- Sebagai staff marketing, saya bisa melihat daftar nama wilayah (kelurahan/kecamatan) yang masuk dalam radius 5km tersebut.
- Sebagai staff marketing, saya bisa menuliskan catatan/insight target market untuk cabang tersebut (misal: karakteristik penduduk, jenis perumahan) supaya tersimpan dan bisa dilihat tim lain.
- Sebagai admin, saya bisa mengedit koordinat cabang secara manual jika hasil geocoding kurang akurat.
- Sebagai user, saya harus login terlebih dahulu untuk mengakses sistem.

---

## 6. Alur pengguna (User flow)

1. User login ke sistem.
2. User memilih cabang dari daftar cabang (atau admin menambah cabang baru).
3. Sistem menampilkan peta dengan marker lokasi cabang dan lingkaran radius 5km.
4. Sistem menampilkan daftar wilayah (kelurahan/kecamatan) yang bersinggungan dengan radius tersebut, di panel samping peta.
5. User (staff marketing) menambahkan catatan target market pada kolom insight yang tersedia.
6. User dapat mencetak/export ringkasan cabang tersebut (peta + daftar wilayah + catatan) ke PDF.

---

## 7. Kebutuhan fungsional

| ID | Requirement | Deskripsi | Prioritas |
|---|---|---|---|
| F1 | Manajemen data cabang | CRUD data cabang: nama, alamat, koordinat, catatan wilayah | Must |
| F2 | Geocoding otomatis | Alamat cabang otomatis dikonversi ke latitude/longitude via Nominatim (OpenStreetMap), mengikuti usage policy resmi (maks. 1 request/detik, wajib header User-Agent, wajib atribusi ke OpenStreetMap) | Must |
| F3 | Visualisasi radius 5km | Tampilkan peta interaktif (Leaflet.js + tile OpenStreetMap) dengan marker cabang dan lingkaran radius 5km | Must |
| F4 | Daftar wilayah cakupan | Tampilkan nama kelurahan/kecamatan yang bersinggungan dengan radius 5km | Must |
| F5 | Catatan target market | Field teks bebas per cabang untuk mencatat insight/karakteristik target market | Should |
| F6 | Autentikasi & role | Login, dua role: admin dan marketing, akses dibatasi sesuai role | Must |
| F7 | Export ringkasan | Export tampilan cabang (peta + wilayah + catatan) ke PDF | Could |
| F8 | Pencarian & filter cabang | Cari cabang berdasarkan nama/kota | Should |

---

## 8. Kebutuhan data

Entitas utama pada MVP ini:

| Entitas | Field | Keterangan |
|---|---|---|
| Branch (cabang) | id, name, address, latitude, longitude, city, target_market_notes, created_at, updated_at | Data inti cabang. latitude/longitude hasil geocoding, bisa dikoreksi manual |
| CoveredArea (wilayah cakupan) | id, branch_id, area_name, area_type (kelurahan/kecamatan), source | Hasil perhitungan wilayah yang bersinggungan dengan radius; source menandai apakah dari data poligon resmi atau estimasi radius |
| User | id, name, email, password_hash, role (admin/marketing) | Akun pengguna sistem |

**Catatan teknis (radius & wilayah):** perhitungan "wilayah dalam radius" idealnya menggunakan data poligon batas administratif (kelurahan/kecamatan) yang diintersect dengan lingkaran radius (PostGIS `ST_Intersects`), bukan sekadar menampilkan lingkaran kosong — supaya nama wilayah bisa ditampilkan langsung ke marketing tanpa mereka baca peta manual. Sumber data poligon: Badan Informasi Geospasial (BIG) atau ekstraksi boundary dari OpenStreetMap (keduanya gratis, tidak perlu API berbayar). Jika data poligon resmi belum tersedia di awal untuk kota tertentu, MVP bisa mulai dengan visualisasi lingkaran radius saja (F3) dan daftar wilayah diisi manual oleh admin (fallback), lalu F4 otomatis menyusul begitu data poligon didapat.

**Catatan teknis (geocoding):** Nominatim adalah layanan publik gratis dengan usage policy ketat (rate limit rendah, tidak untuk bulk request dalam jumlah besar dan cepat). Untuk volume cabang yang bertambah banyak di masa depan, pertimbangkan meng-host instance Nominatim sendiri (self-hosted, tetap gratis dari sisi lisensi data OSM, hanya perlu biaya server) agar tidak bergantung pada rate limit layanan publik.

---

## 9. Kebutuhan non-fungsional

- Peta harus termuat dalam < 3 detik pada koneksi kantor standar.
- Sistem hanya bisa diakses oleh user yang login (tidak publik).
- Data cabang dan catatan tersimpan permanen di database (PostgreSQL), bukan penyimpanan lokal browser.
- Sistem dapat diakses dari desktop maupun tablet (mobile-friendly, tidak wajib native app).
- Log perubahan data cabang (audit trail) dicatat: siapa mengubah apa dan kapan.
- Permintaan ke layanan Nominatim publik dibatasi maksimal 1 request/detik dan menyertakan header User-Agent sesuai usage policy resmi — proses geocoding cabang baru dilakukan satu per satu, bukan bulk import instan.
- Atribusi "© OpenStreetMap contributors" wajib ditampilkan di setiap tampilan peta, sesuai lisensi ODbL data OpenStreetMap.

---

## 10. Metrik keberhasilan

- 100% cabang aktif sudah terdaftar di sistem dengan koordinat yang tervalidasi.
- Tim marketing menggunakan sistem ini sebagai rujukan utama saat menyusun rencana target market cabang baru (diukur lewat survei/adopsi internal).
- Waktu riset area target per cabang berkurang dibanding proses manual sebelumnya (baseline diukur sebelum rilis).

---

## 11. Asumsi & dependensi

- Sistem menggunakan layanan geocoding & peta open-source/gratis: Nominatim (geocoding), Leaflet.js + tile OpenStreetMap (peta). Tidak ada akun Google Cloud/billing berbayar yang dibutuhkan untuk MVP ini.
- Data poligon batas kelurahan/kecamatan (untuk F4) perlu didapatkan dari sumber seperti Badan Informasi Geospasial (BIG) atau ekstraksi boundary OpenStreetMap; ketersediaan dan kelengkapan data ini masih perlu divalidasi per wilayah operasional — cakupan OSM untuk batas administratif detail di Indonesia bervariasi antar daerah.
- Alamat cabang yang diinput cukup akurat untuk menghasilkan geocoding yang tepat via Nominatim; alamat ambigu atau tidak ditemukan Nominatim memerlukan koreksi/input koordinat manual oleh admin (fallback wajib ada karena akurasi Nominatim untuk alamat informal Indonesia bisa lebih rendah dibanding Google Geocoding).
- Tim menerima trade-off: layanan gratis (Nominatim publik) punya rate limit ketat dan cakupan data yang kadang kurang detail dibanding layanan berbayar — dianggap dapat diterima untuk skala jumlah cabang saat ini.

---

## 12. Fase berikutnya (di luar MVP ini)

| Fase | Ruang lingkup |
|---|---|
| Fase 2 | Auto-fetch data kompetitor daycare dalam radius 5km (opsi: OpenStreetMap Overpass API secara gratis dengan cakupan data lebih terbatas, atau Google Places API berbayar dengan cakupan lebih lengkap — keputusan menyusul berdasar evaluasi kelengkapan data), pencatatan manual data harga & fasilitas kompetitor |
| Fase 3 | Rekomendasi channel iklan berbasis karakteristik wilayah, dashboard perbandingan antar cabang |
| Fase 4 | Integrasi data demografi (kepadatan penduduk, estimasi jumlah keluarga usia anak balita) bila sumber data tersedia |

---

## 13. Risiko & pertanyaan terbuka

- Apakah data poligon batas wilayah administratif yang akurat tersedia untuk semua kota operasional dari BIG/OSM? Jika tidak, F4 perlu fallback manual di awal untuk kota yang datanya belum lengkap.
- Akurasi Nominatim untuk alamat informal khas Indonesia (tanpa nomor jalan baku, patokan lokal) belum diuji — perlu uji coba geocoding terhadap sampel alamat cabang riil sebelum development penuh dimulai, untuk memastikan tingkat koreksi manual yang dibutuhkan admin masih wajar.
- Berapa jumlah cabang saat ini dan proyeksi pertumbuhan cabang — untuk memastikan rate limit Nominatim publik (1 req/detik) tidak jadi bottleneck; jika volume besar, evaluasi self-hosted Nominatim.
- Siapa yang akan menjadi admin data cabang — tim IT, operasional, atau marketing sendiri?
- Apakah dibutuhkan histori perubahan radius/kebijakan (misal radius berubah dari 5km ke nilai lain di masa depan) sebagai parameter yang bisa dikonfigurasi, bukan hardcode?
