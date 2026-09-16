# API Referral — Dokumentasi Integrasi Aplikasi Eksternal

Versi 1.0 · 2026-09-09 · Sistem Radius (Sumber Data) ↔ Aplikasi Eksternal (Pendaftaran Online)

Dokumen ini untuk tim yang membangun **aplikasi eksternal** (web/checkout online tempat calon
pelanggan mendaftar memakai kode referral). Sistem ini adalah **sumber data**: semua nilai promo
dihitung dan disimpan di sini. Aplikasi eksternal cukup menerima angka jadi dan menampilkannya,
**tidak menghitung apapun**.

---

## 1. Ringkasan alur

```
  [Sistem Radius]  --(1) push referral.sync-->  [Aplikasi Eksternal]
      SUMBER DATA                                (tampilkan kode, promo, harga)
          ^                                             |
          |            (2) webhook: used / enrolled /
          +----(3) response OK/Error--------------- visit_completed / cancelled
```

1. **Push (Sistem Radius → Eksternal):** setiap kali staff dibuat/diubah, atau promo disimpan,
   Sistem Radius mengirim `event: referral.sync` berisi data karyawan + aturan promo (termasuk
   harga per cabang untuk klinik).
2. **Webhook (Eksternal → Sistem Radius):** setiap kejadian (kode dipakai, parent enroll, kunjungan
   klinik tuntas, pembatalan) aplikasi eksternal memanggil endpoint webhook kami.
3. **Balasan:** Sistem Radius menjawab dengan JSON `{status: "ok"|"error", ...}`. Untuk beberapa
   kondisi (misal cabang klinik belum dikonfigurasi harga) kami menjawab HTTP 422.

---

## 2. Autentikasi & keamanan bersama

Keduanya membagi satu rahasia:

| Item | Nilai | Pemakaian |
|---|---|---|
| `REFERRAL_SHARED_SECRET` | random ≥ 32 karakter, dibagikan di luar repo | bahan HMAC-SHA256 untuk signature |

Rahasia disimpan di `.env` (gitignored) sistem ini dan di config aman aplikasi eksternal.
Jangan pernah hardcode di kode yang ter-commit repo.

### 2.1 Cara hitung signature (untuk DUA arah)

**Signature** = `hex(hash_hmac('sha256', RAW_BODY, SECRET))` — lowercase.

- **RAW_BODY** = string body JSON mentah persis seperti yang dikirim (bukan hasil `json_encode`
  ulang, bukan body setelah format ulang). ASCII/UTF-8 bytes apa adanya.
- Dikirim via header `X-Signature`.
- Penerima verifikasi dengan `hash_equals()` (constant-time), **bukan** `==`/`===`.

Contoh (PHP penerima webhook):

```php
$raw = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
$valid = hash_equals(hash_hmac('sha256', $raw, REFERRAL_SHARED_SECRET), $sig);
```

Contoh (bash pembuat signature sebelum mengirim):

```bash
SECRET="<REFERRAL_SHARED_SECRET>"
BODY='{"event_id":"ev-1","event_type":"used",...}'
SIG=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')
```

> Catatan: body **tidak** boleh mengandung whitespace tambahan di awal/akhir yang tidak ikut
> disign. Pastikan `SIG` dihitung dari byte yang sama persis dengan yang dikirim di body HTTP.

---

## 3. Push: Sistem Radius → Aplikasi Eksternal

### 3.1 Endpoint tujuan (dikonfigurasi di sistem ini)

| Parameter | Isi |
|---|---|
| URL | nilai `EXTERNAL_APP_PUSH_URL` di `.env` (contoh `https://aplikasi-eksternal.example.com/api/referrals/sync`) |
| Method | `POST` |
| Header | `Content-Type: application/json`<br>`X-Api-Key: <EXTERNAL_APP_API_KEY>`<br>`X-Signature: <HMAC-SHA256 raw body>` |
| Timeout | 10 detik |

Pastikan endpoint ini **menerima** dan menjawab 2xx untuk push sukses. Sistem kami mencatat
setiap percobaan ke `referral_push_logs` dan akan retry (cron `referral-push-retry.php`) untuk
staff yang gagal.

### 3.2 Kapan push dikirim

- Staff **baru** dibuat.
- Field `name` / `referral_code` / `branch_id` / `status` pada staff yang sudah ada **diubah**.
- Kode referral **diregenerate**.
- Promo disimpan (perubahan harga, termasuk per cabang) → Sistem Radius melakukan **resync semua
  staff aktif** (`referral-resync-all.php`).

### 3.3 Struktur payload `referral.sync`

```json
{
  "event": "referral.sync",
  "data": {
    "staff_id": 12,
    "employee_name": "Budi Santoso",
    "referral_code": "BUDI-2481",
    "branch_id": 1,
    "status": "aktif",
    "promos": [
      {
        "type": "daycare",
        "rules": {
          "parent": {
            "rule_type": "recurring_monthly",
            "value": 500000,
            "duration_months": 3
          },
          "staff": {
            "rule_type": "one_time",
            "value": 250000
          }
        }
      },
      {
        "type": "klinik",
        "rules": {
          "parent": {
            "rule_type": "per_visit_tiered",
            "visit_range_start": 1,
            "visit_range_end": 4,
            "branch_pricing": [
              { "branch_id": 1, "branch_name": "Daycare Harapan Indah", "value": 150000 },
              { "branch_id": 2, "branch_name": "Daycare Kemang", "value": 250000 }
            ]
          },
          "staff": null
        }
      }
    ]
  },
  "timestamp": "2026-09-09T10:00:00+07:00"
}
```

### 3.4 Field `promos[].rules`

| Promo | Beneficiary | rule_type | Field | Arti |
|---|---|---|---|---|
| `daycare` | `parent` | `recurring_monthly` | `value`, `duration_months` | diskon/bulan, berapa bulan pertama |
| `daycare` | `staff` | `one_time` | `value` | komisi staff, dibayar sekali saat enroll |
| `klinik` | `parent` | `per_visit_tiered` | `value` tak dipakai langsung; pakai `visit_range_start/end` + `branch_pricing` | harga khusus per kunjungan, berbeda per cabang |
| `klinik` | `staff` | — | `null` | **tidak ada komisi staff untuk klinik** |

- Aturan **global** (parent daycare, staff daycare) berada langsung di `rules`.
- Aturan **per cabang** (parent klinik) ada di `branch_pricing[]`. `staff` klinik selalu `null`.
- Cabang klinik yang belum punya harga **tidak** dimasukkan ke `branch_pricing[]`. Webhook
  `visit_completed` untuk cabang itu akan **ditolak 422** oleh sistem kami sampai harga diatur.

### 3.5 Balasan yang Anda (aplikasi eksternal) harus kirim

HTTP `2xx` apa pun dianggap sukses. Body JSON dianjurkan, contoh:

```json
{ "status": "ok", "received": true }
```

---

## 4. Webhook: Aplikasi Eksternal → Sistem Radius

### 4.1 Endpoint (milik sistem kami)

| Item | Isi |
|---|---|
| URL | `https://<host-sistem>/radius-sistem/index.php?route=webhook/referral-status`<br>alias `https://<host-sistem>/radius-sistem/webhook-referral-status.php` |
| Method | `POST` |
| Content-Type | `application/json` |
| Auth | **tanpa sesi login** (server-to-server). Wajib header `X-Signature` valid. |

### 4.2 Headers yang wajib dikirim aplikasi eksternal

| Header | Nilai |
|---|---|
| `X-Signature` | `hex(hmac_sha256(raw_body, REFERRAL_SHARED_SECRET))` — lihat §2.1 |
| `Content-Type` | `application/json` |

Batasan: payload ≤ 1 MB.

### 4.3 Ringkasan event

| `event_type` | Dipakai utk | Ringkasan aksi di sistem kami |
|---|---|---|
| `used` | lead masuk | insert `registrations` `enrollment_status='lead'` |
| `enrolled` | parent resmi enroll | update `registrations` → `'enrolled'`; (daycare) catat redemptions + komisi staff pending; (klinik) tidak ada komisi |
| `visit_completed` | kunjungan klinik tuntas | catat redemptions parent per kunjungan; tolak branch tak punya harga → 422 |
| `cancelled` | pembatalan | update `registrations` → `'batal'` |

### 4.4 Kontrak umum tiap event

Field umum (wajib pada semua event):

| Field | Tipe | Wajib | Deskripsi |
|---|---|---|---|
| `event_id` | string | ya | unik per kejadian; dipakai idempotency (kirim ulang → balasan `Already processed`) |
| `event_type` | string | ya | salah satu dari 4 event di tabel atas |
| `referral_code` | string | ya | kode referral dipakai, huruf besar |
| `promo_type` | string | ya | `daycare` atau `klinik` |
| `parent_phone` | string | ya (kecuali `used`) | nomor telp orang tua (digit & `+`, ≤ 30 char) |
| `occurred_at` | string | opsional | ISO 8601 waktu kejadian |

---

### 4.5 `event_type = "used"` — lead masuk

Payload:

```json
{
  "event_id": "ext-20260909-001",
  "event_type": "used",
  "referral_code": "BUDI-2481",
  "promo_type": "daycare",
  "parent_name": "Ibu Sari",
  "parent_phone": "081234567890",
  "child_name": "Alya",
  "branch_id": 1,
  "occurred_at": "2026-09-09T08:00:00+07:00"
}
```

Field spesifik:

| Field | Tipe | Wajib | Deskripsi |
|---|---|---|---|
| `parent_name` | string | opsional | nama orang tua |
| `parent_phone` | string | opsional | no telp (boleh kosong pada `used`) |
| `child_name` | string | opsional | nama anak |
| `branch_id` | int | opsional | id cabang di sistem kami (harus valid) |

Hasil: sistem kami insert `registrations` dengan `enrollment_status='lead'` dan
`staff_commission_status` = `'tidak_ada'` (klinik) atau `'belum_ada'` (daycare).

Respon sukses:

```json
{ "status": "ok" }
```

---

### 4.6 `event_type = "enrolled"` — parent resmi mendaftar

Payload:

```json
{
  "event_id": "ext-20260910-001",
  "event_type": "enrolled",
  "referral_code": "BUDI-2481",
  "promo_type": "daycare",
  "parent_phone": "081234567890",
  "occurred_at": "2026-09-10T09:00:00+07:00"
}
```

Sistem kami mencocokkan `registrations` lewat `referral_code_used` + `parent_phone`, lalu:

- set `enrollment_status='enrolled'`;
- **daycare**: catat redemptions parent 3 bulan (period 1,2,3) + 1 komisi staff (one_time),
  `staff_commission_status → 'pending'`;
- **klinik**: tidak ada redemptions/komisi dibuat, `staff_commission_status` tetap `'tidak_ada'`.

> Jika referral + phone tidak ditemukan, sistem kami catat error ke `webhook_logs` dan balas
> error (lihat §5 HTTP). Pastikan `used` dikirim lebih dulu.

---

### 4.7 `event_type = "visit_completed"` — kunjungan klinik tuntas (KHUSUS klinik)

Payload:

```json
{
  "event_id": "ext-20260911-001",
  "event_type": "visit_completed",
  "referral_code": "BUDI-2481",
  "promo_type": "klinik",
  "parent_phone": "082298765432",
  "visit_number": 1,
  "occurred_at": "2026-09-11T08:00:00+07:00"
}
```

Field spesifik:

| Field | Tipe | Wajib | Deskripsi |
|---|---|---|---|
| `visit_number` | int ≥ 1 | ya | kunjungan ke berapa |

Logika sistem kami:

1. Ambil `branch_id_target` dari registrasi klinik terkait.
2. Cari rule promo klinik untuk **cabang itu** (kueri spesifik; tanpa fallback ke cabang lain/global).
3. **Cabang belum diatur harga** → jangan insert; balas `HTTP 422` (§5), log error jelas.
4. `visit_number` dalam range `1..visit_range_end` (biasanya 1–4) → insert redemptions
   `value_applied = <harga cabang>`.
5. `visit_number` di luar range (>4) → tetap insert untuk histori, `value_applied=0`
   (harga normal).

`promo_type` selain `'klinik'` ditolak untuk event ini.

---

### 4.8 `event_type = "cancelled"` — pembatalan

Payload:

```json
{
  "event_id": "ext-20260912-001",
  "event_type": "cancelled",
  "referral_code": "BUDI-2481",
  "promo_type": "klinik",
  "parent_phone": "081234567890",
  "occurred_at": "2026-09-12T10:00:00+07:00"
}
```

Sistem kami:

- set `enrollment_status='batal'`;
- `staff_commission_status → 'batal'` **hanya jika** sebelumnya bukan `'tidak_ada'` (registrasi
  klinik yang memang tanpa komisi tetap `'tidak_ada'`);
- **tidak** menghapus `promo_redemptions` yang sudah tercatat.

---

## 5. Respon HTTP dari sistem kami (webhook masuk)

| HTTP | Kondisi | Body contoh |
|---|---|---|
| `200 OK` | diproses / sudah pernah diproses | `{"status":"ok"}` atau `{"status":"ok","message":"Already processed"}` |
| `400 Bad Request` | struktur/promo_type tidak valid, field wajib kosong | `{"status":"error","message":"Invalid payload"}` |
| `401 Unauthorized` | `X-Signature` salah | `{"status":"error","message":"Invalid signature"}` |
| `413 Payload Too Large` | body > 1 MB | `{"status":"error","message":"Payload too large"}` |
| `422 Unprocessable` | promo klinik utk cabang yang belum diatur harga, dll | `{"status":"error","message":"harga promo klinik belum diatur untuk cabang ini (branch_id=...)"}` |
| `500 Internal Error` | error DB tak terduga | `{"status":"error","message":"Internal error"}` |

Catatan penting soal `used` dengan kode referral tidak ditemukan/tidak aktif: sistem kami menjawab
`200 {"status":"ok","message":"Referral code not found, logged for review"}` **tanpa** membuat
registrasi, dan mencatatnya ke `webhook_logs` untuk review admin. Ini sengaja — jangan
menganggapnya sukses membuat atau gagal fatal; cek log bila perlu.

---

## 6. Alur yang dianjurkan aplikasi eksternal

1. Saat calon orang tua memasukkan **kode referral**, validasi lokal (format) lalu tampilkan
   nama karyawan + promo sesuai data `referral.sync` yang diterima.
2. Saat calon memilih **daycare**: tampilkan diskon bulanan `value` utk `duration_months` pertama,
   dan (jika perlu) komisi `staff.value`.
3. Saat calon memilih **klinik**: tampilkan harga khusus per kunjungan berdasar `branch_id` yang
   dipilih dari `branch_pricing[]`. Bila cabang terpilih tak ada di `branch_pricing[]`, tampilkan
   "harga normal (belum ada promo utk cabang ini)" — jangan menebak harga.
4. Kirim webhook tepat di momen yang sesuai (`used` saat checkout submit, `enrolled` saat
   pembayaran/enroll tuntas, `visit_completed` tiap kunjungan klinik selesai, `cancelled` saat
   batal). Kirim `event_id` baru tiap kejadian; jangan pakai ulang id.

---

## 7. Contoh: mengirim webhook ke sistem kami (curl)

```bash
SECRET="<REFERRAL_SHARED_SECRET>"
URL="https://<host>/radius-sistem/index.php?route=webhook/referral-status"

# --- used (daycare) ---
BODY='{"event_id":"ext-20260909-001","event_type":"used","referral_code":"BUDI-2481","promo_type":"daycare","parent_name":"Ibu Sari","parent_phone":"081234567890","child_name":"Alya","branch_id":1,"occurred_at":"2026-09-09T08:00:00+07:00"}'
SIG=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')
curl -s -X POST "$URL" \
  -H "Content-Type: application/json" \
  -H "X-Signature: $SIG" \
  -d "$BODY"

# --- enrolled (daycare) ---
BODY='{"event_id":"ext-20260910-001","event_type":"enrolled","referral_code":"BUDI-2481","promo_type":"daycare","parent_phone":"081234567890","occurred_at":"2026-09-10T09:00:00+07:00"}'
SIG=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')
curl -s -X POST "$URL" -H "Content-Type: application/json" -H "X-Signature: $SIG" -d "$BODY"

# --- visit_completed (klinik, sudah terkonfigurasi) ---
BODY='{"event_id":"ext-20260911-001","event_type":"visit_completed","referral_code":"BUDI-2481","promo_type":"klinik","parent_phone":"082298765432","visit_number":1,"occurred_at":"2026-09-11T08:00:00+07:00"}'
SIG=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')
curl -s -X POST "$URL" -H "Content-Type: application/json" -H "X-Signature: $SIG" -d "$BODY"

# --- visit_completed utk cabang BELUM dikonfigurasi -> HTTP 422 ---
# (gunakan referral + parent_phone yg branch_id_target-nya tak punya harga klinik)
BODY='{"event_id":"ext-20260911-002","event_type":"visit_completed","referral_code":"BUDI-2481","promo_type":"klinik","parent_phone":"082298765432","visit_number":1,"occurred_at":"2026-09-11T09:00:00+07:00"}'
SIG=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')
curl -s -i -X POST "$URL" -H "Content-Type: application/json" -H "X-Signature: $SIG" -d "$BODY"
```

---

## 8. Referensi tabel (bagi tim yang cek di sisi DB sistem kami)

| Tabel | Isi |
|---|---|
| `staff` | karyawan; `referral_code`, `status`, `branch_id` |
| `promos` | `daycare` / `klinik`, `active` |
| `promo_rules` | nilai & aturan per promo/beneficiary/branch |
| `registrations` | lead/enroll/batal; `staff_commission_status`, `promo_type` |
| `promo_redemptions` | setiap manfaat yang diterapkan (bulan/kunjungan/komisi) |
| `referral_push_logs` | riwayat push ke aplikasi eksternal |
| `webhook_logs` | riwayat request webhook masuk (valid/gagal) |

---

## 9. Checklist implementasi aplikasi eksternal

- [ ] Simpan `REFERRAL_SHARED_SECRET` di config aman (bukan repo).
- [ ] Verifikasi `X-Signature` dengan `hash_equals` pada semua webhook masuk.
- [ ] Terima & simpan payload `referral.sync`; tampilkan promo/branch_pricing apa adanya.
- [ ] Balas 2xx pada `referral.sync` sukses.
- [ ] Kirim `event_id` unik untuk tiap webhook; gunakan `promo_type` yang benar.
- [ ] Tangani HTTP 422 (khususnya cabang klinik belum dikonfigurasi) dengan pesan yang jelas ke
      user/admin, jangan menebak harga.
- [ ] Logging: catat request & response untuk debugging bersama.