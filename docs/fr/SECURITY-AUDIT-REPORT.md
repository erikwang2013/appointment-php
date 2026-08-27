# Rapport d'audit de sécurité — Système de réservation (appointment-php)
> **Languages**: [中文](../SECURITY-AUDIT-REPORT.md) · [English](../en/SECURITY-AUDIT-REPORT.md) · [한국어](../ko/SECURITY-AUDIT-REPORT.md) · [Русский](../ru/SECURITY-AUDIT-REPORT.md) · [Deutsch](../de/SECURITY-AUDIT-REPORT.md) · [Español](../es/SECURITY-AUDIT-REPORT.md) · [Português](../pt/SECURITY-AUDIT-REPORT.md) · [हिन्दी](../hi/SECURITY-AUDIT-REPORT.md) · [العربية](../ar/SECURITY-AUDIT-REPORT.md) · [বাংলা](../bn/SECURITY-AUDIT-REPORT.md) · [Bahasa Indonesia](../id/SECURITY-AUDIT-REPORT.md) · [日本語](../ja/SECURITY-AUDIT-REPORT.md)

**Date** : 2026-08-04
**Périmètre de l'audit** : service (système de réservation de services), admin (back-office ouvert)
**Version PHP** : 8.3.7
**Framework** : webman v2

---

## I. Résultats des tests

| Élément testé | Service | Admin |
|--------|---------|-------|
| Vérification de syntaxe PHP (complète) | Réussie | Réussie |
| Tests unitaires PHPUnit | 59 tests / 165 assertions PASS | 59 tests / 165 assertions PASS |
| Analyse statique PHPStan | Non installé (délai de téléchargement des dev deps) | Non installé (délai de téléchargement des dev deps) |

---

## II. Vue d'ensemble des couches de protection de sécurité

```
Requête → Nginx (en-têtes de sécurité + protection des fichiers sensibles) → Cors (CORS + en-têtes de sécurité) → SecurityMiddleware (31 types de détection d'attaques) → RateLimit (fenêtre glissante Redis) → Auth (JWT) → Controller
                                                                                                   ↓
                                                                                    Liste noire IP (5 attaques/60s → bannissement 15min)
                                                                                    Verrouillage de compte (5 échecs/15min → verrouillage 15min)
```

---

## III. Problèmes corrigés

### 3.1 CORS de Service sans en-têtes de réponse de sécurité → corrigé
**Fichier** : `service/app/middleware/Cors.php`
- Ajout de 6 en-têtes de sécurité : X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP, X-Permitted-Cross-Domain-Policies
- Désormais cohérent avec la configuration des en-têtes de sécurité d'admin

### 3.2 Service sans verrouillage après échecs de connexion → corrigé
**Fichier** : `service/app/api/v1/controller/AuthController.php`
- Les méthodes `login()` et `loginByCode()` ajoutent un compteur d'échecs Redis
- 5 échecs/15 minutes verrouillé → HTTP 429
- Dégradation gracieuse en cas de panne Redis

### 3.3 Origin CORS codé en dur `*` → corrigé
**Fichiers** : `service/app/middleware/Cors.php`, `admin/app/middleware/Cors.php`
- Remplacé par une configuration via la variable d'environnement `CORS_ALLOW_ORIGIN`
- Laissé vide → `*` par défaut (rétrocompatibilité)

### 3.4 Service sans dépendance security-php → corrigé
**Opérations** :
- Ajout de `allow-plugins.erikwang2013/security-php` au composer.json
- Exécution de `composer install --no-dev` pour installer la dépendance
- Fichier de configuration publié dans `config/plugin/erikwang2013/security-php/app.php`
- Détecteur d'Origin CSRF (`csrf_origin`) activé (mode block)

### 3.5 Nginx de Service sans Permissions-Policy → corrigé
**Fichier** : `service/docs/nginx.conf`
- Ajout de `add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;`

### 3.6 Complément de la configuration de l'écosystème → corrigé
- Ajout de `CORS_ALLOW_ORIGIN` à `service/.env.example` et `admin/.env.example`
- Ajout de `CORS_ALLOW_ORIGIN` à `service/.env.docker` et `admin/.env.docker`

---

## IV. Liste complète des protections de sécurité actuelles

### 4.1 Couche WAF — 31 détecteurs d'attaques

| Mode | Détecteurs | Nombre |
|------|--------|------|
| **block** (interception 403) | XSS, injection SQL, injection de commandes, traversée de chemins, téléchargement de fichiers, SSRF, XXE, désérialisation, injection LDAP, injection d'en-têtes de courriel, Open Redirect, attaques JWT, attaques par en-tête Host, Request Smuggling, injection GraphQL, injection XPATH, JNDI/Log4Shell, injection SSI, injection CSV, fuite de données, Prototype Pollution, détournement WebSocket, contournement CORS, DNS Rebinding, validation des méthodes HTTP, taille du corps de requête (10MB), liste blanche Content-Type, Origin CSRF | 28 |
| **log** (enregistrement uniquement) | Injection d'en-têtes de réponse, SSTI, injection NoSQL | 3 |

### 4.2 Authentification et autorisation

| Mécanisme | Service | Admin |
|------|---------|-------|
| Authentification JWT | Middleware Auth | Middleware AdminAuth |
| Liste noire JWT | Ajout à la déconnexion | Ajout à la déconnexion + dépassement de sessions |
| Permissions RBAC | — | Format method.path, cache Redis 60s |
| Verrouillage de compte | 5 fois/15 minutes (Redis) | 5 fois/15 minutes (Redis) |
| Limite de sessions concurrentes | — | 3 Token maximum |
| Hachage des mots de passe | bcrypt | bcrypt |

### 4.3 Limitation de débit

| Route | Service | Admin |
|------|---------|-------|
| Par défaut | 60 fois/minute/IP | 60 fois/minute/IP |
| Connexion | 10 fois/minute | — |
| Inscription | 5 fois/minute | — |
| SMS/mot de passe oublié | 5 fois/minute | — |

### 4.4 Sécurité des données

| Mesure | Service | Admin |
|------|---------|-------|
| Chiffrement des champs de base de données | AES-256-CBC (6 modèles) | AES-256-CBC |
| Chiffrement du transport API | AES-256-CBC | AES-256-CBC |
| Obscurcissement des ID (Hashids) | Tous les ID exposés | Tous les ID exposés |
| ID Snowflake | BIGINT non auto-incrémenté | BIGINT non auto-incrémenté |
| Masquage des champs sensibles | Masquage des numéros de téléphone | Masquage des données exportées |

---

## V. Recommandations en attente

### 5.1 Recommandation : stockage de security-php en Redis (environnement de production)
**Actuel** : les deux services utilisent un stockage de type `file` (fichier JSON local)
**Risque** : en déploiement multi-instances, la liste noire IP n'est pas partagée, un attaquant peut contourner en changeant d'instance
**Recommandation** : passer `storage.type` à `redis` en production

### 5.2 Recommandation : attributs de sécurité du cookie de session
**Actuel** : `secure: false`, `same_site: ''`
**Risque** : le cookie peut être transmis via HTTP, protection CSRF affaiblie
**Recommandation** : définir `secure: true`, `same_site: 'Lax'` en production

### 5.3 Recommandation : installer la dépendance de développement PHPStan
**Actuel** : `composer install --dev` échoue par délai réseau
**Opérations** : `composer install --dev` ou `composer require --dev phpstan/phpstan`

### 5.4 Rappel : modifier toutes les clés avant le déploiement en production
Les clés de remplacement de `.env.docker` doivent être remplacées par des valeurs générées aléatoirement avant le déploiement en production :
- `JWT_SECRET_KEY`
- `HASHIDS_SALT`, `HASHIDS_ALT_SALT`
- `ENCRYPTION_KEY`, `ENCRYPTABLE_KEY`
- `DB_PASSWORD`

---

## VI. Documents produits

| Document | Chemin |
|------|------|
| Architecture de sécurité de Service | `service/docs/SECURITY.md` |
| Architecture de sécurité d'Admin | `admin/docs/SECURITY.md` |
| Le présent rapport d'audit | `docs/SECURITY-AUDIT-REPORT.md` |

---

## VII. Conclusion de l'audit

**Évaluation globale de la protection de sécurité : bonne**

- Couches de défense en profondeur complètes (Nginx → WAF → Rate Limit → Auth → RBAC)
- Couverture globale des 31 détecteurs d'attaques, 28 en mode interception
- Protection d'authentification multicouche JWT + liste noire + verrouillage de compte + liste noire IP
- Chiffrement AES-256-CBC de la couche de données + obscurcissement Hashids
- Trois problèmes clés corrigés côté service : en-têtes de réponse de sécurité manquants, verrouillage de connexion manquant, paquet WAF manquant
- Les recommandations concernent l'optimisation de la configuration de production, pas des vulnérabilités de sécurité

---

## VIII. Passe de correctifs du 2026-08-26 (durcissement de la sécurité)

| Élément | Contenu du correctif |
|----|---------|
| Anti-falsification des commandes | Les prix des articles de commande d'OrderController::store() se réfèrent toujours aux enregistrements de la base (service→appointment_service, product→appointment_product), les prix du client ne participent pas au calcul ; target_type inconnu 422 ; target_id doit être en hashid (un id brut décodé à 0 → 422 « produit inexistant ou retiré ») ; prix des achats groupés/flash également déterminés par la base |
| Déduction de stock flash unifiée | Le stock est déduit de manière unifiée par verrou de ligne dans la transaction `/api/order store()` ; SeckillController::buy ne pré-déduit plus le stock (verrou d'activité Redis + idempotence client_token conservés) ; appeler directement /api/order avec seckill_id déduit également le stock |
| Retraits des techniciens | À la demande, le solde déduit immédiatement la réserve en cours (pending/approved) ; avant l'approbation et le transfert, re-vérification settled−withdrawn−en cours ≥ montant du retrait ; les approbations concurrentes ne provoquent pas de double versement |
| Callbacks de paiement | Comparaison stricte de total_fee du callback WeChat avec le montant dû de la commande, refus en cas d'écart ; masquage des journaux du callback Alipay (sans buyer_id/seller_id, etc.) |
| Protection de /install | À la réussite de l'installation, écriture de .install.lock, double validation de l'interface install (verrou de fichier + isInstalled) ; .install.lock ajouté au .gitignore |
| Convergence des dépendances | webman-scout unifié en 2.0.5 (service/admin) ; ajout de opensearch-project/opensearch-php ^2.6 ; verrouillage précis des versions dompdf/security-php/webman-database (suppression du joker "*") |
| Ingénierie | Suppression de service/app/common/StorageService.php (code mort) ; ajout de TechnicianWithdrawalService/WechatPayService dans admin/app/common/ (admin déployé indépendamment sans dépendre du code de service) ; phpstan.neon des deux applications réparés et exécutables (php -d memory_limit=2G) |
