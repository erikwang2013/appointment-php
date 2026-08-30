# Sistem Layanan Janji Temu
> **Languages**: [中文](../README.md) · [English](../en/README.md) · [한국어](../ko/README.md) · [Русский](../ru/README.md) · [Deutsch](../de/README.md) · [Français](../fr/README.md) · [Español](../es/README.md) · [Português](../pt/README.md) · [हिन्दी](../hi/README.md) · [العربية](../ar/README.md) · [বাংলা](../bn/README.md) · [日本語](../ja/README.md)

> Terjemahan bahasa Indonesia · Asli: [中文](../../README.md)

Platform manajemen layanan janji temu untuk empat ujung: Mini Program WeChat untuk pengguna + Aplikasi Flutter + Aplikasi HarmonyOS (beralih identitas dengan akun yang sama), serta Panel Admin PC.

> **Status Proyek**: Semua selesai ✅ | 143 pengontrol (service 69 / admin 74) | 87 model | 722 tes (service 558 / admin 164) | 95 tabel data | 388 rute (service 227 / admin 161)

## Pengenalan Proyek

<img src="mascot.svg" alt="Maskot sistem layanan janji temu — Kelinci Janji Temu (animasi SVG)" width="200" align="right">

**Sistem Layanan Janji Temu** adalah platform manajemen janji temu empat-ujung untuk industri layanan gaya hidup: sisi pengguna mencakup **Mini Program WeChat, Aplikasi Flutter, Aplikasi HarmonyOS** — tiga ujung, dengan akun yang sama bebas berpindah antar platform, dipadukan dengan **Panel Admin PC**, mewujudkan penutupan digital menyeluruh dari "janji temu pengguna → teknisi menerima pesanan → operasional backend". Baik itu janji temu di toko, layanan teknisi, pemasaran member, maupun penyelesaian keuangan, satu sistem menyelesaikan semuanya.

**Pengalaman Janji Temu Satu Atap**

Pengalaman tiga ujung konsisten: pilih waktu janji temu secara visual lewat kalender, potongan kupon/kartu kunjungan/poin, diskon flash sale dan belanja bersama, pembayaran WeChat/saldo, status pesanan dapat dilacak penuh — ubah jadwal, batalkan, refund, purna jual, dan faktur elektronik semuanya selesai daring; sisi teknisi menyediakan dasbor kerja, absen masuk/pulang, penjadwalan massal, verifikasi layanan, dan persetujuan penarikan dana, efisiensi operasional terlihat jelas.

**Pertumbuhan Pemasaran Rantai Penuh**

Dilengkapi lebih dari sepuluh alat pemasaran: aktivitas potongan (spend X get Y off), flash sale, belanja bersama, transfer hadiah kupon, toko pertukaran poin dan roda keberuntungan, hak kartu member/level pertumbuhan, komisi referral dua tingkat, hadiah pelanggan setia, dan lainnya — dipadukan dengan push pesan langganan dan push aplikasi, membantu merchant terus menambah, mempertahankan, dan mengulang pembelian.

**Keamanan & Kepatuhan Kelas Enterprise**

Menggunakan komponen keamanan buatan sendiri: autentikasi JWT, pengacakan ID, 31 jenis deteksi serangan, enkripsi dua lapis data sensitif, validasi harga sisi server, perbandingan ketat callback pembayaran dan pencegahan duplikasi idempoten, sekaligus mendukung pembagian keuntungan resmi WeChat, ekspor data pribadi, dan penghapusan akun, memenuhi persyaratan kepatuhan.

**Landasan Teknologi yang Matang**

Berbasis PHP 8.3 + webman framework resident berkinerja tinggi, didukung MySQL 8.0 + Redis + Elasticsearch; 95 tabel data, 388 antarmuka, 285 titik izin berbutir halus, 722 tes otomasi semuanya lulus, serta dilengkapi dokumentasi arsitektur lengkap dua bahasa dan skrip instalasi satu klik, siap pakai dan mudah dikembangkan.

Baik itu janji temu toko tunggal maupun rantai multi-toko, Sistem Layanan Janji Temu menyediakan solusi terpadu yang stabil, aman, dan dapat diskalakan.

## Struktur Proyek

```
appointment-php/
├── admin/                     # Panel admin (webman v2 + Flutter Web, deploy terpisah :8787)
│   ├── app/                   #   admin(kontroler backend)/api/model/middleware/process/view
│   ├── apps/                  #   Flutter Web backend / HarmonyOS / Manajemen WeChat
│   ├── config/                #   Konfigurasi rute/database/proses/plugin
│   ├── database/              #   Skrip cadangan (struktur tabel & data seed lihat docs/install.sql)
│   ├── tests/                 #   PHPUnit (gaya atribut #[\Test])
│   └── start.php
├── service/                   # Layanan API bisnis (webman v2, deploy terpisah :8787)
│   ├── app/                   #   Modul api/user/technician/order/wallet/marketing/notification dll.
│   ├── config/                #   Konfigurasi rute/database/proses/pembayaran dll.
│   ├── support/               #   Kelas dasar Model (generateId)/Request/Response
│   ├── tests/                 #   PHPUnit
│   └── start.php
├── apps/                      # Aplikasi frontend sisi pengguna
│   ├── wechat/                #   Mini Program WeChat (native)
│   ├── flutter/               #   Aplikasi Flutter (iOS + Android)
│   └── harmonyos/             #   Aplikasi HarmonyOS (native HarmonyOS)
└── docs/                      # Dokumentasi proyek
    ├── API.md / FEATURES.md / STRUCTURE.md / install.sql / README.md ...
    └── diagrams/              #   Diagram arsitektur/alur (SVG + mermaid)
```

## Memulai Cepat

### Persyaratan Lingkungan

- PHP 8.3+
- MySQL 8.0+
- Redis
- Composer

### Wizard Instalasi Web (Direkomendasikan)

```bash
cd admin/
cp .env.example .env
composer install
php start.php start -d
```

Buka `http://localhost:8787/install` di browser, isi database dan akun admin sesuai petunjuk untuk menyelesaikan instalasi.

### Instalasi Manual

```bash
# 1. Instal dependensi
cd service/ && cp .env.example .env && composer install
cd ../admin/ && cp .env.example .env && composer install

# 2. Impor database satu klik (berisi seluruh 95 tabel + seed izin/konfigurasi)
mysql -u root -p < ../install.sql

# 3. Mulai layanan
cd service/ && php start.php start -d   # API bisnis → :8787
cd ../admin/ && php start.php start -d  # Panel admin → :8787
```

### Deployment Docker

```bash
cd admin/ && cp .env.docker .env && docker-compose up -d
cd ../service/ && cp .env.docker .env && docker-compose up -d
```

## Tumpukan Teknologi

| Lapisan | Teknologi | Keterangan |
|------|------|------|
| Framework backend | webman v2 (PHP 8.3+) | Layanan HTTP resident berkinerja tinggi |
| Database | MySQL 8.0 | Prefiks tabel `appointment_` |
| Cache | Redis | Cache/Batas kecepatan/Session/Antrean |
| Pencarian | Elasticsearch | Full-text search (via webman-scout) |
| Frontend panel admin | Flutter Web | Gaya panel admin PC |
| APP pengguna | Flutter | iOS + Android |
| Mini program pengguna | Mini Program WeChat native | WXML/WXSS/JS |
| APP HarmonyOS pengguna | HarmonyOS ArkTS | Native @ohos.net.http |
| Pembuatan ID | erikwang2013/snowflake-php | Kunci primer BIGINT non-auto-increment |
| Enkripsi ID API | erikwang2013/hashids | Menyembunyikan ID asli dari publik |
| Autentikasi JWT | erikwang2013/jwt-webman | Bearer Token |
| Enkripsi data sensitif | erikwang2013/encryption + encryptable | Enkripsi dua lapis API + DB |
| Proteksi keamanan | erikwang2013/security-php | 31 jenis deteksi serangan |
| Verifikasi operasi | erikwang2013/poster-php | Verifikasi acak operasi sensitif |
| Bendera negara | erikwang2013/season | Ikon bendera |
| Sinkronisasi ES | erikwang2013/webman-scout | Sinkronisasi model otomatis |

## Arsitektur Sistem

<img src="id-architecture.svg" alt="id-architecture.svg" width="100%">

## Alur Inti

### Alur Janji Temu Layanan

<img src="id-appointment-flow.svg" alt="id-appointment-flow.svg" width="100%">

### Alur Pembayaran & Refund

<img src="id-payment-refund.svg" alt="id-payment-refund.svg" width="100%">

## Siklus Hidup Pesanan

<img src="id-order-lifecycle.svg" alt="id-order-lifecycle.svg" width="100%">

## Arsitektur Keamanan

### Sistem Tujuh Lapisan Pertahanan Berlapis

<img src="id-security-defense.svg" alt="id-security-defense.svg" width="100%">

> Diagram selengkapnya: [Diagram Alur](diagrams/FLOWCHART.md) (termasuk penarikan dana teknisi/perpindahan identitas) | [Peta Pikiran Fungsi](diagrams/FUNCTION-DIAGRAM.md) | [Semua Siklus Hidup](diagrams/LIFECYCLE-DIAGRAM.md) | [Arsitektur Keamanan Lengkap](diagrams/SECURITY-ARCHITECTURE.md)

## Sorotan Fungsi Inti (Ronde 6-24)

| Fitur | Keterangan |
|------|------|
| Dompet saldo | Tabel user_wallet / wallet_recharge / wallet_txn; saldo+transaksi, isi ulang pembayaran WeChat (callback nomor pesanan prefiks R), pembayaran saldo pesanan (pay_channel=balance), refund WeChat/saldo otomatis kembali ke saldo |
| UI panel admin lengkap | Flutter Web 21 halaman: dashboard/pengguna/peran/konfigurasi/log/verifikasi/jadwal/layanan/teknisi/pesanan/kupon/member/kartu kunjungan/pengumuman/FAQ/penarikan/ulasan/laporan/after-sales/manager toko/profil |
| Statistik real-time dasbor | Beranda admin merender dinamis 7 kartu statistik (total pengguna/baru hari ini/aktif/log operasi/booking hari ini/penarikan tertunda/teknisi tertunda) + grafik tren 30 hari (volume pesanan/jumlah/pengguna baru/aktivitas) + diagram lingkaran status pengguna + log operasi terbaru, cache Redis svc:dashboard 300s |
| Laporan data | 3 endpoint ReportController: statistik pesanan / TOP10 teknisi / distribusi kanal (GET /admin/reports/orders\|technicians\|distribution, rentang 7/30 hari, cache Redis 300s) + statistik penjualan (svc:sales_stats) + statistik keuangan (svc:finance_stats pendapatan/pengembalian/penarikan/komisi) |
| Pesan langganan mini program | Push langganan 3 skenario pesanan (bayar sukses/refund masuk/verifikasi sukses); push_sent_at idempoten; template tak terkonfigurasi otomatis turun ke notifikasi internal |
| Penarikan dana teknisi | Persetujuan di sisi admin; jumlah ≥500 persetujuan dua tingkat (pemilik toko→keuangan); state machine pending→approved→completed (rejected/failed) |
| Penutupan verifikasi kartu kunjungan | Kartu kunjungan saya hitung real-time used_up/expired; verifikasi Redis NX idempoten + row lock kurangi kuota, langsung buat pesanan completed + OrderItem + OrderPayment(pay_type='card') |
| Dasbor kerja teknisi | Tugas hari ini/catatan selesai/mulai·selesai (row lock + guard state machine + idempoten, setelah selesai tulis notifikasi internal); mini program tech-work tiga Tab |
| Potongan kupon | PriceCalculator: applyCoupon baca-saja hitung jumlah / consume set used saat bayar / restoreCouponAndCard refund idempoten kembalikan; fixed/percent + ambang min_amount |
| Kartu hadiah | Saat redeem tipe cash isi saldo ke dompet (row lock cegah pemasukan ganda, WalletTxn type='gift_card'), tipe gift hanya ditandai |
| Sistem poin | Absen harian dapat poin; konsumsi verifikasi dapat poin floor(paid×1) (idempoten per order_id, snapshot balance); refund dipotong proporsional; detail halaman + filter type/source |
| Manajemen member | Kolom appointment_user.member_level (migrasi 000008); CRUD lengkap kartu member sisi admin (izin 365-369) |
| Rantai pemesanan mini program | Detail layanan → konfirmasi pesanan (pilih kupon/ambang nonaktif/estimasi harga klien) → POST /order → bayar WeChat/saldo; mini program total 20 halaman |
| Penutupan belanja bersama | join berulang 422 + kunci saat penuh + tutup malas saat kedaluwarsa; pemesanan setelah kelompok terbentuk kirim promotion_id untuk harga grup (discount_percent), larang tumpuk kupon/kartu kunjungan/poin, tidak terbentuk otomatis batalkan pesanan dan lepas kunci teknisi (saluran promo FLASH_SALE lama sudah dinonaktifkan, flash sale lewat saluran terpisah) |
| Dasbor kerja pemilik toko | service /api/store-manager 4 antarmuka (overview/orders/technicians/revenue) isolasi paksa store_id (tanpa toko 403); admin ikhtisar dasbor toko + filter pesanan store_id + halaman Flutter + izin 372 |
| Komisi referral | Setelah pesanan pertama yang direferensikan completed, sesuai paid_amount × reward_rate (konfigurasi sistem, default 0.05) beri komisi ke referrer masuk dompet (WalletTxn referral_reward); row lock+cek kosong+verifikasi ulang pesanan pertama tiga lapis idempoten; detail earnings + tampilan record admin (izin 379) |
| Toko pertukaran poin | Dua tabel barang tukar/catatan tukar; antarmuka tukar Redis NX + row lock cegah pertukaran berlebih + uk_user_goods batasi sekali per pengguna; tiga hasil: kupon / masuk saldo wallet / kartu kunci gift_card; admin CRUD + naik/turun + record (izin 373-378) |
| Ubah jadwal janji temu | POST /api/order/reschedule/{id} ganti waktu teknisi yang sama; hanya pending/paid/confirmed dan jarak dari mulai ≥6h bisa ubah; order_lock + kunci teknisi slot baru SETNX(180s) cegah oversell + validasi konflik jadwal B2; catat appointment_order_reschedule + pesan langganan SCENE_RESCHEDULE |
| Transfer hadiah kupon | Kode transfer unik 8 karakter (cadangan uk_code, berlaku 7 hari); claim cegah penyalahgunaan: Redis NX lock + row lock verifikasi ulang cegah double-spend, uk_user_coupon batasi sekali transfer, kupon yang ditransfer tak bisa ditransfer lagi, tak bisa self-claim; kedaluwarsa malas pulihkan kupon asli |
| Kedaluwarsa poin | expires_at (default 365 hari, konfigurasi points.expiry_days); PointsExpiryTimer 60s scan kursor tulis type=expire nilai negatif (tiga lapis idempoten) + notifikasi internal agregat; poin kedaluwarsa tak bisa dipakai tukar saldo/barang |
| Penilaian otomatis level teknisi | TierRatingService statistik real-time jumlah pesanan+rata-rata nilai tulis balik profile, cocokkan sesuai tier_config dari tinggi ke rendah; hanya naik tak turun (allowDowngrade untuk penilaian ulang manual); perubahan catat appointment_technician_tier_log + notifikasi internal; lihat log admin (izin 380) |
| Penutupan pemesanan flash sale | /api/seckill aktivitas + buy idempoten/anti konkurensi, pemesanan suntik seckill_id reuse store(), stok seragam dikurangi row lock dalam transaksi (harga flash sale = seckill_price bersumber DB), habis 422 "Sudah ludes", batal tak isi ulang stok; saluran promo flash_sale lama sudah dinonaktifkan |
| Pengingat sebelum layanan mulai | ServiceReminderTimer 60s scan pesanan confirmed/serving mulai dalam 1 jam → pesan langganan SCENE_REMINDER + notifikasi internal (order_id+type cegah duplikat, tiga lapis idempoten); template tak terkonfigurasi otomatis turun ke notifikasi internal |
| Pengingat kedaluwarsa | ExpiryReminderTimer 6h scan kartu member/kupon kedaluwarsa dalam 3 hari → type=card_expiry/coupon_expiry + pesan langganan SCENE_EXPIRY (order_id catat sumber cegah duplikat) |
| Balasan ulasan teknisi | POST /api/technician/review/reply/{order_id}: bukan milik 404, balasan ganda 422, balasan sukses notifikasi internal ke pengguna; appointment_order_review tambah replied_at; detail balasan admin (izin 381) |
| Notifikasi saldo masuk | Callback isi ulang WeChat dalam transaksi tulis notifikasi internal type='wallet_recharge' (reuse idempoten callback, komit atomik transaksi sama, kegagalan tak blokir alur utama) |
| Transfer saldo | POST /api/wallet/transfer transfer antar pengguna: jumlah 0.01-1000/transaksi + limit harian 5000; Redis NX lock + row lock kedua dompet (user_id urut naik cegah deadlock) + client_token 24h idempoten; WalletTxn transfer_out/transfer_in dua transaksi berisi snapshot balance_after; notifikasi internal penerima type='balance_received' |
| Transfer hadiah poin | POST /api/user/points/transfer antar pengguna: 1-10000 poin + limit harian kumulatif 10000; Redis NX lock + lockForUpdate transaksi terakhir kedua pihak (urut naik cegah deadlock) + verifikasi ulang dalam lock; pengirim consume/penerima earn dua transaksi (penerima berisi expires_at bisa kedaluwarsa normal); notifikasi internal penerima type='points_received' |
| Ulasan tambahan | POST /api/order/review/{order_id}/append: bukan milik 404/duplikat 422/konten kosong 422/bukan completed 422, sukses tulis notifikasi internal teknisi type='review_append'; appointment_order_review tambah append_content/append_images(JSON)/append_at; sekalian daftarkan rute submit ulasan pengguna (store asli tak punya rute tak terjangkau) dan perbaiki TypeError latennya |
| Pelacakan logistik sisi pengguna | GET /api/order/logistics/{id}: hanya pesanan product milik sendiri (404 bukan milik/bukan produk/belum kirim); baca order.remark JSON (shipping_company/tracking_no/shipped_at, admin ship tulis); nomor HP penerima samarkan 138****5678 |
| Preferensi notifikasi | Tabel appointment_user_notify_setting (kunci unik uk_user_type, baris default=nyala); GET/PUT /api/user/notify-settings; 5 saklar service_reminder/card_expiry/points_expiry/marketing/system (system selalu nyala tak bisa mati); notifySettingEnabled gerbang 3 timer + event langganan, mati maka notifikasi internal dan pesan langganan dilewati |
| Kalender janji temu | GET /api/calendar/technician/{id} (tampilan bulan) + /day (tampilan hari): perluas time_slots JSON ke slot jam, kecualikan slot terpesan appointment_order; visualisasi jadwal toko pilih waktu |
| Level pertumbuhan pengguna | appointment_user_growth + appointment_growth_level (Perunggu0/Perak100/Emas500/Platinum2000/Berlian5000); absen +10, ulasan +20, konsumsi 1 yuan 1 poin (reuse verifikasi ulang status yang ada, idempoten alami); GET /api/growth (ikhtisar/records/levels daftar publik) |
| Faktur elektronik | POST/GET /api/invoices (ajukan/daftar/detail): uk_order_type(order_id,order_type) cegah pengajuan ganda, jumlah diambil server; admin terbit/tolak (izin 382-384) |
| Tiket layanan pelanggan | POST/GET /api/tickets + /{id}/close: pengguna ajukan/daftar/detail/tutup; admin balas (izin 385/387) |
| Referral multi-level — komisi tingkat dua | Setelah pesanan dibayar beri referrer dari referrer tingkat satu paid×level2_rate (konfigurasi 0.02): row lock transaksi + uk_order_referred idempoten cegah pemberian ganda; WalletTxn TYPE_REFERRAL_LEVEL2; lihat record admin (izin 386) |
| Hak level pertumbuhan | Benefits GrowthLevel.beringkas: pemesanan diskon sesuai discount_rate level (hanya pesanan standar, kupon/kartu kunjungan→tumpuk diskon level, jumlah diskon masuk discount_amount + catatan bisa ditelusuri, proteksi batas bawah dipotong jadi 0); nilai pertumbuhan callback bayar floor(paid×points_multiplier) kali lipat (ambil level pada saat bayar, tak naikkan level) |
| Manajemen data faktur | Pustaka appointment_invoice_title: simpan/edit/hapus/default (pertama otomatis default, hapus default otomatis pindah, set default transaksi nolkan); ajukan faktur opsional title_id, jalur isi manual dipertahankan |
| Kepuasan tiket | Skor tutup tiket 1-5 (di luar batas 422, tak berikan kompatibel NULL); ringkasan kepuasan admin: rata-rata/distribusi 1-5 bintang/hitung sudah-belum nilai (izin 388) |
| Audit gambar ulasan | admin ReviewAuditController: daftar ulasan bergambar (filter JSON_LENGTH + join nama pengguna/teknisi), sembunyikan/pulihkan (hide hanya visible, restore hanya hidden, validasi dua arah 422); setelah disembunyikan daftar ulasan teknisi otomatis tak terlihat (izin 389-391) |
| Riwayat penelusuran | appointment_browse_history (uk_user_item penelusuran berulang hanya perbarui viewed_at): detail layanan catat (try/catch tak blokir alur utama, tak login dilewati); daftar join info layanan + hashid; hapus satu/bersihkan hanya milik sendiri |

> Ronde 8 perbaikan operasional: hapus 12 Poster::verify fatal laten; statistik DashboardController ganti kueri Capsule Manager.
>
> Tambahan Round-15: pengembalian poin (batal/refund kembalikan poin points_offset, refundOffsetPoints 5 titik sambung idempoten); status PromotionParticipant ubah konstanta integer (perbaiki kerusakan join 1366 mode ketat).
>
> Tambahan Round-16: tukar poin (PointsExchangeController, tipe consume/source=exchange); pemesanan belanja bersama (appointment_order tambah kolom promotion_id/participant_id); komisi referral (ReferralRewardService sambung WorkController::complete).
>
> Tambahan Round-17: ubah jadwal janji temu (appointment_order_reschedule + antarmuka reschedule); transfer hadiah kupon (appointment_user_coupon_transfer + transfer/claim/transfers); kedaluwarsa poin (expires_at + proses PointsExpiryTimer); penilaian otomatis level teknisi (TierRatingService + appointment_technician_tier_log, izin 380).
>
> Perbaikan Round-17: penyisipan notifikasi AutoCancelTimer ganti pakai \support\Model::generateId() (aslinya memanggil Snowflake::generate() yang tak ada, notifikasi auto-cancel gagal senyap).
>
> Tambahan Round-18: pemesanan flash sale (store() dukung harga flash sale); pengingat sebelum layanan mulai (ServiceReminderTimer + SCENE_REMINDER); pengingat kedaluwarsa kartu member/kupon (ExpiryReminderTimer + SCENE_EXPIRY); balasan ulasan teknisi (antarmuka reply ulasan + kolom replied_at + izin 381); notifikasi saldo masuk (dalam transaksi callback type='wallet_recharge').
>
> Tambahan Round-19: transfer saldo (appointment_wallet_transfer + WalletTransferController, dua row lock dalam izin + idempoten client_token); transfer hadiah poin (appointment_user_points_transfer + PointsTransferController, limit harian + dua transaksi); ulasan tambahan (appointment_order_review tambah tiga kolom + antarmuka append + daftarkan rute store); pelacakan logistik sisi pengguna (antarmuka logistics + parse JSON remark + samarkan nomor HP); preferensi notifikasi (appointment_user_notify_setting + NotifySettingController + gerbang 3 timer).
>
> Tambahan Round-20: kalender janji temu (CalendarController tampilan bulan/hari + kecualikan terpesan); level pertumbuhan pengguna (appointment_user_growth + appointment_growth_level 5 tingkat + sambung absen/ulasan/konsumsi); faktur elektronik (appointment_invoice + uk_order_type cegah ganda + terbit/tolak backend, izin 382-384); tiket layanan pelanggan (appointment_ticket ajukan/daftar/detail/tutup + balas backend, izin 385/387); komisi referral tingkat dua (payLevel2Reward row lock transaksi + uk_order_referred idempoten, izin 386).
>
> Tambahan Round-21: hak level pertumbuhan (diskon discount_rate pemesanan + pengali poin points_multiplier bayar, migrasi seed 5 tingkat benefits); manajemen data faktur (pustaka appointment_invoice_title + tautan title_id pengajuan); kepuasan tiket (rating/rated_at skor tutup + statistik ringkasan admin, izin 388); audit gambar ulasan (ReviewAuditController sembunyikan/pulihkan, izin 389-391); riwayat penelusuran pengguna (appointment_browse_history + sambung detail + daftar/hapus/bersihkan).
>
> Tambahan Round-22: aktivitas potongan (appointment_full_reduction potongan otomatis + validasi ambang, izin 396-400); ekspor kalender ICS (RFC5545 janji temu saya); absen teknisi (appointment_technician_attendance masuk/pulang + tanda terlambat + statistik admin, izin 392-393); layanan push APP (abstraksi digerakkan konfigurasi + 5 sambungan event, appointment_push_log); pembagian keuntungan resmi WeChat (appointment_profit_sharing digerakkan konfigurasi + degradasi, izin 394); kepatuhan privasi (ekspor data + penghapusan akun state machine 72h close_status).
>
> Tambahan Round-23: profil kesehatan pengguna (appointment_user_health_profile); kata sandi pembayaran dompet (appointment_user_wallet pay_password set/verifikasi); penjadwalan massal teknisi (impor batch + deteksi konflik tumpang tindih); linimasa status pesanan (appointment_order_status_log 8 titik embed status + tampilan pengguna/backend); roda keberuntungan poin (appointment_lucky_wheel + appointment_wheel_record undian berbobot, izin 401-406); masa berlaku poin (konfigurasi points.expiry_days + transaksi earn baru berisi expires_at).
>
> Tambahan Round-24: mode tamu (/api/guest/* penelusuran baca-saja tanpa login + cache Redis); flash sale (appointment_seckill_activity + Redis NX row lock pembelian + suntik appointment_order.seckill_id pemesanan, izin 407-411/420); manajemen versi APP & deteksi pembaruan (appointment_app_version + /api/app/version, izin 416-419); hadiah pelanggan setia (bonus pembelian kedua 30 hari type=return_customer, izin 412-414); ekspor CSV jadwal (UTF-8 BOM + detail slot waktu, izin 415).
>
> Penguatan keamanan 2026-08-26: harga item pesanan antarmuka pemesanan selalu bersumber catatan database (harga klien tak dipercaya, target_type tak dikenal 422, target_id wajib hashid), harga belanja bersama/flash sale sama bersumber DB; stok flash sale seragam dikurangi row lock dalam transaksi /api/order store() (SeckillController::buy tak lagi pre-deduct, pertahankan lock aktivitas Redis + idempoten client_token); pengajuan penarikan teknisi reservasi in-transit, verifikasi ulang sebelum transfer persetujuan, persetujuan konkurensi cegah pembayaran ganda; callback WeChat total_fee bandingkan ketat dengan jumlah yang harus dibayar pesanan, log callback Alipay samarkan; /install sukses tulis .install.lock validasi ganda cegah re-instal; konvergensi versi dependensi (webman-scout 2.0.5 / opensearch-php ^2.6 / dompdf, security-php, webman-database dikunci presisi); phpstan.neon dua aplikasi diperbaiki dapat berjalan (php -d memory_limit=2G).

## Navigasi Dokumentasi

| Dokumen | Keterangan |
|------|------|
| [Penjelasan Arsitektur](ARCHITECTURE.md) | Arsitektur sistem, hubungan tiga ujung, komponen teknologi, alur data |
| [Penjelasan Fitur](FEATURES.md) | Daftar fitur lengkap sisi pengguna/teknisi/panel admin |
| [Desain Arsitektur](ARCHITECTURE-DESIGN.md) | Desain berlapis, rantai middleware, desain database, desain keamanan |
| [Desain Fitur](FEATURE-DESIGN.md) | Alur bisnis inti, aturan bisnis, state machine, aturan refund |
| [Dokumentasi API](API.md) | API bisnis + API panel admin, contoh permintaan/respons + titik OpenAPI |
| [Petunjuk Instalasi](INSTALL.md) | Persyaratan lingkungan, deployment Docker, variabel lingkungan, konfigurasi pihak ketiga, FAQ |
| [Petunjuk Penggunaan](USAGE.md) | Konfigurasi panel admin, operasi sisi pengguna/teknisi, aturan refund (antarmuka API lihat API.md) |
| [Struktur Proyek](STRUCTURE.md) | Tata letak direktori lengkap, rantai eksekusi middleware, daftar tabel database |
| [Laporan Pengujian](TEST-REPORT.md) | Audit cakupan tes penuh (558 kasus / 2508 asersi) |
| [Spesifikasi Desain](specs/2026-05-26-appointment-system-design.md) | Spesifikasi desain sistem |
| [Rencana Implementasi](plans/2026-05-26-appointment-system-plan.md) | Rencana implementasi bertahap |

## Dukung Proyek / Support

Jika proyek ini bermanfaat bagi Anda, kami sambut dukungan Anda! Terima kasih atas dorongan Anda :heart:

If this project helps you, your support is welcome and appreciated!

<table>
  <tr>
    <td align="center" width="50%">
      <img src="../../docs/weixinpay.png" alt="微信支付 / WeChat Pay" width="130" height="130"><br>
      <b>微信支付</b><br>WeChat Pay
    </td>
    <td align="center" width="50%">
      <img src="../../docs/alipay.png" alt="支付宝 / Alipay" width="130" height="130"><br>
      <b>支付宝</b><br>Alipay
    </td>
  </tr>
</table>

### Transfer Bank Global / Global Bank Transfer

Transfer bank global untuk donasi diterima (HKD / CNY / USD / mata uang lainnya). Terima kasih atas kemurahan hati Anda :heart:

Global bank transfer donations are welcome (HKD / CNY / USD / other currencies). Thank you for your generosity!

| Proyek Item | Detail Informasi |
|-----------|-------------|
| Nama Penerima Beneficiary Name | WANG KEXUN |
| Nomor Akun Account Number | 881015918251 |
| Bank Penerima | ZA Bank Limited（SWIFT Code：AABLHKHHXXX，Bank Code：387） |
| Alamat Bank | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **Bank Perantara Transfer Lintas Batas (jika diperlukan) / Intermediary Bank (if required)**
> Ini adalah informasi bank perantara (bank perantara transfer), bukan bank penerima. Silakan tanyakan kepada bank pengirim apakah informasi ini diperlukan.
> Note: this is intermediary bank information, not the receiving bank. Please check with your remitting bank whether it is required.
>
> - Untuk HKD / CNY / USD：**Citibank N.A. Hong Kong** — SWIFT Code：CITIHKHXXXX，Bank Code：006，Cabang：Hong Kong Branch，Branch Code：391，Alamat：Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - Untuk mata uang lainnya（For other currencies）：**The Bank of New York Mellon** — SWIFT Code：IRVTUS3NXXX，Alamat：240 Greenwich Street, New York, United States

## Hak Cipta

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

### Donasi Kripto (Crypto Donation)

Jika proyek ini membantu Anda, silakan pindai kode QR untuk berdonasi, terima kasih!

| Jaringan (Network) | Kode QR (QR Code) | Alamat dompet (Wallet Address) |
|---|---|---|
| BNB Smart Chain (BEP20) | [<img src="../coin/1.jpg" width="150" alt="BNB Smart Chain (BEP20)">](../coin/1.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Tron (TRC20) | [<img src="../coin/2.jpg" width="150" alt="Tron (TRC20)">](../coin/2.jpg) | `TEdDHWLajt1XvqtPDWmQctdrJaC3pzZZzz` |
| Ethereum (ERC20) | [<img src="../coin/3.jpg" width="150" alt="Ethereum (ERC20)">](../coin/3.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Aptos | [<img src="../coin/4.jpg" width="150" alt="Aptos">](../coin/4.jpg) | `0x836e3780edfc3f7b2372b39e2a1a3a5d7adfaccd96c726f21cfde1b50dd68030` |
| Plasma | [<img src="../coin/5.jpg" width="150" alt="Plasma">](../coin/5.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Polygon POS | [<img src="../coin/6.jpg" width="150" alt="Polygon POS">](../coin/6.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Solana | [<img src="../coin/7.jpg" width="150" alt="Solana">](../coin/7.jpg) | `2hfhboHdmdrYsY25XfQSsEWxq5ip4EQsR7f4AzSRMUyr` |
| The Open Network (TON) | [<img src="../coin/8.jpg" width="150" alt="The Open Network (TON)">](../coin/8.jpg) | `UQB9kFQohzmXUir9QSSZq01iwl9aQZIDdBpNmDklljRtCoGK` |
| Arbitrum One | [<img src="../coin/9.jpg" width="150" alt="Arbitrum One">](../coin/9.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| AVAX C-Chain | [<img src="../coin/10.jpg" width="150" alt="AVAX C-Chain">](../coin/10.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |

