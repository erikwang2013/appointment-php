# Laporan Audit Keamanan — Sistem Janji Temu (appointment-php)
> **Languages**: [中文](../SECURITY-AUDIT-REPORT.md) · [English](../en/SECURITY-AUDIT-REPORT.md) · [한국어](../ko/SECURITY-AUDIT-REPORT.md) · [Русский](../ru/SECURITY-AUDIT-REPORT.md) · [Deutsch](../de/SECURITY-AUDIT-REPORT.md) · [Français](../fr/SECURITY-AUDIT-REPORT.md) · [Español](../es/SECURITY-AUDIT-REPORT.md) · [Português](../pt/SECURITY-AUDIT-REPORT.md) · [हिन्दी](../hi/SECURITY-AUDIT-REPORT.md) · [العربية](../ar/SECURITY-AUDIT-REPORT.md) · [বাংলা](../bn/SECURITY-AUDIT-REPORT.md) · [日本語](../ja/SECURITY-AUDIT-REPORT.md)

> Terjemahan bahasa Indonesia · Asli: [中文](../../docs/SECURITY-AUDIT-REPORT.md)

**Tanggal**: 2026-08-04
**Ruang lingkup audit**: service (sistem layanan janji temu), admin (panel administrasi terbuka)
**Versi PHP**: 8.3.7
**Framework**: webman v2

---

## 1. Hasil Pengujian

| Item pengujian | Service | Admin |
|--------|---------|-------|
| Pemeriksaan sintaks PHP (penuh) | Lulus | Lulus |
| Unit test PHPUnit | 59 tests / 165 assertions PASS | 59 tests / 165 assertions PASS |
| Analisis statis PHPStan | Belum terinstal (timeout unduhan dependensi dev) | Belum terinstal (timeout unduhan dependensi dev) |

---

## 2. Ringkasan Lapisan Pertahanan Keamanan

```
Permintaan → Nginx (header keamanan+perlindungan file sensitif) → Cors (CORS+header keamanan) → SecurityMiddleware (31 deteksi serangan) → RateLimit (jendela geser Redis) → Auth (JWT) → Controller
                                                                                                   ↓
                                                                                    Daftar hitam IP (5 serangan/60s → larang 15min)
                                                                                    Kunci akun (5 kegagalan/15min → kunci 15min)
```

---

## 3. Masalah yang Sudah Diperbaiki

### 3.1 CORS Service kurang header respons keamanan → Sudah diperbaiki
**File**: `service/app/middleware/Cors.php`
- Tambah 6 header keamanan: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP, X-Permitted-Cross-Domain-Policies
- Sekarang konsisten dengan konfigurasi header keamanan admin

### 3.2 Service kurang kunci kegagalan login → Sudah diperbaiki
**File**: `service/app/api/v1/controller/AuthController.php`
- Metode `login()` dan `loginByCode()` menambah penghitung kegagalan Redis
- 5 kegagalan/15 menit kunci → HTTP 429
- Penurunan kapasitas elegan saat Redis gagal

### 3.3 Origin CORS dikodekan keras `*` → Sudah diperbaiki
**File**: `service/app/middleware/Cors.php`, `admin/app/middleware/Cors.php`
- Diubah menjadi dikonfigurasi melalui variabel lingkungan `CORS_ALLOW_ORIGIN`
- Kosong default `*` (kompatibel mundur)

### 3.4 Service kurang dependensi security-php → Sudah diperbaiki
**Operasi**:
- Tambahkan `allow-plugins.erikwang2013/security-php` ke composer.json
- Jalankan `composer install --no-dev` untuk menginstal dependensi
- File konfigurasi sudah dipublikasikan ke `config/plugin/erikwang2013/security-php/app.php`
- Detektor Origin CSRF (`csrf_origin`) sudah diaktifkan (mode block)

### 3.5 Nginx Service kurang Permissions-Policy → Sudah diperbaiki
**File**: `service/docs/nginx.conf`
- Tambahkan `add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;`

### 3.6 Pelengkapan konfigurasi ekosistem → Sudah diperbaiki
- `service/.env.example` dan `admin/.env.example` menambah `CORS_ALLOW_ORIGIN`
- `service/.env.docker` dan `admin/.env.docker` menambah `CORS_ALLOW_ORIGIN`

---

## 4. Daftar Lengkap Pertahanan Keamanan Saat Ini

### 4.1 Lapisan WAF — 31 Detektor Serangan

| Mode | Detektor | Jumlah |
|------|--------|------|
| **block** (cegat 403) | XSS, Injeksi SQL, Injeksi perintah, Path traversal, Upload file, SSRF, XXE, Deserialisasi, Injeksi LDAP, Injeksi header email, Open Redirect, Serangan JWT, Serangan Host header, Request Smuggling, Injeksi GraphQL, Injeksi XPATH, JNDI/Log4Shell, Injeksi SSI, Injeksi CSV, Kebocoran data, Prototype Pollution, Pembajakan WebSocket, Bypass CORS, DNS Rebinding, Validasi metode HTTP, Ukuran body permintaan (10MB), Daftar putih Content-Type, Origin CSRF | 28 |
| **log** (hanya catat) | Injeksi header respons, SSTI, Injeksi NoSQL | 3 |

### 4.2 Otentikasi dan Otorisasi

| Mekanisme | Service | Admin |
|------|---------|-------|
| Otentikasi JWT | Middleware Auth | Middleware AdminAuth |
| Daftar hitam JWT | Ditambahkan saat logout | Ditambahkan saat logout + melebihi batas sesi |
| Otorisasi RBAC | — | format method.path, cache Redis 60s |
| Kunci akun | 5 kali/15 menit (Redis) | 5 kali/15 menit (Redis) |
| Batas sesi bersamaan | — | Maksimal 3 Token |
| Hash kata sandi | bcrypt | bcrypt |

### 4.3 Pembatasan Laju

| Rute | Service | Admin |
|------|---------|-------|
| Default | 60 kali/menit/IP | 60 kali/menit/IP |
| Login | 10 kali/menit | — |
| Registrasi | 5 kali/menit | — |
| SMS/lupa kata sandi | 5 kali/menit | — |

### 4.4 Keamanan Data

| Tindakan | Service | Admin |
|------|---------|-------|
| Enkripsi kolom database | AES-256-CBC (6 model) | AES-256-CBC |
| Enkripsi transmisi API | AES-256-CBC | AES-256-CBC |
| Obfuscation ID (Hashids) | Semua ID eksternal | Semua ID eksternal |
| ID Snowflake | BIGINT non-otomatis | BIGINT non-otomatis |
| Redaksi kolom sensitif | Redaksi nomor ponsel | Redaksi data ekspor |

---

## 5. Saran yang Menunggu Diproses

### 5.1 Saran: penyimpanan security-php ganti Redis (lingkungan produksi)
**Saat ini**: kedua layanan menggunakan penyimpanan tipe `file` (file JSON lokal)
**Risiko**: pada deployment multi-instance daftar hitam IP tidak dibagikan, penyerang dapat berpindah instance untuk menghindar
**Saran**: di lingkungan produksi ubah `storage.type` menjadi `redis`

### 5.2 Saran: atribut keamanan Session Cookie
**Saat ini**: `secure: false`, `same_site: ''`
**Risiko**: Cookie dapat ditransmisikan melalui HTTP, perlindungan CSRF melemah
**Saran**: di lingkungan produksi set `secure: true`, `same_site: 'Lax'`

### 5.3 Saran: instal dependensi dev PHPStan
**Saat ini**: `composer install --dev` gagal karena timeout jaringan
**Operasi**: `composer install --dev` atau `composer require --dev phpstan/phpstan`

### 5.4 Pengingat: ubah semua kunci sebelum deployment produksi
Kunci placeholder di `.env.docker` harus diganti dengan nilai acak sebelum deployment produksi:
- `JWT_SECRET_KEY`
- `HASHIDS_SALT`, `HASHIDS_ALT_SALT`
- `ENCRYPTION_KEY`, `ENCRYPTABLE_KEY`
- `DB_PASSWORD`

---

## 6. Hasil Dokumentasi

| Dokumen | Jalur |
|------|------|
| Arsitektur keamanan Service | `service/docs/SECURITY.md` |
| Arsitektur keamanan Admin | `admin/docs/SECURITY.md` |
| Laporan audit ini | `docs/SECURITY-AUDIT-REPORT.md` |

---

## 7. Kesimpulan Audit

**Rating keseluruhan pertahanan keamanan: Baik**

- Lapisan pertahanan berlapis lengkap (Nginx → WAF → Rate Limit → Auth → RBAC)
- 31 detektor serangan tercakup global, 28 di antaranya mode cegat
- Perlindungan otentikasi multi-lapis JWT + daftar hitam + kunci akun + daftar hitam IP
- Enkripsi AES-256-CBC lapisan data + obfuscation Hashids
- Sudah diperbaiki tiga masalah kunci di sisi service: header respons keamanan hilang, kunci login hilang, paket WAF hilang
- Saran merupakan optimalisasi konfigurasi lingkungan produksi, bukan kerentanan keamanan

---

## 8. Ronde Perbaikan 2026-08-26 (Penguatan Keamanan)

| Item | Isi perbaikan |
|----|---------|
| Anti-manipulasi pemesanan | Harga item pesanan OrderController::store() selalu mengacu catatan database (service→appointment_service、product→appointment_product), harga klien tidak ikut dihitung; target_type tidak dikenal 422; target_id wajib hashid (raw id terdekode 0 → 422「Produk tidak ada atau sudah tidak aktif」); harga belanja bersama/flash sale sama mengacu DB |
| Pengurangan stok flash sale terpadu | Stok seragam dikurangi dengan row lock di dalam transaksi /api/order store(); SeckillController::buy tidak lagi mengurangi stok di muka (tetap mempertahankan kunci aktivitas Redis + idempotensi client_token); langsung memanggil /api/order dengan seckill_id juga mengurangi stok |
| Penarikan dana teknisi | Saldo dipotong dan diarsipkan sebagai dana dalam perjalanan (pending/approved) saat pengajuan; tinjauan ulang sebelum persetujuan transfer: settled−withdrawn−dalam perjalanan ≥ jumlah penarikan; persetujuan bersamaan tidak akan membayar ganda |
| Callback pembayaran | total_fee callback WeChat diperiksa ketat dengan jumlah pembayaran pesanan, tidak cocok ditolak; log callback Alipay di-redaksi (tidak memuat buyer_id/seller_id, dll.) |
| Perlindungan /install | Setelah instalasi sukses tulis .install.lock, antarmuka install verifikasi ganda (file lock + isInstalled); .gitignore sudah mengabaikan .install.lock |
| Konvergensi dependensi | webman-scout seragam 2.0.5 (service/admin); tambah opensearch-project/opensearch-php ^2.6; dompdf/security-php/webman-database dikunci versi presisi (hapus wildcard "*") |
| Teknik | Hapus service/app/common/StorageService.php (kode mati); admin/app/common/ tambah TechnicianWithdrawalService/WechatPayService (admin deployment mandiri tidak bergantung kode service); phpstan.neon kedua aplikasi diperbaiki dapat dijalankan (php -d memory_limit=2G) |
