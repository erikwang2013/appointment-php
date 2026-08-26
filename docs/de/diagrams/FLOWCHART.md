# Kern-Geschäftsprozess-Diagramm

> Deutsche Übersetzung · Original: [中文](../../diagrams/FLOWCHART.md)

## 1. Leistungsbuchungsablauf

```mermaid
flowchart TD
    A["Benutzer durchstöbert Leistungen"] --> B["Filiale/Techniker/Zeit wählen"]
    B --> C["Bemerkung ausfüllen"]
    C --> D{"Gutschein wählen?"}
    D -->|"verwenden"| E["Gutschein-Anrechnung"]
    D -->|"nicht verwenden"| F["Bestellung zum Originalpreis"]
    E --> G["Bestellung kalkulieren (nicht verbrauchen)<br/>PriceCalculator reine Berechnung<br/>Gutschein fixed/percent + Stempelkarte times<br/>min_amount basierend auf Originalpreis"]
    F --> G
    G --> H["Leistungsvereinbarung lesen"]
    H --> I["Bestellung absenden"]
    I --> J{"Techniker per Redis sperren<br/>SETNX 3 Minuten"}
    J -->|"Sperre erfolgreich"| K["Bestellung erstellen pending"]
    J -->|"bereits gesperrt"| L["Hinweis Techniker ausgelastet"]
    K --> M{"Fälliger Betrag?"}
    M -->|"null Yuan"| N["FREE-Direktdurchlauf<br/>transaction_id = 'FREE'+Zahlungsauftragsnummer<br/>Bestellung → paid"]
    M -->|"Guthabenzahlung"| B1["Wallet-Guthaben abziehen<br/>wallet_txn verbuchen<br/>Bestellung → paid"]
    M -->|"Betrag > 0"| O{"Zahlungsart"}
    O -->|"WeChat"| OW["WeChat-Zahlung aufrufen<br/>pay_lock gegen parallele Doppelzahlung"]
    O -->|"Guthaben"| B1
    OW --> P{"Zahlungsergebnis"}
    B1 --> S
    P -->|"erfolgreich"| Q["Zahlungs-Callback verbrauchen<br/>markOrderPaid einziger Verbrauchspunkt<br/>Gutschein/Stempelkarte atomar abbuchen<br/>Bestellung → paid"]
    P -->|"fehlgeschlagen/abgebrochen"| R["Bestellung bleibt pending<br/>nach 15 Minuten automatisch storniert"]
    N --> S["Techniker bestätigt Servicebeginn"]
    Q --> S
    S --> T["Bestellung → serving"]
    T --> U["Leistung abgeschlossen"]
    U --> V["Techniker verifiziert per QR-Scan"]
    V --> W["Bestellung → completed"]
    W --> X["Benutzer bewertet (Text + Bilder)"]
    X --> Y["Bestellung → reviewed ✅"]

    style A fill:#e3f2fd,stroke:#1565c0,color:#333
    style Y fill:#c8e6c9,stroke:#2e7d32,color:#333
    style L fill:#ffcdd2,stroke:#c62828,color:#333
    style R fill:#fff9c4,stroke:#f9a825,color:#333
    style N fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 2. Zahlungs- und Rückerstattungsablauf

```mermaid
flowchart TD
    subgraph 支付流程["Positiver Zahlungsablauf"]
        P1["Zahlungsprotokoll erstellen"] --> P2["WeChat-Einheitliche-Bestellung<br/>pay_lock gegen Konkurrenz<br/>out_trade_no = order_no idempotent"]
        P2 --> P3["Frontend startet Zahlung<br/>Zahlungsart wählen"]
        P3 -->|"Guthaben"| PB["Wallet-Guthaben abziehen<br/>wallet_txn verbuchen<br/>idempotent nur einmal abbuchen"]
        P3 -->|"WeChat"| P4["WeChat-Callback notify"]
        P4 --> P5["Signaturprüfung bestanden"]
        PB --> P6["markOrderPaid idempotent<br/>Gutschein/Stempelkarte nur einmal verbraucht"]
        P5 --> P6
        P6 --> P7["Bestellung → paid<br/>Benutzer + Techniker benachrichtigen"]
    end

    subgraph 退款流程["Rückerstattungsablauf"]
        R1["Benutzer beantragt Rückerstattung<br/>refund_lock gegen Konkurrenz"] --> R2{"Rückerstattungsregel prüfen"}
        R2 -->|"Bestellung ≤15min oder >6h vor Beginn"| R3["Rückerstattung 100 %"]
        R2 -->|"≤6h vor Beginn"| R4["Rückerstattung 90 %"]
        R2 -->|"begonnen, nicht bestätigt"| R5["Rückerstattung 80 %"]
        R2 -->|"nach Servicebestätigung"| R6["Keine Rückerstattung"]
        R3 --> R7["Bestellung → refunding"]
        R4 --> R7
        R5 --> R7
        R7 --> R8["Zweistufige Freigabe<br/>Filialleiter → Finanzabteilung"]
        R8 --> R9["Zweistufige Rückerstattung<br/>innerhalb der Transaktion Rückerstattungsprotokoll anlegen<br/>außerhalb der Transaktion WeChat-Rückerstattung IO"]
        R9 -->|"WeChat fehlgeschlagen"| R10["Bestellung auf PAID zurücksetzen<br/>Rückerstattung wiederholbar"]
        R9 -->|"Rückerstattung erfolgreich"| R11["Bestellung → refunded<br/>WeChat-Originalweg-Rückzahlung / Guthaben-Wiederauffüllung<br/>Gutschein zurück + Punkte abgezogen"]
    end

    style P6 fill:#c8e6c9,stroke:#2e7d32,color:#333
    style R6 fill:#ffcdd2,stroke:#c62828,color:#333
    style R11 fill:#c8e6c9,stroke:#2e7d32,color:#333
    style R10 fill:#fff9c4,stroke:#f9a825,color:#333
```

## 3. Techniker-Auszahlungsablauf

```mermaid
flowchart TD
    A["Techniker beantragt Auszahlung"] --> B{"poster-php<br/>Operations-Verifizierung"}
    B -->|"Verifizierung bestanden"| C{"Auszahlungsbedingungen prüfen"}
    B -->|"Verifizierung fehlgeschlagen"| X["Operation ablehnen"]
    C -->|"am 20. jedes Monats"| D["Auszahlungsprotokoll erstellen"]
    C -->|"kein Auszahlungstag"| Y["Hinweis: Auszahlung am 20. jedes Monats möglich"]
    D --> E["Backend-Prüfung"]
    E --> F{"Prüfungsergebnis"}
    F -->|"genehmigt"| G["Auszahlung ausführen"]
    F -->|"abgelehnt"| H["Antrag zurückweisen<br/>mit Ablehnungsgrund"]
    G --> I["WeChat-Unternehmenszahlung ins Guthaben"]
    I --> J["T+1 auf dem Konto"]
    J --> K["Finanztransaktion erzeugen<br/>Einnahmen/Ausgaben protokollieren"]

    style K fill:#c8e6c9,stroke:#2e7d32,color:#333
    style X fill:#ffcdd2,stroke:#c62828,color:#333
    style Y fill:#fff9c4,stroke:#f9a825,color:#333
    style H fill:#ffcdd2,stroke:#c62828,color:#333
```

## 4. Identitätswechsel-Ablauf

```mermaid
flowchart TD
    A["Aktuelle Identität: Kunde"] --> B["Auf Techniker wechseln klicken"]
    B --> C{"Status des Technikerprofils"}
    C -->|"approved"| D["active_role = technician<br/>Seite wechselt zur Techniker-Workbench"]
    C -->|"nicht aufgenommen/in Prüfung"| E["Zur Aufnahmeantrag-Seite führen"]
    E --> F["Techniker-Informationen ausfüllen<br/>Name/Geschlecht/Telefonnummer<br/>Personalausweis/Fotos"]
    F --> G["Prüfung einreichen"]
    G --> H{"Backend-Prüfung"}
    H -->|"genehmigt"| D
    H -->|"abgelehnt"| I["Ändern und erneut einreichen"]

    J["Aktuelle Identität: Techniker"] --> K["Auf Kunde wechseln klicken"]
    K --> L["active_role = customer<br/>Seite wechselt zur Kundenoberfläche"]

    style D fill:#c8e6c9,stroke:#2e7d32,color:#333
    style L fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 5. Wallet-Auflade-/Geschenkkarten-Verbuchungsablauf

```mermaid
flowchart TD
    A["Benutzer lädt auf / löst Geschenkkarte ein"] --> B{"Verbuchungsart"}
    B -->|"WeChat-Aufladung"| C["WeChat-Zahlungs-Callback<br/>wallet_recharge-Protokoll<br/>idempotente Verbuchung"]
    B -->|"Geschenkkarten-Einlösung"| D["GiftCard redeem verifiziert Kartencode<br/>Betrag wird dem Wallet-Guthaben gutgeschrieben"]
    C --> E["Wallet-Guthaben erhöht<br/>wallet_txn verbucht"]
    D --> E
    E --> F["Bestellungen mit Guthaben bezahlen<br/>oder Rückerstattung füllt Guthaben wieder auf"]
    F --> G["Verbuchung/Wiederauffüllung abgeschlossen ✅"]

    style G fill:#c8e6c9,stroke:#2e7d32,color:#333
```
