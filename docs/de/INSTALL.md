# Buchungsservice-System — Installationsanleitung
> **Languages**: [中文](../INSTALL.md) · [English](../en/INSTALL.md) · [한국어](../ko/INSTALL.md) · [Русский](../ru/INSTALL.md) · [Français](../fr/INSTALL.md) · [Español](../es/INSTALL.md) · [Português](../pt/INSTALL.md) · [हिन्दी](../hi/INSTALL.md) · [العربية](../ar/INSTALL.md) · [বাংলা](../bn/INSTALL.md) · [Bahasa Indonesia](../id/INSTALL.md) · [日本語](../ja/INSTALL.md)

> Deutsche Übersetzung · Original: [中文](../INSTALL.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## Systemvoraussetzungen

| Komponente | Mindestversion | Beschreibung |
|------|----------|------|
| PHP | 8.3+ | Erweiterungen: bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | Tabellenpräfix `appointment_`, Zeichensatz utf8mb4 |
| Redis | 6.0+ | Cache / Rate-Limit / Session / Verifizierungscode-Speicher |
| Composer | 2.x | PHP-Abhängigkeitsverwaltung |
| Elasticsearch | 8.x (optional) | Volltextsuche; ohne Installation bleiben Kernfunktionen intakt |

---

## I. Web-Installationsassistent (empfohlen)

Nach dem Start des Verwaltungsbackends über `/install` im Browser den Ein-Klick-Installationsassistenten aufrufen:

```bash
# 1. Abhängigkeiten installieren und starten
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # Standardport 8787
```

Im Browser `http://localhost:8787/install` öffnen und in 4 Schritten abschließen:

1. **Umgebungsprüfung** — automatische Prüfung von PHP-Version, erforderlichen Erweiterungen und Dateiberechtigungen
2. **Datenbankkonfiguration** — MySQL-Verbindungsdaten eingeben, Verbindung testen
3. **Administratorkonto** — Anwendungsname, Admin-Benutzername und -Passwort festlegen
4. **Installation ausführen** — automatischer SQL-Import → Admin erstellen → .env-Konfiguration schreiben

Nach Abschluss der Installation mit dem festgelegten Benutzernamen und Passwort anmelden. Bei erfolgreicher Installation wird die Datei `.install.lock` geschrieben; die `/install`-Schnittstelle prüft doppelt (Dateisperre + isInstalled) gegen Neuinstallation; `.install.lock` ist in `.gitignore` aufgenommen. Es wird empfohlen, in der Produktion die `/install`-Route in `admin/config/route.php` zu entfernen.

---

## II. Manuelle Installation

### 2.1 Projekt klonen

```bash
git clone <repo-url> appointment-php
cd appointment-php
```

### 1.2 PHP-Abhängigkeiten installieren

```bash
# Business-API-Service
cd service/
cp .env.example .env
composer install --no-dev --optimize-autoloader

# Verwaltungsbackend
cd ../admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

### 1.3 Umgebungsvariablen konfigurieren

`service/.env` (Business-API) und `admin/.env` (Verwaltungsbackend) bearbeiten und folgende Kernkonfiguration anpassen:

```bash
# Datenbankverbindung
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment          # service verwendet appointment, admin verwendet open_admin
DB_USERNAME=root
DB_PASSWORD=your-password

# Redis-Verbindung
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# JWT-Schlüssel — in der Produktion unbedingt auf eine 64-stellige Zufallszeichenkette ändern
JWT_SECRET_KEY=your-64-char-random-string

# Verschlüsselungsschlüssel — in der Produktion unbedingt ändern
ENCRYPTION_KEY=your-32-byte-key
ENCRYPTABLE_KEY=your-32-byte-key

# Hashids-Salt — in der Produktion unbedingt ändern
HASHIDS_SALT=your-random-salt

# Debug-Modus — in der Produktion muss false gesetzt werden
APP_DEBUG=false
```

> Vollständige Variablenerklärung in `service/.env.example` und `admin/.env.example`.

### 1.4 Datenbank erstellen und importieren

```bash
# Datenbanken erstellen (service und admin können dieselbe Datenbank oder getrennte nutzen)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Einheitliches Installationsskript importieren (alle 54+ Tabellen + Berechtigungsdaten + Demodaten)
mysql -u root -p appointment < docs/install.sql
mysql -u root -p open_admin < docs/install.sql
```

> `docs/install.sql` ist aus allen Migrationsdateien zusammengeführt, insgesamt 2723 Zeilen, und enthält sämtliche Tabellenstrukturen und Seed-Daten von Verwaltungsbackend und Business-Service. Für Neuinstallationen einmal ausführen; eine wiederholte Ausführung auf einer bestehenden Datenbank bricht wegen Primärschlüssel-/Spaltenkonflikten ab. Für Upgrades zuerst sichern oder Konflikte manuell beheben.

### 1.5 Dienste starten

```bash
# Business-API-Service starten (Standardport 8787)
cd service/
php start.php start -d

# Verwaltungsbackend starten (Standardport 8787)
cd ../admin/
php start.php start -d
```

### 1.6 Installation verifizieren

```bash
# Business-API
curl http://localhost:8787/api/common/config

# Health-Check des Verwaltungsbackends
curl http://localhost:8787/health

# Login des Verwaltungsbackends (Standard-Zugangsdaten siehe unten)
curl -X POST http://localhost:8787/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 1.7 Standardkonto

| Rolle | Benutzername | Passwort | Beschreibung |
|------|--------|------|------|
| Superadmin | `admin` | `admin123` | Besitzt alle Berechtigungen |

> Nach dem ersten Login das Passwort sofort ändern.

---

## III. Docker-Bereitstellung

### 2.1 Business-API-Service

```bash
cd service/
cp .env.docker .env
# .env bearbeiten, Schlüssel und Passwörter ändern
docker-compose up -d
```

Orchestrierung: nginx (80/443) + app (8787) + mysql (3306) + redis (6379) + elasticsearch (9200)

### 2.2 Verwaltungsbackend

```bash
cd admin/
cp .env.docker .env
docker-compose up -d
```

### 2.3 Datenbank in Docker-Umgebung importieren

```bash
# install.sql in den Container kopieren und ausführen
docker cp docs/install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## IV. Datenbankstruktur-Übersicht

| Domäne | Tabellenanzahl | Kerntabellen |
|----|------|--------|
| Verwaltungsbackend | 8 | `appointment_admin_user`, `appointment_admin_role`, `appointment_admin_permission`, `appointment_operation_log` |
| Benutzerdomäne | 4 | `appointment_user`, `appointment_user_address`, `appointment_user_favorite`, `appointment_user_device` |
| Technikerdomäne | 8 | `appointment_technician_profile`, `appointment_technician_schedule`, `appointment_technician_earning`, `appointment_technician_withdrawal`, `appointment_technician_tier_config` |
| Dienstleistungsdomäne | 4 | `appointment_service_category`, `appointment_service`, `appointment_service_package`, `appointment_service_record` |
| Bestelldomäne | 5 | `appointment_order`, `appointment_order_item`, `appointment_order_payment`, `appointment_order_refund`, `appointment_order_review` |
| Marketingdomäne | 8 | `appointment_coupon`, `appointment_member_card`, `appointment_gift_card`, `appointment_user_points`, `appointment_promotion` |
| Warteschlange | 1 | `appointment_queue_number` |
| Inhaltsdomäne | 5 | `appointment_banner`, `appointment_announcement`, `appointment_faq`, `appointment_feedback`, `appointment_platform_agreement` |
| Communitydomäne | 3 | `appointment_post`, `appointment_comment`, `appointment_moment` |
| Filiale | 1 | `appointment_store` |
| Schulung | 2 | `appointment_training_course`, `appointment_training_progress` |
| Prüfung | 3 | `appointment_exam`, `appointment_exam_question`, `appointment_exam_attempt` |
| System | 3 | `appointment_system_config`, `appointment_notification`, `appointment_signature` |
| **Gesamt** | **55** | |

Alle Tabellen verwenden das `appointment_`-Präfix, der Primärschlüssel `id` ist BIGINT nicht autoinkrementierend (von snowflake-php auf Anwendungsebene generiert).

---

## V. Tests ausführen

```bash
# Business-API-Tests (21 tests)
cd service/
php vendor/bin/phpunit

# Verwaltungsbackend-Tests (59 tests)
cd admin/
php vendor/bin/phpunit

# Statische Analyse
php vendor/bin/phpstan analyse --level=5 app/

# Code-Stil-Prüfung
php vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## VI. Drittanbieter-Dienstkonfiguration

Im Verwaltungsbackend unter „Systemkonfiguration" folgende Konfigurationsgruppen ausfüllen:

| Konfigurationsgruppe | Zweck | Erforderlich |
|--------|------|------|
| `wechat_pay` | WeChat-Zahlungs-Händlernummer / API-Schlüssel / Zertifikate | Für Zahlungsfunktionen |
| `wechat_app` | WeChat-MiniProgramm-AppID / AppSecret | Für WeChat-Login |
| `sms` | SMS-Anbieter (aliyun/tencent) + Signatur/Vorlage | Für SMS-Verifizierungscodes |
| `map_service` | Kartendienst (amap/tencent) + API-Key | Für LBS-Funktionen |
| `storage` | Objektspeicher (oss/cos) + AccessKey/Endpoint | Für Datei-Upload |

---

## VII. Häufige Fragen

**F: Startfehler `Class 'support\Model' not found`**
A: `composer dump-autoload` ausführen.

**F: Datenbankverbindungsfehler `SQLSTATE[HY000] [2002]`**
A: `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` in `.env` prüfen.

**F: Zeichencodierungsfehler beim SQL-Import**
A: `mysql -u root -p --default-character-set=utf8mb4 < docs/install.sql` verwenden.

**F: Redis-Verbindungsfehler**
A: Sicherstellen, dass Redis läuft, und `REDIS_HOST`/`REDIS_PORT` prüfen.

**F: Port bereits belegt**
A: Den `listen`-Port in `config/server.php` ändern.

**F: Verifizierungscode wird nicht angezeigt**
A: Sicherstellen, dass die GD-Erweiterung installiert ist und `POSTER_CAPTCHA_STORAGE` korrekt konfiguriert ist (lokal `file`, Produktion `redis`).

**F: Elasticsearch funktioniert nicht**
A: ES ist eine optionale Komponente; sicherstellen, dass `SCOUT_HOSTS` korrekt konfiguriert ist und der ES-Dienst läuft.

---

## VIII. Verzeichnisstruktur

```
appointment-php/
├── admin/                    # Verwaltungsbackend (webman v2)
│   ├── app/                  # Controller / Modelle / Middleware
│   ├── config/               # Konfiguration von Routen / Datenbank / Middleware
│   ├── database/             # Sicherungsskripte (Tabellenstruktur und Seeds zentral in docs/install.sql)
│   ├── tests/                # PHPUnit-Tests (59 tests)
│   ├── .env.example          # Umgebungsvariablen-Vorlage
│   ├── .env.docker           # Docker-Umgebungsvariablen
│   ├── Dockerfile            # Docker-Build-Datei
│   └── docker-compose.yml    # Docker-Orchestrierung
├── service/                  # Business-API-Service (webman v2)
│   ├── app/                  # Controller / Modelle / Middleware
│   ├── config/               # Konfiguration von Sicherheit / Routen / Datenbank
│   ├── seed.php              # Demo-Daten-Seed-Runner (liest den Demodaten-Abschnitt aus docs/install.sql)
│   ├── tests/                # PHPUnit-Tests (21 tests)
│   ├── .env.example          # Umgebungsvariablen-Vorlage
│   ├── .env.docker           # Docker-Umgebungsvariablen
│   ├── Dockerfile            # Docker-Build-Datei
│   └── docker-compose.yml    # Docker-Orchestrierung
├── docs/                     # Dokumentation
│   ├── INSTALL.md            # Diese Installationsanleitung
│   ├── install.sql           # Einheitliches Datenbank-Installationsskript (2723 Zeilen)
│   ├── ARCHITECTURE.md       # Architekturdesign-Dokument
│   ├── API.md                # API-Referenzdokument
│   └── AUDIT-REPORT.md       # Prüfbericht
└── .github/workflows/        # CI/CD-Pipeline
    └── ci.yml
```
