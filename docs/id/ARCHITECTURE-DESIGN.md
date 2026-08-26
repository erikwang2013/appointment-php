# Desain Arsitektur

> Terjemahan bahasa Indonesia · Asli: [中文](../../docs/ARCHITECTURE-DESIGN.md)

## Arsitektur Berlapis

```
┌─────────────────────────────────────────┐
│              表现层 (Presentation)        │
│  微信小程序 / Flutter APP / Flutter Web   │
├─────────────────────────────────────────┤
│              路由层 (Route)               │
│  config/route.php — 路由分组 + 中间件绑定  │
├─────────────────────────────────────────┤
│            中间件层 (Middleware)           │
│  Cors → Security → RateLimit → Auth      │
│  → TechnicianAuth → OperationLog         │
├─────────────────────────────────────────┤
│            控制器层 (Controller)           │
│  BaseController → 各业务Controller        │
├─────────────────────────────────────────┤
│             服务层 (Service)              │
│  common/ — Snowflake/Hashids/Encryption  │
├─────────────────────────────────────────┤
│             模型层 (Model)                │
│  Eloquent ORM + Encryptable + Scout      │
├─────────────────────────────────────────┤
│              数据层 (Data)                │
│  MySQL / Redis / Elasticsearch           │
└─────────────────────────────────────────┘
```

## Desain Middleware

### Rantai Eksekusi

```
Cors → Security(31种攻击检测) → RateLimit → Auth(JWT+用户状态)
    → [TechnicianAuth(技师身份)] → [AdminPermission(RBAC)] → [OperationLog(8端来源)]
    → Controller
```

### Tanggung Jawab Middleware

| Middleware | Ruang lingkup | Fungsi |
|--------|--------|------|
| Cors | Global | preflight OPTIONS + header respons CORS |
| Security | Global | erikwang2013/security-php, 31 jenis deteksi serangan |
| RateLimit | Global | sliding window Redis + atomik Lua |
| Auth | Grup rute | parsing JWT + validasi keberadaan/status pengguna |
| TechnicianAuth | Grup rute | kueri arsip teknisi + validasi status approved |
| AdminAuth | Grup rute | otentikasi JWT sisi Admin + blacklist |
| AdminPermission | Grup rute | validasi izin RBAC, cache Redis 60s |
| OperationLog | Grup rute | log operasi + deteksi otomatis 8 sumber |

### Strategi Pembatasan Laju

| Antarmuka | Batas |
|------|------|
| Default | 60 kali/menit/IP |
| Login | 10 kali/menit |
| Registrasi | 5 kali/menit |
| Kode verifikasi | 1 kali/60 detik/nomor ponsel |

## Prinsip Desain Basis Data

### Strategi Primary Key

- Semua primary key: BIGINT UNSIGNED NOT NULL, non-auto-increment
- Dihasilkan oleh `erikwang2013/snowflake-php` di lapisan aplikasi
- Model: `$incrementing = false`, `$keyType = 'string'`

### Prefiks Tabel

Seragam prefiks `erik_`, dikonfigurasi di `config/database.php`. Model menulis nama tabel asli, ORM menambah prefiks otomatis.

### Enkripsi Kolom Sensitif

Menggunakan trait `erikwang2013/encryptable`:

```php
use Erikwang2013\Encryptable\Encryptable;

class User extends Model
{
    use Encryptable;
    protected array $encryptable = [
        'phone', 'wx_openid', 'wx_unionid', 'real_name',
    ];
}
```

Panjang kolom terenkripsi VARCHAR diatur 500 (pembengkakan data terenkripsi).

### Soft Delete & Timestamp

- Eloquent SoftDeletes: `deleted_at` DATETIME DEFAULT NULL
- Semua tabel berisi `created_at` + `updated_at`

## Mekanisme Enkripsi/Dekripsi ID API

### Permintaan: decodeIds()

ID ber-encode hashids yang dikirim frontend → controller memanggil `$this->decodeIds($request->all())` untuk decode.

### Respons: encodeIds()

ID hasil kueri DB → `BaseController::success()` otomatis memanggil `encodeIds()` untuk encode → mengembalikan string hashids.

### Aturan

Proses rekursif pada kolom bernama `id` atau berakhiran `_id` di dalam array.

## Desain Keamanan

### Pertahanan Berlapis

```
WAF → Cors → Security(31种检测) → RateLimit → Auth(JWT+状态)
    → [身份校验] → [RBAC] → Controller(Model加密) → 响应
```

### Keamanan Otentikasi

- Kata sandi: hash bcrypt
- JWT: berlaku 7 hari + refresh + blacklist
- Kunci: 5 kali gagal → 15 menit
- Konkurensi: maksimal 3 Token

### Keamanan Data

- Lapisan API: erikwang2013/encryption
- Lapisan DB: trait erikwang2013/encryptable
- Log: data sensitif tidak masuk log

### Keamanan Operasi

- erikwang2013/poster-php: verifikasi sebelum hapus/audit/penarikan dana
- Middleware Security: deteksi XSS/Injeksi SQL/CSRF/traversal path

## Integrasi Elasticsearch

`erikwang2013/webman-scout` sinkron otomatis model ke ES:

```php
use Erikwang2013\WebmanScout\Searchable;

class Service extends Model
{
    use Searchable;
    public function searchableAs(): string { return 'erik_services'; }
}
```

## Ekspor Excel/PDF

- Excel: PhpSpreadsheet, kolom sensitif otomatis dideidentifikasi
- PDF: ekspor visualisasi panel Dashboard

## Deteksi 8 Sumber

OperationLog diurai melalui User-Agent:

```
iPad → iPadOS / Mac → macOS / Windows → Windows
Linux → Linux / iPhone → ios / Android → android
HarmonyOS → harmonyOS / 其他 → web
```


## Pengujian TDD

| Proyek | Jumlah pengujian | Status |
|------|--------|------|
| admin/ | 60 | ✅ Lulus |
| service/ | 21 | ✅ Lulus |
| Total | 81 | ✅ |

Cakupan pengujian: aturan refund / status pesanan / Hashids / sistem antrean / enkripsi / kode verifikasi
