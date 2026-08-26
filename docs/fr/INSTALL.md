# Système de réservation de services — Guide d'installation

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## Prérequis

| Composant | Version minimale | Description |
|------|----------|------|
| PHP | 8.3+ | Extensions : bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | Préfixe de table `erik_`, jeu de caractères utf8mb4 |
| Redis | 6.0+ | Cache / limitation de débit / Session / stockage des codes de vérification |
| Composer | 2.x | Gestion des dépendances PHP |
| Elasticsearch | 8.x (facultatif) | Recherche plein texte, son absence n'affecte pas les fonctions clés |

---

## I. Assistant d'installation Web (recommandé)

Après le démarrage du back-office, accédez à `/install` dans le navigateur pour lancer l'assistant d'installation en une étape :

```bash
# 1. Installation des dépendances et démarrage
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # port par défaut 8787
```

Ouvrez `http://localhost:8787/install` dans le navigateur, 4 étapes :

1. **Vérification de l'environnement** — détection automatique de la version PHP, des extensions requises, des permissions de fichiers
2. **Configuration de la base de données** — renseigner les informations de connexion MySQL, tester la connexion
3. **Compte administrateur** — définir le nom de l'application, le nom d'utilisateur et le mot de passe de l'administrateur
4. **Exécution de l'installation** — import SQL automatique → création de l'administrateur → écriture de la configuration .env

Après l'installation, connectez-vous avec le nom d'utilisateur et le mot de passe définis. En cas de succès, un fichier `.install.lock` est écrit et l'interface `/install` vérifie deux fois (verrou de fichier + isInstalled) pour éviter la réinstallation ; `.install.lock` est ajouté au `.gitignore`. En production, il est recommandé de supprimer la route `/install` de `admin/config/route.php`.

---

## II. Installation manuelle

### 2.1 Cloner le projet

```bash
git clone <repo-url> appointment-php
cd appointment-php
```

### 1.2 Installer les dépendances PHP

```bash
# Service d'API métier
cd service/
cp .env.example .env
composer install --no-dev --optimize-autoloader

# Back-office
cd ../admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

### 1.3 Configurer les variables d'environnement

Modifier `service/.env` (API métier) et `admin/.env` (back-office), renseigner les configurations clés suivantes :

```bash
# Connexion base de données
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment          # service utilise appointment, admin utilise open_admin
DB_USERNAME=root
DB_PASSWORD=your-password

# Connexion Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Clé JWT — en production, remplacez impérativement par une chaîne aléatoire de 64 caractères
JWT_SECRET_KEY=your-64-char-random-string

# Clés de chiffrement — à modifier impérativement en production
ENCRYPTION_KEY=your-32-byte-key
ENCRYPTABLE_KEY=your-32-byte-key

# Sel Hashids — à modifier impérativement en production
HASHIDS_SALT=your-random-salt

# Mode débogage — doit être false en production
APP_DEBUG=false
```

> Description complète des variables dans `service/.env.example` et `admin/.env.example`.

### 1.4 Créer la base de données et importer

```bash
# Création des bases (service et admin peuvent partager la même base ou en utiliser deux distinctes)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import du script d'installation unifié (54+ tables + données de permissions + données de démo)
mysql -u root -p appointment < ../install.sql
mysql -u root -p open_admin < ../install.sql
```

> `install.sql` est la fusion de tous les fichiers de migration, 2723 lignes, incluant toutes les structures de tables et données de démonstration du back-office et du service métier. Exécution unique pour une installation neuve ; une ré-exécution sur une base existante s'interrompra sur des conflits de clés/colonnes — pour une mise à niveau, sauvegardez d'abord ou traitez les conflits manuellement.

### 1.5 Démarrer les services

```bash
# Démarrage du service d'API métier (port par défaut 8787)
cd service/
php start.php start -d

# Démarrage du back-office (port par défaut 8787)
cd ../admin/
php start.php start -d
```

### 1.6 Vérifier l'installation

```bash
# API métier
curl http://localhost:8787/api/common/config

# Vérification de santé du back-office
curl http://localhost:8787/health

# Connexion au back-office (compte par défaut ci-dessous)
curl -X POST http://localhost:8787/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 1.7 Compte par défaut

| Rôle | Nom d'utilisateur | Mot de passe | Description |
|------|--------|------|------|
| Super administrateur | `admin` | `admin123` | Dispose de toutes les permissions |

> Modifiez le mot de passe immédiatement après la première connexion.

---

## III. Déploiement Docker

### 2.1 Service d'API métier

```bash
cd service/
cp .env.docker .env
# Modifier .env : clés et mots de passe
docker-compose up -d
```

Orchestration : nginx (80/443) + app (8787) + mysql (3306) + redis (6379) + elasticsearch (9200)

### 2.2 Back-office

```bash
cd admin/
cp .env.docker .env
docker-compose up -d
```

### 2.3 Import de la base en environnement Docker

```bash
# Copier install.sql dans le conteneur puis l'exécuter
docker cp ../install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## IV. Vue d'ensemble de la structure de la base

| Domaine | Tables | Tables clés |
|----|------|--------|
| Back-office | 8 | `erik_admin_user`, `erik_admin_role`, `erik_admin_permission`, `erik_operation_log` |
| Utilisateurs | 4 | `erik_user`, `erik_user_address`, `erik_user_favorite`, `erik_user_device` |
| Techniciens | 8 | `erik_technician_profile`, `erik_technician_schedule`, `erik_technician_earning`, `erik_technician_withdrawal`, `erik_technician_tier_config` |
| Services | 4 | `erik_service_category`, `erik_service`, `erik_service_package`, `erik_service_record` |
| Commandes | 5 | `erik_order`, `erik_order_item`, `erik_order_payment`, `erik_order_refund`, `erik_order_review` |
| Marketing | 8 | `erik_coupon`, `erik_member_card`, `erik_gift_card`, `erik_user_points`, `erik_promotion` |
| File d'attente | 1 | `erik_queue_number` |
| Contenu | 5 | `erik_banner`, `erik_announcement`, `erik_faq`, `erik_feedback`, `erik_platform_agreement` |
| Communauté | 3 | `erik_post`, `erik_comment`, `erik_moment` |
| Boutiques | 1 | `erik_store` |
| Formation | 2 | `erik_training_course`, `erik_training_progress` |
| Examens | 3 | `erik_exam`, `erik_exam_question`, `erik_exam_attempt` |
| Système | 3 | `erik_system_config`, `erik_notification`, `erik_signature` |
| **Total** | **55** | |

Toutes les tables utilisent le préfixe `erik_`, la clé primaire `id` est BIGINT non auto-incrémentée (générée au niveau application par snowflake-php).

---

## V. Exécution des tests

```bash
# Tests de l'API métier (21 tests)
cd service/
php vendor/bin/phpunit

# Tests du back-office (59 tests)
cd admin/
php vendor/bin/phpunit

# Analyse statique
php vendor/bin/phpstan analyse --level=5 app/

# Vérification du style de code
php vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## VI. Configuration des services tiers

Dans « Configuration du système » du back-office, renseignez les groupes suivants :

| Groupe de configuration | Usage | Requis |
|--------|------|------|
| `wechat_pay` | Numéro de marchand WeChat Pay / clé API / certificat | Nécessaire pour le paiement |
| `wechat_app` | AppID / AppSecret du mini-programme WeChat | Nécessaire pour la connexion WeChat |
| `sms` | Fournisseur SMS (aliyun/tencent) + signature/modèle | Nécessaire pour les codes de vérification SMS |
| `map_service` | Service cartographique (amap/tencent) + API Key | Nécessaire pour les fonctions LBS |
| `storage` | Stockage objet (oss/cos) + AccessKey/Endpoint | Nécessaire pour le téléchargement de fichiers |

---

## VII. Questions fréquentes

**Q : Erreur au démarrage `Class 'support\Model' not found`**
R : Exécuter `composer dump-autoload`.

**Q : Échec de connexion à la base `SQLSTATE[HY000] [2002]`**
R : Vérifier la configuration `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` dans `.env`.

**Q : Erreur d'encodage lors de l'import SQL**
R : Utiliser `mysql -u root -p --default-character-set=utf8mb4 < ../install.sql`

**Q : Échec de connexion Redis**
R : Vérifier que Redis est démarré, contrôler la configuration `REDIS_HOST`/`REDIS_PORT`.

**Q : Port déjà occupé**
R : Modifier le port d'écoute dans `config/server.php` (`listen`).

**Q : Le code de vérification ne s'affiche pas**
R : Vérifier que l'extension GD est installée et que `POSTER_CAPTCHA_STORAGE` est correctement configuré (local : `file`, production : `redis`).

**Q : Elasticsearch ne fonctionne pas**
R : ES est un composant facultatif ; vérifier `SCOUT_HOSTS` et que le service ES est démarré.

---

## VIII. Structure des répertoires

```
appointment-php/
├── admin/                    # Back-office (webman v2)
│   ├── app/                  # Contrôleurs / modèles / middleware
│   ├── config/               # Configuration routes / base de données / middleware
│   ├── database/             # Scripts de sauvegarde (schéma + données de démo unifiés dans docs/install.sql)
│   ├── tests/                # Tests PHPUnit (59 tests)
│   ├── .env.example          # Modèle de variables d'environnement
│   ├── .env.docker           # Variables d'environnement Docker
│   ├── Dockerfile            # Fichier de construction Docker
│   └── docker-compose.yml    # Orchestration Docker
├── service/                  # Service d'API métier (webman v2)
│   ├── app/                  # Contrôleurs / modèles / middleware
│   ├── config/               # Configuration sécurité / routes / base de données
│   ├── seed.php              # Exécuteur des données de démonstration (lit le segment de démo de docs/install.sql)
│   ├── tests/                # Tests PHPUnit (21 tests)
│   ├── .env.example          # Modèle de variables d'environnement
│   ├── .env.docker           # Variables d'environnement Docker
│   ├── Dockerfile            # Fichier de construction Docker
│   └── docker-compose.yml    # Orchestration Docker
├── docs/                     # Documentation
│   ├── INSTALL.md            # Le présent guide d'installation
│   ├── install.sql           # Script d'installation unifié (2723 lignes)
│   ├── ARCHITECTURE.md       # Documentation de conception de l'architecture
│   ├── API.md                # Documentation de référence API
│   └── AUDIT-REPORT.md       # Rapport d'audit
└── .github/workflows/        # Pipeline CI/CD
    └── ci.yml
```
