# Funktionsbeschreibung

> Deutsche Übersetzung · Original: [中文](../FEATURES.md)

> **Projektstatus**: vollständig abgeschlossen ✅ | 109 Controller | 103 Modelle | 344 Tests (service 240 / admin 104) | WebSocket | Zahlungsrückmeldungen | Wartenummern | Prüfungen | Community

## I. Kundenseite (WeChat-Miniprogramm + Flutter APP)

Die Funktionalität von Miniprogramm und APP ist vollständig identisch. Ein einheitliches Konto unterstützt den Wechsel zwischen Kunden-/Technikeridentität.

### 1. Authentifizierung

| Funktion | Beschreibung |
|------|------|
| Registrierung mit Telefonnummer | Telefonnummer + Verifizierungscode + Passwort + Passwort bestätigen, Empfehlungscode unterstützt |
| Passwort-Login | Registrierte Telefonnummer + Passwort |
| Verifizierungscode-Login | Registrierte Telefonnummer + Verifizierungscode |
| WeChat-Login | WeChat-Autorisierungslogin, erstmalig Telefonnummer erforderlich |
| Gastmodus | Browsen möglich, keine Bestellung; Bestellung erfordert Registrierung |
| Passwort vergessen | Passwortänderung per Verifizierungscode |
| Nutzungs-/Datenschutzvereinbarung | Im Verwaltungsbackend bearbeitbar, bei Registrierung angezeigt |

### 2. Startseite

| Funktion | Beschreibung |
|------|------|
| LBS-Ortung | Region des Standorts bestimmen, Dienste dieser Region anzeigen, Stadtwechsel unterstützt |
| Karussell | Automatischer Wechsel, Verwaltungsbackend konfiguriert Sprungziele (Web/Details/keine Aktion) |
| Ankündigungen | Laufender Text, Klick öffnet Liste, im Verwaltungsbackend hinzufügbar |
| Servicekategorien | Bild/Name/Preis/Verkaufszahlen, Klick öffnet Details |
| Neukunden-Gutschein | Automatisch bei Registrierung |

### 3. Serviceleistungen

| Funktion | Beschreibung |
|------|------|
| Basisinformationen | Bild/Name/Preis/Verkaufszahlen/Spezifikationen/Dauer/Leistungsdetails |
| Benutzerbewertungen | Bewertungsinhalte anzeigen, mehr ansehen möglich |
| Buchungsservice | Öffnet Bestellbestätigungsseite |
| Filialauswahl | Filialadresse für Vor-Ort-Service (Navigation)/Öffnungszeiten/Kontakttelefon |
| Technikerauswahl | Technikername/Avatar/Bewertung |
| Servicetermin | Buchungszeitraum wählen |
| Schwachlast 10 % Rabatt | 10–12 Uhr/17–18 Uhr/nach 21:00 |
| Vorabbuchung 5 % Rabatt | 30 Minuten im Voraus, nicht mit Gutscheinen kombinierbar |
| Gutscheine | Verfügbaren Betrag anzeigen, verwenden/nicht verwenden |
| Anmerkungen | Anmerkungen zum Servicebedarf (Zeichenlimit) |
| Servicevereinbarung | Vor dem Absenden lesen und bestätigen |

### 4. Produktsuche und Warenkorb

| Funktion | Beschreibung |
|------|------|
| Produktsuche | Suche nach Namen |
| Kategoriefilter | Suche nach Kategorie |
| Produktdetails | Kaufbare Menge/Favorisieren/Teilen/In den Warenkorb/sofort kaufen |
| Warenkorb | Auswählen/Löschen/Menge ändern |

### 5. Bestellungen

| Funktion | Beschreibung |
|------|------|
| Alle Bestellungen | Nach Statustab ansehen |
| Ausstehend | Ansehen/Bezahlen |
| Ausstehender Versand/Selbstabholung | Versand anmahnen/Bestellung stornieren/Ansehen |
| Ausstehender Empfang | Logistikinformationen/Empfang bestätigen |
| Ausstehende Bewertung | Bestelldetails/Bewertung mit Text + Bildern |
| Abgeschlossen | Bestellinformationen ansehen |
| Rückerstattungsregeln | Innerhalb von 15 min nach Bestellung oder >6 h: 100 % / <6 h: 90 % / nach Beginn: 80 % / nach Bestätigung: keine |

### 6. Techniker (Kundenperspektive)

| Funktion | Beschreibung |
|------|------|
| Technikerliste | Entfernung nah bis fern/Avatar/Name/Bestellanzahl/Bewertung/Favorisieren/Entfernung/verfügbare Zeiten/sofort buchen |
| Technikerdetails | Bilder/Name/Entfernung/Bestellungen/Bewertungen/Favorisieren/Liste der Serviceleistungen |
| Techniker-Onboarding | Informationen einreichen, um Techniker zu werden, Techniker-APP herunterladen |

### 7. Techniker-Arbeitsplatz (nach Identitätswechsel)

| Funktion | Beschreibung |
|------|------|
| Tagesübersicht | Heutige Bestellungen/Einnahmen-Übersicht |
| Schichtplan-Einstellungen | Buchbare Zeiträume pro Tag festlegen |
| Meine Bestellungen | Gebucht, nicht verifiziert/Abgeschlossen |
| QR-Verifizierung | Verifizierung per Scan des Benutzer-QR-Codes |
| Mitgliederverwaltung | Liste betreuter Mitglieder/Verbrauchsdaten/Stempelkarten/Profilbearbeitung |
| Einnahmenverwaltung | Heutige Einnahmen/In Abrechnung/Wallet-Guthaben |
| Fonds unterwegs | Verifiziert, nicht abgerechnet, automatische Bestätigung nach 3 Tagen |
| Auszahlung | Am 20. jedes Monats, T+1 auf WeChat-Guthaben; Verwaltungsprüfung, zweistufige Freigabe (Filialleiter → Finanzen) ab 500; Reservierung des Betrags bei Antrag, Nachprüfung vor Freigabeüberweisung, Schutz gegen Doppelauszahlung bei paralleler Freigabe (Härtung 2026-08-26) |
| Anwesenheit | Check-in/Check-out/Fotoupload der Hygiene |
| Stammkunden-Belohnung | Zweitkauf innerhalb von 30 Tagen erfasst Bonus |
| Fachliche Schulung | Videokurse/Bildkurse |
| Heutige Aufgaben | WorkController today: Echtzeitabruf der heutigen Aufgaben |
| Abschlussprotokoll | WorkController records: historische Abschlussprotokolle |
| Dienst starten/abschließen | WorkController start/complete: Zeilensperre + Statusmaschinen-Wächter + Idempotenz, nach Abschluss automatische In-App-Benachrichtigung |
| MiniProgramm-Technikerarbeitsplatz | tech-work mit 3 Tabs: QR-Verifizierung/heutige Aufgaben/Abschlussprotokoll |

### 8. Persönliches Zentrum

| Funktion | Beschreibung |
|------|------|
| Persönliche Daten | Avatar/Nickname/Telefonnummer |
| Identitätswechsel | Kunde ↔ Techniker |
| Nachrichtenbenachrichtigungen | In-App-Benachrichtigungen (erik_notification); Nachrichtenzentrum-Seite: Paginierung/Pull-to-Refresh/gelesen hervorgehoben/als gelesen markieren/alles gelesen |
| Meine Mitgliederkarten | Monatskarte/VIP-Jahreskarte/Stempelkarte (Ablauf/Anzahl/verbraucht/verbleibend) |
| Meine Punkte | Erwerbsprotokoll/verfügbare Punkte/Verwendungsprotokoll (1:100 für Geschenkkarten); Punkte durch Check-in/Kauf, anteilige Rückbuchung bei Rückerstattung, Details mit Paginierung + type/source-Filter |
| Meine Geschenkkarten | Bargeldkarten/Produktgeschenke; cash-Typ wird direkt auf das Wallet aufgeladen |
| Gutscheine | Eingelöst, verfügbar/Verwendet/Abgelaufen |
| Meine Favoriten | Favorisierte Serviceleistungen |
| WeChat-Kanal folgen | QR-Code-Popup, lange drücken zum Speichern |
| Benutzer-Werbung | Werbeerklärung/QR-Poster/Liste empfohlener Benutzer/Punktbelohnungen |
| Feedback | Text + Bilder einreichen, Antwort innerhalb von 24 h |
| Über uns | LOGO/Vorstellung/Kundendiensttelefon/Website/E-Mail |

### 9. Einstellungen

| Funktion | Beschreibung |
|------|------|
| Passwort ändern | Aktuelles Passwort + neues Passwort + neues Passwort bestätigen |
| Telefonnummer ändern | Verifizierungscode aktuelles Telefon + Verifizierungscode neues Telefon |
| Nutzungsvereinbarung | Textanzeige, im Backend bearbeitbar |
| Datenschutzvereinbarung | Textanzeige, im Backend bearbeitbar |
| Update prüfen | Versionsnummer + Update |
| Konto löschen | Löscherklärung + Bestätigungsvorgang |
| Abmelden | Login-Status löschen |

### 10. Guthaben-Wallet (Runde 6)

| Funktion | Beschreibung |
|------|------|
| Wallet-Guthaben | GET /api/wallet Guthaben + Transaktionshistorie (Tabellen user_wallet/wallet_recharge/wallet_txn) |
| Aufladen | POST /api/wallet/recharge erstellt Aufladebestellung; POST /api/wallet/recharge/{id}/pay WeChat-Zahlungsaufladung, Rückmeldung mit R-Präfix-Bestellnummer |
| Guthabenzahlung | Bestellzahlungskanal pay_channel=balance |
| Rückerstattung lädt Guthaben auf | WeChat-/Guthaben-Rückerstattung lädt automatisch auf (refundToBalance / creditRefundToWallet) |

### 11. Abo-Nachrichten (Runde 6+8)

| Funktion | Beschreibung |
|------|------|
| Abo-Szenarien | Bestell-Events in 3 Szenarien: Zahlungserfolg / Rückerstattung eingegangen / Verifizierung erfolgreich |
| Idempotenz | push_sent_at-Markierung gegen doppelte Zustellung |
| Degradation | Nicht konfigurierte Abo-Vorlagen fallen automatisch auf In-App-Benachrichtigung zurück |

### 12. Stempelkarten-Verifizierungskreislauf (Runde 8)

| Funktion | Beschreibung |
|------|------|
| Meine Stempelkarten | GET /api/marketing/cards/my berechnet used_up/expired in Echtzeit |
| Verifizierung mit Abzug | POST /api/marketing/cards/use: Redis NX Idempotenz + lockForUpdate-Zeilensperre, erstellt direkt completed-Bestellung + OrderItem + OrderPayment(pay_type='card') |

### 13. Gutschein-Anrechnung (Runde 9)

| Funktion | Beschreibung |
|------|------|
| Gutscheinwahl bei Bestellung | user_coupon_id optional, PriceCalculator.applyCoupon prüft nur lesend und berechnet |
| Gutscheintypen | fixed fester Betrag / percent Prozent, min_amount-Schwelle |
| Verbrauch und Rückgabe | consume setzt bei Zahlungserfolg used; Rückerstattung gibt über restoreCouponAndCard idempotent zurück |

### 14. Geschenkkarten (Runde 9)

| Funktion | Beschreibung |
|------|------|
| Einlösen | redeem: cash-Typ lädt auf das Wallet (Zeilensperre gegen Doppelbuchung, WalletTxn type='gift_card'), gift-Typ wird nur markiert |
| Meine Geschenkkarten | GET /api/marketing/gift-cards/my |

### 15. Punktesystem (Runde 9+10)

| Funktion | Beschreibung |
|------|------|
| Check-in-Punkte | CheckIn tägliches Check-in |
| Kauf-Punkte | Bei Verifizierung floor(paid×1), order_id idempotent, balance-Snapshot |
| Rückbuchung bei Rückerstattung | clawbackOrderPoints anteilige Rückbuchung (3 Anbindungen) |
| Punkte gegen Geld | use_points bei Zahlung, 100 Punkte = 1 Yuan (config app.points_rate), SUM-Aggregation prüft Saldo, Verbrauchsbuchung source=points_offset idempotent |
| Punkterückbuchung (Runde 15) | Stornierung/Rückerstattung gibt points_offset-Punkte zurück: refundOffsetPoints mit 5 Anbindungspunkten (doCancel 3 Pfade/doRefund WeChat-Transaktion/creditRefundToWallet/completeOneRefundCompensation), source=points_refund idempotent |
| Punktedetails | GET /api/marketing/points Paginierung + type/source-Filter, type einheitlich earn |

### 16. MiniProgramm-Bestellkette (Runde 10)

| Funktion | Beschreibung |
|------|------|
| Dienst-Detailseite | service/detail |
| Bestellbestätigungsseite | order/confirm: Gutscheinwahl/Schwellenwert ausgegraut/Client-Schätzung → POST /order → WeChat-/Guthabenzahlung |
| Seitenumfang | Das Miniprogramm hat jetzt insgesamt 20 Seiten |

### 17. Drei Kunden-Einstiege (Runde 10)

| Funktion | Beschreibung |
|------|------|
| Favoriten | favorite-Favoritenseite (Einstieg über user-Seite) |
| Werbung | referral: Einladungscode/Link kopieren/Liste empfohlener Benutzer |
| Feedback | feedback-Formular |

### 18. Abo-Nachrichten-Autorisierung (Runde 14)

| Funktion | Beschreibung |
|------|------|
| Abo-Autorisierung | utils/subscribe.js verwaltet Vorlagen-IDs zentral (Schlüsselnamen abgestimmt mit erik_system_config.wechat_app.template_ids des Servers) |
| Auslöseszenarien | wx.requestSubscribeMessage im Geste-Rückruf nach Buchungserfolg/Zahlungserfolg, still bei nicht konfigurierten Vorlagen-IDs oder Benutzerablehnung |
| Serverkette | WechatTemplateMessageService sendet + NotificationReminderService erinnert 2h–1h vor Buchung + AutoCancelTimer-Prozess scannt |

### 19. Kundendienst: Rückgabe/Umtausch (Runde 14)

| Funktion | Beschreibung |
|------|------|
| Kundendienst beantragen | POST /api/aftersales: type=refund/exchange, prüft eigene Bestellung/paid+completed/Deduplizierung pro Bestellung |
| Meine Kundendienste | GET /api/aftersales Paginierung + GET /api/aftersales/{id} Details |
| Prüfungsablauf | Verwaltung approve/reject (rejected mit Pflicht-remark); approved nur Statuswechsel, Rückerstattung nutzt die Bestellrückerstattungsschnittstelle |

### 20. Gruppeneinkauf/Blitzangebot (Runde 15)

> Seit 2026-08 ist der FLASH_SALE-Kanal abgeschaltet: PromotionController::index filtert flash_sale, show/join geben dafür 400 zurück, Blitzangebote laufen einheitlich über den Kanal „43. Blitzangebot (Runde 24)"; die Konstante `Promotion::TYPE_FLASH_SALE` bleibt für historische Daten kompatibel. Dieser Abschnitt sowie „27. Blitzangebot-Bestellung" sind historische Aufzeichnungen.

| Funktion | Beschreibung |
|------|------|
| Aktionsliste/Details | GET /api/promotions + /api/promotions/{id}, type-Filter group_buy/flash_sale |
| Teilnahme | POST /api/promotions/join/{id}: Redis NX-Sperre gegen Überverkauf (flash_sale mit max_people als Bestandsobergrenze), 422 bei erneuter Teilnahme, Vollbelegungssperre bei group_buy, träge Schließung bei Ablauf ohne Vollbelegung (status bei show/join auf 0 gesetzt) |
| Teilnehmerliste | GET /api/promotions/{id}/participants |
| Statusreparatur | PromotionParticipant-Status auf Integer-Konstanten 0/1/2/3 umgestellt (behebt join-1366-Beschädigung im strikten Modus) |

### 21. Gruppeneinkauf-Bestellung nach Gruppenbildung (Runde 16)

| Funktion | Beschreibung |
|------|------|
| Gruppenpreis | join-Antwort liefert discount_percent/original_price/group_price |
| Gruppenbestellung | POST /api/order mit promotion_id: prüft nur group_buy/Aktivität aktiv/Aufrufer ist Teilnehmer/nicht voll/Service passt; Gruppenpreis = Originalpreis×discount_percent/100, Gutscheine/Stempelkarten/Punkte nicht kombinierbar (422) |
| Bestellmarkierung | erik_order neue Spalten promotion_id/participant_id + Index |
| Nicht gebildete Gruppe | Bei Ablauf ohne Vollbelegung → Aktivität schließen + batchweises Stornieren der pending-Bestellungen dieser Aktivität (idempotent); pay() prüft träge, ob geschlossen, storniert dann automatisch und gibt die Technikersperre frei |

### 22. Vertriebs-Provisionen (Runde 16)

| Funktion | Beschreibung |
|------|------|
| Vergaberegeln | Nach der ersten abgeschlossenen Bestellung des Geworbenen: Betrag = paid_amount×reward_rate (erik_system_config referral.reward_rate, Standard 0,05, ungültige Werte fallen auf Konstante zurück), nur bei >0 |
| Anbindungspunkt | ReferralRewardService::handleOrderCompleted innerhalb der Transaktion von WorkController::complete (serving→completed ist der einzige Einstieg, Verifizierung verify führt nur bis serving und löst nicht aus), bei Fehler Gesamtrollback, wiederholbar |
| Idempotenz | erik_user_referral-Zeilensperre lockForUpdate + rewarded_at-Nullprüfung + Erstbestellungs-Nachprüfung in der Sperre (parallele/wiederholte Aufrufe zahlen nur einmal) |
| Verbuchung | Wallet-Zeilensperre kumuliert + WalletTxn type='referral_reward' (balance_after + Bestellnummer remark); Empfehlungsdatensatz schreibt reward_type/reward_amount/rewarded_at/first_order_at |
| Details | GET /api/user/referral/earnings Paginierung (Nickname/Avatar des Geworbenen/Bestellnummer/Betrag/Zeit) |

### 23. Punkte-Einlöse-Shop (Runde 16)

| Funktion | Beschreibung |
|------|------|
| Einlöseartikel | erik_points_exchange_goods: type=coupon/gift_card/wallet, points_cost/value (DECIMAL(25,2) gegen Snowflake-ID-Präzisionsverlust)/stock/status |
| Artikelliste | GET /api/marketing/points-exchange: veröffentlichte Artikel + verbleibender Bestand in Echtzeit + bereits eingelöst |
| Einlösen | POST /api/marketing/points-exchange/{id}: Redis NX-Sperre + Artikel-Zeilensperre gegen Über-Einlösung; Punkte-SUM-Prüfung (422 bei unzureichend) + UserPoints type='consume' source='exchange' Abzug; coupon vergibt Gutschein / wallet verbucht Guthaben (WalletTxn points_exchange) / gift_card liefert Kartencode zurück |
| Idempotenz | uk_user_goods-Index begrenzt gleichen Benutzer auf einmal pro Artikel + Nachprüfung in der Sperre + 1062-Absicherung; Einlöseprotokoll-Snapshot erik_user_points_exchange |

### 24. Buchungsumänderung (Runde 17)

| Funktion | Beschreibung |
|------|------|
| Schnittstelle | POST /api/order/reschedule/{id}: new_service_time (Pflicht) + reason (optional), gleicher Techniker, andere Zeit |
| Regeln | Nur eigene Bestellungen (404 bei fremder); nur appointment-Typ und Status pending/paid/confirmed (sonst 422); ≥6 Stunden vor ursprünglichem Dienstbeginn (entspricht dem Vollrückerstattungsfenster) |
| Parallelitätsschutz | B1 order_lock (gleiche Mutex-Familie wie pay/cancel/refund) → Technikersperre für neuen Zeitraum Redis SETNX EX 180 (gegen Überverkauf bei paralleler Umänderung) → Zeilensperre-Nachlesen in Transaktion + B2 Schichtplanungskonflikt-DB-Prüfung (eigene Bestellung ausgenommen) |
| Abschluss | service_time aktualisieren + erik_order_reschedule schreiben (inkl. reason) + Sperren des alten Zeitraums freigeben/neuen Zeitraum für diese Bestellung behalten; bei Transaktionsfehler Rollback + Freigabe der neuen Zeitraumsperre |
| Benachrichtigung | SCENE_RESCHEDULE-Abo-Nachricht (ohne Vorlage Fallback auf In-App-Benachrichtigung „Buchung erfolgreich umgeändert") + pushOrderUpdate |

### 25. Gutschein-Weitergabe (Runde 17)

| Funktion | Beschreibung |
|------|------|
| Schnittstellen | POST /api/marketing/coupons/transfer (user_coupon_id) erzeugt 8-stelligen deobfuszierten eindeutigen Weitergabecode (uk_code-Absicherung, 7 Tage gültig); POST /api/marketing/coupons/claim (code) einlösen; GET /api/marketing/coupons/transfers gesendete (pending/claimed/expired) + erhaltene (claimed) mit Paginierung |
| Prüfung | Gutschein gehört Benutzer/available/Gutscheindefinition nicht abgelaufen/nicht weitergegeben (422); eigene weitergegebene Gutscheine nicht einlösbar, Empfänger nicht der ursprüngliche Inhaber |
| Missbrauchsschutz | Redis NX-Sperre coupon_transfer_claim:{code} (30 s) + Zeilensperre-Nachprüfung in Transaktion gegen Doppelverwendung; uk_user_coupon-Index begrenzt Weitergabe pro Gutschein auf einmal; weitergegebene Gutscheine nicht erneut weitergebbar (neuer Gutschein ohne Weitergabeprotokoll wird natürlich blockiert); träge Ablaufprüfung setzt expired + stellt Originalgutschein auf available |
| Einlösen | In Transaktion Originalgutschein auf used + neues UserCoupon für Empfänger erzeugen (coupon_id unverändert, d. h. Gültigkeit unverändert) + Weitergabeprotokoll auf claimed |

### 26. Punkteablauf (Runde 17)

| Funktion | Beschreibung |
|------|------|
| Gültigkeit | Spalte erik_user_points.expires_at; alle earn (Check-in/Kauf-Punkte/Rückbuchung) schreiben expires_at = now + points.expiry_days (Standard 365, ≤0 nie ablaufend); consume/use lassen leer |
| Ablaufausführung | PointsExpiryTimer-Timerprozess scannt alle 60 s mit Cursor (100/Stapel) earn-Zeilen mit expires_at < now → schreibt type=expire als negativen Abzug (source=expiry + order_id verweist auf ursprüngliche Buchung) → pro Benutzer aggregierte In-App-Benachrichtigung „Ihre X Punkte sind abgelaufen" |
| Idempotenz | ① expire-Zeile order_id zeigt auf ursprüngliche earn-Buchung, in Transaktion lockForUpdate der Originalzeile + exists-Nachprüfung (parallele Prozesse serialisieren an der Zeilensperre) ② id-Cursor-Paginierung ③ Benachrichtigung nur in tatsächlichen Abzugsrunden |
| Berechnung | Verfügbarer Saldo SUM-Aggregation inklusive expire-Negativzeilen; abgelaufene Punkte nicht mehr gegen Geld/Produkte einlösbar |

### 27. Blitzangebot-Bestellung (Runde 18, eingestellt)

> Wurde durch den `/api/seckill`-Kanal aus Runde 24 ersetzt (store()-Promotionszweig enthält nur noch Gruppeneinkauf), siehe „43. Blitzangebot".

| Funktion | Beschreibung |
|------|------|
| Schnittstelle | POST /api/order mit promotion_id (Typ flash_sale): Blitzpreis = round(total × (100 − discount_percent)/100, 2), identisch mit der Blitzpreis-Berechnung von PromotionController |
| Prüfung | Typ-Whitelist [group_buy, flash_sale] (sonst 422); Aktivität läuft; Aufrufer ist Teilnehmer; Bestellservice passt zur Aktivität; ausverkauft participants_count ≥ max_people 422 „ausverkauft"; Gutscheine/Stempelkarten/Punkte nicht kombinierbar 422 |
| Ablauf | pay() prüft träge isFlashSaleClosed (Muster wie isGroupBuyClosed): Blitzangebot abgelaufen → Aktivität auf 0 + batchweises Stornieren der pending-Bestellungen + automatische Stornierung dieser Bestellung + Technikersperre freigeben 422 |

### 28. Service-Erinnerung + Ablauf-Erinnerung (Runde 18)

| Funktion | Beschreibung |
|------|------|
| Erinnerung vor Dienstbeginn | ServiceReminderTimer scannt alle 60 s service_time ∈ [now+1h, now+1h+60s), Status confirmed/serving, appointment-Typ → In-App-Benachrichtigung (type='service_reminder', inkl. Service/Techniker/Filiale/Zeit) + SCENE_REMINDER-Abo-Nachricht |
| Ablauf-Erinnerung | ExpiryReminderTimer scannt alle 6 h end_at ∈ (now, now+3d+6h]: aktive Mitgliederkarten (type='card_expiry') + verfügbare Gutscheine (type='coupon_expiry', whereHas verknüpfte Gutscheindefinition end_at) + SCENE_EXPIRY-Abo-Nachricht |
| Idempotenz | Beide mit id-Cursor 100/Stapel + Zeilensperre-Nachprüfung in Transaktion + Benachrichtigungs-Deduplizierung (Spalte order_id protokolliert Quell-id/Bestell-id als Anti-Duplikat-Schlüssel); push_sent_at nur nach erfolgreicher Abo-Zustellung, sonst nächste Runde erneut |
| Degradation | Nicht konfigurierte Vorlagen (WECHAT_SUBSCRIBE_TEMPLATE_REMINDER / _EXPIRY) fallen automatisch auf nur In-App-Benachrichtigung zurück |

### 29. Techniker-Antwort auf Bewertungen (Runde 18)

| Funktion | Beschreibung |
|------|------|
| Schnittstelle | POST /api/technician/review/reply/{order_id} (Techniker-Identitätsmiddleware): Bewertung nicht vorhanden/fremd einheitlich 404; bereits vorhandene Antwort 422 (idempotente Ablehnung ohne Überschreiben); leere Antwort 422 |
| Nach der Antwort | In-App-Benachrichtigung an Benutzer (type='review_reply', nicht blockierend try/catch + Log) |
| Daten | erik_order_review idempotent um Spalte replied_at ergänzt (reply-Spalte existierte bereits bei Tabellenanlage); Verwaltungs-Bewertungsliste/show gibt über decorate()->toArray() reply/replied_at aus |

### 30. Auflade-Benachrichtigung (Runde 18)

| Funktion | Beschreibung |
|------|------|
| Schnittstelle | WeChat-Auflade-Rückmeldung (R-Präfix-Bestellnummer) handleRechargeNotify in Transaktion: nach WalletTxn In-App-Benachrichtigung type='wallet_recharge', „Sie haben erfolgreich ¥X.XX aufgeladen" (Betrag in Yuan, number_format 2 Stellen) |
| Idempotenz | Nutzt vorhandene Rückmeldungs-Idempotenz (Aufladebestellungs-Zeilensperre lockForUpdate + Status-Nachprüfung, nur erstmaliges pending→paid erreicht die Benachrichtigung); Benachrichtigung und Statusänderung atomar in derselben Transaktion, keine Crash-Lücke; Signaturfehler/Bestellung nicht vorhanden/Betragsabweichung schreiben keine Benachrichtigung |
| Fehlertoleranz | Benachrichtigungsschreiben in try/catch, Fehler nur Warning-Log, blockiert Hauptablauf nicht |

### 31. Guthaben-Überweisung (Runde 19)

| Funktion | Beschreibung |
|------|------|
| Schnittstelle | POST /api/wallet/transfer: Empfänger-hashid dekodieren + Existenz 404, an sich selbst 422, Betrag 0,01–1000 pro Transaktion 422 (DECIMAL-Vergleich, kein float), unzureichendes Guthaben 422, Tageskumulierung 5000 Yuan 422 |
| Parallelität/Idempotenz | Redis NX-Sperre wallet_transfer:{from} 30 s serialisiert den Sender; in Transaktion lockForUpdate der Wallet-Zeilen in aufsteigender user_id-Reihenfolge (feste Reihenfolge gegen Deadlocks); client_token nach Erfolg SETNX 24 h gegen doppelte Einreichung (fehlgeschlagene Anfragen hinterlassen kein Token, wiederholbar) |
| Verbuchung | Sender abbuchen + Empfänger gutschreiben + WalletTxn-Doppelbuchungen (transfer_out/transfer_in inkl. balance_after-Snapshot) + Überweisungsprotokoll completed + In-App-Benachrichtigung an Empfänger type='balance_received' (Fehler nur Log) |
| Protokolle | GET /api/wallet/transfers (direction=out/in Paginierung) + GET /transfers/{id} (nur beide Seiten sichtbar, sonst 404) |

### 32. Punkte-Weitergabe (Runde 19)

| Funktion | Beschreibung |
|------|------|
| Schnittstelle | POST /api/user/points/transfer: Empfänger existiert 404, an sich selbst 422, Punkte 1–10000 422, unzureichender SUM-Saldo 422, Tageslimit 10000 422 |
| Parallelität/Idempotenz | Redis NX-Sperre points_transfer:{user} 30 s; in Transaktion lockForUpdate der letzten Buchungen beider Seiten (aufsteigende user_id gegen Deadlock bei gegenseitiger Übertragung) + Nachprüfung von Saldo/Limit/Empfänger in der Sperre |
| Buchungsstandard | Sender type=consume source=points_transfer negativ (balance = letzter Snapshot − diesmal, gleiche Berechnung wie points_offset/exchange); Empfänger type=earn source=points_transfer positiv inkl. expires_at (PointsExpiryTimer kann normal ablaufen lassen); Weitergabeprotokoll in Transaktion, nach Commit In-App-Benachrichtigung an Empfänger type='points_received' |
| Protokolle | GET /api/user/points/transfers (direction=sent/received Paginierung, Nickname des Gegenübers) |

### 33. Bewertungs-Nachtrag + Routenvervollständigung (Runde 19)

| Funktion | Beschreibung |
|------|------|
| Nachtrag | POST /api/order/review/{order_id}/append: Bewertung nicht vorhanden/fremd einheitlich 404, nicht completed 422, doppelter Nachtrag 422 (append_content/append_at nicht leer wird abgelehnt), leerer Inhalt 422; bei Erfolg append_content/append_images(JSON)/append_at schreiben + In-App-Benachrichtigung an Techniker type='review_append' |
| Bewertung abgeben | POST /api/order/review/{order_id} registriert (ReviewController::store hatte keine Route, unerreichbar); nebenbei latente TypeError behoben: findByOrderId erhielt int und verletzte die string-Signatur (vgl. (string)-Konvertierung bei append), die Registrierung legte den 500 bei jedem Aufruf offen |
| Daten | erik_order_review um drei Spalten ergänzt: append_content TEXT/append_images JSON/append_at DATETIME (idempotente Migration); Antwort gibt append-Felder aus |

### 34. Sendungsverfolgung für Kunden (Runde 19)

| Funktion | Beschreibung |
|------|------|
| Schnittstelle | GET /api/order/logistics/{id}: nur eigene product-Bestellungen abrufbar (fremd/nicht Produkt/nicht versendet einheitlich 404) |
| Daten | Liest order.remark JSON (shipping_company/tracking_no/shipped_at, von admin MallOrderController::ship() beim Versand geschrieben); parseShippingInfo/parseReceiver doppelte Parsing-Absicherung für alte Formate |
| Maskierung | Empfängernummer maskPhone (138\*\*\*\*5678), gegen Datenlecks |

### 35. Nachrichteneinstellungen (Runde 19)

| Funktion | Beschreibung |
|------|------|
| Daten | Tabelle erik_user_notify_setting (zusammengesetzter Unique-Key user_id+type uk_user_type, fehlende Zeile = Standard an); 5 Typen: service_reminder Service-Erinnerung / card_expiry Ablauf-Erinnerung (Karte + Gutschein einheitlicher Schirm) / points_expiry Punkteablauf / marketing Marketing (reserviert) / system System (nicht abschaltbar, PUT erzwingt 1) |
| Schnittstellen | GET /api/user/notify-settings liefert alle 5 Schalter; PUT batch-upsert ohne doppelte Zeilen |
| Steuerung | NotificationReminderService::notifySettingEnabled an 3 Timerprozessen (ServiceReminderTimer/ExpiryReminderTimer Karte+Gutschein/PointsExpiryTimer, Timer schreiben direkt in erik_notification und laufen nicht über den Dienst-Schreibpfad, daher gleiche Steuerung eingebaut) + Abo-Events (sendSubscribeForOrderEvent/Notification Szenario-Mapping PAY/REFUND/VERIFIED/RESCHEDULE→system immer gesendet, REMINDER→service_reminder, EXPIRY→card_expiry); bei deaktiviertem Typ werden In-App-Benachrichtigung und Abo-Nachrichten übersprungen |

---

## II. Verwaltungsbackend (PC Web)

Flutter-Web-Single-Page-App mit insgesamt 21 Seiten: Dashboard/Benutzer/Rollen/Konfiguration/Logs/Verifizierung/Schichtplanung/Dienste/Techniker/Bestellungen/Gutscheine/Mitglieder/Stempelkarten/Ankündigungen/FAQ/Auszahlungen/Bewertungen/Berichte/Profilzentrum/Filialarbeitsplatz.

### 1. Start-Dashboard

- Echtzeit-Statistik: Benutzerzahl/Bestellsumme/Technikerzahl/Servicebestellungen
- Liniendiagramme: Bestelltendenz/Betragstendenz/neue Benutzer/Aktivität
- Schnellnavigation: Buttons für zu bearbeitende Module
- In-App-Nachrichten: Neue-Bestellung-Benachrichtigungen/Rückerstattungsbenachrichtigungen

### 2. Technikerverwaltung

- Technikerliste: Suche nach UID/Telefonnummer/Name/Herkunft/Registrierungszeit
- Listendarstellung: Nummer/UID/Telefonnummer/Nickname/Empfehler/Status/Schülerzahl/Leistung/Kontostatus/Registrierungszeit/letzter Login/Herkunft
- Aktionen: Exportieren/übergeordneten Benutzer ändern/Untergeordnete ansehen/Passwort und Telefonnummer ändern/Schichtplanverwaltung/Techniker-Serviceleistungs-Einstellungen/Kursfortschritt ansehen
- Neu: Name/Geschlecht/Telefonnummer/Personalausweis/Personalausweis-Foto
- Onboarding-Bewerbungen prüfen

### 3. Benutzerverwaltung

- Mitgliederliste: Name/Telefonnummer/Avatar/Stufe/Ausgabebetrag
- Suche: UID/Telefonnummer/Nickname/Registrierungszeit
- Aktionen: Details/übergeordneten Benutzer ändern/Untergeordnete ansehen/Passwort und Telefonnummer ändern/Mitgliederstufe festlegen

### 4. Filialverwaltung

- Filialliste: Aktivieren/Deaktivieren/Löschen
- Neue Filiale: Name/Adresse/Koordinaten/Telefon/Öffnungszeiten/Bilder

### 5. Serviceverwaltung

- Serviceliste: Suche nach Name/Kategorie; Nummer/Name/Typ/Rabatt/Mindestpreis/Verkaufszahlen/Cover/Reihenfolge/Status/Zeit
- Aktionen: Neu/Ändern/Löschen/Kartendesign
- Produktliste: Typ/Name/Rabatt/Mindestpreis/Verkaufszahlen/Lagerbestand/Cover/Reihenfolge/Status/Zeit

### 6. Shop-Verwaltung

- Shop-Bestellungen: Details/Versand/Logistik/Drucken
- Kundendienst-Bestellungen: Ansehen/Prüfen/Drucken
- Bewertungsverwaltung: Ansehen/Prüfen (show/hide)/Löschen (ReviewController index/show/audit/destroy)
- Zahlungsbuchungen
- Verkaufsstatistik

### 7. Bestellverwaltung

- Nicht verwendete Bestellungen: Suche mit mehreren Bedingungen
- Aktionen: Details/Plattform-Stornierung/Abschluss bestätigen

### 8. Gutschein-Aktionen

- Liste: Nummer/Bild/Typ/Name/Veröffentlicht/Zurückgezogen/Gesamt/Verbleibend/Admin/Zeit/Enddatum
- Aktionen: Neu/Ändern/Löschen

### 9. Finanzverwaltung

- Bestell-Profit-Sharing: Suche/Details
- Techniker-Auszahlungen: WithdrawalController-Prüfung; zweistufige Freigabe ab 500 (Filialleiter store_approved_at → Finanzen finance_approved_at); Statusmaschine pending→approved→completed (rejected/failed)
- Provisions-Einstellungen: Provisionssatz/Abrechnungszyklus/Boni und Strafen/Saldo ändern
- Einnahmen-Ausgaben-Buchungen
- Auszahlungskontenverwaltung
- Auszahlungslimit-Konfiguration

### 10. Inhaltsverwaltung

- Karussell-CRUD
- Über-uns-Einstellungen
- Moment-Prüfung
- FAQ-CRUD
- Feedback-Bearbeitung
- Plattform-Ankündigungs-CRUD

### 11. Einstellungen

- Plattformvereinbarungen bearbeiten (Nutzungs-/Datenschutz-/Servicevereinbarung)
- Einheitliche Techniker-Provision
- Systemnachrichten-Vorlagen (inkl. MiniProgramm-Abo-Vorlagenkonfiguration, ohne Konfiguration Fallback auf In-App-Benachrichtigung)
- Unterkonto-Berechtigungsverwaltung (Filialleiter kann Gutscheine ausgeben + Schichtpläne)

### 12. Erweiterte Funktionen

- Kartendesign: Leistungs-+Produktkombination/Handwerkergebühr/Provisions-Einstellungen
- Systemmonitor: Echtzeit-Dashboard für CPU/Arbeitsspeicher/Disk/Redis/MySQL/Queue
- IP-Blacklist: Visualisierung der security-php-Angriffsprotokolle + manuelle Sperren
- Datenbanksicherung: Webschnittstelle für Backup/Download/Wiederherstellung
- Kundenprofil: 360-Grad-Sicht/Konsumpräferenzen/segmentiertes Marketing
- Batch-Push: Vorlagen-Nachrichten/segmentierter Massenversand
- Rückerstattungsprüfungsablauf: zweistufige Freigabe (Filialleiter → Finanzen)
- Technikerstufen: junior/senior/expert automatische Einstufung
- Geplante Aufgaben: automatische Stornierung/Abrechnung/Ablaufbehandlung
- SMS-Konfiguration: Alibaba Cloud/Tencent Cloud Multi-Kanalverwaltung
- Speicherkonfiguration: lokal/OSS/COS/CDN
- Berichtsverbesserungen: benutzerdefinierte Felder/geplante E-Mail-Berichte
- Schichtplan-Export: Excel-Export von Buchungsprotokollen/Anwesenheitslisten
- Techniker-Geschlechtsbeschränkung: Geschlechtskontrolle für bestimmte Leistungen
- Techniker-Schulung: Kursverwaltung/Lernfortschrittsverfolgung
- Filialleiter-Konto: store_id-Datenisolierung + spezielle Berechtigungen

### 13. Datenberichte (Runde 7)

- ReportController 3 Endpunkte: Bestellstatistik / Technikerleistung / Filialverteilung
- Redis-Cache svc:admin_report:{type}:{start}:{end}, TTL 300

### 14. Mitgliederkartenverwaltung (Runde 10)

- erik_user.member_level Mitgliedsstufenspalte (Migration 000008)
- MemberCardController vollständiges CRUD (Berechtigungen 365–369): GET/POST/PUT/DELETE /admin/member-cards
- Flutter-Seite zur Verwaltung der Mitgliederkartendefinitionen

### 15. Kundendienst-Verwaltung (Runde 14)

- Tabelle erik_order_aftersale (Migration 000009): type=refund/exchange, status=pending/approved/rejected/completed
- AftersaleController: GET /admin/aftersales (Paginierung + status/uid/order_no-Filter) + POST /admin/aftersales/{id}/review (approve/reject+remark)
- Flutter-Kundendienst-Seite (Liste + Prüfdialog, Berechtigungen 370/371), Layout registriert

### 16. Filialleiter-Arbeitsplatz (Runde 15)

- service /api/store-manager: overview (heutige Bestellungen/Umsatz/laufend/Technikerzahl/Verifizierungszahl) + orders (Paginierung + Statusfilter) + technicians (inkl. heutigem Schichtplan) + revenue (Aggregation der letzten 7 Tage), requireStoreId() erzwingt store_id-Isolierung (403 ohne Filiale)
- admin StoreController::workbenchOverview (GET /admin/stores/workbench-overview?store_id=, Berechnung wie service) + AppointmentOrderController Bestellliste mit store_id-Filter (hashid-Dekodierung)
- Flutter-Filialarbeitsplatz-Seite: Filial-Dropdown + Statusfilter + 5 Übersichtskarten + Bestell-DataTable + Paginierung (Berechtigung 372)

### 17. Punkte-Einlöseartikel (Runde 16)

- PointsExchangeGoodsController: GET/POST/PUT/DELETE /admin/points-exchange-goods + POST {id}/toggle-status (Veröffentlichen/Zurückziehen) + GET {id}/exchanges (Einlöseprotokolle, inkl. Telefonnummer + result JSON-Parsing)
- Migrationen 000012 (zwei Tabellen) + 000013 (Berechtigungen 373–378) angewendet

### 18. Provisionsprotokolle (Runde 16)

- ReferralRewardController: GET /admin/referral-rewards (nur Datensätze mit nicht leerem rewarded_at, Paginierung + keyword-Filter nach Nickname oder Telefonnummer von Empfehler/Geworbenem, hashid-codiert, Berechtigung 379)

### 19. Automatische Techniker-Einstufung (Runde 17)

- TierRatingService::evaluate(technicianId, allowDowngrade=false): Echtzeit-Statistik der erik_order completed-Bestellungen + erik_order_review-Durchschnitt (gerundet auf 1 Nachkommastelle) zurück ins profile.order_count/rating, Abgleich mit erik_technician_tier_config (min_orders/min_rating) von hoch nach niedrig, ohne Treffer niedrigste Stufe
- Auf-/Abstufungsregeln: nur Aufwertung ohne Abwertung (Stufe ist an Provisionssatz und Preiskoeffizient gebunden, automatische Abwertung beeinflusst das Technikereinkommen und führt leicht zu Streit, Rückgang wird manuell vom admin aufgefangen); nur mit allowDowngrade=true (Szenario manuelle Neubewertung im Backend) wird abgestuft, Abwertung protokolliert + benachrichtigt ebenfalls
- Idempotenz: Wenn die Soll-Stufe mit profile.tier_id übereinstimmt, werden nur Statistiken synchronisiert, kein Log und keine Benachrichtigung
- Log: Änderungen schreiben erik_technician_tier_log (id/technician_id/old_tier_id/new_tier_id/reason/created_at) + In-App-Benachrichtigung (type='tier')
- Auslösepunkte: WorkController::complete / Bewertungsschreiben in ReviewController / Profilabruf in ProfileController träge Prüfung
- Verwaltung: TechnicianTierController behält manuelle Konfiguration; GET /admin/technician-tiers/logs Paginierung der Änderungsprotokolle (join Technikername und alte/neue Stufennamen, ID hashid-codiert, Berechtigung 380)

### 20. Bewertungsantwort-Anzeige (Runde 18)

- ReviewController neue Methode reply(): GET /admin/reviews/{id}/reply Antwortdetails (decodeId → find → 404 → decorate-Ausgabe, bei keiner Antwort reply='', reply/replied_at über toArray ausgegeben)
- Route als statische Route (vor audit, vor der resource-Definition); Berechtigungs-Seed id 381 (slug 'get.admin/reviews/{id}/reply', type 3, idempotente Verknüpfung mit Superadmin-Rolle)
- Berechtigungspunkt: 381

### 21. Buchungskalender (Runde 20)

- CalendarController Monats-/Tagesansicht: GET /api/calendar/technician/{id} (Monatsansicht) + /day (Tagesansicht)
- Datenquelle: technician_schedule.time_slots JSON pro Wochentag zu Stunden-Slots expandiert, bereits gebuchte Zeiträume in erik_order an diesem Tag ausgeschlossen (status ∈ pending/paid/confirmed/serving), verbleibende buchbare Slots ausgegeben
- Zweck: visuelle Zeitwahl für Filial-Schichtplanung, Frontend horizontaler Tages-Scroll + Zeitpunktauswahl

### 22. Kunden-Wachstumsstufen (Runde 20)

- erik_user_growth (Buchungen) + erik_growth_level (Stufenseeds 5 Stufen: Bronze 0/Silber 100/Gold 500/Platin 2000/Diamant 5000)
- Wachstumswert-Gutschriften: Check-in +10 (CheckInController); Bewertung abgeben +20 (ReviewController::store, Nachtrag ohne Gutschrift); Kauf floor(paid) 1 Punkt pro 1 Yuan (WechatPayService::markOrderPaid, nutzt vorhandene Zahlungsstatus-Nachprüfung, natürlich idempotent, wiederholte Rückmeldung ohne doppelte Gutschrift)
- Schnittstellen: GET /api/growth (aktuelle Stufenübersicht: balance/level/Differenz zur nächsten Stufe); GET /api/growth/records (Buchungen mit Paginierung); GET /api/growth/levels (öffentliche Stufenliste, kein Login nötig)
- Fehlerstrategie: Jeder Gutschriftspunkt try/catch mit Log, Hauptablauf unbeeinflusst

### 23. Elektronische Rechnungen (Runde 20)

- erik_invoice: uk_order_type(order_id,order_type) gegen doppelte Anträge pro Bestellung (422 bei Wiederholung, inkl. MySQL-1062-Fang als Absicherung); idx_user_created/idx_status
- Kundenseite: POST /api/invoices (Antrag, Betrag/Titel serverseitig aus der Bestellung, nicht manipulierbar); GET /api/invoices (Liste); GET /api/invoices/{id} (Details)
- Verwaltung: InvoiceController issue (Ausstellung: schreibt invoice_no + status=issued + issued_at) / reject (Ablehnung: status=rejected + reject_reason), Berechtigungen 382 Liste/383 Ausstellung/384 Ablehnung
- Statusmaschine: pending → issued / rejected

### 24. Kundenservice-Tickets (Runde 20)

- erik_ticket: Benutzer reicht Ticket ein (title/content), Backend-Antwort wird angehängt (reply_content/replied_at), Benutzer kann schließen (closed_at)
- Kundenseite: POST /api/tickets (einreichen); GET /api/tickets (Liste); GET /api/tickets/{id} (Details, nur eigene); POST /api/tickets/{id}/close (schließen)
- Verwaltung: TicketController index (Liste) / reply (Antwort), statische Routen vor der resource-Definition gegen {id}-Shadowing; Berechtigungen 385 Ticket-Antwort/387 Ticket-Liste
- Statusmaschine: open → replied (nach Antwort zurück zu open, erneut beantwortbar) / closed

### 25. Mehrstufiger Vertrieb – zweistufige Provision (Runde 20)

- ReferralRewardService::payLevel2Reward(paidAmount, orderId): nach erfolgreicher Bestellzahlung den Empfehler des Erstreferenten ermitteln (Zweitstufen-Empfehlungsbeziehung), paid×level2_rate auszahlen (Systemkonfiguration referral.level2_rate, Standard 0,02)
- Idempotenz: Zeilensperre in Transaktion + Unique-Key uk_order_referred(order_id, level2_user_id), wiederholte Zahlungsrückmeldung/Parallelität ohne doppelte Auszahlung; try/catch-Fehler nur Log, Zahlungshauptablauf unbeeinflusst
- Verbuchung: WalletTxn type='referral_level2' (Konstante TYPE_REFERRAL_LEVEL2) + Wallet-Saldo kumulieren
- Verwaltung: ReferralLevel2Controller index Paginierung (Berechtigung 386), join der Nicknamen beider Stufen

### 26. Wachstumsstufen-Vorteile umgesetzt (Runde 21)

- GrowthLevel.benefits-JSON-Skelett umgesetzt: Migrations-Seeds für 5 Stufen (Bronze {"discount_rate":1.0,"points_multiplier":1.0}, Silber 0.98/1.1, Gold 0.95/1.2, Platin 0.92/1.3, Diamant 0.9/1.5)
- Stufenrabatt: OrderController::store applyGrowthDiscount() – nur Standardbestellungen (promotion_id leer, Gruppeneinkauf/Blitzangebot ohne Kombination); Reihenfolge: fälliger Betrag nach Gutschein/Stempelkarte × discount_rate; Rabattbetrag in discount_amount, Bestellnotiz um „Stufenrabatt: Silber 9,8 %, Ermäßigung ¥2,00" nachvollziehbar erweitert; Mindestpreisschutz: nach Rabatt mindestens 0,01 Yuan (auf Cent-Basis ≥100), sonst Rabatt auf 0 gekappt
- Punktemultiplikator: WechatPayService::markOrderPaid Wachstumswert von floor(paid) auf floor(paid × points_multiplier), Multiplikator nach Stufe zum Zahlungszeitpunkt (vor Gutschrift kumuliert, diese Bestellung hebt keine Stufe); try/catch-Anbindungen aus R20 vollständig erhalten
- Abfrage-Wiederverwendung: GrowthLevel::levelForGrowth() ermittelt Stufe nach kumuliertem Wachstumswert, von Bestellung/Zahlung wiederverwendet; GET /api/growth liefert bereits benefits und next_gap (R20-Implementierung, keine Änderung nötig)

### 27. Rechnungsanschrift-Verwaltung (Runde 21)

- erik_invoice_title (uk_user_title(user_id, title_type, invoice_title) gegen Duplikate + idx_user_default)
- Schnittstellen: POST /api/invoice-titles (speichern, company muss tax_no haben, 422 bei Duplikat); GET (Liste, Standard oben); PUT /{id} (bearbeiten, nur eigene); DELETE /{id} (löschen, nur eigene); POST /{id}/default (als Standard setzen, Transaktion löscht andere Zeilen des Benutzers)
- Standardregeln: erste gespeicherte Anschrift automatisch Standard; nach Löschen der Standardanschrift wird die älteste automatisch Standard
- Antragsverknüpfung: InvoiceController::store optional title_id, löst Anschrift zu invoice_title/tax_no/title_type auf, ohne title_id bleibt der manuelle Pfad erhalten; uk_order_type-Deduplizierung unverändert

### 28. Ticket-Zufriedenheit (Runde 21)

- erik_ticket um rating TINYINT NULL + rated_at DATETIME NULL ergänzt (Migration 000303)
- Bewertung beim Schließen: TicketController::close() unterstützt optionales rating 1–5 (filter_var-Integerprüfung, außerhalb/kein Integer 422; bei Angabe rating+rated_at geschrieben, sonst NULL für alte Clients; Regel „nur open-Tickets schließen" bleibt)
- Backend-Statistik: GET /admin/tickets/satisfaction (statische Route vor resource gegen {id}-Shadowing) liefert total/rated_count/unrated_count/average (1 Nachkommastelle)/distribution (Anzahl pro 1–5 Sterne, fehlende Sterne mit 0 aufgefüllt); Berechtigung 388

### 29. Bewertungsbild-Prüfung (Runde 21)

- admin ReviewAuditController (neu, vorhandener ReviewController unverändert): GET /admin/review-audit Liste von Bewertungen mit Bildern (JSON_LENGTH(images)>0-Filter + leftJoin Benutzernickname und Technikername + status-Filter + hashid-Codierung); POST /{id}/hide ausblenden; POST /{id}/restore wiederherstellen
- Statusmaschine: hide nur bei visible möglich, restore nur bei hidden (beidseitig 422); OrderReview-Status ganzzahlig (STATUS_HIDDEN=0/STATUS_VISIBLE=1)
- Wirkungskette: Kunden-Techniker-Bewertungsliste filtert bereits nach status → nach Ausblenden automatisch unsichtbar
- Berechtigungen: 389 Liste / 390 Ausblenden / 391 Wiederherstellen

### 30. Benutzer-Browserverlauf (Runde 21)

- erik_browse_history (uk_user_item(user_id, item_id) Unique, wiederholtes Ansehen aktualisiert nur viewed_at ohne Doppelinsert; idx_user_viewed Sortierung)
- Protokollierung: ServiceController::detail() nach Erfolg (try/catch + Log::warning ohne Hauptablauf-Beeinflussung; öffentliche Route ohne JWT, user_id-Nullprüfung überspringt anonyme)
- Schnittstellen: GET /api/browse-history (join erik_service Name/Cover/Preis/Originalpreis, viewed_at absteigend, per_page Standard 15 Maximum 50, item_id hashid); DELETE /{item_id} (nur eigene, ungültig/fremd 404); DELETE / (leeren, nur eigene)

### 31. Rabatt ab Mindestbetrag (Runde 22)

- erik_full_reduction_activity (threshold/reduction/title/status/start_at/end_at + idx_status_status_time)
- Bestell-Kombination: nur Standardbestellungen (Gruppeneinkauf/Blitzangebot übersprungen), Schwelle nach Gutschein/Stempelkarten-Abzug, Reihenfolge **Gutschein/Stempelkarte → Rabatt ab Mindestbetrag → Stufenrabatt**; Aktivität mit größtem Abzug; Rabattbetrag in discount_amount + Notiz „Rabatt ab Mindestbetrag: ab X reduziert um Y"; Mindestzahlung nach Rabatt 0,01 Yuan (auf Cent-Basis)
- Kundenseite GET /api/full-reduction-activities (öffentlich, aktive absteigend nach Abzugsbetrag)
- admin FullReductionController: CRUD + toggle-status Veröffentlichen/Zurückziehen (destroy mit confirmPassword)
- Berechtigungen: 396 Liste / 397 Neu / 398 Bearbeiten / 399 Veröffentlichen/Zurückziehen / 400 Löschen (ein Berechtigungsdatensatz entspricht genau einem method.path-slug, 5 Routen in 5 Einträgen)

### 32. Meine Buchungen als ICS-Export (Runde 22)

- IcsController GET /api/order/ics: Bestellungen der letzten 90 Tage mit Status pending/paid/confirmed/serving als iCal exportiert (RFC5545), nur eigene
- VEVENT: UID=Bestell-ID, DTSTAMP(UTC), TZID=Asia/Shanghai, Standarddauer 1 h, Zusammenfassung „Buchung: Servicename" (fehlt → „Buchung"), Beschreibung Techniker/Filiale/Adresse (fehlt → weggelassen), LOCATION; Text-Escaping (\, \; \\ \n) + 75-Byte-Zeilenumbruch
- Ohne Bestellungen gültiger leerer Kalender (`BEGIN:VCALENDAR`-Grundgerüst)

### 33. Techniker-Anwesenheit (Runde 22)

- erik_technician_attendance (date/check_in_at/check_out_at/status + uk_technician_date Unique-Index gegen paralleles Doppelstempeln)
- Technikerseite (TechnicianAuth): check-in 422 bei Wiederholung am selben Tag; check-out 422 ohne Arbeit/bereits ausgestempelt + Zeilensperre; >10:00 Verspätung markiert; GET Monatsliste + Anwesenheitstage/Gesamtstunden/Durchschnittsstunden (?month=YYYY-MM ungültig 422)
- admin: GET /admin/attendance (date+Technikername-Filter, join real_name, hashid) + /stats (gruppierte Statistik pro Techniker)
- Berechtigungen: 392 Liste / 393 Statistik

### 34. APP-Push-Dienst (Runde 22)

- AppPushService (config group=push: enabled Standard 0 / provider jpush/getui/placeholder): nicht aktiviert stiller Fallback nur Log; aktiviert strukturierter Aufbau Plattform/Titel/Inhalt/payload + Log + erik_push_log schreiben (status=sent); Hersteller-SDK-Anbindung als TODO (ohne Anmeldedaten kein tatsächliches Senden)
- 5 Event-Anbindungen: Zahlungserfolg (WechatPayService::markOrderPaid), automatische Rückerstattung (autoRefundCancelledOrder), manuelle Rückerstattung (doRefund/refundToBalance), Rückerstattungskompensation (completeOneRefundCompensation), Servicebeginn-Erinnerung (ServiceReminderTimer); alle try/catch ohne Hauptablauf-Blockade
- erik_push_log (user_id/title/content/payload JSON/status/provider + idx_user)

### 35. Offizielles WeChat-Profit-Sharing (Runde 22)

- WechatProfitSharingService (config group=profit_sharing: enabled/receiver_ratio, Anmeldedaten teilen wechat_pay): nicht aktiviert disabled-Degradation nur Log ohne DB-Schreiben; aktiviert → Betragsprüfung (>0 und ≤paid, real paid×0,7 Standard) + Idempotenz (gleiche Bestellung pending/success übersprungen) → pending-Datensatz schreiben → Struktur „einmalige Profit-Sharing-Anfrage" aufbauen (ohne Anmeldedaten kein HTTP, Anfrageinhalt im Log, Datensatz bleibt pending); HTTP-isolierter privater doRequest testbar
- WechatPayService::markOrderPaid bindet nach dem Senden requestSharing an (try/catch-Fehler nur Log)
- erik_profit_sharing (uk_sharing_no Unique + idx_order); admin GET /admin/profit-sharing Liste (join Bestellnummer/Technikernickname, Filter Status/Bestellnummer/Technikername)
- Berechtigung: 394

### 36. Datenschutz-Compliance (Runde 22)

- GET /api/privacy/data: Datenexport (Gruppen personal/orders/points/wallet_txns/reviews/addresses/invoices; Log nur maskierte Telefonnummer + Anzahl)
- Lösch-Kreislauf: close-request (Guthaben ≠ 0 / unfertige Bestellungen / laufende Tickets 422 → close_status=1) → close-cancel (1→0) → close-confirm (nach 72 h → close_status=2 + close_at + phone/nickname anonymisiert zu user{id} + status=0)
- erik_user um close_status/close_requested_at/close_at ergänzt (idempotente ALTER-Migration); AuthController login/loginByCode gibt bei close_status=2 403 „Konto wurde gelöscht"

### 37. Benutzer-Gesundheitsprofil (Runde 23)

- GET/PUT/DELETE /api/health-profile: eine Akte pro Person (uk_user Unique-Index), upsert aktualisiert nur angegebene Felder
- allergies/health_notes maximal 500 Zeichen, preferred_technician_id prüft Existenz, Antwort hashid-codiert
- Migration 000504_user_health_profile; HealthProfileTest 6 Tests

### 38. Wallet-Zahlungspasswort (Runde 23)

- POST /api/wallet/pay-password/{set,verify,check}: 6-stellige Zahlenprüfung, password_hash-Speicherung + pay_password_set_at
- Bei bereits gesetztem Passwort erfordert die Änderung das alte Passwort 422; verify prüft nur ohne DB-Schreiben; check liefert, ob gesetzt
- Migration 000502 (INFORMATION_SCHEMA idempotente ALTER-Migration zweier Spalten); WalletPayPasswordTest 7 Tests

### 39. Techniker-Massen-Schichtplanung (Runde 23)

- POST /api/technician/schedule/batch: Datumsbereich ≤7 Tage + weekdays-Filter, Tage mit vorhandenem Schichtplan übersprungen
- Einzeleinstellungen aktivieren ebenfalls Zeitraum-Überlappungserkennung (422 „Zeitkonflikt mit vorhandenem Schichtplan: HH:MM-HH:MM")
- ScheduleConflictTest 5 Tests

### 40. Bestellstatus-Zeitachse (Runde 23)

- GET /api/order/{id}/timeline: nur eigene abrufbar (fremde 404), absteigend; admin Bestelldetails binden timeline-Array ein
- OrderStatusLog::record() statische Markierung für 8 Änderungstypen: Einreichung/Zahlung/Stornierung/Bestätigung/Rückerstattungsantrag/Rückerstattung genehmigt/Dienstbeginn/Dienstabschluss/Timeout-Autostornierung/Backend-Operation (operator=admin)
- Zahlungsrückmeldung markOrderPaid als einzige Konsumstelle; record() intern try/catch + Log::warning, blockiert Hauptablauf nie
- Migration 000501_order_status_log; OrderTimelineTest 4 Tests

### 41. Punkte-Glücksrad (Runde 23)

- GET /api/wheel/prizes (weight/stock verborgen); POST /api/wheel/spin: Redis NX + Zeilensperre gegen Parallelität, random_int gewichtete Ziehung, client_token Idempotenz
- Preise verbucht: Punkte → earn-Buchung (inkl. Ablaufzeit, von PointsExpiryTimer normal ablaufend), Guthaben → lockForUpdate, Gutschein → pending manuelle Ausgabe, kein Preis → lose
- GET /api/wheel/records meine Protokolle mit Paginierung; admin /admin/lucky-wheel CRUD + Veröffentlichen/Zurückziehen + Protokolle (Berechtigungen 401–406)
- Migrationen 000503 (erik_lucky_wheel + erik_wheel_record + w60/w40-Demoseeds) + 000505 (Berechtigungsseeds); LuckyWheelTest admin 3 + service 6 Tests

### 42. Gastmodus (Runde 24)

- GET /api/guest/{home,services,services/{id},stores,technicians}: ohne Authentifizierung (nur ApiVersion-Middleware) als Anmeldeeinstieg zum Browsen
- home aggregiert Karussell/Ankündigungen/Servicekategorien/populäre Dienste, Redis-Cache svc:guest:home 300 s; services mit Kategoriefilter + newest/sales/price-Sortierung (page/per_page≤50); technicians nur freigegebene, service_id-Filter möglich, Bewertung absteigend
- GuestControllerTest abgedeckt

### 43. Blitzangebot (Runde 24)

- erik_seckill_activity (name/service_id/seckill_price/original_price/stock/start_at/end_at/status); verkaufte Menge = Anzahl der erik_order mit seckill_id
- GET /api/seckill (status=1 + Zeitfenster), /{id} (state=not_started/ongoing/ended), POST /{id}/buy: client_token (8–64 Zeichen, SETNX 24 h) Idempotenz + Redis NX 30 s gegen Parallelität + Aktivitätsprüfung (ab 2026-08-26 keine Vorreservierung des Lagerbestands mehr)
- Bestellung injiziert seckill_id und nutzt OrderController::store; Lagerbestand einheitlich in der store()-Transaktion per Zeilensperre abgezogen (direkter /api/order-Aufruf mit seckill_id zieht ebenfalls ab), Blitzpreis = seckill_price (DB maßgeblich), keine Kombination mit Gutscheinen/Punkten/Mitgliederkarten; Stornierung gibt Lagerbestand nicht zurück; alter Promotionskanal FLASH_SALE entfernt (store()-Promotionszweig enthält nur noch Gruppeneinkauf, PromotionController index filtert flash_sale, show/join 400), Blitzangebote laufen nur über diesen Kanal
- admin /admin/seckill CRUD + Veröffentlichen/Zurückziehen + Bestellliste (Berechtigungen 407–411, 420); Migration 000606 Berechtigungsseeds; SeckillTest service + admin

### 44. APP-Versionsverwaltung und Update-Prüfung (Runde 24)

- erik_app_version (platform/version_code/version_name/force_update/changelog/download_url/status)
- GET /api/app/version?platform=android|ios öffentliche Update-Prüfung (platform ungültig 422; aus status=1 die neueste; ohne Treffer leeres Objekt)
- admin /admin/versions CRUD (Berechtigungen 416–419); Migration 000609 Berechtigungsseeds; VersionTest service + admin

### 45. Stammkunden-Belohnung (Runde 24)

- ReturnCustomerRewardService: bei zweitem Kauf des Benutzers beim selben Techniker innerhalb von 30 Tagen (Bestellabschluss) erhält der Techniker einen Bonus = real bezahlt paid_amount × ratio (system_config group=return_customer, ratio Standard 0,05, enabled-Schalter, ungültige Werte fallen auf Standard zurück)
- Schreibt erik_technician_earnings (type=return_customer, status=pending), nutzt die Provisionsabrechnungskette, Technikerseiten-Einnahmenaggregation enthält automatisch; idempotent per order_id+type; in der WorkController::complete-Zeilensperrentransaktion aufgerufen
- admin /admin/return-customer/config (GET/PUT) + /rewards (?keyword Technikername/Bestellnummer/Benutzernickname) (Berechtigungen 412–414); Migration 000607 Berechtigungsseeds; ReturnCustomerRewardServiceTest

### 46. Schichtplan-Export (Runde 24)

- GET /admin/technician-schedule/export: CSV (UTF-8 BOM, direkt in Excel öffnbar), Dateiname schedules_{YmdHis}.csv
- start_date/end_date Pflicht (YYYY-MM-DD, ungültig 422) und Spanne ≤31 Tage; technician_id optional (hashid, ungültig 422)
- Spalten: Techniker-ID/Technikername/Datum/Zeitraumdetails (time_slots JSON zu „09:00-12:00, 14:00-18:00" aufgelöst)
- Berechtigung: 415; Migration 000608 Berechtigungsseeds; ScheduleExportTest abgedeckt
