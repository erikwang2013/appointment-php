# System-Funktionsdiagramm
> **Languages**: [中文](../../diagrams/FUNCTION-DIAGRAM.md) · [English](../../en/diagrams/FUNCTION-DIAGRAM.md) · [한국어](../../ko/diagrams/FUNCTION-DIAGRAM.md) · [Русский](../../ru/diagrams/FUNCTION-DIAGRAM.md) · [Français](../../fr/diagrams/FUNCTION-DIAGRAM.md) · [Español](../../es/diagrams/FUNCTION-DIAGRAM.md) · [Português](../../pt/diagrams/FUNCTION-DIAGRAM.md) · [हिन्दी](../../hi/diagrams/FUNCTION-DIAGRAM.md) · [العربية](../../ar/diagrams/FUNCTION-DIAGRAM.md) · [বাংলা](../../bn/diagrams/FUNCTION-DIAGRAM.md) · [Bahasa Indonesia](../../id/diagrams/FUNCTION-DIAGRAM.md) · [日本語](../../ja/diagrams/FUNCTION-DIAGRAM.md)

> Deutsche Übersetzung · Original: [中文](../../diagrams/FUNCTION-DIAGRAM.md)

```mermaid
mindmap
  root((Buchungsservicesystem))
    Benutzerseite
      Authentifizierung
        Telefonnummer-Registrierung/-Login
        Verifizierungscode-Login
        WeChat-Autorisierungs-Login
        Gastmodus
        Passwort vergessen
        Nutzungs-/Datenschutzvereinbarung
      Startseite
        LBS-Standort und Stadtwechsel
        Karussell/Ankündigungen
        Servicekategorie-Einstiege
        Neukunden-Gutschein
      Leistungsbuchung
        Filialauswahl inkl. Navigation
        Technikerauswahl inkl. Bewertung
        Auswahl der Servicezeit
        Nebenzeiten 9 % Rabatt / Vorabbuchung 95 %
        Gutscheinverwendung
        Bemerkung und Leistungsvereinbarung
      Produkt-Shop
        Produktsuche und -filter
        Produktdetails und Favoriten
        Warenkorbverwaltung
        Sofort kaufen
      Bestellverwaltung
        Alle Bestellungen Tab-Ansicht
        Ausstehende Zahlung/ausstehender Versand/ausstehender Empfang
        Stornieren/Versand anmahnen/Empfang bestätigen
        Rückerstattungsantrag
        After-Sales-Antrag Rückerstattung/Umtausch Statusverfolgung
        Punkte-Anrechnung Anrechnung bei Zahlung
        Gruppenkauf-Bestellung nach Teilnahme mit Gruppenpreis bestellen
        Blitzangebots-Bestellung mit Blitzpreis bestellen Ausverkauft-Sperre
        Terminverschiebung Zeitwechsel beim selben Techniker ≥6h vor Beginn
        Termin-Kalender Monats-/Tagesansicht des Schichtplans Gebuchte ausgeschlossen
        Erinnerung vor Servicebeginn 1h vorher Abonnementnachricht + In-App
        Text- + Bildbewertung
        Bewertungsergänzung Inhalte/Bilder ergänzen einmalig
        Logistikverfolgung Versandstatus/maskierter Empfänger
        Elektronische Rechnung Antrag/Liste/Details Duplikatschutz
        ICS-Kalenderexport Buchungen der letzten 90 Tage als iCal
        Bestell-Zeitlinie Statusänderungsprotokoll/nur eigene
        Rechnungsanschriften Anschriftenbibliothek/Standard
        Nachrichtenpräferenzen Benachrichtigungsschalter/Timer-Gating
      Technikermodul
        Technikerliste Entfernungssortierung
        Technikerdetails und Favoriten
        Aufnahmeantrag
        Batch-Schichtplan Datumsbereich ≤7 Tage/Überschneidungskonflikterkennung
      Marketingzentrum
        Gutscheine Einlösen/Anrechnung bei Bestellung
        Gutschein-Übertragung 8-stelliger Übertragungscode/gegen Doppelbeanspruchung/7 Tage gültig
        Mitgliederkarte Monatskarte/VIP/Stempelkarte
        Stempelkarten-Verifizierung my/use
        Punkte erhalten und einlösen/Konsum-Rückvergütung
        Punkte-Ablauf 365 Tage Gültigkeit/timerbasierte Abbuchung
        Punkte-Einlösungs-Shop Gutschein/Guthaben/Geschenkkarte einlösen
        Gruppenkauf/Blitzangebot Teilnahme/Voll-Gruppen-Sperre/Gruppenbestellung
        Karten-Ablauf-Erinnerung Benachrichtigung 3 Tage vor Ablauf
        Geschenkkarte Bargeld/Sachwert/Einlösung-Verbuchung
        Punkte-Übertragung zwischen Benutzern/Tageslimit/doppelte Transaktionen
        Provision Stufe 2 Zweitempfehler 2 % Provision
        Rabatt-ab-Mindestbetrag X-Y-Rabatt/automatische Stapelung bei Bestellung
        Punkte-Glücksrad Gewichtsziehung/Punkte-Guthaben-Gutschein/lose
      Wallet
        Guthabenabfrage
        Aufladen Gutschrift-Benachrichtigung
        Guthabenzahlung
        Rückerstattungs-Wiederauffüllung
        Guthabenübertragung zwischen Benutzern/Doppel-Zeilensperre/Übertragungsprotokoll
        Zahlungspasswort 6-stellige Zahl festlegen/prüfen/ändern
      Persönliches Zentrum
        Avatar/Nickname/Telefonnummer
        Identitätswechsel Kunde↔Techniker
        Benachrichtigungen
        Meine Favoriten
        Browser-Verlauf zuletzt angesehene Leistungen
        Gesundheitsprofil Allergien/bevorzugter Techniker
        Offiziellem Konto folgen
        Benutzer-Empfehlung QR-Code-Plakat/Provisionsdetails
        Wachstumsstufen Check-in/Bewertung/Konsum 5 Stufen
        Stufen-Vorteile Bestellrabatt/Punktemultiplikator
        Kundendienst-Ticket einreichen/Liste/Details/schließen
        Ticket-Zufriedenheit Bewertung beim Schließen/Backend-Zusammenfassung
        Feedback
      Einstellungen
        Passwort ändern
        Telefonnummer neu binden
        Vereinbarungen ansehen
        Update prüfen
        Datenschutz-Compliance Datenexport/Löschung 72h-Kreislauf
        Konto löschen

    Techniker-Workbench
      Anwesenheitsstempel
        Arbeitsbeginn stempeln Verspätungsmarkierung
        Arbeitsende stempeln
      Workbench-Kreislauf
        today heutige Bestellungen
        records Leistungsprotokolle
        start Leistung beginnen
        complete Verifizierung abschließen
      Tagesübersicht
        Anzahl heutiger Bestellungen
        Einnahmenübersicht
      Schichtplanverwaltung
        Zeitfenster pro Tag festlegen
        Buchbare Zeiten veröffentlichen
      Bestellbearbeitung
        Gebucht und nicht verifiziert Liste
        Abgeschlossene Liste
        QR-Verifizierung
      Mitgliederverwaltung
        Bediente Mitglieder
        Kursverbrauchsdaten
        Stempelkarten-Protokoll
        Mitgliedsprofil bearbeiten
      Bewertungsinteraktion
        Benutzerbewertung beantworten 404/doppelt 422/In-App-Benachrichtigung
      Einnahmenverwaltung
        Heutige Einnahmen
        Betrag in Abrechnung
        Wallet-Guthaben
        Unterwegs befindliches Guthaben automatische Bestätigung nach 3 Tagen
      Auszahlung
        Antrag am 20. jedes Monats
        T+1 auf WeChat-Guthaben
        Mindest-/Reserve-/Hunderter-Limits
      Stammkunden-Belohnung
        Bonus für 2. Konsum innerhalb von 30 Tagen
      Berufliche Weiterbildung
        Videokurse
        Bild-Text-Kurse

    Verwaltungsbackend
      Dashboard
        7 Statistik-Karten  Gesamtnutzer/neue heute/aktive Nutzer/Operationsprotokoll/heutige Buchungen/ausstehende Auszahlungen/ausstehende Technikerprüfung
        30-Tage-Trenddiagramme  Bestellvolumen/Betrag/neue Nutzer/Aktivität
        Benutzerstatus-Verteilungsdiagramm  aktiviert/deaktiviert
        Letzte Operationsprotokolle 10
        Schnellnavigation
        Interne Nachrichten
      Technikerverwaltung
        Technikerliste und Suche
        Hinzufügen/Export
        Aufnahmeanträge prüfen
        Schichtplan/Leistungsangebot festlegen
        Kursfortschritt verfolgen
        Automatische Technikerstufen-Bewertung Bestellmenge+Durchschnitt/nur Aufwertung/Änderungsprotokoll
        Anwesenheitsstatistik monatlich/nach Techniker gruppiert/Verspätung
      Benutzerverwaltung
        Mitgliederliste und Suche
        Details/Stufenfestlegung
        Vorgesetzten/Passwort/Telefon ändern
      Filialverwaltung
        Filial-CRUD
        Aktivieren/Deaktivieren
        Kartenkoordinaten konfigurieren
        Filial-Workbench Übersicht/Bestellfilter
      Leistungen und Produkte
        Leistungs-CRUD
        Produkt-CRUD
        Baumstruktur-Kategorienverwaltung
        Kartendesign Leistungs-+Produktkombination
      Shop-Verwaltung
        Shop-Bestellungen/Versand/Logistik
        After-Sales-Bestellprüfung
        Bewertungsverwaltung
        Bewertungsbild-Prüfung ausblenden/wiederherstellen Berechtigungen 389-391
        Zahlungstransaktionen
        Verkaufsstatistik
      Buchungsbestellungen
        Mehrfachkriterien-Suche
        Plattform-Stornierung/Erledigung bestätigen
        Details ansehen
      Gutscheinaktionen
        Gutschein-CRUD
        An-/Abheben
        Einlösestatistik
      Rabatt-ab-Mindestbetrag-Aktionen
        X-Y-Rabatt-CRUD
        An-/Abheben
      Punkte-Glücksrad
        Preis-CRUD
        An-/Abheben
        Drehprotokoll ansehen
      Blitzangebots-Aktionen
        Aktions-CRUD
        An-/Abheben
        Blitzbestellungen ansehen
      Punkte-Einlösung
        Einlöseartikel-CRUD
        An-/Abheben
        Einlöseprotokoll ansehen
      Mitgliederkartenverwaltung
        Mitgliederkartendefinition-CRUD
        Stempelkarte/Monatskarte/VIP
      After-Sales-Verwaltung
        After-Sales-Liste Status/Benutzer/Bestellfilter
        Prüfung genehmigen/ablehnen Bemerkung
      Bewertungen und Berichte
        Leistungsbewertungsverwaltung
        Datenberichte  Bestellstatistik/Techniker-TOP10/Kanalverteilung 7-30-Tage-Bereich Redis 300s
        Verkaufsstatistik  Zeitraum-Bestellübersicht/Standort/Servicetyp
      Finanzverwaltung
        Bestell-Umsatzbeteiligung
        Techniker-Auszahlungsprüfung
        Provisionsfestlegung und Belohnung/Strafe
        Einnahmen-Ausgaben-Transaktionen
        Finanzstatistik  Einnahmen/Erstattungen/Auszahlungen/Provisionen Zeitraumübersicht
        Auszahlungskonto/Limit-Konfiguration
        Rückerstattungs-Zweistufenfreigabe
        Distributions-Provisionsprotokoll
        Provisionsprotokoll Stufe 2 Berechtigung 386
        Umsatzbeteiligungs-Protokoll WeChat-Umsatzbeteiligung/Statusfilter
        Rechnungsprüfung ausstellen/ablehnen Berechtigungen 382-384
        Stammkunden-Belohnung Schalter/Verhältnis/Belohnungsprotokoll Berechtigungen 412-414
      Inhaltsverwaltung
        Karussell-CRUD
        Ankündigungs-CRUD und -Veröffentlichung
        Vereinbarungsbearbeitung
        FAQ-CRUD
        Feedback-Bearbeitung
        Kundendienst-Ticket-Antwort Berechtigungen 385/387
        Ticket-Zufriedenheitsstatistik Berechtigung 388
        Momente-Prüfung
        Über-uns-Einstellung
      Systemeinstellungen
        Plattformvereinbarungsverwaltung
        Einheitliche Techniker-Provision
        Systemnachrichten-Vorlagen
        APP-Push konfigurationsgetrieben/5 Ereignis-Anbindungen
        Abonnementnachrichten 3 Szenarien für Bestellereignisse
        APP-Versionsverwaltung Versions-CRUD/erzwungenes Update
        Unterkonten-Berechtigungen RBAC
      Erweiterte Funktionen
        Systemmonitoring CPU/Speicher/Redis/MySQL
        IP-Blacklist-Verwaltung
        Datenbank-Backup/Wiederherstellung
        Kundenprofil 360-Ansicht
        Batch-Nachrichten-Push
        Geplante-Aufgaben-Verwaltung
        SMS-Doppelkanal-Konfiguration
        Speicherkonfiguration lokal/OSS/COS
        Schichtplan-Excel-Export
        Filialleiter-Konten store_id-Isolation
```
