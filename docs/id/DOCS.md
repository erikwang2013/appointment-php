# Sistem Layanan Janji Temu — Indeks Dokumen

> Terjemahan bahasa Indonesia · Asli: [中文](../../docs/README.md)

> **Status Proyek**: Semua selesai ✅ | 143 controller (service 69 / admin 74) | 87 model | 722 pengujian (service 558 / admin 164) | 95 tabel data | 388 rute (service 227 / admin 161)

## Dokumen Inti

| Dokumen | Keterangan |
|------|------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Penjelasan arsitektur: ringkasan sistem, komposisi proyek, komponen inti, rantai middleware, alur data |
| [FEATURES.md](FEATURES.md) | Penjelasan fitur: daftar fitur lengkap sisi pengguna + workbench teknisi + panel admin |
| [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) | Desain arsitektur: arsitektur berlapis, desain middleware, desain basis data, desain keamanan, integrasi ES |
| [FEATURE-DESIGN.md](FEATURE-DESIGN.md) | Desain fitur: alur pembelian, state machine pesanan, aturan pengembalian dana, desain kartu member, peralihan identitas |
| [STRUCTURE.md](STRUCTURE.md) | Struktur proyek: tata letak direktori lengkap empat platform, rantai eksekusi middleware, daftar tabel basis data |
| [INSTALL.md](INSTALL.md) | Petunjuk instalasi: wizard instalasi Web, instalasi manual, deployment Docker, variabel lingkungan, FAQ |
| [USAGE.md](USAGE.md) | Petunjuk penggunaan: operasi panel admin / sisi pengguna / sisi teknisi (antarmuka API lihat [API.md](API.md)) |
| [API.md](API.md) | Dokumentasi API: API bisnis + API panel admin, lengkap dengan contoh permintaan/respons + endpoint OpenAPI |

## Pengujian & Keamanan

| Dokumen | Keterangan |
|------|------|
| [TEST-REPORT.md](TEST-REPORT.md) | Laporan pengujian: audit cakupan 558 kasus penuh / 2508 asersi + catatan smoke test HTTP |
| [AUDIT-REPORT.md](AUDIT-REPORT.md) | Laporan audit: hasil pengujian, penilaian konfigurasi ekosistem, catatan perbaikan masalah, analisis arsitektur kode |
| [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) | Laporan audit keamanan |

## Basis Data & Operasional

| Dokumen | Keterangan |
|------|------|
| [install.sql](../install.sql) | Skrip instalasi terpadu: 67 migrasi digabung, 2723 baris, 95 tabel / 285 izin / 38 konfigurasi + data demo |

## Spesifikasi & Rencana

| Dokumen | Keterangan |
|------|------|
| [spesifikasi desain sistem](specs/2026-05-26-appointment-system-design.md) | Spesifikasi desain sistem |
| [rencana implementasi](plans/2026-05-26-appointment-system-plan.md) | Rencana implementasi |

## Dokumen Panel Admin

Dokumen milik `admin/`: ARCHITECTURE.md, DESIGN.md, SECURITY.md, API.md, nginx-security.conf.
