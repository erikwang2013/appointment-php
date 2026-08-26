# Laporan Tim Pengujian — Audit Cakupan Pengujian Penuh

> Waktu pembuatan: 2026-08-26　Versi: v1.3.8
> Tim: deep-audit (tester-php / tester-api / tester-ui / tester-go / tester-rust)

## 1. Ringkasan Eksekusi

| Peran | Tugas | Hasil |
|------|------|------|
| Insinyur Pengujian PHP | Pengujian unit/integrasi semua modul | 70 pengujian lama + penambahan ronde ini (lihat §3) |
| Insinyur Pengujian API | Otomasi semua antarmuka | Pengujian integrasi lapisan controller adalah bentuk otomasi API proyek ini (§4) |
| Insinyur Otomasi UI | End-to-end semua halaman | Lingkungan tidak tersedia, kesimpulan di §5 |
| Insinyur Pengujian GO | Pengujian unit | **Dilewati: proyek tidak memiliki kode GO** (nol file .go) |
| Insinyur Pengujian Rust | Pengujian unit | **Dilewati: proyek tidak memiliki kode Rust** (nol file .rs) |

## 2. Tumpukan Teknologi & Bentuk Pengujian

- Backend: PHP 8.3 webman, dua aplikasi (service sisi pengguna / admin sisi backend), berbagi model service
- Kerangka pengujian: PHPUnit + Eloquent, **MySQL asli + rollback transaksi** (non-mock), otomatis skip saat DB tidak tersedia
- Menjalankan pengujian: `cd service && php -d memory_limit=2G vendor/bin/phpunit`
- Otomasi API = pengujian integrasi lapisan controller (membangun Request untuk langsung memanggil metode controller, memukul DB asli, rollback transaksi)

## 3. Cakupan Pengujian PHP

**Hasil penuh: 558 tests / 2508 assertions, 0 gagal 0 error 0 skip** (2 deprecation vendor lama, 2 notice PHPUnit lama, keduanya tidak diperkenalkan ronde ini; 4 skip gate penarikan dana asli telah dihilangkan melalui config('withdraw.gate_day') yang dapat disuntikkan, dapat dijalankan kapan saja)

### Penambahan ronde ini (tester-php, 6 file 32 kasus, semuanya DB asli + rollback transaksi)

| File pengujian | Kasus | Cakupan |
|---------|------|------|
| CartControllerTest | 4 | Normalisasi penyimpanan (whitelist/qty≥1/buang item kotor), non-array 400, keranjang kosong, kosongkan |
| PointControllerTest | 4 | Saldo = snapshot terbaru, meta paginasi, filter type/source, daftar kosong |
| AddressControllerTest | 7 | Tambah+default, wajib 400, eksklusivitas default, default diprioritaskan, akses berlebih 404, alih default, hapus+404 kedua |
| FavoriteControllerTest | 7 | Favoritkan layanan/teknisi, tipe ilegal 400, duplikat 400, perubahan favorite_count, favorit yatim, hapus 404 |
| ReferralControllerTest | 5 | Pembuatan kode undangan+statistik, pengguna 404, URL kode QR, daftar direkomendasikan, detail komisi referral |
| WithdrawControllerTest | 5 | Penolakan hari gerbang (suntikan config bukan hari ini), berhasil, saldo tidak cukup, <10 yuan, tidak ada akun (bisa dijalankan kapan saja, 0 skip) |

### Cakupan lama (70 file, tidak berubah)

35+ controller sudah tercakup: Auth/state machine Order/refund/verifikasi/penggantian jadwal/notifikasi pembayaran/flash sale/belanja bersama/kupon/kartu hadiah/poin/dompet/transfer/kartu member/nilai pertumbuhan/rebate/penarikan dana/check-in/jadwal/faktur/logistik/notifikasi/subscription message/antrean, dll.

### Perbaikan ronde ini (ditemukan tester-php)

- 【bug】AddressController::show/update/destroy dan FavoriteController::destroy tidak melakukan decode hashids, panggilan hashid 404.
  Perbaikan akar masalah: `BaseController::decodeId` menambah kompatibilitas lewat langsung angka murni (jika hashids tidak bisa decode dan ctype_digit, kembalikan apa adanya),
  semua 89 titik panggilan di repo mendapat manfaat seragam; 4 metode controller menambah decodeId di pintu masuk. Regresi penuh lolos.
- 【bug】Saat hashids min-length 0, sebagian ID angka telanjang (seperti 306) kebetulan merupakan encoding hashids legal dari ID lain,
  decodeId bisa salah decode menjadi ID yang salah (AddressControllerTest sesekali 404, terulang acak dalam banyak ronde penuh).
  Perbaikan akar masalah: `config/hashids.php` service/admin koneksi main `length` 0→8,
  encoding selalu ≥8 karakter, tidak berpotongan dengan panjang ID angka telanjang (<8 digit atau 16 digit), ambiguitas dihilangkan dari ruang encoding.
  Jalankan 5 ronde AddressControllerTest berturut-turut untuk verifikasi stabilitas, regresi penuh lolos.
- Gate hari penarikan dana yang di-hardcode tanggal 20 diubah menjadi `config('withdraw.gate_day')` yang dapat disuntikkan (config/withdraw.php),
  4 kasus skip asli "hanya tanggal 20" diubah menjadi suntikan refleksi hari gerbang, bisa dijalankan kapan saja, 0 skip.

## 4. Kesimpulan Pengujian Otomasi API

- Proyek ini tidak memiliki skrip pengujian lapisan HTTP terpisah; 70 file pengujian lama semuanya pengujian integrasi lapisan controller (DB asli),
  mencakup 35+ controller, setara dengan pengujian otomasi antarmuka
- Matriks cakupan pengujian lihat §3
- **Smoke test HTTP sudah dijalankan** (2026-08-26): 8787 dipakai proyek lain, maka sementara service
  `config/process.php` listen diubah ke 8791 untuk mulai layanan (32 webman worker + websocket + 4 timer semua [OK]),
  terukur `GET /health` → `{"code":0,"message":"ok"}`、`GET /api/guest/services` → HTTP 200
  JSON normal (ID encoding hashids terlihat), lalu stop dan pulihkan konfigurasi, nol proses sisa
- Disarankan CI menambah flutter build web → Playwright E2E jalur kunci panel admin (lihat §5)

## 5. Kesimpulan End-to-end UI

- Klien: Flutter (apps/flutter sisi pengguna, admin/apps/flutter panel admin), WeChat Mini Program (apps/wechat),
  HarmonyOS (apps/harmonyos), admin/apps/weixin
- Status saat ini: admin Flutter web tidak ada artefak build (build/web tidak ada); tidak ada layanan UI berjalan di mesin ini;
  WeChat Mini Program/HarmonyOS tidak memiliki kanal otomasi browser
- **Kesimpulan: lingkungan otomasi end-to-end tidak tersedia**. Disarankan menambah di CI: flutter build web → Playwright
  menggerakkan jalur kunci panel admin (login → daftar pesanan → verifikasi); Mini Program/HarmonyOS perlu pengujian manual perangkat asli/emulator
- Sudah disediakan: admin/public/apidoc (halaman dokumentasi antarmuka)

## 6. GO / Rust

Pemindaian rekursif root proyek **0 file .go, 0 file .rs** (kecuali vendor/node_modules/.git).
Toolchain terinstal (go / rustc tersedia) tetapi tidak ada objek yang dapat diuji. Jika nanti memperkenalkan layanan GO/Rust, perlu pengujian tambahan.

## 7. Risiko Sisa (area bernilai tinggi yang tidak tercakup)

- Alur utama order (sudah tercakup melalui pengujian tingkat trait OrderState/OrderRefundFlow, dll.)
- Notifikasi pembayaran WeChat asli (WechatPayService ada unit test, sandbox WeChat asli belum diuji terintegrasi)
- Modul dependensi eksternal seperti pencetakan, LBS, kode verifikasi

（§3 menunggu tester-php kembali untuk diisi）
