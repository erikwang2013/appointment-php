# Dokumentasi API

> Terjemahan bahasa Indonesia · Asli: [中文](../../docs/API.md)

## Ringkasan

- **API bisnis** (service/): `http://localhost:8787` — menyediakan antarmuka bisnis untuk Mini Program/APP
- **API panel admin** (admin/): `http://localhost:8787` — menyediakan antarmuka untuk Flutter Web panel admin
- **Cara otentikasi**: Bearer Token (JWT), header permintaan `Authorization: Bearer <token>`
- **Kontrol versi**: melalui header permintaan `API-Version: v1` mengontrol versi API, tidak tercermin di URL. Default v1
- **Encoding ID**: semua kolom ID dalam permintaan/respons menggunakan encoding hashids, menyembunyikan ID database asli ke luar
- **Dokumen OpenAPI**: dihasilkan dengan `hg/apidoc`, terpisah untuk sisi admin dan klien

| Sisi | Alamat dokumen OpenAPI | Keterangan |
|------|------|------|
| Admin | `GET http://localhost:8787/api/docs` | spesifikasi lengkap API panel admin (OpenAPI 3.0 JSON) |
| Klien | `GET http://localhost:8787/api/docs` | spesifikasi lengkap API bisnis (OpenAPI 3.0 JSON) |

Dapat mengimpor alamat di atas melalui alat seperti Swagger UI untuk melihat dokumen interaktif.

- **Format respons umum**:

```json
{
  "code": 0,
  "message": "操作成功",
  "data": {}
}
```

Respons paginasi:
```json
{
  "code": 0,
  "message": "success",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

---

## I. API Bisnis (service/ :8787)

### 1. Antarmuka Publik (tanpa otentikasi)

#### 1.1 Kode Verifikasi

**`POST /api/captcha/send`** — kirim kode verifikasi SMS

Permintaan:
```json
{
  "phone": "13800138000"
}
```
Respons: `{"code":0,"message":"验证码已发送","data":null}`

Batas: hanya dapat mengirim 1 kali setiap 60 detik, kode verifikasi berlaku 5 menit.

---

#### 1.2 Otentikasi

**`POST /api/auth/register`** — registrasi nomor ponsel

Permintaan:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "abc123",
  "confirm_password": "abc123",
  "referral_code": "A1B2C3D4"
}
```
Respons:
```json
{
  "code": 0,
  "message": "注册成功",
  "data": {
    "token": "eyJhbGciOi...",
    "user": {
      "id": "aB3xK9mQ",
      "phone": "138****8000",
      "nickname": "用户138****8000",
      "user_type": "customer",
      "active_role": "customer",
      "referral_code": "E5F6G7H8"
    }
  }
}
```

---

**`POST /api/auth/login`** — login kata sandi

Permintaan:
```json
{
  "phone": "13800138000",
  "password": "abc123"
}
```
Respons: sama dengan respons registrasi, berisi token dan info user.

---

**`POST /api/auth/login-by-code`** — login kode verifikasi

Permintaan:
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```
Respons: sama dengan login. Pengguna belum terdaftar otomatis dibuatkan akun.

---

**`POST /api/auth/forget-password`** — lupa kata sandi

Permintaan:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "newpass123",
  "confirm_password": "newpass123"
}
```

---

**`POST /api/auth/refresh`** — refresh Token

Header permintaan: `Authorization: Bearer <旧token>`
Respons: `{"code":0,"data":{"token":"eyJhbGciOi..."}}`

---

#### 1.3 WeChat

**`POST /api/wechat/mini-login`** — login Mini Program

Permintaan: `{"code":"微信登录code"}`
Keterangan: login pertama perlu memanggil `/api/wechat/phone` untuk mengikat nomor ponsel.

---

**`POST /api/wechat/phone`** — ikat nomor ponsel

Permintaan: `{"code":"微信手机号组件code"}`

---

**`POST /api/wechat/oa-login`** — login Official Account

Permintaan: `{"code":"公众号授权code"}`

---

#### 1.4 Layanan Publik

**`GET /api/common/config`** — konfigurasi publik

Respons: berisi teks perjanjian (perjanjian pengguna/perjanjian privasi/perjanjian layanan), info tentang kami, nomor versi.

---

**`GET /api/common/area`** — daftar area kota

---

#### 1.5 Kueri Layanan

**`GET /api/service/categories`** — daftar kategori

Parameter: `?parent_id=0`

---

**`GET /api/service/items`** — daftar item layanan

Parameter: `?category_id=&page=1&per_page=10&sort=sales`

---

**`GET /api/service/detail/{id}`** — detail layanan

Respons berisi: gambar/nama/harga/spesifikasi/durasi/volume penjualan/daftar ulasan.

---

**`GET /api/service/products`** — daftar produk

**`GET /api/service/stores`** — daftar toko

Parameter: `?lat=&lng=&city=`

---

#### 1.6 Kueri Teknisi

**`GET /api/technician/list`** — daftar teknisi

Parameter: `?lat=&lng=&service_id=&page=1`
Urutkan berdasarkan jarak dekat ke jauh, mengembalikan: avatar/nama/rating/jumlah pesanan/jumlah favorit/jarak/waktu tercepat bisa dijanjikan/apakah bisa melayani.

---

**`GET /api/technician/detail/{id}`** — detail teknisi

Respons berisi: gambar/nama/pengenalan/rating/jarak/daftar item layanan/ulasan.

---

**`GET /api/technician/schedule/{id}`** — jadwal teknisi

Parameter: `?date=2026-05-26`
Mengembalikan slot waktu yang dapat dijanjikan dan status tersedia pada tanggal tersebut.

---

#### 1.7 Konten

**`GET /api/content/banners`** — banner

Parameter: `?position=home`

**`GET /api/content/articles`** — daftar pengumuman/artikel

Parameter: `?type=announcement&page=1`

**`GET /api/content/article/{id}`** — detail artikel

---

#### 1.8 LBS

**`GET /api/lbs/nearby-stores`** — toko terdekat

Parameter: `?lat=&lng=&radius=5000`

**`GET /api/lbs/geocode`** — reverse geocoding

Parameter: `?lat=&lng=`

---

### 2. Antarmuka Pengguna (perlu otentikasi JWT)

Semua antarmuka membawa header `Authorization: Bearer <token>`

#### 2.1 Profil Pribadi

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/user/profile` | dapatkan info pribadi |
| PUT | `/api/user/profile` | perbarui nama panggilan/avatar/jenis kelamin |
| POST | `/api/user/change-password` | ubah kata sandi (old_password/new_password/confirm_password) |
| POST | `/api/user/change-phone` | ganti ponsel (old_code/new_phone/new_code) |
| POST | `/api/user/cancel-account` | hapus akun (perlu verifikasi kata sandi) |
| POST | `/api/user/logout` | keluar login (token masuk blacklist) |
| POST | `/api/user/switch-role` | alih identitas (role: customer/technician) |

Alih ke technician perlu memiliki arsip teknisi berstatus approved.

#### 2.2 Manajemen Alamat

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/user/addresses` | daftar alamat |
| POST | `/api/user/addresses` | tambah alamat (contact_name/contact_phone/province/city/district/detail/lat/lng/is_default) |
| GET | `/api/user/addresses/{id}` | detail alamat |
| PUT | `/api/user/addresses/{id}` | perbarui alamat |
| DELETE | `/api/user/addresses/{id}` | hapus alamat |

Saat diatur sebagai default otomatis membatalkan alamat default lain.

#### 2.3 Favorit

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/user/favorites` | daftar favorit (?type=service/technician) |
| POST | `/api/user/favorites` | tambah favorit (target_type/target_id) |
| DELETE | `/api/user/favorites/{id}` | batalkan favorit |

#### 2.4 Masukan

`POST /api/user/feedback` — submit masukan (content + array images)

#### 2.5 Promosi Referral

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/user/referral` | info promosi (kode referral/jumlah direkomendasikan/jumlah pesanan pertama/poin didapat) |
| GET | `/api/user/referral/qrcode` | kode QR promosi (kode referral + tautan undangan) |
| GET | `/api/user/referral/referred-users` | daftar pengguna yang direkomendasikan |
| GET | `/api/user/referral/earnings` | detail komisi referral (paginasi: nama panggilan/avatar/nomor pesanan/jumlah/waktu ter-referral) |

**Komisi referral**: setelah pesanan pertama ter-referral completed, jumlah = paid_amount × reward_rate (erik_system_config referral.reward_rate, default 0.05, nilai ilegal jatuh konstanta). Row lock + cek kosong rewarded_at + periksa ulang pesanan pertama tiga lapis idempoten; pencatatan WalletTxn type=referral_reward.

#### 2.6 Transfer Poin (ronde ke-19)

| Metode | Jalur | Keterangan |
|------|------|------|
| POST | `/api/user/points/transfer` | transfer poin (to_user_id hashid/points) |
| GET | `/api/user/points/transfers` | catatan transfer (?direction=sent/received&page=1) |

**Transfer poin**: decode hashid penerima + keberadaan 404, ke diri sendiri 422, jumlah poin 1-10000 422, saldo SUM agregat kurang 422, batas akumulasi harian 10000 422. Proteksi bersamaan: kunci Redis NX points_transfer:{user} 30s → dalam transaksi lockForUpdate transaksi terakhir kedua pihak (ascending user_id cegah deadlock transfer timbal balik) → verifikasi ulang dalam kunci saldo/batas/penerima. Standar transaksi: pengirim type=consume/source=points_transfer negatif (balance=snapshot sebelumnya-berkurang), penerima type=earn/source=points_transfer positif termasuk expires_at (PointsExpiryTimer bisa kedaluwarsa normal); setelah commit notifikasi situs penerima type='points_received' (gagal hanya warn).

#### 2.7 Pengaturan Preferensi Pesan (ronde ke-19)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/user/notify-settings` | kueri saklar notifikasi (5 jenis lengkap) |
| PUT | `/api/user/notify-settings` | perbarui saklar massal (types: {service_reminder: 0/1, ...}) |

**Saklar notifikasi**: tabel erik_user_notify_setting (kunci unik gabungan user_id+type, baris default kosong=default nyala). 5 jenis: service_reminder pengingat layanan / card_expiry pengingat kedaluwarsa (kartu+kupon payung seragam) / points_expiry kedaluwarsa poin / marketing pemasaran (cadangan) / system sistem (tidak bisa dimatikan, PUT paksa 1). Gerbang: notifySettingEnabled pasang 3 proses timer ServiceReminderTimer/ExpiryReminderTimer/PointsExpiryTimer + pemetaan skenario event subscription (PAY/REFUND/VERIFIED/RESCHEDULE→system selalu kirim, REMINDER→service_reminder, EXPIRY→card_expiry); jenis dimatikan notifikasi situs dan subscription message sama-sama dilewati.

---

### 3. Antarmuka Teknisi (perlu JWT + identitas teknisi)

#### 3.1 Arsip Teknisi

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/technician/profile` | dapatkan arsip teknisi |
| PUT | `/api/technician/profile` | perbarui arsip (avatar/intro/real_name/gender/id_card/id_card_front/id_card_back) |

Pengisian lengkap pertama kali dianggap permohonan pendaftaran, status=pending menunggu audit.

#### 3.2 Jadwal

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/technician/schedule` | kueri jadwal (?start_date=&end_date=) |
| PUT | `/api/technician/schedule` | atur jadwal (date/time_slots/status), tumpang tindih slot waktu 422「Konflik dengan jadwal yang ada」 |
| POST | `/api/technician/schedule/batch` | jadwal massal (ronde ke-23): rentang tanggal ≤7 hari + filter weekdays, hari sudah ada jadwal dilewati, respons created/skipped |

#### 3.3 Pesanan Teknisi

`GET /api/technician/orders` — daftar pesanan (?status=&page=1)

#### 3.4 Pendapatan

`GET /api/technician/earnings` — ringkasan pendapatan (today_income/pending_settlement/balance + daftar transaksi)

#### 3.5 Penarikan Dana

`POST /api/technician/withdraw` — ajukan penarikan (amount)
Aturan: setiap tanggal 20 bisa menarik, T+1 masuk, jumlah minimum/batas kelipatan ratus dikonfigurasi backend.

**Reservasi dalam perjalanan (2026-08-26)**: saat pengajuan saldo langsung dipotong dicadangkan dalam perjalanan (pending/approved); sebelum transfer persetujuan verifikasi ulang settled − withdrawn − dalam perjalanan ≥ jumlah penarikan; persetujuan bersamaan tidak akan transfer ganda.

#### 3.6 Balasan Ulasan (ronde ke-18)

`POST /api/technician/review/reply/{order_id}` — balas ulasan teknisi (reply). Ulasan tidak ada/bukan sendiri seragam 404 (tidak bocorkan keberadaan); sudah ada balasan 422 (tolak idempoten tanpa timpa); balasan kosong 422. Balasan sukses notifikasi situs pengguna (type='review_reply').

#### 3.6 Workbench

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/technician/work/today` | daftar tugas hari ini |
| GET | `/api/technician/work/records` | catatan selesai paginasi |
| POST | `/api/technician/work/{id}/start` | mulai layanan |
| POST | `/api/technician/work/{id}/complete` | selesaikan layanan |

**Tugas hari ini**: status ∈ [confirmed, serving], service_time hari ini atau kosong, mengembalikan service_name/price/nickname/avatar.

**Catatan selesai**: status ∈ [serving, completed], urut service_end_at turun, respons paginasi berisi meta.

**Mulai/selesaikan layanan**: row lock + validasi state machine, operasi idempoten. Mulai layanan tulis service_start_at; selesaikan layanan tulis service_end_at dan kirim notifikasi situs. Kode error: bukan sendiri 403, status salah 422, hashid tidak valid 422.

---

### 4. Antarmuka Pesanan (perlu otentikasi JWT)

| Metode | Jalur | Keterangan |
|------|------|------|
| POST | `/api/order` | buat pesanan (order_type/items/store_id/technician_id/service_time/coupon_id/user_coupon_id/promotion_id/remark) |
| GET | `/api/order/list` | daftar pesanan (?status=&page=1) |
| GET | `/api/order/detail/{id}` | detail pesanan |
| POST | `/api/order/cancel/{id}` | batalkan pesanan (reason) |
| POST | `/api/order/pay/{id}` | inisiasi pembayaran (pay_channel: wechat/balance, use_points: poin setara uang opsional) |
| POST | `/api/order/refund/{id}` | ajukan refund |
| POST | `/api/order/verify/{id}` | verifikasi (code: nilai kode QR) |
| POST | `/api/order/reschedule/{id}` | ganti jadwal janji temu (new_service_time wajib/reason opsional) |
| GET | `/api/order/logistics/{id}` | pelacakan logistik (ronde ke-19, pesanan product) |
| POST | `/api/order/review/{order_id}` | submit ulasan (rating 1-5/content/images) (registrasi lengkap ronde ke-19) |
| POST | `/api/order/review/{order_id}/append` | ulasan susulan (content/images dipisah koma) (ronde ke-19) |

**Status pesanan**: pending(待支付) → paid(已支付) → confirmed(已确认) → serving(服务中) → completed(已完成)

**Saat buat pesanan**: Redis SETNX kunci teknisi 3 menit, keluar halaman atau timeout lepaskan.

**Anti tamper harga (2026-08-26)**: jumlah item pesanan selalu berdasarkan catatan database (target_type=service cari erik_service, product cari erik_product), harga kiriman klien tidak ikut perhitungan; target_type tidak dikenal 422; target_id wajib kirim nilai encode hashid (kirim raw id decode menjadi 0 → 422「Barang tidak ada atau sudah dihapus」); harga belanja bersama/flash sale juga berdasarkan DB.

**Aturan refund**: dalam 15 menit pemesanan atau jarak mulai >6 jam refund 100% / ≤6 jam 90% / sudah mulai 80% / setelah konfirmasi mulai tidak refund.

**Potongan kupon**: buat pesanan opsional kirim user_coupon_id (hashid). Kode error: kupon orang lain 404, ambang kurang/sudah kedaluwarsa/sudah dihapus/sudah dipakai 422, hashid ilegal 422. Potongan dua tahap: saat order PriceCalculator.applyCoupon validasi read-only dan hitung jumlah potongan tulis discount_amount; setelah pembayaran sukses consume set kupon used; saat refund restoreCouponAndCard idempoten kembalikan.

**Pembayaran saldo & refund**: badan permintaan pembayaran kirim `pay_channel: "balance"` pakai saldo dompet; refund WeChat dan refund saldo sama-sama isi ulang jumlah ke saldo dompet.

**Poin setara uang**: badan permintaan pembayaran opsional kirim `use_points` (bilangan bulat). Validasi SUM agregat saldo poin (kolom balance erik_user_points adalah snapshot kenaikan satuan, tidak bisa langsung dipakai sebagai saldo), jumlah potongan = floor(use_points / config('app.points_rate', 100)) yuan, jumlah bayar aktual = terutang asli - potongan (batas bawah 0.01, melebihi terutang potong sesuai terutang tidak buang poin). Sukses tulis transaksi konsumsi type=consume/source=points_offset (idempoten, retry tidak potong ulang). Saldo kurang 422.

**Isi ulang poin**: saat batalkan/refund kembalikan poin yang dikonsumsi points_offset (type=earn/source=points_refund): batal penuh, refund proporsional, 5 titik pemasangan idempoten (refundOffsetPoints).

**Pemesanan belanja bersama (ronde ke-16)**: buat pesanan opsional kirim `promotion_id` (hashid). Validasi: hanya tipe group_buy, dalam masa berlaku aktivitas, pemanggil adalah peserta, belum penuh (sudah terbentuk terkunci 422), layanan pesanan cocok aktivitas; harga belanja bersama = harga asli × discount_percent/100, larang tumpuk kupon/kartu kunjungan/poin (kirim salah satu 422). Pesanan masuk DB promotion_id/participant_id; pembayaran sepenuhnya pakai ulang `POST /api/order/pay/{id}`, saat pay malas menilai aktivitas sudah tutup (kedaluwarsa belum terbentuk) → pesanan otomatis dibatalkan dan lepaskan kunci teknisi.

**Pemesanan flash sale (ronde ke-18, sudah dimatikan)**: ~~buat pesanan kirim `promotion_id` (tipe flash_sale)~~ —— mulai 2026-08 kanal promosi lama FLASH_SALE dihapus, cabang promosi store() tinggal GROUP_BUY belanja bersama (non belanja bersama promotion 422); flash sale seragam lewat kanal `/api/seckill` ronde ke-24 (seckill_id injeksi ke store, potong stok row lock dalam transaksi), PromotionController::index filter flash_sale, show/join untuk itu mengembalikan 400, konstanta `Promotion::TYPE_FLASH_SALE` dipertahankan kompatibel data historis.

**Ganti jadwal janji temu (ronde ke-17)**: `POST /api/order/reschedule/{id}` kirim new_service_time (wajib) + reason (opsional), ganti waktu teknisi sama. Aturan: hanya pesanan sendiri (bukan sendiri 404), hanya tipe appointment dan status pending/paid/confirmed bisa ganti (lainnya 422), jarak mulai layanan asli ≥ 6 jam (selaras jendela refund penuh) baru bisa ganti. Proteksi bersamaan: B1 order_lock (keluarga mutual exclusion sama dengan pay/cancel/refund) → kunci teknisi slot baru Redis SETNX EX 180 (cegah oversold ganti jadwal bersamaan) → row lock baca ulang dalam transaksi + validasi DB konflik jadwal B2 (kecuali pesanan ini) → perbarui service_time + tulis catatan erik_order_reschedule → lepaskan kunci slot lama, kunci slot baru dipegang pesanan ini → subscription message SCENE_RESCHEDULE (tidak dikonfigurasi degradasi notifikasi situs). Jalur gagal rollback transaksi sekaligus lepaskan kunci slot baru.

**Pelacakan logistik (ronde ke-19)**: `GET /api/order/logistics/{id}` — hanya pesanan product sendiri yang bisa dilihat (bukan sendiri/bukan barang/belum kirim seragam 404). Baca order.remark JSON (shipping_company/tracking_no/shipped_at, ditulis saat pengiriman oleh admin MallOrderController::ship()), parseShippingInfo/parseReceiver dua parsing fallback format lama; nomor ponsel penerima deidentifikasi 138****5678.

**Ulasan (ronde ke-19)**: `POST /api/order/review/{order_id}` submit ulasan (rating wajib 1-5, content/images opsional): bukan sendiri 404, non-completed 422, ulasan duplikat 400. `POST /api/order/review/{order_id}/append` ulasan susulan (content wajib, images dipisah koma): ulasan tidak ada/bukan sendiri seragam 404, non-completed 422, ulasan susulan duplikat 422, konten kosong 422; sukses tulis append_content/append_images(JSON)/append_at dan notifikasi situs teknisi type='review_append', respons tampilkan kolom append.

### 4.1 Antarmuka Purna Jual (perlu otentikasi JWT)

| Metode | Jalur | Keterangan |
|------|------|------|
| POST | `/api/aftersales` | ajukan purna jual (order_id hashid/type: refund|exchange/reason), validasi pesanan sendiri 404, status paid+completed baru bisa diajukan 422, purna jual berlangsung pesanan sama deduplikasi 422 |
| GET | `/api/aftersales` | daftar purna jual saya (?status=&page=1&limit=) |
| GET | `/api/aftersales/{id}` | detail purna jual (validasi kepemilikan 404) |

**Status purna jual**: pending(待审核) → approved(通过) / rejected(拒绝). approved hanya peralihan status, tindakan refund tetap memakai `POST /api/order/refund/{id}`.

---

### 4.2 Antarmuka Belanja Bersama/Promosi (perlu otentikasi JWT; FLASH_SALE sudah dimatikan)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/promotions` | daftar aktivitas (?type=group_buy；flash_sale sudah difilter tidak kembali) |
| GET | `/api/promotions/{id}` | detail aktivitas (termasuk jumlah peserta/apakah sudah terbentuk; tipe flash_sale 400) |
| GET | `/api/promotions/{id}/participants` | daftar peserta |
| POST | `/api/promotions/join/{id}` | ikut aktivitas (sempurna ronde ke-15: respons berisi discount_percent/original_price/group_price; tipe flash_sale 400) |

**Aturan partisipasi**: group_buy penuh (≥min_people) terkunci, setelah terbentuk peserta baru 422; kedaluwarsa belum penuh tutup malas (saat show/join status set 0). Setelah join pesan dengan harga belanja bersama lihat「Pemesanan belanja bersama (ronde ke-16)」. Flash sale tidak lagi lewat kanal ini, lihat「24. Antarmuka Flash Sale」.

---

### 5. Antarmuka Pemasaran (perlu otentikasi JWT)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/marketing/coupons` | daftar kupon (?status=available/used/expired) |
| POST | `/api/marketing/coupons/receive` | ambil kupon (coupon_id) |
| GET | `/api/marketing/cards` | daftar kartu member |
| POST | `/api/marketing/cards/buy` | beli kartu member (card_id) |
| GET | `/api/marketing/cards/my` | daftar kartu kunjungan saya |
| POST | `/api/marketing/cards/use` | verifikasi kartu kunjungan (user_card_id/service_id/remark?) |
| GET | `/api/marketing/gift-cards` | daftar kartu hadiah |
| GET | `/api/marketing/gift-cards/my` | kartu hadiah saya (catatan redeem) |
| POST | `/api/marketing/gift-cards/redeem` | tukar kartu hadiah (tipe cash setelah tukar top-up saldo dompet) |
| GET | `/api/marketing/points` | transaksi poin (?type=earn/use/expire&source=order/referral/gift_card/check_in/admin) |
| GET | `/api/marketing/points-exchange` | daftar barang tukar poin (tayang + sisa stok real-time + jumlah sudah ditukar) |
| POST | `/api/marketing/points-exchange/{id}` | tukar (type=coupon terbit kupon / wallet masuk saldo / gift_card kembalikan kode) |
| POST | `/api/marketing/coupons/transfer` | hasilkan kode transfer (user_coupon_id: kode unik 8 digit/berlaku 7 hari) |
| POST | `/api/marketing/coupons/claim` | klaim kupon transfer (code) |
| GET | `/api/marketing/coupons/transfers` | catatan transfer (terkirim pending/claimed/expired + diterima claimed) |

**Kartu kunjungan**: cards/my mengembalikan card_id/name/type/services/total_times/used_times/remaining_times/start_at/end_at/status (perhitungan real-time). Verifikasi sukses mengembalikan {order_id, usage_id, remaining_times}; kode error: hashid tidak valid 422, jumlah kurang 422, sudah kedaluwarsa 400, bukan sendiri 404, cegah duplikat Redis 400.

**Kartu hadiah**: gift-cards/my mengembalikan catatan redeem (type/amount/gift_name/status/used_at).

**Aturan poin**: detail paginasi, filter type (earn/use/expire), filter source (order/referral/gift_card/check_in/admin). Check-in kembali poin (CheckIn, type=earn); konsumsi kembali poin floor(paid_amount×1), dibagikan saat verifikasi dan idempoten; refund potong poin proporsional.

**Kedaluwarsa poin (ronde ke-17)**: kolom erik_user_points.expires_at (config points.expiry_days, default 365 hari, ≤0 tidak pernah kedaluwarsa), semua earn masuk DB isi masa berlaku; proses terjadwal PointsExpiryTimer setiap 60s pemindaian kursor baris earn kedaluwarsa, tulis baris potongan negatif type=expire (source=expiry + order_id telusur transaksi asli, tiga lapis idempoten) + agregat notifikasi situs「Anda memiliki X poin telah kedaluwarsa」; standar saldo tersedia SUM termasuk baris negatif expire, poin kedaluwarsa tidak bisa setara uang/tukar lagi.

**Transfer kupon (ronde ke-17)**: transfer validasi kupon milik sendiri/available/definisi kupon belum kedaluwarsa/belum pernah ditransfer, hasilkan kode transfer 8 karakter unik anti-ambigu (indeks unik uk_code fallback), berlaku 7 hari. claim anti penyalahgunaan: kunci Redis NX (coupon_transfer_claim:{code} 30s) + verifikasi ulang row lock cegah double-spend, indeks unik uk_user_coupon batasi kupon sama hanya bisa ditransfer sekali, kupon ditransfer tidak bisa ditransfer lagi (kupon baru tanpa catatan transfer terblokir natural), tidak bisa klaim kupon yang ditransfer sendiri 422, penerima bukan pemegang asli; malas menilai kedaluwarsa set expired dan pulihkan kupon asli available. Dalam transaksi claim kupon asli set used + buat UserCoupon baru ikat penerima (coupon_id tidak berubah artinya masa berlaku tidak berubah) + catatan set claimed.

---

### 6. Antarmuka Notifikasi (perlu otentikasi JWT)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/notification` | daftar notifikasi (?type=order/system&page=1) |
| PUT | `/api/notification/read/{id}` | tandai sudah baca |
| PUT | `/api/notification/read-all` | semua sudah baca |

---

### 7. Antarmuka Dompet (perlu otentikasi JWT)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/wallet` | saldo dompet + transaksi paginasi |
| POST | `/api/wallet/recharge` | buat slip top-up (amount: yuan) |
| POST | `/api/wallet/recharge/{id}/pay` | inisiasi pembayaran slip top-up (WeChat) |
| POST | `/api/wallet/transfer` | transfer saldo (to_user_id hashid/amount/remark opsional/client_token opsional) (ronde ke-19) |
| GET | `/api/wallet/transfers` | catatan transfer (?direction=out/in&page=1) (ronde ke-19) |
| GET | `/api/wallet/transfers/{id}` | detail transfer (hanya kedua pihak terlihat, orang lain 404) (ronde ke-19) |

**Transaksi**: tipe wallet_txn: recharge / consume / refund / gift_card / referral_reward(komisi referral) / referral_level2(komisi referral level dua) / points_exchange(pencatatan tukar poin), dikembalikan paginasi.

**Top-up**: `POST /api/wallet/recharge` kirim amount (yuan) buat slip top-up, kembalikan hashid slip top-up. `POST /api/wallet/recharge/{id}/pay` inisiasi pembayaran WeChat, respons berisi sign_params (pola sama pembayaran pesanan); callback pembayaran membedakan slip top-up dan pesanan dengan out_trade_no prefiks R.

**Pembayaran saldo**: badan permintaan pembayaran pesanan kirim `pay_channel: "balance"` pakai saldo dompet; refund WeChat dan refund saldo sama-sama isi ulang jumlah ke saldo dompet.

**Transfer saldo (ronde ke-19)**: `POST /api/wallet/transfer` — decode hashid penerima + keberadaan 404, ke diri sendiri 422, jumlah 0.01-1000/per transaksi 422 (perbandingan DECIMAL larang float), saldo kurang 422, akumulasi harian 5000 yuan 422. Bersamaan/idempoten: kunci Redis NX wallet_transfer:{from} 30s serialkan pengirim → dalam transaksi lockForUpdate baris dompet kedua pihak urutan ascending user_id (urutan tetap cegah deadlock) → potong pengirim + tambah penerima + WalletTxn dua transaksi (transfer_out/transfer_in termasuk snapshot balance_after) + catatan transfer completed + notifikasi situs penerima type='balance_received' (gagal hanya catat log). client_token opsional: sukses SETNX 24 jam cegah submit duplikat (permintaan gagal tidak tulis token bisa retry).

---

### 8. Antarmuka Workbench Manajer Toko (perlu otentikasi JWT)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/store-manager/overview` | ringkasan hari ini (jumlah pesanan hari ini/pendapatan hari ini/berlangsung/jumlah teknisi/jumlah verifikasi) |
| GET | `/api/store-manager/orders` | daftar pesanan toko (?status=&page=&limit=) |
| GET | `/api/store-manager/technicians` | daftar teknisi (termasuk jadwal hari ini) |
| GET | `/api/store-manager/revenue` | agregat pendapatan 7 hari terakhir |

**Isolasi store_id**: requireStoreId() paksa pengguna saat ini terikat toko (erik_user.store_id), tanpa toko 403; semua kueri difilter berdasarkan store_id.

---

### 9. Antarmuka Level Pertumbuhan (perlu otentikasi JWT, ronde ke-20)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/growth` | ringkasan pertumbuhan saat ini (balance/level/selisih tingkatan berikutnya/nama level) |
| GET | `/api/growth/records` | transaksi nilai pertumbuhan paginasi (?page=&limit=) |
| GET | `/api/growth/levels` | daftar tingkatan (publik, tidak perlu login) |

**Pencatatan nilai pertumbuhan**: check-in +10; submit ulasan +20 (ulasan susulan tidak masuk); konsumsi floor(paid) setiap 1 yuan 1 poin (dalam callback pembayaran pakai ulang verifikasi status idempoten, callback duplikat tidak masuk ulang).

### 10. Antarmuka Faktur (perlu otentikasi JWT, ronde ke-20)

| Metode | Jalur | Keterangan |
|------|------|------|
| POST | `/api/invoices` | ajukan faktur (order_id hashid/order_type: service=layanan/points_exchange=tukar poin/order_type default service; jumlah dan judul dibawa keluar server, tidak bisa diubah) |
| GET | `/api/invoices` | daftar faktur (?status=&page=) |
| GET | `/api/invoices/{id}` | detail faktur (hanya sendiri) |

**Anti duplikat**: kunci unik uk_order_type(order_id, order_type), pesanan sama tipe sama pengajuan ulang 422 (termasuk tangkap MySQL 1062 fallback).

### 11. Antarmuka Tiket Layanan Pelanggan (perlu otentikasi JWT, ronde ke-20)

| Metode | Jalur | Keterangan |
|------|------|------|
| POST | `/api/tickets` | submit tiket (title/content wajib) |
| GET | `/api/tickets` | daftar tiket (?status=open/closed&page=) |
| GET | `/api/tickets/{id}` | detail tiket (hanya sendiri, orang lain 404) |
| POST | `/api/tickets/{id}/close` | tutup tiket (hanya sendiri/hanya open; rating kepuasan opsional 1-5, di luar batas/bukan bilangan bulat 422, tidak diberikan kompatibel NULL) |

### 12. Antarmuka Kalender Bulanan (perlu otentikasi JWT, ronde ke-20)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/calendar/technician/{id}` | tampilan bulan (?month=YYYY-MM): time_slots jadwal diperluas ke slot jam + sudah dijanjikan dikecualikan |
| GET | `/api/calendar/technician/{id}/day` | tampilan hari (?date=YYYY-MM-DD): detail slot bisa dijanjikan/sudah dijanjikan/tidak bisa dijanjikan hari itu |

### 13. Antarmuka Judul Faktur (perlu otentikasi JWT, ronde ke-21)

| Metode | Jalur | Keterangan |
|------|------|------|
| POST | `/api/invoice-titles` | simpan judul (title_type: personal/company; company wajib tax_no; pengguna sama judul sama duplikat 422; pertama otomatis default) |
| GET | `/api/invoice-titles` | daftar judul (default di atas) |
| PUT | `/api/invoice-titles/{id}` | edit judul (hanya sendiri) |
| DELETE | `/api/invoice-titles/{id}` | hapus judul (hanya sendiri; hapus default otomatis tunjuk paling awal) |
| POST | `/api/invoice-titles/{id}/default` | set default (transaksi nolkan baris lain pengguna sama) |

**Kaitan pengajuan**: POST /api/invoices dukung title_id opsional —— parse judul otomatis bawa masuk invoice_title/tax_no/title_type, tanpa title_id pertahankan jalur isi manual asli.

### 14. Antarmuka Jejak Penjelajahan (perlu otentikasi JWT, ronde ke-21)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/browse-history` | layanan yang baru dijelajahi (join nama layanan/sampul/harga/harga asli, viewed_at urut turun, per_page default 15 batas atas 50) |
| DELETE | `/api/browse-history/{item_id}` | hapus satu (hanya sendiri, ilegal/punya orang lain 404) |
| DELETE | `/api/browse-history` | kosongkan jejak (hanya sendiri) |

**Waktu pencatatan**: setelah akses antarmuka detail layanan sukses otomatis dicatat (belum login dilewati; jelajah duplikat hanya refresh viewed_at tidak insert ulang).

### 15. Antarmuka Aktivitas Potongan (ronde ke-22)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/full-reduction-activities` | daftar aktivitas potongan berlangsung (status=1 dan waktu dalam masa berlaku, urut pengurangan turun; antarmuka publik) |

**Aturan tumpuk saat order**: potongan hanya berlaku pesanan standar (belanja bersama/flash sale lewati), ambang (threshold) dinilai dari jumlah terutang setelah potongan kupon/kartu kunjungan, urutan tumpuk **kupon/kartu kunjungan → potongan → diskon level**; ambil aktivitas pengurangan terbesar; jumlah diskon masuk discount_amount, catatan tambah「Potongan: beli X potong Y」; batas bawah bayar aktual setelah potongan 0.01 yuan.

### 16. Ekspor ICS Janji Temu Saya (perlu otentikasi JWT, ronde ke-22)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/order/ics` | ekspor pesanan berlaku 90 hari (pending/paid/confirmed/serving) sebagai iCal (RFC5545) |

**Output**: `Content-Type: text/calendar; charset=utf-8` + `Content-Disposition: attachment; filename="my-appointments.ics"`. VEVENT: UID=ID pesanan, TZID=Asia/Shanghai, ringkasan「Janji temu: nama layanan」(hilang degradasi「Janji temu」), keterangan (teknisi/toko/alamat, hilang lewati), LOCATION nama toko; teks escape sesuai RFC5545 (\, \; \\ \n) + lipat baris 75 byte. Tanpa pesanan kembalikan kalender kosong legal; hanya ekspor pesanan sendiri.

### 17. Antarmuka Kehadiran Teknisi (perlu otentikasi JWT, ronde ke-22)

| Metode | Jalur | Keterangan |
|------|------|------|
| POST | `/api/technician/attendance/check-in` | check-in masuk kerja (hari sama duplikat 422, indeks unik fallback bersamaan; >10:00 tandai terlambat) |
| POST | `/api/technician/attendance/check-out` | check-out pulang kerja (belum masuk/sudah pulang 422, row lock bersamaan) |
| GET | `/api/technician/attendance` | daftar kehadiran bulan ini + ringkasan hari hadir/jam kerja total/jam kerja rata-rata (?month=YYYY-MM, ilegal 422) |

### 18. Antarmuka Kepatuhan Privasi (perlu otentikasi JWT, ronde ke-22)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/privacy/data` | ekspor data (kelompok JSON personal/orders/points/wallet_txns/reviews/addresses/invoices; log server hanya catat nomor ponsel deidentifikasi+jumlah) |
| POST | `/api/privacy/close-request` | ajukan penghapusan (saldo bukan 0 / pesanan belum selesai / tiket berlangsung 422; set close_status=1 + close_requested_at) |
| POST | `/api/privacy/close-cancel` | batalkan pengajuan penghapusan (close_status 1→0) |
| POST | `/api/privacy/close-confirm` | konfirmasi penghapusan (genap 72 jam baru bisa; close_status=2 + close_at + phone/nickname anonimisasi user{id} + status=0) |

**Intersepsi login**: akun close_status=2 login mengembalikan 403「Akun telah dihapus」.

### 19. Antarmuka Arsip Kesehatan Pengguna (perlu otentikasi JWT, ronde ke-23)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/health-profile` | kueri arsip kesehatan saya (tanpa arsip kembalikan objek kosong) |
| PUT | `/api/health-profile` | buat/perbarui (upsert, satu orang satu; allergies/health_notes batas atas 500 karakter, preferred_technician_id validasi keberadaan; hanya perbarui kolom yang diberikan, respons encode hashid) |
| DELETE | `/api/health-profile` | hapus arsip saya (hanya sendiri) |

Kolom: allergies(riwayat alergi)/health_notes(catatan kesehatan)/preferred_technician_id(teknisi preferensi, bisa kosong).

### 20. Antarmuka Kata Sandi Pembayaran Dompet (perlu otentikasi JWT, ronde ke-23)

| Metode | Jalur | Keterangan |
|------|------|------|
| POST | `/api/wallet/pay-password/set` | set kata sandi pembayaran (6 digit angka `\d{6}`; sudah diatur perlu kirim kata sandi lama 422 intersepsi) |
| POST | `/api/wallet/pay-password/verify` | validasi kata sandi pembayaran (benar/salah kembalikan boolean, tidak masuk DB) |
| POST | `/api/wallet/pay-password/check` | kueri apakah sudah diatur (set: true/false) |

Penyimpanan: hash password_hash() + pay_password_set_at, tidak pernah menyimpan teks polos.

### 21. Antarmuka Garis Waktu Status Pesanan (perlu otentikasi JWT, ronde ke-23)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/order/{id}/timeline` | garis waktu perubahan status pesanan (urut turun; hanya sendiri, pesanan orang lain 404 tidak bocorkan keberadaan) |

Titik tanam: submit/pembayaran (callback WeChat markOrderPaid titik konsumsi tunggal)/batal/konfirmasi teknisi/pengajuan refund/refund lulus/mulai layanan/selesai layanan/batal otomatis timeout/operasi backend (operator=admin) total 8 jenis perubahan.

### 22. Antarmuka Roda Keberuntungan Poin (perlu otentikasi JWT, ronde ke-23)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/wheel/prizes` | daftar hadiah roda (sembunyikan kolom sensitif weight/stock) |
| POST | `/api/wheel/spin` | undian sekali (Redis NX + row lock cegah bersamaan; undian berbobot random_int; poin→transaksi earn termasuk waktu kedaluwarsa, saldo→pencatatan lockForUpdate, kupon→pending terbit manual, tanpa hadiah→lose; client_token idempoten) |
| GET | `/api/wheel/records` | catatan undian saya (paginasi) |

### 23. Antarmuka Mode Tamu (ronde ke-24)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/guest/home` | agregat beranda (banner/pengumuman/kategori layanan/layanan populer, cache Redis svc:guest:home 300s) |
| GET | `/api/guest/services` | daftar layanan (?category_id=hashid&sort=newest|sales|price&page/per_page≤50) |
| GET | `/api/guest/services/{id}` | detail layanan (tidak ada 404) |
| GET | `/api/guest/stores` | daftar toko |
| GET | `/api/guest/technicians` | daftar teknisi (hanya lolos audit; filter ?service_id=hashid; rating urut turun) |

Pintu masuk jelajah tanpa login tanpa otentikasi (hanya middleware ApiVersion).

### 24. Antarmuka Flash Sale (perlu otentikasi JWT, ronde ke-24)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/seckill` | daftar aktivitas flash sale (status=1 dan dalam jendela waktu; termasuk jumlah terjual = jumlah pesanan erik_order.seckill_id, sisa stok) |
| GET | `/api/seckill/{id}` | detail aktivitas (state=not_started/ongoing/ended) |
| POST | `/api/seckill/{id}/buy` | pesan flash sale (client_token idempoten + Redis NX 30s cegah bersamaan + validasi aktivitas; tidak lagi pre-deduct stok) |

**Aturan pemesanan (mulai 2026-08-26)**: stok seragam dipotong row lock dalam transaksi `/api/order store()`, buy hanya validasi pintu masuk/idempoten; harga flash sale = seckill_price (berdasarkan DB), tidak tumpuk kupon/poin/kartu member; pembatalan pesanan tidak isi ulang stok; panggil langsung `/api/order` bawa seckill_id juga potong stok.

### 25. Antarmuka Pemeriksaan Versi APP (ronde ke-24)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/api/app/version?platform=android|ios` | pemeriksaan versi terbaru (platform ilegal 422; tanpa versi kembalikan objek kosong; antarmuka publik) |

Respons: id/platform/version_code/version_name/force_update(1=paksa)/changelog/download_url.

---

## II. API Panel Admin (admin/ :8787)

Header permintaan: `Authorization: Bearer <admin_token>`, `API-Version: v1`

### Dashboard

**`GET /admin/dashboard`** — data dashboard

Respons: user_count / order_count / technician_count / today_revenue + data grafik(jumlah pesanan/jumlah/akun baru/aktivitas)

### Manajemen Pengguna

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/user` | daftar pengguna (?keyword/status/page/per_page) |
| POST | `/admin/user` | tambah pengguna |
| GET | `/admin/user/{id}` | detail pengguna |
| PUT | `/admin/user/{id}` | edit pengguna |
| DELETE | `/admin/user/{id}` | hapus pengguna |
| POST | `/admin/user/batch/destroy` | hapus massal |
| POST | `/admin/user/batch/status` | aktif/nonaktif massal |

### Manajemen Kartu Member

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/member-cards` | daftar kartu (?keyword/status/page/per_page) |
| GET | `/admin/member-cards/{id}` | detail kartu |
| POST | `/admin/member-cards` | tambah kartu (validasi JSON services) |
| PUT | `/admin/member-cards/{id}` | perbarui kartu/tayang tidak tayang |
| DELETE | `/admin/member-cards/{id}` | hapus kartu (ada pengguna pegang kartu tolak) |

ID izin: 365-369.

### Workbench Toko (ronde ke-15)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/stores/workbench-overview` | ringkasan workbench toko (?store_id=hashid：jumlah pesanan hari ini/pendapatan hari ini/berlangsung/jumlah teknisi/verifikasi hari ini, standar sama sisi service) |
| GET | `/admin/orders` | daftar pesanan tambah filter store_id (decode hashid) |

ID izin: 372.

### Barang Tukar Poin (ronde ke-16)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/points-exchange-goods` | daftar barang (?keyword/status/page/per_page) |
| POST | `/admin/points-exchange-goods` | tambah barang (type=coupon/gift_card/wallet；coupon kirim hashid、wallet/gift_card kirim jumlah yuan) |
| PUT | `/admin/points-exchange-goods/{id}` | perbarui barang |
| DELETE | `/admin/points-exchange-goods/{id}` | hapus barang |
| POST | `/admin/points-exchange-goods/{id}/toggle-status` | alih tayang/tidak tayang |
| GET | `/admin/points-exchange-goods/{id}/exchanges` | daftar catatan tukar (termasuk nomor ponsel pengguna + snapshot result) |

ID izin: 373-378.

### Catatan Komisi Referral (ronde ke-16)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/referral-rewards` | catatan komisi (?keyword=&page=&limit=, hanya catatan sudah terbit, filter nama panggilan perekomendasi/ter-referral atau nomor ponsel, encode hashid) |

ID izin: 379.

### Level Teknisi (ronde ke-17)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/technician-tiers/logs` | log perubahan level (join nama teknisi dan nama level lama baru, encode hashid, paginasi) |

ID izin: 380.

**Penilaian otomatis**: TierRatingService::evaluate statistik real-time (jumlah pesanan erik_order completed + rata-rata ulasan, pembulatan 1 desimal) tulis ulang profile.order_count/rating, cocokkan dari tinggi ke rendah sesuai erik_technician_tier_config (min_orders/min_rating), tanpa cocok jatuh ke level terendah. Hanya naik tidak turun (turun mempengaruhi rasio komisi dan koefisien harga, ditangani manual backend sebagai fallback; allowDowngrade=true untuk penilaian ulang manual); idempoten (level sama hanya sinkron statistik); perubahan tulis erik_technician_tier_log + notifikasi situs. Titik pemicu: WorkController::complete / penulisan ulasan ReviewController / penilaian malas saat lihat profil ProfileController.

### Lihat Balasan Ulasan (ronde ke-18)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/reviews/{id}/reply` | detail balasan ulasan (decodeId → find → 404 → output decorate; belum dibalas reply='', reply/replied_at tampil lewat toArray; rute statis mendahului resource) |

ID izin: 381 (slug 'get.admin/reviews/{id}/reply').

### Manajemen Faktur (ronde ke-20)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/invoices` | daftar faktur (?status=pending/issued/rejected&page=) |
| POST | `/admin/invoices/{id}/issue` | terbit faktur (invoice_no wajib, status→issued + issued_at; idempoten: sudah terbit 422) |
| POST | `/admin/invoices/{id}/reject` | tolak (reject_reason wajib, status→rejected; hanya pending bisa ditolak) |

ID izin: 382 daftar / 383 terbit / 384 tolak.

### Manajemen Tiket (ronde ke-20)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/tickets` | daftar tiket (?status=&page=, rute statis mendahului resource hindari shadow) |
| POST | `/admin/tickets/{id}/reply` | balas tiket (content wajib, tulis reply_content/replied_at, tiket kembali open) |
| GET | `/admin/tickets/satisfaction` | ringkasan kepuasan (ronde ke-21): total/rated_count/unrated_count/average 1 desimal/distribution 1-5 bintang bintang kurang isi 0; rute statis mendahului resource |

ID izin: 385 balas tiket / 387 lihat daftar tiket / 388 statistik kepuasan tiket.

### Audit Foto Ulasan (ronde ke-21)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/review-audit` | daftar ulasan bergambar (JSON_LENGTH(images)>0, ?status=visible/hidden&page=, join nama panggilan pengguna dan nama teknisi, ID encode hashid) |
| POST | `/admin/review-audit/{id}/hide` | sembunyikan ulasan (hanya visible bisa disembunyikan, jika tidak 422; setelah disembunyikan daftar ulasan teknisi sisi pengguna otomatis tidak terlihat) |
| POST | `/admin/review-audit/{id}/restore` | pulihkan ulasan (hanya hidden bisa dipulihkan, jika tidak 422) |

ID izin: 389 daftar / 390 sembunyikan / 391 pulihkan.

### Catatan Komisi Referral Level Dua (ronde ke-20)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/referral-level2` | catatan komisi referral level dua (join nama panggilan perekomendasi level satu dan perekomendasi level dua, paginasi) |

ID izin: 386. Aturan pemberian: setelah pembayaran pesanan beri perekomendasi perekomendasi level satu paid×level2_rate (konfigurasi sistem referral.level2_rate default 0.02), uk_order_referred idempoten cegah duplikat.

### Manajemen Kehadiran (ronde ke-22)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/attendance` | catatan kehadiran (?date=YYYY-MM&name=nama teknisi&page=; join real_name, ID encode hashid) |
| GET | `/admin/attendance/stats` | statistik dikelompokkan per teknisi (hari check-in/jam kerja total/jam kerja rata-rata; ?date=YYYY-MM, ilegal 422) |

ID izin: 392 daftar / 393 statistik.

### Manajemen Aktivitas Potongan (ronde ke-22)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/full-reduction-activities` | daftar aktivitas (paginasi) |
| POST | `/admin/full-reduction-activities` | tambah (threshold/reduction/title/status/start_at/end_at) |
| PUT | `/admin/full-reduction-activities/{id}` | edit |
| POST | `/admin/full-reduction-activities/{id}/toggle-status` | tayang/tidak tayang |
| DELETE | `/admin/full-reduction-activities/{id}` | hapus (dengan confirmPassword) |

ID izin: 396 daftar / 397 tambah / 398 edit / 399 tayang/tidak tayang / 400 hapus (satu catatan izin sesuai satu slug method.path, maka 5 rute 5 catatan).

### Catatan Bagi Hasil (ronde ke-22)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/profit-sharing` | catatan bagi hasil (leftJoin nomor pesanan/nama panggilan teknisi, ?status&order_no&technician_name&page=, encode hashid) |

ID izin: 394. Logika server: erik_system_config group=profit_sharing (enabled/receiver_ratio); tidak diaktifkan degradasi disabled hanya log; setelah diaktifkan pembayaran sukses otomatis minta bagi hasil (jumlah=bayar aktual×receiver_ratio default 0.7, pesanan sama pending/success idempoten lewati); tanpa kredensial tidak eksekusi HTTP, struktur permintaan dicatat log.

### Manajemen Roda Poin (ronde ke-23)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/lucky-wheel` | daftar hadiah roda (termasuk weight/stock, paginasi) |
| POST | `/admin/lucky-wheel` | tambah hadiah (nama/tipe points/balance/coupon/none/weight/stok/gambar) |
| GET/PUT | `/admin/lucky-wheel/{id}` | detail / edit |
| DELETE | `/admin/lucky-wheel/{id}` | hapus |
| POST | `/admin/lucky-wheel/{id}/toggle-status` | tayang/tidak tayang |
| GET | `/admin/lucky-wheel/records` | catatan undian (?status&page=, termasuk nama panggilan pengguna/nama hadiah) |

ID izin: 401-406. Rute statis `/lucky-wheel/records` dan `/lucky-wheel/{id}/toggle-status` terdaftar sebelum resource hindari shadow {id}.

### Manajemen Bonus Pelanggan Berulang (ronde ke-24)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/return-customer/config` | lihat konfigurasi (saklar enabled / rasio ratio) |
| PUT | `/admin/return-customer/config` | perbarui konfigurasi (enabled in:0,1；ratio between:0.01,1) |
| GET | `/admin/return-customer/rewards` | daftar catatan bonus (?keyword nama teknisi/nomor pesanan/nama panggilan pengguna, type=return_customer paginasi) |

ID izin: 412-414. Aturan bonus: pengguna konsumsi kedua (pesanan selesai) ke teknisi sama dalam 30 hari terbit bonus = bayar aktual × ratio (default 0.05), tulis erik_technician_earnings (type=return_customer, status=pending) ikut rantai penyelesaian komisi diselesaikan seragam; pesanan sama idempoten tidak terbit ulang.

### Manajemen Aktivitas Flash Sale (ronde ke-24)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/seckill` | daftar aktivitas (paginasi) |
| POST | `/admin/seckill` | tambah aktivitas (name/service_id/seckill_price/original_price/stock/start_at/end_at) |
| GET | `/admin/seckill/{id}` | detail aktivitas |
| PUT | `/admin/seckill/{id}` | edit |
| DELETE | `/admin/seckill/{id}` | hapus |
| POST | `/admin/seckill/{id}/toggle-status` | tayang/tidak tayang |
| GET | `/admin/seckill/{id}/orders` | daftar pesanan flash sale |

ID izin: 407-411、420. Jumlah terjual = jumlah pesanan erik_order.seckill_id; stok potong row lock, sold out intersepsi.

### Manajemen Versi APP (ronde ke-24)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/versions` | daftar versi |
| POST | `/admin/versions` | tambah versi (platform/version_code/version_name/force_update/changelog/download_url/status) |
| PUT | `/admin/versions/{id}` | edit |
| DELETE | `/admin/versions/{id}` | hapus |

ID izin: 416-419. Antarmuka deteksi pembaruan /api/app/version ambil versi terbaru (updated_at/id terbesar) di status=1.

### Ekspor Jadwal (ronde ke-24)

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/technician-schedule/export` | ekspor CSV jadwal (UTF-8 BOM, Excel buka langsung; start_date/end_date wajib dan rentang ≤31 hari; technician_id opsional hashid) |

ID izin: 415. Kolom: ID teknisi/nama teknisi/tanggal/detail slot waktu (time_slots JSON diurai menjadi "09:00-12:00, 14:00-18:00").

### Peran & Izin

| Metode | Jalur | Keterangan |
|------|------|------|
| GET/POST/PUT/DELETE | `/admin/role` | CRUD peran |
| GET/POST/PUT/DELETE | `/admin/permission` | CRUD izin (struktur pohon)|

### Konfigurasi Sistem

| Metode | Jalur | Keterangan |
|------|------|------|
| GET | `/admin/config` | daftar konfigurasi |
| POST | `/admin/config` | tambah konfigurasi (group/key/value/type/description) |
| PUT | `/admin/config/{id}` | edit konfigurasi |
| DELETE | `/admin/config/{id}` | hapus konfigurasi |

### Log Operasi

**`GET /admin/log`** — kueri log

Parameter: `?user_id/action/source/start_date/end_date/page`

Kolom `souce`: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### Ekspor

| Metode | Jalur | Keterangan |
|------|------|------|
| POST | `/admin/export/excel` | ekspor Excel (type: users/technicians/orders/finance). Kolom sensitif otomatis dideidentifikasi |
| POST | `/admin/export/pdf` | ekspor panel PDF (type: dashboard) |

### Unggah File

**`POST /admin/upload`** — unggah file (multipart/form-data)

### Pusat Pribadi

| Metode | Jalur | Keterangan |
|------|------|------|
| PUT | `/admin/profile` | ubah profil pribadi |
| PUT | `/admin/profile/password` | ubah kata sandi |
| POST | `/admin/profile/logout` | keluar login |

### Impor

**`POST /admin/import/users`** — impor pengguna massal (Excel)

### Pemantauan

| Metode | Jalur | Otentikasi | Keterangan |
|------|------|------|------|
| GET | `/health` | Tanpa | pemeriksaan kesehatan |
| GET | `/metrics` | Tanpa | metrik Prometheus |
| GET | `/.well-known/security.txt` | Tanpa | kontak keamanan (RFC 9116) |
| GET | `/api/docs` | Tanpa | dokumen API |

---

## III. Keterangan Umum

### Kode Error

| code | Keterangan |
|------|------|
| 0 | sukses |
| 401 | belum login atau Token kedaluwarsa |
| 403 | tanpa izin |
| 404 | sumber tidak ada |
| 422 | validasi parameter gagal |
| 429 | permintaan terlalu sering |

### Encoding ID

- Semua `id` dan `*_id` dalam respons API di-encode melalui hashids
- Parameter `id` yang dibawa dalam permintaan juga harus memakai format encoding hashids
- Frontend langsung memakai string encoding, tidak perlu decode manual

### Deidentifikasi Nomor Ponsel

Format nomor ponsel dalam respons: `138****8000`. Ekspor Excel diperlakukan sama.

### Enkripsi Data

- Lapisan API: kolom sensitif dalam respons dienkripsi melalui `erikwang2013/encryption`
- Lapisan DB: nomor ponsel/KTP/ID WeChat dll di-enkripsi/dekripsi otomatis melalui `erikwang2013/encryptable`

### Konfigurasi Variabel Lingkungan

| Variabel | Keterangan |
|------|------|
| WECHAT_SUBSCRIBE_TEMPLATE_ID | template ID subscription message pengingat janji temu |
| WECHAT_SUBSCRIBE_TEMPLATE_PAID | template ID subscription message pembayaran sukses |
| WECHAT_SUBSCRIBE_TEMPLATE_REFUND | template ID subscription message refund |
| WECHAT_SUBSCRIBE_TEMPLATE_VERIFIED | template ID subscription message verifikasi |
| WECHAT_SUBSCRIBE_TEMPLATE_REMINDER | template ID subscription message pengingat sebelum layanan mulai (ronde ke-18) |
| WECHAT_SUBSCRIBE_TEMPLATE_EXPIRY | template ID subscription message pengingat kedaluwarsa kartu member/kupon (ronde ke-18) |

Template subscription message tidak dikonfigurasi otomatis degradasi ke notifikasi situs.

**Skenario subscription message**: SCENE_PAY(pembayaran sukses) / SCENE_REFUND(refund masuk) / SCENE_VERIFIED(verifikasi sukses) / SCENE_RESCHEDULE(ganti jadwal sukses) / SCENE_REMINDER(pengingat sebelum layanan mulai, ronde ke-18) / SCENE_EXPIRY(pengingat kedaluwarsa, ronde ke-18). Push sukses baru tulis push_sent_at, gagal retry ronde berikutnya.

**Notifikasi top-up masuk (ronde ke-18)**: callback top-up WeChat (nomor slip prefiks R) dalam transaksi tulis notifikasi situs type='wallet_recharge'「Anda berhasil top-up ¥X.XX」; pakai ulang idempoten callback (hanya pertama pending→paid terpicu), commit atomik transaksi sama dengan perubahan status, kegagalan penulisan tidak blokir alur utama.
