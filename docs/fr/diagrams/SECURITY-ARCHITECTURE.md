# Schéma de l'architecture de sécurité
> **Languages**: [中文](../../diagrams/SECURITY-ARCHITECTURE.md) · [English](../../en/diagrams/SECURITY-ARCHITECTURE.md) · [한국어](../../ko/diagrams/SECURITY-ARCHITECTURE.md) · [Русский](../../ru/diagrams/SECURITY-ARCHITECTURE.md) · [Deutsch](../../de/diagrams/SECURITY-ARCHITECTURE.md) · [Español](../../es/diagrams/SECURITY-ARCHITECTURE.md) · [Português](../../pt/diagrams/SECURITY-ARCHITECTURE.md) · [हिन्दी](../../hi/diagrams/SECURITY-ARCHITECTURE.md) · [العربية](../../ar/diagrams/SECURITY-ARCHITECTURE.md) · [বাংলা](../../bn/diagrams/SECURITY-ARCHITECTURE.md) · [Bahasa Indonesia](../../id/diagrams/SECURITY-ARCHITECTURE.md) · [日本語](../../ja/diagrams/SECURITY-ARCHITECTURE.md)

## 1. Système de défense en profondeur

```mermaid
graph TB
    subgraph 边界防护["Première couche : protection périmétrique"]
        WAF["WAF / Nginx<br/>en-têtes de sécurité<br/>protection des fichiers sensibles<br/>TLS 1.3"]
    end

    subgraph 接入防护["Deuxième couche : protection d'accès"]
        CORS["Middleware Cors<br/>liste blanche CORS_ALLOW_ORIGIN<br/>réponse * · non configuré : même origine uniquement<br/>6 en-têtes de sécurité<br/>pré-vérification OPTIONS"]
    end

    subgraph 攻击检测["Troisième couche : détection des attaques"]
        SEC["Middleware Security<br/>erikwang2013/security-php<br/>31 détecteurs d'attaques<br/>XSS / injection SQL / CSRF<br/>traversée de chemin / inclusion de fichiers<br/>détection d'origine CSRF (block)"]
        BLOCK["Bannissement automatique<br/>5 attaques / 60 s<br/>→ liste noire IP 15 min"]
    end

    subgraph 流量控制["Quatrième couche : contrôle du trafic"]
        RL["Middleware RateLimit<br/>fenêtre glissante Redis + atomique Lua<br/>défaut : 60 req/min/IP<br/>connexion : 10 req/min<br/>inscription : 5 req/min<br/>code de vérification : 1 req/60 s/numéro de mobile"]
    end

    subgraph 身份认证["Cinquième couche : authentification"]
        AUTH["Middleware Auth<br/>JWT Bearer Token (7 jours)<br/>JWT_SECRET_KEY obligatoire<br/>démarrage refusé si manquant / valeur publique par défaut<br/>mots de passe hachés bcrypt<br/>rafraîchissement du Token + liste noire<br/>verrouillage de connexion : 5 échecs → 15 min<br/>limite de concurrence : 3 Tokens maximum"]
        TECH_AUTH["TechnicianAuth<br/>validation du dossier du technicien<br/>contrôle du statut approved"]
        ADMIN_AUTH["AdminAuth<br/>authentification JWT du back-office<br/>liste noire des Tokens"]
    end

    subgraph 权限控制["Sixième couche : contrôle des permissions"]
        RBAC["AdminPermission<br/>vérification des permissions de rôle RBAC<br/>cache Redis 60 s<br/>utilisateur → rôle → permission"]
        POSTER["Validation Poster<br/>erikwang2013/poster-php<br/>suppression / examen / retrait<br/>validation aléatoire des opérations sensibles"]
    end

    subgraph 数据安全["Septième couche : sécurité des données"]
        ENC_API["Chiffrement de la couche API<br/>erikwang2013/encryption<br/>chiffrement / déchiffrement des champs sensibles"]
        ENC_DB["Chiffrement de la couche DB<br/>erikwang2013/encryptable<br/>chiffrement / déchiffrement automatique via trait de modèle<br/>chiffre uniquement real_name / id_card, etc.<br/>phone / wx_openid stockés en clair<br/>(la connexion / la recherche de doublons reposent sur la recherche en clair)"]
        HASHID["Chiffrement des ID<br/>erikwang2013/hashids<br/>masquage des ID réels<br/>encodage / déchiffrement récursifs"]
        SLOG["Journaux de sécurité<br/>masquage unifié des anomalies M3<br/>messages génériques + Log::error<br/>aucune donnée sensible dans les journaux<br/>OperationLog : 8 sources"]
    end

    subgraph 管理端防护["Huitième couche : protection du back-office"]
        EXCEL["Protection des exports<br/>safeCellValue()<br/>= + - @ / début par Tab / CR<br/>échappement par préfixe ' contre l'injection de formules"]
        UPLOAD["Validation des uploads<br/>finfo magic bytes<br/>MIME incompatible avec l'extension<br/>→ rejet 422"]
        INSTALL["Verrou d'installation<br/>déjà installé (installed=1<br/>ou administrateur existant)<br/>→ assistant d'installation désactivé 404"]
    end

    请求["Requête HTTP"] --> WAF
    WAF --> CORS
    CORS --> SEC
    SEC -->|"Passe"| RL
    SEC -->|"Attaque détectée"| BLOCK
    BLOCK -.->|"Rejet"| 拒绝["HTTP 403/429<br/>journalisation de l'attaque"]
    RL -->|"Passe"| AUTH
    RL -->|"Limite dépassée"| 限流拒绝["HTTP 429<br/>Retry-After"]
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
    INSTALL --> 响应["HTTP Response<br/>données chiffrées et encodées"]

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

## 2. Matrice des composants de sécurité

```mermaid
graph LR
    subgraph 组件["Composants de sécurité"]
        C1["security-php<br/>━━━━━━━━<br/>31 détecteurs d'attaques<br/>XSS / injection SQL / CSRF<br/>traversée de chemin / inclusion de fichiers<br/>détection d'origine CSRF"]
        C2["encryption<br/>━━━━━━━━<br/>AES-256-CBC<br/>chiffrement de la couche API<br/>prise en charge de la rotation des clés"]
        C3["encryptable<br/>━━━━━━━━<br/>chiffrement / déchiffrement automatique des champs DB<br/>chiffre uniquement real_name / id_card, etc.<br/>phone / wx_openid stockés en clair<br/>compatibilité d'expansion du chiffrement VARCHAR(500)"]
        C4["hashids<br/>━━━━━━━━<br/>encodage / décodage des ID<br/>traitement récursif des relations<br/>masquage des ID réels"]
        C5["jwt-webman<br/>━━━━━━━━<br/>Bearer Token<br/>JWT_SECRET_KEY obligatoire<br/>démarrage refusé si manquant / valeur par défaut<br/>7 jours + rafraîchissement + liste noire<br/>concurrence ≤ 3"]
        C6["poster-php<br/>━━━━━━━━<br/>validation aléatoire avant opération<br/>suppression / examen / retrait<br/>prévention des fausses manipulations"]
        C7["snowflake-php<br/>━━━━━━━━<br/>ID distribués BIGINT<br/>non incrémentaux anti-énumération<br/>unicité globale"]
    end

    subgraph 攻击面["Surfaces d'attaque couvertes"]
        A1["Injections<br/>SQL / commande / LDAP"]
        A2["XSS / CSRF<br/>script intersite / falsification de requête"]
        A3["Traversée de chemin<br/>dépassement de répertoire / inclusion de fichiers"]
        A4["Force brute<br/>attaque de connexion / attaque de code de vérification"]
        A5["Fuite de données<br/>énumération des ID / champs sensibles"]
        A6["Actions non autorisées<br/>dépassement horizontal / vertical"]
        A7["Abus de concurrence<br/>prolifération des Tokens / rafale d'appels API"]
    end

    C1 -.->|Défend| A1
    C1 -.->|Défend| A2
    C1 -.->|Défend| A3
    C2 -.->|Défend| A5
    C3 -.->|Défend| A5
    C4 -.->|Défend| A5
    C5 -.->|Défend| A4
    C5 -.->|Défend| A7
    C6 -.->|Défend| A6
    C7 -.->|Défend| A5

    classDef comp fill:#e8eaf6,stroke:#3949ab,stroke-width:2px,color:#1a237e
    classDef attack fill:#ffebee,stroke:#c62828,stroke-width:1px,color:#b71c1c

    class C1,C2,C3,C4,C5,C6,C7 comp
    class A1,A2,A3,A4,A5,A6,A7 attack
```

## 3. Processus d'authentification et d'autorisation

```mermaid
flowchart TD
    A["Requête du client"] --> B{"Token présent ?"}
    B -->|"Non"| C["Retour 401<br/>invitation à se connecter"]
    B -->|"Oui"| D["Analyse du JWT Token"]
    D --> E{"Token valide ?"}
    E -->|"Expiré"| F{"Refresh Token ?"}
    F -->|"Oui"| G["Rafraîchissement du Token<br/>ancien Token ajouté à la liste noire"]
    F -->|"Non"| C
    G --> H["Retour du nouveau Token"]
    E -->|"Valide"| I{"Vérification de la liste noire"}
    I -->|"Liste noire"| C
    I -->|"Normal"| J["Recherche des informations de l'utilisateur"]
    J --> K{"Utilisateur existant et actif ?"}
    K -->|"Non"| L["Retour 403<br/>compte désactivé"]
    K -->|"Oui"| M{"Nombre d'échecs de connexion ?"}
    M -->|"≥ 5 / 15 min"| N["Retour 429<br/>compte verrouillé"]
    M -->|"Normal"| O{"Nombre de Tokens concurrents ?"}
    O -->|"> 3"| P["Invalidation automatique de l'ancien Token<br/>ajout à la liste noire"]
    O -->|"≤ 3"| Q{"Identité technicien requise ?"}
    Q -->|"Oui"| R{"Dossier du technicien approuvé ?"}
    R -->|"Non"| S["Retour 403<br/>non technicien ou en cours d'examen"]
    R -->|"Oui"| T{"RBAC requis ?"}
    Q -->|"Non"| T
    T -->|"Oui"| U{"Vérification des permissions"}
    U -->|"Sans permission"| V["Retour 403<br/>aucune permission"]
    U -->|"Avec permission"| W["Exécution de la logique métier"]
    T -->|"Non"| W
    W --> X["Retour de la réponse<br/>ID encodés<br/>données sensibles chiffrées"]

    style C fill:#ffcdd2,stroke:#c62828,color:#333
    style L fill:#ffcdd2,stroke:#c62828,color:#333
    style N fill:#ffcdd2,stroke:#c62828,color:#333
    style S fill:#ffcdd2,stroke:#c62828,color:#333
    style V fill:#ffcdd2,stroke:#c62828,color:#333
    style W fill:#c8e6c9,stroke:#2e7d32,color:#333
    style X fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 4. Flux de sécurité des données

```mermaid
flowchart LR
    subgraph 输入["Saisie utilisateur"]
        I1["Numéro de mobile en clair"]
        I2["Carte d'identité en clair"]
        I3["OpenID en clair"]
        I4["Nom en clair"]
    end

    subgraph API加密["Couche API (encryption)"]
        E1["encrypt(id_card)<br/>→ texte chiffré"]
        E2["encrypt(real_name)<br/>→ texte chiffré"]
    end

    subgraph DB存储["Couche de stockage DB"]
        D1["appointment_user.phone<br/>stocké en clair<br/>la connexion / la recherche de doublons reposent sur la recherche en clair"]
        D2["appointment_technician_profile<br/>.id_card VARCHAR(500)<br/>chiffré via encryptable"]
        D3["appointment_user.wx_openid<br/>stocké en clair"]
        D4["appointment_user.real_name<br/>chiffré via encryptable"]
    end

    subgraph ID处理["Traitement des ID (hashids + snowflake)"]
        H1["Génération Snowflake<br/>1860000000000001"]
        H2["Encodage Hashids<br/>→ 'Kx9mP2vR'"]
        H3["Réponse API<br/>id : 'Kx9mP2vR'"]
    end

    subgraph 输出["Sortie externe"]
        O1["ID encodés<br/>non énumérables"]
        O2["Champs sensibles masqués<br/>aucun texte en clair dans les journaux"]
        O3["En-têtes avec politiques de sécurité<br/>CSP / CORS / HSTS"]
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
