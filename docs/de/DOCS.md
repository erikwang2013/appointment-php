# Buchungsservice-System — Dokumentationsindex
> **Languages**: [中文](../README.md) · [English](../en/DOCS.md) · [한국어](../ko/DOCS.md) · [Русский](../ru/DOCS.md) · [Français](../fr/DOCS.md) · [Español](../es/DOCS.md) · [Português](../pt/DOCS.md) · [हिन्दी](../hi/DOCS.md) · [العربية](../ar/DOCS.md) · [বাংলা](../bn/DOCS.md) · [Bahasa Indonesia](../id/DOCS.md) · [日本語](../ja/DOCS.md)

> Deutsche Übersetzung · Original: [中文](../README.md)

> **Projektstatus**: vollständig abgeschlossen ✅ | 143 Controller (service 69 / admin 74) | 87 Modelle | 757 Tests (service 579 / admin 178) | 95 Datenbanktabellen | 479 Routen (service 221 / admin 258)

## Kerndokumente

| Dokument | Beschreibung |
|------|------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Architekturbeschreibung: Systemübersicht, Projektbestandteile, Kernkomponenten, Middleware-Kette, Datenfluss |
| [FEATURES.md](FEATURES.md) | Funktionsbeschreibung: vollständige Funktionsliste für Kunden + Techniker-Arbeitsplatz + Verwaltungsbackend |
| [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) | Architekturdesign: Schichtenarchitektur, Middleware-Design, Datenbankdesign, Sicherheitsdesign, ES-Integration |
| [FEATURE-DESIGN.md](FEATURE-DESIGN.md) | Funktionsdesign: Kaufablauf, Bestellstatusmaschine, Rückerstattungsregeln, Mitgliederkarten-Design, Identitätswechsel |
| [STRUCTURE.md](STRUCTURE.md) | Projektstruktur: vollständige Verzeichnisstruktur der vier Endgeräte, Middleware-Ausführungskette, Datenbanktabellenliste |
| [INSTALL.md](INSTALL.md) | Installationsanleitung: Web-Installationsassistent, manuelle Installation, Docker-Bereitstellung, Umgebungsvariablen, FAQ |
| [USAGE.md](USAGE.md) | Bedienungsanleitung: Verwaltungsbackend / Kunde / Techniker (API-Schnittstellen siehe [API.md](API.md)) |
| [API.md](API.md) | API-Dokumentation: Business-API + Verwaltungsbackend-API, mit Anfrage-/Antwortbeispielen + OpenAPI-Endpunkten |

## Diagramme (SVG)

Alle Diagramme liegen in [diagrams/](diagrams/): die chinesischen `cn-*`- und englischen `en-*`-Originale befinden sich in `docs/diagrams/`, jede Sprache hat ihr eigenes gespiegeltes Set in `docs/<lang>/diagrams/`:

| Diagramm | Beschreibung | Mermaid-Quelle |
|------|------|-----------|
| [de-architecture.svg](diagrams/de-architecture.svg) | Systemarchitektur: Topologie der vier Client-Schichten + Middleware + Datenschicht + Drittanbieterdienste | [ARCHITECTURE-DIAGRAM.md](diagrams/ARCHITECTURE-DIAGRAM.md) |
| [de-architecture-design.svg](diagrams/de-architecture-design.svg) | Architekturdesign: 7 Schichten + Middleware-Kette + Ratenbegrenzung + Datenbankdesign-Regeln + Sicherheitsdesign | [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) |
| [de-feature-design.svg](diagrams/de-feature-design.svg) | Funktionsdesign: drei Funktionsdomänen + Kaufabläufe + Handelsregeln + Vermögenswerte & Ansprüche + Technikerabrechnung + Rollenwechsel + Zahlung | [FEATURE-DESIGN.md](FEATURE-DESIGN.md) |
| [de-project-structure.svg](diagrams/de-project-structure.svg) | Projektstruktur: Verzeichnisbaum der vier Plattformen + Moduldetails | [STRUCTURE.md](STRUCTURE.md) |
| [de-appointment-flow.svg](diagrams/de-appointment-flow.svg) | Service-Buchungsablauf | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [de-payment-refund.svg](diagrams/de-payment-refund.svg) | Zahlungs- und Rückerstattungsablauf | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [de-order-lifecycle.svg](diagrams/de-order-lifecycle.svg) | Zustandsmaschine des Bestelllebenszyklus | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [de-lifecycle-overview.svg](diagrams/de-lifecycle-overview.svg) | Überblick über alle Lebenszyklen (17, in vier Gruppen) | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [de-security-defense.svg](diagrams/de-security-defense.svg) | Siebenstufige Verteidigung in der Tiefe | [SECURITY-ARCHITECTURE.md](diagrams/SECURITY-ARCHITECTURE.md) |
| [mascot.svg](diagrams/mascot.svg) | Projektmaskottchen „Kalendergeist Yue" (SMIL-Animation, ohne externe Abhängigkeiten) | — |

## Tests und Sicherheit

| Dokument | Beschreibung |
|------|------|
| [TEST-REPORT.md](TEST-REPORT.md) | Testbericht: vollständiges Abdeckungsaudit mit 558 Fällen / 2508 Assertions + HTTP-Smoke-Protokoll |
| [AUDIT-REPORT.md](AUDIT-REPORT.md) | Prüfbericht: Testergebnisse, Ökosystem-Konfigurationsbewertung, Fehlerbehebungsprotokoll, Code-Architekturanalyse |
| [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) | Sicherheitsaudit-Bericht |

## Datenbank und Betrieb

| Dokument | Beschreibung |
|------|------|
| [install.sql](../install.sql) | Einheitliches Installationsskript: 67 konsolidierte Migrationen, 2723 Zeilen, 95 Tabellen / 285 Berechtigungen / 38 Konfigurationen + Demodaten |

## Spezifikationen und Pläne

| Dokument | Beschreibung |
|------|------|
| [specs/2026-05-26-appointment-system-design.md](specs/2026-05-26-appointment-system-design.md) | Systemdesign-Spezifikation |
| [plans/2026-05-26-appointment-system-plan.md](plans/2026-05-26-appointment-system-plan.md) | Implementierungsplan |

## Verwaltungsbackend-Dokumentation

Eigene Dokumentation im `admin/`-Verzeichnis: ARCHITECTURE.md, DESIGN.md, SECURITY.md, API.md, nginx-security.conf.
