# Architekturbeschreibung

> Deutsche Übersetzung · Original: [中文](../ARCHITECTURE.md)

## Systemübersicht

Das Buchungsservice-System verwendet eine Architektur mit drei Endgeräten + zwei Diensten:

```
┌─────────────────────────────────────────────────────┐
│                    Nutzer-Endgeräteschicht           │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ WeChat-      │  │ Flutter APP  │                 │
│  │ Miniprogramm │  │ apps/flutter/│                 │
│  │ apps/wechat/ │  │              │                 │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │    funktional äquivalent  │                │
│         └────────┬─────────┘                         │
│                  │ Kunde/Techniker Identitätswechsel │
├──────────────────┼──────────────────────────────────┤
│              Business-API-Schicht                    │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ service/ API │  │ admin/ API   │                 │
│  │ Port 8787    │  │ Port 8787    │                 │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │                  │                         │
│         └────────┬─────────┘                         │
│                  │ geteiltes MySQL/Redis/ES          │
├──────────────────┼──────────────────────────────────┤
│                  Datenschicht                        │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌──────────┐      │
│  │ MySQL  │ │ Redis  │ │  ES    │ │Drittanbieter│   │
│  └────────┘ └────────┘ └────────┘ └──────────┘      │
└─────────────────────────────────────────────────────┘
```

## Projektbestandteile

### service/ — Business-API-Service

Stellt alle Geschäftsschnittstellen für das WeChat-Miniprogramm und die Flutter APP bereit. webman v2, Port 8787.

**Modulaufteilung:**

| Modul | Pfad | Authentifizierung | Beschreibung |
|------|------|------|------|
| Öffentliche API | `api/` | keine | Login/Registrierung/Verifizierungscode/WeChat-Rückmeldung |
| Benutzermodul | `user/` | JWT | Profil/Adressen/Favoriten/Feedback/Werbung |
| Technikermodul | `technician/` | JWT+Techniker | Profil/Schichtplan/Arbeitsplatz/Verifizierung/Mitglieder/Einnahmen/Auszahlungen |
| Servicemodul | `service/` | gemischt | Kategorien/Leistungen/Suche/Filialen |
| Bestellmodul | `order/` | JWT | Warenkorb/Bestellung/Zahlung/Rückerstattung/Verifizierung/Bewertung (OrderController ist nach Geschäftsdomänen in 10 Traits aufgeteilt, Routen und Methodennamen unverändert) |
| Marketingmodul | `marketing/` | JWT | Gutscheine/Mitgliederkarten (Stempelkarten)/Punkte/Geschenkkarten/Mitgliedervorteile |
| Wallet-Modul | `wallet/` | JWT | Guthaben/Aufladen/Transaktionshistorie/Guthabenzahlung |
| Inhaltsmodul | `content/` | gemischt | Karussell/Ankündigungen/Benachrichtigungen |
| LBS-Modul | `lbs/` | öffentlich | Städte/Filialen in der Nähe |

### admin/ — Verwaltungsbackend

PC-Verwaltungsbackend. webman v2 + Flutter Web, Port 8787.

**Vorhandene Module:** Authentifizierung, Dashboard, Benutzerverwaltung, Rollen und Berechtigungen, Systemkonfiguration, Betriebsprotokolle, Datei-Upload, Sicherheitsschutz

**Modellverteilung:** `admin/app/model/` enthält nur 6 eigene Modelle (AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig); alle übrigen Modelle werden über Composer psr-4 (`app\model\` → `../service/app/model/`) mit der service-Version geteilt, um ein Driften doppelter Modelle zu vermeiden; die Basisklasse `support\Model` ist mit service ausgerichtet, die Beziehungsmethode `UserPointsExchange::user()` wurde in die service-Modellversion übernommen.

**Erweiterungsmodule:** Technikerverwaltung, Mitgliederverwaltung, Filialverwaltung, Service-/Produktverwaltung, Bestellverwaltung, Gutscheine, Mitgliederkarten, Auszahlungsprüfung, Bewertungsverwaltung, Berichtsstatistik, Finanzverwaltung, Inhaltsverwaltung, Systemeinstellungen

### apps/ — Frontend-Apps für Kunden

| Verzeichnis | Technologie | Plattform |
|------|------|------|
| `apps/wechat/` | Nativeres WeChat-Miniprogramm | WeChat |
| `apps/flutter/` | Flutter 3.x + GetX + Dio | iOS + Android |

## Kernkomponenten

### Snowflake ID

Alle Primärschlüssel werden von `erikwang2013/snowflake-php` generiert, BIGINT nicht autoinkrementierend, garantiert global eindeutig verteilt. `service/support/Model::nextId()` nutzt innerhalb des Prozesses eine einzelne Snowflake-Instanz; die `generateId()`-Kopien der 64 Modelle wurden entfernt (einheitliche Vererbung der Basisklassen-Implementierung).

### Hashids

IDs in API-Anfragen/-Antworten werden über `erikwang2013/hashids` codiert und nach außen als Hash-Strings ausgegeben.

### JWT-Authentifizierung

`erikwang2013/jwt-webman` Bearer Token, 7 Tage gültig, mit Refresh und Blacklist.

### Datenverschlüsselung

- **API-Ebene**: `erikwang2013/encryption` ver- und entschlüsselt sensible Daten
- **DB-Ebene**: Trait `erikwang2013/encryptable` ver- und entschlüsselt Felder automatisch

### Sicherheitsschutz

- `erikwang2013/security-php`: 31 Arten von Angriffserkennung
- `erikwang2013/poster-php`: Zufallsvalidierung bei sensiblen Operationen
- Login-Sperre: 5 Fehlversuche sperren für 15 Minuten
- Parallelitätslimit: maximal 3 gültige Token

### API-Dokumentation

`hg/apidoc` generiert OpenAPI-3.0-Spezifikationsdokumente, getrennt für Verwaltungs- und Kundenseite:

| Endgerät | Adresse | Beschreibung |
|------|------|------|
| Verwaltung | `admin/ GET /api/docs` | Verwaltungsbackend-API (JWT+RBAC) |
| Kunde | `service/ GET /api/docs` | Business-API (JWT Bearer) |

Die Dokumentation ist öffentlich zugänglich und kann in Swagger UI importiert werden, um interaktive Schnittstellendokumente anzuzeigen.

### Elasticsearch

`erikwang2013/webman-scout` synchronisiert Modelle automatisch mit ES und unterstützt Volltextsuche.

## Middleware-Ausführungskette

### service/-Middleware

```
Öffentliche API:  Cors → Security(31 Erkennungen) → RateLimit → ApiVersion → Controller
Benutzer-API:     Cors → Security → RateLimit → Auth(JWT) → Controller
Techniker-API:    Cors → Security → RateLimit → ApiVersion → Auth → TechnicianAuth → Controller
```

### admin/-Middleware

```
Öffentliche API:  Cors → Security → RateLimit → Controller
Verwaltungs-API:  Cors → Security → RateLimit → AdminAuth(JWT) → AdminPermission(RBAC) → OperationLog → Controller
Health-Check:     Cors → Security → RateLimit → Controller
```

## Datenfluss

### Anfragefluss

```
Client → Cors → Security → RateLimit → Auth(JWT) → [TechnicianAuth] → Controller
    → Model(encryptable ver-/entschlüsseln) → BaseController(hashids codieren) → JSON-Antwort
```

### Buchungsablauf

```
Dienst ansehen → Filiale/Techniker/Zeit wählen → Bestellung aufgeben → Redis sperrt Techniker 3 Minuten
    → WeChat-Zahlung → Techniker benachrichtigen → Dienst starten → Dienst abschließen → Bewerten → Bestellung abgeschlossen
```

## 8 Operations-Herkünfte

## Neueste Erweiterungen

| Kategorie | Funktion |
|------|------|
| Echtzeit | WebSocket-Push / Zahlungsrückmeldungen / APNs+FCM |
| Nachrichten | Abo-Nachrichten-Push (sendSubscribeMessage Bestell-Events 3 Szenarien) |
| Wallet | Guthabenaufladung / Guthabenzahlung / Rückerstattung lädt Guthaben auf |
| Filiale | Bluetooth-Druck / elektronische Siegel / Wartenummern-Ruf |
| Techniker | Online-Prüfung / Kurzvideo-Präsentation / Arbeitsplatz (today/records/start/complete) |
| Community | Beiträge/Kommentare/Likes/Prüfung |
| System | Mehrsprachigkeit (Chinesisch/Englisch) / automatische Bestellstornierung / Daten-Seeds |

Das `source`-Feld protokolliert die Operationsherkunft: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### Integration von Drittanbieter-Diensten

| Dienst | Klasse | Fähigkeit |
|------|------|------|
| WeChat-Zahlung | WechatPayService | Unified Order/Abfrage/Rückerstattung/Auszahlung auf WeChat-Guthaben |
| SMS | SmsService | Alibaba Cloud/Tencent Cloud Doppelkanal |
| Karten | MapService | Amap/Tencent Reverse-Geocoding/Entfernung/Navigation |
| Vorlagen-Nachrichten | WechatTemplateMessageService | Bestell-/Rückerstattungs-/Erinnerungs-Push + Abo-Nachrichten (sendSubscribeMessage Bestell-Events 3 Szenarien) |
