# Petunjuk Penggunaan

## Login Panel Admin

Administrator default: `admin` / `admin123` | Alamat: `http://localhost:8787`

> Segera ganti kata sandi setelah login pertama

---

## Alur Konfigurasi Sistem

### 1. Pengaturan Dasar
Konfigurasi Sistem → isi Nama Platform/LOGO → Tentang Kami → Telepon Layanan/Website/Email → Perjanjian Platform → edit Perjanjian Pengguna/Perjanjian Privasi

### 2. Toko & Layanan
Manajemen Toko → Tambah Toko (nama/alamat/koordinat/telepon/jam buka) → Kategori Layanan → Buat Kategori → Item Layanan → Tambah Layanan (nama/harga/durasi/spesifikasi) → Manajemen Produk → Tambah Barang/Kartu Kupon

### 3. Penerimaan Teknisi
Permohonan lewat APP Teknisi → Panel Admin「Manajemen Teknisi」tinjau → setelah disetujui teknisi atur jadwal → dapat menerima janji temu

### 4. Konfigurasi Operasional
Banner → unggah + atur tautan | Pengumuman → terbitkan pengumuman berjalan | Kupon → buat kupon pengguna baru/kupon potongan | Kartu Member → Kartu Bulanan/VIP/Kartu Kunjungan | Komisi → atur proporsi komisi teknisi

---

## Alur Sisi Pengguna

### Registrasi & Login
Cari di WeChat/pindai kode QR → registrasi dengan nomor ponsel + kode verifikasi (kode referral opsional) → atau login satu klik WeChat → pengguna baru otomatis dapat kupon

### Janji Temu Layanan
Jelajahi kategori di beranda → klik layanan untuk lihat detail → lihat harga/ulasan → Janji Sekarang → pilih Toko/Teknisi/Waktu/Kupon → konfirmasi pesanan → bayar dengan WeChat Pay → pembayaran berhasil

### Manajemen Pesanan
Belum dibayar: selesaikan pembayaran | Sudah dibayar: menunggu layanan | Selesai: beri ulasan (bintang + teks + foto) | Pengembalian dana: proporsi pengembalian dihitung otomatis

### Pusat Pribadi
Pesanan/Kupon/Kartu Member/Poin/Favorit | Pusat Promosi: dapatkan kode QR promosi untuk dapat poin | Masukan: teks + foto

---

## Operasi Sisi Teknisi

### Alih Identitas
APP「Saya」→ Alih ke Teknisi → Workbench

### Pekerjaan Harian
- **Atur Jadwal**: atur slot waktu yang dapat dijanjikan per hari
- **Lihat Janji Temu**: daftar pesanan yang sudah dijanjikan hari ini
- **Pindai Verifikasi**: pindai kode QR pengguna untuk verifikasi kunjungan
- **Arsip Member**: isi arsip pelanggan dalam 24 jam setiap pesanan (terlambat tidak ada komisi)
- **Absensi**: check-in/check-out/foto kebersihan

### Pendapatan
Lihat pendapatan hari ini/dana dalam perjalanan/saldo → penarikan dana setiap tanggal 20 → T+1 masuk ke WeChat Pay

### Pertumbuhan
Ikuti kursus pelatihan → ikuti ujian → lulus menaikkan level teknisi (mempengaruhi rasio komisi)

---

## Antarmuka API

Dokumentasi antarmuka dikelola terpisah, lihat [API.md](API.md) (API bisnis + API panel admin, lengkap dengan contoh permintaan/respons dan endpoint OpenAPI).

---

## WebSocket

```
ws://localhost:8282
```

Otentikasi: `{"type":"auth","token":"<JWT>"}`

Event: `order_update` / `technician_online` / `system_notice`

---

## Konfigurasi Notifikasi

iOS(APNs): konfigurasi apns_key_id/team_id/bundle_id/file .p8  
Android(FCM): konfigurasi fcm_server_key

Registrasi perangkat APP: `POST /api/user/device/register {"platform":"ios","device_token":"..."}`

---

## Tugas Terjadwal

| Tugas | Frekuensi | Keterangan |
|------|------|------|
| Pembatalan pesanan otomatis | 30 detik | lebih dari 30 menit belum dibayar |
| Penyelesaian pendapatan otomatis | 3 hari | selesaikan komisi pesanan selesai |
| Kedaluwarsa kupon | setiap hari | tandai expired |
| Kedaluwarsa kartu member | setiap hari | tandai expired |

---

## Aturan Pengembalian Dana

| Kondisi | Proporsi |
|------|------|
| Dalam 15 menit setelah pemesanan atau jarak mulai >6 jam | 100% |
| Jarak mulai ≤6 jam | 90% |
| Sudah mulai belum dikonfirmasi | 80% |
| Setelah dikonfirmasi mulai | 0% |

---

## Pemantauan

```bash
GET /health          # Pemeriksaan kesehatan
GET /metrics         # Metrik Prometheus
GET /.well-known/security.txt  # Kontak keamanan
```

## Pengujian

```bash
admin/ && phpunit --bootstrap tests/bootstrap.php     # 60 tests
service/ && phpunit --configuration phpunit.xml        # 21 tests
```
