# Conception de l'architecture
> **Languages**: [中文](../ARCHITECTURE-DESIGN.md) · [English](../en/ARCHITECTURE-DESIGN.md) · [한국어](../ko/ARCHITECTURE-DESIGN.md) · [Русский](../ru/ARCHITECTURE-DESIGN.md) · [Deutsch](../de/ARCHITECTURE-DESIGN.md) · [Español](../es/ARCHITECTURE-DESIGN.md) · [Português](../pt/ARCHITECTURE-DESIGN.md) · [हिन्दी](../hi/ARCHITECTURE-DESIGN.md) · [العربية](../ar/ARCHITECTURE-DESIGN.md) · [বাংলা](../bn/ARCHITECTURE-DESIGN.md) · [Bahasa Indonesia](../id/ARCHITECTURE-DESIGN.md) · [日本語](../ja/ARCHITECTURE-DESIGN.md)

## Architecture par couches

```
┌─────────────────────────────────────────┐
│            Couche de présentation        │
│  Mini-programme WeChat / Flutter APP / Flutter Web│
├─────────────────────────────────────────┤
│            Couche de routage             │
│  config/route.php — groupement des routes + liaison middleware │
├─────────────────────────────────────────┤
│          Couche de middleware            │
│  Cors → Security → RateLimit → Auth     │
│  → TechnicianAuth → OperationLog        │
├─────────────────────────────────────────┤
│         Couche de contrôleurs            │
│  BaseController → Contrôleurs métier     │
├─────────────────────────────────────────┤
│           Couche de services             │
│  common/ — Snowflake/Hashids/Encryption  │
├─────────────────────────────────────────┤
│            Couche de modèles             │
│  Eloquent ORM + Encryptable + Scout      │
├─────────────────────────────────────────┤
│            Couche de données             │
│  MySQL / Redis / Elasticsearch           │
└─────────────────────────────────────────┘
```

## Conception du middleware

### Chaîne d'exécution

```
Cors → Security(31 types d'attaques) → RateLimit → Auth(JWT+état utilisateur)
    → [TechnicianAuth(identité technicien)] → [AdminPermission(RBAC)] → [OperationLog(8 sources)]
    → Controller
```

### Responsabilités du middleware

| Middleware | Portée | Fonction |
|--------|--------|------|
| Cors | Globale | Pré-vol OPTIONS + en-têtes de réponse CORS |
| Security | Globale | erikwang2013/security-php, 31 types d'attaques |
| RateLimit | Globale | Fenêtre glissante Redis + Lua atomique |
| Auth | Groupe de routes | Analyse JWT + contrôle d'existence/état de l'utilisateur |
| TechnicianAuth | Groupe de routes | Requête du profil technicien + contrôle du statut approved |
| AdminAuth | Groupe de routes | Authentification JWT côté admin + liste noire |
| AdminPermission | Groupe de routes | Vérification RBAC, cache Redis 60 s |
| OperationLog | Groupe de routes | Journal d'opérations + détection automatique des 8 sources |

### Stratégie de limitation de débit

| Interface | Limite |
|------|------|
| Par défaut | 60/min/IP |
| Connexion | 10/min |
| Inscription | 5/min |
| Code de vérification | 1/60 s/téléphone |

## Principes de conception de la base de données

### Stratégie des clés primaires

- Toutes les clés primaires : BIGINT UNSIGNED NOT NULL, non auto-incrémentées
- Générées au niveau application par `erikwang2013/snowflake-php`
- Model : `$incrementing = false`, `$keyType = 'string'`

### Préfixe de table

Préfixe unifié `erik_`, configuré dans `config/database.php`. Le Model écrit le nom de table brut, l'ORM ajoute le préfixe automatiquement.

### Chiffrement des champs sensibles

Utilisation du trait `erikwang2013/encryptable` :

```php
use Erikwang2013\Encryptable\Encryptable;

class User extends Model
{
    use Encryptable;
    protected array $encryptable = [
        'phone', 'wx_openid', 'wx_unionid', 'real_name',
    ];
}
```

La longueur VARCHAR des champs chiffrés est fixée à 500 (dilatation des données chiffrées).

### Suppression douce et horodatage

- Eloquent SoftDeletes : `deleted_at` DATETIME DEFAULT NULL
- Toutes les tables contiennent `created_at` + `updated_at`

## Mécanisme de chiffrement des ID API

### Requête : decodeIds()

Le frontend envoie des ID encodés hashids → le contrôleur appelle `$this->decodeIds($request->all())` pour décoder.

### Réponse : encodeIds()

Les ID des résultats de requête DB → `BaseController::success()` appelle automatiquement `encodeIds()` → renvoie des chaînes hashids.

### Règles

Traitement récursif des champs dont la clé est `id` ou se termine par `_id` dans les tableaux.

## Conception de la sécurité

### Défense en profondeur

```
WAF → Cors → Security(31 types) → RateLimit → Auth(JWT+état)
    → [Vérification d'identité] → [RBAC] → Controller(Model chiffré) → Réponse
```

### Sécurité de l'authentification

- Mot de passe : hachage bcrypt
- JWT : 7 jours + rafraîchissement + liste noire
- Verrouillage : 5 échecs → 15 minutes
- Concurrence : 3 tokens maximum

### Sécurité des données

- Couche API : erikwang2013/encryption
- Couche DB : trait erikwang2013/encryptable
- Journaux : aucune donnée sensible dans les journaux

### Sécurité des opérations

- erikwang2013/poster-php : vérification avant suppression/modération/retrait
- Middleware Security : détection XSS / injection SQL / CSRF / traversée de chemin

## Intégration Elasticsearch

`erikwang2013/webman-scout` synchronise automatiquement les modèles vers ES :

```php
use Erikwang2013\WebmanScout\Searchable;

class Service extends Model
{
    use Searchable;
    public function searchableAs(): string { return 'erik_services'; }
}
```

## Export Excel/PDF

- Excel : PhpSpreadsheet, anonymisation automatique des champs sensibles
- PDF : export de visualisation du tableau de bord

## Détection des 8 sources

OperationLog analyse le User-Agent :

```
iPad → iPadOS / Mac → macOS / Windows → Windows
Linux → Linux / iPhone → ios / Android → android
HarmonyOS → harmonyOS / autre → web
```


## Tests TDD

| Élément | Nombre de tests | État |
|------|--------|------|
| admin/ | 60 | ✅ Réussi |
| service/ | 21 | ✅ Réussi |
| Total | 81 | ✅ |

Couverture : règles de remboursement / états de commande / Hashids / système de file d'attente / chiffrement / code de vérification
