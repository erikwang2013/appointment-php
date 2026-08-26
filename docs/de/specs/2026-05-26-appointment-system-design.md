# Design-Spezifikation des Buchungsservice-Systems
> **Languages**: [中文](../../superpowers/specs/2026-05-26-appointment-system-design.md) · [English](../../en/specs/2026-05-26-appointment-system-design.md) · [한국어](../../ko/specs/2026-05-26-appointment-system-design.md) · [Русский](../../ru/specs/2026-05-26-appointment-system-design.md) · [Français](../../fr/specs/2026-05-26-appointment-system-design.md) · [Español](../../es/specs/2026-05-26-appointment-system-design.md) · [Português](../../pt/specs/2026-05-26-appointment-system-design.md) · [हिन्दी](../../hi/specs/2026-05-26-appointment-system-design.md) · [العربية](../../ar/specs/2026-05-26-appointment-system-design.md) · [বাংলা](../../bn/specs/2026-05-26-appointment-system-design.md) · [Bahasa Indonesia](../../id/specs/2026-05-26-appointment-system-design.md) · [日本語](../../ja/specs/2026-05-26-appointment-system-design.md)

> Deutsche Übersetzung · Original: [中文](../../superpowers/specs/2026-05-26-appointment-system-design.md)

## Überblick

Drei-Endpunkte-Buchungsservicesystem: Benutzerseite (WeChat-MiniProgramm + Flutter APP) + Techniker-Workbench (Identitätswechsel in derselben APP) + Verwaltungsbackend (PC Web).

## Architekturentscheidungen

| Entscheidung | Lösung |
|------|------|
| Backend-Architektur | `admin/` (Verwaltungsbackend-API) + `service/` (Business-API), beide Dienste teilen sich MySQL/Redis |
| Benutzer-MiniProgramm | Natives WeChat-MiniProgramm `apps/wechat/` |
| Benutzer-APP | Flutter `apps/flutter/` (iOS + Android) |
| Benutzeridentität | Einheitliches Konto, Kunden-/Techniker-Identität wechselbar |
| Verhältnis MiniProgramm zu APP | Funktional identisch, nur Plattformunterschiede |
| Verwaltungsbackend-Frontend | Erweiterung des bestehenden Flutter Web (`admin/apps/flutter/`) |
| Verwaltungsbackend-Backend | Erweiterung der Geschäftsmodule des bestehenden webman v2 (`admin/`) |
| Drittanbieterdienste | WeChat-Login/Zahlung/SMS/Karten — Anschlussplan vorgesehen |

## Systemarchitektur-Diagramm

```
┌──────────────────────────────────────────────────────────┐
│                    Benutzer-Endgeräteschicht               │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ WeChat-MiniProgramm│ │ Flutter APP       │              │
│  │ apps/wechat/      │  │ apps/flutter/     │              │
│  │ (natives WXML/WXSS)│ │ (iOS + Android)   │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │         funktional identisch  │               │
│           └──────────┬──────────┘                        │
│                      │ Kunden-/Techniker-Identitätswechsel │
├──────────────────────┼──────────────────────────────────┤
│               Business-API-Gateway                         │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ service/ API      │  │ admin/ API        │              │
│  │ (webman v2)       │  │ (webman v2)       │              │
│  │ Benutzer/Order/Zahlung/│ Verwaltungsbackend-│            │
│  │ Techniker/Filiale/ │  │ Schnittstellen    │              │
│  │ Marketing...       │  │ (bestehend + Erw.)│              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │                      │                        │
│           └──────────┬───────────┘                        │
│                      │                                    │
├──────────────────────┼──────────────────────────────────┤
│                   Datenschicht                             │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────────────┐    │
│  │ MySQL  │ │ Redis  │ │  ES    │ │ Drittanbieter-  │    │
│  │ 8.0    │ │ Cache/ │ │ Suche  │ │ Dienste         │    │
│  │        │ │ Limit/ │ │        │ │ WeChat/SMS/Karte│    │
│  │        │ │ Session│ │        │ │ (Anschluss vor- │    │
│  │        │ │        │ │        │ │  gesehen)       │    │
│  └────────┘ └────────┘ └────────┘ └────────────────┘    │
└──────────────────────────────────────────────────────────┘
```

## Kern-Datenbanktabellen

Alle Tabellen verwenden das `erik_`-Präfix, BIGINT nicht autoinkrementierende Primärschlüssel (Snowflake-generiert). Sensible Felder werden über das encryptable-Trait ver-/entschlüsselt.

### Benutzer- und Identitätsdomäne

| Tabellenname | Beschreibung | Kernfelder |
|------|------|----------|
| `erik_user` | Einheitliche Benutzertabelle | phone, password, wx_openid, wx_unionid, avatar, nickname, user_type(customer/technician), status. Technician-Benutzer besitzen gleichzeitig Kundenfunktionen und können die aktuell aktive Identität frei wechseln |
| `erik_user_address` | Benutzeradresse | user_id, contact_name, contact_phone, province, city, district, detail, is_default |
| `erik_technician_profile` | Technikerprofil | user_id, real_name, gender, id_card, id_card_front, id_card_back, avatar, rating, order_count, status(pending/approved/rejected), intro |
| `erik_technician_schedule` | Techniker-Schichtplan | technician_id, date, time_slots(JSON), status |
| `erik_technician_service` | Vom Techniker angebotene Leistungen | technician_id, service_id |
| `erik_technician_earnings` | Techniker-Einnahmentransaktionen | technician_id, order_id, type(commission/bonus/penalty), amount, status |
| `erik_technician_withdrawal` | Techniker-Auszahlungsprotokoll | technician_id, amount, actual_amount, commission_fee, account_info, status, reviewed_at |
| `erik_technician_attendance` | Techniker-Anwesenheit | technician_id, date, check_in_at, check_out_at, clean_photo |
| `erik_technician_member_note` | Mitgliedsprofil | technician_id, user_id, content, written_at |

### Leistungs- und Produktdomäne

| Tabellenname | Beschreibung | Kernfelder |
|------|------|----------|
| `erik_service_category` | Leistungskategorie | name, icon, parent_id, sort, status |
| `erik_service` | Leistungsposition | category_id, name, description, cover_image, images(JSON), price, duration, sales_volume, specs(JSON), status |
| `erik_product` | Produkt | category_id, name, cover_image, price, stock, sales_volume, type, status |
| `erik_store` | Filiale | name, address, lat, lng, phone, business_hours(JSON), images, status |

### Bestelldomäne

| Tabellenname | Beschreibung | Kernfelder |
|------|------|----------|
| `erik_order` | Bestellhaupttabelle | order_no, user_id, technician_id, store_id, total_amount, discount_amount, paid_amount, status, service_time, cancel_reason, remark |
| `erik_order_item` | Bestellpositionen | order_id, service_id, product_id, type, name, price, quantity, spec_info |
| `erik_order_payment` | Zahlungsprotokoll | order_id, pay_type(wechat), transaction_id, amount, status, paid_at |
| `erik_order_refund` | Rückerstattungsprotokoll | order_id, payment_id, refund_no, amount, ratio, reason, status |
| `erik_order_review` | Leistungsbewertung | order_id, user_id, technician_id, rating, content, images |
| `erik_order_verification` | Verifizierungsprotokoll | order_id, code, verified_at, verified_by, location |

### Marketingdomäne

| Tabellenname | Beschreibung | Kernfelder |
|------|------|----------|
| `erik_coupon` | Gutscheindefinition | name, type, amount, min_amount, total_qty, remain_qty, start_at, end_at, status |
| `erik_user_coupon` | Benutzergutschein | user_id, coupon_id, status(available/used/expired), used_at |
| `erik_member_card` | Mitgliederkartendefinition | name, type(month/vip/times), price, duration_days, total_times, services(JSON) |
| `erik_user_member_card` | Benutzer-Mitgliederkarte | user_id, card_id, start_at, end_at, total_times, used_times, status |
| `erik_member_card_usage` | Stempelkarten-Nutzungsprotokoll | user_card_id, order_id, service_id, used_at |
| `erik_user_points` | Punkte-Transaktionen | user_id, type(earn/use), points, source, order_id |
| `erik_gift_card` | Geschenkkarte | code, type, amount_or_gift, status, used_by, used_at |
| `erik_user_referral` | Benutzer-Empfehlung | referrer_id, referred_user_id, reward_type, reward_amount, registered_at, first_order_at |

### Inhalts- und Benachrichtigungsdomäne

| Tabellenname | Beschreibung | Kernfelder |
|------|------|----------|
| `erik_banner` | Karussellbild | position, image, jump_type(url/detail/none), jump_value, sort, status |
| `erik_announcement` | Ankündigung | content, status, published_at |
| `erik_platform_agreement` | Plattformvereinbarung | type(user_agreement/privacy_policy/service_agreement), title, content, version |
| `erik_faq` | Häufige Fragen | title, content, sort |
| `erik_feedback` | Feedback | user_id, content, images, handler_reply, status(pending/handled) |
| `erik_moment` | Momente-Beitrag | content, images, published_at |
| `erik_notification` | Benachrichtigung | user_id, type(order/system), title, content, is_read, created_at |

### Finanzdomäne (admin-Seite)

| Tabellenname | Beschreibung | Kernfelder |
|------|------|----------|
| `erik_finance_transaction` | Einnahmen-/Ausgabentransaktionen | user_id, order_id, type, direction(income/expense), amount, actual_amount, commission, status |
| `erik_technician_commission_config` | Provisionskonfiguration | technician_id, commission_rate, settlement_cycle |
| `erik_withdrawal_account` | Auszahlungskonto | user_id, type(wechat), account_name, account_no |
| `erik_withdrawal_config` | Auszahlungslimit-Konfiguration | min_amount, reserve_amount, round_to_hundred |

## Service-API-Module

### Öffentliche API (ohne Authentifizierung)
- **AuthController** — Login/Registrierung/Passwort vergessen/Gastmodus/Identitätswechsel
- **CaptchaController** — SMS-Verifizierungscode
- **WechatController** — WeChat-Autorisierung/Login/Zahlungs-Callback
- **CommonController** — Vereinbarungstexte/Über uns/Version

### Benutzermodul `user/` (Authentifizierung erforderlich)
- **ProfileController** — Persönliche Informationen/Passwort ändern/Telefon neu binden/Konto löschen
- **AddressController** — Lieferadressen-CRUD
- **FavoriteController** — Favoriten
- **FeedbackController** — Feedback
- **ReferralController** — Empfehlung/Liste empfohlener Benutzer

### Technikermodul `technician/` (Techniker-Identität + TechnicianAuth-Middleware erforderlich)
- **ProfileController** — Technikerprofil/Aufnahmeantrag
- **ScheduleController** — Schichtplaneinstellung
- **OrderController** — Gebucht und nicht verifiziert/abgeschlossen/QR-Verifizierung
- **MemberController** — Meine Mitglieder/Mitgliedsprofile
- **EarningsController** — Einnahmen/Unterwegs befindliches Guthaben
- **WithdrawalController** — Auszahlung
- **AttendanceController** — Anwesenheit/Hygienefotos

### Leistungsmodul `service/`
- **CategoryController** — Leistungskategorien
- **ItemController** — Leistungs-/Produktlisten und -details
- **SearchController** — Suche
- **StoreController** — Filialliste/-details

### Bestellmodul `order/` (Authentifizierung erforderlich)
- **CartController** — Warenkorb
- **OrderController** — Bestellung aufgeben/Bestellliste/Details/Stornierung
- **PaymentController** — Zahlung/Rückerstattung
- **VerificationController** — QR-Code-Verifizierung
- **ReviewController** — Bewertung

### Marketingmodul `marketing/` (Authentifizierung erforderlich)
- **CouponController** — Gutscheinliste/Einlösen/Verwenden
- **MemberCardController** — Mitgliederkarte/Stempelkarte
- **PointsController** — Punkte
- **GiftCardController** — Geschenkkarte

### Inhaltsmodul `content/`
- **BannerController** — Karussellbilder
- **AnnouncementController** — Ankündigungen
- **NotificationController** — Benachrichtigungen

### LBS-Modul
- **LocationController** — Standort/Stadtwechsel/Filialen in der Nähe

### Allgemeine Fähigkeiten `common/`
- SnowflakeService — ID-Generierung
- HashidsService — ID-Ver-/Entschlüsselung
- EncryptionService — Ver-/Entschlüsselung sensibler Daten
- WechatPayService — WeChat-Zahlung (vorgesehen)
- WechatAuthService — WeChat-Login (vorgesehen)
- SmsService — SMS-Dienst (vorgesehen)
- MapService — Kartendienst (vorgesehen)

### Middleware
- Auth — JWT-Authentifizierung (teilt das Paket erikwang2013/jwt-webman mit admin)
- TechnicianAuth — Techniker-Identitätsprüfung
- RateLimit — Ratenbegrenzung (mit admin geteilt)

## Admin-Verwaltungsbackend-Erweiterung

Auf Basis des bestehenden Frameworks neue Controller:

### Technikerverwaltung
- **TechnicianController** — Technikerliste/Suche/Export/Prüfung/Schichtplanverwaltung/Technische-Leistungen-Einstellung/Kursfortschritt

### Benutzerverwaltungs-Erweiterung
- **MemberController** — Mitgliederliste/Stufenfestlegung/Konsumstatistik

### Filialverwaltung
- **StoreController** — Filial-CRUD/Aktivieren-Deaktivieren

### Leistungsverwaltung
- **ServiceController** — Leistungsliste/CRUD/Kartendesign
- **ServiceCategoryController** — Kategorienverwaltung
- **ProductController** — Produktliste/CRUD

### Shop-Verwaltung
- **MallOrderController** — Shop-Bestellungen/Versand/After-Sales/Bewertungen
- **SalesStatsController** — Verkaufsstatistik

### Bestellverwaltung
- **AppointmentOrderController** — Bestellungen zur Nutzung ausstehend/Stornierung/Erledigung bestätigen

### Gutscheinaktionen
- **CouponController** — Gutschein-CRUD/Ausgabe

### Finanzverwaltung
- **FinanceController** — Bestell-Umsatzbeteiligung/Einnahmen-Ausgaben-Transaktionen
- **WithdrawalController** — Techniker-Auszahlungsprüfung/Abschluss
- **CommissionController** — Provisionsfestlegung/Belohnung-Strafe/Guthabenabfrage
- **WithdrawalAccountController** — Auszahlungskontenverwaltung
- **WithdrawalConfigController** — Auszahlungslimit-Konfiguration

### Inhaltsverwaltung
- **BannerController** — Karussell-CRUD
- **AnnouncementController** — Ankündigungs-CRUD
- **FaqController** — FAQ-CRUD
- **FeedbackController** — Feedback-Bearbeitung
- **MomentController** — Momente-Prüfung
- **AgreementController** — Vereinbarungsbearbeitung (Nutzungs-/Datenschutz-/Leistungsvereinbarung)
- **AboutController** — Über-uns-Einstellung

### Einstellungen
- **SystemMessageController** — Systemnachrichten-Einstellung
- **AdminUserController** — Unterkontenverwaltung (auf Basis des bestehenden RBAC)

### Dashboard-Erweiterung
- Echtzeit-Statistikkarten: Benutzerzahl/Gesamtbestellungen/Technikerzahl/Leistungsbestellungen
- Liniendiagramme: Bestellmenge/Betrag/täglich neue Benutzer/Aktivität
- Schnellnavigation: Schaltflächen für ausstehende Module
- Interne Nachrichten: neue Bestellbenachrichtigungen/Rückerstattungsbenachrichtigungen

## Seitenstruktur der Benutzerseite

WeChat-MiniProgramm und Flutter APP sind funktional identisch.

### auth/ — Authentifizierung
- login — Login (Telefonnummer/Verifizierungscode/WeChat/Gast-Einstieg)
- register — Registrierung (Telefonnummer + Verifizierungscode + Passwort + Empfehlungscode)
- forget-password — Passwort vergessen
- agreement — Vereinbarungen ansehen

### home/ — Startseite
- index — Startseite (Karussell + Ankündigungen + Leistungskategorien + Empfehlungen)
- search — Suchseite

### service/ — Leistungen
- list — Leistungsliste (nach Kategorie gefiltert)
- detail — Leistungsdetails (Basisinformationen + Bewertungen + Sofort buchen)
- product-list — Produktliste

### order/ — Bestellungen
- confirm — Bestellung bestätigen (Filiale/Techniker/Zeit/Gutschein/Notiz/Vereinbarung)
- payment — Zahlungsseite
- payment-success — Zahlung erfolgreich
- list — Alle Bestellungen (nach Status-Tab gefiltert)
- detail — Bestelldetails
- review — Leistungsbewertung
- verification — QR-Code-Verifizierung

### cart/ — Warenkorb
- index — Warenkorbliste

### technician/ — Techniker (Kundenperspektive)
- list — Technikerliste (nach Entfernung nah bis fern sortiert)
- detail — Technikerdetails (Bewertungen/anbietbare Leistungen/sofort buchen)
- apply — Techniker-Aufnahmeantrag

### tech-work/ — Techniker-Workbench (Techniker-Identität)
- index — Workbench-Startseite (heutige Bestellungen/Einnahmenübersicht)
- schedule — Schichtplaneinstellung
- order-list — Meine Bestellungen (gebucht und nicht verifiziert/abgeschlossen)
- scan-verify — QR-Verifizierung
- member-list — Meine Mitglieder
- member-detail — Mitgliederdetails/Profilbearbeitung
- earnings — Meine Einnahmen
- withdrawal — Auszahlung
- transaction-list — Transaktionsdetails
- attendance — Anwesenheit/Hygienefoto-Upload
- training — Berufliche Weiterbildung

### user/ — Persönliches Zentrum
- index — Persönliche Informationen (Avatar/Nickname/Mitgliederkarte/Favoriten/Gutschein-Einstiege)
- settings — Einstellungen (Passwort ändern/Telefon neu binden/Vereinbarungen/Update/Konto löschen/Abmelden)
- switch-role — Identitätswechsel (Kunde ↔ Techniker)

### marketing/ — Marketing
- coupon-list — Gutscheinliste
- member-card — Meine Mitgliederkarte
- points — Meine Punkte
- gift-card — Meine Geschenkkarte
- referral — Empfehlung (Erklärung + QR-Code-Plakat + Liste empfohlener Benutzer)

### Weitere Seiten
- message/ — Nachrichtenliste/-details
- store/list, store/detail — Filialliste (LBS-Sortierung)/Details (Navigation)
- other/about — Über uns
- other/feedback — Feedback
- other/official-account — Offiziellem Konto folgen

### Gemeinsame Komponenten
- navbar, tabbar, service-card, technician-card
- coupon-popup, lbs-selector, empty-state, loading

### Identitätswechsel-Logik
- Kunden-Identität untere Navigation: Startseite / Leistungen / Warenkorb / Bestellungen / Mein
- Techniker-Identität untere Navigation: Workbench / Bestellungen / Mitglieder / Einnahmen / Mein
- Die Seite „Mein" bietet den Einstieg zum Identitätswechsel
- Benutzer, die noch keine Techniker sind, werden beim Wechsel zur Techniker-Identität zur Aufnahmeantrag-Seite geführt

## Kaufablauf-Erläuterung

Das System hat zwei verschiedene Kaufabläufe:

### Leistungsbuchungsablauf (direkte Bestellung, ohne Warenkorb)
- Detailseite der Leistung → Bestellung bestätigen (Filiale/Techniker/Zeit wählen) → Zahlung → Verifizierung
- Exklusive Technikerressource: Beim Betreten der Bestellbestätigungsseite wird der Techniker 3 Minuten lang gesperrt
- Für Offline-Leistungen wie Massage, Beauty usw.

### Produktkaufablauf (Warenkorb-Modus)
- Produktliste → In den Warenkorb → Warenkorb bestätigen → Bestellung absenden → Zahlung → Versand/Empfang
- Mengenänderung und Artikel-Löschung unterstützt
- Für physische Artikel oder Kartenverkauf

## Kern-Geschäftsregeln

### Techniker-Sperrmechanismus
- Nicht mehrere Personen können gleichzeitig denselben Techniker buchen
- Beim Betreten der Bestellbestätigungsseite wird der Techniker per Redis SETNX 3 Minuten gesperrt
- Beim Verlassen der Buchungsseite oder Timeout automatische Freigabe der Sperre

### Rückerstattungsregeln
| Bedingung | Rückerstattungsanteil |
|------|----------|
| Innerhalb 15 Minuten nach Bestellung oder >6 Stunden vor Beginn | 100 % |
| ≤6 Stunden vor Beginn | 90 % |
| Begonnen, aber Leistung nicht bestätigt | 80 % |
| Nach bestätigtem Leistungsbeginn | 0 % (keine Rückerstattung) |

### Rabattregeln
- Nebenzeiten (10-12 Uhr/17-18 Uhr/nach 21:00 Uhr) 9 % Rabatt
- Buchung 30 Minuten im Voraus 95 % (nicht mit Gutscheinen stapelbar)

### Techniker-Auszahlung
- Auszahlung am 20. jedes Monats möglich, T+1 Werktag auf dem Konto
- Auszahlung in WeChat-Guthaben unterstützt
- Verifizierte, noch nicht abgerechnete Bestellungen werden vom System innerhalb von 3 Tagen automatisch bestätigt
- Mitgliedsprofil muss innerhalb von 24 Stunden ausgefüllt werden, sonst keine Provision

### Stammkunden-Belohnung
- 2. Konsum beim selben Techniker innerhalb von 30 Tagen → Bonus wird protokolliert
- Nach der Leistung Hygienefoto hochladen

### Punkteregeln
- 1:100 Einlösung für Geschenkkarten (im Backend konfigurierbar)
- Nach erfolgreicher Registrierung und Bestellung empfohlener Benutzer werden festgelegte Punkte vergeben (Backend-Einstellung)
