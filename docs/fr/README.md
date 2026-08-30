> Traduction française · Original : [中文](../../README.md)

# Système de réservation de services
> **Languages**: [中文](../README.md) · [English](../en/README.md) · [한국어](../ko/README.md) · [Русский](../ru/README.md) · [Deutsch](../de/README.md) · [Español](../es/README.md) · [Português](../pt/README.md) · [हिन्दी](../hi/README.md) · [العربية](../ar/README.md) · [বাংলা](../bn/README.md) · [Bahasa Indonesia](../id/README.md) · [日本語](../ja/README.md)

Plateforme de gestion des réservations multi-appareils : mini-programme WeChat + application Flutter + application HarmonyOS côté utilisateur (changement d'identité avec le même compte), et back-office PC.

> **État du projet** : Tout est terminé ✅ | 143 contrôleurs (service 69 / admin 74) | 87 modèles | 722 tests (service 558 / admin 164) | 95 tables | 388 routes (service 227 / admin 161)

## Présentation du projet

<img src="diagrams/mascot.svg" alt="Mascotte du système de réservation — Petit Lapin des rendez-vous (animation SVG)" width="200" align="right">

**Le système de réservation de services** est une plateforme de gestion des réservations multi-appareils destinée au secteur des services à la personne : côté utilisateur, elle couvre le **mini-programme WeChat, l'application Flutter et l'application HarmonyOS** — trois terminaux, avec changement d'identité fluide via le même compte — complétés par un **back-office PC**, pour une boucle numérique complète : « réservation utilisateur → acceptation par le technicien → gestion en back-office ». Réservation en boutique, prestation du technicien, marketing de fidélité ou règlement financier : une seule solution pour tout.

**Une expérience de réservation tout-en-un**

Expérience identique sur les trois terminaux : choix de créneau par calendrier, déduction par bon de réduction / carte à forfait / points, ventes flash et offres groupées, paiement WeChat ou par solde, statut de commande traçable de bout en bout — modification de rendez-vous, annulation, remboursement, après-vente et facture électronique entièrement en ligne ; côté technicien : poste de travail, pointage, planification par lots, vérification des prestations et approbation des retraits, pour une efficacité opérationnelle immédiate.

**Croissance marketing de bout en bout**

Plus d'une dizaine d'outils marketing intégrés : promotions par réduction directe, ventes flash, offres groupées, transfert de bons, boutique de points et roue de la chance, cartes de fidélité / avantages selon le niveau de croissance, commission à deux niveaux, récompense pour le client fidèle, etc., avec notifications par abonnement de messages et push applicatif pour attirer, fidéliser et faire revenir les clients.

**Sécurité et conformité de niveau entreprise**

Composants de sécurité maison : authentification JWT, obscurcissement des ID, détection de 31 types d'attaques, double chiffrement des données sensibles, validation des prix côté serveur, comparaison stricte des callbacks de paiement et idempotence anti-doublons ; en outre : répartition officielle WeChat, export des données personnelles et suppression de compte, pour répondre aux exigences de conformité.

**Une base technique éprouvée**

Basé sur PHP 8.3 + webman, framework haute performance en mémoire résidente, avec MySQL 8.0 + Redis + Elasticsearch ; 95 tables, 388 interfaces, 285 permissions granulaires, 722 tests automatisés tous verts, documentation d'architecture complète en chinois et en anglais et script d'installation en un clic : prêt à l'emploi et facile à faire évoluer.

Que ce soit pour une boutique unique ou une chaîne multi-établissements, le système de réservation de services vous offre une solution intégrée, stable, sécurisée et extensible.

## Structure du projet

```
appointment-php/
├── admin/                     # Back-office (webman v2 + Flutter Web, déploiement autonome :8787)
│   ├── app/                   #   admin (contrôleurs back-office)/api/model/middleware/process/view
│   ├── apps/                  #   Flutter Web back-office / HarmonyOS / WeChat gestion
│   ├── config/                #   configuration routes/base de données/processus/plugins
│   ├── database/              #   scripts de sauvegarde (schéma + données de démo unifiés dans docs/install.sql)
│   ├── tests/                 #   PHPUnit (style d'attribut #[\Test])
│   └── start.php
├── service/                   # Service d'API métier (webman v2, déploiement autonome :8787)
│   ├── app/                   #   modules api/user/technician/order/wallet/marketing/notification etc.
│   ├── config/                #   configuration routes/base de données/processus/paiement etc.
│   ├── support/               #   classe de base Model (generateId)/Request/Response
│   ├── tests/                 #   PHPUnit
│   └── start.php
├── apps/                      # Applications frontend utilisateur
│   ├── wechat/                #   Mini-programme WeChat (natif)
│   ├── flutter/               #   Application Flutter (iOS + Android)
│   └── harmonyos/             #   Application HarmonyOS (natif)
└── docs/                      # Documentation du projet
    ├── API.md / FEATURES.md / STRUCTURE.md / install.sql / README.md ...
    └── diagrams/              #   Schémas d'architecture / de flux (SVG + mermaid)
```

## Démarrage rapide

### Prérequis

- PHP 8.3+
- MySQL 8.0+
- Redis
- Composer

### Assistant d'installation Web (recommandé)

```bash
cd admin/
cp .env.example .env
composer install
php start.php start -d
```

Ouvrez `http://localhost:8787/install` dans le navigateur et suivez les instructions pour renseigner la base de données et le compte administrateur.

### Installation manuelle

```bash
# 1. Installation des dépendances
cd service/ && cp .env.example .env && composer install
cd ../admin/ && cp .env.example .env && composer install

# 2. Import de la base en une commande (95 tables + permissions/config de démo)
mysql -u root -p < docs/install.sql

# 3. Démarrage des services
cd service/ && php start.php start -d   # API métier → :8787
cd ../admin/ && php start.php start -d  # Back-office → :8787
```

### Déploiement Docker

```bash
cd admin/ && cp .env.docker .env && docker-compose up -d
cd ../service/ && cp .env.docker .env && docker-compose up -d
```

## Pile technique

| Couche | Technologie | Description |
|------|------|------|
| Framework backend | webman v2 (PHP 8.3+) | Service HTTP haute performance en mémoire résidente |
| Base de données | MySQL 8.0 | Préfixe de table `appointment_` |
| Cache | Redis | Cache/limitation de débit/Session/file d'attente |
| Recherche | Elasticsearch | Recherche plein texte (via webman-scout) |
| Frontend back-office | Flutter Web | Interface d'administration PC |
| Application utilisateur | Flutter | iOS + Android |
| Mini-programme utilisateur | Mini-programme WeChat natif | WXML/WXSS/JS |
| Application HarmonyOS | HarmonyOS ArkTS | Natif @ohos.net.http |
| Génération d'ID | erikwang2013/snowflake-php | Clé primaire BIGINT non auto-incrémentée |
| Chiffrement d'ID API | erikwang2013/hashids | Masque les ID réels |
| Authentification JWT | erikwang2013/jwt-webman | Bearer Token |
| Chiffrement des données sensibles | erikwang2013/encryption + encryptable | Double chiffrement API + DB |
| Protection de sécurité | erikwang2013/security-php | Détection de 31 types d'attaques |
| Vérification d'opération | erikwang2013/poster-php | Vérification aléatoire des opérations sensibles |
| Drapeaux nationaux | erikwang2013/season | Icônes de drapeaux |
| Synchronisation ES | erikwang2013/webman-scout | Synchronisation automatique des modèles |

## Architecture du système

<img src="diagrams/fr-architecture.svg" alt="fr-architecture.svg" width="100%">

## Processus clés

### Processus de réservation d'un service

<img src="diagrams/fr-appointment-flow.svg" alt="fr-appointment-flow.svg" width="100%">

### Processus de paiement et de remboursement

<img src="diagrams/fr-payment-refund.svg" alt="fr-payment-refund.svg" width="100%">

## Cycle de vie d'une commande

<img src="diagrams/fr-order-lifecycle.svg" alt="fr-order-lifecycle.svg" width="100%">

## Architecture de sécurité

### Défense en profondeur en sept couches

<img src="diagrams/fr-security-defense.svg" alt="fr-security-defense.svg" width="100%">

> Plus de schémas détaillés : [Organigrammes](diagrams/FLOWCHART.md) (retrait technicien / changement d'identité) | [Carte mentale des fonctions](diagrams/FUNCTION-DIAGRAM.md) | [Tous les cycles de vie](diagrams/LIFECYCLE-DIAGRAM.md) | [Architecture de sécurité complète](diagrams/SECURITY-ARCHITECTURE.md)

## Points forts des fonctionnalités (tours 6 à 24)

| Fonctionnalité | Description |
|------|------|
| Portefeuille rechargeable | Tables user_wallet / wallet_recharge / wallet_txn ; solde + historique, recharge par WeChat Pay (callback, préfixe R pour le numéro de commande), paiement de commande par solde (pay_channel=balance), remboursements WeChat/solde recrédités automatiquement sur le solde |
| Interface back-office complète | 20 pages Flutter Web : dashboard/utilisateurs/rôles/config/journaux/vérification/planning/services/techniciens/commandes/bons/cartes de membre/cartes à forfait/annonces/FAQ/retraits/avis/rapports/espace personnel |
| Messages d'abonnement mini-programme | 3 scénarios d'abonnement par commande (paiement réussi / remboursement reçu / vérification réussie) ; idempotence push_sent_at ; repli automatique sur notification interne si le modèle n'est pas configuré |
| Retrait du technicien | Validation en back-office ; approbation en deux niveaux ≥ 500 (gérant → finances) ; machine à états pending → approved → completed (rejected/failed) |
| Boucle de vérification carte à forfait | Mes cartes à forfait : calcul temps réel used_up/expired ; vérification idempotente Redis NX + verrou de ligne pour décompter, création directe d'une commande completed + OrderItem + OrderPayment(pay_type='card') |
| Poste de travail technicien | Tâches du jour / historique / début·fin (verrou de ligne + garde de machine à états + idempotence, notification interne après fin) ; mini-programme tech-work à 3 onglets |
| Déduction par bon | PriceCalculator : applyCoupon lecture seule / consume passage à used au paiement / restoreCouponAndCard restitution idempotente au remboursement ; fixed/percent + seuil min_amount |
| Carte cadeau | Au redeem, type cash : rechargé sur le portefeuille (verrou de ligne anti-double écriture, WalletTxn type='gift_card') ; type gift : simple marquage |
| Système de points | Points de présence ; points de consommation à la vérification floor(paid×1) (idempotence order_id, instantané balance) ; reprise proportionnelle au remboursement ; historique paginé + filtre type/source |
| Gestion des membres | Colonne appointment_user.member_level (migration 000008) ; CRUD complet des cartes de membre en back-office (permissions 365-369) |
| Chaîne de commande mini-programme | Détail du service → confirmation de commande (choix du bon / seuil grisé / estimation côté client) → POST /order → paiement WeChat/solde ; 20 pages au total |
| Boucle d'offre groupée | Join en double → 422 + verrouillage à effectif complet + fermeture paresseuse à l'expiration ; commande de groupe via store avec promotion_id au prix groupé (discount_percent), bons/cartes à forfait/points interdits en superposition ; si le groupe échoue, annulation automatique et libération du verrou technicien (l'ancien canal FLASH_SALE est retiré, la vente flash passe par son propre canal) |
| Poste de travail du gérant | Service /api/store-manager, 4 interfaces (overview/orders/technicians/revenue) avec isolation forcée store_id (403 sans boutique) ; vue d'ensemble en back-office + filtre store_id sur les commandes + page Flutter + permission 372 |
| Commission de parrainage | Après le premier completed du filleul, commission paid_amount × reward_rate (config système, défaut 0.05) versée sur le portefeuille du parrain (WalletTxn referral_reward) ; triple idempotence : verrou de ligne + contrôle de nullité + revérification de la première commande ; détail des gains + consultation back-office (permission 379) |
| Boutique d'échange de points | Deux tables : produits et enregistrements d'échange ; échange avec Redis NX + verrou de ligne anti-surdébit + uk_user_goods (une seule fois par utilisateur) ; trois résultats : coupon / crédit wallet / carte cadeau avec clé ; CRUD back-office + mise en ligne/sous ligne + historique (permissions 373-378) |
| Report de rendez-vous | POST /api/order/reschedule/{id}, changement d'horaire avec le même technicien ; uniquement pending/paid/confirmed et ≥6 h avant le début du service ; order_lock + verrou technicien SETNX(180s) sur le nouveau créneau anti-survente + contrôle de conflit de planning B2 ; enregistrement appointment_order_reschedule + message d'abonnement SCENE_RESCHEDULE |
| Transfert de bon | Code de transfert unique à 8 caractères (repli uk_code, valable 7 jours) ; claim anti-abus : verrou Redis NX + revérification par verrou de ligne anti-double dépense, uk_user_coupon (un seul transfert), un bon transféré ne peut pas être re-transféré, pas d'auto-réclamation ; restauration paresseuse du bon d'origine à l'expiration |
| Expiration des points | expires_at (défaut 365 jours, config points.expiry_days) ; PointsExpiryTimer 60 s scan curseur, écriture de débit négatif type=expire (triple idempotence) + notification interne agrégée ; points expirés non utilisables en déduction/échange |
| Évaluation automatique du niveau technicien | TierRatingService : statistiques temps réel des commandes + moyenne des notes écrites sur le profil, correspondance du plus haut au plus bas selon tier_config ; montée de niveau uniquement (allowDowngrade pour réévaluation manuelle) ; journal appointment_technician_tier_log + notification interne ; consultation back-office (permission 380) |
| Boucle de commande flash | /api/seckill, activités + buy idempotent/anti-concurrence, injection seckill_id réutilisant store() ; stock décrémenté uniquement par verrou de ligne en transaction (prix flash = seckill_price selon la base) ; épuisé → 422 « Tout est épuisé » ; l'annulation ne rend pas le stock ; l'ancien canal promotion flash_sale est retiré |
| Rappel avant début de service | ServiceReminderTimer 60 s scan des commandes confirmed/serving démarrant sous 1 h → message d'abonnement SCENE_REMINDER + notification interne (anti-doublon order_id+type, triple idempotence) ; repli notification interne si modèle non configuré |
| Rappel d'expiration | ExpiryReminderTimer 6 h scan des cartes de membre/bons expirant sous 3 jours → type=card_expiry/coupon_expiry + message d'abonnement SCENE_EXPIRY (order_id pour la provenance, anti-doublon) |
| Réponse du technicien aux avis | POST /api/technician/review/reply/{order_id} : 404 si pas le sien, 422 en double réponse, notification interne à l'utilisateur après réponse ; appointment_order_review complété replied_at ; détail de réponse back-office (permission 381) |
| Notification de recharge reçue | À l'intérieur de la transaction du callback de recharge WeChat : notification interne type='wallet_recharge' (réutilise l'idempotence du callback, même transaction, échec sans bloquer le flux principal) |
| Transfert de solde | POST /api/wallet/transfer, transfert entre utilisateurs : 0,01-1000/opération + plafond journalier 5000 ; verrou Redis NX + verrous de ligne des deux portefeuilles (user_id croissant anti-interblocage) + idempotence client_token 24 h ; double écriture WalletTxn transfer_out/transfer_in avec instantané balance_after ; notification interne au destinataire type='balance_received' |
| Transfert de points | POST /api/user/points/transfer, transfert entre utilisateurs : 1-10000 points + plafond journalier cumulé 10000 ; verrou Redis NX + lockForUpdate sur la dernière écriture des deux comptes (ordre croissant anti-interblocage) + revérification sous verrou ; double écriture consume (émetteur)/earn (destinataire, avec expires_at normalement applicable) ; notification interne au destinataire type='points_received' |
| Avis complémentaire | POST /api/order/review/{order_id}/append : 404 si pas le sien / 422 en double / 422 vide / 422 si non completed ; notification interne technicien type='review_append' ; appointment_order_review ajoute append_content/append_images(JSON)/append_at ; au passage, ajout de la route de soumission d'avis des utilisateurs enregistrés (l'ancien store sans route était inaccessible) et correction de son TypeError latent |
| Suivi logistique côté utilisateur | GET /api/order/logistics/{id} : commandes product personnelles uniquement (404 si pas le sien / pas un produit / non expédié) ; lecture du JSON order.remark (shipping_company/tracking_no/shipped_at, écrit par l'admin à l'expédition) ; numéro de téléphone du destinataire masqué 138****5678 |
| Préférences de notification | Table appointment_user_notify_setting (clé unique uk_user_type, ligne absente = activé par défaut) ; GET/PUT /api/user/notify-settings ; 5 interrupteurs service_reminder/card_expiry/points_expiry/marketing/system (system toujours activé, non désactivable) ; notifySettingEnabled contrôle 3 minuteurs + événements d'abonnement ; si désactivé, notification interne et message d'abonnement sont tous deux ignorés |
| Calendrier de réservation | GET /api/calendar/technician/{id} (vue mensuelle) + /day (vue journalière) : dépliage time_slots en créneaux horaires, exclusion des créneaux déjà réservés appointment_order ; choix visuel du créneau selon le planning du magasin |
| Niveau de croissance utilisateur | appointment_user_growth + appointment_growth_level (Bronze 0 / Argent 100 / Or 500 / Platine 2000 / Diamant 5000) ; présence +10, avis +20, 1 point par yuan dépensé (idempotence naturelle via la revérification existante) ; GET /api/growth (aperçu/historique/niveaux publics) |
| Facture électronique | POST/GET /api/invoices (demande/liste/détail) : uk_order_type(order_id,order_type) anti-double demande, montant fourni par le serveur ; émission/rejet back-office (permissions 382-384) |
| Tickets de support | POST/GET /api/tickets + /{id}/close : soumission/liste/détail/fermeture côté utilisateur ; réponse back-office (permissions 385/387) |
| Parrainage multi-niveaux — commission niveau 2 | Après paiement, commission paid×level2_rate (config 0.02) au parrain du parrain de niveau 1 : verrou de ligne transactionnel + idempotence uk_order_referred anti-double versement ; WalletTxn TYPE_REFERRAL_LEVEL2 ; consultation back-office (permission 386) |
| Avantages du niveau de croissance | Bénéfices GrowthLevel.benefits mis en œuvre : remise discount_rate sur les commandes selon le niveau (commandes standard uniquement ; bon/carte à forfait → remise de niveau en superposition, montant dans discount_amount + remarque traçable, plancher de protection tronqué à 0) ; points de croissance au callback de paiement floor(paid×points_multiplier) (niveau figé au moment du paiement, pas de montée rétroactive) |
| Gestion des intitulés de facture | Bibliothèque appointment_invoice_title : enregistrer/modifier/supprimer/défaut (première entrée auto-défaut, suppression du défaut auto-transféré, définition du défaut transactionnelle) ; sélection title_id lors de la demande, saisie manuelle compatible |
| Satisfaction des tickets | Note 1-5 à la fermeture (422 hors bornes, NULL si absente) ; synthèse back-office : moyenne/distribution 1-5 étoiles/compteurs notés et non notés (permission 388) |
| Modération des images d'avis | Admin ReviewAuditController : liste des avis avec images (filtre JSON_LENGTH + jointure nom utilisateur/technicien), masquer/restaurer (hide uniquement visible, restore uniquement hidden, double contrôle 422) ; avis masqués invisibles dans la liste du technicien (permissions 389-391) |
| Historique de navigation | appointment_browse_history (uk_user_item : les visites répétées ne rafraîchissent que viewed_at) ; enregistrement sur le détail du service (try/catch sans bloquer le flux, non connecté → ignoré) ; liste avec jointure infos du service + hashid ; suppression unitaire/globale réservée au propriétaire |

> Tour 8 — corrections opérationnelles : suppression de 12 Poster::verify fatals latents ; statistiques DashboardController passées aux requêtes Capsule Manager.
>
> Tour 15 : reprise de points (annulation/remboursement restitue les points points_offset, refundOffsetPoints idempotent sur 5 points d'accroche) ; PromotionParticipant passé en constantes entières (corrige la corruption join 1366 en mode strict).
>
> Tour 16 : échange de points (PointsExchangeController, type consume/source=exchange) ; commande groupée (nouvelles colonnes appointment_order promotion_id/participant_id) ; commission de parrainage (ReferralRewardService accroché à WorkController::complete).
>
> Tour 17 : report de rendez-vous (appointment_order_reschedule + interface reschedule) ; transfert de bon (appointment_user_coupon_transfer + transfer/claim/transfers) ; expiration des points (expires_at + processus PointsExpiryTimer) ; évaluation automatique du niveau technicien (TierRatingService + appointment_technician_tier_log, permission 380).
>
> Tour 17 — correction : l'insertion de notification AutoCancelTimer utilise désormais \support\Model::generateId() (l'appel à Snowflake::generate() inexistant faisait échouer silencieusement la notification d'annulation automatique).
>
> Tour 18 : commande flash (store() prend en charge le prix flash flash_sale) ; rappel avant début de service (ServiceReminderTimer + SCENE_REMINDER) ; rappel d'expiration cartes/bons (ExpiryReminderTimer + SCENE_EXPIRY) ; réponse du technicien aux avis (interface review reply + colonne replied_at + permission 381) ; notification de recharge reçue (type='wallet_recharge' dans la transaction du callback).
>
> Tour 19 : transfert de solde (appointment_wallet_transfer + WalletTransferController, double verrou de ligne sous permission + idempotence client_token) ; transfert de points (appointment_user_points_transfer + PointsTransferController, plafond journalier + double écriture) ; avis complémentaire (3 colonnes append sur appointment_order_review + interface append + route store d'inscription restaurée) ; suivi logistique utilisateur (interface logistics + parsing remark JSON + masquage du téléphone) ; préférences de notification (appointment_user_notify_setting + NotifySettingController + contrôle de 3 minuteurs).
>
> Tour 20 : calendrier de réservation (CalendarController vues mensuelle/journalière + exclusion des réservés) ; niveau de croissance utilisateur (appointment_user_growth + appointment_growth_level 5 niveaux + accroches présence/avis/consommation) ; facture électronique (appointment_invoice + uk_order_type anti-double + émission/rejet back-office, permissions 382-384) ; tickets de support (appointment_ticket soumission/liste/détail/fermeture + réponse back-office, permissions 385/387) ; parrainage niveau 2 (payLevel2Reward verrou de ligne transactionnel + idempotence uk_order_referred, permission 386).
>
> Tour 21 : avantages du niveau de croissance (remise discount_rate à la commande + multiplicateur de points points_multiplier au paiement, bénéfices de 5 niveaux en seed de migration) ; intitulés de facture (appointment_invoice_title + liaison title_id) ; satisfaction des tickets (note rating/rated_at à la fermeture + statistiques admin, permission 388) ; modération des images d'avis (ReviewAuditController masquer/restaurer, permissions 389-391) ; historique de navigation (appointment_browse_history + accroche au détail + liste/suppression).
>
> Tour 22 : promotion par réduction directe (appointment_full_reduction auto-déduction + contrôle de seuil, permissions 396-400) ; export calendrier ICS (RFC5545, mes réservations) ; pointage technicien (appointment_technician_attendance, pointage entrée/sortie + marquage retard + statistiques admin, permissions 392-393) ; push applicatif (abstraction pilotée par config + 5 points d'événement, appointment_push_log) ; répartition officielle WeChat (appointment_profit_sharing_log piloté par config + repli, permission 394) ; conformité vie privée (export des données + suppression de compte, machine à états 72 h close_status).
>
> Tour 23 : dossier santé utilisateur (appointment_user_health_profile) ; mot de passe de paiement du portefeuille (appointment_user_wallet pay_password, réglage/vérification) ; planification par lots technicien (import batch + détection de chevauchement) ; chronologie des statuts de commande (appointment_order_status_log, 8 statuts tracés + affichage utilisateur/back-office) ; roue de la chance à points (appointment_lucky_wheel + appointment_wheel_record, tirage pondéré, permissions 401-406) ; durée de validité des points (config points.expiry_days + nouvelles écritures earn avec expires_at).
>
> Tour 24 : mode invité (/api/guest/* navigation en lecture seule sans connexion + cache Redis) ; vente flash (appointment_seckill_activity + verrou de ligne Redis NX + injection appointment_order.seckill_id, permissions 407-411/420) ; gestion des versions APP et détection de mise à jour (appointment_app_version + /api/app/version, permissions 416-419) ; récompense du client fidèle (bonus de 2e consommation sous 30 jours, type=return_customer, permissions 412-414) ; export CSV du planning (BOM UTF-8 + détail des créneaux, permission 415).
>
> Renforcement de sécurité 2026-08-26 : les prix des articles de commande proviennent toujours de la base (prix client non fiable, target_type inconnu → 422, target_id doit être un hashid) ; prix groupé/flash également selon la base ; le stock flash est décrémenté uniquement dans la transaction de /api/order store() (SeckillController::buy ne prélève plus, conserve le verrou d'activité Redis + idempotence client_token) ; au retrait technicien, réserve en transit à la demande, re-vérification avant transfert, approbation concurrente anti-double versement ; le total_fee du callback WeChat Pay est strictement comparé au montant dû, journaux de callback Alipay anonymisés ; /install écrit .install.lock après succès, double contrôle anti-réinstallation ; convergence des versions de dépendances (webman-scout 2.0.5 / opensearch-php ^2.6 / dompdf, security-php, webman-database verrouillés précisément) ; phpstan.neon des deux applications réparés et exécutables (php -d memory_limit=2G).

## Navigation dans la documentation

| Document | Description |
|------|------|
| [Architecture](ARCHITECTURE.md) | Architecture du système, relations entre les trois applications, composants techniques, flux de données |
| [Fonctionnalités](FEATURES.md) | Liste complète des fonctions : utilisateur / technicien / back-office |
| [Conception de l'architecture](ARCHITECTURE-DESIGN.md) | Conception par couches, chaîne de middleware, conception de la base de données, conception de la sécurité |
| [Conception des fonctionnalités](FEATURE-DESIGN.md) | Processus métier clés, règles métier, machines à états, règles de remboursement |
| [Documentation API](API.md) | API métier + API back-office, exemples de requêtes/réponses + endpoints OpenAPI |
| [Installation](INSTALL.md) | Prérequis, déploiement Docker, variables d'environnement, configuration des tiers, FAQ |
| [Utilisation](USAGE.md) | Configuration du back-office, opérations utilisateur/technicien, règles de remboursement (interfaces API dans API.md) |
| [Structure du projet](STRUCTURE.md) | Arborescence complète, chaîne d'exécution du middleware, liste des tables |
| [Rapport de test](TEST-REPORT.md) | Audit de couverture des tests (558 cas / 2508 assertions) |
| [Spécifications de conception](specs/2026-05-26-appointment-system-design.md) | Spécifications du système |
| [Plan d'implémentation](plans/2026-05-26-appointment-system-plan.md) | Plan d'implémentation par phases |

## Soutenir le projet / Support

Si ce projet vous aide, votre soutien est le bienvenu ! Merci pour votre encouragement :heart:

If this project helps you, your support is welcome and appreciated!

<table>
  <tr>
    <td align="center" width="50%">
      <img src="../weixinpay.png" alt="微信支付 / WeChat Pay" width="130" height="130"><br>
      <b>微信支付</b><br>WeChat Pay
    </td>
    <td align="center" width="50%">
      <img src="../alipay.png" alt="支付宝 / Alipay" width="130" height="130"><br>
      <b>支付宝</b><br>Alipay
    </td>
  </tr>
</table>

### Transfert bancaire international / Global Bank Transfer

Soutien possible par virement bancaire international (dollars de Hong Kong / yuan / dollars américains / autres devises), merci pour votre générosité :heart:

Global bank transfer donations are welcome (HKD / CNY / USD / other currencies). Thank you for your generosity!

| Élément | Détails |
|-----------|-------------|
| Bénéficiaire / Beneficiary Name | WANG KEXUN |
| Numéro de compte / Account Number | 881015918251 |
| Banque | ZA Bank Limited (Code SWIFT : AABLHKHHXXX, Code banque : 387) |
| Adresse de la banque / Bank Address | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **Banque intermédiaire pour virements transfrontaliers (si requis) / Intermediary Bank (if required)**
> Il s'agit de la banque intermédiaire (de transit), et non de la banque bénéficiaire ; renseignez-vous auprès de votre banque émettrice pour savoir si ces informations sont requises.
> Note: this is intermediary bank information, not the receiving bank. Please check with your remitting bank whether it is required.
>
> - Pour HKD, CNY et USD / For HKD / CNY / USD : **Citibank N.A. Hong Kong** — Code SWIFT : CITIHKHXXXX, Code banque : 006, Succursale : Hong Kong Branch, Code succursale : 391, Adresse : Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - Pour les autres devises / For other currencies : **The Bank of New York Mellon** — Code SWIFT : IRVTUS3NXXX, Adresse : 240 Greenwich Street, New York, United States

## Copyright

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

### Don en cryptomonnaie (Crypto Donation)

Si ce projet vous est utile, scannez le code QR pour faire un don, merci !

| Réseau (Network) | Code QR (QR Code) | Adresse du portefeuille (Wallet Address) |
|---|---|---|
| BNB Smart Chain (BEP20) | [<img src="../coin/1.jpg" width="150" alt="BNB Smart Chain (BEP20)">](../coin/1.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Tron (TRC20) | [<img src="../coin/2.jpg" width="150" alt="Tron (TRC20)">](../coin/2.jpg) | `TEdDHWLajt1XvqtPDWmQctdrJaC3pzZZzz` |
| Ethereum (ERC20) | [<img src="../coin/3.jpg" width="150" alt="Ethereum (ERC20)">](../coin/3.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Aptos | [<img src="../coin/4.jpg" width="150" alt="Aptos">](../coin/4.jpg) | `0x836e3780edfc3f7b2372b39e2a1a3a5d7adfaccd96c726f21cfde1b50dd68030` |
| Plasma | [<img src="../coin/5.jpg" width="150" alt="Plasma">](../coin/5.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Polygon POS | [<img src="../coin/6.jpg" width="150" alt="Polygon POS">](../coin/6.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Solana | [<img src="../coin/7.jpg" width="150" alt="Solana">](../coin/7.jpg) | `2hfhboHdmdrYsY25XfQSsEWxq5ip4EQsR7f4AzSRMUyr` |
| The Open Network (TON) | [<img src="../coin/8.jpg" width="150" alt="The Open Network (TON)">](../coin/8.jpg) | `UQB9kFQohzmXUir9QSSZq01iwl9aQZIDdBpNmDklljRtCoGK` |
| Arbitrum One | [<img src="../coin/9.jpg" width="150" alt="Arbitrum One">](../coin/9.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| AVAX C-Chain | [<img src="../coin/10.jpg" width="150" alt="AVAX C-Chain">](../coin/10.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |

