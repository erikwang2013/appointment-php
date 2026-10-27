# Appointment Service System — Documentation Index
> **Languages**: [中文](../README.md)

> **Project status**: All complete ✅ | 115 controllers (service 53 / admin 62) | 65 models | 93 tests (service 68 / admin 25) | 73 database tables | 311 routes (service 173 / admin 138)

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

All diagrams live in [diagrams/](diagrams/). Only Chinese (`cn-*`) and English (`en-*`) diagram masters are maintained in `docs/diagrams/` and `docs/en/diagrams/`:

| Diagram | Description | Mermaid source |
|------|------|------|
| [en-architecture.svg](diagrams/en-architecture.svg) | System architecture: two-platform layered topology + middleware + data layer + third-party services | [ARCHITECTURE-DIAGRAM.md](diagrams/ARCHITECTURE-DIAGRAM.md) |
| [en-architecture-design.svg](diagrams/en-architecture-design.svg) | Architecture design: 7 layers + middleware execution chain + rate limiting + database design rules + security design | [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) |
| [en-feature-design.svg](diagrams/en-feature-design.svg) | Feature design: three functional domains + purchase flows + trading rules + assets & entitlements + technician settlement + identity switching + payment | [FEATURE-DESIGN.md](FEATURE-DESIGN.md) |
| [en-project-structure.svg](diagrams/en-project-structure.svg) | Project structure: two-platform directory tree + module detail | [STRUCTURE.md](STRUCTURE.md) |
| [en-appointment-flow.svg](diagrams/en-appointment-flow.svg) | Service booking flow | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [en-payment-refund.svg](diagrams/en-payment-refund.svg) | Payment and refund flow | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [en-order-lifecycle.svg](diagrams/en-order-lifecycle.svg) | Order lifecycle state machine | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [en-security-defense.svg](diagrams/en-security-defense.svg) | Seven-layer defense-in-depth | [SECURITY-ARCHITECTURE.md](diagrams/SECURITY-ARCHITECTURE.md) |
| [mascot.svg](diagrams/mascot.svg) | The project mascot — Yue, the Calendar Sprite (SMIL-animated SVG, no external dependencies) | — |

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
