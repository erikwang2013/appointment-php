# Buchungsservice-System — Projektstruktur
> **Languages**: [中文](../STRUCTURE.md) · [English](../en/STRUCTURE.md) · [한국어](../ko/STRUCTURE.md) · [Русский](../ru/STRUCTURE.md) · [Français](../fr/STRUCTURE.md) · [Español](../es/STRUCTURE.md) · [Português](../pt/STRUCTURE.md) · [हिन्दी](../hi/STRUCTURE.md) · [العربية](../ar/STRUCTURE.md) · [বাংলা](../bn/STRUCTURE.md) · [Bahasa Indonesia](../id/STRUCTURE.md) · [日本語](../ja/STRUCTURE.md)

> Deutsche Übersetzung · Original: [中文](../STRUCTURE.md)

## Repository-Übersicht

```
appointment-php/
├── admin/              # Verwaltungsbackend (webman v2 + Flutter Web)
├── service/            # Business-API-Service (webman v2)
├── apps/               # Frontend-Apps für Kunden
│   ├── wechat/         #   WeChat-Miniprogramm (nativ)
│   ├── flutter/        #   Flutter APP (iOS + Android)
│   └── harmonyos/      #   HarmonyOS APP (HarmonyOS-nativ)
├── docs/               # Projektdokumentation
└── .claude/            # Claude-Code-Konfiguration
```

## Projektbeziehungen

```
┌──────────────────────────────────────────────┐
│                   apps/                       │
│  ┌─────────────┐  ┌──────────┐  ┌─────────┐  │
│  │ wechat/      │  │ flutter/  │  │harmonyos/│  │
│  │ WeChat-      │  │iOS/Android│  │HarmonyOS│  │
│  │ Miniprogramm │  │           │  │  APP    │  │
│  └──────┬──────┘  └────┬─────┘  └────┬────┘  │
│         │    funktional identisch    │        │
│         └──────────┬─────────┘                │
│                    │ HTTP-API                 │
├────────────────────┼─────────────────────────┤
│              service/                         │
│         Business-API (webman v2)              │
│              Port: 8787                       │
│                    │                          │
│                    │ geteiltes MySQL/Redis/ES │
│                    │                          │
│              admin/                           │
│     Verwaltungsbackend-API (webman v2)        │
│              Port: 8787                       │
│                    │                          │
│         ┌──────────┴──────────┐               │
│         │                     │               │
│    admin/apps/flutter/    Flutter Web         │
│    Verwaltungsbackend-Frontend (PC)           │
└──────────────────────────────────────────────┘
```

## admin/ — Verwaltungsbackend

```
admin/
├── app/
│   ├── admin/controller/       # Verwaltungs-Controller
│   │   ├── BaseController          # Basis-Controller
│   │   ├── DashboardController     # Dashboard
│   │   ├── UserController          # Benutzerverwaltung
│   │   ├── RoleController          # Rollenverwaltung
│   │   ├── PermissionController    # Berechtigungsverwaltung
│   │   ├── ConfigController        # Systemkonfiguration
│   │   ├── LogController           # Betriebsprotokolle
│   │   ├── ProfileController       # Profilzentrum
│   │   ├── ExportController        # Export
│   │   ├── ImportController        # Import
│   │   ├── UploadController        # Datei-Upload
│   │   ├── HealthController        # Health-Check
│   │   ├── DocsController          # API-Dokumentation
│   │   ├── MetricsController       # Prometheus-Metriken
│   │   │                            # ✅ Implementierte Geschäftsmodule:
│   │   ├── TechnicianController    #   Technikerverwaltung (Liste/Prüfung/Schichtplan/Export)
│   │   ├── MemberController        #   Mitgliederverwaltung (Stufe/Konsum)
│   │   ├── StoreController         #   Filial-CRUD
│   │   ├── ServiceController       #   Serviceleistungs-CRUD
│   │   ├── ServiceCategoryController # Servicekategorie-CRUD (Baumstruktur)
│   │   ├── ProductController       #   Produkt-CRUD
│   │   ├── MallOrderController     #   Shop-Bestellungen/Versand/Kundendienst
│   │   ├── SalesStatsController    #   Verkaufsstatistik (Redis-Cache)
│   │   ├── AppointmentOrderController  # Buchungsbestellungen (Stornieren/Abschließen)
│   │   ├── MemberCardController    #   Mitgliederkarten-Definitions-CRUD
│   │   ├── ReviewController        #   Servicebewertungsverwaltung
│   │   ├── ReportController        #   Datenberichtsstatistik
│   │   ├── CouponController        #   Gutschein-CRUD
│   │   ├── FinanceController       #   Finanzbuchungen/Statistik
│   │   ├── WithdrawalController    #   Auszahlungsprüfung (Genehmigen/Ablehnen/Abschließen)
│   │   ├── CommissionController    #   Provisions-Einstellungen/Boni und Strafen
│   │   ├── WithdrawalAccountController # Auszahlungskontenverwaltung
│   │   ├── WithdrawalConfigController  # Auszahlungslimit-Konfiguration
│   │   ├── BannerController        #   Karussell-CRUD
│   │   ├── AnnouncementController  #   Ankündigungs-CRUD/Veröffentlichung
│   │   ├── FaqController           #   FAQ-CRUD
│   │   ├── FeedbackController      #   Feedback/Antworten
│   │   ├── MomentController        #   Moments-Prüfung
│   │   ├── AgreementController     #   Vereinbarungs-Editierung/Veröffentlichung
│   │   ├── AboutController         #   Über-uns-Einstellungen
│   │   └── SystemMessageController #   Systemnachrichten-Vorlagen/Versand
│   │   │                            # ✅ Erweiterungsmodule:
│   │   ├── ServiceCardController    #   Kartendesign
│   │   ├── SystemMonitorController  #   Systemmonitor
│   │   ├── IpBlacklistController    #   IP-Blacklist-Verwaltung
│   │   ├── DbBackupController       #   Datenbanksicherung
│   │   ├── SmsConfigController      #   SMS-Konfiguration
│   │   ├── StorageConfigController  #   Speicherkonfiguration
│   │   ├── StoreManagerController   #   Filialleiter-Konto
│   │   ├── TrainingController       #   Techniker-Schulung
│   │   ├── ScheduledTaskController  #   Geplante Aufgaben
│   │   ├── CustomerProfileController #  Kundenprofil
│   │   ├── BatchMessageController   #   Batch-Push
│   │   ├── RefundWorkflowController #   Rückerstattungsprüfung
│   │   ├── TechnicianTierController #   Technikerstufen
│   │   │                            # ✅ Neu in Runde 22–25:
│   │   ├── FullReductionController  #   Rabatt ab Mindestbetrag
│   │   ├── AttendanceController     #   Techniker-Anwesenheit
│   │   ├── ProfitSharingController  #   WeChat-Profit-Sharing
│   │   ├── LuckyWheelController     #   Punkte-Glücksrad
│   │   ├── PointsExchangeGoodsController # Punkte-Einlöseartikel
│   │   ├── ReviewAuditController    #   Bewertungsbild-Prüfung
│   │   ├── InvoiceController        #   Elektronische Rechnungen
│   │   ├── TicketController         #   Kundenservice-Tickets
│   │   ├── ReferralRewardController #   Erststufen-Provisionen
│   │   ├── ReferralLevel2Controller #   Zweitstufen-Provisionen
│   │   ├── ReturnCustomerController #   Stammkunden-Belohnung
│   │   ├── SeckillController        #   Blitzangebots-Aktivitäten
│   │   ├── VersionController        #   APP-Versionsverwaltung
│   │   ├── TechnicianScheduleController # Schichtplanverwaltung/CSV-Export
│   │   ├── AftersaleController      #   Kundendienst-Bearbeitung
│   │   ├── OrderVerificationController # Verifizierungsprotokolle
│   │   ├── CommunityModerationController # Community-Prüfung
│   │   ├── VideoAuditController     #   Videoprüfung
│   │   └── InstallController        #   Installationsassistent
│   ├── api/v1/controller/      # Öffentliche API v1
│   │   ├── AuthController
│   │   └── CaptchaController
│   ├── common/                 # Gemeinsame Werkzeuge
│   │   ├── HashidsService
│   │   ├── SnowflakeService
│   │   ├── EncryptionService
│   │   ├── TechnicianWithdrawalService
│   │   └── WechatPayService
│   ├── middleware/             # Middleware
│   │   ├── Cors
│   │   ├── RateLimit
│   │   ├── ApiVersion
│   │   ├── AdminAuth
│   │   ├── AdminPermission
│   │   └── OperationLog
│   ├── model/                  # Datenmodelle (nur 6 eigene Modelle: AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig; übrige psr-4-geteilt mit service-Version)
│   ├── queue/                  # Queue-Aufgaben
│   └── process/                # Prozesse
├── apps/
│   ├── flutter/                # Flutter-Web-Verwaltungsbackend-Frontend
│   │   └── lib/app/
│   │       ├── pages/           #   Seiten (20)
│   │       │   ├── dashboard/   #   Dashboard
│   │       │   ├── login/       #   Login
│   │       │   ├── user/        #   Benutzerverwaltung
│   │       │   ├── member/      #   Mitgliederverwaltung
│   │       │   ├── role/        #   Rollen und Berechtigungen
│   │       │   ├── config/      #   Systemkonfiguration
│   │       │   ├── log/         #   Betriebsprotokolle
│   │       │   ├── profile/     #   Profilzentrum
│   │       │   ├── technician/  #   Technikerverwaltung
│   │       │   ├── schedule/    #   Schichtplan
│   │       │   ├── service/     #   Service-/Produktverwaltung
│   │       │   ├── service_card/#   Kartendesign
│   │       │   ├── order/       #   Bestellverwaltung
│   │       │   ├── verification/#   Verifizierungsprotokolle
│   │       │   ├── coupon/      #   Gutscheine
│   │       │   ├── withdrawal/  #   Auszahlungsprüfung
│   │       │   ├── report/      #   Berichtsstatistik
│   │       │   ├── review/      #   Bewertungsverwaltung
│   │       │   ├── announcement/#   Ankündigungen
│   │       │   └── faq/         #   FAQ
│   │       ├── services/        #   API-Serviceschicht
│   │       ├── layouts/         #   Layouts
│   │       └── theme/           #   Themes
│   ├── harmonyos/               # HarmonyOS-Verwaltung (ArkTS)
│   └── weixin/                  # WeChat-Verwaltung
├── config/                     # Konfigurationsdateien
│   ├── route.php
│   ├── middleware.php
│   ├── database.php
│   ├── jwt.php
│   ├── snowflake.php
│   ├── hashids.php
│   ├── encryption.php
│   ├── encryptable.php
│   └── ...
├── database/
│   └── backup/                 # Sicherungsskripte (Tabellenstruktur und Seeds zentral in docs/install.sql)
├── docs/                       # Verwaltungsbackend-Dokumentation
├── public/                     # Einstiegsdatei
├── runtime/                    # Laufzeit
├── tests/                      # Tests
├── vendor/                     # Abhängigkeiten
├── CLAUDE.md
├── composer.json
├── Dockerfile
└── docker-compose.yml
```

## service/ — Business-API

```
service/
├── app/
│   ├── api/v1/controller/       # Öffentliche API v1 (26 Controller)
│   │   ├── AuthController          # Login/Registrierung/Passwort vergessen/Refresh/Identitätswechsel
│   │   ├── CaptchaController       # SMS-Verifizierungscode (Redis-Rate-Limit)
│   │   ├── CommonController        # Gemeinsame Konfiguration/Vereinbarungen/Regionen
│   │   ├── ContentController       # Karussell/Ankündigungen/Artikel
│   │   ├── DocsController          # OpenAPI-Dokumentation (hg/apidoc)
│   │   ├── LbsController           # Filialen in der Nähe (Haversine)/Reverse-Geocoding
│   │   ├── GuestController         # Gastmodus (schreibgeschütztes Browsen ohne Login, Redis-Cache)
│   │   ├── SeckillController       # Blitzangebots-Aktivitäten/Kauf (eigener Kanal)
│   │   ├── PromotionController     # Gruppeneinkauf (alter flash_sale-Kanal eingestellt)
│   │   ├── ServiceController       # Servicekategorien/Leistungen/Produkte/Filialen
│   │   ├── ServicePackageController # Servicepakete
│   │   ├── StoreManagerController  # Filialleiter-Arbeitsplatz (overview/orders/technicians/revenue)
│   │   ├── TechnicianController    # Öffentliche Technikerinformationen
│   │   ├── BrowseHistoryController # Browserverlauf
│   │   ├── CalendarController      # Buchungskalender (Monats-/Tagesansicht)
│   │   ├── CommunityController     # Community-Momente
│   │   ├── CommunityCommentController # Community-Kommentare
│   │   ├── FullReductionController # Rabatt ab Mindestbetrag
│   │   ├── PaymentNotifyController # Zahlungsrückmeldungen (WeChat/Alipay)
│   │   ├── PrintController         # Drucken
│   │   ├── PrivacyController       # Datenschutz-Compliance (Datenexport/Löschung)
│   │   ├── QueueController         # Wartenummern-Ruf
│   │   ├── VersionController       # APP-Versionsverwaltung/Update-Prüfung
│   │   ├── VideoController         # Videos
│   │   ├── WechatController        # WeChat-bezogen
│   │   └── WheelController         # Punkte-Glücksrad
│   ├── user/v1/controller/      # Benutzermodul v1 (14 Controller)
│   │   ├── ProfileController       # Persönliche Daten/Passwort/Telefon/Löschung/Abmeldung
│   │   ├── AddressController       # Adress-CRUD (Standardadressverwaltung)
│   │   ├── FavoriteController      # Favoriten (Service/Techniker)
│   │   ├── FeedbackController      # Feedback (Text + Bilder)
│   │   ├── ReferralController      # Werbung/QR-Code/empfohlene Benutzer
│   │   ├── CheckInController       # Check-in
│   │   ├── DeviceController        # Benutzergeräteverwaltung
│   │   ├── GrowthController        # Wachstumsstufen (Übersicht/records/levels)
│   │   ├── HealthProfileController # Gesundheitsprofil
│   │   ├── InvoiceController       # Elektronische Rechnungen: Antrag/Liste/Details
│   │   ├── InvoiceTitleController  # Rechnungsanschrift-Bibliothek
│   │   ├── NotifySettingController # Nachrichteneinstellungen
│   │   ├── PointsTransferController# Punkte-Weitergabe
│   │   └── TicketController        # Kundenservice-Tickets
│   ├── technician/v1/controller/ # Technikermodul v1 (10 Controller)
│   │   ├── ProfileController       # Technikerprofil/Onboarding-Antrag
│   │   ├── ScheduleController      # Schichtplan-Abfrage/Einstellungen
│   │   ├── OrderController         # Techniker-Bestellliste
│   │   ├── WorkController          # Arbeitsplatz (today/records/start/complete)
│   │   ├── EarningController       # Einnahmenübersicht + Buchungen
│   │   ├── WithdrawController      # Auszahlungsantrag (am config('withdraw.gate_day')-Tag jedes Monats, konfigurierbar)
│   │   ├── ServiceRecordController # Serviceprotokolle
│   │   ├── ExamController          # Online-Prüfung
│   │   ├── AttendanceController    # Anwesenheitsstempelung
│   │   └── ReviewController        # Techniker-Antwort auf Bewertungen
│   ├── order/v1/controller/     # Bestellmodul v1 (8 Controller + 9 Traits)
│   │   ├── OrderController         # Bestellung aufgeben (Technikersperre)/Liste/Details/Stornierung/Zahlung/Rückerstattung/Verifizierung (Aggregat-Einstieg, 38 Zeilen, Methoden alle aus Traits)
│   │   ├── OrderCreateTrait        # Bestellungserstellung store/Preisberechnung (475 Zeilen)
│   │   ├── OrderQueryTrait         # Bestellabfrage Liste/Details/Logistik (205 Zeilen)
│   │   ├── OrderPayTrait           # Zahlung pay/Guthabenzahlung/Punkteabzug (415 Zeilen)
│   │   ├── OrderCancelTrait        # Bestellstornierung (272 Zeilen)
│   │   ├── OrderRefundTrait        # Rückerstattungsantrag (379 Zeilen)
│   │   ├── OrderCompensateTrait    # Rückerstattungskompensations-Scan + Gutschein-/Punkterückgabe (345 Zeilen)
│   │   ├── OrderVerifyTrait        # Verifizierung Provision/Punkte (256 Zeilen)
│   │   ├── OrderRescheduleTrait    # Buchungsumänderung (181 Zeilen)
│   │   ├── OrderNotifyTrait        # Benachrichtigungen Abo/Vorlage/In-App/WebSocket (195 Zeilen)
│   │   └── OrderLockTrait          # Werkzeuge für verteilte Sperren (80 Zeilen)
│   │   ├── AftersaleController     # Kundendienst
│   │   ├── CartController          # Warenkorb
│   │   ├── IcsController           # ICS-Kalenderexport
│   │   ├── ReviewController        # Bewertungen/Nachtrag
│   │   ├── SignatureController     # Signatur
│   │   ├── TimelineController      # Bestellstatus-Zeitachse
│   │   └── WaitlistController      # Warteliste
│   ├── wallet/v1/controller/    # Wallet-Modul v1 (2 Controller)
│   │   ├── WalletController        # Guthaben/Aufladen/Transaktionshistorie/Guthabenzahlung
│   │   └── WalletTransferController# Überweisung zwischen Benutzern
│   ├── marketing/v1/controller/ # Marketingmodul v1 (7 Controller)
│   │   ├── CouponController        # Gutscheinliste/Einlösen/Abzug bei Bestellung
│   │   ├── CardController          # Mitgliederkartenliste/Kauf/Stempelkarte my/use
│   │   ├── PointController         # Punktebuchungen/Konsumrückbuchung
│   │   ├── GiftCardController      # Geschenkkarten/Einlösen redeem
│   │   ├── MemberBenefitController # Mitgliedervorteile
│   │   ├── MemberCardController    # Mitgliederkarten-Definitionen
│   │   └── PointsExchangeController# Punkte-Einlöse-Shop
│   ├── notification/v1/controller/ # Benachrichtigungsmodul v1 (1 Controller)
│   │   └── NotificationController  # Benachrichtigungsliste/als gelesen markieren
│   ├── common/                  # Gemeinsame Fähigkeiten (BaseController usw.)
│   ├── middleware/              # Middleware
│   │   ├── ApiVersion              # API-Versionskontrolle (API-Version-Header)
│   │   ├── Auth                    # JWT-Authentifizierung + Benutzerstatusprüfung
│   │   ├── Cors                    # Cross-Origin-Behandlung
│   │   ├── Security                # Sicherheitsprüfung (security-php)
│   │   └── TechnicianAuth          # Techniker-Identitätsprüfung
│   └── model/                   # Datenmodelle (81)
│       ├── User.php → appointment_user
│       ├── TechnicianProfile.php → appointment_technician_profile
│       ├── Service.php → appointment_service (ES: appointment_services)
│       ├── Product.php → appointment_product (ES: appointment_products)
│       ├── Store.php → appointment_store
│       ├── Order.php → appointment_order (inkl. Rückerstattungsregeln/Statusmaschine)
│       ├── Coupon.php → appointment_coupon
│       ├── MemberCard.php → appointment_member_card
│       ├── Notification.php → appointment_notification
│       └── ... (insgesamt 81 Modelldateien; admin hat weitere 6 eigene Modelle, zusammen 87)
├── config/                     # Konfigurationsdateien
├── public/                     # Einstieg
├── runtime/                    # Laufzeit
├── vendor/                     # Abhängigkeiten
├── start.php
├── composer.json
└── Dockerfile
```

## apps/ — Frontend-Apps für Kunden

### apps/wechat/ — WeChat-Miniprogramm

```
apps/wechat/
├── app.js                      # App-Einstieg
├── app.json                    # Globale Konfiguration
├── app.wxss                    # Globale Styles
├── pages/
│   ├── auth/                   # Authentifizierung
│   │   ├── login               #   Login
│   │   ├── register            #   Registrierung
│   │   ├── forget-password     #   Passwort vergessen
│   │   └── agreement           #   Vereinbarung ansehen
│   ├── home/                   # Startseite (Karussell/Ankündigungen/Kategorien/Suche)
│   ├── service/                # Dienste
│   │   ├── list                #   Serviceliste
│   │   └── detail              #   Servicedetails
│   ├── order/                  # Bestellungen
│   │   ├── list                #   Bestellliste
│   │   ├── detail              #   Bestelldetails
│   │   └── confirm             #   Bestellung bestätigen
│   ├── cart/                   # Warenkorb
│   ├── cards/                  # Mitgliederkarten (Kauf/Meine/Stempelkarte my/use)
│   ├── gift-cards/             # Geschenkkarten (Einlösen redeem/Gutschrift)
│   ├── points/                 # Punkte (Buchungen/Einlösung)
│   ├── marketing/              # Marketing (Gutscheine usw.)
│   ├── favorite/               # Favoriten
│   ├── feedback/               # Feedback
│   ├── referral/               # Werbung
│   ├── message/                # Nachrichten
│   │   ├── list                #   Nachrichtenliste
│   │   └── detail              #   Nachrichtendetails
│   ├── tech-work/              # Techniker-Arbeitsplatz
│   │   ├── index               #   Arbeitsplatz-Startseite (today/records/start/complete)
│   │   ├── schedule            #   Schichtplan
│   │   ├── order-list          #   Bestellungen
│   │   ├── scan-verify         #   QR-Verifizierung
│   │   ├── member-list         #   Mitgliederliste
│   │   ├── member-detail       #   Mitgliederdetails
│   │   ├── earnings            #   Einnahmen
│   │   ├── withdrawal          #   Auszahlung
│   │   ├── transaction-list    #   Transaktionsdetails
│   │   └── training            #   Schulung
│   ├── user/                   # Persönliches Zentrum
│   │   ├── index               #   Persönliche Daten
│   │   ├── settings            #   Einstellungen
│   │   └── switch-role         #   Identitätswechsel
│   └── wallet/                 # Wallet (Guthaben/Aufladen/Transaktionshistorie)
├── components/                 # Gemeinsame Komponenten
│   ├── navbar
│   ├── tabbar
│   ├── service-card
│   ├── technician-card
│   ├── coupon-popup
│   └── lbs-selector
├── utils/                      # Werkzeuge
│   ├── api.js                  #   HTTP-Anfragen
│   ├── auth.js                 #   Authentifizierungsverwaltung
│   ├── location.js             #   LBS-Ortung
│   └── constants.js            #   Konstanten
├── styles/                     # Gemeinsame Styles
└── images/                     # Bildressourcen
```

### apps/flutter/ — Flutter APP

```
apps/flutter/
├── lib/
│   ├── main.dart               # Einstieg
│   ├── app.dart                # App-Konfiguration/Routen/Theme
│   ├── pages/                  # Seiten (Struktur wie Miniprogramm)
│   │   ├── auth/
│   │   ├── home/
│   │   ├── service/
│   │   ├── order/
│   │   ├── cart/
│   │   ├── technician/
│   │   ├── tech_work/
│   │   ├── user/
│   │   ├── marketing/
│   │   ├── message/
│   │   ├── store/
│   │   └── other/
│   ├── widgets/                # Gemeinsame Komponenten
│   ├── services/               # API-Services
│   │   ├── api_service         #   HTTP (Dio)
│   │   ├── auth_service        #   Authentifizierung
│   │   └── location_service    #   Ortung
│   ├── models/                 # Datenmodelle
│   ├── state/                  # Zustandsverwaltung
│   └── utils/                  # Werkzeuge
├── android/                    # Android-Projekt
├── ios/                        # iOS-Projekt
├── pubspec.yaml
└── ...
```

## Middleware-Ausführungskette

### service/

```
Öffentliche API:  Cors → Security → RateLimit → Controller
Benutzer-API:     Cors → Security → RateLimit → Auth → Controller
Techniker-API:    Cors → Security → RateLimit → Auth → TechnicianAuth → Controller
Zahlungsrückmeldungen: Cors → Security → Controller
```

### admin/

```
Öffentliche API:  Cors → Security → RateLimit → Controller
Verwaltungs-API:  Cors → Security → RateLimit → AdminAuth → AdminPermission → OperationLog → Controller
Health-Check:     Cors → Security → RateLimit → Controller
```

## Datenbanktabellenliste

Alle Tabellen verwenden das `appointment_`-Präfix, BIGINT nicht autoinkrementierende Primärschlüssel (Snowflake-generiert).

| Domäne | Tabellenname | Beschreibung |
|----|------|------|
| Benutzer | appointment_user | Einheitliche Benutzertabelle |
| Benutzer | appointment_user_address | Lieferadressen |
| Techniker | appointment_technician_profile | Technikerprofil |
| Techniker | appointment_technician_schedule | Techniker-Schichtplan |
| Techniker | appointment_technician_service | Vom Techniker angebotene Leistungen |
| Techniker | appointment_technician_earnings | Techniker-Einnahmenbuchungen |
| Techniker | appointment_technician_withdrawal | Techniker-Auszahlungsprotokolle |
| Techniker | appointment_technician_attendance | Techniker-Anwesenheit |
| Techniker | appointment_technician_member_note | Mitgliederprofile |
| Dienstleistung | appointment_service_category | Servicekategorien |
| Dienstleistung | appointment_service | Serviceleistungen |
| Dienstleistung | appointment_product | Produkte |
| Dienstleistung | appointment_store | Filialen |
| Bestellung | appointment_order | Bestell-Haupttabelle (seckill_id-Verknüpfungsspalte, Runde 24) |
| Bestellung | appointment_order_item | Bestellpositionen |
| Bestellung | appointment_order_payment | Zahlungsprotokolle |
| Bestellung | appointment_order_refund | Rückerstattungsprotokolle |
| Bestellung | appointment_order_review | Servicebewertungen |
| Bestellung | appointment_order_verification | Verifizierungsprotokolle |
| Bestellung | appointment_order_reschedule | Buchungsumänderungen (Runde 17) |
| Marketing | appointment_coupon | Gutschein-Definitionen |
| Marketing | appointment_user_coupon | Benutzergutscheine |
| Marketing | appointment_user_coupon_transfer | Gutschein-Weitergabeprotokolle (Runde 17) |
| Marketing | appointment_user_points_transfer | Punkte-Weitergabeprotokolle (Runde 19) |
| Marketing | appointment_technician_tier_log | Techniker-Stufenänderungsprotokolle (Runde 17) |
| Marketing | appointment_member_card | Mitgliederkarten-Definitionen |
| Marketing | appointment_user_member_card | Benutzer-Mitgliederkarten |
| Marketing | appointment_member_card_usage | Stempelkarten-Nutzungsprotokolle |
| Marketing | appointment_user_points | Punktebuchungen |
| Marketing | appointment_gift_card | Geschenkkarten |
| Marketing | appointment_user_referral | Benutzerwerbung |
| Marketing | appointment_user_favorite | Benutzerfavoriten |
| Wallet | appointment_user_wallet | Benutzer-Wallet-Guthaben |
| Wallet | appointment_wallet_recharge | Wallet-Aufladeprotokolle |
| Wallet | appointment_wallet_txn | Wallet-Transaktionsbuchungen |
| Wallet | appointment_wallet_transfer | Überweisungsprotokolle zwischen Benutzern (Runde 19) |
| Benutzer | appointment_user_notify_setting | Nachrichteneinstellungen (Runde 19) |
| Inhalt | appointment_banner | Karussell |
| Inhalt | appointment_announcement | Ankündigungen |
| Inhalt | appointment_platform_agreement | Plattformvereinbarungen |
| Inhalt | appointment_faq | FAQ |
| Inhalt | appointment_feedback | Feedback |
| Inhalt | appointment_moment | Moments-Beiträge |
| Inhalt | appointment_notification | Benachrichtigungen |
| Finanzen | appointment_finance_transaction | Einnahmen-Ausgaben-Buchungen |
| Finanzen | appointment_technician_commission_config | Provisionskonfiguration |
| Finanzen | appointment_withdrawal_account | Auszahlungskonten |
| Finanzen | appointment_withdrawal_config | Auszahlungslimit-Konfiguration |
| System | appointment_admin_user | Verwaltungsbenutzer (angelegt) |
| System | appointment_admin_role | Rollen (angelegt) |
| System | appointment_admin_permission | Berechtigungen (angelegt) |
| System | appointment_admin_user_role | Benutzer-Rollen-Verknüpfung (angelegt) |
| System | appointment_admin_role_permission | Rollen-Berechtigungs-Verknüpfung (angelegt) |
| System | appointment_system_config | Systemkonfiguration (angelegt) |
| System | appointment_operation_log | Betriebsprotokolle (angelegt) |
| Benutzer | appointment_user_growth | Wachstumswert-Buchungen (Runde 20) |
| Benutzer | appointment_growth_level | Wachstumsstufen (Runde 20) |
| Bestellung | appointment_invoice | Elektronische Rechnungen (Runde 20) |
| Benutzer | appointment_ticket | Kundenservice-Tickets (Runde 20) |
| Marketing | appointment_referral_level2_reward | Zweitstufen-Provisionen (Runde 20) |
| Benutzer | appointment_invoice_title | Rechnungsanschrift-Bibliothek (Runde 21) |
| Benutzer | appointment_browse_history | Browserverlauf (Runde 21) |
| Marketing | appointment_full_reduction_activity | Rabatt-ab-Mindestbetrag-Aktionen (Runde 22) |
| Techniker | appointment_technician_attendance | Techniker-Anwesenheit (Runde 22) |
| System | appointment_push_log | APP-Push-Protokolle (Runde 22) |
| Finanzen | appointment_profit_sharing | WeChat-Profit-Sharing-Protokolle (Runde 22) |
| Bestellung | appointment_order_status_log | Bestellstatus-Zeitachse (Runde 23) |
| Benutzer | appointment_user_health_profile | Benutzer-Gesundheitsprofile (Runde 23) |
| Marketing | appointment_lucky_wheel | Glücksrad-Preise (Runde 23) |
| Marketing | appointment_wheel_record | Glücksrad-Ziehungsprotokolle (Runde 23) |
| Marketing | appointment_seckill_activity | Blitzangebots-Aktivitäten (Runde 24) |
| System | appointment_app_version | APP-Versionen (Runde 24) |

### Ergänzungsliste (Teil der 95 Tabellen aus docs/install.sql, die oben nicht aufgeführt sind; die vollständige maßgebliche Liste ist install.sql)

| Domäne | Tabellenname | Beschreibung |
|----|------|------|
| Marketing | appointment_card_transfer | Stempelkarten-Weitergabe |
| Benutzer | appointment_check_in | Check-in |
| Inhalt | appointment_community_post | Community-Momente |
| Inhalt | appointment_community_comment | Community-Kommentare |
| Techniker | appointment_exam | Prüfungen |
| Techniker | appointment_exam_question | Prüfungsfragen |
| Techniker | appointment_exam_attempt | Prüfungsantworten |
| System | appointment_operation_log_detail | Betriebsprotokoll-Details |
| Bestellung | appointment_order_aftersale | Bestell-Kundendienst |
| Marketing | appointment_points_exchange_goods | Punkte-Einlöseartikel |
| Marketing | appointment_promotion | Gruppeneinkauf-Aktivitäten |
| Marketing | appointment_promotion_participant | Gruppeneinkauf-Teilnehmer |
| Bestellung | appointment_queue_number | Wartenummern-Ruf |
| Dienstleistung | appointment_service_package | Servicepakete |
| Techniker | appointment_service_record | Serviceprotokolle |
| Inhalt | appointment_share | Teilungsprotokolle |
| Bestellung | appointment_signature | Signaturen |
| Techniker | appointment_technician_tier_config | Techniker-Stufenkonfiguration |
| Techniker | appointment_training_course | Schulungskurse |
| Techniker | appointment_training_progress | Schulungsfortschritt |
| Benutzer | appointment_user_device | Benutzergeräte |
| Marketing | appointment_user_points_exchange | Punkte-Einlöseprotokolle |
| Inhalt | appointment_video_post | Video-Momente |
| Bestellung | appointment_waitlist | Warteliste |

## Reservierte externe Dienste

| Dienst | Zweck | Anbindungspunkt |
|------|------|--------|
| WeChat Open Platform | WeChat-Login/UnionID | WechatAuthService |
| WeChat-Zahlung | Zahlung/Rückerstattung/Auszahlung | WechatPayService |
| SMS-Anbieter | Verifizierungscodes/Benachrichtigungen | SmsService |
| Kartendienst | LBS-Ortung/Navigation/Entfernungsberechnung | MapService |
