# Appointment Service System — Documentation Index
> **Languages**: [中文](../README.md) · [한국어](../ko/DOCS.md) · [Русский](../ru/DOCS.md) · [Deutsch](../de/DOCS.md) · [Français](../fr/DOCS.md) · [Español](../es/DOCS.md) · [Português](../pt/DOCS.md) · [हिन्दी](../hi/DOCS.md) · [العربية](../ar/DOCS.md) · [বাংলা](../bn/DOCS.md) · [Bahasa Indonesia](../id/DOCS.md) · [日本語](../ja/DOCS.md)

> **Project status**: All complete ✅ | 143 controllers (service 69 / admin 74) | 87 models | 757 tests (service 579 / admin 178) | 95 database tables | 479 routes (service 221 / admin 258)

## Core Documentation

| Document | Description |
|------|------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Architecture: system overview, project composition, core components, middleware chain, data flows |
| [FEATURES.md](FEATURES.md) | Features: complete feature list for user clients + technician workbench + admin dashboard |
| [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) | Architecture design: layered architecture, middleware design, database design, security design, ES integration |
| [FEATURE-DESIGN.md](FEATURE-DESIGN.md) | Feature design: purchase flow, order state machine, refund rules, membership card design, identity switching |
| [STRUCTURE.md](STRUCTURE.md) | Project structure: full directory layout of the four clients, middleware execution chain, database table list |
| [INSTALL.md](INSTALL.md) | Installation: Web install wizard, manual installation, Docker deployment, environment variables, FAQ |
| [USAGE.md](USAGE.md) | Usage: admin dashboard / user clients / technician operations (API endpoints in [API.md](API.md)) |
| [API.md](API.md) | API documentation: business API + admin API, with request/response examples + OpenAPI endpoints |

## Diagrams (SVG)

All diagrams live in [diagrams/](diagrams/) — the mirrored set for this language. The Chinese `cn-*` and English `en-*` masters live in `docs/diagrams/`, and each of the 12 languages keeps its own mirror under `docs/<lang>/diagrams/`:

| Diagram | Description | Mermaid source |
|------|------|------|
| [en-architecture.svg](diagrams/en-architecture.svg) | System architecture: four-client layered topology + middleware + data layer + third-party services | [ARCHITECTURE-DIAGRAM.md](diagrams/ARCHITECTURE-DIAGRAM.md) |
| [en-architecture-design.svg](diagrams/en-architecture-design.svg) | Architecture design: 7 layers + middleware execution chain + rate limiting + database design rules + security design | [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) |
| [en-feature-design.svg](diagrams/en-feature-design.svg) | Feature design: three functional domains + purchase flows + trading rules + assets & entitlements + technician settlement + identity switching + payment | [FEATURE-DESIGN.md](FEATURE-DESIGN.md) |
| [en-project-structure.svg](diagrams/en-project-structure.svg) | Project structure: four-client directory tree + module detail | [STRUCTURE.md](STRUCTURE.md) |
| [en-appointment-flow.svg](diagrams/en-appointment-flow.svg) | Service booking flow | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [en-payment-refund.svg](diagrams/en-payment-refund.svg) | Payment and refund flow | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [en-order-lifecycle.svg](diagrams/en-order-lifecycle.svg) | Order lifecycle state machine | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [en-lifecycle-overview.svg](diagrams/en-lifecycle-overview.svg) | All 17 business lifecycles at a glance, in four groups | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [en-security-defense.svg](diagrams/en-security-defense.svg) | Seven-layer defense-in-depth | [SECURITY-ARCHITECTURE.md](diagrams/SECURITY-ARCHITECTURE.md) |
| [mascot.svg](diagrams/mascot.svg) | The project mascot "Booking Bunny" (SMIL-animated SVG, no external dependencies) | — |

## Testing & Security

| Document | Description |
|------|------|
| [TEST-REPORT.md](TEST-REPORT.md) | Test report: full 558-case / 2508-assertion coverage audit + HTTP smoke test record |
| [AUDIT-REPORT.md](AUDIT-REPORT.md) | Audit report: test results, ecosystem configuration scores, fix records, code architecture analysis |
| [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) | Security audit report |

## Database & Operations

| Document | Description |
|------|------|
| [install.sql](../install.sql) | Unified install script: 67 migrations merged, 2723 lines, 95 tables / 285 permissions / 38 configs + demo data |

## Specs & Plans

| Document | Description |
|------|------|
| [superpowers/specs/2026-05-26-appointment-system-design.md](specs/2026-05-26-appointment-system-design.md) | System design specification |
| [superpowers/plans/2026-05-26-appointment-system-plan.md](plans/2026-05-26-appointment-system-plan.md) | Implementation plan |

## Admin Dashboard Documentation

`admin/` keeps its own docs: ARCHITECTURE.md, DESIGN.md, SECURITY.md, API.md, nginx-security.conf.
