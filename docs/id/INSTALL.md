# Sistem Layanan Janji Temu — Panduan Instalasi
> **Languages**: [中文](../INSTALL.md) · [English](../en/INSTALL.md) · [한국어](../ko/INSTALL.md) · [Русский](../ru/INSTALL.md) · [Deutsch](../de/INSTALL.md) · [Français](../fr/INSTALL.md) · [Español](../es/INSTALL.md) · [Português](../pt/INSTALL.md) · [हिन्दी](../hi/INSTALL.md) · [العربية](../ar/INSTALL.md) · [বাংলা](../bn/INSTALL.md) · [日本語](../ja/INSTALL.md)

> Terjemahan bahasa Indonesia · Asli: [中文](../../docs/INSTALL.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## Persyaratan Lingkungan

| Komponen | Versi minimum | Keterangan |
|------|----------|------|
| PHP | 8.3+ | Ekstensi: bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | Prefiks tabel `appointment_`, charset utf8mb4 |
| Redis | 6.0+ | Cache / rate limit / Session / penyimpanan kode verifikasi |
| Composer | 2.x | Manajemen dependensi PHP |
| Elasticsearch | 8.x (opsional) | Pencarian teks penuh, tidak dipasang tidak memengaruhi fungsi inti |

---

## I. Wizard Instalasi Web (disarankan)

Setelah panel admin dimulai, akses `/install` di browser untuk masuk ke wizard instalasi satu klik:

```bash
# 1. Install dependensi dan mulai
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # Port default 8787
```

Buka `http://localhost:8787/install` di browser, selesaikan dalam 4 langkah:

1. **Pemeriksaan lingkungan** — deteksi otomatis versi PHP, ekstensi wajib, izin file
2. **Konfigurasi database** — isi informasi koneksi MySQL, klik uji koneksi
3. **Akun admin** — atur nama aplikasi, username dan kata sandi admin
4. **Eksekusi instalasi** — impor SQL otomatis → buat admin → tulis konfigurasi .env

Setelah instalasi selesai gunakan username dan kata sandi yang diatur untuk login. Instalasi sukses akan menulis file `.install.lock`, antarmuka `/install` verifikasi ganda (file lock + isInstalled) cegah instalasi ulang; `.install.lock` sudah masuk `.gitignore`. Disarankan di lingkungan produksi hapus rute `/install` di `admin/config/route.php`.

---

## II. Instalasi Manual

### 2.1 Klon Proyek

```bash
git clone <repo-url> appointment-php
cd appointment-php
```

### 1.2 Install Dependensi PHP

```bash
# Layanan API bisnis
cd service/
cp .env.example .env
composer install --no-dev --optimize-autoloader

# Panel admin
cd ../admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

### 1.3 Konfigurasi Variabel Lingkungan

Edit `service/.env` (API bisnis) dan `admin/.env` (panel admin), ubah konfigurasi kunci berikut:

```bash
# Koneksi database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment          # service pakai appointment, admin pakai open_admin
DB_USERNAME=root
DB_PASSWORD=your-password

# Koneksi Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Kunci JWT — wajib ganti dengan string acak 64 karakter di produksi
JWT_SECRET_KEY=your-64-char-random-string

# Kunci enkripsi — wajib ganti di produksi
ENCRYPTION_KEY=your-32-byte-key
ENCRYPTABLE_KEY=your-32-byte-key

# Salt Hashids — wajib ganti di produksi
HASHIDS_SALT=your-random-salt

# Mode debug — di produksi wajib false
APP_DEBUG=false
```

> Penjelasan variabel lengkap lihat `service/.env.example` dan `admin/.env.example`.

### 1.4 Buat Database dan Impor

```bash
# Buat database (service dan admin bisa pakai database sama atau terpisah)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Impor skrip instalasi terpadu (semua 54+ tabel + data izin + data demo)
mysql -u root -p appointment < docs/install.sql
mysql -u root -p open_admin < docs/install.sql
```

> `docs/install.sql` adalah penggabungan seluruh file migrasi, total 2723 baris, berisi seluruh struktur tabel dan data seed panel admin serta layanan bisnis. Instalasi baru dieksekusi sekali; eksekusi berulang pada database lama akan terputus karena konflik primary key/kolom, skenario upgrade mohon backup dulu atau tangani konflik manual.

### 1.5 Mulai Layanan

```bash
# Mulai layanan API bisnis (port default 8787)
cd service/
php start.php start -d

# Mulai panel admin (port default 8787)
cd ../admin/
php start.php start -d
```

### 1.6 Verifikasi Instalasi

```bash
# API bisnis
curl http://localhost:8787/api/v1/common/config

# Pemeriksaan kesehatan panel admin
curl http://localhost:8787/health

# Login panel admin (akun dan sandi default lihat di bawah)
curl -X POST http://localhost:8787/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 1.7 Akun Default

| Peran | Username | Kata sandi | Keterangan |
|------|--------|------|------|
| Super admin | `admin` | `admin123` | memiliki seluruh izin |

> Setelah login pertama mohon segera ganti kata sandi.

---

## III. Deployment Docker

### 2.1 Layanan API Bisnis

```bash
cd service/
cp .env.docker .env
# Edit .env, ganti kunci dan sandi
docker-compose up -d
```

Orkestrasi: nginx (80/443) + app (8787) + mysql (3306) + redis (6379) + elasticsearch (9200)

### 2.2 Panel Admin

```bash
cd admin/
cp .env.docker .env
docker-compose up -d
```

### 2.3 Impor Database di Lingkungan Docker

```bash
# Salin install.sql ke kontainer lalu jalankan
docker cp docs/install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## IV. Ringkasan Struktur Database

| Domain | Jumlah tabel | Tabel inti |
|----|------|--------|
| Panel admin | 8 | `appointment_admin_user`, `appointment_admin_role`, `appointment_admin_permission`, `appointment_operation_log` |
| Domain pengguna | 4 | `appointment_user`, `appointment_user_address`, `appointment_user_favorite`, `appointment_user_device` |
| Domain teknisi | 8 | `appointment_technician_profile`, `appointment_technician_schedule`, `appointment_technician_earning`, `appointment_technician_withdrawal`, `appointment_technician_tier_config` |
| Domain layanan | 4 | `appointment_service_category`, `appointment_service`, `appointment_service_package`, `appointment_service_record` |
| Domain pesanan | 5 | `appointment_order`, `appointment_order_item`, `appointment_order_payment`, `appointment_order_refund`, `appointment_order_review` |
| Domain pemasaran | 8 | `appointment_coupon`, `appointment_member_card`, `appointment_gift_card`, `appointment_user_points`, `appointment_promotion` |
| Antrean | 1 | `appointment_queue_number` |
| Domain konten | 5 | `appointment_banner`, `appointment_announcement`, `appointment_faq`, `appointment_feedback`, `appointment_platform_agreement` |
| Domain komunitas | 3 | `appointment_post`, `appointment_comment`, `appointment_moment` |
| Toko | 1 | `appointment_store` |
| Pelatihan | 2 | `appointment_training_course`, `appointment_training_progress` |
| Ujian | 3 | `appointment_exam`, `appointment_exam_question`, `appointment_exam_attempt` |
| Sistem | 3 | `appointment_system_config`, `appointment_notification`, `appointment_signature` |
| **Total** | **55** | |

Semua tabel memakai prefiks `appointment_`, primary key `id` adalah BIGINT non-auto-increment (dihasilkan aplikasi snowflake-php).

---

## V. Menjalankan Pengujian

```bash
# Pengujian API bisnis (21 tests)
cd service/
php vendor/bin/phpunit

# Pengujian panel admin (59 tests)
cd admin/
php vendor/bin/phpunit

# Analisis statis
php vendor/bin/phpstan analyse --level=5 app/

# Pemeriksaan gaya kode
php vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## VI. Konfigurasi Layanan Pihak Ketiga

Isi grup konfigurasi berikut di panel admin「Konfigurasi Sistem」:

| Grup konfigurasi | Kegunaan | Wajib |
|--------|------|------|
| `wechat_pay` | merchant ID WeChat Pay / kunci API / sertifikat | fungsi pembayaran perlu |
| `wechat_app` | AppID / AppSecret WeChat Mini Program | login WeChat perlu |
| `sms` | penyedia SMS (aliyun/tencent) + tanda tangan/template | kode verifikasi SMS perlu |
| `map_service` | layanan peta (amap/tencent) + API Key | fungsi LBS perlu |
| `storage` | penyimpanan objek (oss/cos) + AccessKey/Endpoint | unggah file perlu |

---

## VII. Pertanyaan Umum

**Q: Error saat mulai `Class 'support\Model' not found`**
A: Jalankan `composer dump-autoload`.

**Q: Koneksi database gagal `SQLSTATE[HY000] [2002]`**
A: Periksa konfigurasi `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` di `.env`.

**Q: Error encoding saat impor SQL**
A: Gunakan `mysql -u root -p --default-character-set=utf8mb4 < docs/install.sql`

**Q: Koneksi Redis gagal**
A: Pastikan Redis sudah dimulai, periksa konfigurasi `REDIS_HOST`/`REDIS_PORT`.

**Q: Port terpakai**
A: Ubah port `listen` di `config/server.php`.

**Q: Kode verifikasi tidak tampil**
A: Pastikan ekstensi GD terinstal, konfigurasi `POSTER_CAPTCHA_STORAGE` benar (lokal bisa `file`, produksi gunakan `redis`).

**Q: Elasticsearch tidak bekerja**
A: ES adalah komponen opsional, pastikan konfigurasi `SCOUT_HOSTS` benar dan layanan ES sudah dimulai.

---

## VIII. Struktur Direktori

```
appointment-php/
├── admin/                    # Panel admin (webman v2)
│   ├── app/                  # Kontroler / Model / Middleware
│   ├── config/               # Konfigurasi rute / database / middleware
│   ├── database/             # Skrip cadangan (struktur tabel & data seed terpadu di docs/install.sql)
│   ├── tests/                # Pengujian PHPUnit (59 tests)
│   ├── .env.example          # Templat variabel lingkungan
│   ├── .env.docker           # Variabel lingkungan Docker
│   ├── Dockerfile            # File build Docker
│   └── docker-compose.yml    # Orkestrasi Docker
├── service/                  # Layanan API bisnis (webman v2)
│   ├── app/                  # Kontroler / Model / Middleware
│   ├── config/               # Konfigurasi keamanan / rute / database
│   ├── seed.php              # Penjalankan seed data demo (membaca segmen data demo docs/install.sql)
│   ├── tests/                # Pengujian PHPUnit (21 tests)
│   ├── .env.example          # Templat variabel lingkungan
│   ├── .env.docker           # Variabel lingkungan Docker
│   ├── Dockerfile            # File build Docker
│   └── docker-compose.yml    # Orkestrasi Docker
├── docs/                     # Dokumentasi
│   ├── INSTALL.md            # Panduan instalasi ini
│   ├── install.sql           # Skrip instalasi database terpadu (2723 baris)
│   ├── ARCHITECTURE.md       # Dokumen desain arsitektur
│   ├── API.md                # Dokumen referensi API
│   └── AUDIT-REPORT.md       # Laporan audit
└── .github/workflows/        # Pipeline CI/CD
    └── ci.yml
```
