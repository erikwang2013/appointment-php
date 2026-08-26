# Système de réservation de services — Structure du projet

## Vue d'ensemble du dépôt

```
appointment-php/
├── admin/              # Back-office (webman v2 + Flutter Web)
├── service/            # Service d'API métier (webman v2)
├── apps/               # Applications frontend utilisateur
│   ├── wechat/         #   Mini-programme WeChat (natif)
│   ├── flutter/        #   Flutter APP (iOS + Android)
│   └── harmonyos/      #   HarmonyOS APP (natif Harmony)
├── docs/               # Documentation du projet
└── .claude/            # Configuration Claude Code
```

## Relations entre les projets

```
┌──────────────────────────────────────────────┐
│                   apps/                       │
│  ┌─────────────┐  ┌──────────┐  ┌─────────┐  │
│  │ wechat/      │  │ flutter/  │  │harmonyos/│  │
│  │ Mini-prog.   │  │iOS/Android│  │ APP Natif│  │
│  └──────┬──────┘  └────┬─────┘  └────┬────┘  │
│         │     Fonctionnalités      │            │
│         │     identiques            │            │
│         └──────────┬─────────┘            │
│                    │ API HTTP                 │
├────────────────────┼─────────────────────────┤
│              service/                         │
│         API métier (webman v2)                │
│             Port : 8787                       │
│                    │                          │
│                    │ Partage MySQL/Redis/ES   │
│                    │                          │
│              admin/                           │
│      API du back-office (webman v2)           │
│             Port : 8787                       │
│                    │                          │
│         ┌──────────┴──────────┐               │
│         │                     │               │
│    admin/apps/flutter/    Flutter Web         │
│    Frontend du back-office (PC)               │
└──────────────────────────────────────────────┘
```

## admin/ — Back-office

```
admin/
├── app/
│   ├── admin/controller/       # Contrôleurs du back-office
│   │   ├── BaseController          # Contrôleur de base
│   │   ├── DashboardController     # Tableau de bord
│   │   ├── UserController          # Gestion des utilisateurs
│   │   ├── RoleController          # Gestion des rôles
│   │   ├── PermissionController    # Gestion des permissions
│   │   ├── ConfigController        # Configuration système
│   │   ├── LogController           # Journaux d'opérations
│   │   ├── ProfileController       # Espace personnel
│   │   ├── ExportController        # Export
│   │   ├── ImportController        # Import
│   │   ├── UploadController        # Téléchargement de fichiers
│   │   ├── HealthController        # Vérification de santé
│   │   ├── DocsController          # Documentation API
│   │   ├── MetricsController       # Métriques Prometheus
│   │   │                            # ✅ Modules métier implémentés :
│   │   ├── TechnicianController    #   Gestion des techniciens (liste/audit/planning/export)
│   │   ├── MemberController        #   Gestion des membres (niveau/consommation)
│   │   ├── StoreController         #   CRUD des boutiques
│   │   ├── ServiceController       #   CRUD des prestations
│   │   ├── ServiceCategoryController # CRUD des catégories de prestations (arborescent)
│   │   ├── ProductController       #   CRUD des produits
│   │   ├── MallOrderController     #   Commandes boutique/expédition/S.A.V.
│   │   ├── SalesStatsController    #   Statistiques de ventes (cache Redis)
│   │   ├── AppointmentOrderController  # Commandes de réservation (annulation/clôture)
│   │   ├── MemberCardController    #   CRUD des cartes de membre
│   │   ├── ReviewController        #   Gestion des évaluations de service
│   │   ├── ReportController        #   Statistiques des rapports
│   │   ├── CouponController        #   CRUD des bons
│   │   ├── FinanceController       #   Flux financiers/statistiques
│   │   ├── WithdrawalController    #   Validation des retraits (approuver/rejeter/clôturer)
│   │   ├── CommissionController    #   Paramètres de commission/récompenses et pénalités
│   │   ├── WithdrawalAccountController # Gestion des comptes de retrait
│   │   ├── WithdrawalConfigController  # Configuration des limites de retrait
│   │   ├── BannerController        #   CRUD des bannières
│   │   ├── AnnouncementController  #   CRUD/publication des annonces
│   │   ├── FaqController           #   CRUD des questions fréquentes
│   │   ├── FeedbackController      #   Retours d'expérience/réponses
│   │   ├── MomentController        #   Modération du fil d'actualités
│   │   ├── AgreementController     #   Édition/publication des accords
│   │   ├── AboutController         #   Paramètres « À propos »
│   │   └── SystemMessageController #   Modèles/envoi de messages système
│   │   │                            # ✅ Modules étendus :
│   │   ├── ServiceCardController    #   Conception des cartes de prestations
│   │   ├── SystemMonitorController  #   Supervision système
│   │   ├── IpBlacklistController    #   Gestion de la liste noire IP
│   │   ├── DbBackupController       #   Sauvegarde de la base de données
│   │   ├── SmsConfigController      #   Configuration SMS
│   │   ├── StorageConfigController  #   Configuration du stockage
│   │   ├── StoreManagerController   #   Comptes de responsable de boutique
│   │   ├── TrainingController       #   Formation des techniciens
│   │   ├── ScheduledTaskController  #   Tâches planifiées
│   │   ├── CustomerProfileController #  Profil client
│   │   ├── BatchMessageController   #   Envoi groupé
│   │   ├── RefundWorkflowController #   Validation des remboursements
│   │   ├── TechnicianTierController #   Niveaux de technicien
│   │   │                            # ✅ Ajouté aux tours 22-25 :
│   │   ├── FullReductionController  #   Opérations de remise (montant)
│   │   ├── AttendanceController     #   Pointage des techniciens
│   │   ├── ProfitSharingController  #   Partage des profits WeChat
│   │   ├── LuckyWheelController     #   Roue de la fortune à points
│   │   ├── PointsExchangeGoodsController # Produits d'échange à points
│   │   ├── ReviewAuditController    #   Modération des images d'évaluation
│   │   ├── InvoiceController        #   Factures électroniques
│   │   ├── TicketController         #   Tickets du service client
│   │   ├── ReferralRewardController #   Enregistrements de commission niveau 1
│   │   ├── ReferralLevel2Controller #   Enregistrements de commission niveau 2
│   │   ├── ReturnCustomerController #   Récompenses client fidèle
│   │   ├── SeckillController        #   Opérations flash
│   │   ├── VersionController        #   Gestion des versions APP
│   │   ├── TechnicianScheduleController # Gestion des plannings/export CSV
│   │   ├── AftersaleController      #   Traitement du S.A.V.
│   │   ├── OrderVerificationController # Enregistrements de vérification
│   │   ├── CommunityModerationController # Modération de la communauté
│   │   ├── VideoAuditController     #   Modération des vidéos
│   │   └── InstallController        #   Assistant d'installation
│   ├── api/v1/controller/      # API publique v1
│   │   ├── AuthController
│   │   └── CaptchaController
│   ├── common/                 # Outils communs
│   │   ├── HashidsService
│   │   ├── SnowflakeService
│   │   ├── EncryptionService
│   │   ├── TechnicianWithdrawalService
│   │   └── WechatPayService
│   ├── middleware/             # Middleware
│   │   ├── Cors
│   │   ├── RateLimit
│   │   ├── ApiVersion
│   │   ├── AdminAuth
│   │   ├── AdminPermission
│   │   └── OperationLog
│   ├── model/                  # Modèles de données (6 modèles spécifiques uniquement : AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig ; les autres partagent la version service via psr-4)
│   ├── queue/                  # Tâches de file d'attente
│   └── process/                # Processus
├── apps/
│   ├── flutter/                # Frontend Flutter Web du back-office
│   │   └── lib/app/
│   │       ├── pages/           #   Pages (20)
│   │       │   ├── dashboard/   #   Tableau de bord
│   │       │   ├── login/       #   Connexion
│   │       │   ├── user/        #   Gestion des utilisateurs
│   │       │   ├── member/      #   Gestion des membres
│   │       │   ├── role/        #   Rôles et permissions
│   │       │   ├── config/      #   Configuration système
│   │       │   ├── log/         #   Journaux d'opérations
│   │       │   ├── profile/     #   Espace personnel
│   │       │   ├── technician/  #   Gestion des techniciens
│   │       │   ├── schedule/    #   Planning
│   │       │   ├── service/     #   Gestion des prestations/produits
│   │       │   ├── service_card/#   Conception des cartes de prestations
│   │       │   ├── order/       #   Gestion des commandes
│   │       │   ├── verification/#   Enregistrements de vérification
│   │       │   ├── coupon/      #   Bons
│   │       │   ├── withdrawal/  #   Validation des retraits
│   │       │   ├── report/      #   Statistiques des rapports
│   │       │   ├── review/      #   Gestion des évaluations
│   │       │   ├── announcement/#   Annonces
│   │       │   └── faq/         #   Questions fréquentes
│   │       ├── services/        #   Couche de services API
│   │       ├── layouts/         #   Mises en page
│   │       └── theme/           #   Thèmes
│   ├── harmonyos/               # Back-office HarmonyOS (ArkTS)
│   └── weixin/                  # Back-office WeChat
├── config/                     # Fichiers de configuration
│   ├── route.php
│   ├── middleware.php
│   ├── database.php
│   ├── jwt.php
│   ├── snowflake.php
│   ├── hashids.php
│   ├── encryption.php
│   ├── encryptable.php
│   └── ...
├── database/
│   └── backup/                 # Scripts de sauvegarde (schéma et données de démo unifiés dans ../install.sql)
├── docs/                       # Documentation du back-office
├── public/                     # Fichiers d'entrée
├── runtime/                    # Exécution
├── tests/                      # Tests
├── vendor/                     # Dépendances
├── CLAUDE.md
├── composer.json
├── Dockerfile
└── docker-compose.yml
```

## service/ — API métier

```
service/
├── app/
│   ├── api/v1/controller/       # API publique v1 (26 contrôleurs)
│   │   ├── AuthController          # Connexion/inscription/mot de passe oublié/rafraîchissement/changement d'identité
│   │   ├── CaptchaController       # Code de vérification SMS (limitation Redis)
│   │   ├── CommonController        # Configuration publique/accords/régions
│   │   ├── ContentController       # Bannières/annonces/articles
│   │   ├── DocsController          # Documentation OpenAPI (hg/apidoc)
│   │   ├── LbsController           # Boutiques à proximité (Haversine)/géocodage inverse
│   │   ├── GuestController         # Mode invité (navigation en lecture seule sans connexion, cache Redis)
│   │   ├── SeckillController       # Opérations flash/achat flash (canal dédié)
│   │   ├── PromotionController     # Achats groupés (ancien canal flash_sale mis hors ligne)
│   │   ├── ServiceController       # Catégories de prestations/projets/produits/boutiques
│   │   ├── ServicePackageController # Forfaits de prestations
│   │   ├── StoreManagerController  # Poste de travail du responsable de boutique (overview/orders/technicians/revenue)
│   │   ├── TechnicianController    # Informations publiques des techniciens
│   │   ├── BrowseHistoryController # Historique de navigation
│   │   ├── CalendarController      # Calendrier de réservation (vues mois/jour)
│   │   ├── CommunityController     # Activités communautaires
│   │   ├── CommunityCommentController # Commentaires communautaires
│   │   ├── FullReductionController # Opérations de remise (montant)
│   │   ├── PaymentNotifyController # Callbacks de paiement (WeChat/Alipay)
│   │   ├── PrintController         # Impression
│   │   ├── PrivacyController       # Conformité vie privée (export de données/suppression de compte)
│   │   ├── QueueController         # File d'attente et appel
│   │   ├── VersionController       # Gestion des versions APP/détection de mise à jour
│   │   ├── VideoController         # Vidéos
│   │   ├── WechatController        # Lié à WeChat
│   │   └── WheelController         # Roue de la fortune à points
│   ├── user/v1/controller/      # Module utilisateur v1 (14 contrôleurs)
│   │   ├── ProfileController       # Informations personnelles/mot de passe/téléphone/suppression de compte/déconnexion
│   │   ├── AddressController       # CRUD des adresses (gestion de l'adresse par défaut)
│   │   ├── FavoriteController      # Favoris (prestations/techniciens)
│   │   ├── FeedbackController      # Retours d'expérience (texte + images)
│   │   ├── ReferralController      # Parrainage/QR code/utilisateurs recommandés
│   │   ├── CheckInController       # Pointage de présence
│   │   ├── DeviceController        # Gestion des appareils utilisateur
│   │   ├── GrowthController        # Niveau de croissance (aperçu/records/levels)
│   │   ├── HealthProfileController # Dossier de santé
│   │   ├── InvoiceController       # Demandes/liste/détails de factures électroniques
│   │   ├── InvoiceTitleController  # Bibliothèque d'intitulés de facture
│   │   ├── NotifySettingController # Préférences de notification
│   │   ├── PointsTransferController# Transfert de points
│   │   └── TicketController        # Tickets du service client
│   ├── technician/v1/controller/ # Module technicien v1 (10 contrôleurs)
│   │   ├── ProfileController       # Dossier du technicien/demande d'adhésion
│   │   ├── ScheduleController      # Consultation/paramétrage du planning
│   │   ├── OrderController         # Liste des commandes du technicien
│   │   ├── WorkController          # Poste de travail (today/records/start/complete)
│   │   ├── EarningController       # Aperçu des revenus + flux
│   │   ├── WithdrawController      # Demande de retrait (le jour config('withdraw.gate_day') de chaque mois, configurable)
│   │   ├── ServiceRecordController # Enregistrements de service
│   │   ├── ExamController          # Évaluation en ligne
│   │   ├── AttendanceController    # Pointage d'arrivée/départ
│   │   └── ReviewController        # Réponses du technicien aux évaluations
│   ├── order/v1/controller/     # Module commandes v1 (8 contrôleurs + 9 traits)
│   │   ├── OrderController         # Commande (verrou du technicien)/liste/détails/annulation/paiement/remboursement/vérification (point d'entrée agrégé, 38 lignes, toutes les méthodes proviennent des traits)
│   │   ├── OrderCreateTrait        # Création de commande store/aide à la tarification (475 lignes)
│   │   ├── OrderQueryTrait         # Consultation de commande liste/détails/logistique (205 lignes)
│   │   ├── OrderPayTrait           # Paiement pay/paiement par solde/réduction de points (415 lignes)
│   │   ├── OrderCancelTrait        # Annulation de commande (272 lignes)
│   │   ├── OrderRefundTrait        # Demande de remboursement (379 lignes)
│   │   ├── OrderCompensateTrait    # Analyse de compensation de remboursement + restitution offres/points (345 lignes)
│   │   ├── OrderVerifyTrait        # Vérification commission/points (256 lignes)
│   │   ├── OrderRescheduleTrait    # Report de réservation (181 lignes)
│   │   ├── OrderNotifyTrait        # Notifications abonnement/modèle/interne/WebSocket (195 lignes)
│   │   └── OrderLockTrait          # Outil de verrouillage distribué (80 lignes)
│   │   ├── AftersaleController     # S.A.V.
│   │   ├── CartController          # Panier
│   │   ├── IcsController           # Export du calendrier ICS
│   │   ├── ReviewController        # Évaluations/évaluations complémentaires
│   │   ├── SignatureController     # Signatures
│   │   ├── TimelineController      # Chronologie des statuts de commande
│   │   └── WaitlistController      # Liste d'attente
│   ├── wallet/v1/controller/    # Module portefeuille v1 (2 contrôleurs)
│   │   ├── WalletController        # Solde/recharge/flux de transactions/paiement par solde
│   │   └── WalletTransferController# Transferts entre utilisateurs
│   ├── marketing/v1/controller/ # Module marketing v1 (7 contrôleurs)
│   │   ├── CouponController        # Liste/réception/réduction à la commande des bons
│   │   ├── CardController          # Liste/achat des cartes de membre/carte à forfait my/use
│   │   ├── PointController         # Flux de points/reduction de consommation
│   │   ├── GiftCardController      # Cartes cadeaux/échange redeem
│   │   ├── MemberBenefitController # Avantages membres
│   │   ├── MemberCardController    # Définition des cartes de membre
│   │   └── PointsExchangeController# Boutique d'échange à points
│   ├── notification/v1/controller/ # Module notifications v1 (1 contrôleur)
│   │   └── NotificationController  # Liste des notifications/marquage lu
│   ├── common/                  # Capacités communes (BaseController, etc.)
│   ├── middleware/              # Middleware
│   │   ├── ApiVersion              # Contrôle de version API (en-tête API-Version)
│   │   ├── Auth                    # Authentification JWT + validation du statut utilisateur
│   │   ├── Cors                    # Traitement interdomaine
│   │   ├── Security                # Détection de sécurité (security-php)
│   │   └── TechnicianAuth          # Validation de l'identité technicien
│   └── model/                   # Modèles de données (81)
│       ├── User.php → erik_user
│       ├── TechnicianProfile.php → erik_technician_profile
│       ├── Service.php → erik_service (ES: erik_services)
│       ├── Product.php → erik_product (ES: erik_products)
│       ├── Store.php → erik_store
│       ├── Order.php → erik_order (contient les règles de remboursement/machine à états)
│       ├── Coupon.php → erik_coupon
│       ├── MemberCard.php → erik_member_card
│       ├── Notification.php → erik_notification
│       └── ... (81 fichiers de modèles au total ; admin en compte 6 autres spécifiques, soit 87 au total)
├── config/                     # Fichiers de configuration
├── public/                     # Entrée
├── runtime/                    # Exécution
├── vendor/                     # Dépendances
├── start.php
├── composer.json
└── Dockerfile
```

## apps/ — Frontend utilisateur

### apps/wechat/ — Mini-programme WeChat

```
apps/wechat/
├── app.js                      # Entrée de l'application
├── app.json                    # Configuration globale
├── app.wxss                    # Styles globaux
├── pages/
│   ├── auth/                   # Authentification
│   │   ├── login               #   Connexion
│   │   ├── register            #   Inscription
│   │   ├── forget-password     #   Mot de passe oublié
│   │   └── agreement           #   Consultation des accords
│   ├── home/                   # Accueil (bannières/annonces/catégories/recherche)
│   ├── service/                # Prestations
│   │   ├── list                #   Liste des prestations
│   │   └── detail              #   Détail d'une prestation
│   ├── order/                  # Commandes
│   │   ├── list                #   Liste des commandes
│   │   ├── detail              #   Détail d'une commande
│   │   └── confirm             #   Confirmation de commande
│   ├── cart/                   # Panier
│   ├── cards/                  # Cartes de membre (achat/mes cartes/utilisation de la carte à forfait my/use)
│   ├── gift-cards/             # Cartes cadeaux (échange redeem/entrée en compte)
│   ├── points/                 # Points (flux/échange)
│   ├── marketing/              # Marketing (bons, etc.)
│   ├── favorite/               # Favoris
│   ├── feedback/               # Retours d'expérience
│   ├── referral/               # Parrainage
│   ├── message/                # Messages
│   │   ├── list                #   Liste des messages
│   │   └── detail              #   Détail d'un message
│   ├── tech-work/              # Poste de travail du technicien
│   │   ├── index               #   Accueil du poste de travail (today/records/start/complete)
│   │   ├── schedule            #   Planning
│   │   ├── order-list          #   Commandes
│   │   ├── scan-verify         #   Vérification par scan
│   │   ├── member-list         #   Liste des membres
│   │   ├── member-detail       #   Détail d'un membre
│   │   ├── earnings            #   Revenus
│   │   ├── withdrawal          #   Retrait
│   │   ├── transaction-list    #   Détail des transactions
│   │   └── training            #   Formation
│   ├── user/                   # Espace personnel
│   │   ├── index               #   Informations personnelles
│   │   ├── settings            #   Paramètres
│   │   └── switch-role         #   Changement d'identité
│   └── wallet/                 # Portefeuille (solde/recharge/flux de transactions)
├── components/                 # Composants communs
│   ├── navbar
│   ├── tabbar
│   ├── service-card
│   ├── technician-card
│   ├── coupon-popup
│   └── lbs-selector
├── utils/                      # Utilitaires
│   ├── api.js                  #   Requêtes HTTP
│   ├── auth.js                 #   Gestion de l'authentification
│   ├── location.js             #   Localisation LBS
│   └── constants.js            #   Constantes
├── styles/                     # Styles communs
└── images/                     # Ressources d'images
```

### apps/flutter/ — Flutter APP

```
apps/flutter/
├── lib/
│   ├── main.dart               # Entrée
│   ├── app.dart                # Configuration de l'app/routes/thèmes
│   ├── pages/                  # Pages (structure identique au mini-programme)
│   │   ├── auth/
│   │   ├── home/
│   │   ├── service/
│   │   ├── order/
│   │   ├── cart/
│   │   ├── technician/
│   │   ├── tech_work/
│   │   ├── user/
│   │   ├── marketing/
│   │   ├── message/
│   │   ├── store/
│   │   └── other/
│   ├── widgets/                # Composants communs
│   ├── services/               # Services API
│   │   ├── api_service         #   HTTP (Dio)
│   │   ├── auth_service        #   Authentification
│   │   └── location_service    #   Localisation
│   ├── models/                 # Modèles de données
│   ├── state/                  # Gestion d'état
│   └── utils/                  # Utilitaires
├── android/                    # Projet Android
├── ios/                        # Projet iOS
├── pubspec.yaml
└── ...
```

## Chaîne d'exécution du middleware

### service/

```
API publique :  Cors → Security → RateLimit → Controller
API utilisateur :  Cors → Security → RateLimit → Auth → Controller
API technicien :  Cors → Security → RateLimit → Auth → TechnicianAuth → Controller
Callback de paiement :  Cors → Security → Controller
```

### admin/

```
API publique :  Cors → Security → RateLimit → Controller
API de gestion :  Cors → Security → RateLimit → AdminAuth → AdminPermission → OperationLog → Controller
Vérification de santé :  Cors → Security → RateLimit → Controller
```

## Liste des tables de la base de données

Toutes les tables utilisent le préfixe `erik_`, clés primaires BIGINT non auto-incrémentées (générées par Snowflake).

| Domaine | Nom de la table | Description |
|----|------|------|
| Utilisateurs | erik_user | Table utilisateur unifiée |
| Utilisateurs | erik_user_address | Adresses de livraison |
| Techniciens | erik_technician_profile | Dossier du technicien |
| Techniciens | erik_technician_schedule | Planning du technicien |
| Techniciens | erik_technician_service | Prestations proposées par le technicien |
| Techniciens | erik_technician_earnings | Flux de revenus du technicien |
| Techniciens | erik_technician_withdrawal | Enregistrements de retrait du technicien |
| Techniciens | erik_technician_attendance | Pointage du technicien |
| Techniciens | erik_technician_member_note | Dossier client |
| Services | erik_service_category | Catégories de prestations |
| Services | erik_service | Prestations |
| Services | erik_product | Produits |
| Services | erik_store | Boutiques |
| Commandes | erik_order | Table principale des commandes (colonne de liaison seckill_id pour le flash, tour 24) |
| Commandes | erik_order_item | Détails de commande |
| Commandes | erik_order_payment | Enregistrements de paiement |
| Commandes | erik_order_refund | Enregistrements de remboursement |
| Commandes | erik_order_review | Évaluations de service |
| Commandes | erik_order_verification | Enregistrements de vérification |
| Commandes | erik_order_reschedule | Enregistrements de report de réservation (tour 17) |
| Marketing | erik_coupon | Définition des bons |
| Marketing | erik_user_coupon | Bons de l'utilisateur |
| Marketing | erik_user_coupon_transfer | Enregistrements de transfert de bons (tour 17) |
| Marketing | erik_user_points_transfer | Enregistrements de transfert de points (tour 19) |
| Marketing | erik_technician_tier_log | Journal de changement de niveau de technicien (tour 17) |
| Marketing | erik_member_card | Définition des cartes de membre |
| Marketing | erik_user_member_card | Cartes de membre de l'utilisateur |
| Marketing | erik_member_card_usage | Enregistrements d'utilisation des cartes à forfait |
| Marketing | erik_user_points | Flux de points |
| Marketing | erik_gift_card | Cartes cadeaux |
| Marketing | erik_user_referral | Parrainage utilisateur |
| Marketing | erik_user_favorite | Favoris utilisateur |
| Portefeuille | erik_user_wallet | Solde du portefeuille utilisateur |
| Portefeuille | erik_wallet_recharge | Enregistrements de recharge du portefeuille |
| Portefeuille | erik_wallet_txn | Flux de transactions du portefeuille |
| Portefeuille | erik_wallet_transfer | Enregistrements de transfert entre utilisateurs (tour 19) |
| Utilisateurs | erik_user_notify_setting | Préférences de notification (tour 19) |
| Contenu | erik_banner | Bannières |
| Contenu | erik_announcement | Annonces |
| Contenu | erik_platform_agreement | Accords de plateforme |
| Contenu | erik_faq | Questions fréquentes |
| Contenu | erik_feedback | Retours d'expérience |
| Contenu | erik_moment | Fil d'actualités |
| Contenu | erik_notification | Notifications |
| Finances | erik_finance_transaction | Flux de revenus et dépenses |
| Finances | erik_technician_commission_config | Configuration des commissions |
| Finances | erik_withdrawal_account | Comptes de retrait |
| Finances | erik_withdrawal_config | Configuration des limites de retrait |
| Système | erik_admin_user | Utilisateurs d'administration (déjà créés) |
| Système | erik_admin_role | Rôles (déjà créés) |
| Système | erik_admin_permission | Permissions (déjà créées) |
| Système | erik_admin_user_role | Liaison utilisateur-rôle (déjà créée) |
| Système | erik_admin_role_permission | Liaison rôle-permission (déjà créée) |
| Système | erik_system_config | Configuration système (déjà créée) |
| Système | erik_operation_log | Journaux d'opérations (déjà créés) |
| Utilisateurs | erik_user_growth | Flux de croissance (tour 20) |
| Utilisateurs | erik_growth_level | Niveaux de croissance (tour 20) |
| Commandes | erik_invoice | Factures électroniques (tour 20) |
| Utilisateurs | erik_ticket | Tickets du service client (tour 20) |
| Marketing | erik_referral_level2_reward | Enregistrements de commission niveau 2 (tour 20) |
| Utilisateurs | erik_invoice_title | Bibliothèque d'intitulés de facture (tour 21) |
| Utilisateurs | erik_browse_history | Historique de navigation (tour 21) |
| Marketing | erik_full_reduction_activity | Opérations de remise (tour 22) |
| Techniciens | erik_technician_attendance | Pointage des techniciens (tour 22) |
| Système | erik_push_log | Enregistrements de push APP (tour 22) |
| Finances | erik_profit_sharing | Enregistrements de partage des profits WeChat (tour 22) |
| Commandes | erik_order_status_log | Chronologie des statuts de commande (tour 23) |
| Utilisateurs | erik_user_health_profile | Dossier de santé utilisateur (tour 23) |
| Marketing | erik_lucky_wheel | Définition des prix de la roue (tour 23) |
| Marketing | erik_wheel_record | Enregistrements de tirage de la roue (tour 23) |
| Marketing | erik_seckill_activity | Opérations flash (tour 24) |
| Système | erik_app_version | Versions APP (tour 24) |

### Liste complémentaire (parties des 95 tables de ../install.sql non listées ci-dessus ; la liste complète faisant autorité est install.sql)

| Domaine | Nom de la table | Description |
|----|------|------|
| Marketing | erik_card_transfer | Transfert de carte à forfait |
| Utilisateurs | erik_check_in | Pointage de présence |
| Contenu | erik_community_post | Activités communautaires |
| Contenu | erik_community_comment | Commentaires communautaires |
| Techniciens | erik_exam | Évaluation |
| Techniciens | erik_exam_question | Questions d'évaluation |
| Techniciens | erik_exam_attempt | Copies d'évaluation |
| Système | erik_operation_log_detail | Détails des journaux d'opérations |
| Commandes | erik_order_aftersale | S.A.V. des commandes |
| Marketing | erik_points_exchange_goods | Produits d'échange à points |
| Marketing | erik_promotion | Opérations d'achat groupé |
| Marketing | erik_promotion_participant | Participants à l'achat groupé |
| Commandes | erik_queue_number | File d'attente et appel |
| Services | erik_service_package | Forfaits de prestations |
| Techniciens | erik_service_record | Enregistrements de service |
| Contenu | erik_share | Enregistrements de partage |
| Commandes | erik_signature | Signatures |
| Techniciens | erik_technician_tier_config | Configuration des niveaux de technicien |
| Techniciens | erik_training_course | Cours de formation |
| Techniciens | erik_training_progress | Progression de la formation |
| Utilisateurs | erik_user_device | Appareils utilisateur |
| Marketing | erik_user_points_exchange | Enregistrements d'échange de points |
| Contenu | erik_video_post | Vidéos dynamiques |
| Commandes | erik_waitlist | Liste d'attente |

## Services externes réservés

| Service | Utilisation | Point d'intégration |
|------|------|--------|
| Plateforme ouverte WeChat | Connexion WeChat/UnionID | WechatAuthService |
| WeChat Pay | Paiement/remboursement/retrait | WechatPayService |
| Fournisseur SMS | Codes de vérification/notifications | SmsService |
| Service cartographique | Localisation LBS/navigation/calcul de distance | MapService |
