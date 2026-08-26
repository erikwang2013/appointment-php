# Bedienungsanleitung

> Deutsche Übersetzung · Original: [中文](../USAGE.md)

## Verwaltungsbackend-Login

Standard-Admin: `admin` / `admin123` | Adresse: `http://localhost:8787`

> Nach dem ersten Login das Passwort sofort ändern

---

## Systemkonfigurationsablauf

### 1. Grundeinstellungen
Systemkonfiguration → Plattformname/LOGO ausfüllen → Über uns → Kundendiensttelefon/Website/E-Mail → Plattformvereinbarungen → Nutzungs-/Datenschutzvereinbarung bearbeiten

### 2. Filialen und Dienste
Filialverwaltung → Neue Filiale (Name/Adresse/Koordinaten/Telefon/Zeiten) → Servicekategorien → Kategorie erstellen → Serviceleistungen → Neuen Service anlegen (Name/Preis/Dauer/Spezifikationen) → Produktverwaltung → Neues Produkt/Gutschein anlegen

### 3. Techniker-Onboarding
Antrag in der Techniker-APP → Prüfung im Verwaltungsbackend „Technikerverwaltung" → nach Genehmigung richtet der Techniker seinen Schichtplan ein → kann Buchungen annehmen

### 4. Betriebskonfiguration
Karussell → Upload + Sprungziele setzen | Ankündigungen → laufende Ankündigung veröffentlichen | Gutscheine → Neukunden-Gutschein/Rabatt-ab-Mindestbetrag-Gutschein erstellen | Mitgliederkarten → Monatskarte/VIP/Stempelkarte | Provisionen → Provisionssatz für Techniker festlegen

---

## Ablauf auf der Kundenseite

### Registrierung und Login
WeChat-Suche/QR-Scan → Registrierung mit Telefonnummer + Verifizierungscode (Empfehlungscode optional) → oder WeChat-Ein-Klick-Login → Neukunden erhalten automatisch einen Gutschein

### Service buchen
Kategorien auf der Startseite durchstöbern → Service für Details öffnen → Preise/Bewertungen ansehen → sofort buchen → Filiale/Techniker/Zeit/Gutschein wählen → Bestellung bestätigen → WeChat-Zahlung → Zahlung erfolgreich

### Bestellverwaltung
Ausstehend: Zahlung abschließen | Bezahlt: auf den Service warten | Abgeschlossen: bewerten (Sterne + Text + Bilder) | Rückerstattung: Rückerstattungsanteil wird automatisch berechnet

### Persönliches Zentrum
Bestellungen/Gutscheine/Mitgliederkarten/Punkte/Favoriten | Werbezentrum: Werbe-QR-Code holen, um Punkte zu erhalten | Feedback: Text + Bilder

---

## Bedienung auf der Technikerseite

### Identität wechseln
In der APP „Mein" → auf Techniker wechseln → Arbeitsplatz

### Tägliche Arbeit
- **Schichtplan-Einstellungen**: buchbare Zeiträume pro Tag festlegen
- **Buchungen ansehen**: Liste der heute gebuchten Bestellungen
- **QR-Verifizierung**: Verifizierung per Scan des Benutzer-QR-Codes
- **Mitgliederprofile**: Kundenprofil innerhalb von 24 h pro Bestellung ausfüllen (sonst keine Provision)
- **Anwesenheits-Check-in**: Check-in/Check-out/Fotoupload der Hygiene

### Einnahmen
Heutige Einnahmen/Fonds unterwegs/Guthaben ansehen → Auszahlung am 20. jedes Monats → T+1 auf WeChat-Guthaben

### Wachstum
Schulungskurse lernen → Prüfung ablegen → bestehen hebt die Technikerstufe (beeinflusst den Provisionssatz)

---

## API-Schnittstellen

Die Schnittstellendokumentation wird separat gepflegt, siehe [API.md](API.md) (Business-API + Verwaltungsbackend-API, mit Anfrage-/Antwortbeispielen und OpenAPI-Endpunkten).

---

## WebSocket

```
ws://localhost:8282
```

Authentifizierung: `{"type":"auth","token":"<JWT>"}`

Events: `order_update` / `technician_online` / `system_notice`

---

## Push-Konfiguration

iOS (APNs): apns_key_id/team_id/bundle_id/.p8-Datei konfigurieren  
Android (FCM): fcm_server_key konfigurieren

APP-Geräteregistrierung: `POST /api/user/device/register {"platform":"ios","device_token":"..."}`

---

## Geplante Aufgaben

| Aufgabe | Häufigkeit | Beschreibung |
|------|------|------|
| Automatische Bestellstornierung | 30 Sekunden | Ausstehende Zahlung länger als 30 Minuten |
| Automatische Einnahmenabrechnung | 3 Tage | Provision für abgeschlossene Bestellungen abrechnen |
| Gutscheinablauf | Täglich | als expired markieren |
| Mitgliederkartenablauf | Täglich | als expired markieren |

---

## Rückerstattungsregeln

| Bedingung | Anteil |
|------|------|
| Innerhalb von 15 Minuten nach Bestellung oder >6 h vor Beginn | 100 % |
| ≤6 h vor Beginn | 90 % |
| Begonnen, nicht bestätigt | 80 % |
| Nach bestätigtem Beginn | 0 % |

---

## Monitoring

```bash
GET /health          # Health-Check
GET /metrics         # Prometheus-Metriken
GET /.well-known/security.txt  # Sicherheitskontakt
```

## Tests

```bash
admin/ && phpunit --bootstrap tests/bootstrap.php     # 60 tests
service/ && phpunit --configuration phpunit.xml        # 21 tests
```
