# Funktionsdesign

> Deutsche Übersetzung · Original: [中文](../FEATURE-DESIGN.md)

## Kaufablauf

### Service-Buchungsablauf (direkte Bestellung)

```
Dienst-Details → Bestellung bestätigen (Filiale/Techniker/Zeit/Gutschein/Anmerkungen) → Servicevereinbarung lesen
    → Bestellung aufgeben → Redis sperrt Techniker 3 Minuten → WeChat-Zahlung → Zahlung erfolgreich
    → Benutzer+Techniker benachrichtigen → Dienstzeit erreicht → Techniker bestätigt Start
    → Dienst abgeschlossen → QR-Code-Verifizierung → Benutzerbewertung → Bestellung abgeschlossen
```

### Produktkaufablauf (Warenkorb-Modus)

```
Produktliste → in den Warenkorb → Warenkorb bestätigen (Menge ändern/löschen)
    → Bestellung aufgeben → Zahlung → Versand → Empfang → Abschluss
```

## Bestellstatusmaschine

```
pending(ausstehend) → paid(bezahlt) → confirmed(bestätigt)
    → serving(in Bearbeitung) → completed(abgeschlossen) → reviewed(bewertet)

pending → cancelled(storniert)
paid → cancelled
paid → refunding(Rückerstattung läuft) → refunded(rückerstattet)
```

## Techniker-Sperrmechanismus

Der Benutzer öffnet die Bestellbestätigungsseite → Redis SETNX sperrt für 3 Minuten. Beim Verlassen/Zeitüberschreitung wird freigegeben.

```
SETNX lock:tech:123:2026-05-26-14:00 user_456 EX 180
 → Erfolg: mit Bestellung fortfahren
 → Fehler: Techniker bereits gesperrt
```

## Rückerstattungsregeln

| Bedingung | Rückerstattungsanteil |
|------|----------|
| Innerhalb von 15 Minuten nach Bestellung oder >6 Stunden vor Beginn | 100 % |
| ≤6 Stunden vor Beginn | 90 % |
| Begonnen, aber Dienst nicht bestätigt | 80 % |
| Nach bestätigtem Dienstbeginn | 0 % (keine Rückerstattung) |

## Rabattregeln

| Typ | Bedingung | Rabatt | Kombinierbar |
|------|------|------|------|
| Schwachlast-Rabatt | 10–12 Uhr/17–18 Uhr/nach 21:00 | 10 % Rabatt | mit Gutscheinen kombinierbar |
| Vorab-Buchung | mehr als 30 Minuten im Voraus | 5 % Rabatt | nicht mit Gutscheinen kombinierbar |

## Techniker-Auszahlung

- Am 20. jedes Monats auszahlbar, T+1 auf WeChat-Guthaben
- Verifiziert, aber nicht abgerechnet: automatische Bestätigung nach 3 Tagen
- Mindestbetrag/Rücklagebetrag/ganzzahlige Hunderter im Backend konfigurierbar

### Auszahlungsablauf

```
Auszahlung beantragen → poster-php-Validierung → Backend-Prüfung (genehmigen/ablehnen)
    → Auszahlung abschließen → WeChat-Guthaben eingegangen → Finanzbuchung erzeugen
```

### Einkommensarten

| Typ | Beschreibung |
|------|------|
| commission | Service-Provision |
| bonus | Bonus (Stammkunden/Anwesenheit) |
| penalty | Geldstrafe (Profil nicht innerhalb von 24 h geschrieben) |
| subsidy | Zuschuss |
| attendance | Anwesenheitsprämie |

### Stammkunden-Belohnung

Zweiter Kauf beim selben Techniker innerhalb von 30 Tagen → Bonus wird erfasst

### Mitgliederprofil

Nach jeder abgeschlossenen Bestellung muss das Profil innerhalb von 24 h geschrieben werden, sonst keine Provision

## Punkte-Design

- Punkte durch Kauf und Empfehlung (im Backend konfigurierbar)
- 1:100 Einlösung für Geschenkkarten (im Backend konfigurierbar)
- Punkte-Transaktionstabelle protokolliert jede Änderung + Saldo

## Mitgliederkarten-Design

| Typ | Abrechnung | Beschreibung |
|------|------|------|
| month | pro Tag | normale Monatskarte |
| vip | pro Tag | VIP-Jahreskarte |
| times | pro Einheit | Stempelkarte, frei kombinierbare Serviceleistungen |

Stempelkarte: bei Kauf Leistungskombination wählen (A×3+B×5), jede Einheit verbraucht 1 Stempel des jeweiligen Leistungsmoduls. Aufgebraucht → used_up, abgelaufen → expired.

## Identitätswechsel

```
Kunde → auf Techniker wechseln → prüfen, ob Technikerprofil approved ist
    → ja: active_role=technician, Seitenwechsel
    → nein: Einreichung der Beitrittsbewerbung anleiten

Techniker → auf Kunde wechseln → active_role=customer, Seitenwechsel
```

## Belohnung für neue Benutzer

```
Registrierung → Empfehlungscode generieren → mit Empfehler: Werbeprotokoll erstellen
    → automatisch Neukunden-Gutschein senden (Phase 5)
    → Empfehler erhält Punkte (nach der ersten Bestellung des Geworbenen)
```

## Zahlungsdesign (WeChat-Zahlung vorgesehen)

```
POST /api/order/pay/{id}
    → Zahlungsprotokoll erstellen → WeChat Unified Order aufrufen (WechatPayService vorgesehen)
    → Zahlungsparameter zurückgeben → Frontend ruft Zahlung auf
    → WeChat-Rückmeldung /api/wechat/notify → Signaturprüfung → Status auf paid setzen
    → Benutzer+Techniker benachrichtigen
```
