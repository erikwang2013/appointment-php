# Architecture

## Vue d'ensemble du système

Le système de réservation de services adopte une architecture « trois applications + deux services » :

```
┌─────────────────────────────────────────────────────┐
│                    Couche des terminaux utilisateurs │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ Mini-prog.    │  │ Flutter APP  │                │
│  │ WeChat        │  │             │                 │
│  │ apps/wechat/  │  │ apps/flutter/ │                │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │   Équivalence    │                         │
│         │   fonctionnelle   │                         │
│         └────────┬─────────┘                         │
│                  │ Changement d'identité client/technicien│
├──────────────────┼──────────────────────────────────┤
│               Couche d'API métier                     │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ service/ API │  │ admin/ API   │                 │
│  │ Port 8787    │  │ Port 8787    │                 │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │                  │                          │
│         └────────┬─────────┘                          │
│                  │ Partage MySQL/Redis/ES             │
├──────────────────┼──────────────────────────────────┤
│                  Couche de données                    │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌──────────┐     │
│  │ MySQL  │ │ Redis  │ │  ES    │ │ Services │     │
│  │        │ │        │ │        │ │ tiers     │     │
│  └────────┘ └────────┘ └────────┘ └──────────┘     │
└─────────────────────────────────────────────────────┘
```

## Composition du projet

### service/ — Service d'API métier

Fournit toutes les interfaces métier au mini-programme WeChat et à l'application Flutter. webman v2, port 8787.

**Découpage en modules :**

| Module | Chemin | Authentification | Description |
|------|------|------|------|
| API publique | `api/` | Aucune | Connexion / inscription / code de vérification / callback WeChat |
| Module utilisateur | `user/` | JWT | Profil / adresse / favoris / retour d'expérience / parrainage |
| Module technicien | `technician/` | JWT+technicien | Profil / planning / poste de travail / vérification / membres / revenus / retrait |
| Module services | `service/` | Mixte | Catégories / prestations / recherche / boutiques |
| Module commandes | `order/` | JWT | Panier / commande / paiement / remboursement / vérification / avis (OrderController découpé en 10 traits par domaine métier, routes et noms de méthodes inchangés) |
| Module marketing | `marketing/` | JWT | Bons / cartes de membre (carte à forfait) / points / cartes cadeaux / avantages membres |
| Module portefeuille | `wallet/` | JWT | Solde / recharge / historique des transactions / paiement par solde |
| Module contenu | `content/` | Mixte | Bannières / annonces / notifications |
| Module LBS | `lbs/` | Public | Villes / boutiques à proximité |

### admin/ — Back-office

Back-office PC. webman v2 + Flutter Web, port 8787.

**Modules existants :** authentification, tableau de bord, gestion des utilisateurs, rôles et permissions, configuration système, journaux d'opérations, téléchargement de fichiers, protection de sécurité

**Répartition des modèles :** `admin/app/model/` ne conserve que 6 modèles spécifiques (AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig) ; les autres modèles partagent la version service via composer psr-4 (`app\model\` → `../service/app/model/`), évitant la dérive d'un double jeu de modèles ; la classe de base `support\Model` est alignée sur service, la méthode de relation `UserPointsExchange::user()` est fusionnée dans le modèle côté service.

**Modules étendus :** gestion des techniciens, gestion des membres, gestion des boutiques, gestion des services/produits, gestion des commandes, bons, cartes de membre, validation des retraits, gestion des avis, statistiques de rapports, gestion financière, gestion de contenu, paramètres système

### apps/ — Frontend utilisateur

| Répertoire | Technologie | Plateforme |
|------|------|------|
| `apps/wechat/` | Mini-programme WeChat natif | WeChat |
| `apps/flutter/` | Flutter 3.x + GetX + Dio | iOS + Android |

## Composants clés

### Snowflake ID

Toutes les clés primaires sont générées par `erikwang2013/snowflake-php`, BIGINT non auto-incrémenté, garantissant l'unicité globale distribuée. `service/support/Model::nextId()` réutilise une seule instance Snowflake dans le processus ; les 64 copies de `generateId()` des modèles ont été supprimées (héritage unifié de l'implémentation de la classe de base).

### Hashids

Les ID des requêtes/réponses API sont encodés via `erikwang2013/hashids`, exposant des chaînes hash à l'extérieur.

### Authentification JWT

`erikwang2013/jwt-webman` Bearer Token, validité 7 jours, avec rafraîchissement et liste noire.

### Chiffrement des données

- **Couche API** : `erikwang2013/encryption` chiffrement/déchiffrement des données sensibles
- **Couche DB** : trait `erikwang2013/encryptable` chiffrement/déchiffrement automatique des champs

### Protection de sécurité

- `erikwang2013/security-php` : détection de 31 types d'attaques
- `erikwang2013/poster-php` : vérification aléatoire des opérations sensibles
- Verrouillage de connexion : 5 échecs → verrouillage 15 minutes
- Limite de concurrence : 3 tokens valides maximum

### Documentation API

`hg/apidoc` génère la documentation OpenAPI 3.0, séparée entre back-office et client :

| Application | Adresse | Description |
|------|------|------|
| Back-office | `admin/ GET /api/docs` | API du back-office (JWT+RBAC) |
| Client | `service/ GET /api/docs` | API métier (JWT Bearer) |

Documentation accessible publiquement, importable dans Swagger UI pour une documentation interactive.

### Elasticsearch

`erikwang2013/webman-scout` synchronise automatiquement les modèles vers ES, recherche plein texte.

## Chaîne d'exécution du middleware

### Middleware de service/

```
API publique :  Cors → Security(31 détections) → RateLimit → ApiVersion → Controller
API utilisateur :  Cors → Security → RateLimit → Auth(JWT) → Controller
API technicien :  Cors → Security → RateLimit → ApiVersion → Auth → TechnicianAuth → Controller
```

### Middleware de admin/

```
API publique :  Cors → Security → RateLimit → Controller
API de gestion :  Cors → Security → RateLimit → AdminAuth(JWT) → AdminPermission(RBAC) → OperationLog → Controller
Vérification de santé :  Cors → Security → RateLimit → Controller
```

## Flux de données

### Flux de requête

```
Client → Cors → Security → RateLimit → Auth(JWT) → [TechnicianAuth] → Controller
    → Model (chiffrement/déchiffrement encryptable) → BaseController (encodage hashids) → Réponse JSON
```

### Flux de réservation

```
Parcourir les services → choisir boutique/technicien/créneau → soumettre la commande → verrou Redis du technicien 3 min
    → paiement WeChat → notification au technicien → début du service → fin du service → avis → commande terminée
```

## 8 sources d'origine des opérations

## Dernières extensions

| Catégorie | Fonctionnalité |
|------|------|
| Temps réel | Push WebSocket / callback de paiement / APNs+FCM |
| Messages | Push de messages d'abonnement (sendSubscribeMessage, 3 scénarios d'événements de commande) |
| Portefeuille | Recharge de solde / paiement par solde / recrédit au remboursement |
| Boutique | Impression Bluetooth / signature électronique / file d'attente |
| Technicien | Évaluation en ligne / vitrine vidéo courte / poste de travail (today/records/start/complete) |
| Communauté | Publications / commentaires / likes / modération |
| Système | Multilingue (zh/en) / annulation automatique des commandes / données de démonstration |

Le champ `source` enregistre l'origine de l'opération : web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### Intégration des services tiers

| Service | Classe | Capacités |
|------|------|------|
| Paiement WeChat | WechatPayService | Commande unifiée / requête / remboursement / retrait vers compte WeChat |
| SMS | SmsService | Double canal Aliyun/Tencent Cloud |
| Cartographie | MapService | AMap/Tencent, géocodage inverse / distance / navigation |
| Messages de modèle | WechatTemplateMessageService | Push commande/remboursement/rappel + messages d'abonnement (sendSubscribeMessage, 3 scénarios d'événements de commande) |
