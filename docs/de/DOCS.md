# Buchungsservice-System — Dokumentationsindex

> Deutsche Übersetzung · Original: [中文](../README.md)

> **Projektstatus**: vollständig abgeschlossen ✅ | 143 Controller (service 69 / admin 74) | 87 Modelle | 722 Tests (service 558 / admin 164) | 95 Datenbanktabellen | 388 Routen (service 227 / admin 161)

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
