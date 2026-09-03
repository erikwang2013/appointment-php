# API-Dokumentation
> **Languages**: [中文](../API.md) · [English](../en/API.md) · [한국어](../ko/API.md) · [Русский](../ru/API.md) · [Français](../fr/API.md) · [Español](../es/API.md) · [Português](../pt/API.md) · [हिन्दी](../hi/API.md) · [العربية](../ar/API.md) · [বাংলা](../bn/API.md) · [Bahasa Indonesia](../id/API.md) · [日本語](../ja/API.md)

> Deutsche Übersetzung · Original: [中文](../API.md)

## Überblick

- **Business-API** (service/): `http://localhost:8787` — stellt die Geschäftsschnittstellen für MiniProgramm/APP bereit
- **Verwaltungsbackend-API** (admin/): `http://localhost:8787` — stellt die Schnittstellen für das Flutter-Web-Verwaltungsbackend bereit
- **Authentifizierung**: Bearer Token (JWT), Request-Header `Authorization: Bearer <token>`
- **Versionskontrolle**: Die Version ist fest im URL-Pfadpräfix `/api/v1` verankert (z. B. `POST /api/v1/auth/login`); URLs ohne Versionspräfix liefern 404
- **ID-Codierung**: Alle ID-Felder in Anfragen/Antworten sind mit hashids codiert, die echten Datenbank-IDs werden nach außen verborgen
- **OpenAPI-Dokumentation**: wird mit `hg/apidoc` generiert, Verwaltungsseite und Clientseite getrennt

| Endpunkt | OpenAPI-Dokumentationsadresse | Beschreibung |
|------|------|------|
| Verwaltungsseite | `GET http://localhost:8787/api/docs` | Vollständige Spezifikation der Verwaltungsbackend-API (OpenAPI 3.0 JSON) |
| Clientseite | `GET http://localhost:8787/api/docs` | Vollständige Spezifikation der Business-API (OpenAPI 3.0 JSON) |

Die oben genannten Adressen können über Tools wie Swagger UI importiert werden, um die interaktive Dokumentation anzusehen.

- **Allgemeines Antwortformat**:

```json
{
  "code": 0,
  "message": "操作成功",
  "data": {}
}
```

Paginierte Antwort:
```json
{
  "code": 0,
  "message": "success",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

---

## I. Business-API (service/ :8787)

### 1. Öffentliche Schnittstellen (ohne Authentifizierung)

#### 1.1 Verifizierungscode

**`POST /api/v1/captcha/send`** — SMS-Verifizierungscode senden

Anfrage:
```json
{
  "phone": "13800138000"
}
```
Antwort: `{"code":0,"message":"验证码已发送","data":null}`

Einschränkung: Maximal 1 Sendung pro 60 Sekunden, der Verifizierungscode ist 5 Minuten gültig.

---

#### 1.2 Authentifizierung

**`POST /api/v1/auth/register`** — Registrierung mit Telefonnummer

Anfrage:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "abc123",
  "confirm_password": "abc123",
  "referral_code": "A1B2C3D4"
}
```
Antwort:
```json
{
  "code": 0,
  "message": "注册成功",
  "data": {
    "token": "eyJhbGciOi...",
    "user": {
      "id": "aB3xK9mQ",
      "phone": "138****8000",
      "nickname": "用户138****8000",
      "user_type": "customer",
      "active_role": "customer",
      "referral_code": "E5F6G7H8"
    }
  }
}
```

---

**`POST /api/v1/auth/login`** — Passwort-Login

Anfrage:
```json
{
  "phone": "13800138000",
  "password": "abc123"
}
```
Antwort: Wie bei der Registrierung, enthält token und user-Informationen.

---

**`POST /api/v1/auth/login-by-code`** — Verifizierungscode-Login

Anfrage:
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```
Antwort: Wie beim Login. Nicht registrierte Benutzer erhalten automatisch ein Konto.

---

**`POST /api/v1/auth/forget-password`** — Passwort vergessen

Anfrage:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "newpass123",
  "confirm_password": "newpass123"
}
```

---

**`POST /api/v1/auth/refresh`** — Token aktualisieren

Request-Header: `Authorization: Bearer <altes Token>`
Antwort: `{"code":0,"data":{"token":"eyJhbGciOi..."}}`

---

#### 1.3 WeChat

**`POST /api/v1/wechat/mini-login`** — MiniProgramm-Login

Anfrage: `{"code":"微信登录code"}`
Hinweis: Beim ersten Login muss anschließend `/api/v1/wechat/phone` aufgerufen werden, um die Telefonnummer zu binden.

---

**`POST /api/v1/wechat/phone`** — Telefonnummer binden

Anfrage: `{"code":"微信手机号组件code"}`

---

**`POST /api/v1/wechat/oa-login`** — Offizielles-Konto-Login

Anfrage: `{"code":"公众号授权code"}`

---

#### 1.4 Allgemeine Dienste

**`GET /api/v1/common/config`** — Öffentliche Konfiguration

Antwort: Enthält Vereinbarungstexte (Nutzungsvereinbarung/Datenschutzvereinbarung/Dienstleistungsvereinbarung), Über-uns-Informationen, Versionsnummer.

---

**`GET /api/v1/common/area`** — Städte-/Regionenliste

---

#### 1.5 Service-Abfrage

**`GET /api/v1/service/categories`** — Kategorienliste

Parameter: `?parent_id=0`

---

**`GET /api/v1/service/items`** — Service-Leistungsliste

Parameter: `?category_id=&page=1&per_page=10&sort=sales`

---

**`GET /api/v1/service/detail/{id}`** — Servicedetails

Die Antwort enthält: Bilder/Name/Preis/Spezifikationen/Dauer/Verkaufszahlen/Bewertungsliste.

---

**`GET /api/v1/service/products`** — Produktliste

**`GET /api/v1/service/stores`** — Filialliste

Parameter: `?lat=&lng=&city=`

---

#### 1.6 Techniker-Abfrage

**`GET /api/v1/technician/list`** — Technikerliste

Parameter: `?lat=&lng=&service_id=&page=1`
Sortierung nach Entfernung von nah nach fern, Rückgabe: Avatar/Name/Bewertung/Anzahl Bestellungen/Anzahl Favoriten/Entfernung/Frühester verfügbarer Termin/Ob verfügbar.

---

**`GET /api/v1/technician/detail/{id}`** — Technikerdetails

Die Antwort enthält: Bilder/Name/Vorstellung/Bewertung/Entfernung/Liste der angebotenen Leistungen/Bewertungen.

---

**`GET /api/v1/technician/schedule/{id}`** — Techniker-Schichtplan

Parameter: `?date=2026-05-26`
Gibt die buchbaren Zeitfenster und den Verfügbarkeitsstatus für dieses Datum zurück.

---

#### 1.7 Inhalte

**`GET /api/v1/content/banners`** — Karussellbilder

Parameter: `?position=home`

**`GET /api/v1/content/articles`** — Ankündigungs-/Artikel-Liste

Parameter: `?type=announcement&page=1`

**`GET /api/v1/content/article/{id}`** — Artikeldetails

---

#### 1.8 LBS

**`GET /api/v1/lbs/nearby-stores`** — Filialen in der Nähe

Parameter: `?lat=&lng=&radius=5000`

**`GET /api/v1/lbs/geocode`** — Reverse-Geocodierung

Parameter: `?lat=&lng=`

---

### 2. Benutzerschnittstellen (JWT-Authentifizierung erforderlich)

Alle Schnittstellen führen im Request-Header `Authorization: Bearer <token>` mit.

#### 2.1 Persönliches Profil

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/user/profile` | Persönliche Informationen abrufen |
| PUT | `/api/v1/user/profile` | Nickname/Avatar/Geschlecht aktualisieren |
| POST | `/api/v1/user/change-password` | Passwort ändern (old_password/new_password/confirm_password) |
| POST | `/api/v1/user/change-phone` | Telefonnummer neu binden (old_code/new_phone/new_code) |
| POST | `/api/v1/user/cancel-account` | Konto löschen (Passwortverifizierung erforderlich) |
| POST | `/api/v1/user/logout` | Abmelden (Token wird auf die Blacklist gesetzt) |
| POST | `/api/v1/user/switch-role` | Identität wechseln (role: customer/technician) |

Für den Wechsel zu technician ist ein bereits mit Status approved vorliegendes Technikerprofil erforderlich.

#### 2.2 Adressverwaltung

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/user/addresses` | Adressliste |
| POST | `/api/v1/user/addresses` | Adresse hinzufügen (contact_name/contact_phone/province/city/district/detail/lat/lng/is_default) |
| GET | `/api/v1/user/addresses/{id}` | Adressdetails |
| PUT | `/api/v1/user/addresses/{id}` | Adresse aktualisieren |
| DELETE | `/api/v1/user/addresses/{id}` | Adresse löschen |

Beim Setzen als Standardadresse werden andere Standardadressen automatisch aufgehoben.

#### 2.3 Favoriten

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/user/favorites` | Favoritenliste (?type=service/technician) |
| POST | `/api/v1/user/favorites` | Favorit hinzufügen (target_type/target_id) |
| DELETE | `/api/v1/user/favorites/{id}` | Favorit entfernen |

#### 2.4 Feedback

`POST /api/v1/user/feedback` — Feedback senden (content + images-Array)

#### 2.5 Empfehlung und Provision

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/user/referral` | Empfehlungsinformationen (Empfehlungscode/Anzahl empfohlener/Anzahl Erstbesteller/erhaltene Punkte) |
| GET | `/api/v1/user/referral/qrcode` | Empfehlungs-QR-Code (Empfehlungscode + Einladungslink) |
| GET | `/api/v1/user/referral/referred-users` | Liste der empfohlenen Benutzer |
| GET | `/api/v1/user/referral/earnings` | Provisionsdetails der Distribution (paginiert: Nickname/Avatar des Empfohlenen/Bestellnummer/Betrag/Auszahlungszeit) |

**Distributionsprovision**: Wird nach der ersten completed-Bestellung des Empfohlenen ausgezahlt, Betrag = paid_amount × reward_rate (appointment_system_config referral.reward_rate, Standard 0.05, ungültige Werte fallen auf die Konstante zurück). Dreifache Idempotenz durch Zeilensperre + rewarded_at-Leerprüfung + Erstbestell-Recheck; Verbuchung in WalletTxn type=referral_reward.

#### 2.6 Punkte-Übertragung (Runde 19)

| Methode | Pfad | Beschreibung |
|------|------|------|
| POST | `/api/v1/user/points/transfer` | Punkte übertragen (to_user_id hashid/points) |
| GET | `/api/v1/user/points/transfers` | Übertragungsprotokoll (?direction=sent/received&page=1) |

**Punkte-Übertragung**: hashid-Dekodierung + Existenzprüfung des Empfängers 404, Übertragung an sich selbst 422, Punkte 1-10000 422, unzureichendes Guthaben per SUM-Aggregation 422, Tageslimit 10000 kumuliert 422. Konkurrenzschutz: Redis-NX-Sperre points_transfer:{user} 30s → innerhalb der Transaktion beide Seiten mit dem letzten Eintrag lockForUpdate (user_id aufsteigend, verhindert Deadlocks bei gegenseitigen Übertragungen) → innerhalb der Sperre Guthaben/Limit/Empfänger erneut prüfen. Transaktionsprotokoll: Sender type=consume/source=points_transfer negativer Wert (balance=letzter Snapshot − diesmal), Empfänger type=earn/source=points_transfer positiver Wert inkl. expires_at (PointsExpiryTimer kann normal ablaufen); nach commit In-App-Benachrichtigung an den Empfänger type='points_received' (bei Fehler nur warn).

#### 2.7 Benachrichtigungspräferenzen (Runde 19)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/user/notify-settings` | Benachrichtigungsschalter abfragen (alle 5 Kategorien) |
| PUT | `/api/v1/user/notify-settings` | Schalter batch-weise aktualisieren (types: {service_reminder: 0/1, ...}) |

**Benachrichtigungsschalter**: Tabelle appointment_user_notify_setting (user_id+type zusammengesetzter eindeutiger Schlüssel, fehlende Zeile = standardmäßig an). 5 Kategorien: service_reminder Service-Erinnerung / card_expiry Ablauf-Erinnerung (einheitlicher Dachschirm für Karten + Gutscheine) / points_expiry Punkte-Ablauf / marketing Marketing (reserviert) / system System (kann nicht ausgeschaltet werden, PUT erzwingt 1). Gating: notifySettingEnabled hängt an den 3 Timer-Prozessen ServiceReminderTimer/ExpiryReminderTimer/PointsExpiryTimer + Szenario-Zuordnung von Abonnement-Events (PAY/REFUND/VERIFIED/RESCHEDULE→system immer gesendet, REMINDER→service_reminder, EXPIRY→card_expiry); bei deaktiviertem Typ werden In-App-Benachrichtigungen und Abonnementnachrichten gleichermaßen übersprungen.

---

### 3. Techniker-Schnittstellen (JWT + Techniker-Identität erforderlich)

#### 3.1 Technikerprofil

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/technician/profile` | Technikerprofil abrufen |
| PUT | `/api/v1/technician/profile` | Profil aktualisieren (avatar/intro/real_name/gender/id_card/id_card_front/id_card_back) |

Das erste vollständige Ausfüllen gilt als Aufnahmeantrag, status=pending wartet auf Prüfung.

#### 3.2 Schichtplan

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/technician/schedule` | Schichtplan-Abfrage (?start_date=&end_date=) |
| PUT | `/api/v1/technician/schedule` | Schichtplan festlegen (date/time_slots/status), Zeitfenster-Überschneidung 422 „与已有排班时间冲突" |
| POST | `/api/v1/technician/schedule/batch` | Schichtplan in Batch (Runde 23): Datumsbereich ≤7 Tage + weekdays-Filter, Tage mit vorhandenem Schichtplan werden übersprungen, Antwort created/skipped |

#### 3.3 Techniker-Bestellungen

`GET /api/v1/technician/orders` — Bestellliste (?status=&page=1)

#### 3.4 Einnahmen

`GET /api/v1/technician/earnings` — Einnahmenübersicht (today_income/pending_settlement/balance + Transaktionsliste)

#### 3.5 Auszahlung

`POST /api/v1/technician/withdraw` — Auszahlung beantragen (amount)
Regeln: Auszahlung am 20. jedes Monats möglich, T+1 auf dem Konto, Mindestbetrag/Hunderter-Beschränkung über die Backend-Konfiguration.

**Unterwegs-Reservierung (2026-08-26)**: Bei der Beantragung wird der unterwegs befindliche Betrag (pending/approved) vom Guthaben reserviert; vor der Freigabe-Überweisung erneute Prüfung settled − withdrawn − unterwegs ≥ Auszahlungsbetrag; parallele Freigaben verursachen keine Doppelauszahlung.

#### 3.6 Bewertungsantwort (Runde 18)

`POST /api/v1/technician/review/reply/{order_id}` — Techniker antwortet auf Bewertung (reply). Bewertung nicht vorhanden/nicht eigene einheitlich 404 (keine Existenzpreisgabe); bereits vorhandene Antwort 422 (Idempotenz, keine Überschreibung); leere Antwort 422. Bei Erfolg In-App-Benachrichtigung an den Benutzer (type='review_reply').

#### 3.6 Arbeitsplatz

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/technician/work/today` | Heutige Aufgabenliste |
| GET | `/api/v1/technician/work/records` | Abschlussprotokoll paginiert |
| POST | `/api/v1/technician/work/{id}/start` | Service beginnen |
| POST | `/api/v1/technician/work/{id}/complete` | Service abschließen |

**Heutige Aufgaben**: status ∈ [confirmed, serving], service_time heute oder leer, Rückgabe service_name/price/nickname/avatar.

**Abschlussprotokoll**: status ∈ [serving, completed], nach service_end_at absteigend, paginierte Antwort enthält meta.

**Service beginnen/abschließen**: Zeilensperre + Statusmaschinenprüfung, idempotente Operation. Beginn schreibt service_start_at; Abschluss schreibt service_end_at und sendet eine In-App-Benachrichtigung. Fehlercodes: nicht eigene 403, Statusfehler 422, ungültiges hashid 422.

---

### 4. Bestellschnittstellen (JWT-Authentifizierung erforderlich)

| Methode | Pfad | Beschreibung |
|------|------|------|
| POST | `/api/v1/order` | Bestellung erstellen (order_type/items/store_id/technician_id/service_time/coupon_id/user_coupon_id/promotion_id/remark) |
| GET | `/api/v1/order/list` | Bestellliste (?status=&page=1) |
| GET | `/api/v1/order/detail/{id}` | Bestelldetails |
| POST | `/api/v1/order/cancel/{id}` | Bestellung stornieren (reason) |
| POST | `/api/v1/order/pay/{id}` | Zahlung auslösen (pay_channel: wechat/balance, use_points: optionale Punkte-Anrechnung) |
| POST | `/api/v1/order/refund/{id}` | Rückerstattung beantragen |
| POST | `/api/v1/order/verify/{id}` | Verifizierung (code: QR-Code-Wert) |
| POST | `/api/v1/order/reschedule/{id}` | Termin verschieben (new_service_time Pflichtfeld/reason optional) |
| GET | `/api/v1/order/logistics/{id}` | Logistikverfolgung (Runde 19, product-Bestellungen) |
| POST | `/api/v1/order/review/{order_id}` | Bewertung abgeben (rating 1-5/content/images) (Runde 19 nachregistriert) |
| POST | `/api/v1/order/review/{order_id}/append` | Bewertung ergänzen (content/images kommasepariert) (Runde 19) |

**Bestellstatus**: pending(ausstehende Zahlung) → paid(bezahlt) → confirmed(bestätigt) → serving(in Bearbeitung) → completed(abgeschlossen)

**Beim Erstellen der Bestellung**: Redis SETNX sperrt den Techniker 3 Minuten lang, Freigabe beim Verlassen der Seite oder bei Timeout.

**Preis-Manipulationsschutz (2026-08-26)**: Bestellpositionsbeträge ausschließlich aus der Datenbank (target_type=service fragt appointment_service ab, product fragt appointment_product ab), vom Client übermittelte Preise fließen nicht in die Berechnung ein; unbekannter target_type 422; target_id muss als hashid-codierter Wert übergeben werden (raw id wird zu 0 dekodiert → 422 „商品不存在或已下架"); Gruppen-/Blitzpreise ebenfalls auf DB-Basis.

**Rückerstattungsregeln**: Innerhalb 15 min nach Bestellung oder >6 h vor Beginn 100 % / ≤6 h vor Beginn 90 % / bereits begonnen 80 % / nach bestätigtem Beginn keine Rückerstattung.

**Gutschein-Anrechnung**: Beim Erstellen der Bestellung optional user_coupon_id (hashid) übergeben. Fehlercodes: fremder Gutschein 404, Schwellenwert nicht erreicht/abgelaufen/aus dem Sortiment genommen/bereits verwendet 422, ungültiges hashid 422. Anrechnung zweistufig: beim Erstellen der Bestellung prüft PriceCalculator.applyCoupon nur lesend und berechnet den Anrechnungsbetrag, schreibt discount_amount; nach erfolgreicher Zahlung setzt consume den Gutschein auf used; bei Rückerstattung gibt restoreCouponAndCard idempotent zurück.

**Guthabenzahlung und -rückerstattung**: Mit `pay_channel: "balance"` im Zahlungs-Body wird das Wallet-Guthaben verwendet; sowohl WeChat-Rückerstattungen als auch Guthabenrückerstattungen schreiben den Betrag ins Wallet-Guthaben zurück.

**Punkte-Anrechnung**: Im Zahlungs-Body optional `use_points` (Ganzzahl) übergeben. SUM-Aggregationsprüfung des Punkteguthabens (die balance-Spalte von appointment_user_points ist ein inkrementeller Schnappschuss pro Eintrag und nicht direkt als Guthaben nutzbar), Anrechnungsbetrag = floor(use_points / config('app.points_rate', 100)) Yuan, tatsächlicher Zahlbetrag = ursprünglich fällig − Anrechnungsbetrag (Untergrenze 0.01, bei Überschreiten des fälligen Betrags volle Anrechnung ohne Punktverschwendung). Bei Erfolg wird type=consume/source=points_offset geschrieben (idempotent, Wiederholungen ziehen nicht doppelt ab). Unzureichendes Guthaben 422.

**Punkte-Rückerstattung**: Bei Stornierung/Rückerstattung werden die durch points_offset verbrauchten Punkte zurückgegeben (type=earn/source=points_refund): Stornierung in voller Höhe, Rückerstattung anteilig, 5 idempotente Anbindungspunkte (refundOffsetPoints).

**Gruppenkauf-Bestellung (Runde 16)**: Beim Erstellen der Bestellung optional `promotion_id` (hashid) übergeben. Prüfung: nur Typ group_buy, Aktivität im gültigen Zeitraum, Aufrufer ist Teilnehmer, nicht voll (bereits als Gruppe geschlossen 422), Bestellservice passt zur Aktivität; Gruppenpreis = Originalpreis × discount_percent/100, Gutscheine/Stempelkarten/Punkte-Stapelung deaktiviert (Übergabe von einem davon 422). Bestellung speichert promotion_id/participant_id; Zahlung vollständig über `POST /api/v1/order/pay/{id}` wiederverwendet, bei pay lazy-Prüfung ob die Aktivität geschlossen ist (abgelaufen ohne Gruppe) → Bestellung automatisch storniert und Technikersperre freigegeben.

**Blitzangebots-Bestellung (Runde 18, eingestellt)**: ~~Beim Erstellen der Bestellung `promotion_id` (Typ flash_sale) übergeben~~ — ab 2026-08 wurde der alte Promotionskanal FLASH_SALE entfernt, der store()-Promotionszweig kennt nur noch Gruppenkauf GROUP_BUY (nicht Gruppenkauf-Promotion 422); Blitzangebote laufen einheitlich über den /api/v1/seckill-Kanal aus Runde 24 (seckill_id wird in der store-Transaktion per Zeilensperre in den Lagerbestand eingerechnet), PromotionController::index filtert flash_sale heraus, show/join geben dafür 400 zurück, die Konstante `Promotion::TYPE_FLASH_SALE` bleibt für die Kompatibilität historischer Daten erhalten.

**Terminverschiebung (Runde 17)**: `POST /api/v1/order/reschedule/{id}` mit new_service_time (Pflichtfeld) + reason (optional), Terminwechsel beim selben Techniker. Regeln: nur eigene Bestellungen (fremde 404), nur Typ appointment mit Status pending/paid/confirmed änderbar (sonst 422), mindestens 6 Stunden vor dem ursprünglichen Servicebeginn (identisch mit dem Vollrückerstattungsfenster). Konkurrenzschutz: B1 order_lock (gleiche Mutex-Familie wie pay/cancel/refund) → Technikersperre für den neuen Zeitraum per Redis SETNX EX 180 (verhindert Überbuchung bei parallelen Umbuchungen) → innerhalb der Transaktion Zeilensperre + erneutes Lesen + B2 Schichtkonflikt-DB-Prüfung (eigene Bestellung ausgeschlossen) → service_time aktualisieren + Datensatz in appointment_order_reschedule schreiben → Sperre des alten Zeitraums freigeben, Sperre des neuen Zeitraums wird von dieser Bestellung gehalten → SCENE_RESCHEDULE-Abonnementnachricht (ohne Konfiguration Degradation auf In-App-Benachrichtigung). Bei Fehlerpfad wird die Transaktion zurückgerollt und gleichzeitig die Sperre des neuen Zeitraums freigegeben.

**Logistikverfolgung (Runde 19)**: `GET /api/v1/order/logistics/{id}` — nur eigene product-Bestellungen abrufbar (fremde/keine Produktbestellung/nicht versendet einheitlich 404). Liest order.remark JSON (shipping_company/tracking_no/shipped_at, wird von der Admin-Methode MallOrderController::ship() beim Versand geschrieben), parseShippingInfo/parseReceiver doppelte Parsing-Absicherung für das alte Format; Telefonnummer des Empfängers maskiert 138****5678.

**Bewertung (Runde 19)**: `POST /api/v1/order/review/{order_id}` Bewertung abgeben (rating Pflichtfeld 1-5, content/images optional): fremde 404, nicht completed 422, doppelte Bewertung 400. `POST /api/v1/order/review/{order_id}/append` Bewertung ergänzen (content Pflichtfeld, images kommasepariert): Bewertung nicht vorhanden/fremde einheitlich 404, nicht completed 422, doppelte Ergänzung 422, leerer Inhalt 422; bei Erfolg append_content/append_images(JSON)/append_at schreiben und In-App-Benachrichtigung an den Techniker type='review_append', die Antwort enthält das append-Feld.

### 4.1 After-Sales-Schnittstellen (JWT-Authentifizierung erforderlich)

| Methode | Pfad | Beschreibung |
|------|------|------|
| POST | `/api/v1/aftersales` | After-Sales beantragen (order_id hashid/type: refund\|exchange/reason), Prüfung eigene Bestellung 404, nur Status paid+completed beantragbar 422, laufender After-Sales zur selben Bestellung de-dupliziert 422 |
| GET | `/api/v1/aftersales` | Meine After-Sales-Liste (?status=&page=1&limit=) |
| GET | `/api/v1/aftersales/{id}` | After-Sales-Details (Zugehörigkeitsprüfung 404) |

**After-Sales-Status**: pending(ausstehende Prüfung) → approved(genehmigt) / rejected(abgelehnt). approved ist nur ein Statusübergang, der Rückerstattungsvorgang läuft weiterhin über `POST /api/v1/order/refund/{id}`.

---

### 4.2 Gruppenkauf-/Promotions-Schnittstellen (JWT-Authentifizierung erforderlich; FLASH_SALE eingestellt)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/promotions` | Aktivitätsliste (?type=group_buy; flash_sale wird gefiltert und nicht zurückgegeben) |
| GET | `/api/v1/promotions/{id}` | Aktivitätsdetails (inkl. Teilnehmerzahl/ob Gruppe geschlossen; Typ flash_sale 400) |
| GET | `/api/v1/promotions/{id}/participants` | Teilnehmerliste |
| POST | `/api/v1/promotions/join/{id}` | An Aktivität teilnehmen (Runde 15 vervollständigt: Antwort enthält discount_percent/original_price/group_price; Typ flash_sale 400) |

**Teilnahmeregeln**: group_buy bei voller Gruppe (≥min_people) gesperrt, nach Gruppenschluss neue Teilnahme 422; bei Ablauf ohne volle Gruppe lazy-Deaktivierung (bei show/join wird status auf 0 gesetzt). Nach dem Beitritt Bestellung zum Gruppenpreis siehe „Gruppenkauf-Bestellung (Runde 16)". Blitzangebote laufen nicht mehr über diesen Kanal, siehe „24. 秒杀接口".

---

### 5. Marketing-Schnittstellen (JWT-Authentifizierung erforderlich)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/marketing/coupons` | Gutscheinliste (?status=available/used/expired) |
| POST | `/api/v1/marketing/coupons/receive` | Gutschein einlösen (coupon_id) |
| GET | `/api/v1/marketing/cards` | Mitgliederkartenliste |
| POST | `/api/v1/marketing/cards/buy` | Mitgliederkarte kaufen (card_id) |
| GET | `/api/v1/marketing/cards/my` | Meine Stempelkartenliste |
| POST | `/api/v1/marketing/cards/use` | Stempelkarte verifizieren (user_card_id/service_id/remark?) |
| GET | `/api/v1/marketing/gift-cards` | Geschenkkartenliste |
| GET | `/api/v1/marketing/gift-cards/my` | Meine Geschenkkarten (redeem-Protokoll) |
| POST | `/api/v1/marketing/gift-cards/redeem` | Geschenkkarte einlösen (Typ cash lädt nach Einlösung das Wallet-Guthaben auf) |
| GET | `/api/v1/marketing/points` | Punkte-Transaktionen (?type=earn/use/expire&source=order/referral/gift_card/check_in/admin) |
| GET | `/api/v1/marketing/points-exchange` | Liste der Punkte-Einlöseartikel (verfügbar + aktueller Restbestand + bereits eingelöste Anzahl) |
| POST | `/api/v1/marketing/points-exchange/{id}` | Einlösen (type=coupon Gutschein ausgeben / wallet Gutschrift / gift_card Rückgabe von Kartencode) |
| POST | `/api/v1/marketing/coupons/transfer` | Übertragungscode generieren (user_coupon_id: 8-stelliger eindeutiger Code/7 Tage gültig) |
| POST | `/api/v1/marketing/coupons/claim` | Übertragenen Gutschein beanspruchen (code) |
| GET | `/api/v1/marketing/coupons/transfers` | Übertragungsprotokoll (ausgehend pending/claimed/expired + erhalten claimed) |

**Stempelkarte**: cards/my gibt card_id/name/type/services/total_times/used_times/remaining_times/start_at/end_at/status zurück (in Echtzeit berechnet). Erfolgreiche Verifizierung gibt {order_id, usage_id, remaining_times} zurück; Fehlercodes: ungültiges hashid 422, unzureichende Anzahl 422, abgelaufen 400, fremde 404, Redis-Duplikatschutz 400.

**Geschenkkarte**: gift-cards/my gibt redeem-Protokoll zurück (type/amount/gift_name/status/used_at).

**Punkteregeln**: Transaktionen paginiert, type-Filter (earn/use/expire), source-Filter (order/referral/gift_card/check_in/admin). Check-in gibt Punkte zurück (CheckIn, type=earn); Konsum gibt floor(paid_amount×1) Punkte, Ausgabe bei Verifizierung und idempotent; Rückerstattung zieht Punkte anteilig zurück.

**Punkte-Ablauf (Runde 17)**: Spalte appointment_user_points.expires_at (Konfiguration points.expiry_days, Standard 365 Tage, ≤0 nie ablaufend), alle earn-Einträge speichern die Gültigkeit; der Timer-Prozess PointsExpiryTimer scannt alle 60 s cursor-basiert abgelaufene earn-Zeilen, schreibt type=expire negative Abbuchungszeilen (source=expiry + order_id führt zur ursprünglichen Transaktion, dreischichtige Idempotenz) + aggregierte In-App-Benachrichtigung „您有 X 积分已过期"; das verfügbare Guthaben per SUM umfasst expire-negative Zeilen, abgelaufene Punkte können nicht mehr angerechnet/eingelöst werden.

**Gutschein-Übertragung (Runde 17)**: transfer prüft, ob der Gutschein zur eigenen Person gehört/available/Definition nicht abgelaufen/noch nie übertragen wurde, generiert einen 8-stelligen verwechslungsfreien eindeutigen Übertragungscode (uk_code eindeutiger Index als Absicherung), 7 Tage gültig. claim-Abuse-Schutz: Redis-NX-Sperre (coupon_transfer_claim:{code} 30s) + Zeilensperre und erneute Prüfung gegen Doppelbeanspruchung, eindeutiger Index uk_user_coupon begrenzt denselben Gutschein auf eine Übertragung, übertragene Gutscheine können nicht erneut übertragen werden (neuer Gutschein ohne Übertragungsprotokoll wird natürlich blockiert), Beanstandung des eigenen übertragenen Gutscheins 422, Empfänger ist nicht der ursprüngliche Inhaber; lazy-Ablaufprüfung setzt expired und stellt den Originalgutschein auf available zurück. In der claim-Transaktion wird der Originalgutschein auf used gesetzt + neuer UserCoupon für den Empfänger generiert (coupon_id unverändert, also auch die Gültigkeit unverändert) + Protokoll auf claimed gesetzt.

---

### 6. Benachrichtigungsschnittstellen (JWT-Authentifizierung erforderlich)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/notification` | Benachrichtigungsliste (?type=order/system&page=1) |
| PUT | `/api/v1/notification/read/{id}` | Als gelesen markieren |
| PUT | `/api/v1/notification/read-all` | Alle als gelesen markieren |

---

### 7. Wallet-Schnittstellen (JWT-Authentifizierung erforderlich)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/wallet` | Wallet-Guthaben + Transaktionsseiten |
| POST | `/api/v1/wallet/recharge` | Aufladeauftrag erstellen (amount: Yuan) |
| POST | `/api/v1/wallet/recharge/{id}/pay` | Zahlung für Aufladeauftrag auslösen (WeChat) |
| POST | `/api/v1/wallet/transfer` | Guthabenübertragung (to_user_id hashid/amount/remark optional/client_token optional) (Runde 19) |
| GET | `/api/v1/wallet/transfers` | Übertragungsprotokoll (?direction=out/in&page=1) (Runde 19) |
| GET | `/api/v1/wallet/transfers/{id}` | Übertragungsdetails (nur für beide Parteien sichtbar, fremde 404) (Runde 19) |

**Transaktionen**: wallet_txn-Typen: recharge / consume / refund / gift_card / referral_reward (Distributionsprovision) / referral_level2 (Provisionsstufe 2) / points_exchange (Punkte-Einlösegutschrift), paginiert zurückgegeben.

**Aufladen**: `POST /api/v1/wallet/recharge` mit amount (Yuan) erstellt den Aufladeauftrag, Rückgabe der Aufladeauftrag-hashid. `POST /api/v1/wallet/recharge/{id}/pay` löst die WeChat-Zahlung aus, Antwort enthält sign_params (wie beim Bestellzahlungsmodus); der Zahlungs-Callback unterscheidet Aufladeauftrag und Bestellung über die out_trade_no mit R-Präfix.

**Guthabenzahlung**: Im Bestellzahlungs-Body `pay_channel: "balance"` übergeben; WeChat-Rückerstattungen und Guthabenrückerstattungen schreiben den Betrag beide ins Wallet-Guthaben zurück.

**Guthabenübertragung (Runde 19)**: `POST /api/v1/wallet/transfer` — hashid-Dekodierung + Existenzprüfung des Empfängers 404, Übertragung an sich selbst 422, Betrag 0.01-1000 pro Vorgang 422 (DECIMAL-Vergleich, float verboten), unzureichendes Guthaben 422, Tageslimit 5000 Yuan kumuliert 422. Konkurrenz/Idempotenz: Redis-NX-Sperre wallet_transfer:{from} 30s serialisiert den Sender → innerhalb der Transaktion beide Wallet-Zeilen nach user_id aufsteigend lockForUpdate (feste Reihenfolge gegen Deadlocks) → Sender abbuchen + Empfänger gutschreiben + WalletTxn-Doppeltransaktionen (transfer_out/transfer_in inkl. balance_after-Schnappschuss) + Übertragungsprotokoll completed + In-App-Benachrichtigung an den Empfänger type='balance_received' (bei Fehler nur Log). client_token optional: bei Erfolg SETNX 24 h gegen doppeltes Absenden (fehlgeschlagene Anfragen setzen kein Token und können wiederholt werden).

---

### 8. Filialleiter-Workbench-Schnittstellen (JWT-Authentifizierung erforderlich)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/store-manager/overview` | Tagesübersicht (heutige Bestellanzahl/heutiger Umsatz/laufend/Technikeranzahl/Verifizierungsanzahl) |
| GET | `/api/v1/store-manager/orders` | Filialbestellliste (?status=&page=&limit=) |
| GET | `/api/v1/store-manager/technicians` | Technikerliste (inkl. heutigem Schichtplan) |
| GET | `/api/v1/store-manager/revenue` | Umsatzaggregation der letzten 7 Tage |

**store_id-Isolation**: requireStoreId() erzwingt, dass der aktuelle Benutzer an eine Filiale gebunden ist (appointment_user.store_id), ohne Filiale 403; alle Abfragen werden nach store_id gefiltert.

---

### 9. Wachstumsstufen-Schnittstellen (JWT-Authentifizierung erforderlich, Runde 20)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/growth` | Aktuelle Wachstumsübersicht (balance/Stufe/Differenz zur nächsten Stufe/Stufenname) |
| GET | `/api/v1/growth/records` | Wachstumswert-Transaktionen paginiert (?page=&limit=) |
| GET | `/api/v1/growth/levels` | Stufenliste (öffentlich, ohne Login) |

**Wachstumswert-Gutschrift**: Check-in +10; Bewertung abgeben +20 (Ergänzungen zählen nicht); Konsum floor(paid) 1 Punkt pro Yuan (im Zahlungs-Callback mit Status-Neuprüfung idempotent, wiederholte Callbacks buchen nicht doppelt).

### 10. Rechnungsschnittstellen (JWT-Authentifizierung erforderlich, Runde 20)

| Methode | Pfad | Beschreibung |
|------|------|------|
| POST | `/api/v1/invoices` | Rechnung beantragen (order_id hashid/order_type: service=Leistung/points_exchange=Punkte-Einlösung/order_type Standard service; Betrag und Rechnungsanschrift werden serverseitig mitgeführt, nicht manipulierbar) |
| GET | `/api/v1/invoices` | Rechnungsliste (?status=&page=) |
| GET | `/api/v1/invoices/{id}` | Rechnungsdetails (nur eigene) |

**Duplikatschutz**: uk_order_type(order_id, order_type) eindeutiger Schlüssel, doppelter Antrag für dieselbe Bestellung und denselben Typ 422 (inkl. MySQL-1062-Fangnetz).

### 11. Kundendienst-Ticket-Schnittstellen (JWT-Authentifizierung erforderlich, Runde 20)

| Methode | Pfad | Beschreibung |
|------|------|------|
| POST | `/api/v1/tickets` | Ticket einreichen (title/content Pflichtfelder) |
| GET | `/api/v1/tickets` | Ticketliste (?status=open/closed&page=) |
| GET | `/api/v1/tickets/{id}` | Ticketdetails (nur eigene, fremde 404) |
| POST | `/api/v1/tickets/{id}/close` | Ticket schließen (nur eigene/nur open; optional rating 1-5 Zufriedenheitsbewertung, außerhalb des Bereichs/keine Ganzzahl 422, nicht angegeben → NULL) |

### 12. Termin-Kalenderschnittstellen (JWT-Authentifizierung erforderlich, Runde 20)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/calendar/technician/{id}` | Monatsansicht (?month=YYYY-MM): time_slots des Schichtplans zu Stunden-Slots erweitert + bereits gebuchte ausgeschlossen |
| GET | `/api/v1/calendar/technician/{id}/day` | Tagesansicht (?date=YYYY-MM-DD): Details zu verfügbaren/gebuchten/nicht verfügbaren Slots des Tages |

### 13. Rechnungsanschriften-Schnittstellen (JWT-Authentifizierung erforderlich, Runde 21)

| Methode | Pfad | Beschreibung |
|------|------|------|
| POST | `/api/v1/invoice-titles` | Anschrift speichern (title_type: personal/company; company muss tax_no; doppelte Anschrift desselben Benutzers 422; erster Eintrag automatisch Standard) |
| GET | `/api/v1/invoice-titles` | Anschriftenliste (Standard zuerst) |
| PUT | `/api/v1/invoice-titles/{id}` | Anschrift bearbeiten (nur eigene) |
| DELETE | `/api/v1/invoice-titles/{id}` | Anschrift löschen (nur eigene; nach Löschen der Standardanschrift wird automatisch der älteste Eintrag zur Standardanschrift) |
| POST | `/api/v1/invoice-titles/{id}/default` | Als Standard festlegen (Transaktion setzt andere Zeilen desselben Benutzers zurück) |

**Antrags-Verknüpfung**: POST /api/v1/invoices unterstützt optionales title_id — Auflösung der Anschrift trägt automatisch invoice_title/tax_no/title_type ein, ohne title_id bleibt der bisherige manuelle Pfad.

### 14. Browser-Verlaufs-Schnittstellen (JWT-Authentifizierung erforderlich, Runde 21)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/browse-history` | Zuletzt angesehene Leistungen (join mit Leistungsname/Cover/Preis/Originalpreis, viewed_at absteigend, per_page Standard 15, Maximum 50) |
| DELETE | `/api/v1/browse-history/{item_id}` | Einzelnen Eintrag löschen (nur eigene, ungültige/fremde 404) |
| DELETE | `/api/v1/browse-history` | Verlauf leeren (nur eigene) |

**Erfassungszeitpunkt**: Wird nach erfolgreichem Aufruf der Servicedetails-Schnittstelle automatisch protokolliert (ohne Login übersprungen; erneutes Ansehen aktualisiert nur viewed_at, kein doppelter Eintrag).

### 15. Rabatt-ab-Mindestbetrag-Schnittstellen (Runde 22)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/full-reduction-activities` | Liste der aktiven Rabatt-ab-Mindestbetrag-Aktionen (status=1 und Zeit im gültigen Zeitraum, absteigend nach Rabattbetrag; öffentliche Schnittstelle) |

**Stapelungsregeln bei der Bestellung**: Rabatt-ab-Mindestbetrag gilt nur für Standardbestellungen (Gruppenkauf/Blitzangebot übersprungen), die Schwelle wird am fälligen Betrag nach Gutschein-/Stempelkarten-Anrechnung gemessen, Stapelungsreihenfolge **Gutschein/Stempelkarte → Mindestbetragsrabatt → Stufenrabatt**; die Aktion mit dem größten Rabattbetrag wird angewendet; der Rabattbetrag fließt in discount_amount ein, die Notiz wird um „满减：满X减Y" ergänzt; Untergrenze des tatsächlichen Zahlbetrags nach Rabatt 0.01 Yuan.

### 16. ICS-Export meiner Termine (JWT-Authentifizierung erforderlich, Runde 22)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/order/ics` | Gültige Bestellungen der letzten 90 Tage (pending/paid/confirmed/serving) als iCal exportieren (RFC5545) |

**Ausgabe**: `Content-Type: text/calendar; charset=utf-8` + `Content-Disposition: attachment; filename="my-appointments.ics"`. VEVENT: UID=Bestell-ID, TZID=Asia/Shanghai, Zusammenfassung „预约：服务名" (bei Fehlen Degradation auf „预约"), Beschreibung (Techniker/Filiale/Adresse, bei Fehlen übersprungen), LOCATION Filialname; Text nach RFC5545 maskiert (\, \; \\ \n) + 75-Byte-Zeilenumbruch. Ohne Bestellungen wird ein gültiger leerer Kalender zurückgegeben; nur eigene Bestellungen werden exportiert.

### 17. Techniker-Anwesenheitsschnittstellen (JWT-Authentifizierung erforderlich, Runde 22)

| Methode | Pfad | Beschreibung |
|------|------|------|
| POST | `/api/v1/technician/attendance/check-in` | Arbeitsbeginn stempeln (Wiederholung am selben Tag 422, eindeutiger Index sichert gegen Konkurrenz; >10:00 wird als Verspätung markiert) |
| POST | `/api/v1/technician/attendance/check-out` | Arbeitsende stempeln (nicht gestempelt/bereits beendet 422, Zeilensperre gegen Konkurrenz) |
| GET | `/api/v1/technician/attendance` | Anwesenheitsliste des Monats + Zusammenfassung Anwesenheitstage/Gesamtstunden/Durchschnittsstunden (?month=YYYY-MM, ungültig 422) |

### 18. Datenschutz-Compliance-Schnittstellen (JWT-Authentifizierung erforderlich, Runde 22)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/privacy/data` | Datencxport (nach personal/orders/points/wallet_txns/reviews/addresses/invoices gruppiertes JSON; Serverlog protokolliert nur maskierte Telefonnummer + Zeilenzahl) |
| POST | `/api/v1/privacy/close-request` | Löschung beantragen (Guthaben ungleich 0 / unfertige Bestellungen / laufende Tickets 422; setzt close_status=1 + close_requested_at) |
| POST | `/api/v1/privacy/close-cancel` | Löschungsantrag stornieren (close_status 1→0) |
| POST | `/api/v1/privacy/close-confirm` | Löschung bestätigen (erst nach 72 h möglich; close_status=2 + close_at + phone/nickname anonymisiert zu user{id} + status=0) |

**Login-Sperre**: Konten mit close_status=2 erhalten beim Login 403 „账号已注销".

### 19. Benutzer-Gesundheitsprofil-Schnittstellen (JWT-Authentifizierung erforderlich, Runde 23)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/health-profile` | Mein Gesundheitsprofil abfragen (ohne Profil leeres Objekt) |
| PUT | `/api/v1/health-profile` | Erstellen/Aktualisieren (upsert, eine Person ein Profil; allergies/health_notes maximal 500 Zeichen, preferred_technician_id Existenzprüfung; nur übergebene Felder aktualisieren, Antwort hashid-codiert) |
| DELETE | `/api/v1/health-profile` | Mein Profil löschen (nur eigene) |

Felder: allergies (Allergien)/health_notes (Gesundheitsnotizen)/preferred_technician_id (bevorzugter Techniker, nullable).

### 20. Wallet-Zahlungspasswort-Schnittstellen (JWT-Authentifizierung erforderlich, Runde 23)

| Methode | Pfad | Beschreibung |
|------|------|------|
| POST | `/api/v1/wallet/pay-password/set` | Zahlungspasswort festlegen (6-stellige Zahl `\d{6}`; wenn bereits festgelegt, muss das alte Passwort übergeben werden, sonst 422) |
| POST | `/api/v1/wallet/pay-password/verify` | Zahlungspasswort prüfen (richtig/falsch gibt Bool zurück, keine Speicherung) |
| POST | `/api/v1/wallet/pay-password/check` | Abfragen, ob festgelegt (set: true/false) |

Speicherung: password_hash()-Hash + pay_password_set_at, Klartext wird niemals gespeichert.

### 21. Bestellstatus-Zeitleisten-Schnittstellen (JWT-Authentifizierung erforderlich, Runde 23)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/order/{id}/timeline` | Zeitlinie der Bestellstatus-Änderungen (absteigend; nur eigene, fremde Bestellungen 404 ohne Existenzpreisgabe) |

Tracker: Einreichung/Zahlung (WeChat-Callback markOrderPaid als einziger Verbrauchspunkt)/Stornierung/Technikerbestätigung/Rückerstattungsantrag/Rückerstattungsgenehmigung/Servicebeginn/Serviceabschluss/Timeout-automatische Stornierung/Backend-Operation (operator=admin) — insgesamt 8 Arten von Änderungen.

### 22. Punkte-Glücksrad-Schnittstellen (JWT-Authentifizierung erforderlich, Runde 23)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/wheel/prizes` | Rad-Preisliste (sensible Felder weight/stock ausgeblendet) |
| POST | `/api/v1/wheel/spin` | Einmal drehen (Redis NX + Zeilensperre gegen Konkurrenz; random_int-Gewichtszug; Punkte→earn-Transaktion inkl. Ablaufzeit, Guthaben→lockForUpdate-Gutschrift, Gutschein→pending manuelle Ausgabe, kein Gewinn→lose; client_token idempotent) |
| GET | `/api/v1/wheel/records` | Meine Drehprotokolle (paginiert) |

### 23. Gastmodus-Schnittstellen (Runde 24)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/guest/home` | Startseiten-Aggregation (Karussell/Ankündigungen/Servicekategorien/beliebte Leistungen, Redis-Cache svc:guest:home 300s) |
| GET | `/api/v1/guest/services` | Service-Liste (?category_id=hashid&sort=newest\|sales\|price&page/per_page≤50) |
| GET | `/api/v1/guest/services/{id}` | Servicedetails (nicht vorhanden 404) |
| GET | `/api/v1/guest/stores` | Filialliste |
| GET | `/api/v1/guest/technicians` | Technikerliste (nur genehmigte; ?service_id=hashid-Filter; Bewertung absteigend) |

Öffentliche Schnittstellen (ohne Authentifizierung); dienen als Browsereinstieg für nicht angemeldete Benutzer.

### 24. Blitzangebots-Schnittstellen (JWT-Authentifizierung erforderlich, Runde 24)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/seckill` | Blitzangebots-Aktivitätsliste (status=1 und im Zeitfenster; enthält verkaufte Menge = Anzahl der appointment_order.seckill_id-Bestellungen, Restbestand) |
| GET | `/api/v1/seckill/{id}` | Aktivitätsdetails (state=not_started/ongoing/ended) |
| POST | `/api/v1/seckill/{id}/buy` | Blitzangebots-Bestellung (client_token idempotent + Redis NX 30s gegen Konkurrenz + Aktivitätsprüfung; kein vorheriges Reservieren des Lagerbestands mehr) |

**Bestellregeln (ab 2026-08-26)**: Der Lagerbestand wird einheitlich in der `/api/v1/order store()`-Transaktion per Zeilensperre abgezogen, buy führt nur Einstiegsprüfung/Idempotenz durch; Blitzpreis = seckill_price (auf DB-Basis), keine Stapelung mit Gutscheinen/Punkten/Mitgliederkarten; Bestellstornierung füllt den Lagerbestand nicht auf; direkter `/api/v1/order`-Aufruf mit seckill_id zieht den Lagerbestand ebenfalls ab.

### 25. APP-Versionsprüf-Schnittstellen (Runde 24)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/api/v1/app/version?platform=android|ios` | Prüfung der neuesten Version (ungültiges platform 422; ohne Version leeres Objekt; öffentliche Schnittstelle) |

Antwort: id/platform/version_code/version_name/force_update (1=erzwungen)/changelog/download_url.

---

## II. Verwaltungsbackend-API (admin/ :8787)

Request-Header: `Authorization: Bearer <admin_token>`; öffentliche Auth-Schnittstellen werden über das URL-Präfix `/api/v1` versioniert

### Dashboard

**`GET /admin/dashboard`** — Dashboard-Daten

Antwort: user_count / order_count / technician_count / today_revenue + Diagrammdaten (Bestellmenge/Betrag/neue Benutzer/Aktivität)

### Benutzerverwaltung

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/user` | Benutzerliste (?keyword/status/page/per_page) |
| POST | `/admin/user` | Benutzer hinzufügen |
| GET | `/admin/user/{id}` | Benutzerdetails |
| PUT | `/admin/user/{id}` | Benutzer bearbeiten |
| DELETE | `/admin/user/{id}` | Benutzer löschen |
| POST | `/admin/user/batch/destroy` | Batch-Löschen |
| POST | `/admin/user/batch/status` | Batch-Aktivieren/Deaktivieren |

### Mitgliederkartenverwaltung

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/member-cards` | Kartenliste (?keyword/status/page/per_page) |
| GET | `/admin/member-cards/{id}` | Kartendetails |
| POST | `/admin/member-cards` | Karte hinzufügen (services JSON-Validierung) |
| PUT | `/admin/member-cards/{id}` | Karte aktualisieren/An- und Abheben |
| DELETE | `/admin/member-cards/{id}` | Karte löschen (abgelehnt, wenn Benutzer die Karte besitzen) |

Berechtigungs-IDs: 365-369.

### Filial-Workbench (Runde 15)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/stores/workbench-overview` | Filial-Workbench-Übersicht (?store_id=hashid: heutige Bestellanzahl/heutiger Umsatz/laufend/Technikeranzahl/heutige Verifizierungen, Berechnungsbasis identisch mit der service-Seite) |
| GET | `/admin/orders` | Bestellliste mit neuem store_id-Filter (hashid-Dekodierung) |

Berechtigungs-ID: 372.

### Punkte-Einlöseartikel (Runde 16)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/points-exchange-goods` | Artikel-Liste (?keyword/status/page/per_page) |
| POST | `/admin/points-exchange-goods` | Artikel hinzufügen (type=coupon/gift_card/wallet; coupon übergibt hashid, wallet/gift_card übergibt Betrag in Yuan) |
| PUT | `/admin/points-exchange-goods/{id}` | Artikel aktualisieren |
| DELETE | `/admin/points-exchange-goods/{id}` | Artikel löschen |
| POST | `/admin/points-exchange-goods/{id}/toggle-status` | An-/Abheben umschalten |
| GET | `/admin/points-exchange-goods/{id}/exchanges` | Einlöseprotokoll-Liste (inkl. Benutzertelefonnummer + result-Schnappschuss) |

Berechtigungs-IDs: 373-378.

### Provisionsprotokoll (Runde 16)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/referral-rewards` | Provisionsprotokoll (?keyword=&page=&limit=, nur bereits ausgezahlte Einträge, Filter nach Nickname oder Telefonnummer von Empfehler/Empfohlenem, hashid-codiert) |

Berechtigungs-ID: 379.

### Technikerstufen (Runde 17)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/technician-tiers/logs` | Stufenänderungsprotokoll (join mit Technikername und alten/neuen Stufennamen, hashid-codiert, paginiert) |

Berechtigungs-ID: 380.

**Automatische Bewertung**: TierRatingService::evaluate berechnet in Echtzeit (Anzahl der appointment_order completed-Bestellungen + Bewertungsdurchschnitt, auf 1 Nachkommastelle gerundet) und schreibt profile.order_count/rating zurück, Abgleich nach appointment_technician_tier_config (min_orders/min_rating) von hoch nach niedrig, ohne Treffer niedrigste Stufe. Nur Aufwertung, keine Abwertung (Abwertung beeinflusst Provisionssatz und Preisfaktor, Backend greift manuell ein; allowDowngrade=true für manuelle Neubewertung); idempotent (bei gleicher Stufe nur Statistik synchronisiert); Änderungen in appointment_technician_tier_log + In-App-Benachrichtigung. Auslöser: WorkController::complete / ReviewController Bewertungs-Einschreibung / ProfileController lazy-Prüfung beim Profilaufruf.

### Bewertungsantwort-Anzeige (Runde 18)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/reviews/{id}/reply` | Bewertungsantwort-Details (decodeId → find → 404 → decorate-Ausgabe; ohne Antwort reply='', reply/replied_at über toArray durchgereicht; statische Route vor resource) |

Berechtigungs-ID: 381 (slug 'get.admin/reviews/{id}/reply').

### Rechnungsverwaltung (Runde 20)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/invoices` | Rechnungsliste (?status=pending/issued/rejected&page=) |
| POST | `/admin/invoices/{id}/issue` | Rechnung ausstellen (invoice_no Pflichtfeld, status→issued + issued_at; idempotent: bereits ausgestellt 422) |
| POST | `/admin/invoices/{id}/reject` | Ablehnen (reject_reason Pflichtfeld, status→rejected; nur pending ablehnbar) |

Berechtigungs-IDs: 382 Liste / 383 Ausstellen / 384 Ablehnen.

### Ticketverwaltung (Runde 20)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/tickets` | Ticketliste (?status=&page=, statische Route vor resource, um Shadowing zu vermeiden) |
| POST | `/admin/tickets/{id}/reply` | Ticket beantworten (content Pflichtfeld, schreibt reply_content/replied_at, Ticket zurück auf open) |
| GET | `/admin/tickets/satisfaction` | Zufriedenheits-Zusammenfassung (Runde 21): total/rated_count/unrated_count/average 1 Nachkommastelle/1-5-Sterne distribution, fehlende Sterne mit 0 aufgefüllt; statische Route vor resource |

Berechtigungs-IDs: 385 Ticket-Antwort / 387 Ticketlisten-Ansicht / 388 Ticket-Zufriedenheitsstatistik.

### Bewertungsbild-Prüfung (Runde 21)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/review-audit` | Bewertungsliste mit Bildern (JSON_LENGTH(images)>0, ?status=visible/hidden&page=, join Benutzernickname und Technikername, ID hashid-codiert) |
| POST | `/admin/review-audit/{id}/hide` | Bewertung ausblenden (nur visible ausblendbar, sonst 422; nach dem Ausblenden ist die Bewertung in der Techniker-Bewertungsliste der Clientseite automatisch unsichtbar) |
| POST | `/admin/review-audit/{id}/restore` | Bewertung wiederherstellen (nur hidden wiederherstellbar, sonst 422) |

Berechtigungs-IDs: 389 Liste / 390 Ausblenden / 391 Wiederherstellen.

### Provisionsprotokoll Stufe 2 (Runde 20)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/referral-level2` | Provisionsprotokoll Stufe 2 (join Nickname von Erstempfehler und Zweitempfehler, paginiert) |

Berechtigungs-ID: 386. Auszahlungsregel: Nach der Bestellzahlung erhält der Empfehler des Erstempfohlenen paid×level2_rate (Systemkonfiguration referral.level2_rate Standard 0.02), uk_order_referred idempotent gegen Dopplungen.

### Anwesenheitsverwaltung (Runde 22)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/attendance` | Anwesenheitsprotokoll (?date=YYYY-MM&name=Technikername&page=; join real_name, ID hashid-codiert) |
| GET | `/admin/attendance/stats` | Nach Techniker gruppierte Statistik (Stempeltage/Gesamtstunden/Durchschnittsstunden; ?date=YYYY-MM, ungültig 422) |

Berechtigungs-IDs: 392 Liste / 393 Statistik.

### Rabatt-ab-Mindestbetrag-Verwaltung (Runde 22)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/full-reduction-activities` | Aktivitätsliste (paginiert) |
| POST | `/admin/full-reduction-activities` | Hinzufügen (threshold/reduction/title/status/start_at/end_at) |
| PUT | `/admin/full-reduction-activities/{id}` | Bearbeiten |
| POST | `/admin/full-reduction-activities/{id}/toggle-status` | An-/Abheben |
| DELETE | `/admin/full-reduction-activities/{id}` | Löschen (mit confirmPassword) |

Berechtigungs-IDs: 396 Liste / 397 Hinzufügen / 398 Bearbeiten / 399 An-/Abheben / 400 Löschen (ein Berechtigungsdatensatz entspricht einem method.path-Slug, daher 5 Routen, 5 Einträge).

### Umsatzbeteiligungs-Protokoll (Runde 22)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/profit-sharing` | Umsatzbeteiligungs-Protokoll (leftJoin Bestellnummer/Technikernickname, ?status&order_no&technician_name&page=, hashid-codiert) |

Berechtigungs-ID: 394. Serverseitige Logik: appointment_system_config group=profit_sharing (enabled/receiver_ratio); bei nicht aktiviert disabled-Degradation nur Log; nach Aktivierung automatische Beteiligungsanfrage bei erfolgreicher Zahlung (Betrag=real gezahlt×receiver_ratio Standard 0.7, bei derselben Bestellung pending/success idempotent übersprungen); ohne Anmeldedaten kein HTTP-Aufruf, Anfragestruktur wird protokolliert.

### Punkte-Glücksrad-Verwaltung (Runde 23)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/lucky-wheel` | Rad-Preisliste (inkl. weight/stock, paginiert) |
| POST | `/admin/lucky-wheel` | Preis hinzufügen (Name/Typ points/balance/coupon/none/Gewicht/Bestand/Bild) |
| GET/PUT | `/admin/lucky-wheel/{id}` | Details / Bearbeiten |
| DELETE | `/admin/lucky-wheel/{id}` | Löschen |
| POST | `/admin/lucky-wheel/{id}/toggle-status` | An-/Abheben |
| GET | `/admin/lucky-wheel/records` | Drehprotokoll (?status&page=, inkl. Benutzernickname/Preisname) |

Berechtigungs-IDs: 401-406. Die statischen Routen `/lucky-wheel/records` und `/lucky-wheel/{id}/toggle-status` werden vor resource registriert, damit {id} sie nicht überschattet.

### Stammkunden-Belohnungsverwaltung (Runde 24)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/return-customer/config` | Konfigurationsansicht (enabled-Schalter / ratio-Verhältnis) |
| PUT | `/admin/return-customer/config` | Konfigurationsaktualisierung (enabled in:0,1; ratio between:0.01,1) |
| GET | `/admin/return-customer/rewards` | Belohnungsprotokoll-Liste (?keyword Technikername/Bestellnummer/Benutzernickname, type=return_customer paginiert) |

Berechtigungs-IDs: 412-414. Belohnungsregel: Beim 2. Konsum eines Benutzers beim selben Techniker innerhalb von 30 Tagen (Bestellabschluss) wird ein Bonus von real gezahlt × ratio (Standard 0.05) ausgezahlt, Eintrag in appointment_technician_earnings (type=return_customer, status=pending), Abrechnung einheitlich über die Provisions-Abrechnungskette; für dieselbe Bestellung idempotent ohne Doppelauszahlung.

### Blitzangebots-Verwaltung (Runde 24)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/seckill` | Aktivitätsliste (paginiert) |
| POST | `/admin/seckill` | Aktivität hinzufügen (name/service_id/seckill_price/original_price/stock/start_at/end_at) |
| GET | `/admin/seckill/{id}` | Aktivitätsdetails |
| PUT | `/admin/seckill/{id}` | Bearbeiten |
| DELETE | `/admin/seckill/{id}` | Löschen |
| POST | `/admin/seckill/{id}/toggle-status` | An-/Abheben |
| GET | `/admin/seckill/{id}/orders` | Blitzangebots-Bestellliste |

Berechtigungs-IDs: 407-411, 420. Verkaufte Menge = Anzahl der appointment_order.seckill_id-Bestellungen; Lagerbestand per Zeilensperre abgezogen, Ausverkauft-Sperre.

### APP-Versionsverwaltung (Runde 24)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/versions` | Versionsliste |
| POST | `/admin/versions` | Version hinzufügen (platform/version_code/version_name/force_update/changelog/download_url/status) |
| PUT | `/admin/versions/{id}` | Bearbeiten |
| DELETE | `/admin/versions/{id}` | Löschen |

Berechtigungs-IDs: 416-419. Die Update-Prüf-Schnittstelle /api/v1/app/version nimmt die neueste Version mit status=1 (größtes updated_at/id).

### Schichtplan-Export (Runde 24)

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/technician-schedule/export` | Schichtplan-CSV-Export (UTF-8 BOM, direkt in Excel zu öffnen; start_date/end_date Pflichtfelder und Spanne ≤31 Tage; technician_id optional hashid) |

Berechtigungs-ID: 415. Spalten: Techniker-ID/Technikername/Datum/Zeitfenster-Details (time_slots JSON zu „09:00-12:00, 14:00-18:00" geparst).

### Rollen-Berechtigungen

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET/POST/PUT/DELETE | `/admin/role` | Rollen-CRUD |
| GET/POST/PUT/DELETE | `/admin/permission` | Berechtigungs-CRUD (Baumstruktur) |

### Systemkonfiguration

| Methode | Pfad | Beschreibung |
|------|------|------|
| GET | `/admin/config` | Konfigurationsliste |
| POST | `/admin/config` | Konfiguration hinzufügen (group/key/value/type/description) |
| PUT | `/admin/config/{id}` | Konfiguration bearbeiten |
| DELETE | `/admin/config/{id}` | Konfiguration löschen |

### Operationsprotokoll

**`GET /admin/log`** — Protokollabfrage

Parameter: `?user_id/action/source/start_date/end_date/page`

`souce`-Feld: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### Export

| Methode | Pfad | Beschreibung |
|------|------|------|
| POST | `/admin/export/excel` | Excel-Export (type: users/technicians/orders/finance). Sensible Felder automatisch maskiert |
| POST | `/admin/export/pdf` | PDF-Panel-Export (type: dashboard) |

### Datei-Upload

**`POST /admin/upload`** — Datei-Upload (multipart/form-data)

### Persönliches Zentrum

| Methode | Pfad | Beschreibung |
|------|------|------|
| PUT | `/admin/profile` | Persönliches Profil ändern |
| PUT | `/admin/profile/password` | Passwort ändern |
| POST | `/admin/profile/logout` | Abmelden |

### Import

**`POST /admin/import/users`** — Benutzer im Batch importieren (Excel)

### Monitoring

| Methode | Pfad | Authentifizierung | Beschreibung |
|------|------|------|------|
| GET | `/health` | keine | Gesundheitsprüfung |
| GET | `/metrics` | keine | Prometheus-Metriken |
| GET | `/.well-known/security.txt` | keine | Sicherheitskontakt (RFC 9116) |
| GET | `/api/docs` | keine | API-Dokumentation |

---

## III. Allgemeine Hinweise

### Fehlercodes

| code | Beschreibung |
|------|------|
| 0 | Erfolg |
| 401 | Nicht angemeldet oder Token abgelaufen |
| 403 | Keine Berechtigung |
| 404 | Ressource nicht vorhanden |
| 422 | Parametervalidierung fehlgeschlagen |
| 429 | Zu viele Anfragen |

### ID-Codierung

- Alle `id`- und `*_id`-Felder in API-Antworten sind mit hashids codiert
- Auch die in Anfragen übergebenen `id`-Parameter sollten im hashids-Codierungsformat sein
- Das Frontend verwendet die codierten Zeichenketten direkt, keine manuelle Dekodierung erforderlich

### Telefonnummern-Maskierung

Format der Telefonnummern in Antworten: `138****8000`. Beim Excel-Export gleich behandelt.

### Datenverschlüsselung

- API-Ebene: sensible Felder in Antworten werden über `erikwang2013/encryption` verschlüsselt
- DB-Ebene: Telefonnummer/Personalausweis/WeChat-ID usw. werden über `erikwang2013/encryptable` automatisch ver-/entschlüsselt

### Umgebungsvariablen-Konfiguration

| Variable | Beschreibung |
|------|------|
| WECHAT_SUBSCRIBE_TEMPLATE_ID | Vorlagen-ID der Abonnementnachricht für Terminerinnerung |
| WECHAT_SUBSCRIBE_TEMPLATE_PAID | Vorlagen-ID der Abonnementnachricht für erfolgreiche Zahlung |
| WECHAT_SUBSCRIBE_TEMPLATE_REFUND | Vorlagen-ID der Abonnementnachricht für Rückerstattung |
| WECHAT_SUBSCRIBE_TEMPLATE_VERIFIED | Vorlagen-ID der Abonnementnachricht für Verifizierung |
| WECHAT_SUBSCRIBE_TEMPLATE_REMINDER | Vorlagen-ID der Abonnementnachricht für Erinnerung vor Servicebeginn (Runde 18) |
| WECHAT_SUBSCRIBE_TEMPLATE_EXPIRY | Vorlagen-ID der Abonnementnachricht für Ablauf-Erinnerung von Mitgliederkarten/Gutscheinen (Runde 18) |

Ohne konfigurierte Abonnementnachricht-Vorlagen automatische Degradation auf In-App-Benachrichtigung.

**Abonnementnachricht-Szenarien**: SCENE_PAY (Zahlung erfolgreich) / SCENE_REFUND (Rückerstattung eingegangen) / SCENE_VERIFIED (Verifizierung erfolgreich) / SCENE_RESCHEDULE (Terminverschiebung erfolgreich) / SCENE_REMINDER (Erinnerung vor Servicebeginn, Runde 18) / SCENE_EXPIRY (Ablauf-Erinnerung, Runde 18). push_sent_at wird nur bei erfolgreichem Push geschrieben, bei Fehlern Wiederholung im nächsten Zyklus.

**Aufladegutschrift-Benachrichtigung (Runde 18)**: Der WeChat-Auflade-Callback (Bestellnummer mit R-Präfix) schreibt innerhalb der Transaktion eine In-App-Benachrichtigung type='wallet_recharge' „您已成功充值 ¥X.XX"; Wiederverwendung der Callback-Idempotenz (nur beim ersten pending→paid ausgelöst), atomarer Commit in derselben Transaktion wie die Statusänderung, Schreibfehler blockieren den Hauptablauf nicht.
