# Schéma d'architecture du système
> **Languages**: [中文](../../diagrams/ARCHITECTURE-DIAGRAM.md) · [English](../../en/diagrams/ARCHITECTURE-DIAGRAM.md) · [한국어](../../ko/diagrams/ARCHITECTURE-DIAGRAM.md) · [Русский](../../ru/diagrams/ARCHITECTURE-DIAGRAM.md) · [Deutsch](../../de/diagrams/ARCHITECTURE-DIAGRAM.md) · [Español](../../es/diagrams/ARCHITECTURE-DIAGRAM.md) · [Português](../../pt/diagrams/ARCHITECTURE-DIAGRAM.md) · [हिन्दी](../../hi/diagrams/ARCHITECTURE-DIAGRAM.md) · [العربية](../../ar/diagrams/ARCHITECTURE-DIAGRAM.md) · [বাংলা](../../bn/diagrams/ARCHITECTURE-DIAGRAM.md) · [Bahasa Indonesia](../../id/diagrams/ARCHITECTURE-DIAGRAM.md) · [日本語](../../ja/diagrams/ARCHITECTURE-DIAGRAM.md)

```mermaid
graph TB
    subgraph CLIENTS["Couche des terminaux utilisateurs"]
        WX["Mini-programme WeChat<br/>apps/wechat/<br/>WXML/WXSS/JS natif"]
        APP["Flutter APP<br/>apps/flutter/<br/>iOS + Android<br/>GetX + Dio"]
    end

    subgraph SERVICE_LAYER["Couche des services métier :8787"]
        direction TB
        MW1["Chaîne de middleware<br/>Cors → Security → RateLimit"]
        subgraph API_MODULES["Module de routes API"]
            PUB["API publique<br/>api/<br/>connexion/inscription/code de vérification"]
            USER["Module utilisateur<br/>user/<br/>profil/adresses/favoris"]
            TECH["Module technicien<br/>technician/<br/>planning/poste de travail/vérification/revenus/retrait"]
            SVC["Module services<br/>service/<br/>catégories/projets/recherche"]
            ORD["Module commandes<br/>order/<br/>panier/commande/paiement/remboursement/vérification"]
            MKT["Module marketing<br/>marketing/<br/>bons/cartes de membre (à forfait)/points<br/>cartes cadeaux/avantages membres"]
            WALLET["Module portefeuille<br/>wallet/<br/>solde/recharge/flux de transactions<br/>paiement par solde"]
            CTN["Module contenu<br/>content/<br/>bannières/annonces/notifications"]
            LBS["Module LBS<br/>lbs/<br/>villes/boutiques à proximité"]
            CACHE["Cache de listes Redis<br/>préfixe svc:* setex 300s<br/>catégories/projets/produits/techniciens/contenu<br/>interfaces de listes de cartes/marketing<br/>clearSvcCache() invalidation sur les chemins d'écriture admin"]
            RES["Contrat de réponse<br/>success/paginate code=0<br/>codes d'erreur non nuls<br/>correspond à la convention du mini-programme"]
        end
    end

    subgraph ADMIN_LAYER["Couche du back-office :8787"]
        MW2["Chaîne de middleware<br/>Cors → Security → RateLimit → AdminAuth → RBAC → OperationLog"]
        ADMIN_API["API de gestion<br/>admin/controller/<br/>tableau de bord/utilisateurs/techniciens/boutiques/services<br/>commandes/bons/cartes de membre/retraits/évaluations<br/>rapports/finances/contenu/paramètres"]
        FLUTTER_WEB["Frontend Flutter Web<br/>admin/apps/flutter/<br/>interface PC du back-office"]
        MODEL["Modèles partagés<br/>admin/app/model<br/>39 symlinks<br/>→ service/app/model même implémentation"]
    end

    subgraph DATA_LAYER["Couche de données"]
        MySQL[("MySQL 8.0<br/>55+ tables · préfixe erik_<br/>clés primaires BIGINT Snowflake")]
        Redis[("Redis<br/>cache/limitation/session<br/>file d'attente/verrous techniciens<br/>cache de listes svc:*")]
        ES[("Elasticsearch<br/>recherche plein texte<br/>synchronisation automatique webman-scout")]
    end

    subgraph EXTERNAL["Services tiers"]
        WXPAY["WeChat Pay<br/>commande unifiée/remboursement/retrait"]
        SMS["Service SMS<br/>Aliyun/Tencent Cloud"]
        MAP["Service cartographique<br/>AMap/Tencent<br/>géocodage inverse/navigation"]
        OSS["Stockage objet<br/>local/OSS/COS/CDN"]
        SUBMSG["Messages d'abonnement WeChat<br/>WechatTemplateMessageService<br/>sendSubscribeMessage<br/>3 scénarios d'événements de commande"]
    end

    subgraph SECURITY["Couche des composants de sécurité"]
        SEC["Security-PHP<br/>31 types de détection d'attaques"]
        JWT["Authentification JWT<br/>validité 7 jours + liste noire"]
        ENC["Double chiffrement<br/>couche API + couche DB"]
        POSTER["Validation des opérations<br/>validation aléatoire des opérations sensibles"]
    end

    WX -->|"HTTP API<br/>fonctionnalités équivalentes"| MW1
    APP -->|"HTTP API<br/>fonctionnalités équivalentes"| MW1
    MW1 --> API_MODULES

    FLUTTER_WEB -->|"HTTP API"| MW2
    MW2 --> ADMIN_API

    API_MODULES --> MySQL
    API_MODULES --> Redis
    API_MODULES --> ES
    ADMIN_API --> MySQL
    ADMIN_API --> Redis
    ADMIN_API --> ES

    SECURITY -.->|protection| SERVICE_LAYER
    SECURITY -.->|protection| ADMIN_LAYER

    API_MODULES -.->|appels| EXTERNAL
    ADMIN_API -.->|appels| EXTERNAL

    classDef terminal fill:#e1f5fe,stroke:#0288d1,stroke-width:2px,color:#01579b
    classDef service fill:#fff3e0,stroke:#f57c00,stroke-width:2px,color:#e65100
    classDef admin fill:#e8f5e9,stroke:#388e3c,stroke-width:2px,color:#1b5e20
    classDef data fill:#fce4ec,stroke:#c62828,stroke-width:2px,color:#880e4f
    classDef external fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px,color:#4a148c
    classDef security fill:#fff8e1,stroke:#f9a825,stroke-width:2px,color:#f57f17

    class WX,APP terminal
    class MW1,API_MODULES,PUB,USER,TECH,SVC,ORD,MKT,WALLET,CTN,LBS,CACHE,RES service
    class MW2,ADMIN_API,FLUTTER_WEB,MODEL admin
    class MySQL,Redis,ES data
    class WXPAY,SMS,MAP,OSS,SUBMSG external
    class SEC,JWT,ENC,POSTER security
```
