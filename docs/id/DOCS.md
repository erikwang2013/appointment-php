# Sistem Layanan Janji Temu — Indeks Dokumen
> **Languages**: [中文](../README.md) · [English](../en/DOCS.md) · [한국어](../ko/DOCS.md) · [Русский](../ru/DOCS.md) · [Deutsch](../de/DOCS.md) · [Français](../fr/DOCS.md) · [Español](../es/DOCS.md) · [Português](../pt/DOCS.md) · [हिन्दी](../hi/DOCS.md) · [العربية](../ar/DOCS.md) · [বাংলা](../bn/DOCS.md) · [日本語](../ja/DOCS.md)

> Terjemahan bahasa Indonesia · Asli: [中文](../../docs/README.md)

> **Status Proyek**: Semua selesai ✅ | 143 controller (service 69 / admin 74) | 87 model | 757 pengujian (service 579 / admin 178) | 95 tabel data | 479 rute (service 221 / admin 258)

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

## Diagram (SVG)

Semua diagram berada di [diagrams/](diagrams/): versi asli bahasa Mandarin `cn-*` dan bahasa Inggris `en-*` ada di `docs/diagrams/`, dan masing-masing dari 12 bahasa memiliki salinan cermin di `docs/<lang>/diagrams/`:

| Diagram | Keterangan | Sumber Mermaid |
|------|------|-----------|
| [id-architecture.svg](diagrams/id-architecture.svg) | Arsitektur sistem: topologi berlapis empat platform + middleware + lapisan data + layanan pihak ketiga | [ARCHITECTURE-DIAGRAM.md](diagrams/ARCHITECTURE-DIAGRAM.md) |
| [id-architecture-design.svg](diagrams/id-architecture-design.svg) | Desain arsitektur: arsitektur 7 lapisan + rantai eksekusi middleware + pembatasan permintaan + prinsip desain basis data + desain keamanan | [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) |
| [id-feature-design.svg](diagrams/id-feature-design.svg) | Desain fitur: tiga domain fungsional + alur pembelian + aturan transaksi + aset dan hak + penyelesaian teknisi + peralihan identitas + pembayaran | [FEATURE-DESIGN.md](FEATURE-DESIGN.md) |
| [id-project-structure.svg](diagrams/id-project-structure.svg) | Struktur proyek: pohon direktori empat platform + detail modul | [STRUCTURE.md](STRUCTURE.md) |
| [id-appointment-flow.svg](diagrams/id-appointment-flow.svg) | Alur janji temu layanan | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [id-payment-refund.svg](diagrams/id-payment-refund.svg) | Alur pembayaran & refund | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [id-order-lifecycle.svg](diagrams/id-order-lifecycle.svg) | State machine siklus hidup pesanan | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [id-lifecycle-overview.svg](diagrams/id-lifecycle-overview.svg) | Semua siklus hidup sekilas (17 total, dalam empat kelompok) | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [id-security-defense.svg](diagrams/id-security-defense.svg) | Sistem tujuh lapisan pertahanan berlapis | [SECURITY-ARCHITECTURE.md](diagrams/SECURITY-ARCHITECTURE.md) |
| [mascot.svg](diagrams/mascot.svg) | Maskot proyek "Yue, si Peri Kalender" (animasi SMIL, tanpa dependensi eksternal) | — |

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
