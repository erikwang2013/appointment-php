# Architekturdesign

> Deutsche Übersetzung · Original: [中文](../ARCHITECTURE-DESIGN.md)

## Schichtenarchitektur

```
┌─────────────────────────────────────────┐
│            Präsentationsschicht          │
│  WeChat-Miniprogramm / Flutter APP / Flutter Web │
├─────────────────────────────────────────┤
│              Routenschicht               │
│  config/route.php — Routengruppen + Middleware-Bindung │
├─────────────────────────────────────────┤
│            Middleware-Schicht            │
│  Cors → Security → RateLimit → Auth      │
│  → TechnicianAuth → OperationLog         │
├─────────────────────────────────────────┤
│            Controllerschicht             │
│  BaseController → Business-Controller    │
├─────────────────────────────────────────┤
│              Serviceschicht              │
│  common/ — Snowflake/Hashids/Encryption  │
├─────────────────────────────────────────┤
│              Model-Schicht               │
│  Eloquent ORM + Encryptable + Scout      │
├─────────────────────────────────────────┤
│               Datenschicht               │
│  MySQL / Redis / Elasticsearch           │
└─────────────────────────────────────────┘
```

## Middleware-Design

### Ausführungskette

```
Cors → Security(31 Angriffserkennungen) → RateLimit → Auth(JWT+Benutzerstatus)
    → [TechnicianAuth(Technikeridentität)] → [AdminPermission(RBAC)] → [OperationLog(8 Herkünfte)]
    → Controller
```

### Middleware-Aufgaben

| Middleware | Gültigkeitsbereich | Funktion |
|--------|--------|------|
| Cors | global | OPTIONS-Preflight + CORS-Antwortheader |
| Security | global | erikwang2013/security-php, 31 Angriffserkennungen |
| RateLimit | global | Redis Sliding Window + atomar per Lua |
| Auth | Routengruppe | JWT-Parsing + Existenz-/Statusprüfung des Benutzers |
| TechnicianAuth | Routengruppe | Technikerprofil-Abfrage + approved-Statusprüfung |
| AdminAuth | Routengruppe | Admin-JWT-Authentifizierung + Blacklist |
| AdminPermission | Routengruppe | RBAC-Berechtigungsprüfung, Redis 60-s-Cache |
| OperationLog | Routengruppe | Betriebsprotokoll + automatische Erkennung der 8 Herkünfte |

### Rate-Limit-Strategie

| Schnittstelle | Limit |
|------|------|
| Standard | 60 pro Minute/IP |
| Login | 10 pro Minute |
| Registrierung | 5 pro Minute |
| Verifizierungscode | 1 pro 60 s/Telefonnummer |

## Prinzipien des Datenbankdesigns

### Primärschlüssel-Strategie

- Alle Primärschlüssel: BIGINT UNSIGNED NOT NULL, nicht autoinkrementierend
- Generiert von `erikwang2013/snowflake-php` auf Anwendungsebene
- Model: `$incrementing = false`, `$keyType = 'string'`

### Tabellenpräfix

Einheitliches `erik_`-Präfix, konfiguriert in `config/database.php`. Modelle schreiben den ursprünglichen Tabellennamen, das ORM fügt das Präfix automatisch hinzu.

### Verschlüsselung sensibler Felder

Verwendung des Traits `erikwang2013/encryptable`:

```php
use Erikwang2013\Encryptable\Encryptable;

class User extends Model
{
    use Encryptable;
    protected array $encryptable = [
        'phone', 'wx_openid', 'wx_unionid', 'real_name',
    ];
}
```

Die Länge verschlüsselter Felder ist auf VARCHAR 500 gesetzt (aufgrund der Größenexpansion durch Verschlüsselung).

### Soft Deletes und Zeitstempel

- Eloquent SoftDeletes: `deleted_at` DATETIME DEFAULT NULL
- Alle Tabellen enthalten `created_at` + `updated_at`

## API-ID-Ver- und Entschlüsselungsmechanismus

### Anfrage: decodeIds()

Das Frontend sendet hashids-codierte IDs → der Controller ruft `$this->decodeIds($request->all())` zur Dekodierung auf.

### Antwort: encodeIds()

IDs der DB-Abfrageergebnisse → `BaseController::success()` ruft automatisch `encodeIds()` auf → gibt Hashids-Strings zurück.

### Regeln

Felder mit dem Schlüsselnamen `id` oder Endung `_id` in Arrays werden rekursiv verarbeitet.

## Sicherheitsdesign

### Verteidigung in der Tiefe

```
WAF → Cors → Security(31 Erkennungen) → RateLimit → Auth(JWT+Status)
    → [Identitätsprüfung] → [RBAC] → Controller(Model-Verschlüsselung) → Antwort
```

### Authentifizierungssicherheit

- Passwort: bcrypt-Hash
- JWT: 7 Tage gültig + Refresh + Blacklist
- Sperre: 5 Fehlversuche → 15 Minuten
- Parallelität: maximal 3 Token

### Datensicherheit

- API-Ebene: erikwang2013/encryption
- DB-Ebene: Trait erikwang2013/encryptable
- Protokolle: sensible Daten landen nicht in Protokollen

### Operationssicherheit

- erikwang2013/poster-php: Validierung vor Löschen/Prüfung/Auszahlung
- Security-Middleware: XSS/SQL-Injection/CSRF/Pfad-Traversal-Erkennung

## Elasticsearch-Integration

`erikwang2013/webman-scout` synchronisiert Modelle automatisch mit ES:

```php
use Erikwang2013\WebmanScout\Searchable;

class Service extends Model
{
    use Searchable;
    public function searchableAs(): string { return 'erik_services'; }
}
```

## Excel/PDF-Export

- Excel: PhpSpreadsheet, sensible Felder werden automatisch maskiert
- PDF: Dashboard-Visualisierungsexport

## Erkennung der 8 Herkünfte

OperationLog analysiert über den User-Agent:

```
iPad → iPadOS / Mac → macOS / Windows → Windows
Linux → Linux / iPhone → ios / Android → android
HarmonyOS → harmonyOS / sonstiges → web
```

## TDD-Tests

| Projekt | Testanzahl | Status |
|------|--------|------|
| admin/ | 60 | ✅ bestanden |
| service/ | 21 | ✅ bestanden |
| Gesamt | 81 | ✅ |

Testabdeckung: Rückerstattungsregeln / Bestellstatus / Hashids / Warteschlangensystem / Verschlüsselung / Verifizierungscodes
