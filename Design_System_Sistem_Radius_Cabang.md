# Design System — Sistem Analisis Radius Cabang & Target Pasar

Versi 0.2 · 27 Agustus 2026 · Internal tool untuk tim IT & Marketing
Perubahan dari versi sebelumnya: peta menggunakan Leaflet.js + tile OpenStreetMap (bukan Google Maps), lihat catatan atribusi wajib di bagian 3.6.

## 1. Prinsip desain

Ini adalah **alat kerja internal**, dipakai tim marketing tiap hari untuk mengambil keputusan berbasis peta dan data. Tiga prinsip yang memandu semua keputusan visual:

1. **Peta adalah pusat perhatian, bukan chrome di sekitarnya.** UI harus mengalah pada peta — warna netral, kontras rendah di luar peta, supaya mata langsung tertuju ke radius dan marker.
2. **Data harus scannable dalam hitungan detik.** Staff marketing membuka ini di sela kerja lain. Tabel, badge status, dan angka harus terbaca cepat tanpa perlu mikir.
3. **Rapi dan tepercaya, bukan playful.** Konteks daycare itu hangat, tapi ini alat kerja internal — bukan halaman marketing untuk orang tua. Warna hangat dipakai sebagai aksen tipis, bukan dominan.

---

## 2. Design tokens

### 2.1 Warna

Palet dasar: **teal** sebagai warna utama (dipilih karena netral-profesional tapi tetap ada kehangatan, beda dari biru korporat yang generik), **coral hangat** sebagai aksen sekunder untuk elemen yang terkait "anak/daycare" (misal ikon badge kategori), dan skala abu-abu netral untuk UI structural.

```css
:root {
  /* Primary — Teal */
  --color-primary-50:  #E1F5EE;
  --color-primary-100: #9FE1CB;
  --color-primary-400: #1D9E75;
  --color-primary-600: #0F6E56;  /* primary actions, links, active states */
  --color-primary-800: #085041;
  --color-primary-900: #04342C;

  /* Accent — Coral (dipakai tipis: badge, highlight radius circle) */
  --color-accent-50:  #FAECE7;
  --color-accent-400: #D85A30;
  --color-accent-600: #993C1D;

  /* Semantic */
  --color-success: #3B6D11;
  --color-warning: #854F0B;
  --color-danger:  #A32D2D;
  --color-info:    #185FA5;

  /* Neutral / structural */
  --color-surface-0: #FFFFFF;   /* page background */
  --color-surface-1: #F7F6F3;   /* card background */
  --color-surface-2: #FFFFFF;   /* elevated card (modal, dropdown) */
  --color-border:        #D3D1C7;
  --color-border-strong: #B4B2A9;
  --color-text-primary:   #2C2C2A;
  --color-text-secondary: #5F5E5A;
  --color-text-muted:     #888780;
}
```

**Dark mode:** aplikasi internal ini defaultnya light-mode-only untuk MVP (prioritas dev lebih penting dipakai untuk fitur inti). Jika dibutuhkan dark mode di fase berikutnya, invert `surface` dan `text` menggunakan stop yang sama dari ramp di atas (surface-0 → #1A1A18, text-primary → #F1EFE8, dst).

**Aturan pemakaian warna:**
- Teal 600 = tombol utama, link, state aktif di sidebar/tab.
- Coral dipakai HANYA untuk: garis lingkaran radius di peta, dan badge kategori kecil. Jangan dipakai di tombol besar — supaya tetap terasa sebagai aksen, bukan warna kedua yang bersaing dengan teal.
- Merah (`--color-danger`) hanya untuk error/hapus data. Jangan dipakai dekoratif.

### 2.2 Tipografi

```css
:root {
  --font-sans: "Inter", -apple-system, "Segoe UI", sans-serif;
  --font-mono: "IBM Plex Mono", "SFMono-Regular", monospace; /* untuk koordinat lat/long, ID */
}
```

Kenapa Inter: sangat legible di ukuran kecil (angka & tabel padat), punya tabular figures untuk kolom angka rapi, dan gratis/open-source jadi gak ada isu lisensi untuk tool internal.

| Token | Ukuran | Weight | Line-height | Pemakaian |
|---|---|---|---|---|
| `text-display` | 28px | 600 | 1.2 | Judul halaman (misal "Cabang: Harapan Indah") |
| `text-h2` | 20px | 600 | 1.3 | Judul section/card |
| `text-h3` | 16px | 600 | 1.4 | Sub-section, label grup form |
| `text-body` | 14px | 400 | 1.6 | Teks isi, deskripsi |
| `text-body-strong` | 14px | 600 | 1.6 | Label field, nama entitas di tabel |
| `text-small` | 12px | 400 | 1.5 | Caption, timestamp, helper text |
| `text-mono-data` | 13px | 400 | 1.4 | Koordinat, ID, kode wilayah — pakai `--font-mono` |

Skala dibuat kompak (bukan skala editorial 40px+) karena ini dashboard data-dense, bukan halaman landas pemasaran.

### 2.3 Spacing

Basis 4px, dipakai konsisten di semua padding/margin/gap:

```css
:root {
  --space-1: 4px;
  --space-2: 8px;
  --space-3: 12px;
  --space-4: 16px;
  --space-5: 24px;
  --space-6: 32px;
  --space-8: 48px;
}
```

- Padding dalam card: `--space-5` (24px)
- Gap antar elemen form: `--space-4` (16px)
- Gap antar card di grid: `--space-5` (24px)

### 2.4 Radius & shadow

```css
:root {
  --radius-sm: 6px;   /* input, badge, button kecil */
  --radius-md: 8px;   /* button, dropdown */
  --radius-lg: 12px;  /* card */

  --shadow-sm: 0 1px 2px rgba(44,44,42,0.06);
  --shadow-md: 0 2px 8px rgba(44,44,42,0.08);
  --shadow-modal: 0 8px 24px rgba(44,44,42,0.16);
}
```

Tidak pakai gradient atau shadow tebal — flat design, supaya peta (yang punya banyak detail visual sendiri) tidak bersaing dengan chrome UI di sekitarnya.

### 2.5 Breakpoints

```css
--bp-tablet:  768px;
--bp-desktop: 1024px;
--bp-wide:    1440px;
```

Target utama: desktop (staff marketing kerja di laptop/PC kantor). Tablet perlu tetap dapat dipakai (lihat NFR di PRD), mobile phone tidak jadi prioritas MVP.

---

## 3. Komponen inti

### 3.1 Tombol (Button)

| Varian | Pemakaian | Style |
|---|---|---|
| Primary | Aksi utama per halaman (misal "Simpan cabang", "Sync kompetitor") | Background `primary-600`, teks putih, radius `md` |
| Secondary | Aksi sekunder (misal "Batal", "Export PDF") | Border `border-strong`, background putih, teks `text-primary` |
| Danger | Hapus data | Background `danger`, teks putih — selalu minta konfirmasi dulu |
| Ghost/Text | Aksi tersier dalam tabel (misal "Edit", "Lihat detail") | Tanpa background, teks `primary-600`, underline saat hover |

Ukuran: height 36px (default), 44px untuk CTA halaman penting seperti "Tambah cabang baru". Padding horizontal `--space-4`.

Semua tombol butuh state: default, hover (darken 10%), focus (outline 2px `primary-400`, wajib untuk aksesibilitas keyboard), disabled (opacity 0.5, cursor not-allowed), loading (spinner + teks tetap terlihat, tombol disabled sementara).

### 3.2 Form input

- Height 40px, border `--color-border`, radius `--radius-sm`.
- Focus: border `primary-600` + ring tipis `primary-100`.
- Error: border `danger`, pesan error di bawah field pakai `text-small` warna `danger`, muncul HANYA setelah field disentuh (touched), bukan langsung saat halaman dimuat.
- Label selalu di atas input (bukan placeholder-as-label — placeholder hilang saat user mulai ngetik dan bikin bingung).
- Field alamat cabang: sertakan helper text kecil "Alamat akan dikonversi otomatis ke koordinat peta" supaya user paham kenapa ada delay singkat setelah submit.

### 3.3 Card

Container dasar untuk tiap blok informasi (misal "Info cabang", "Wilayah cakupan"). Background `surface-2`, border 1px `--color-border`, radius `--radius-lg`, padding `--space-5`, shadow `--shadow-sm`. Judul card pakai `text-h2`.

### 3.4 Tabel data

Dipakai untuk daftar cabang, daftar wilayah cakupan, (nanti) daftar kompetitor.

- Header row: background `surface-1`, teks `text-body-strong`, `text-secondary` color (bukan primary — biar tidak terbaca sebagai link).
- Baris zebra TIDAK dipakai (bikin ramai); gunakan border-bottom tipis `--color-border` antar baris saja.
- Hover baris: background `surface-1`.
- Kolom angka (jarak km, jumlah wilayah): rata kanan, pakai `text-mono-data` supaya digit sejajar.
- Baris yang butuh aksi (edit/hapus): ikon aksi muncul di kolom paling kanan, hanya terlihat jelas saat hover baris (mengurangi noise visual saat tidak berinteraksi).

### 3.5 Badge / status pill

Dipakai untuk menandai role user, status data (misal "Terverifikasi", "Perlu review"), tipe wilayah (kelurahan vs kecamatan).

```css
.badge {
  display: inline-flex;
  align-items: center;
  height: 22px;
  padding: 0 var(--space-2);
  border-radius: 999px; /* pill */
  font-size: 12px;
  font-weight: 600;
}
```

Warna badge ikut warna semantik (success/warning/info/neutral), fill 50, teks stop 600-800 dari ramp yang sama — konsisten dengan aturan token warna di atas.

### 3.6 Peta (komponen paling penting di sistem ini)

Peta dibangun dengan **Leaflet.js** menggunakan tile **OpenStreetMap** (bukan Google Maps) — konsekuensinya beberapa hal perlu diperhatikan di desain:

- Marker cabang: pin warna `primary-600`, dengan initial nama cabang saat di-hover (tooltip). Karena Leaflet tidak menyediakan style marker semewah Google Maps secara default, marker custom digambar sebagai SVG sendiri (pin + lingkaran putih di tengah), bukan mengandalkan ikon bawaan library.
- Lingkaran radius 5km: digambar pakai `L.circle()`, stroke `accent-600` 2px, fill `accent-50` dengan opacity 25% — cukup terlihat tapi tidak menutupi detail jalan/wilayah di baliknya.
- Highlight wilayah yang tercakup (jika data poligon BIG/OSM tersedia, digambar sebagai `L.geoJSON()` layer): fill `primary-100` opacity 30%, stroke `primary-400` 1px.
- Panel daftar wilayah selalu diletakkan di sisi kanan peta (bukan overlay di atas peta) — supaya peta tidak tertutup dan user bisa lihat keduanya sekaligus di desktop. Di tablet, panel jadi collapsible drawer dari bawah.
- **Atribusi OpenStreetMap wajib ditampilkan** di pojok kanan bawah peta (`© OpenStreetMap contributors`) sesuai lisensi ODbL — ini bagian non-negotiable dari komponen peta, bukan opsional secara visual. Style teks kecil (`text-small`, `text-muted`) di atas overlay semi-transparan supaya tetap terbaca di atas tile apapun.
- Tile default OSM punya kontras warna yang lebih ramai dibanding Google Maps minimalis — pertimbangkan tile style alternatif yang lebih netral (misal CartoDB Positron, gratis untuk pemakaian wajar) supaya lingkaran radius & marker tetap jadi fokus utama, bukan bersaing dengan warna tile dasar.

### 3.7 Navigasi

Sidebar kiri (fixed, 240px lebar di desktop, collapsible jadi icon-only 64px):
- Logo/nama sistem di atas
- Menu: Dashboard cabang, Tambah cabang (admin only), Pengaturan user (admin only)
- Info user + logout di bagian bawah sidebar

Top bar per halaman: judul halaman + breadcrumb kalau perlu (misal "Cabang / Harapan Indah / Radius").

---

## 4. Aksesibilitas & kualitas dasar

- Semua warna teks-di-atas-background sudah dicek kontras minimal AA (4.5:1 untuk teks body, 3:1 untuk teks besar/UI component).
- Semua elemen interaktif (tombol, link, input, baris tabel yang clickable) punya `focus-visible` outline yang terlihat jelas — jangan pernah `outline: none` tanpa pengganti.
- Ikon-only button (misal ikon edit/hapus di tabel) wajib punya `aria-label`.
- Peta harus punya alternatif teks (daftar wilayah di panel kanan berfungsi sebagai representasi non-visual dari radius, bukan cuma dekorasi).

---

## 5. Yang sengaja TIDAK dipakai

- **Gradient dan shadow tebal** — bikin dashboard data-dense terasa berat dan tidak sesuai untuk tool kerja harian.
- **Animasi berlebihan** — transisi cukup 150-200ms untuk hover/focus, tanpa animasi masuk/scroll yang dramatis. Ini tool kerja, bukan halaman promosi.
- **Warna coral sebagai warna dominan** — dicadangkan sebagai aksen radius/badge saja supaya tidak "berisik" dan tetap membaca sebagai penanda khusus, bukan warna UI biasa.
