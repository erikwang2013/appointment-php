# Spesifikasi Desain Sistem Layanan Janji Temu

> Terjemahan bahasa Indonesia · Asli: [中文](../../../docs/superpowers/specs/2026-05-26-appointment-system-design.md)

## Ringkasan

Sistem layanan janji temu tiga platform: sisi pengguna (WeChat Mini Program + Flutter APP) + workbench teknisi (peralihan identitas dalam APP yang sama) + panel administrasi (PC Web).

## Keputusan Arsitektur

| Keputusan | Solusi |
|------|------|
| Arsitektur backend | `admin/` (API panel administrasi) + `service/` (API bisnis), dua layanan berbagi MySQL/Redis |
| Mini program sisi pengguna | WeChat Mini Program asli `apps/wechat/` |
| APP sisi pengguna | Flutter `apps/flutter/` (iOS + Android) |
| Identitas pengguna | Akun terpadu, identitas pelanggan/teknisi dapat dialihkan |
| Hubungan mini program dengan APP | Fungsi sepenuhnya sama, hanya berbeda platform |
| Frontend panel administrasi | Perluasan Flutter Web yang ada (`admin/apps/flutter/`) |
| Backend panel administrasi | Perluasan modul bisnis webman v2 yang ada (`admin/`) |
| Layanan pihak ketiga | Login/pembayaran/SMS/peta WeChat — skema integrasi yang dicadangkan |

## Diagram Arsitektur Sistem

```
┌──────────────────────────────────────────────────────────┐
│                      用户终端层                            │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ 微信小程序        │  │ Flutter APP       │              │
│  │ apps/wechat/      │  │ apps/flutter/     │              │
│  │ (原生WXML/WXSS)   │  │ (iOS + Android)   │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │         功能完全相同  │                        │
│           └──────────┬──────────┘                        │
│                      │ 客户身份 / 技师身份切换              │
├──────────────────────┼──────────────────────────────────┤
│              业务API网关                                   │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ service/ API      │  │ admin/ API        │              │
│  │ (webman v2)       │  │ (webman v2)       │              │
│  │ 用户/订单/支付/    │  │ 管理后台接口       │              │
│  │ 技师/门店/营销...   │  │ (已建 + 扩展)     │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │                      │                        │
│           └──────────┬───────────┘                        │
│                      │                                    │
├──────────────────────┼──────────────────────────────────┤
│                  数据层                                    │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────────────┐    │
│  │ MySQL  │ │ Redis  │ │  ES    │ │ 第三方服务      │    │
│  │ 8.0    │ │ 缓存/   │ │ 搜索   │ │ 微信/短信/地图  │    │
│  │        │ │ 限流/   │ │        │ │ (预留对接)     │    │
│  │        │ │ Session │ │        │ │                │    │
│  └────────┘ └────────┘ └────────┘ └────────────────┘    │
└──────────────────────────────────────────────────────────┘
```

## Tabel Inti Database

Semua tabel menggunakan prefiks `erik_`, primary key BIGINT non-auto-increment (dihasilkan Snowflake). Kolom sensitif menggunakan trait encryptable untuk enkripsi/dekripsi.

### Domain Pengguna dan Identitas

| Nama tabel | Keterangan | Kolom inti |
|------|------|----------|
| `erik_user` | Tabel pengguna terpadu | phone, password, wx_openid, wx_unionid, avatar, nickname, user_type(customer/technician), status. Pengguna teknisi sekaligus memiliki fungsi pelanggan, dapat bebas mengalihkan identitas aktif saat ini |
| `erik_user_address` | Alamat pengguna | user_id, contact_name, contact_phone, province, city, district, detail, is_default |
| `erik_technician_profile` | Arsip teknisi | user_id, real_name, gender, id_card, id_card_front, id_card_back, avatar, rating, order_count, status(pending/approved/rejected), intro |
| `erik_technician_schedule` | Jadwal teknisi | technician_id, date, time_slots(JSON), status |
| `erik_technician_service` | Item layanan teknisi | technician_id, service_id |
| `erik_technician_earnings` | Riwayat pendapatan teknisi | technician_id, order_id, type(commission/bonus/penalty), amount, status |
| `erik_technician_withdrawal` | Catatan penarikan teknisi | technician_id, amount, actual_amount, commission_fee, account_info, status, reviewed_at |
| `erik_technician_attendance` | Kehadiran teknisi | technician_id, date, check_in_at, check_out_at, clean_photo |
| `erik_technician_member_note` | Arsip anggota | technician_id, user_id, content, written_at |

### Domain Layanan dan Produk

| Nama tabel | Keterangan | Kolom inti |
|------|------|----------|
| `erik_service_category` | Kategori layanan | name, icon, parent_id, sort, status |
| `erik_service` | Item layanan | category_id, name, description, cover_image, images(JSON), price, duration, sales_volume, specs(JSON), status |
| `erik_product` | Produk | category_id, name, cover_image, price, stock, sales_volume, type, status |
| `erik_store` | Toko | name, address, lat, lng, phone, business_hours(JSON), images, status |

### Domain Pesanan

| Nama tabel | Keterangan | Kolom inti |
|------|------|----------|
| `erik_order` | Tabel utama pesanan | order_no, user_id, technician_id, store_id, total_amount, discount_amount, paid_amount, status, service_time, cancel_reason, remark |
| `erik_order_item` | Rincian pesanan | order_id, service_id, product_id, type, name, price, quantity, spec_info |
| `erik_order_payment` | Catatan pembayaran | order_id, pay_type(wechat), transaction_id, amount, status, paid_at |
| `erik_order_refund` | Catatan refund | order_id, payment_id, refund_no, amount, ratio, reason, status |
| `erik_order_review` | Ulasan layanan | order_id, user_id, technician_id, rating, content, images |
| `erik_order_verification` | Catatan verifikasi | order_id, code, verified_at, verified_by, location |

### Domain Pemasaran

| Nama tabel | Keterangan | Kolom inti |
|------|------|----------|
| `erik_coupon` | Definisi kupon | name, type, amount, min_amount, total_qty, remain_qty, start_at, end_at, status |
| `erik_user_coupon` | Kupon pengguna | user_id, coupon_id, status(available/used/expired), used_at |
| `erik_member_card` | Definisi kartu member | name, type(month/vip/times), price, duration_days, total_times, services(JSON) |
| `erik_user_member_card` | Kartu member pengguna | user_id, card_id, start_at, end_at, total_times, used_times, status |
| `erik_member_card_usage` | Catatan penggunaan kartu kunjungan | user_card_id, order_id, service_id, used_at |
| `erik_user_points` | Riwayat poin | user_id, type(earn/use), points, source, order_id |
| `erik_gift_card` | Kartu hadiah | code, type, amount_or_gift, status, used_by, used_at |
| `erik_user_referral` | Promosi pengguna | referrer_id, referred_user_id, reward_type, reward_amount, registered_at, first_order_at |

### Domain Konten dan Notifikasi

| Nama tabel | Keterangan | Kolom inti |
|------|------|----------|
| `erik_banner` | Banner | position, image, jump_type(url/detail/none), jump_value, sort, status |
| `erik_announcement` | Pengumuman | content, status, published_at |
| `erik_platform_agreement` | Perjanjian platform | type(user_agreement/privacy_policy/service_agreement), title, content, version |
| `erik_faq` | Pertanyaan umum | title, content, sort |
| `erik_feedback` | Umpan balik | user_id, content, images, handler_reply, status(pending/handled) |
| `erik_moment` | Dinamika momen | content, images, published_at |
| `erik_notification` | Notifikasi pesan | user_id, type(order/system), title, content, is_read, created_at |

### Domain Keuangan (sisi admin)

| Nama tabel | Keterangan | Kolom inti |
|------|------|----------|
| `erik_finance_transaction` | Riwayat pemasukan/pengeluaran | user_id, order_id, type, direction(income/expense), amount, actual_amount, commission, status |
| `erik_technician_commission_config` | Konfigurasi komisi | technician_id, commission_rate, settlement_cycle |
| `erik_withdrawal_account` | Akun penarikan | user_id, type(wechat), account_name, account_no |
| `erik_withdrawal_config` | Konfigurasi batas penarikan | min_amount, reserve_amount, round_to_hundred |

## Modul API Service

### API Publik (tanpa otentikasi)
- **AuthController** — login/registrasi/lupa kata sandi/mode tamu/peralihan identitas
- **CaptchaController** — kode verifikasi SMS
- **WechatController** — otorisasi/login/callback pembayaran WeChat
- **CommonController** — teks perjanjian/tentang kami/informasi versi

### Modul Pengguna `user/` (perlu otentikasi)
- **ProfileController** — informasi pribadi/ubah kata sandi/ganti ponsel/penonaktifan
- **AddressController** — CRUD alamat penerima
- **FavoriteController** — favorit
- **FeedbackController** — umpan balik
- **ReferralController** — promosi/daftar pengguna yang direkomendasikan

### Modul Teknisi `technician/` (perlu identitas teknisi + middleware TechnicianAuth)
- **ProfileController** — arsip teknisi/pengajuan masuk
- **ScheduleController** — pengaturan jadwal
- **OrderController** — sudah dipesan belum diverifikasi/selesai/verifikasi scan QR
- **MemberController** — member saya/arsip member
- **EarningsController** — pendapatan/dana dalam perjalanan
- **WithdrawalController** — penarikan dana
- **AttendanceController** — kehadiran/foto kebersihan

### Modul Layanan `service/`
- **CategoryController** — kategori layanan
- **ItemController** — daftar dan detail layanan/produk
- **SearchController** — pencarian
- **StoreController** — daftar/detail toko

### Modul Pesanan `order/` (perlu otentikasi)
- **CartController** — keranjang belanja
- **OrderController** — buat pesanan/daftar pesanan/detail/batalkan
- **PaymentController** — pembayaran/refund
- **VerificationController** — verifikasi QR code
- **ReviewController** — ulasan

### Modul Pemasaran `marketing/` (perlu otentikasi)
- **CouponController** — daftar kupon/ambil/gunakan
- **MemberCardController** — kartu member/kartu kunjungan
- **PointsController** — poin
- **GiftCardController** — kartu hadiah

### Modul Konten `content/`
- **BannerController** — banner
- **AnnouncementController** — pengumuman
- **NotificationController** — notifikasi pesan

### Modul LBS
- **LocationController** — lokasi/peralihan kota/toko terdekat

### Kemampuan Umum `common/`
- SnowflakeService — pembuatan ID
- HashidsService — enkripsi/dekripsi ID
- EncryptionService — enkripsi/dekripsi data sensitif
- WechatPayService — pembayaran WeChat (dicadangkan)
- WechatAuthService — login WeChat (dicadangkan)
- SmsService — layanan SMS (dicadangkan)
- MapService — layanan peta (dicadangkan)

### Middleware
- Auth — otentikasi JWT (berbagi paket erikwang2013/jwt-webman dengan admin)
- TechnicianAuth — pemeriksaan identitas teknisi
- RateLimit — pembatasan laju (berbagi dengan admin)

## Perluasan Panel Administrasi Admin

Tambahkan pengontrol baru di atas framework yang ada:

### Manajemen Teknisi
- **TechnicianController** — daftar teknisi/pencarian/ekspor/review/pengelolaan jadwal/pengaturan item layanan teknisi/progres belajar kursus

### Perluasan Manajemen Pengguna
- **MemberController** — daftar member/pengaturan level/statistik konsumsi

### Manajemen Toko
- **StoreController** — CRUD toko/aktif-nonaktif

### Manajemen Layanan
- **ServiceController** — daftar layanan/CRUD/desain item kartu
- **ServiceCategoryController** — manajemen kategori
- **ProductController** — daftar produk/CRUD

### Manajemen Toko Online
- **MallOrderController** — pesanan toko/pengiriman/purna jual/ulasan
- **SalesStatsController** — statistik penjualan

### Manajemen Pesanan
- **AppointmentOrderController** — pesanan belum dipakai/batalkan/konfirmasi selesai

### Aktivitas Kupon
- **CouponController** — CRUD kupon/penyaluran

### Manajemen Keuangan
- **FinanceController** — pembagian pendapatan pesanan/riwayat pemasukan-pengeluaran
- **WithdrawalController** — review penarikan teknisi/selesaikan
- **CommissionController** — pengaturan komisi/hadiah-hukuman/query saldo
- **WithdrawalAccountController** — manajemen akun penarikan
- **WithdrawalConfigController** — konfigurasi batas penarikan

### Manajemen Konten
- **BannerController** — CRUD banner
- **AnnouncementController** — CRUD pengumuman
- **FaqController** — CRUD FAQ
- **FeedbackController** — penanganan umpan balik
- **MomentController** — review dinamika momen
- **AgreementController** — penyuntingan perjanjian (perjanjian pengguna/perjanjian privasi/perjanjian layanan)
- **AboutController** — pengaturan tentang kami

### Pengaturan
- **SystemMessageController** — pengaturan pesan sistem
- **AdminUserController** — manajemen sub-akun (berbasis RBAC yang ada)

### Perluasan Dashboard
- Kartu statistik real-time: jumlah pengguna/jumlah pesanan/jumlah teknisi/jumlah pesanan layanan
- Grafik garis: volume pesanan/jumlah/daily pengguna baru/aktivitas
- Navigasi cepat: tombol modul yang menunggu diproses
- Pesan dalam situs: notifikasi pesanan baru/notifikasi refund

## Struktur Halaman Sisi Pengguna

WeChat Mini Program dan Flutter APP memiliki fungsi yang sepenuhnya sama.

### auth/ — Otentikasi
- login — masuk (ponsel/kode verifikasi/WeChat/entri tamu)
- register — daftar (ponsel+kode verifikasi+kata sandi+kode rekomendasi)
- forget-password — lupa kata sandi
- agreement — lihat perjanjian

### home/ — Beranda
- index — beranda (banner+pengumuman+kategori layanan+rekomendasi)
- search — halaman pencarian

### service/ — Layanan
- list — daftar layanan (filter berdasarkan kategori)
- detail — detail layanan (info dasar+ulasan+janji temu langsung)
- product-list — daftar produk

### order/ — Pesanan
- confirm — konfirmasi pesanan (toko/teknisi/waktu/kupon/catatan/perjanjian)
- payment — halaman pembayaran
- payment-success — pembayaran berhasil
- list — semua pesanan (filter Tab berdasarkan status)
- detail — detail pesanan
- review — ulasan layanan
- verification — verifikasi QR code

### cart/ — Keranjang
- index — daftar keranjang

### technician/ — Teknisi (perspektif pelanggan)
- list — daftar teknisi (urut jarak terdekat)
- detail — detail teknisi (ulasan/item layanan yang tersedia/janji temu langsung)
- apply — pengajuan masuk teknisi

### tech-work/ — Workbench Teknisi (identitas teknisi)
- index — beranda workbench (pesanan hari ini/ringkasan pendapatan)
- schedule — pengaturan jadwal
- order-list — pesanan saya (sudah dipesan belum diverifikasi/selesai)
- scan-verify — verifikasi scan QR
- member-list — member saya
- member-detail — detail member/edit arsip
- earnings — pendapatan saya
- withdrawal — penarikan dana
- transaction-list — rincian transaksi
- attendance — upload kehadiran/foto kebersihan
- training — pelatihan profesional

### user/ — Pusat Pribadi
- index — informasi pribadi (avatar/nama panggilan/kartu member/favorit/entri kupon)
- settings — pengaturan (ubah kata sandi/ganti ponsel/perjanjian/pembaruan/penonaktifan/keluar)
- switch-role — peralihan identitas (pelanggan ↔ teknisi)

### marketing/ — Pemasaran
- coupon-list — daftar kupon
- member-card — kartu member saya
- points — poin saya
- gift-card — kartu hadiah saya
- referral — promosi (penjelasan+poster QR code+daftar pengguna yang direkomendasikan)

### Halaman Lain
- message/ — daftar/rincian pesan
- store/list, store/detail — daftar toko (urut LBS)/detail (navigasi)
- other/about — tentang kami
- other/feedback — umpan balik
- other/official-account — ikuti akun resmi

### Komponen Umum
- navbar, tabbar, service-card, technician-card
- coupon-popup, lbs-selector, empty-state, loading

### Logika Peralihan Identitas
- Navigasi bawah identitas pelanggan: Beranda / Layanan / Keranjang / Pesanan / Saya
- Navigasi bawah identitas teknisi: Workbench / Pesanan / Member / Pendapatan / Saya
- Halaman「Saya」menyediakan entri peralihan identitas
- Pengguna yang belum menjadi teknisi diarahkan ke halaman pengajuan masuk saat beralih ke identitas teknisi

## Penjelasan Alur Pembelian

Sistem memiliki dua alur pembelian yang berbeda:

### Alur Janji Temu Layanan (pemesanan langsung, tanpa keranjang)
- Halaman detail item layanan → konfirmasi pesanan (pilih toko/teknisi/waktu) → pembayaran → verifikasi
- Eksklusivitas sumber daya teknisi: teknisi dikunci 3 menit saat masuk halaman konfirmasi pesanan
- Digunakan untuk item layanan offline seperti pijat, kecantikan, dll.

### Alur Pembelian Produk (mode keranjang)
- Daftar produk → tambahkan ke keranjang → konfirmasi keranjang → kirim pesanan → pembayaran → pengiriman/penerimaan
- Mendukung ubah jumlah, hapus produk
- Digunakan untuk penjualan barang fisik atau kartu voucher

## Aturan Bisnis Kunci

### Mekanisme Kunci Teknisi
- Tidak bisa banyak orang memesan satu teknisi pada waktu bersamaan
- Saat pengguna masuk halaman konfirmasi pesanan, teknisi dikunci 3 menit melalui Redis SETNX
- Keluar dari halaman pemesanan atau timeout melepaskan kunci otomatis

### Aturan Refund
| Kondisi | Proporsi refund |
|------|----------|
| Dalam 15 menit pemesanan atau jarak mulai >6 jam | 100% |
| Jarak mulai ≤6 jam | 90% |
| Sudah mulai tetapi belum konfirmasi layanan | 80% |
| Setelah layanan dikonfirmasi mulai | 0% (tidak direfund) |

### Aturan Diskon
- Jam sepi (10-12 / 17-18 / setelah 21:00) diskon 90%
- Janji temu 30 menit lebih awal diskon 95% (tidak bisa ditumpuk dengan kupon)

### Penarikan Dana Teknisi
- Setiap tanggal 20 dapat menarik dana, masuk T+1 hari kerja
- Mendukung penarikan ke saldo WeChat
- Pesanan terverifikasi belum diselesaikan, dikonfirmasi otomatis sistem dalam 3 hari
- Wajib menyelesaikan arsip member dalam 24 jam, jika tidak tidak ada komisi

### Bonus Pelanggan Berulang
- Konsumsi kedua ke teknisi yang sama dalam 30 hari → catat bonus
- Upload foto kebersihan setelah layanan

### Aturan Poin
- 1:100 tukar kartu hadiah (dapat dikonfigurasi di panel)
- Rekomendasikan pengguna daftar sukses dan setelah order mendapatkan poin tertentu (diatur di panel)
