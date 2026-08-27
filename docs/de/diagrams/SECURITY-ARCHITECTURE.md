# Sicherheitsarchitektur-Diagramm
> **Languages**: [中文](../../diagrams/SECURITY-ARCHITECTURE.md) · [English](../../en/diagrams/SECURITY-ARCHITECTURE.md) · [한국어](../../ko/diagrams/SECURITY-ARCHITECTURE.md) · [Русский](../../ru/diagrams/SECURITY-ARCHITECTURE.md) · [Français](../../fr/diagrams/SECURITY-ARCHITECTURE.md) · [Español](../../es/diagrams/SECURITY-ARCHITECTURE.md) · [Português](../../pt/diagrams/SECURITY-ARCHITECTURE.md) · [हिन्दी](../../hi/diagrams/SECURITY-ARCHITECTURE.md) · [العربية](../../ar/diagrams/SECURITY-ARCHITECTURE.md) · [বাংলা](../../bn/diagrams/SECURITY-ARCHITECTURE.md) · [Bahasa Indonesia](../../id/diagrams/SECURITY-ARCHITECTURE.md) · [日本語](../../ja/diagrams/SECURITY-ARCHITECTURE.md)

> Deutsche Übersetzung · Original: [中文](../../diagrams/SECURITY-ARCHITECTURE.md)

## 1. Verteidigung-in-der-Tiefe-System

```mermaid
graph TB
    subgraph 边界防护["Ebene 1: Randabsicherung"]
        WAF["WAF / Nginx<br/>Sicherheits-Response-Header<br/>Schutz sensibler Dateien<br/>TLS 1.3"]
    end

    subgraph 接入防护["Ebene 2: Zugangsabsicherung"]
        CORS["Cors-Middleware<br/>CORS_ALLOW_ORIGIN-Whitelist<br/>* Echo · ohne Konfiguration nur gleiche Quelle<br/>6 Sicherheits-Response-Header<br/>OPTIONS-Preflight"]
    end

    subgraph 攻击检测["Ebene 3: Angriffserkennung"]
        SEC["Security-Middleware<br/>erikwang2013/security-php<br/>31 Angriffserkennungen<br/>XSS / SQL-Injection / CSRF<br/>Pfad-Traversal / Datei-Inclusion<br/>CSRF-Origin-Erkennung (block)"]
        BLOCK["Automatische Sperre<br/>5 Angriffe/60s<br/>→ IP-Blacklist 15min"]
    end

    subgraph 流量控制["Ebene 4: Traffic-Steuerung"]
        RL["RateLimit-Middleware<br/>Redis Sliding Window + Lua atomar<br/>Standard: 60-mal/min/IP<br/>Login: 10-mal/min<br/>Registrierung: 5-mal/min<br/>Verifizierungscode: 1-mal/60s/Telefonnummer"]
    end

    subgraph 身份认证["Ebene 5: Identitätsauthentifizierung"]
        AUTH["Auth-Middleware<br/>JWT Bearer Token (7 Tage)<br/>JWT_SECRET_KEY Pflichtkonfiguration<br/>fehlend/öffentlicher Standardwert verweigert Start<br/>Passwort bcrypt-Hash<br/>Token-Refresh + Blacklist<br/>Login-Sperre: 5 Fehlversuche→15min<br/>Parallelitätslimit: maximal 3 Token"]
        TECH_AUTH["TechnicianAuth<br/>Technikerprofil-Prüfung<br/>approved-Statusprüfung"]
        ADMIN_AUTH["AdminAuth<br/>Admin-JWT-Authentifizierung<br/>Token-Blacklist"]
    end

    subgraph 权限控制["Ebene 6: Berechtigungskontrolle"]
        RBAC["AdminPermission<br/>RBAC-Rollenberechtigungsprüfung<br/>Redis 60s Cache<br/>Benutzer→Rolle→Berechtigung"]
        POSTER["Poster-Verifizierung<br/>erikwang2013/poster-php<br/>Löschen/Prüfung/Auszahlung<br/>Zufallsprüfung sensibler Operationen"]
    end

    subgraph 数据安全["Ebene 7: Datensicherheit"]
        ENC_API["API-Ebenen-Verschlüsselung<br/>erikwang2013/encryption<br/>sensible Felder ver-/entschlüsseln"]
        ENC_DB["DB-Ebenen-Verschlüsselung<br/>erikwang2013/encryptable<br/>Model-Trait automatische Ver-/Entschlüsselung<br/>verschlüsselt nur real_name/id_card usw.<br/>phone/wx_openid müssen im Klartext gespeichert werden<br/>(Login/Duplikatsuche hängt von Klartext-Abfrage ab)"]
        HASHID["ID-Ver-/Entschlüsselung<br/>erikwang2013/hashids<br/>verbirgt echte IDs nach außen<br/>rekursive Codierung/Dekodierung"]
        SLOG["Sicherheitslog<br/>M3-Ausnahmen einheitlich maskiert<br/>allgemeiner Text + Log::error<br/>sensible Daten nicht im Log<br/>OperationLog 8 Quell-Endpunkte"]
    end

    subgraph 管理端防护["Ebene 8: Verwaltungsseiten-Schutz"]
        EXCEL["Export-Schutz<br/>safeCellValue()<br/>= + - @ / Tab/CR am Anfang<br/>Präfix ' maskiert gegen Formel-Injection"]
        UPLOAD["Upload-Prüfung<br/>finfo magic bytes<br/>MIME und Dateiendung nicht passend<br/>→ 422 abgelehnt"]
        INSTALL["Installationssperre<br/>bereits installiert (installed=1<br/>oder Administrator vorhanden)<br/>→ 404 Installationsassistent deaktiviert"]
    end

    请求["HTTP Request"] --> WAF
    WAF --> CORS
    CORS --> SEC
    SEC -->|"bestanden"| RL
    SEC -->|"Angriff erkannt"| BLOCK
    BLOCK -.->|"abgelehnt"| 拒绝["HTTP 403/429<br/>Angriffslog aufzeichnen"]
    RL -->|"bestanden"| AUTH
    RL -->|"Limit überschritten"| 限流拒绝["HTTP 429<br/>Retry-After"]
    AUTH --> TECH_AUTH
    AUTH --> ADMIN_AUTH
    TECH_AUTH --> RBAC
    ADMIN_AUTH --> RBAC
    RBAC --> POSTER
    POSTER --> ENC_API
    ENC_API --> ENC_DB
    ENC_DB --> HASHID
    HASHID --> SLOG
    SLOG --> EXCEL
    EXCEL --> UPLOAD
    UPLOAD --> INSTALL
    INSTALL --> 响应["HTTP Response<br/>Daten verschlüsselt + codiert"]

    classDef layer1 fill:#e3f2fd,stroke:#1565c0,stroke-width:2px,color:#01579b
    classDef layer2 fill:#bbdefb,stroke:#1976d2,stroke-width:2px,color:#01579b
    classDef layer3 fill:#ffcdd2,stroke:#c62828,stroke-width:2px,color:#b71c1c
    classDef layer4 fill:#fff9c4,stroke:#f9a825,stroke-width:2px,color:#f57f17
    classDef layer5 fill:#c8e6c9,stroke:#2e7d32,stroke-width:2px,color:#1b5e20
    classDef layer6 fill:#e1bee7,stroke:#7b1fa2,stroke-width:2px,color:#4a148c
    classDef layer7 fill:#d7ccc8,stroke:#5d4037,stroke-width:2px,color:#3e2723
    classDef layer8 fill:#cfd8dc,stroke:#37474f,stroke-width:2px,color:#263238
    classDef reject fill:#ff5252,stroke:#b71c1c,stroke-width:2px,color:#fff

    class WAF layer1
    class CORS layer2
    class SEC,BLOCK layer3
    class RL layer4
    class AUTH,TECH_AUTH,ADMIN_AUTH layer5
    class RBAC,POSTER layer6
    class ENC_API,ENC_DB,HASHID,SLOG layer7
    class EXCEL,UPLOAD,INSTALL layer8
    class 拒绝,限流拒绝 reject
```

## 2. Sicherheitskomponenten-Matrix

```mermaid
graph LR
    subgraph 组件["Sicherheitskomponenten"]
        C1["security-php<br/>━━━━━━━━<br/>31 Angriffserkennungen<br/>XSS/SQL-Injection/CSRF<br/>Pfad-Traversal/Datei-Inclusion<br/>CSRF-Origin-Erkennung"]
        C2["encryption<br/>━━━━━━━━<br/>AES-256-CBC<br/>API-Ebenen-Ver-/Entschlüsselung<br/>Schlüsselrotation unterstützt"]
        C3["encryptable<br/>━━━━━━━━<br/>DB-Felder automatisch ver-/entschlüsselt<br/>verschlüsselt nur real_name/id_card usw.<br/>phone/wx_openid im Klartext gespeichert<br/>VARCHAR(500) kompatibel mit Verschlüsselungsaufblähung"]
        C4["hashids<br/>━━━━━━━━<br/>ID-Codierung/-Dekodierung<br/>rekursive Verarbeitung von Beziehungen<br/>verbirgt echte IDs nach außen"]
        C5["jwt-webman<br/>━━━━━━━━<br/>Bearer Token<br/>JWT_SECRET_KEY Pflichtkonfiguration<br/>fehlend/Standardwert verweigert Start<br/>7 Tage + Refresh + Blacklist<br/>parallel ≤3"]
        C6["poster-php<br/>━━━━━━━━<br/>Zufallsprüfung vor Operation<br/>Löschen/Prüfung/Auszahlung<br/>gegen Fehlbedienung"]
        C7["snowflake-php<br/>━━━━━━━━<br/>BIGINT verteilte IDs<br/>nicht autoinkrementierend gegen Enumerierung<br/>global eindeutig"]
    end

    subgraph 攻击面["Geschützte Angriffsflächen"]
        A1["Injection-Angriffe<br/>SQL/Kommando/LDAP"]
        A2["XSS/CSRF<br/>Cross-Site-Scripting/Request-Fälschung"]
        A3["Pfad-Traversal<br/>Verzeichnis-Sprung/Datei-Inclusion"]
        A4["Brute-Force<br/>Login-Brute-Force/Verifizierungscode-Brute-Force"]
        A5["Datenlecks<br/>ID-Enumerierung/sensible Felder"]
        A6["Autorisierungsüberschreitung<br/>horizontal/vertikal"]
        A7["Parallelitäts-Missbrauch<br/>Token-Flut/Schnittstellen-Bombardierung"]
    end

    C1 -.->|Abwehr| A1
    C1 -.->|Abwehr| A2
    C1 -.->|Abwehr| A3
    C2 -.->|Abwehr| A5
    C3 -.->|Abwehr| A5
    C4 -.->|Abwehr| A5
    C5 -.->|Abwehr| A4
    C5 -.->|Abwehr| A7
    C6 -.->|Abwehr| A6
    C7 -.->|Abwehr| A5

    classDef comp fill:#e8eaf6,stroke:#3949ab,stroke-width:2px,color:#1a237e
    classDef attack fill:#ffebee,stroke:#c62828,stroke-width:1px,color:#b71c1c

    class C1,C2,C3,C4,C5,C6,C7 comp
    class A1,A2,A3,A4,A5,A6,A7 attack
```

## 3. Authentifizierungs- und Autorisierungsablauf

```mermaid
flowchart TD
    A["Client-Anfrage"] --> B{"Token vorhanden?"}
    B -->|"nein"| C["401 zurückgeben<br/>Login-Hinweis"]
    B -->|"ja"| D["JWT-Token parsen"]
    D --> E{"Token gültig?"}
    E -->|"abgelaufen"| F{"Refresh Token?"}
    F -->|"ja"| G["Token aktualisieren<br/>altes Token in die Blacklist"]
    F -->|"nein"| C
    G --> H["Neues Token zurückgeben"]
    E -->|"gültig"| I{"Blacklist-Prüfung"}
    I -->|"gesperrt"| C
    I -->|"normal"| J["Benutzerinformationen abfragen"]
    J --> K{"Benutzer existiert und aktiv?"}
    K -->|"nein"| L["403 zurückgeben<br/>Konto deaktiviert"]
    K -->|"ja"| M{"Anzahl Login-Fehlversuche?"}
    M -->|"≥5-mal/15min"| N["429 zurückgeben<br/>Konto gesperrt"]
    M -->|"normal"| O{"Anzahl paralleler Token?"}
    O -->|">3"| P["alte Token automatisch ungültig<br/>in die Blacklist"]
    O -->|"≤3"| Q{"Techniker-Identität erforderlich?"}
    Q -->|"ja"| R{"Technikerprofil approved?"}
    R -->|"nein"| S["403 zurückgeben<br/>kein Techniker oder in Prüfung"]
    R -->|"ja"| T{"RBAC erforderlich?"}
    Q -->|"nein"| T
    T -->|"ja"| U{"Berechtigungsprüfung"}
    U -->|"keine Berechtigung"| V["403 zurückgeben<br/>keine Operationsberechtigung"]
    U -->|"mit Berechtigung"| W["Geschäftslogik ausführen"]
    T -->|"nein"| W
    W --> X["Antwort zurückgeben<br/>IDs codiert<br/>sensible Daten verschlüsselt"]

    style C fill:#ffcdd2,stroke:#c62828,color:#333
    style L fill:#ffcdd2,stroke:#c62828,color:#333
    style N fill:#ffcdd2,stroke:#c62828,color:#333
    style S fill:#ffcdd2,stroke:#c62828,color:#333
    style V fill:#ffcdd2,stroke:#c62828,color:#333
    style W fill:#c8e6c9,stroke:#2e7d32,color:#333
    style X fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 4. Datensicherheits-Ablauf

```mermaid
flowchart LR
    subgraph 输入["Benutzereingabe"]
        I1["Telefonnummer im Klartext"]
        I2["Personalausweis im Klartext"]
        I3["OpenID im Klartext"]
        I4["Name im Klartext"]
    end

    subgraph API加密["API-Ebene (encryption)"]
        E1["encrypt(id_card)<br/>→ ciphertext"]
        E2["encrypt(real_name)<br/>→ ciphertext"]
    end

    subgraph DB存储["DB-Ebenen-Speicherung"]
        D1["appointment_user.phone<br/>im Klartext gespeichert<br/>Login/Duplikatsuche hängt von Klartext-Abfrage ab"]
        D2["appointment_technician_profile<br/>.id_card VARCHAR(500)<br/>encryptable verschlüsselt"]
        D3["appointment_user.wx_openid<br/>im Klartext gespeichert"]
        D4["appointment_user.real_name<br/>encryptable verschlüsselt"]
    end

    subgraph ID处理["ID-Verarbeitung (hashids + snowflake)"]
        H1["Snowflake-Generierung<br/>1860000000000001"]
        H2["Hashids-Codierung<br/>→ 'Kx9mP2vR'"]
        H3["API-Antwort<br/>id: 'Kx9mP2vR'"]
    end

    subgraph 输出["Ausgabe nach außen"]
        O1["IDs codiert<br/>nicht enumerierbar"]
        O2["sensible Felder maskiert<br/>Log ohne Klartext"]
        O3["Response-Header mit Sicherheitsrichtlinien<br/>CSP/CORS/HSTS"]
    end

    I1 --> D1
    I2 --> E1 --> D2
    I3 --> D3
    I4 --> E2 --> D4
    D1 --> H1 --> H2 --> H3
    H3 --> O1
    D1 --> O2
    O1 --> O3

    classDef input fill:#e3f2fd,stroke:#1565c0,color:#333
    classDef encrypt fill:#fff3e0,stroke:#f57c00,color:#333
    classDef db fill:#fce4ec,stroke:#c62828,color:#333
    classDef id fill:#e8f5e9,stroke:#2e7d32,color:#333
    classDef output fill:#f3e5f5,stroke:#7b1fa2,color:#333

    class I1,I2,I3,I4 input
    class E1,E2 encrypt
    class D1,D2,D3,D4 db
    class H1,H2,H3 id
    class O1,O2,O3 output
```
