# Penjelasan Fitur
> **Languages**: [中文](../FEATURES.md) · [English](../en/FEATURES.md) · [한국어](../ko/FEATURES.md) · [Русский](../ru/FEATURES.md) · [Deutsch](../de/FEATURES.md) · [Français](../fr/FEATURES.md) · [Español](../es/FEATURES.md) · [Português](../pt/FEATURES.md) · [हिन्दी](../hi/FEATURES.md) · [العربية](../ar/FEATURES.md) · [বাংলা](../bn/FEATURES.md) · [日本語](../ja/FEATURES.md)

> Terjemahan bahasa Indonesia · Asli: [中文](../../docs/FEATURES.md)

> **Status Proyek**: Semua selesai ✅ | 109 controller | 103 model | 344 pengujian (service 240 / admin 104) | WebSocket | callback pembayaran | pemanggilan nomor | ujian | komunitas

## I. Sisi Pengguna (WeChat Mini Program + Flutter APP)

Fungsi Mini Program dan APP sisi pengguna sepenuhnya sama. Akun terpadu mendukung peralihan identitas pelanggan/teknisi.

### 1. Otentikasi

| Fitur | Keterangan |
|------|------|
| Registrasi nomor ponsel | nomor ponsel + kode verifikasi + kata sandi + konfirmasi kata sandi, mendukung kode referral |
| Login kata sandi | nomor ponsel terdaftar + kata sandi |
| Login kode verifikasi | nomor ponsel terdaftar + kode verifikasi |
| Login WeChat | login otorisasi WeChat, pertama kali perlu mengikat nomor ponsel |
| Mode tamu | bisa menjelajah tetapi tidak bisa memesan, pemesanan perlu registrasi |
| Lupa kata sandi | ubah kata sandi dengan kode verifikasi |
| Perjanjian pengguna/perjanjian privasi | dapat diedit di panel admin, ditampilkan saat registrasi |

### 2. Beranda

| Fitur | Keterangan |
|------|------|
| Lokasi LBS | lokasi area pengguna, menampilkan layanan area tersebut, mendukung ganti kota |
| Banner | putar otomatis, panel admin mengatur tautan (halaman web/detail/tanpa tindakan) |
| Pengumuman | berjalan berputar, klik lihat daftar, ditambah oleh panel admin |
| Kategori layanan | gambar/nama/harga/volume penjualan, klik masuk detail |
| Kupon pengguna baru | otomatis didapat saat registrasi |

### 3. Item Layanan

| Fitur | Keterangan |
|------|------|
| Informasi dasar | gambar/nama/harga/volume penjualan/spesifikasi/durasi layanan/detail item |
| Ulasan pengguna | menampilkan isi ulasan, bisa lihat lebih banyak |
| Janji temu layanan | masuk ke halaman konfirmasi pesanan |
| Pilihan toko | alamat toko layanan di lokasi (navigasi)/jam buka/telepon kontak |
| Pilihan teknisi | nama teknisi/avatar/rating |
| Waktu layanan | pilih slot waktu janji temu |
| Diskon 90% jam sepi | 10-12 / 17-18 / setelah 21:00 |
| Diskon 95% janji lebih awal | 30 menit lebih awal, tidak dapat digabung dengan kupon |
| Kupon | menampilkan jumlah yang bisa dipakai, pakai/tidak pakai |
| Catatan | catatan kebutuhan layanan (dibatasi karakter) |
| Perjanjian layanan | baca dan konfirmasi sebelum submit |

### 4. Pencarian Produk & Keranjang

| Fitur | Keterangan |
|------|------|
| Pencarian barang | pencarian nama |
| Filter kategori | cari berdasarkan kategori |
| Detail barang | jumlah yang bisa dibeli/favorit/bagikan/tambah ke keranjang/beli sekarang |
| Keranjang | pilih/hapus/ubah jumlah |

### 5. Pesanan

| Fitur | Keterangan |
|------|------|
| Semua pesanan | lihat per Tab status |
| Belum dibayar | lihat/bayar |
| Belum dikirim/ambil sendiri | dorong kirim/batalkan pesanan/lihat |
| Belum diterima | info logistik/konfirmasi terima |
| Belum diulas | detail pesanan/ulasan teks + foto |
| Selesai | lihat info pesanan |
| Aturan refund | dalam 15 menit pemesanan atau >6 jam refund 100% / <6 jam refund 90% / setelah mulai refund 80% / setelah dikonfirmasi tidak refund |

### 6. Teknisi (perspektif pelanggan)

| Fitur | Keterangan |
|------|------|
| Daftar teknisi | jarak dekat ke jauh/avatar/nama/jumlah pesanan/rating/favorit/jarak/waktu tersedia/janji sekarang |
| Detail teknisi | gambar/nama/jarak/pesanan/ulasan/favorit/daftar item layanan |
| Pendaftaran teknisi | isi informasi untuk mendaftar jadi teknisi, unduh APP sisi teknisi |

### 7. Workbench Teknisi (setelah alih identitas teknisi)

| Fitur | Keterangan |
|------|------|
| Ringkasan hari ini | ringkasan pesanan/pendapatan hari ini |
| Pengaturan jadwal | atur slot waktu yang bisa dijanjikan per hari |
| Pesanan saya | sudah dijanjikan belum diverifikasi/selesai |
| Pindai verifikasi | pindai kode QR pengguna untuk verifikasi kunjungan |
| Manajemen member | daftar member yang dilayani/data konsumsi kartu/verifikasi kunjungan/arsip |
| Manajemen pendapatan | pendapatan hari ini/dalam penyelesaian/saldo dompet |
| Dana dalam perjalanan | sudah diverifikasi belum diselesaikan, konfirmasi otomatis 3 hari |
| Penarikan dana | setiap tanggal 20, T+1 masuk WeChat Pay; audit sisi admin, jumlah ≥500 persetujuan dua tingkat (manajer toko → keuangan); saat pengajuan saldo dicadangkan dalam perjalanan, verifikasi ulang sebelum transfer persetujuan, cegah transfer ganda pada persetujuan bersamaan (diperkuat 2026-08-26) |
| Kehadiran | check-in/check-out/unggah foto kebersihan |
| Bonus pelanggan berulang | catat bonus konsumsi kedua dalam 30 hari |
| Pelatihan profesional | kursus video/kursus teks-gambar |
| Tugas hari ini | WorkController today: ambil todo hari ini secara real-time |
| Catatan selesai | WorkController records: catatan selesai historis |
| Mulai/selesaikan layanan | WorkController start/complete: row lock + guard state machine + idempoten, otomatis tulis notifikasi situs setelah selesai |
| Workbench teknisi Mini Program | tech-work tiga Tab: pindai verifikasi/tugas hari ini/catatan selesai |

### 8. Pusat Pribadi

| Fitur | Keterangan |
|------|------|
| Info pribadi | avatar/nama panggilan/nomor ponsel |
| Alih identitas | pelanggan ↔ teknisi |
| Notifikasi pesan | notifikasi situs (erik_notification); halaman pusat pesan: paginasi/tarik-refresh/sorot sudah baca/tandai sudah baca/semua sudah baca |
| Kartu member saya | kartu bulanan/kartu tahunan VIP/kartu kunjungan (kedaluwarsa/jumlah/sudah pakai/sisa) |
| Poin saya | catatan perolehan/poin tersedia/catatan penggunaan (1:100 tukar kartu hadiah); check-in/poin kembali konsumsi, refund dipotong proporsional, detail terbagi + filter type/source |
| Kartu hadiah saya | kartu tunai/hadiah fisik; tipe cash tukar langsung top-up ke dompet |
| Kupon | sudah ambil tersedia/sudah pakai/sudah kedaluwarsa |
| Favorit saya | item layanan yang difavoritkan |
| Ikuti akun publik | dialog kode QR, tekan lama untuk simpan |
| Promosi pengguna | keterangan promosi/poster kode QR/daftar pengguna direkomendasikan/bonus poin |
| Masukan | submit teks + foto, balasan 24 jam |
| Tentang kami | LOGO/pengenalan/telepon layanan/website/email |

### 9. Pengaturan

| Fitur | Keterangan |
|------|------|
| Ubah kata sandi | kata sandi saat ini + kata sandi baru + konfirmasi kata sandi baru |
| Ganti ponsel | kode verifikasi ponsel saat ini + kode verifikasi ponsel baru |
| Perjanjian pengguna | tampilan teks, dapat diedit di backend |
| Perjanjian privasi | tampilan teks, dapat diedit di backend |
| Deteksi pembaruan | nomor versi + pembaruan |
| Penghapusan akun | keterangan penghapusan + konfirmasi operasi |
| Keluar login | hapus status login |

### 10. Dompet Isi Saldo (ronde ke-6)

| Fitur | Keterangan |
|------|------|
| Saldo dompet | GET /api/wallet saldo + transaksi (tabel user_wallet/wallet_recharge/wallet_txn) |
| Top-up | POST /api/wallet/recharge buat slip top-up; POST /api/wallet/recharge/{id}/pay bayar top-up WeChat, callback memakai nomor slip prefiks R |
| Pembayaran saldo | pay_channel=balance kanal pembayaran pesanan |
| Isi ulang refund | refund WeChat/saldo otomatis isi ulang ke saldo (refundToBalance / creditRefundToWallet) |

### 11. Subscription Message (ronde ke-6+8)

| Fitur | Keterangan |
|------|------|
| Skenario berlangganan | 3 skenario event pesanan: pembayaran sukses / refund masuk / verifikasi sukses |
| Idempoten | penanda push_sent_at cegah push duplikat |
| Degradasi | tidak konfigurasi template berlangganan otomatis degradasi ke notifikasi situs |

### 12. Siklus Tertutup Verifikasi Kartu Kunjungan (ronde ke-8)

| Fitur | Keterangan |
|------|------|
| Kartu kunjungan saya | GET /api/marketing/cards/my hitung real-time used_up/expired |
| Verifikasi potong kunjungan | POST /api/marketing/cards/use: Redis NX idempoten + lockForUpdate row lock, langsung buat pesanan completed + OrderItem + OrderPayment(pay_type='card') |

### 13. Potongan Kupon (ronde ke-9)

| Fitur | Keterangan |
|------|------|
| Pilih kupon saat order | bisa kirim user_coupon_id opsional saat order, PriceCalculator.applyCoupon validasi read-only + hitung jumlah |
| Tipe diskon | fixed jumlah tetap / percent persentase, min_amount ambang potongan |
| Konsumsi & pengembalian | pembayaran sukses consume set used; refund restoreCouponAndCard idempoten kembalikan |

### 14. Kartu Hadiah (ronde ke-9)

| Fitur | Keterangan |
|------|------|
| Tukar | redeem: tipe cash top-up ke dompet (row lock cegah double entry, WalletTxn type='gift_card'), tipe gift hanya tandai |
| Kartu hadiah saya | GET /api/marketing/gift-cards/my |

### 15. Sistem Poin (ronde ke-9+10)

| Fitur | Keterangan |
|------|------|
| Check-in kembali poin | CheckIn check-in harian |
| Konsumsi kembali poin | saat verifikasi floor(paid×1), idempoten order_id, snapshot balance |
| Potongan refund | clawbackOrderPoints potong proporsional (3 titik pemasangan) |
| Poin setara uang | kirim use_points saat bayar, 100 poin=1 yuan (config app.points_rate), validasi SUM agregat saldo, transaksi konsumsi source=points_offset idempoten |
| Isi ulang poin (ronde ke-15) | batalkan/refund kembalikan poin points_offset: refundOffsetPoints 5 titik pemasangan (doCancel 3 jalur/doRefund transaksi WeChat/creditRefundToWallet/completeOneRefundCompensation), source=points_refund idempoten |
| Detail poin | GET /api/marketing/points paginasi + filter type/source, type seragam earn |

### 16. Rantai Pemesanan Mini Program (ronde ke-10)

| Fitur | Keterangan |
|------|------|
| Halaman detail layanan | service/detail |
| Halaman konfirmasi pesanan | order/confirm: pilih kupon/ambang abu/destinasi perkiraan klien → POST /order → bayar WeChat/saldo |
| Skala halaman | Mini Program kini total 20 halaman |

### 17. Tiga Pintu Masuk Sisi Pengguna (ronde ke-10)

| Fitur | Keterangan |
|------|------|
| Favorit | halaman favorit favorite (pintu masuk halaman user) |
| Promosi | referral: kode undangan/salin tautan/daftar pengguna direkomendasikan |
| Masukan | formulir feedback |

### 18. Otorisasi Subscription Message (ronde ke-14)

| Fitur | Keterangan |
|------|------|
| Otorisasi berlangganan | utils/subscribe.js kelola terpusat ID template (nama kunci selaras erik_system_config.wechat_app.template_ids sisi server) |
| Skenario pemicu | dalam callback gesture setelah janji sukses/pembayaran sukses wx.requestSubscribeMessage, template ID tidak dikonfigurasi atau pengguna menolak keduanya diam |
| Rantai sisi server | WechatTemplateMessageService kirim + NotificationReminderService pengingat 2 jam~1 jam sebelum janji + proses pemindaian AutoCancelTimer |

### 19. Purna Jual Tukar/Beli Kembali (ronde ke-14)

| Fitur | Keterangan |
|------|------|
| Ajukan purna jual | POST /api/aftersales: type=refund/exchange, validasi pesanan sendiri/paid+completed/deduplikasi pesanan sama |
| Purna jual saya | GET /api/aftersales daftar paginasi + GET /api/aftersales/{id} detail |
| Alur audit | sisi admin approve/reject (rejected wajib remark); approved hanya peralihan status, refund tetap memakai antarmuka refund pesanan |

### 20. Belanja Bersama/Flash Sale (ronde ke-15)

> Mulai 2026-08 kanal FLASH_SALE dimatikan: PromotionController::index filter flash_sale, show/join untuk itu mengembalikan 400, flash sale seragam lewat kanal「43. Flash Sale (ronde ke-24)」; konstanta `Promotion::TYPE_FLASH_SALE` dipertahankan kompatibel data historis. Seksi ini dan「27. Pemesanan Flash Sale」adalah catatan historis.

| Fitur | Keterangan |
|------|------|
| Daftar/detail aktivitas | GET /api/promotions + /api/promotions/{id}, filter type group_buy/flash_sale |
| Ikut | POST /api/promotions/join/{id}: kunci Redis NX cegah oversold (flash_sale batas stok max_people), ikut ulang 422, group_buy penuh terkunci, kedaluwarsa belum penuh tutup malas (saat show/join status set 0) |
| Daftar peserta | GET /api/promotions/{id}/participants |
| Perbaikan status | status PromotionParticipant diubah konstanta bilangan bulat 0/1/2/3 (perbaiki join 1366 rusak di mode strict) |

### 21. Pemesanan Setelah Belanja Bersama Terbentuk (ronde ke-16)

| Fitur | Keterangan |
|------|------|
| Harga belanja bersama | respons join mengembalikan discount_percent/original_price/group_price |
| Pesan belanja bersama | POST /api/order kirim promotion_id: validasi hanya group_buy/aktivitas valid/pemanggil adalah peserta/belum penuh/layanan cocok; harga belanja bersama=harga asli×discount_percent/100, larang tumpuk kupon/kartu kunjungan/poin (422) |
| Penanda pesanan | erik_order tambah kolom promotion_id/participant_id + indeks |
| Penanganan belum terbentuk | kedaluwarsa belum penuh→tutup aktivitas+batalkan massal pesanan pending aktivitas tersebut (idempoten); pay() malas menilai sudah tutup maka otomatis batalkan pesanan dan lepaskan kunci teknisi |

### 22. Komisi Referral (ronde ke-16)

| Fitur | Keterangan |
|------|------|
| Aturan pemberian | setelah pesanan pertama ter-referral completed: jumlah=paid_amount×reward_rate (erik_system_config referral.reward_rate default 0.05, ilegal jatuh ke konstanta), >0 baru beri |
| Titik pemasangan | ReferralRewardService::handleOrderCompleted dipasang dalam transaksi WorkController::complete (serving→completed satu-satunya pintu masuk, verifikasi hanya sampai serving tidak memicu), gagal rollback menyeluruh bisa retry |
| Idempoten | row lock erik_user_referral lockForUpdate + cek kosong rewarded_at + periksa ulang pesanan pertama dalam kunci (panggilan bersamaan/duplikat hanya beri sekali) |
| Pencatatan | row lock dompet akumulasi + WalletTxn type='referral_reward' (balance_after + nomor pesanan remark); catatan referral tulis reward_type/reward_amount/rewarded_at/first_order_at |
| Detail | GET /api/user/referral/earnings paginasi (nama panggilan/avatar/nomor pesanan/jumlah/waktu ter-referral) |

### 23. Mall Tukar Poin (ronde ke-16)

| Fitur | Keterangan |
|------|------|
| Barang tukar | erik_points_exchange_goods: type=coupon/gift_card/wallet, points_cost/value (DECIMAL(25,2) cegah kehilangan presisi ID longsor)/stock/status |
| Daftar barang | GET /api/marketing/points-exchange: barang terpasang + sisa stok real-time + jumlah sudah ditukar |
| Tukar | POST /api/marketing/points-exchange/{id}: kunci Redis NX + row lock barang cegah overtrade; validasi SUM poin (kurang 422) + UserPoints type='consume' source='exchange' potong; coupon terbit kupon / wallet masuk saldo (WalletTxn points_exchange) / gift_card kembalikan kode |
| Idempoten | indeks unik uk_user_goods batasi sekali per pengguna per barang + verifikasi ulang dalam kunci + fallback 1062; snapshot catatan tukar erik_user_points_exchange |

### 24. Penggantian Jadwal Janji Temu (ronde ke-17)

| Fitur | Keterangan |
|------|------|
| Antarmuka | POST /api/order/reschedule/{id}: new_service_time (wajib) + reason (opsional), ganti waktu teknisi sama |
| Aturan | hanya pesanan sendiri (bukan sendiri 404); hanya tipe appointment dan status pending/paid/confirmed (lainnya 422); jarak mulai layanan asli ≥ 6 jam (selaras jendela refund penuh) |
| Proteksi bersamaan | B1 order_lock (keluarga mutual exclusion sama dengan pay/cancel/refund) → kunci teknisi slot baru Redis SETNX EX 180 (cegah oversold pada ganti jadwal bersamaan) → row lock baca ulang dalam transaksi + validasi DB konflik jadwal B2 (kecuali pesanan ini) |
| Penutup | perbarui service_time + tulis erik_order_reschedule (termasuk reason) + lepaskan kunci slot lama/kunci slot baru milik pesanan ini; gagal rollback transaksi sekaligus lepaskan kunci slot baru |
| Notifikasi | subscription message SCENE_RESCHEDULE (template tidak dikonfigurasi degradasi notifikasi situs「Penggantian jadwal janji temu berhasil」) + pushOrderUpdate |

### 25. Transfer Kupon (ronde ke-17)

| Fitur | Keterangan |
|------|------|
| Antarmuka | POST /api/marketing/coupons/transfer (user_coupon_id) hasilkan kode transfer 8 karakter unik anti-ambigu (fallback uk_code, berlaku 7 hari); POST /api/marketing/coupons/claim (code) klaim; GET /api/marketing/coupons/transfers terkirim(pending/claimed/expired)+diterima(claimed) paginasi |
| Validasi | kupon milik sendiri/available/definisi kupon belum kedaluwarsa/belum pernah ditransfer (422); tidak bisa klaim kupon yang ditransfer sendiri, penerima bukan pemegang asli |
| Anti penyalahgunaan | kunci Redis NX coupon_transfer_claim:{code} (30s) + row lock verifikasi ulang dalam transaksi cegah double-spend; indeks unik uk_user_coupon batasi transfer kupon sama sekali; kupon ditransfer tidak bisa ditransfer lagi (kupon baru tanpa catatan transfer terblokir natural); malas menilai kedaluwarsa set expired + pulihkan kupon asli available |
| Klaim | dalam transaksi kupon asli set used + buat UserCoupon baru ikat penerima (coupon_id tidak berubah artinya masa berlaku tidak berubah) + catatan transfer set claimed |

### 26. Kedaluwarsa Poin (ronde ke-17)

| Fitur | Keterangan |
|------|------|
| Masa berlaku | kolom erik_user_points.expires_at; semua earn (check-in/kembali konsumsi/isi ulang) isi expires_at = now + points.expiry_days (default 365, ≤0 tidak pernah kedaluwarsa); consume/use kosong |
| Eksekusi kedaluwarsa | proses terjadwal PointsExpiryTimer setiap 60s pemindaian kursor (100/batch) baris earn expires_at < now → tulis baris potongan negatif type=expire (source=expiry + order_id telusur transaksi asli) → agregat per pengguna notifikasi situs「Anda memiliki X poin telah kedaluwarsa」 |
| Idempoten | ① baris expire order_id menunjuk transaksi earn asli, dalam transaksi lockForUpdate baris asli + verifikasi ulang exists (proses bersamaan serial di row lock) ② kursor id paginasi ③ notifikasi hanya muncul di ronde potongan aktual |
| Standar | saldo tersedia SUM agregat termasuk baris negatif expire; poin kedaluwarsa tidak bisa setara uang/tukar lagi |

### 27. Pemesanan Flash Sale (ronde ke-18, sudah dimatikan)

> Sudah digantikan kanal `/api/seckill` ronde ke-24 (cabang promosi store() tinggal belanja bersama), lihat「43. Flash Sale」.

| Fitur | Keterangan |
|------|------|
| Antarmuka | POST /api/order kirim promotion_id (tipe flash_sale): harga flash sale = round(total × (100 − discount_percent)/100, 2), selaras standar harga flash sale PromotionController |
| Validasi | whitelist tipe [group_buy, flash_sale] (lainnya 422); aktivitas berlangsung; pemanggil adalah peserta; layanan pesanan cocok aktivitas; sold out participants_count ≥ max_people 422「Sudah habis」; larang tumpuk kupon/kartu kunjungan/poin 422 |
| Kedaluwarsa | pay() malas menilai isFlashSaleClosed (pola sama isGroupBuyClosed): flash sale kedaluwarsa → aktivitas set 0 + batalkan massal pesanan pending aktivitas tersebut + pesanan ini otomatis batalkan + lepaskan kunci teknisi 422 |

### 28. Pengingat Layanan + Pengingat Kedaluwarsa (ronde ke-18)

| Fitur | Keterangan |
|------|------|
| Pengingat sebelum layanan mulai | ServiceReminderTimer 60s memindai service_time ∈ [now+1h, now+1h+60s), status confirmed/serving, pesanan tipe appointment → notifikasi situs (type='service_reminder', termasuk layanan/teknisi/toko/waktu) + subscription message SCENE_REMINDER |
| Pengingat kedaluwarsa | ExpiryReminderTimer 6 jam memindai end_at ∈ (now, now+3d+6h]: kartu member aktif (type='card_expiry') + kupon available (type='coupon_expiry', whereHas kait definisi kupon end_at) + subscription message SCENE_EXPIRY |
| Idempoten | keduanya kursor id 100/batch + row lock verifikasi ulang dalam transaksi + cek duplikat notifikasi (kolom order_id catat id sumber/id pesanan sebagai kunci anti duplikat); subscription message sukses baru tulis push_sent_at, gagal retry ronde berikutnya |
| Degradasi | template tidak dikonfigurasi (WECHAT_SUBSCRIBE_TEMPLATE_REMINDER / _EXPIRY) otomatis degradasi hanya notifikasi situs |

### 29. Balasan Ulasan Teknisi (ronde ke-18)

| Fitur | Keterangan |
|------|------|
| Antarmuka | POST /api/technician/review/reply/{order_id} (middleware identitas teknisi): ulasan tidak ada/bukan sendiri seragam 404; sudah ada balasan 422 (tolak idempoten tanpa timpa); balasan kosong 422 |
| Setelah balas | notifikasi situs pengguna (type='review_reply', non-blocking try/catch + Log) |
| Data | erik_order_review idempoten tambah kolom replied_at (kolom reply sudah ada saat create table); list/show ulasan sisi admin lewat decorate()->toArray() tampilkan reply/replied_at |

### 30. Notifikasi Top-up Masuk (ronde ke-18)

| Fitur | Keterangan |
|------|------|
| Antarmuka | callback top-up WeChat (nomor slip prefiks R) handleRechargeNotify dalam transaksi: setelah WalletTxn tulis notifikasi situs type='wallet_recharge',「Anda berhasil top-up ¥X.XX」(jumlah yuan, number_format 2 digit) |
| Idempoten | pakai kembali idempoten callback yang ada (baris slip top-up lockForUpdate + verifikasi ulang status, hanya pertama pending→paid sampai notifikasi); notifikasi dan perubahan status commit atomik transaksi sama, tanpa celah crash; gagal verifikasi tanda tangan/slip tidak ada/jumlah tidak cocok tidak tulis notifikasi |
| Toleransi | tulis notifikasi try/catch, gagal hanya catat log warning tidak blokir alur utama |

### 31. Transfer Saldo (ronde ke-19)

| Fitur | Keterangan |
|------|------|
| Antarmuka | POST /api/wallet/transfer: decode hashid penerima + keberadaan 404, transfer ke diri sendiri 422, jumlah 0.01-1000/per transaksi 422 (perbandingan DECIMAL larang float), saldo kurang 422, akumulasi harian 5000 yuan 422 |
| Bersamaan/idempoten | kunci Redis NX wallet_transfer:{from} 30s serialkan pengirim; dalam transaksi lockForUpdate baris dompet kedua pihak urutan ascending user_id (urutan tetap cegah deadlock); client_token sukses SETNX 24h cegah submit duplikat (permintaan gagal tidak tulis token bisa retry) |
| Pencatatan | potong pengirim + tambah penerima + WalletTxn dua transaksi (transfer_out/transfer_in termasuk snapshot balance_after) + catatan transfer completed + notifikasi situs penerima type='balance_received' (gagal hanya catat log) |
| Catatan | GET /api/wallet/transfers (direction=out/in paginasi) + GET /transfers/{id} (hanya kedua pihak terlihat 404) |

### 32. Transfer Poin (ronde ke-19)

| Fitur | Keterangan |
|------|------|
| Antarmuka | POST /api/user/points/transfer: penerima ada 404, ke diri sendiri 422, jumlah poin 1-10000 422, saldo SUM agregat kurang 422, batas akumulasi harian 10000 422 |
| Bersamaan/idempoten | kunci Redis NX points_transfer:{user} 30s; dalam transaksi lockForUpdate transaksi terakhir kedua pihak (ascending user_id cegah deadlock transfer timbal balik) + verifikasi ulang dalam kunci saldo/batas/penerima |
| Standar transaksi | pengirim type=consume source=points_transfer negatif (balance=snapshot sebelumnya-berkurang, sama standar points_offset/exchange); penerima type=earn source=points_transfer positif termasuk expires_at (PointsExpiryTimer bisa kedaluwarsa normal); dalam transaksi tulis catatan transfer, setelah commit notifikasi situs penerima type='points_received' |
| Catatan | GET /api/user/points/transfers (direction=sent/received paginasi, nama panggilan lawan) |

### 33. Ulasan Susulan + Pelengkapan Rute Submit (ronde ke-19)

| Fitur | Keterangan |
|------|------|
| Ulasan susulan | POST /api/order/review/{order_id}/append: ulasan tidak ada/bukan sendiri seragam 404, non-completed 422, ulasan susulan duplikat 422 (append_content/append_at salah satu tidak kosong tolak), konten kosong 422; sukses tulis append_content/append_images(JSON)/append_at + notifikasi situs teknisi type='review_append' |
| Submit ulasan | lengkapi registrasi POST /api/order/review/{order_id} (ReviewController::store aslinya tanpa rute tidak dapat diakses); sekalian perbaiki TypeError laten: findByOrderId menerima int melanggar signature string (bandingkan konversi (string) append), registrasi lengkap langsung memperlihatkan 500 saat dipanggil |
| Data | erik_order_review tambah tiga kolom append_content TEXT/append_images JSON/append_at DATETIME (migrasi idempoten); respons tampilkan kolom append |

### 34. Pelacakan Logistik Sisi Pengguna (ronde ke-19)

| Fitur | Keterangan |
|------|------|
| Antarmuka | GET /api/order/logistics/{id}: hanya pesanan product sendiri yang bisa dilihat (bukan sendiri/bukan barang/belum kirim seragam 404) |
| Data | baca order.remark JSON (shipping_company/tracking_no/shipped_at, ditulis saat pengiriman oleh admin MallOrderController::ship()); parseShippingInfo/parseReceiver dua parsing fallback format lama |
| Deidentifikasi | nomor ponsel penerima maskPhone (138****5678), cegah kebocoran |

### 35. Pengaturan Preferensi Pesan (ronde ke-19)

| Fitur | Keterangan |
|------|------|
| Data | tabel erik_user_notify_setting (kunci unik gabungan user_id+type uk_user_type, baris default kosong=default nyala); 5 jenis: service_reminder pengingat layanan / card_expiry pengingat kedaluwarsa (kartu+kupon payung seragam) / points_expiry kedaluwarsa poin / marketing pemasaran (cadangan) / system sistem (tidak bisa dimatikan, PUT paksa 1) |
| Antarmuka | GET /api/user/notify-settings kembalikan 5 jenis saklar lengkap; PUT upsert massal tidak hasilkan baris duplikat |
| Gerbang | NotificationReminderService::notifySettingEnabled pasang 3 proses timer (ServiceReminderTimer/ExpiryReminderTimer kartu+kupon/PointsExpiryTimer, timer langsung tulis tabel erik_notification tidak lewat jalur tulis layanan sehingga masing-masing tambah gerbang sama) + event subscription (pemetaan skenario sendSubscribeForOrderEvent/Notification PAY/REFUND/VERIFIED/RESCHEDULE→system selalu kirim, REMINDER→service_reminder, EXPIRY→card_expiry); jenis dimatikan notifikasi situs dan subscription message sama-sama dilewati |

---

## II. Panel Admin (PC Web)

Aplikasi satu halaman Flutter Web, total 21 halaman: dashboard/pengguna/peran/konfigurasi/log/verifikasi/jadwal/layanan/teknisi/pesanan/kupon/member/kartu kunjungan/pengumuman/FAQ/penarikan dana/ulasan/laporan/pusat pribadi/workbench toko.

### 1. Dashboard Beranda

- Statistik real-time: jumlah pengguna/jumlah pesanan/jumlah teknisi/jumlah pesanan layanan
- Grafik garis: tren jumlah pesanan/tren jumlah/akun baru/aktivitas
- Navigasi cepat: tombol modul menunggu diproses
- Pesan situs: notifikasi pesanan baru/notifikasi refund

### 2. Manajemen Teknisi

- Daftar teknisi: pencarian UID/nomor ponsel/nama/asal pendaftaran/waktu registrasi
- Tampilan daftar: nomor/UID/nomor ponsel/nama panggilan/perekomendasi/status/jumlah murid/kinerja/status akun/waktu registrasi/login terakhir/asal
- Operasi: ekspor/ubah atasan/lihat bawahan/ubah kata sandi nomor ponsel/manajemen jadwal/pengaturan item layanan teknis/lihat progres kursus
- Tambah: nama/jenis kelamin/nomor ponsel/KTP/foto KTP
- Audit permohonan pendaftaran

### 3. Manajemen Pengguna

- Daftar member: nama/nomor ponsel/avatar/level/jumlah konsumsi
- Pencarian: UID/nomor ponsel/nama panggilan/waktu registrasi
- Operasi: detail/ubah atasan/lihat bawahan/ubah kata sandi nomor ponsel/atur level member

### 4. Manajemen Toko

- Daftar toko: aktif/nonaktif/hapus
- Tambah toko: nama/alamat/koordinat/telepon/jam buka/gambar

### 5. Manajemen Layanan

- Daftar layanan: pencarian nama/kategori; nomor/nama/tipe/diskon/harga minimum/volume penjualan/sampul/urutan/status/waktu
- Operasi: tambah/ubah/hapus/desain kartu
- Daftar produk: tipe/nama/diskon/harga minimum/volume penjualan/stok/sampul/urutan/status/waktu

### 6. Manajemen Mall

- Pesanan mall: detail/kirim/logistik/cetak
- Pesanan purna jual: lihat/audit/cetak
- Manajemen ulasan: lihat/audit (show/hide)/hapus (ReviewController index/show/audit/destroy)
- Transaksi pembayaran
- Statistik penjualan

### 7. Manajemen Pesanan

- Pesanan belum dipakai: pencarian multi kondisi
- Operasi: detail/batalkan platform/konfirmasi selesai

### 8. Aktivitas Kupon

- Daftar: nomor/gambar/tipe/nama/tayang/tidak tayang/total/sisa/admin/waktu/tanggal akhir
- Operasi: tambah/ubah/hapus

### 9. Manajemen Keuangan

- Bagi hasil pesanan: cari/detail
- Penarikan teknisi: audit WithdrawalController; jumlah ≥500 persetujuan dua tingkat (manajer toko store_approved_at → keuangan finance_approved_at); state machine pending→approved→completed (rejected/failed)
- Pengaturan komisi: ubah rasio komisi/periode penyelesaian/hadiah hukuman/saldo
- Transaksi pemasukan pengeluaran
- Manajemen akun penarikan
- Konfigurasi batas penarikan

### 10. Manajemen Konten

- CRUD banner
- Pengaturan tentang kami
- Audit postingan momen
- CRUD FAQ
- Penanganan masukan
- CRUD pengumuman platform

### 11. Pengaturan

- Edit perjanjian platform (perjanjian pengguna/perjanjian privasi/perjanjian layanan)
- Pengaturan komisi seragam teknisi
- Template pesan sistem (termasuk konfigurasi template subscription message Mini Program, tidak dikonfigurasi otomatis degradasi notifikasi situs)
- Manajemen izin sub-akun (manajer toko bisa terbit kupon + jadwal)

### 12. Fitur Ekstensi

- Desain kartu: kombinasi item+produk/biaya manual/pengaturan komisi
- Pemantauan sistem: papan real-time CPU/memori/disk/Redis/MySQL/antrean
- Blacklist IP: visualisasi catatan serangan security-php + blokir manual
- Backup basis data: backup/unduh/pulihkan lewat antarmuka Web
- Profil pelanggan: tampilan 360/preferensi konsumsi/pemasaran berlapis
- Push massal: template message/pengiriman massal terbagi
- Alur audit refund: persetujuan dua tingkat (manajer toko→keuangan)
- Level teknisi: penilaian otomatis junior/senior/expert
- Tugas terjadwal: pembatalan otomatis/penyelesaian/penanganan kedaluwarsa
- Konfigurasi SMS: manajemen multi kanal Alibaba Cloud/Tencent Cloud
- Konfigurasi penyimpanan: lokal/OSS/COS/CDN
- Laporan ditingkatkan: kolom kustom/laporan email terjadwal
- Ekspor jadwal: ekspor Excel catatan janji temu/daftar kehadiran
- Batasan jenis kelamin teknisi: kontrol jenis kelamin item tertentu
- Pelatihan teknisi: manajemen kursus/pelacakan progres belajar
- Akun manajer toko: isolasi data store_id + izin khusus

### 13. Laporan Data (ronde ke-7)

- ReportController 3 endpoint: statistik pesanan / kinerja teknisi / distribusi toko
- Cache Redis svc:admin_report:{type}:{start}:{end}, TTL 300

### 14. Manajemen Kartu Member (ronde ke-10)

- Kolom level member erik_user.member_level (migrasi 000008)
- MemberCardController CRUD lengkap (izin 365-369): GET/POST/PUT/DELETE /admin/member-cards
- Halaman manajemen definisi kartu member Flutter

### 15. Manajemen Purna Jual (ronde ke-14)

- Tabel erik_order_aftersale (migrasi 000009): type=refund/exchange, status=pending/approved/rejected/completed
- AftersaleController: GET /admin/aftersales (paginasi + filter status/uid/order_no) + POST /admin/aftersales/{id}/review (approve/reject+remark)
- Halaman manajemen purna jual Flutter (daftar + dialog audit, izin 370/371), tata letak sudah terdaftar

### 16. Workbench Manajer Toko (ronde ke-15)

- service /api/store-manager: overview (pesanan hari ini/pendapatan/berlangsung/jumlah teknisi/jumlah verifikasi) + orders (paginasi + filter status) + technicians (termasuk jadwal hari ini) + revenue (agregat 7 hari terakhir), requireStoreId() paksa isolasi store_id (tanpa toko 403)
- admin StoreController::workbenchOverview (GET /admin/stores/workbench-overview?store_id=, standar sama service) + daftar pesanan AppointmentOrderController filter store_id (decode hashid)
- Halaman workbench toko Flutter: dropdown toko + filter status + 5 kartu ringkasan + DataTable pesanan + paginasi (izin 372)

### 17. Barang Tukar Poin (ronde ke-16)

- PointsExchangeGoodsController: GET/POST/PUT/DELETE /admin/points-exchange-goods + POST {id}/toggle-status (tayang/tidak tayang) + GET {id}/exchanges (catatan tukar, termasuk nomor ponsel + parsing JSON result)
- Migrasi 000012 (dua tabel) + 000013 (izin 373-378) sudah diterapkan

### 18. Catatan Komisi Referral (ronde ke-16)

- ReferralRewardController: GET /admin/referral-rewards (hanya catatan rewarded_at tidak kosong, paginasi + filter keyword nama panggilan perekomendasi/ter-referral atau nomor ponsel, encode hashid, izin 379)

### 19. Penilaian Otomatis Level Teknisi (ronde ke-17)

- TierRatingService::evaluate(technicianId, allowDowngrade=false): statistik real-time jumlah pesanan erik_order completed + rata-rata erik_order_review (pembulatan 1 desimal) tulis ulang profile.order_count/rating, cocokkan dari tinggi ke rendah sesuai erik_technician_tier_config (min_orders/min_rating), tanpa cocok jatuh ke level terendah
- Aturan naik/turun: hanya naik tidak turun (level terikat rasio komisi dan koefisien harga, turun otomatis mempengaruhi pendapatan teknisi mudah memicu sengketa, penurunan ditangani manual admin sebagai fallback); allowDowngrade=true (skenario penilaian ulang manual backend) baru eksekusi turun, turun juga tulis log + notifikasi
- Idempoten: level yang seharusnya cocok profile.tier_id hanya sinkron statistik, tidak tulis log tidak kirim notifikasi
- Log: perubahan tulis erik_technician_tier_log (id/technician_id/old_tier_id/new_tier_id/reason/created_at) + notifikasi situs (type='tier')
- Titik pemicu: WorkController::complete / penulisan ulasan ReviewController / penilaian malas saat lihat profil ProfileController
- Sisi admin: TechnicianTierController pertahankan kemampuan konfigurasi manual; GET /admin/technician-tiers/logs paginasi lihat log perubahan (join nama teknisi dan nama level lama baru, encode hashid ID, izin 380)

### 20. Lihat Balasan Ulasan (ronde ke-18)

- ReviewController tambah reply(): GET /admin/reviews/{id}/reply detail balasan (decodeId → find → 404 → output decorate, belum dibalas reply='', reply/replied_at tampil lewat toArray)
- Rute adalah rute statis (terletak sebelum audit, didefinisikan mendahului resource); seed izin id 381 (slug 'get.admin/reviews/{id}/reply', type 3, asosiasi idempoten peran super admin)
- Titik izin: 381

### 21. Kalender Bulanan Janji Temu (ronde ke-20)

- CalendarController tampilan bulan/hari: GET /api/calendar/technician/{id} (tampilan bulan) + /day (tampilan hari)
- Sumber data: time_slots JSON technician_schedule diperluas per hari kerja ke slot jam, slot sudah dijanjikan hari itu erik_order dikecualikan (status ∈ pending/paid/confirmed/serving), sisa slot bisa dijanjikan dioutput
- Kegunaan: visualisasi pemilihan waktu jadwal toko, frontend scroll horizontal per hari + pilih titik grid waktu

### 22. Level Pertumbuhan Pengguna (ronde ke-20)

- erik_user_growth (transaksi) + erik_growth_level (seed tingkatan 5 level: Bronze0/Perak100/Emas500/Platinum2000/Berlian5000)
- Titik masuk nilai pertumbuhan: check-in +10 (CheckInController); submit ulasan +20 (ReviewController::store, ulasan susulan tidak masuk); konsumsi floor(paid) setiap 1 yuan 1 poin (WechatPayService::markOrderPaid, pakai kembali verifikasi ulang status pembayaran yang ada natural idempoten, callback duplikat tidak masuk ulang)
- Antarmuka: GET /api/growth (ringkasan level saat ini: balance/level/selisih tingkatan berikutnya); GET /api/growth/records (transaksi paginasi); GET /api/growth/levels (daftar tingkatan publik, tidak perlu login)
- Strategi gagal: titik masuk mana pun try/catch catat log, tidak mempengaruhi alur utama

### 23. Faktur Elektronik (ronde ke-20)

- erik_invoice: uk_order_type(order_id,order_type) cegah pengajuan duplikat pesanan sama (pengajuan duplikat 422, termasuk tangkap MySQL 1062 fallback); idx_user_created/idx_status
- Sisi pengguna: POST /api/invoices (pengajuan, jumlah/judul diambil server dari pesanan, tidak bisa diubah); GET /api/invoices (daftar); GET /api/invoices/{id} (detail)
- Sisi admin: InvoiceController issue (terbit faktur: tulis invoice_no + status=issued + issued_at) / reject (tolak: status=rejected + reject_reason), izin 382 daftar/383 terbit/384 tolak
- State machine: pending → issued / rejected

### 24. Tiket Layanan Pelanggan (ronde ke-20)

- erik_ticket: pengguna submit tiket (title/content), backend balas tambah (reply_content/replied_at), pengguna bisa tutup (closed_at)
- Sisi pengguna: POST /api/tickets (submit); GET /api/tickets (daftar); GET /api/tickets/{id} (detail, hanya sendiri); POST /api/tickets/{id}/close (tutup)
- Sisi admin: TicketController index (daftar) / reply (balas), rute statis didefinisikan mendahului resource hindari shadow {id}; izin 385 balas tiket/387 lihat daftar tiket
- State machine: open → replied (setelah balas kembali open bisa balas lagi) / closed

### 25. Distribusi Multi Level - Komisi Referral Level Dua (ronde ke-20)

- ReferralRewardService::payLevel2Reward(paidAmount, orderId): setelah pembayaran pesanan sukses, cari perekomendasi perekomendasi level satu (hubungan referral level dua), beri paid×level2_rate (konfigurasi sistem referral.level2_rate, default 0.02)
- Idempoten: row lock dalam transaksi + kunci unik uk_order_referred(order_id, level2_user_id), callback pembayaran duplikat/bersamaan tidak beri ulang; try/catch gagal hanya catat log tidak mempengaruhi alur pembayaran utama
- Pencatatan: WalletTxn type='referral_level2' (konstanta TYPE_REFERRAL_LEVEL2) + akumulasi saldo dompet
- Sisi admin: ReferralLevel2Controller index catatan paginasi (izin 386), join nama panggilan dua level pengguna

### 26. Realisasi Hak Level Pertumbuhan (ronde ke-21)

- GrowthLevel.benefits JSON shell realisasi: seed migrasi 5 tingkatan (Bronze {"discount_rate":1.0,"points_multiplier":1.0}, Perak 0.98/1.1, Emas 0.95/1.2, Platinum 0.92/1.3, Berlian 0.9/1.5)
- Diskon level: OrderController::store applyGrowthDiscount() — hanya pesanan standar (promotion_id kosong, belanja bersama/flash sale larang tumpuk); urutan: jumlah terutang setelah diskon kupon/kartu kunjungan × discount_rate; jumlah diskon masuk discount_amount, catatan pesanan tambah「Diskon level: Perak 9,8折, diskon ¥2.00」dapat ditelusuri; proteksi harga minimum: bayar aktual setelah diskon ≥0.01 yuan (basis sen ≥100), kurang maka diskon dipotong jadi 0
- Pengali poin: WechatPayService::markOrderPaid nilai pertumbuhan dari floor(paid) ubah floor(paid × points_multiplier), pengali diambil sesuai level pada titik pembayaran (akumulasi sebelum masuk, pesanan ini tidak naik level); titik pemasangan try/catch R20 dipertahankan lengkap
- Reuse kueri: GrowthLevel::levelForGrowth() ambil tingkatan sesuai nilai pertumbuhan kumulatif, dipakai ulang saat order/bayar; GET /api/growth sudah mengembalikan benefits dan next_gap (implementasi R20, tidak perlu ubah)

### 27. Manajemen Judul Faktur (ronde ke-21)

- erik_invoice_title (uk_user_title(user_id, title_type, invoice_title) cegah duplikat + idx_user_default)
- Antarmuka: POST /api/invoice-titles (simpan, company wajib tax_no, duplikat 422); GET (daftar, default di atas); PUT /{id} (edit, hanya sendiri); DELETE /{id} (hapus, hanya sendiri); POST /{id}/default (set default, transaksi nolkan baris lain pengguna sama)
- Aturan default: penyimpanan pertama otomatis default; hapus default otomatis tunjuk baris paling awal
- Kaitan pengajuan: InvoiceController::store opsional title_id parse judul bawa masuk invoice_title/tax_no/title_type, tanpa title_id pertahankan jalur isi manual asli; logika anti duplikat uk_order_type tidak diubah

### 28. Kepuasan Tiket (ronde ke-21)

- erik_ticket tambah rating TINYINT NULL + rated_at DATETIME NULL (migrasi 000303)
- Skor saat tutup: TicketController::close() dukung rating opsional 1-5 (validasi bilangan bulat filter_var, di luar batas/bukan bilangan bulat 422; diberikan tulis rating+rated_at, tidak diberikan pertahankan NULL kompatibel klien lama; aturan hanya tutup tiket open dipertahankan)
- Statistik backend: GET /admin/tickets/satisfaction (rute statis didefinisikan mendahului resource hindari shadow {id}) kembalikan total/rated_count/unrated_count/average (1 desimal)/distribution (jumlah masing 1-5 bintang, bintang kurang isi 0); izin 388

### 29. Audit Foto Ulasan (ronde ke-21)

- admin ReviewAuditController (baru, tidak mengubah ReviewController yang ada): GET /admin/review-audit daftar ulasan bergambar (filter JSON_LENGTH(images)>0 + leftJoin nama panggilan pengguna dan nama teknisi + filter status + encode hashid); POST /{id}/hide sembunyikan; POST /{id}/restore pulihkan
- State machine: hide hanya visible bisa disembunyikan, restore hanya hidden bisa dipulihkan (dua arah 422); OrderReview status sistem bilangan bulat (STATUS_HIDDEN=0/STATUS_VISIBLE=1)
- Rantai berlaku: daftar ulasan teknisi sisi pengguna sudah difilter status → setelah disembunyikan otomatis tidak terlihat
- Izin: 389 daftar / 390 sembunyikan / 391 pulihkan

### 30. Jejak Penjelajahan Pengguna (ronde ke-21)

- erik_browse_history (uk_user_item(user_id, item_id) unik, jelajah duplikat hanya refresh viewed_at tidak insert ulang; idx_user_viewed urutkan)
- Titik pemasangan catatan: ServiceController::detail() setelah sukses catat (try/catch + Log::warning tidak mempengaruhi alur utama; rute publik tanpa JWT, user_id kosong lewati anonim)
- Antarmuka: GET /api/browse-history (join erik_service nama/sampul/harga/harga asli, viewed_at urutan turun, per_page default 15 batas atas 50, item_id hashid); DELETE /{item_id} (hanya sendiri, ilegal/punya orang lain 404); DELETE / (kosongkan hanya sendiri)

### 31. Pemasaran Potongan (ronde ke-22)

- erik_full_reduction_activity (threshold/reduction/title/status/start_at/end_at + idx_status_status_time)
- Tumpuk saat order: hanya pesanan standar (belanja bersama/flash sale lewati), ambang dinilai dari jumlah terutang setelah potongan kupon/kartu kunjungan, urutan **kupon/kartu kunjungan → potongan → diskon level**; ambil aktivitas pengurangan terbesar; jumlah diskon masuk discount_amount + catatan「Potongan: beli X potong Y」; batas bawah bayar aktual setelah potongan 0.01 yuan (basis sen)
- Sisi pengguna GET /api/full-reduction-activities (publik, yang berlangsung urut pengurangan turun)
- admin FullReductionController: CRUD + toggle-status tayang/tidak tayang (destroy dengan confirmPassword)
- Izin: 396 daftar / 397 tambah / 398 edit / 399 tayang/tidak tayang / 400 hapus (satu catatan izin hanya sesuai satu slug method.path, 5 rute pecah 5 catatan)

### 32. Ekspor ICS Janji Temu Saya (ronde ke-22)

- IcsController GET /api/order/ics: ekspor pesanan 90 hari pending/paid/confirmed/serving ke iCal (RFC5545), hanya sendiri
- VEVENT: UID=ID pesanan, DTSTAMP(UTC), TZID=Asia/Shanghai, durasi default 1 jam, ringkasan「Janji temu: nama layanan」(hilang degradasi「Janji temu」), keterangan teknisi/toko/alamat (hilang lewati), LOCATION; escape teks (\, \; \\ \n) + lipat baris 75 byte
- Tanpa pesanan kembalikan kalender kosong legal (`BEGIN:VCALENDAR` kerangka)

### 33. Kehadiran Teknisi (ronde ke-22)

- erik_technician_attendance (date/check_in_at/check_out_at/status + indeks unik uk_technician_date cegah check-in duplikat bersamaan)
- Sisi teknisi (TechnicianAuth): check-in hari sama duplikat 422; check-out belum masuk/sudah pulang 422 + row lock; >10:00 tandai terlambat; GET daftar bulan ini + hari hadir/jam kerja total/jam kerja rata-rata (?month=YYYY-MM ilegal 422)
- admin: GET /admin/attendance (filter date+nama teknisi, join real_name, hashid) + /stats (statistik dikelompokkan per teknisi)
- Izin: 392 daftar / 393 statistik

### 34. Layanan Push APP (ronde ke-22)

- AppPushService (config group=push: enabled default 0 / provider jpush/getui/placeholder): tidak diaktifkan degradasi diam hanya log; diaktifkan bangun struktur platform/judul/konten/payload catat Log + tulis erik_push_log (status=sent); integrasi SDK vendor tinggal TODO (tanpa kredensial tidak kirim aktual)
- Terhubung 5 titik event: pembayaran sukses (WechatPayService::markOrderPaid), refund otomatis (autoRefundCancelledOrder), refund manual (doRefund/refundToBalance), kompensasi refund (completeOneRefundCompensation), pengingat mulai layanan (ServiceReminderTimer); semua try/catch tidak blokir alur utama
- erik_push_log (user_id/title/content/payload JSON/status/provider + idx_user)

### 35. Bagi Hasil Resmi WeChat (ronde ke-22)

- WechatProfitSharingService (config group=profit_sharing: enabled/receiver_ratio, kredensial pakai ulang wechat_pay): tidak diaktifkan degradasi disabled hanya log tidak tulis DB; diaktifkan→validasi jumlah (>0 dan ≤paid, bayar aktual×0.7 default) + idempoten (pesanan sama pending/success lewati) → tulis catatan pending → bangun struktur「request single profit sharing」(tanpa kredensial tidak eksekusi HTTP, isi permintaan catat log, catatan tetap pending); doRequest privat isolasi HTTP bisa diuji
- WechatPayService::markOrderPaid setelah submit pasang requestSharing (try/catch gagal hanya log)
- erik_profit_sharing (uk_sharing_no unik + idx_order); admin GET /admin/profit-sharing daftar (join nomor pesanan/nama panggilan teknisi, filter status/nomor pesanan/nama teknisi)
- Izin: 394

### 36. Kepatuhan Privasi (ronde ke-22)

- GET /api/privacy/data: ekspor data (kelompok personal/orders/points/wallet_txns/reviews/addresses/invoices; log hanya catat nomor ponsel deidentifikasi + jumlah)
- Siklus tertutup penghapusan: close-request (saldo bukan 0 / pesanan belum selesai / tiket berlangsung 422 → close_status=1) → close-cancel (1→0) → close-confirm (genap 72 jam → close_status=2 + close_at + phone/nickname anonimisasi user{id} + status=0)
- erik_user tambah close_status/close_requested_at/close_at (migrasi ALTER idempoten); AuthController login/loginByCode untuk close_status=2 kembalikan 403「Akun telah dihapus」

### 37. Arsip Kesehatan Pengguna (ronde ke-23)

- GET/PUT/DELETE /api/health-profile: satu orang satu (indeks unik uk_user), upsert hanya perbarui kolom yang diberikan
- allergies/health_notes batas atas 500 karakter, preferred_technician_id validasi keberadaan, respons encode hashid
- Migrasi 000504_user_health_profile; HealthProfileTest 6 tests

### 38. Kata Sandi Pembayaran Dompet (ronde ke-23)

- POST /api/wallet/pay-password/{set,verify,check}: validasi 6 digit angka, simpan password_hash + pay_password_set_at
- Sudah diatur ubah perlu kata sandi lama 422; verify hanya validasi tidak tulis DB; check kembalikan apakah sudah diatur
- Migrasi 000502 (INFORMATION_SCHEMA ALTER dua kolom idempoten); WalletPayPasswordTest 7 tests

### 39. Jadwal Massal Teknisi (ronde ke-23)

- POST /api/technician/schedule/batch: rentang tanggal ≤7 hari + filter weekdays, hari sudah ada jadwal dilewati
- Pengaturan satu entri juga aktifkan deteksi tumpang tindih slot waktu (422「Konflik waktu dengan jadwal yang ada: HH:MM-HH:MM」)
- ScheduleConflictTest 5 tests

### 40. Garis Waktu Status Pesanan (ronde ke-23)

- GET /api/order/{id}/timeline: hanya sendiri bisa lihat (punya orang lain 404), urutan turun kembalikan; detail pesanan admin gabung ke array timeline
- OrderStatusLog::record() titik tanam statis 8 jenis perubahan: submit/pembayaran/batal/refund pengajuan/refund lulus/mulai layanan/selesai layanan/batal otomatis timeout/operasi backend (operator=admin)
- markOrderPaid callback pembayaran adalah satu titik konsumsi; record() internal try/catch + Log::warning tidak pernah blokir alur utama
- Migrasi 000501_order_status_log; OrderTimelineTest 4 tests

### 41. Roda Keberuntungan Poin (ronde ke-23)

- GET /api/wheel/prizes (sembunyikan weight/stock); POST /api/wheel/spin: Redis NX + row lock cegah bersamaan, random_int undian berbobot, client_token idempoten
- Penetapan hadiah: poin→transaksi earn (termasuk waktu kedaluwarsa, bisa kedaluwarsa normal oleh PointsExpiryTimer), saldo→lockForUpdate, kupon→pending terbit manual, tanpa hadiah→lose
- GET /api/wheel/records catatan saya paginasi; admin /admin/lucky-wheel CRUD + tayang/tidak tayang + catatan (izin 401-406)
- Migrasi 000503 (erik_lucky_wheel + erik_wheel_record + seed demo w60/w40) + 000505 (seed izin); LuckyWheelTest admin 3 + service 6 tests

### 42. Mode Tamu (ronde ke-24)

- GET /api/guest/{home,services,services/{id},stores,technicians}: pintu masuk jelajah tanpa login tanpa otentikasi (hanya middleware ApiVersion)
- home agregat banner/pengumuman/kategori layanan/layanan populer, cache Redis svc:guest:home 300s; services dukung filter kategori + urutkan newest/sales/price (page/per_page≤50); technicians hanya lolos audit, bisa filter service_id, rating urutan turun
- GuestControllerTest tercakup

### 43. Flash Sale (ronde ke-24)

- erik_seckill_activity (name/service_id/seckill_price/original_price/stock/start_at/end_at/status); jumlah terjual = jumlah pesanan erik_order.seckill_id
- GET /api/seckill (status=1 + jendela waktu)、/{id} (state=not_started/ongoing/ended)、POST /{id}/buy: client_token (8-64 karakter, SETNX 24 jam) idempoten + Redis NX 30s cegah bersamaan + validasi aktivitas (mulai 2026-08-26 tidak lagi pre-deduct stok)
- Pemesanan injeksi seckill_id pakai ulang OrderController::store; stok seragam dipotong row lock dalam transaksi store() (panggil langsung /api/order bawa seckill_id juga potong stok), harga flash sale = seckill_price (berdasarkan DB), tidak tumpuk kupon/poin/kartu member; pembatalan pesanan tidak isi ulang stok; kanal FLASH_SALE promosi lama sudah dihapus (cabang promosi store() tinggal belanja bersama, PromotionController index filter flash_sale, show/join 400), flash sale hanya lewat kanal ini
- admin /admin/seckill CRUD + tayang/tidak tayang + daftar pesanan (izin 407-411、420); migrasi 000606 seed izin; SeckillTest service + admin

### 44. Manajemen Versi APP & Deteksi Pembaruan (ronde ke-24)

- erik_app_version (platform/version_code/version_name/force_update/changelog/download_url/status)
- GET /api/app/version?platform=android|ios deteksi pembaruan publik (platform ilegal 422; status=1 ambil terbaru; tanpa ada objek kosong)
- admin /admin/versions CRUD (izin 416-419); migrasi 000609 seed izin; VersionTest service + admin

### 45. Bonus Pelanggan Berulang (ronde ke-24)

- ReturnCustomerRewardService: pengguna konsumsi kedua (pesanan selesai) ke teknisi sama dalam 30 hari beri bonus teknisi = bayar aktual paid_amount × ratio (system_config group=return_customer, ratio default 0.05, saklar enabled, nilai ilegal jatuh default)
- Tulis erik_technician_earnings (type=return_customer, status=pending) pakai ulang rantai penyelesaian komisi, ringkasan earnings sisi teknisi otomatis termasuk; idempoten order_id+type sama; dipanggil dalam transaksi row lock WorkController::complete
- admin /admin/return-customer/config (GET/PUT) + /rewards (?keyword nama teknisi/nomor pesanan/nama panggilan pengguna) (izin 412-414); migrasi 000607 seed izin; ReturnCustomerRewardServiceTest

### 46. Ekspor Jadwal (ronde ke-24)

- GET /admin/technician-schedule/export: CSV (UTF-8 BOM, Excel buka langsung), nama file schedules_{YmdHis}.csv
- start_date/end_date wajib (YYYY-MM-DD, ilegal 422) dan rentang ≤31 hari; technician_id opsional (hashid, ilegal 422)
- Kolom: ID teknisi/nama teknisi/tanggal/detail slot waktu (time_slots JSON diurai menjadi "09:00-12:00, 14:00-18:00")
- Izin: 415; migrasi 000608 seed izin; ScheduleExportTest tercakup
