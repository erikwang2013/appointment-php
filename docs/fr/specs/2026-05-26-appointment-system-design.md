# Spécifications de conception du système de réservation de services
> **Languages**: [中文](../../superpowers/specs/2026-05-26-appointment-system-design.md) · [English](../../en/specs/2026-05-26-appointment-system-design.md) · [한국어](../../ko/specs/2026-05-26-appointment-system-design.md) · [Русский](../../ru/specs/2026-05-26-appointment-system-design.md) · [Deutsch](../../de/specs/2026-05-26-appointment-system-design.md) · [Español](../../es/specs/2026-05-26-appointment-system-design.md) · [Português](../../pt/specs/2026-05-26-appointment-system-design.md) · [हिन्दी](../../hi/specs/2026-05-26-appointment-system-design.md) · [العربية](../../ar/specs/2026-05-26-appointment-system-design.md) · [বাংলা](../../bn/specs/2026-05-26-appointment-system-design.md) · [Bahasa Indonesia](../../id/specs/2026-05-26-appointment-system-design.md) · [日本語](../../ja/specs/2026-05-26-appointment-system-design.md)

## Vue d'ensemble

Système de réservation de services à trois extrémités : utilisateur (mini-programme WeChat + Flutter APP) + poste de travail du technicien (changement d'identité dans la même APP) + back-office (PC Web).

## Décisions d'architecture

| Décision | Solution |
|------|------|
| Architecture backend | `admin/` (API du back-office) + `service/` (API métier), deux services partageant MySQL/Redis |
| Mini-programme utilisateur | Mini-programme WeChat natif `apps/wechat/` |
| APP utilisateur | Flutter `apps/flutter/` (iOS + Android) |
| Identité utilisateur | Compte unifié, identité client/technicien commutable |
| Relation mini-programme/APP | Fonctionnalités identiques, seule la plateforme diffère |
| Frontend du back-office | Extension du Flutter Web existant (`admin/apps/flutter/`) |
| Backend du back-office | Extension des modules métier du webman v2 existant (`admin/`) |
| Services tiers | Connexion WeChat/paiement/SMS/cartographie — solutions d'intégration réservées |

## Schéma d'architecture du système

```
┌──────────────────────────────────────────────────────────┐
│                      Couche des terminaux                 │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ Mini-prog. WeChat │  │ Flutter APP       │              │
│  │ apps/wechat/      │  │ apps/flutter/     │              │
│  │ (WXML/WXSS natif) │  │ (iOS + Android)   │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │    Fonctionnalités    │                        │
│           │      identiques      │                        │
│           └──────────┬──────────┘                        │
│                      │ Identité client / technicien      │
├──────────────────────┼──────────────────────────────────┤
│              Passerelle API métier                        │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ service/ API      │  │ admin/ API        │              │
│  │ (webman v2)       │  │ (webman v2)       │              │
│  │ Utilisateurs/     │  │ Interfaces du    │              │
│  │ commandes/        │  │ back-office      │              │
│  │ paiement/         │  │ (existantes +    │              │
│  │ techniciens/      │  │ extensions)      │              │
│  │ boutiques/        │  │                  │              │
│  │ marketing...      │  │                  │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │                      │                        │
│           └──────────┬───────────┘                        │
│                      │                                    │
├──────────────────────┼──────────────────────────────────┤
│                   Couche de données                       │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────────────┐    │
│  │ MySQL  │ │ Redis  │ │  ES    │ │ Services tiers  │    │
│  │ 8.0    │ │ Cache/ │ │ Recher-│ │ WeChat/SMS/    │    │
│  │        │ │ Limite/│ │ che    │ │ cartographie   │    │
│  │        │ │ Session│ │        │ │ (intégration   │    │
│  │        │ │        │ │        │ │ réservée)      │    │
│  └────────┘ └────────┘ └────────┘ └────────────────┘    │
└──────────────────────────────────────────────────────────┘
```

## Tables principales de la base de données

Toutes les tables utilisent le préfixe `appointment_`, clés primaires BIGINT non auto-incrémentées (générées par Snowflake). Les champs sensibles sont chiffrés/déchiffrés avec le trait encryptable.

### Domaine utilisateurs et identité

| Nom de la table | Description | Champs principaux |
|------|------|----------|
| `appointment_user` | Table utilisateur unifiée | phone, password, wx_openid, wx_unionid, avatar, nickname, user_type(customer/technician), status. Les utilisateurs techniciens disposent également des fonctions client et peuvent basculer librement l'identité active |
| `appointment_user_address` | Adresses utilisateur | user_id, contact_name, contact_phone, province, city, district, detail, is_default |
| `appointment_technician_profile` | Dossier du technicien | user_id, real_name, gender, id_card, id_card_front, id_card_back, avatar, rating, order_count, status(pending/approved/rejected), intro |
| `appointment_technician_schedule` | Planning du technicien | technician_id, date, time_slots(JSON), status |
| `appointment_technician_service` | Prestations proposées par le technicien | technician_id, service_id |
| `appointment_technician_earnings` | Flux de revenus du technicien | technician_id, order_id, type(commission/bonus/penalty), amount, status |
| `appointment_technician_withdrawal` | Enregistrements de retrait du technicien | technician_id, amount, actual_amount, commission_fee, account_info, status, reviewed_at |
| `appointment_technician_attendance` | Pointage du technicien | technician_id, date, check_in_at, check_out_at, clean_photo |
| `appointment_technician_member_note` | Dossier client | technician_id, user_id, content, written_at |

### Domaine services et produits

| Nom de la table | Description | Champs principaux |
|------|------|----------|
| `appointment_service_category` | Catégories de prestations | name, icon, parent_id, sort, status |
| `appointment_service` | Prestations | category_id, name, description, cover_image, images(JSON), price, duration, sales_volume, specs(JSON), status |
| `appointment_product` | Produits | category_id, name, cover_image, price, stock, sales_volume, type, status |
| `appointment_store` | Boutiques | name, address, lat, lng, phone, business_hours(JSON), images, status |

### Domaine commandes

| Nom de la table | Description | Champs principaux |
|------|------|----------|
| `appointment_order` | Table principale des commandes | order_no, user_id, technician_id, store_id, total_amount, discount_amount, paid_amount, status, service_time, cancel_reason, remark |
| `appointment_order_item` | Détails de commande | order_id, service_id, product_id, type, name, price, quantity, spec_info |
| `appointment_order_payment` | Enregistrements de paiement | order_id, pay_type(wechat), transaction_id, amount, status, paid_at |
| `appointment_order_refund` | Enregistrements de remboursement | order_id, payment_id, refund_no, amount, ratio, reason, status |
| `appointment_order_review` | Évaluations de service | order_id, user_id, technician_id, rating, content, images |
| `appointment_order_verification` | Enregistrements de vérification | order_id, code, verified_at, verified_by, location |

### Domaine marketing

| Nom de la table | Description | Champs principaux |
|------|------|----------|
| `appointment_coupon` | Définition des bons | name, type, amount, min_amount, total_qty, remain_qty, start_at, end_at, status |
| `appointment_user_coupon` | Bons de l'utilisateur | user_id, coupon_id, status(available/used/expired), used_at |
| `appointment_member_card` | Définition des cartes de membre | name, type(month/vip/times), price, duration_days, total_times, services(JSON) |
| `appointment_user_member_card` | Cartes de membre de l'utilisateur | user_id, card_id, start_at, end_at, total_times, used_times, status |
| `appointment_member_card_usage` | Enregistrements d'utilisation des cartes à forfait | user_card_id, order_id, service_id, used_at |
| `appointment_user_points` | Flux de points | user_id, type(earn/use), points, source, order_id |
| `appointment_gift_card` | Cartes cadeaux | code, type, amount_or_gift, status, used_by, used_at |
| `appointment_user_referral` | Parrainage utilisateur | referrer_id, referred_user_id, reward_type, reward_amount, registered_at, first_order_at |

### Domaine contenu et notifications

| Nom de la table | Description | Champs principaux |
|------|------|----------|
| `appointment_banner` | Bannières | position, image, jump_type(url/detail/none), jump_value, sort, status |
| `appointment_announcement` | Annonces | content, status, published_at |
| `appointment_platform_agreement` | Accords de plateforme | type(user_agreement/privacy_policy/service_agreement), title, content, version |
| `appointment_faq` | Questions fréquentes | title, content, sort |
| `appointment_feedback` | Retours d'expérience | user_id, content, images, handler_reply, status(pending/handled) |
| `appointment_moment` | Fil d'actualités | content, images, published_at |
| `appointment_notification` | Notifications | user_id, type(order/system), title, content, is_read, created_at |

### Domaine financier (côté admin)

| Nom de la table | Description | Champs principaux |
|------|------|----------|
| `appointment_finance_transaction` | Flux de revenus et dépenses | user_id, order_id, type, direction(income/expense), amount, actual_amount, commission, status |
| `appointment_technician_commission_config` | Configuration des commissions | technician_id, commission_rate, settlement_cycle |
| `appointment_withdrawal_account` | Comptes de retrait | user_id, type(wechat), account_name, account_no |
| `appointment_withdrawal_config` | Configuration des limites de retrait | min_amount, reserve_amount, round_to_hundred |

## Modules de l'API Service

### API publique (sans authentification)
- **AuthController** — connexion/inscription/mot de passe oublié/mode invité/changement d'identité
- **CaptchaController** — code de vérification SMS
- **WechatController** — autorisation/connexion WeChat/callback de paiement
- **CommonController** — textes d'accords/à propos/informations de version

### Module utilisateur `user/` (authentification requise)
- **ProfileController** — informations personnelles/modification du mot de passe/changement de téléphone/suppression de compte
- **AddressController** — CRUD des adresses de livraison
- **FavoriteController** — favoris
- **FeedbackController** — retours d'expérience
- **ReferralController** — parrainage/liste des utilisateurs recommandés

### Module technicien `technician/` (identité technicien + middleware TechnicianAuth requis)
- **ProfileController** — dossier du technicien/demande d'adhésion
- **ScheduleController** — configuration du planning
- **OrderController** — réservé non vérifié/terminé/vérification par scan
- **MemberController** — mes membres/dossier client
- **EarningsController** — revenus/fonds en cours
- **WithdrawalController** — retrait
- **AttendanceController** — pointage/photos d'hygiène

### Module services `service/`
- **CategoryController** — catégories de prestations
- **ItemController** — listes et détails des prestations/produits
- **SearchController** — recherche
- **StoreController** — liste/détails des boutiques

### Module commandes `order/` (authentification requise)
- **CartController** — panier
- **OrderController** — commande/liste des commandes/détails/annulation
- **PaymentController** — paiement/remboursement
- **VerificationController** — vérification par QR code
- **ReviewController** — évaluations

### Module marketing `marketing/` (authentification requise)
- **CouponController** — liste/réception/utilisation des bons
- **MemberCardController** — cartes de membre/cartes à forfait
- **PointsController** — points
- **GiftCardController** — cartes cadeaux

### Module contenu `content/`
- **BannerController** — bannières
- **AnnouncementController** — annonces
- **NotificationController** — notifications

### Module LBS
- **LocationController** — localisation/changement de ville/boutiques à proximité

### Capacités communes `common/`
- SnowflakeService — génération des ID
- HashidsService — chiffrement/déchiffrement des ID
- EncryptionService — chiffrement/déchiffrement des données sensibles
- WechatPayService — paiement WeChat (réservé)
- WechatAuthService — connexion WeChat (réservé)
- SmsService — service SMS (réservé)
- MapService — service cartographique (réservé)

### Middleware
- Auth — authentification JWT (partage du paquet erikwang2013/jwt-webman avec admin)
- TechnicianAuth — validation de l'identité technicien
- RateLimit — limitation de débit (partagée avec admin)

## Extensions du back-office Admin

Nouveaux contrôleurs ajoutés sur la base du framework existant :

### Gestion des techniciens
- **TechnicianController** — liste des techniciens/recherche/export/audit/gestion des plannings/configuration des prestations/progression des cours

### Extension de la gestion des utilisateurs
- **MemberController** — liste des membres/définition des niveaux/statistiques de consommation

### Gestion des boutiques
- **StoreController** — CRUD des boutiques/activation et désactivation

### Gestion des services
- **ServiceController** — liste des prestations/CRUD/conception des cartes
- **ServiceCategoryController** — gestion des catégories
- **ProductController** — liste/CRUD des produits

### Gestion de la boutique
- **MallOrderController** — commandes boutique/expédition/S.A.V./évaluations
- **SalesStatsController** — statistiques de ventes

### Gestion des commandes
- **AppointmentOrderController** — commandes à utiliser/annulation/confirmation de clôture

### Opérations de bons
- **CouponController** — CRUD/émission des bons

### Gestion financière
- **FinanceController** — partage des commandes/flux de revenus et dépenses
- **WithdrawalController** — audit/clôture des retraits techniciens
- **CommissionController** — configuration des commissions/récompenses et pénalités/consultation du solde
- **WithdrawalAccountController** — gestion des comptes de retrait
- **WithdrawalConfigController** — configuration des limites de retrait

### Gestion de contenu
- **BannerController** — CRUD des bannières
- **AnnouncementController** — CRUD des annonces
- **FaqController** — CRUD des FAQ
- **FeedbackController** — traitement des retours d'expérience
- **MomentController** — modération du fil d'actualités
- **AgreementController** — édition des accords (accord utilisateur/accord de confidentialité/accord de service)
- **AboutController** — paramètres « À propos »

### Paramètres
- **SystemMessageController** — paramètres des messages système
- **AdminUserController** — gestion des sous-comptes (basée sur le RBAC existant)

### Extension du Dashboard
- Cartes de statistiques en temps réel : nombre d'utilisateurs/total des commandes/nombre de techniciens/nombre de commandes de services
- Graphiques en courbes : volume de commandes/montants/nouveaux utilisateurs par jour/activité
- Navigation rapide : boutons des modules à traiter
- Messages internes : notification de nouvelle commande/notification de remboursement

## Structure des pages côté utilisateur

Les fonctionnalités du mini-programme WeChat et de la Flutter APP sont identiques.

### auth/ — Authentification
- login — connexion (téléphone/code de vérification/WeChat/entrée invité)
- register — inscription (téléphone + code de vérification + mot de passe + code de parrainage)
- forget-password — mot de passe oublié
- agreement — consultation des accords

### home/ — Accueil
- index — accueil (bannières + annonces + catégories de prestations + recommandations)
- search — page de recherche

### service/ — Prestations
- list — liste des prestations (filtre par catégorie)
- detail — détail d'une prestation (informations de base + évaluations + réservation immédiate)
- product-list — liste des produits

### order/ — Commandes
- confirm — confirmation de commande (boutique/technicien/heure/bon/remarque/accord)
- payment — page de paiement
- payment-success — paiement réussi
- list — toutes les commandes (filtre par onglets de statut)
- detail — détail d'une commande
- review — évaluation de service
- verification — vérification par QR code

### cart/ — Panier
- index — liste du panier

### technician/ — Techniciens (vue client)
- list — liste des techniciens (tri par distance croissante)
- detail — détail d'un technicien (évaluations/prestations proposées/réservation immédiate)
- apply — demande d'adhésion technicien

### tech-work/ — Poste de travail du technicien (identité technicien)
- index — accueil du poste de travail (commandes du jour/aperçu des revenus)
- schedule — configuration du planning
- order-list — mes commandes (réservées non vérifiées/terminées)
- scan-verify — vérification par scan
- member-list — mes membres
- member-detail — détail du membre/édition du dossier
- earnings — mes revenus
- withdrawal — retrait
- transaction-list — détail des transactions
- attendance — pointage/téléchargement des photos d'hygiène
- training — formation professionnelle

### user/ — Espace personnel
- index — informations personnelles (avatar/surnom/carte de membre/favoris/entrée des bons)
- settings — paramètres (modification du mot de passe/changement de téléphone/accords/mise à jour/suppression de compte/déconnexion)
- switch-role — changement d'identité (client ↔ technicien)

### marketing/ — Marketing
- coupon-list — liste des bons
- member-card — mes cartes de membre
- points — mes points
- gift-card — mes cartes cadeaux
- referral — parrainage (explication + affiche QR code + liste des utilisateurs recommandés)

### Autres pages
- message/ — liste/détails des messages
- store/list, store/detail — liste des boutiques (tri LBS)/détail (navigation)
- other/about — à propos
- other/feedback — retours d'expérience
- other/official-account — suivi du compte officiel

### Composants communs
- navbar, tabbar, service-card, technician-card
- coupon-popup, lbs-selector, empty-state, loading

### Logique de changement d'identité
- Navigation basse de l'identité client : accueil / prestations / panier / commandes / moi
- Navigation basse de l'identité technicien : poste de travail / commandes / membres / revenus / moi
- La page « Moi » fournit l'entrée de changement d'identité
- Les utilisateurs pas encore techniciens qui basculent vers l'identité technicien sont redirigés vers la page de demande d'adhésion

## Description des parcours d'achat

Le système comporte deux parcours d'achat différents :

### Parcours de réservation de service (commande directe, sans panier)
- Page de détail de la prestation → confirmation de commande (choix boutique/technicien/heure) → paiement → vérification
- Ressource technicien exclusive : verrouillage du technicien pendant 3 minutes à l'entrée sur la page de confirmation de commande
- Pour les prestations physiques comme le massage, l'esthétique, etc.

### Parcours d'achat de produits (mode panier)
- Liste des produits → ajout au panier → confirmation du panier → soumission de la commande → paiement → expédition/réception
- Prise en charge de la modification des quantités, de la suppression des produits
- Pour la vente de produits physiques ou de cartes et bons

## Règles métier clés

### Mécanisme de verrouillage du technicien
- Une même personne ne peut pas être réservée simultanément par plusieurs personnes
- À l'entrée sur la page de confirmation de commande, l'utilisateur verrouille le technicien pendant 3 minutes via Redis SETNX
- Libération automatique du verrou à la sortie de la page de réservation ou à l'expiration

### Règles de remboursement
| Condition | Ratio de remboursement |
|------|----------|
| Moins de 15 min après la commande ou >6 h avant le début | 100 % |
| ≤6 h avant le début | 90 % |
| Commencé mais service non confirmé | 80 % |
| Après confirmation du début du service | 0 % (aucun remboursement) |

### Règles de remise
- Heures creuses (10-12 h/17-18 h/après 21:00) : 9e
- Réservation 30 minutes à l'avance : 95e (non cumulable avec un bon)

### Retrait du technicien
- Retrait possible le 20 de chaque mois, versement sous T+1 jour ouvré
- Prise en charge du retrait vers le portefeuille WeChat
- Commandes vérifiées non réglées : confirmation automatique sous 3 jours
- Le dossier client doit être complété sous 24 h, sinon pas de commission

### Récompense du client fidèle
- Seconde consommation chez le même technicien sous 30 jours → prime enregistrée
- Téléchargement de la photo d'hygiène après le service

### Règles de points
- Échange 1:100 contre une carte cadeau (configurable en back-office)
- Après inscription réussie d'un utilisateur recommandé et commande, obtention de points définis (paramétré en back-office)
