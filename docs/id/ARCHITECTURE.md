# Penjelasan Arsitektur
> **Languages**: [中文](../ARCHITECTURE.md) · [English](../en/ARCHITECTURE.md) · [한국어](../ko/ARCHITECTURE.md) · [Русский](../ru/ARCHITECTURE.md) · [Deutsch](../de/ARCHITECTURE.md) · [Français](../fr/ARCHITECTURE.md) · [Español](../es/ARCHITECTURE.md) · [Português](../pt/ARCHITECTURE.md) · [हिन्दी](../hi/ARCHITECTURE.md) · [العربية](../ar/ARCHITECTURE.md) · [বাংলা](../bn/ARCHITECTURE.md) · [日本語](../ja/ARCHITECTURE.md)

> Terjemahan bahasa Indonesia · Asli: [中文](../../docs/ARCHITECTURE.md)

## Ringkasan Sistem

Sistem Layanan Janji Temu menggunakan arsitektur tiga platform + dua layanan:

```
┌─────────────────────────────────────────────────────┐
│                    用户终端层                         │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ 微信小程序     │  │ Flutter APP   │                │
│  │ apps/wechat/  │  │ apps/flutter/ │                │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │     功能等价      │                         │
│         └────────┬─────────┘                         │
│                  │ 客户/技师 身份切换                   │
├──────────────────┼──────────────────────────────────┤
│              业务API层                                 │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ service/ API  │  │ admin/ API    │                │
│  │ 端口 8787     │  │ 端口 8787     │                │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │                  │                          │
│         └────────┬─────────┘                          │
│                  │ 共享 MySQL/Redis/ES                 │
├──────────────────┼──────────────────────────────────┤
│                  数据层                                 │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌──────────┐     │
│  │ MySQL  │ │ Redis  │ │  ES    │ │第三方服务 │     │
│  └────────┘ └────────┘ └────────┘ └──────────┘     │
└─────────────────────────────────────────────────────┘
```

## Komposisi Proyek

### service/ — Layanan API Bisnis

Menyediakan seluruh antarmuka bisnis untuk WeChat Mini Program dan Flutter APP. webman v2, port 8787.

**Pembagian modul:**

| Modul | Jalur | Otentikasi | Keterangan |
|------|------|------|------|
| API publik | `api/` | Tanpa | login/registrasi/kode verifikasi/callback WeChat |
| Modul pengguna | `user/` | JWT | profil/alamat/favorit/feedback/promosi |
| Modul teknisi | `technician/` | JWT+teknisi | arsip/jadwal/workbench/verifikasi/member/pendapatan/penarikan dana |
| Modul layanan | `service/` | Campuran | kategori/item/pencarian/toko |
| Modul pesanan | `order/` | JWT | keranjang/buat pesanan/pembayaran/refund/verifikasi/ulasan (OrderController dipecah menjadi 10 trait per domain bisnis, rute dan nama metode tidak berubah) |
| Modul pemasaran | `marketing/` | JWT | kupon/kartu member(kartu kunjungan)/poin/kartu hadiah/hak member |
| Modul dompet | `wallet/` | JWT | saldo/top-up/riwayat transaksi/pembayaran saldo |
| Modul konten | `content/` | Campuran | banner/pengumuman/notifikasi |
| Modul LBS | `lbs/` | Publik | kota/toko terdekat |

### admin/ — Panel Admin

Panel admin PC. webman v2 + Flutter Web, port 8787.

**Modul yang ada:** Otentikasi, dashboard, manajemen pengguna, peran & izin, konfigurasi sistem, log operasi, unggah file, proteksi keamanan

**Distribusi model:** `admin/app/model/` hanya menyimpan 6 model khusus (AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig), model lainnya berbagi versi service melalui composer psr-4 (`app\model\` → `../service/app/model/`), menghindari drift model ganda; kelas dasar `support\Model` diselaraskan dengan service, metode relasi `UserPointsExchange::user()` digabung ke model versi service.

**Modul ekstensi:** manajemen teknisi, manajemen member, manajemen toko, manajemen layanan/produk, manajemen pesanan, kupon, kartu member, audit penarikan dana, manajemen ulasan, statistik laporan, manajemen keuangan, manajemen konten, pengaturan sistem

### apps/ — Frontend Sisi Pengguna

| Direktori | Teknologi | Platform |
|------|------|------|
| `apps/wechat/` | WeChat Mini Program native | WeChat |
| `apps/flutter/` | Flutter 3.x + GetX + Dio | iOS + Android |

## Komponen Inti

### Snowflake ID

Semua primary key dihasilkan oleh `erikwang2013/snowflake-php`, BIGINT non-auto-increment, menjamin unik global terdistribusi. `service/support/Model::nextId()` memakai ulang satu instance Snowflake dalam proses, 64 salinan `generateId()` model telah dihapus (seragam mewarisi implementasi kelas dasar).

### Hashids

ID dalam permintaan/respons API di-encode melalui `erikwang2013/hashids`, mengekspos string hash ke luar.

### Otentikasi JWT

`erikwang2013/jwt-webman` Bearer Token, berlaku 7 hari, mendukung refresh dan blacklist.

### Enkripsi Data

- **Lapisan API**: `erikwang2013/encryption` enkripsi/dekripsi data sensitif
- **Lapisan DB**: trait `erikwang2013/encryptable` enkripsi/dekripsi kolom otomatis

### Proteksi Keamanan

- `erikwang2013/security-php`: 31 jenis deteksi serangan
- `erikwang2013/poster-php`: verifikasi acak operasi sensitif
- Kunci login: 5 kali gagal kunci 15 menit
- Batas konkurensi: maksimal 3 Token valid

### Dokumentasi API

`hg/apidoc` menghasilkan dokumen spesifikasi OpenAPI 3.0, terpisah untuk sisi admin dan klien:

| Sisi | Alamat | Keterangan |
|------|------|------|
| Admin | `admin/ GET /api/docs` | API panel admin (JWT+RBAC) |
| Klien | `service/ GET /api/docs` | API bisnis (JWT Bearer) |

Dokumen diakses publik, dapat diimpor ke Swagger UI untuk melihat dokumentasi antarmuka interaktif.

### Elasticsearch

`erikwang2013/webman-scout` model sinkron otomatis ke ES, mendukung pencarian teks penuh.

## Rantai Eksekusi Middleware

### Middleware service/

```
公开API:  Cors → Security(31种检测) → RateLimit → ApiVersion → Controller
用户API:  Cors → Security → RateLimit → Auth(JWT) → Controller
技师API:  Cors → Security → RateLimit → ApiVersion → Auth → TechnicianAuth → Controller
```

### Middleware admin/

```
公开API:  Cors → Security → RateLimit → Controller
管理API:  Cors → Security → RateLimit → AdminAuth(JWT) → AdminPermission(RBAC) → OperationLog → Controller
健康检查: Cors → Security → RateLimit → Controller
```

## Alur Data

### Alur Permintaan

```
客户端 → Cors → Security → RateLimit → Auth(JWT) → [TechnicianAuth] → Controller
    → Model(encryptable加解密) → BaseController(hashids编码) → JSON响应
```

### Alur Janji Temu

```
浏览服务 → 选门店/技师/时间 → 提交订单 → Redis锁技师3分钟
    → 微信支付 → 通知技师 → 服务开始 → 服务完成 → 评价 → 订单完成
```

## 8 Sumber Operasi

## Ekstensi Terbaru

| Kategori | Fitur |
|------|------|
| Real-time | Push WebSocket / callback pembayaran / APNs+FCM |
| Pesan | Push subscription message (sendSubscribeMessage 3 skenario event pesanan) |
| Dompet | top-up saldo / pembayaran saldo / isi ulang refund |
| Toko | pencetakan Bluetooth / stempel elektronik / antrean nomor panggilan |
| Teknisi | ujian online / tampilan video pendek / workbench (today/records/start/complete) |
| Komunitas | posting/komentar/suka/audit |
| Sistem | multi-bahasa (cn/en) / pembatalan pesanan otomatis / data seed |

Kolom `source` mencatat sumber operasi: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### Integrasi Layanan Pihak Ketiga

| Layanan | Kelas | Kemampuan |
|------|------|------|
| WeChat Pay | WechatPayService | unified order/query/refund/penarikan ke saldo WeChat |
| SMS | SmsService | kanal ganda Alibaba Cloud/Tencent Cloud |
| Peta | MapService | Amap/Tencent reverse geocoding/jarak/navigasi |
| Template message | WechatTemplateMessageService | push pesanan/refund/pengingat + subscription message (sendSubscribeMessage 3 skenario event pesanan) |
