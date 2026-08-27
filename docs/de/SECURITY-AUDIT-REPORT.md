# Sicherheitsaudit-Bericht — Buchungssystem (appointment-php)
> **Languages**: [中文](../SECURITY-AUDIT-REPORT.md) · [English](../en/SECURITY-AUDIT-REPORT.md) · [한국어](../ko/SECURITY-AUDIT-REPORT.md) · [Русский](../ru/SECURITY-AUDIT-REPORT.md) · [Français](../fr/SECURITY-AUDIT-REPORT.md) · [Español](../es/SECURITY-AUDIT-REPORT.md) · [Português](../pt/SECURITY-AUDIT-REPORT.md) · [हिन्दी](../hi/SECURITY-AUDIT-REPORT.md) · [العربية](../ar/SECURITY-AUDIT-REPORT.md) · [বাংলা](../bn/SECURITY-AUDIT-REPORT.md) · [Bahasa Indonesia](../id/SECURITY-AUDIT-REPORT.md) · [日本語](../ja/SECURITY-AUDIT-REPORT.md)

> Deutsche Übersetzung · Original: [中文](../SECURITY-AUDIT-REPORT.md)

**Datum**: 2026-08-04
**Prüfumfang**: service (Buchungsservice-System), admin (offenes Verwaltungsbackend)
**PHP-Version**: 8.3.7
**Framework**: webman v2

---

## I. Testergebnisse

| Testpunkt | Service | Admin |
|--------|---------|-------|
| PHP-Syntaxprüfung (vollständig) | bestanden | bestanden |
| PHPUnit-Unit-Tests | 59 tests / 165 assertions PASS | 59 tests / 165 assertions PASS |
| PHPStan-Statische Analyse | nicht installiert (Dev-Abhängigkeits-Download-Timeout) | nicht installiert (Dev-Abhängigkeits-Download-Timeout) |

---

## II. Sicherheitsschicht-Überblick

```
Anfrage → Nginx (Sicherheitsheader+Schutz sensibler Dateien) → Cors (CORS+Sicherheitsheader) → SecurityMiddleware (31 Angriffserkennungen) → RateLimit (Redis Sliding Window) → Auth (JWT) → Controller
                                                                                                   ↓
                                                                                    IP-Blacklist (5 Angriffe/60s → 15min Sperre)
                                                                                    Kontosperre (5 Fehlversuche/15min → 15min Sperre)
```

---

## III. Behobene Probleme

### 3.1 Service-CORS ohne Sicherheits-Response-Header → behoben
**Datei**: `service/app/middleware/Cors.php`
- 6 Sicherheitsheader ergänzt: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP, X-Permitted-Cross-Domain-Policies
- Jetzt konsistent mit der admin-Sicherheitsheader-Konfiguration

### 3.2 Service ohne Login-Fehler-Sperre → behoben
**Datei**: `service/app/api/v1/controller/AuthController.php`
- Redis-Fehlversuchszähler in `login()` und `loginByCode()` ergänzt
- 5 Fehlversuche/15 Minuten Sperre → HTTP 429
- Graziöse Degradation bei Redis-Ausfall

### 3.3 CORS-Origin hartcodiert `*` → behoben
**Datei**: `service/app/middleware/Cors.php`, `admin/app/middleware/Cors.php`
- Über die Umgebungsvariable `CORS_ALLOW_ORIGIN` konfigurierbar gemacht
- Leer Standard `*` (abwärtskompatibel)

### 3.4 Service ohne security-php-Abhängigkeit → behoben
**Vorgang**:
- `allow-plugins.erikwang2013/security-php` zu composer.json hinzugefügt
- `composer install --no-dev` zur Installation ausgeführt
- Konfigurationsdatei nach `config/plugin/erikwang2013/security-php/app.php` veröffentlicht
- CSRF-Origin-Detektor (`csrf_origin`) aktiviert (block-Modus)

### 3.5 Service-Nginx ohne Permissions-Policy → behoben
**Datei**: `service/docs/nginx.conf`
- `add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;` ergänzt

### 3.6 Ökosystem-Konfiguration vervollständigt → behoben
- `CORS_ALLOW_ORIGIN` in `service/.env.example` und `admin/.env.example` ergänzt
- `CORS_ALLOW_ORIGIN` in `service/.env.docker` und `admin/.env.docker` ergänzt

---

## IV. Vollständige Liste des aktuellen Sicherheitsschutzes

### 4.1 WAF-Ebene — 31 Angriffsdetektoren

| Modus | Detektoren | Anzahl |
|------|--------|------|
| **block** (Unterbrechung 403) | XSS, SQL-Injection, Command-Injection, Pfad-Traversal, Datei-Upload, SSRF, XXE, Deserialisierung, LDAP-Injection, E-Mail-Header-Injection, Open Redirect, JWT-Angriffe, Host-Header-Angriffe, Request Smuggling, GraphQL-Injection, XPATH-Injection, JNDI/Log4Shell, SSI-Injection, CSV-Injection, Datenlecks, Prototype Pollution, WebSocket-Entführung, CORS-Bypass, DNS-Rebinding, HTTP-Methodenprüfung, Request-Bodengröße (10MB), Content-Type-Whitelist, CSRF-Origin | 28 |
| **log** (nur aufzeichnen) | Response-Header-Injection, SSTI, NoSQL-Injection | 3 |

### 4.2 Authentifizierung und Autorisierung

| Mechanismus | Service | Admin |
|------|---------|-------|
| JWT-Authentifizierung | Auth-Middleware | AdminAuth-Middleware |
| JWT-Blacklist | bei Abmeldung ergänzt | bei Abmeldung + Sessions-Überlimit ergänzt |
| RBAC-Berechtigungen | — | method.path-Format, Redis 60-s-Cache |
| Kontosperre | 5-mal/15 Minuten (Redis) | 5-mal/15 Minuten (Redis) |
| Parallele-Sessions-Begrenzung | — | maximal 3 Token |
| Passwort-Hash | bcrypt | bcrypt |

### 4.3 Rate-Limit

| Route | Service | Admin |
|------|---------|-------|
| Standard | 60-mal/Minute/IP | 60-mal/Minute/IP |
| Login | 10-mal/Minute | — |
| Registrierung | 5-mal/Minute | — |
| SMS/Passwort vergessen | 5-mal/Minute | — |

### 4.4 Datensicherheit

| Maßnahme | Service | Admin |
|------|---------|-------|
| Datenbankfeld-Verschlüsselung | AES-256-CBC (6 Modelle) | AES-256-CBC |
| API-Übertragungsverschlüsselung | AES-256-CBC | AES-256-CBC |
| ID-Verschleierung (Hashids) | alle externen IDs | alle externen IDs |
| Snowflake-ID | nicht autoinkrementierend BIGINT | nicht autoinkrementierend BIGINT |
| Maskierung sensibler Felder | Telefonnummer-Maskierung | Exportdaten-Maskierung |

---

## V. Offene Empfehlungen

### 5.1 Empfehlung: security-php-Speicher auf Redis umstellen (Produktion)
**Aktuell**: beide Dienste verwenden den `file`-Speichertyp (lokale JSON-Dateien)
**Risiko**: Bei Multi-Instanz-Bereitstellung wird die IP-Blacklist nicht geteilt, Angreifer können über Instanzwechsel ausweichen
**Empfehlung**: In der Produktion `storage.type` auf `redis` umstellen

### 5.2 Empfehlung: Sicherheitsattribute der Session-Cookies
**Aktuell**: `secure: false`, `same_site: ''`
**Risiko**: Cookie kann über HTTP übertragen werden, CSRF-Schutz geschwächt
**Empfehlung**: In der Produktion `secure: true`, `same_site: 'Lax'` setzen

### 5.3 Empfehlung: PHPStan-Dev-Abhängigkeit installieren
**Aktuell**: `composer install --dev` schlug wegen Netzwerk-Timeout fehl
**Vorgang**: `composer install --dev` oder `composer require --dev phpstan/phpstan`

### 5.4 Hinweis: Vor Produktionsbereitstellung alle Schlüssel ändern
Die Platzhalter-Schlüssel in `.env.docker` müssen vor der Produktionsbereitstellung durch zufällig generierte Werte ersetzt werden:
- `JWT_SECRET_KEY`
- `HASHIDS_SALT`, `HASHIDS_ALT_SALT`
- `ENCRYPTION_KEY`, `ENCRYPTABLE_KEY`
- `DB_PASSWORD`

---

## VI. Dokumentergebnisse

| Dokument | Pfad |
|------|------|
| Service-Sicherheitsarchitektur | `service/docs/SECURITY.md` |
| Admin-Sicherheitsarchitektur | `admin/docs/SECURITY.md` |
| Dieser Audit-Bericht | `docs/SECURITY-AUDIT-REPORT.md` |

---

## VII. Audit-Schlussfolgerung

**Gesamtbewertung des Sicherheitsschutzes: gut**

- Vollständige Verteidigung-in-der-Tiefe-Schichten (Nginx → WAF → Rate Limit → Auth → RBAC)
- 31 Angriffsdetektoren global abgedeckt, 28 im Unterbrechungsmodus
- JWT + Blacklist + Kontosperre + IP-Blacklist mehrschichtiger Authentifizierungsschutz
- AES-256-CBC-Verschlüsselung + Hashids-Verschleierung auf Datenebene
- Drei Kernprobleme auf der Service-Seite behoben: fehlende Sicherheits-Response-Header, fehlende Login-Sperre, fehlendes WAF-Paket
- Die Empfehlungen sind Produktionskonfigurations-Optimierungen, keine Sicherheitslücken

---

## VIII. Reparaturrunde 2026-08-26 (Sicherheitshärtung)

| Punkt | Reparaturinhalt |
|----|---------|
| Bestell-Manipulationsschutz | OrderController::store() Bestellpositionspreise ausschließlich aus der Datenbank (service→appointment_service, product→appointment_product), Client-Preise fließen nicht in die Berechnung ein; unbekannter target_type 422; target_id muss hashid sein (raw id zu 0 dekodiert → 422 „Produkt nicht vorhanden oder nicht mehr im Angebot"); Gruppen-/Blitzpreise ebenfalls DB-basiert |
| Blitzangebot-Lagerbestand vereinheitlicht | Lagerbestand einheitlich in /api/order store() innerhalb der Transaktion per Zeilensperre abgezogen; SeckillController::buy reserviert keinen Lagerbestand mehr (Redis-Aktivitätssperre + client_token Idempotenz bleiben); direkter /api/order-Aufruf mit seckill_id zieht ebenfalls ab |
| Techniker-Auszahlung | Bei Antrag wird das Guthaben um den unterwegs befindlichen Betrag (pending/approved) reduziert und reserviert; vor Freigabeüberweisung Nachprüfung settled−withdrawn−unterwegs ≥ Auszahlungsbetrag; parallele Freigaben verursachen keine Doppelauszahlung |
| Zahlungsrückmeldungen | WeChat-Rückmeldung vergleicht total_fee strikt mit dem fälligen Bestellbetrag, bei Abweichung Ablehnung; Alipay-Rückmeldungslogs maskiert (ohne buyer_id/seller_id usw.) |
| /install-Schutz | Nach erfolgreicher Installation .install.lock geschrieben, install-Schnittstelle prüft doppelt (Dateisperre + isInstalled); .gitignore ignoriert .install.lock |
| Abhängigkeitskonsolidierung | webman-scout einheitlich 2.0.5 (service/admin); opensearch-project/opensearch-php ^2.6 ergänzt; dompdf/security-php/webman-database exakt gepinnt („*"-Wildcard entfernt) |
| Engineering | service/app/common/StorageService.php gelöscht (toter Code); admin/app/common/ um TechnicianWithdrawalService/WechatPayService ergänzt (admin unabhängig bereitgestellt, hängt nicht von service-Code ab); phpstan.neon beider Apps repariert und lauffähig (php -d memory_limit=2G) |
