# Sistem Layanan Janji Temu — Panduan Instalasi

> Terjemahan bahasa Indonesia · Asli: [中文](../../docs/INSTALL.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## Persyaratan Lingkungan

| Komponen | Versi minimum | Keterangan |
|------|----------|------|
| PHP | 8.3+ | Ekstensi: bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | Prefiks tabel `erik_`, charset utf8mb4 |
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
php start.php start -d     # 默认端口 8787
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
# 数据库连接
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment          # service 用 appointment，admin 用 open_admin
DB_USERNAME=root
DB_PASSWORD=your-password

# Redis 连接
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# JWT 密钥 — 生产环境务必修改为 64 位随机字符串
JWT_SECRET_KEY=your-64-char-random-string

# 加密密钥 — 生产环境务必修改
ENCRYPTION_KEY=your-32-byte-key
ENCRYPTABLE_KEY=your-32-byte-key

# Hashids 盐值 — 生产环境务必修改
HASHIDS_SALT=your-random-salt

# 调试模式 — 生产环境必须设为 false
APP_DEBUG=false
```

> Penjelasan variabel lengkap lihat `service/.env.example` dan `admin/.env.example`.

### 1.4 Buat Database dan Impor

```bash
# 创建数据库（service 和 admin 可使用同一数据库，也可分开）
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 导入统一安装脚本（包含全部 54+ 张表 + 权限数据 + 演示数据）
mysql -u root -p appointment < docs/install.sql
mysql -u root -p open_admin < docs/install.sql
```

> `docs/install.sql` adalah penggabungan seluruh file migrasi, total 2723 baris, berisi seluruh struktur tabel dan data seed panel admin serta layanan bisnis. Instalasi baru dieksekusi sekali; eksekusi berulang pada database lama akan terputus karena konflik primary key/kolom, skenario upgrade mohon backup dulu atau tangani konflik manual.

### 1.5 Mulai Layanan

```bash
# 启动业务 API 服务（默认端口 8787）
cd service/
php start.php start -d

# 启动管理后台（默认端口 8787）
cd ../admin/
php start.php start -d
```

### 1.6 Verifikasi Instalasi

```bash
# 业务 API
curl http://localhost:8787/api/common/config

# 管理后台健康检查
curl http://localhost:8787/health

# 管理后台登录（默认账号密码见下方）
curl -X POST http://localhost:8787/api/auth/login \
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
# 编辑 .env，修改密钥和密码
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
# 将 install.sql 复制到容器中执行
docker cp docs/install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## IV. Ringkasan Struktur Database

| Domain | Jumlah tabel | Tabel inti |
|----|------|--------|
| Panel admin | 8 | `erik_admin_user`, `erik_admin_role`, `erik_admin_permission`, `erik_operation_log` |
| Domain pengguna | 4 | `erik_user`, `erik_user_address`, `erik_user_favorite`, `erik_user_device` |
| Domain teknisi | 8 | `erik_technician_profile`, `erik_technician_schedule`, `erik_technician_earning`, `erik_technician_withdrawal`, `erik_technician_tier_config` |
| Domain layanan | 4 | `erik_service_category`, `erik_service`, `erik_service_package`, `erik_service_record` |
| Domain pesanan | 5 | `erik_order`, `erik_order_item`, `erik_order_payment`, `erik_order_refund`, `erik_order_review` |
| Domain pemasaran | 8 | `erik_coupon`, `erik_member_card`, `erik_gift_card`, `erik_user_points`, `erik_promotion` |
| Antrean | 1 | `erik_queue_number` |
| Domain konten | 5 | `erik_banner`, `erik_announcement`, `erik_faq`, `erik_feedback`, `erik_platform_agreement` |
| Domain komunitas | 3 | `erik_post`, `erik_comment`, `erik_moment` |
| Toko | 1 | `erik_store` |
| Pelatihan | 2 | `erik_training_course`, `erik_training_progress` |
| Ujian | 3 | `erik_exam`, `erik_exam_question`, `erik_exam_attempt` |
| Sistem | 3 | `erik_system_config`, `erik_notification`, `erik_signature` |
| **Total** | **55** | |

Semua tabel memakai prefiks `erik_`, primary key `id` adalah BIGINT non-auto-increment (dihasilkan aplikasi snowflake-php).

---

## V. Menjalankan Pengujian

```bash
# 业务 API 测试（21 tests）
cd service/
php vendor/bin/phpunit

# 管理后台测试（59 tests）
cd admin/
php vendor/bin/phpunit

# 静态分析
php vendor/bin/phpstan analyse --level=5 app/

# 代码风格检查
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
├── admin/                    # 管理后台 (webman v2)
│   ├── app/                  # 控制器 / 模型 / 中间件
│   ├── config/               # 路由 / 数据库 / 中间件配置
│   ├── database/             # 备份脚本（表结构与种子数据统一见 docs/install.sql）
│   ├── tests/                # PHPUnit 测试 (59 tests)
│   ├── .env.example          # 环境变量模板
│   ├── .env.docker           # Docker 环境变量
│   ├── Dockerfile            # Docker 构建文件
│   └── docker-compose.yml    # Docker 编排
├── service/                  # 业务 API 服务 (webman v2)
│   ├── app/                  # 控制器 / 模型 / 中间件
│   ├── config/               # 安全 / 路由 / 数据库配置
│   ├── seed.php              # 演示数据种子运行器（读取 docs/install.sql 演示数据段）
│   ├── tests/                # PHPUnit 测试 (21 tests)
│   ├── .env.example          # 环境变量模板
│   ├── .env.docker           # Docker 环境变量
│   ├── Dockerfile            # Docker 构建文件
│   └── docker-compose.yml    # Docker 编排
├── docs/                     # 文档
│   ├── INSTALL.md            # 本安装指南
│   ├── install.sql           # 统一数据库安装脚本（2723 行）
│   ├── ARCHITECTURE.md       # 架构设计文档
│   ├── API.md                # API 参考文档
│   └── AUDIT-REPORT.md       # 审查报告
└── .github/workflows/        # CI/CD 流水线
    └── ci.yml
```
