# Diagram Fungsi Sistem
> **Languages**: [中文](../../diagrams/FUNCTION-DIAGRAM.md) · [English](../../en/diagrams/FUNCTION-DIAGRAM.md) · [한국어](../../ko/diagrams/FUNCTION-DIAGRAM.md) · [Русский](../../ru/diagrams/FUNCTION-DIAGRAM.md) · [Deutsch](../../de/diagrams/FUNCTION-DIAGRAM.md) · [Français](../../fr/diagrams/FUNCTION-DIAGRAM.md) · [Español](../../es/diagrams/FUNCTION-DIAGRAM.md) · [Português](../../pt/diagrams/FUNCTION-DIAGRAM.md) · [हिन्दी](../../hi/diagrams/FUNCTION-DIAGRAM.md) · [العربية](../../ar/diagrams/FUNCTION-DIAGRAM.md) · [বাংলা](../../bn/diagrams/FUNCTION-DIAGRAM.md) · [日本語](../../ja/diagrams/FUNCTION-DIAGRAM.md)

> Terjemahan bahasa Indonesia · Asli: [中文](../../diagrams/FUNCTION-DIAGRAM.md)

```mermaid
mindmap
  root((Sistem Layanan Janji Temu))
    Sisi pengguna
      Otentikasi
        Registrasi nomor ponsel / login
        Login kode verifikasi
        Login WeChat
        Mode tamu
        Lupa kata sandi
        Perjanjian pengguna / privasi
      Beranda
        Lokasi LBS & ganti kota
        Banner / pengumuman
        Pintu masuk kategori layanan
        Kupon pengguna baru
      Janji temu layanan
        Pilih toko termasuk navigasi
        Pilih teknisi termasuk penilaian
        Pilih waktu layanan
        Non-puncak diskon 10% / pesan awal diskon 5%
        Penggunaan kupon
        Catatan & perjanjian layanan
      Mal produk
        Cari & filter produk
        Detail produk & favorit
        Manajemen keranjang
        Beli sekarang
      Manajemen pesanan
        Semua pesanan lihat Tab
        Belum dibayar / belum dikirim / menunggu diterima
        Batalkan / desak kirim / konfirmasi terima
        Ajukan pengembalian dana
        Ajukan after-sales  retur/tukar pelacakan status
        Poin potong tunai  potong saat bayar
        Pesan grup  pesan dengan harga grup setelah ikut aktivitas
        Pesan seckill  pesan dengan harga seckill, cegah saat habis
        Ubah jadwal  ganti waktu dengan teknisi sama jarak mulai ≥6 jam
        Kalender janji  tampilan bulan/hari jadwal, sudah dijanjikan dikecualikan
        Pengingat sebelum layanan  1 jam sebelum pesan langganan + situs
        Ulasan teks + gambar
        Ulasan lanjutan  tambah konten/gambar sekali
        Lacak logistik  status kirim/penerima disamarkan
        E-invoice  ajukan/daftar/detail cegah duplikat
        Ekspor kalender ICS  ekspor janji 90 hari sebagai iCal
        Linimasa pesanan  catatan perubahan status/hanya sendiri
        Judul invoice  pustaka judul umum/default
        Pengaturan preferensi pesan  saklar notifikasi/pintu pengatur waktu
      Modul teknisi
        Daftar teknisi  urut jarak
        Detail teknisi & favorit
        Permohonan bergabung
        Jadwal massal  rentang tanggal ≤7 hari/deteksi konflik tumpang tindih
      Pusat pemasaran
        Kupon  klaim/potongan saat pesan
        Transfer kupon  kode transfer 8 digit/cegah pakai ganda/berlaku 7 hari
        Kartu member  bulanan/VIP/kartu kunjungan
        Verifikasi kartu kunjungan  my/use
        Dapatkan & tukar poin/rabat belanja
        Poin kedaluwarsa  berlaku 365 hari/potong terjadwal
        Mal tukar poin  tukar kupon/saldo/kartu hadiah
        Grup/seckill  ikut/kunci penuh/pesan setelah terbentuk
        Pengingat kedaluwarsa kartu  notifikasi dalam 3 hari
        Kartu hadiah  tunai/fisik/kredit tukar
        Transfer poin  antar pengguna/batas harian/transaksi dua arah
        Komisi level-2  referrer level-2 komisi 2%
        Aktivitas potongan penuh  belanja X potong Y/otomatis menumpuk saat pesan
        Roda poin  undian berbobot/kupon saldo poin/kalah
      Dompet
        Cek saldo
        Isi ulang  notifikasi situs saat masuk
        Bayar dengan saldo
        Isi kembali dana refund
        Transfer saldo  antar pengguna/kunci dua baris/catatan transfer
        Kata sandi bayar  6 digit atur/verifikasi/ubah
      Pusat pribadi
        Avatar/nama panggilan/nomor ponsel
        Alih identitas  pelanggan↔teknisi
        Notifikasi pesan
        Favorit saya
        Jejak kunjungan  layanan yang baru dilihat
        Arsip kesehatan  riwayat alergi/teknisi pilihan
        Ikuti akun resmi
        Promosi pengguna  poster QR/rincian komisi
        Level pertumbuhan  check-in/ulasan/belanja 5 tingkat
        Hak level  diskon pesan/pengganda poin
        Tiket layanan  kirim/daftar/detail/tutup
        Kepuasan tiket  nilai saat tutup/rangkuman panel admin
        Masukan
      Pengaturan
        Ubah kata sandi
        Ganti ikatan ponsel
        Lihat perjanjian
        Cek pembaruan
        Kepatuhan privasi  ekspor data/penutupan 72 jam loop
        Tutup akun

    Workbench teknisi
      Absensi check-in
        Check-in kerja  tandai terlambat
        Check-out
      Loop workbench
        today  pesanan hari ini
        records  catatan layanan
        start  mulai layanan
        complete  selesaikan verifikasi
      Ringkasan hari ini
        Jumlah pesanan hari ini
        Ringkasan pendapatan
      Manajemen jadwal
        Atur slot waktu per hari
        Terbitkan waktu yang bisa dijanjikan
      Penanganan pesanan
        Daftar dijanjikan belum diverifikasi
        Daftar selesai
        Pindai QR untuk verifikasi
      Manajemen member
        Member yang dilayani
        Data pemakaian kelas
        Catatan kartu kunjungan
        Edit arsip member
      Interaksi ulasan
        Balas ulasan pengguna  404/duplikat 422/notifikasi situs
      Manajemen pendapatan
        Pendapatan hari ini
        Jumlah dalam penyelesaian
        Saldo dompet
        Dana perjalanan  konfirmasi otomatis setelah 3 hari
      Penarikan dana
        Ajukan setiap tanggal 20
        T+1 masuk ke WeChat Pay
        Batas minimum/sisihkan/kelipatan 100
      Hadiah pelanggan kembali
        Bonus belanja kedua dalam 30 hari
      Pelatihan profesional
        Kursus video
        Kursus teks + gambar

    Panel admin
      Dashboard
        Panel statistik real-time
        Grafik tren pesanan/jumlah
        Akun baru/aktivitas
        Navigasi cepat
        Pesan situs
      Manajemen teknisi
        Daftar & cari teknisi
        Tambah/ekspor
        Tinjau permohonan bergabung
        Pengaturan jadwal/item layanan
        Lacak kemajuan kursus
        Penilaian otomatis level teknisi  jumlah pesanan + rata-rata/hanya naik/catatan perubahan
        Statistik absensi  per bulan/kelompok teknisi/terlambat
      Manajemen pengguna
        Daftar & cari member
        Detail/pengaturan level
        Ubah atasan/kata sandi/ponsel
      Manajemen toko
        Toko CRUD
        Kontrol aktif/nonaktif
        Konfigurasi koordinat peta
        Workbench toko  ringkasan/filter pesanan
      Layanan & produk
        Item layanan CRUD
        Produk CRUD
        Manajemen pohon kategori
        Desain kartu  kombinasi item+produk
      Manajemen mal
        Pesanan mal/kirim/logistik
        Tinjau pesanan after-sales
        Manajemen ulasan
        Audit gambar ulasan  sembunyikan/pulihkan izin 389-391
        Transaksi pembayaran
        Statistik penjualan
      Pesanan janji temu
        Cari multi kondisi
        Batalkan platform/konfirmasi selesai
        Lihat detail
      Aktivitas kupon
        Kupon CRUD
        Kontrol terbit/tarik
        Statistik klaim
      Aktivitas potongan penuh
        Belanja X potong Y CRUD
        Kontrol terbit/tarik
      Roda poin
        Hadiah CRUD
        Kontrol terbit/tarik
        Lihat catatan undian
      Aktivitas seckill
        Aktivitas CRUD
        Kontrol terbit/tarik
        Lihat pesanan seckill
      Tukar poin
        Barang tukar CRUD
        Kontrol terbit/tarik
        Lihat catatan tukar
      Manajemen kartu member
        Definisi kartu member CRUD
        Kartu kunjungan/bulanan/VIP
      Manajemen after-sales
        Daftar after-sales  filter status/pengguna/pesanan
        Tinjau  setujui/tolak catatan
      Ulasan & laporan
        Manajemen ulasan layanan
        Statistik laporan data
      Manajemen keuangan
        Pembagian pesanan
        Tinjau penarikan teknisi
        Pengaturan komisi & hadiah/denda
        Transaksi masuk/keluar
        Konfigurasi akun/batas penarikan
        Persetujuan refund dua level
        Catatan komisi distribusi
        Catatan komisi level-2  izin 386
        Catatan pembagian  pembagian WeChat/filter status
        Tinjau invoice  terbit/tolak izin 382-384
        Hadiah pelanggan kembali  saklar/rasio/catatan hadiah izin 412-414
      Manajemen konten
        Banner CRUD
        Pengumuman CRUD & terbitkan
        Edit perjanjian
        FAQ CRUD
        Tangani masukan
        Balas tiket layanan  izin 385/387
        Statistik kepuasan tiket  izin 388
        Tinjau momen
        Pengaturan tentang kami
      Pengaturan sistem
        Manajemen perjanjian platform
        Komisi seragam teknisi
        Template pesan sistem
        Push APP  didorong konfigurasi/5 event terhubung
        Pesan langganan  3 skenario event pesanan
        Manajemen versi APP  versi CRUD/paksa perbarui
        Izin sub-akun  RBAC
      Fitur tambahan
        Monitor sistem  CPU/memori/Redis/MySQL
        Manajemen daftar hitam IP
        Cadangan/pulihkan database
        Profil pelanggan  tampilan 360
        Push pesan massal
        Manajemen tugas terjadwal
        Konfigurasi SMS dua kanal
        Konfigurasi penyimpanan  lokal/OSS/COS
        Ekspor jadwal Excel
        Akun manajer toko  isolasi store_id
```
