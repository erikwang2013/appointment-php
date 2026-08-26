# Documentation de l'API
> **Languages**: [中文](../API.md) · [English](../en/API.md) · [한국어](../ko/API.md) · [Русский](../ru/API.md) · [Deutsch](../de/API.md) · [Español](../es/API.md) · [Português](../pt/API.md) · [हिन्दी](../hi/API.md) · [العربية](../ar/API.md) · [বাংলা](../bn/API.md) · [Bahasa Indonesia](../id/API.md) · [日本語](../ja/API.md)

## Vue d'ensemble

- **API métier** (service/) : `http://localhost:8787` — fournit les interfaces métier au mini-programme/à l'APP
- **API du back-office** (admin/) : `http://localhost:8787` — fournit les interfaces au frontend Flutter Web du back-office
- **Méthode d'authentification** : Bearer Token (JWT), en-tête `Authorization: Bearer <token>`
- **Gestion des versions** : la version de l'API est contrôlée par l'en-tête `API-Version: v1`, non reflétée dans l'URL. v1 par défaut
- **Encodage des ID** : tous les champs d'ID des requêtes/réponses sont encodés en hashids, masquant les ID réels de la base de données
- **Documentation OpenAPI** : générée avec `hg/apidoc`, séparée entre back-office et client

| Extrémité | Adresse de la documentation OpenAPI | Description |
|------|------|------|
| Back-office | `GET http://localhost:8787/api/docs` | Spécification complète de l'API du back-office (OpenAPI 3.0 JSON) |
| Client | `GET http://localhost:8787/api/docs` | Spécification complète de l'API métier (OpenAPI 3.0 JSON) |

Ces adresses peuvent être importées dans des outils comme Swagger UI pour consulter la documentation interactive.

- **Format de réponse générique** :

```json
{
  "code": 0,
  "message": "操作成功",
  "data": {}
}
```

Réponse paginée :
```json
{
  "code": 0,
  "message": "success",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

---

## I. API métier (service/ :8787)

### 1. Interfaces publiques (sans authentification)

#### 1.1 Code de vérification

**`POST /api/captcha/send`** — Envoi d'un code de vérification SMS

Requête :
```json
{
  "phone": "13800138000"
}
```
Réponse : `{"code":0,"message":"验证码已发送","data":null}`

Limite : 1 envoi maximum toutes les 60 secondes, code valable 5 minutes.

---

#### 1.2 Authentification

**`POST /api/auth/register`** — Inscription par numéro de téléphone

Requête :
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "abc123",
  "confirm_password": "abc123",
  "referral_code": "A1B2C3D4"
}
```
Réponse :
```json
{
  "code": 0,
  "message": "注册成功",
  "data": {
    "token": "eyJhbGciOi...",
    "user": {
      "id": "aB3xK9mQ",
      "phone": "138****8000",
      "nickname": "用户138****8000",
      "user_type": "customer",
      "active_role": "customer",
      "referral_code": "E5F6G7H8"
    }
  }
}
```

---

**`POST /api/auth/login`** — Connexion par mot de passe

Requête :
```json
{
  "phone": "13800138000",
  "password": "abc123"
}
```
Réponse : identique à l'inscription, contient le token et les informations utilisateur.

---

**`POST /api/auth/login-by-code`** — Connexion par code de vérification

Requête :
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```
Réponse : identique à la connexion. Un compte est créé automatiquement pour les utilisateurs non inscrits.

---

**`POST /api/auth/forget-password`** — Mot de passe oublié

Requête :
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "newpass123",
  "confirm_password": "newpass123"
}
```

---

**`POST /api/auth/refresh`** — Rafraîchissement du Token

En-tête : `Authorization: Bearer <ancien token>`
Réponse : `{"code":0,"data":{"token":"eyJhbGciOi..."}}`

---

#### 1.3 WeChat

**`POST /api/wechat/mini-login`** — Connexion au mini-programme

Requête : `{"code":"微信登录code"}`
Note : lors de la première connexion, il faut ensuite appeler `/api/wechat/phone` pour lier le numéro de téléphone.

---

**`POST /api/wechat/phone`** — Liaison du numéro de téléphone

Requête : `{"code":"微信手机号组件code"}`

---

**`POST /api/wechat/oa-login`** — Connexion au compte officiel

Requête : `{"code":"公众号授权code"}`

---

#### 1.4 Services publics

**`GET /api/common/config`** — Configuration publique

Réponse : contient les textes d'accords (accord utilisateur/accord de confidentialité/accord de service), les informations « À propos », le numéro de version.

---

**`GET /api/common/area`** — Liste des villes et régions

---

#### 1.5 Consultation des prestations

**`GET /api/service/categories`** — Liste des catégories

Paramètre : `?parent_id=0`

---

**`GET /api/service/items`** — Liste des prestations

Paramètres : `?category_id=&page=1&per_page=10&sort=sales`

---

**`GET /api/service/detail/{id}`** — Détail d'une prestation

La réponse contient : images/nom/prix/spécifications/durée/volume de ventes/liste des évaluations.

---

**`GET /api/service/products`** — Liste des produits

**`GET /api/service/stores`** — Liste des boutiques

Paramètres : `?lat=&lng=&city=`

---

#### 1.6 Consultation des techniciens

**`GET /api/technician/list`** — Liste des techniciens

Paramètres : `?lat=&lng=&service_id=&page=1`
Trié par distance croissante, retourne : avatar/nom/note/nombre de commandes/nombre de favoris/distance/heure d'ouverture la plus proche/disponibilité.

---

**`GET /api/technician/detail/{id}`** — Détail d'un technicien

La réponse contient : images/nom/présentation/note/distance/liste des prestations proposées/évaluations.

---

**`GET /api/technician/schedule/{id}`** — Planning du technicien

Paramètre : `?date=2026-05-26`
Retourne les créneaux réservables de cette date et leur disponibilité.

---

#### 1.7 Contenu

**`GET /api/content/banners`** — Bannières

Paramètre : `?position=home`

**`GET /api/content/articles`** — Liste des annonces/articles

Paramètres : `?type=announcement&page=1`

**`GET /api/content/article/{id}`** — Détail d'un article

---

#### 1.8 LBS

**`GET /api/lbs/nearby-stores`** — Boutiques à proximité

Paramètres : `?lat=&lng=&radius=5000`

**`GET /api/lbs/geocode`** — Géocodage inverse

Paramètres : `?lat=&lng=`

---

### 2. Interfaces utilisateur (authentification JWT requise)

Toutes les interfaces doivent porter l'en-tête `Authorization: Bearer <token>`

#### 2.1 Profil personnel

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/user/profile` | Récupération des informations personnelles |
| PUT | `/api/user/profile` | Mise à jour du surnom/avatar/sexe |
| POST | `/api/user/change-password` | Modification du mot de passe (old_password/new_password/confirm_password) |
| POST | `/api/user/change-phone` | Changement de téléphone (old_code/new_phone/new_code) |
| POST | `/api/user/cancel-account` | Suppression du compte (vérification du mot de passe requise) |
| POST | `/api/user/logout` | Déconnexion (token ajouté à la liste noire) |
| POST | `/api/user/switch-role` | Changement d'identité (role: customer/technician) |

Le passage en technicien nécessite un dossier de technicien déjà approuvé.

#### 2.2 Gestion des adresses

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/user/addresses` | Liste des adresses |
| POST | `/api/user/addresses` | Nouvelle adresse (contact_name/contact_phone/province/city/district/detail/lat/lng/is_default) |
| GET | `/api/user/addresses/{id}` | Détail d'une adresse |
| PUT | `/api/user/addresses/{id}` | Mise à jour d'une adresse |
| DELETE | `/api/user/addresses/{id}` | Suppression d'une adresse |

Le passage en adresse par défaut annule automatiquement les autres adresses par défaut.

#### 2.3 Favoris

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/user/favorites` | Liste des favoris (?type=service/technician) |
| POST | `/api/user/favorites` | Ajout d'un favori (target_type/target_id) |
| DELETE | `/api/user/favorites/{id}` | Retrait d'un favori |

#### 2.4 Retours d'expérience

`POST /api/user/feedback` — Soumission d'un retour (content + tableau images)

#### 2.5 Parrainage

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/user/referral` | Informations de parrainage (code de parrainage/nombre de recommandés/nombre de premières commandes/points obtenus) |
| GET | `/api/user/referral/qrcode` | QR code de parrainage (code de parrainage + lien d'invitation) |
| GET | `/api/user/referral/referred-users` | Liste des utilisateurs recommandés |
| GET | `/api/user/referral/earnings` | Détail des commissions de distribution (pagé : surnom/avatar du recommandé/numéro de commande/montant/heure d'émission) |

**Commission de distribution** : versée après la première commande completed du recommandé, montant = paid_amount × reward_rate (erik_system_config referral.reward_rate, défaut 0.05, repli sur la constante en cas de valeur invalide). Triple idempotence : verrou de ligne + contrôle rewarded_at non vide + re-vérification de la première commande ; crédit WalletTxn type=referral_reward.

#### 2.6 Transfert de points (tour 19)

| Méthode | Chemin | Description |
|------|------|------|
| POST | `/api/user/points/transfer` | Transfert de points (to_user_id hashid/points) |
| GET | `/api/user/points/transfers` | Enregistrements de transfert (?direction=sent/received&page=1) |

**Transfert de points** : décodage du hashid du destinataire + existence 404, vers soi-même 422, points 1-10000 422, solde SUM agrégé insuffisant 422, plafond journalier cumulé 10000 422. Protection contre la concurrence : verrou Redis NX points_transfer:{user} 30s → lockForUpdate sur les deux dernières écritures dans la transaction (user_id croissant pour éviter les interblocages de transferts croisés) → re-vérification du solde/plafond/destinataire sous verrou. Normalisation des écritures : côté émetteur type=consume/source=points_transfer négatif (balance=snapshot précédent-cet échange), côté destinataire type=earn/source=points_transfer positif avec expires_at (PointsExpiryTimer peut expirer normalement) ; après commit, notification interne au destinataire type='points_received' (échec uniquement warn).

#### 2.7 Préférences de notification (tour 19)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/user/notify-settings` | Consultation des interrupteurs de notification (5 types complets) |
| PUT | `/api/user/notify-settings` | Mise à jour groupée des interrupteurs (types: {service_reminder: 0/1, ...}) |

**Interrupteurs de notification** : table erik_user_notify_setting (clé unique composite user_id+type, ligne absente = activé par défaut). 5 types : service_reminder rappel de service / card_expiry rappel d'expiration (parapluie unifié cartes+bons) / points_expiry expiration des points / marketing marketing (réservé) / system système (non désactivable, PUT forcé à 1). Contrôle d'accès : notifySettingEnabled branché sur les 3 processus de minuteries ServiceReminderTimer/ExpiryReminderTimer/PointsExpiryTimer + mappage des scénarios d'événements d'abonnement (PAY/REFUND/VERIFIED/RESCHEDULE→system toujours envoyé, REMINDER→service_reminder, EXPIRY→card_expiry) ; quand un type est désactivé, notifications internes et messages d'abonnement sont sautés.

---

### 3. Interfaces technicien (JWT + identité technicien requises)

#### 3.1 Dossier du technicien

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/technician/profile` | Récupération du dossier du technicien |
| PUT | `/api/technician/profile` | Mise à jour du dossier (avatar/intro/real_name/gender/id_card/id_card_front/id_card_back) |

Le premier remplissage complet est considéré comme une demande d'adhésion, statut=pending en attente d'audit.

#### 3.2 Planning

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/technician/schedule` | Consultation du planning (?start_date=&end_date=) |
| PUT | `/api/technician/schedule` | Définition du planning (date/time_slots/status), chevauchement de créneaux 422 « en conflit avec un planning existant » |
| POST | `/api/technician/schedule/batch` | Planning par lots (tour 23) : période ≤ 7 jours + filtre weekdays, jours déjà planifiés ignorés, réponse created/skipped |

#### 3.3 Commandes du technicien

`GET /api/technician/orders` — Liste des commandes (?status=&page=1)

#### 3.4 Revenus

`GET /api/technician/earnings` — Aperçu des revenus (today_income/pending_settlement/balance + liste des flux)

#### 3.5 Retrait

`POST /api/technician/withdraw` — Demande de retrait (amount)
Règle : retrait possible le 20 de chaque mois, versement T+1, montant minimum/limitation aux centaines configurées en back-office.

**Réservation en cours (2026-08-26)** : lors de la demande, le solde est immédiatement déduit de la réserve en cours (pending/approved) ; avant l'approbation et le transfert, re-vérification settled − withdrawn − en cours ≥ montant du retrait ; les approbations concurrentes ne provoquent pas de double versement.

#### 3.6 Réponse aux évaluations (tour 18)

`POST /api/technician/review/reply/{order_id}` — Réponse du technicien à une évaluation (reply). Évaluation inexistante/non personnelle : 404 unifié (sans fuite d'existence) ; réponse existante 422 (refus idempotent sans écrasement) ; réponse vide 422. Après succès, notification interne à l'utilisateur (type='review_reply').

#### 3.6 Poste de travail

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/technician/work/today` | Liste des tâches du jour |
| GET | `/api/technician/work/records` | Enregistrements terminés paginés |
| POST | `/api/technician/work/{id}/start` | Début du service |
| POST | `/api/technician/work/{id}/complete` | Fin du service |

**Tâches du jour** : status ∈ [confirmed, serving], service_time aujourd'hui ou vide, retourne service_name/price/nickname/avatar.

**Enregistrements terminés** : status ∈ [serving, completed], triés par service_end_at décroissant, réponse paginée avec meta.

**Début/fin de service** : verrou de ligne + validation de la machine à états, opération idempotente. Le début écrit service_start_at ; la fin écrit service_end_at et envoie une notification interne. Codes d'erreur : non personnel 403, mauvais statut 422, hashid invalide 422.

---

### 4. Interfaces de commande (authentification JWT requise)

| Méthode | Chemin | Description |
|------|------|------|
| POST | `/api/order` | Création d'une commande (order_type/items/store_id/technician_id/service_time/coupon_id/user_coupon_id/promotion_id/remark) |
| GET | `/api/order/list` | Liste des commandes (?status=&page=1) |
| GET | `/api/order/detail/{id}` | Détail d'une commande |
| POST | `/api/order/cancel/{id}` | Annulation d'une commande (reason) |
| POST | `/api/order/pay/{id}` | Lancement du paiement (pay_channel: wechat/balance, use_points: réduction en points optionnelle) |
| POST | `/api/order/refund/{id}` | Demande de remboursement |
| POST | `/api/order/verify/{id}` | Vérification (code: valeur du QR code) |
| POST | `/api/order/reschedule/{id}` | Report de réservation (new_service_time obligatoire/reason facultatif) |
| GET | `/api/order/logistics/{id}` | Suivi logistique (tour 19, commandes product) |
| POST | `/api/order/review/{order_id}` | Soumission d'une évaluation (rating 1-5/content/images) (enregistrement complété au tour 19) |
| POST | `/api/order/review/{order_id}/append` | Évaluation complémentaire (content/images séparés par des virgules) (tour 19) |

**Statuts de commande** : pending(en attente de paiement) → paid(payée) → confirmed(confirmée) → serving(service en cours) → completed(terminée)

**Lors de la création d'une commande** : verrou Redis SETNX du technicien pendant 3 minutes, libéré à la sortie de la page ou à l'expiration.

**Anti-falsification des prix (2026-08-26)** : les montants des articles de commande se réfèrent toujours aux enregistrements de la base (target_type=service interroge erik_service, product interroge erik_product), les prix envoyés par le client ne participent pas au calcul ; target_type inconnu 422 ; target_id doit être une valeur encodée en hashids (envoyer un id brut le décode à 0 → 422 « produit inexistant ou retiré ») ; les prix des achats groupés/flash sont également déterminés par la base.

**Règles de remboursement** : moins de 15 min après la commande ou >6 h avant le début : 100 % / ≤6 h : 90 % / commencé : 80 % / après confirmation du début : aucun.

**Réduction par bon** : user_coupon_id facultatif (hashid) à la création de la commande. Codes d'erreur : bon d'autrui 404, seuil non atteint/expiré/retiré/déjà utilisé 422, hashid invalide 422. Réduction en deux temps : à la commande, PriceCalculator.applyCoupon ne fait qu'une validation en lecture et calcule le montant de réduction écrit dans discount_amount ; après paiement réussi, consume passe le bon à used ; au remboursement, restoreCouponAndCard restitue de manière idempotente.

**Paiement et remboursement par solde** : envoyer `pay_channel: "balance"` dans le corps de la demande de paiement pour utiliser le solde du portefeuille ; les remboursements WeChat et les remboursements de solde créditent tous deux le solde du portefeuille.

**Réduction en points** : `use_points` (entier) facultatif dans le corps de la demande de paiement. Validation SUM agrégée du solde de points (la colonne balance de erik_user_points est un snapshot incrémental, ne pas l'utiliser directement comme solde), réduction = floor(use_points / config('app.points_rate', 100)) yuans, montant réel = montant dû − réduction (minimum 0.01, si la réduction dépasse le dû, le dû est réduit à 0 sans gaspiller de points). En cas de succès, écriture d'un flux de consommation type=consume/source=points_offset (idempotent, pas de double déduction en cas de nouvelle tentative). Solde insuffisant 422.

**Restitution des points** : à l'annulation/au remboursement, les points consommés en points_offset sont restitués (type=earn/source=points_refund) : annulation en totalité, remboursement au prorata, idempotent sur 5 points de branchement (refundOffsetPoints).

**Commande d'achat groupé (tour 16)** : promotion_id facultatif (hashid) à la création de la commande. Validation : uniquement type group_buy, activité dans sa période de validité, l'appelant est participant, pas complet (groupe constitué verrouillé 422), la prestation de la commande correspond à l'activité ; prix groupé = prix d'origine × discount_percent/100, bons/cartes à forfait/points désactivés en cumul (tout envoi de l'un d'eux → 422). La commande stocke promotion_id/participant_id ; le paiement réutilise entièrement `POST /api/order/pay/{id}`, avec au pay une évaluation paresseuse de la clôture de l'activité (expirée sans groupe constitué) → la commande est automatiquement annulée et le verrou du technicien libéré.

**Commande flash (tour 18, mis hors ligne)** : ~~promotion_id (type flash_sale) à la création de la commande~~ — depuis 2026-08, l'ancien canal promotionnel FLASH_SALE est supprimé, la branche promotionnelle de store() ne conserve que l'achat groupé GROUP_BUY (promotion non groupé 422) ; le flash passe unifié par le canal `/api/seckill` du tour 24 (seckill_id injecté dans la transaction de store avec verrou de ligne sur le stock), PromotionController::index filtre flash_sale, show/join lui retourne 400, la constante `Promotion::TYPE_FLASH_SALE` est conservée pour la compatibilité des données historiques.

**Report de réservation (tour 17)** : `POST /api/order/reschedule/{id}` avec new_service_time (obligatoire) + reason (facultatif), changement d'horaire avec le même technicien. Règles : commande personnelle uniquement (non personnel 404), uniquement type appointment et statut pending/paid/confirmed modifiable (autres 422), ≥ 6 heures avant le début du service d'origine (aligné sur la fenêtre de remboursement complet) pour reporter. Protection contre la concurrence : B1 order_lock (même famille d'exclusion mutuelle que pay/cancel/refund) → verrou Redis SETNX EX 180 du technicien sur le nouveau créneau (prévention des surventes en report concurrent) → relecture sous verrou de ligne dans la transaction + B2 validation DB des conflits de planning (en excluant la commande en cours) → mise à jour de service_time + enregistrement erik_order_reschedule → libération du verrou de l'ancien créneau, le nouveau verrou étant détenu par cette commande → message d'abonnement SCENE_RESCHEDULE (dégradé en notification interne si non configuré). En cas d'échec, la transaction est annulée et le verrou du nouveau créneau est également libéré.

**Suivi logistique (tour 19)** : `GET /api/order/logistics/{id}` — uniquement les commandes product personnelles (non personnel/non produit/non expédié : 404 unifié). Lecture du JSON order.remark (shipping_company/tracking_no/shipped_at, écrit à l'expédition par admin MallOrderController::ship()), double analyse parseShippingInfo/parseReceiver pour les anciens formats ; numéro de téléphone du destinataire masqué 138****5678.

**Évaluation (tour 19)** : `POST /api/order/review/{order_id}` soumission d'une évaluation (rating obligatoire 1-5, content/images facultatifs) : non personnel 404, non completed 422, évaluation en double 400. `POST /api/order/review/{order_id}/append` évaluation complémentaire (content obligatoire, images séparées par des virgules) : évaluation inexistante/non personnelle : 404 unifié, non completed 422, complément en double 422, contenu vide 422 ; en cas de succès, écriture de append_content/append_images(JSON)/append_at et notification interne au technicien type='review_append', la réponse expose le champ append.

### 4.1 Interfaces S.A.V. (authentification JWT requise)

| Méthode | Chemin | Description |
|------|------|------|
| POST | `/api/aftersales` | Demande de S.A.V. (order_id hashid/type: refund|exchange/reason), validation commande personnelle 404, uniquement statut paid+completed 422, S.A.V. en cours sur la même commande dédoublonnée 422 |
| GET | `/api/aftersales` | Liste de mes demandes de S.A.V. (?status=&page=1&limit=) |
| GET | `/api/aftersales/{id}` | Détail d'une demande de S.A.V. (contrôle de propriété 404) |

**Statuts de S.A.V.** : pending(en attente d'audit) → approved(approuvée) / rejected(rejetée). approved ne fait que faire évoluer le statut, l'action de remboursement réutilise `POST /api/order/refund/{id}`.

---

### 4.2 Interfaces d'achat groupé/promotion (authentification JWT requise ; FLASH_SALE mis hors ligne)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/promotions` | Liste des activités (?type=group_buy ; flash_sale filtré et non retourné) |
| GET | `/api/promotions/{id}` | Détail d'une activité (nombre de participants/groupe constitué ; type flash_sale 400) |
| GET | `/api/promotions/{id}/participants` | Liste des participants |
| POST | `/api/promotions/join/{id}` | Participation à l'activité (complété au tour 15 : réponse avec discount_percent/original_price/group_price ; type flash_sale 400) |

**Règles de participation** : groupe group_buy complet (≥min_people) verrouillé, nouvelle participation après constitution 422 ; clôture paresseuse à l'expiration sans groupe complet (statut mis à 0 à show/join). Après join, commander au prix groupé, voir « Commande d'achat groupé (tour 16) ». Le flash ne passe plus par ce canal, voir « 24. Interfaces de flash ».

---

### 5. Interfaces marketing (authentification JWT requise)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/marketing/coupons` | Liste des bons (?status=available/used/expired) |
| POST | `/api/marketing/coupons/receive` | Réception d'un bon (coupon_id) |
| GET | `/api/marketing/cards` | Liste des cartes de membre |
| POST | `/api/marketing/cards/buy` | Achat d'une carte de membre (card_id) |
| GET | `/api/marketing/cards/my` | Liste de mes cartes à forfait |
| POST | `/api/marketing/cards/use` | Utilisation d'une carte à forfait (user_card_id/service_id/remark?) |
| GET | `/api/marketing/gift-cards` | Liste des cartes cadeaux |
| GET | `/api/marketing/gift-cards/my` | Mes cartes cadeaux (enregistrements redeem) |
| POST | `/api/marketing/gift-cards/redeem` | Échange d'une carte cadeau (le type cash crédite le solde du portefeuille après échange) |
| GET | `/api/marketing/points` | Flux de points (?type=earn/use/expire&source=order/referral/gift_card/check_in/admin) |
| GET | `/api/marketing/points-exchange` | Liste des produits d'échange à points (en ligne + stock restant en temps réel + quantité déjà échangée) |
| POST | `/api/marketing/points-exchange/{id}` | Échange (type=coupon émission de bon / wallet crédit / gift_card retour de code) |
| POST | `/api/marketing/coupons/transfer` | Génération d'un code de transfert (user_coupon_id : code unique de 8 caractères/valable 7 jours) |
| POST | `/api/marketing/coupons/claim` | Réclamation du bon transféré (code) |
| GET | `/api/marketing/coupons/transfers` | Enregistrements de transfert (émis pending/claimed/expired + reçus claimed) |

**Carte à forfait** : cards/my retourne card_id/name/type/services/total_times/used_times/remaining_times/start_at/end_at/status (calcul en temps réel). En cas d'utilisation réussie, retourne {order_id, usage_id, remaining_times} ; codes d'erreur : hashid invalide 422, nombre d'utilisations insuffisant 422, expirée 400, non personnelle 404, anti-double soumission Redis 400.

**Carte cadeau** : gift-cards/my retourne les enregistrements redeem (type/amount/gift_name/status/used_at).

**Règles de points** : flux paginé, filtre type (earn/use/expire), filtre source (order/referral/gift_card/check_in/admin). Pointage de présence : points (CheckIn, type=earn) ; consommation : points floor(paid_amount×1), émis à la vérification et idempotents ; remboursement : restitution au prorata.

**Expiration des points (tour 17)** : colonne erik_user_points.expires_at (config points.expiry_days, défaut 365 jours, ≤0 jamais expiré), toutes les écritures earn enregistrent la date de validité ; PointsExpiryTimer, processus planifié, analyse par curseur les lignes earn expirées toutes les 60 s, écrit des lignes de déduction négatives type=expire (source=expiry + order_id remontant au flux d'origine, triple idempotence) + notification interne agrégée « X de vos points ont expiré » ; le solde disponible SUM inclut les lignes expire négatives, les points expirés ne peuvent plus être utilisés pour la réduction/l'échange.

**Transfert de bons (tour 17)** : transfer valide que le bon appartient à l'utilisateur/est available/la définition du bon n'est pas expirée/le bon n'a pas déjà été transféré, génère un code de transfert unique de 8 caractères anti-confusion (index unique uk_code en secours), valable 7 jours. claim anti-abus : verrou Redis NX (coupon_transfer_claim:{code} 30s) + re-vérification sous verrou de ligne anti-double dépense, index unique uk_user_coupon limitant un même bon à un seul transfert, un bon transféré ne peut pas être re-transféré (le nouveau bon n'a pas d'enregistrement de transfert, bloqué naturellement), impossible de réclamer son propre bon transféré 422, le destinataire n'est pas le détenteur d'origine ; clôture paresseuse : expiré → status expired et le bon d'origine revient à available. Dans la transaction de claim : le bon d'origine passe à used + création d'un nouveau UserCoupon lié au destinataire (coupon_id inchangé, donc validité inchangée) + enregistrement passé à claimed.

---

### 6. Interfaces de notification (authentification JWT requise)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/notification` | Liste des notifications (?type=order/system&page=1) |
| PUT | `/api/notification/read/{id}` | Marquage comme lu |
| PUT | `/api/notification/read-all` | Tout marquer comme lu |

---

### 7. Interfaces du portefeuille (authentification JWT requise)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/wallet` | Solde du portefeuille + flux paginés |
| POST | `/api/wallet/recharge` | Création d'un bon de recharge (amount: yuans) |
| POST | `/api/wallet/recharge/{id}/pay` | Lancement du paiement du bon de recharge (WeChat) |
| POST | `/api/wallet/transfer` | Transfert de solde (to_user_id hashid/amount/remark facultatif/client_token facultatif) (tour 19) |
| GET | `/api/wallet/transfers` | Enregistrements de transfert (?direction=out/in&page=1) (tour 19) |
| GET | `/api/wallet/transfers/{id}` | Détail d'un transfert (visible uniquement par les deux parties, autrui 404) (tour 19) |

**Flux** : types wallet_txn : recharge / consume / refund / gift_card / referral_reward(commission de distribution) / referral_level2(commission niveau 2) / points_exchange(crédit d'échange de points), retour paginé.

**Recharge** : `POST /api/wallet/recharge` avec amount (yuans) crée un bon de recharge et retourne son hashid. `POST /api/wallet/recharge/{id}/pay` lance le paiement WeChat, la réponse contient sign_params (même schéma que le paiement de commande) ; le callback de paiement distingue bons de recharge et commandes par le préfixe R de out_trade_no.

**Paiement par solde** : envoyer `pay_channel: "balance"` dans le corps de la demande de paiement de commande pour utiliser le solde du portefeuille ; les remboursements WeChat et les remboursements de solde créditent tous deux le solde du portefeuille.

**Transfert de solde (tour 19)** : `POST /api/wallet/transfer` — décodage du hashid du destinataire + existence 404, vers soi-même 422, montant 0.01-1000/opération 422 (comparaison DECIMAL, float interdit), solde insuffisant 422, cumul journalier 5000 yuans 422. Concurrence/idempotence : verrou Redis NX wallet_transfer:{from} 30s sérialisant l'émetteur → dans la transaction, verrous lockForUpdate sur les deux portefeuilles par user_id croissant (ordre fixe anti-interblocage) → débit de l'émetteur + crédit du destinataire + double écriture WalletTxn (transfer_out/transfer_in avec snapshot balance_after) + enregistrement de transfert completed + notification interne au destinataire type='balance_received' (échec seulement journalisé). client_token facultatif : après succès, SETNX 24h anti-double soumission (les demandes échouées ne posent pas de token, peuvent être retentées).

---

### 8. Interfaces du poste de travail du responsable de boutique (authentification JWT requise)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/store-manager/overview` | Aperçu du jour (commandes du jour/chiffre d'affaires du jour/en cours/nombre de techniciens/vérifications) |
| GET | `/api/store-manager/orders` | Liste des commandes de la boutique (?status=&page=&limit=) |
| GET | `/api/store-manager/technicians` | Liste des techniciens (avec le planning du jour) |
| GET | `/api/store-manager/revenue` | Agrégation du chiffre d'affaires des 7 derniers jours |

**Isolation store_id** : requireStoreId() impose que l'utilisateur actuel soit lié à une boutique (erik_user.store_id), sans boutique 403 ; toutes les requêtes sont filtrées par store_id.

---

### 9. Interfaces de niveau de croissance (authentification JWT requise, tour 20)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/growth` | Aperçu de croissance actuel (balance/niveau/écart au niveau suivant/nom du niveau) |
| GET | `/api/growth/records` | Flux de croissance paginé (?page=&limit=) |
| GET | `/api/growth/levels` | Liste des niveaux (publique, sans connexion) |

**Crédits de croissance** : pointage de présence +10 ; évaluation soumise +20 (les compléments ne créditent pas) ; consommation floor(paid) 1 point par yuan (dans le callback de paiement, réutilisation de la re-vérification d'état, idempotent, les callbacks en double ne créditent pas deux fois).

### 10. Interfaces de facturation (authentification JWT requise, tour 20)

| Méthode | Chemin | Description |
|------|------|------|
| POST | `/api/invoices` | Demande de facture (order_id hashid/order_type: service=prestation/points_exchange=échange de points/order_type par défaut service ; montant et intitulé portés côté serveur, non falsifiables) |
| GET | `/api/invoices` | Liste des factures (?status=&page=) |
| GET | `/api/invoices/{id}` | Détail d'une facture (personnelle uniquement) |

**Anti-doublon** : clé unique uk_order_type(order_id, order_type), demande en double du même type pour la même commande 422 (avec capture MySQL 1062 en secours).

### 11. Interfaces de tickets du service client (authentification JWT requise, tour 20)

| Méthode | Chemin | Description |
|------|------|------|
| POST | `/api/tickets` | Soumission d'un ticket (title/content obligatoires) |
| GET | `/api/tickets` | Liste des tickets (?status=open/closed&page=) |
| GET | `/api/tickets/{id}` | Détail d'un ticket (personnel uniquement, autrui 404) |
| POST | `/api/tickets/{id}/close` | Clôture d'un ticket (personnel uniquement/open uniquement ; rating 1-5 facultatif pour la satisfaction, hors limites/non entier 422, absent → NULL compatible) |

### 12. Interfaces du calendrier de réservation (authentification JWT requise, tour 20)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/calendar/technician/{id}` | Vue mensuelle (?month=YYYY-MM) : time_slots du planning dépliés en créneaux horaires + exclusion des réservations existantes |
| GET | `/api/calendar/technician/{id}/day` | Vue journalière (?date=YYYY-MM-DD) : détail des créneaux réservables/déjà réservés/non réservables |

### 13. Interfaces d'intitulés de facture (authentification JWT requise, tour 21)

| Méthode | Chemin | Description |
|------|------|------|
| POST | `/api/invoice-titles` | Enregistrement d'un intitulé (title_type: personal/company ; company doit avoir tax_no ; même intitulé pour le même utilisateur 422 ; la première entrée devient par défaut) |
| GET | `/api/invoice-titles` | Liste des intitulés (par défaut en tête) |
| PUT | `/api/invoice-titles/{id}` | Modification d'un intitulé (personnel uniquement) |
| DELETE | `/api/invoice-titles/{id}` | Suppression d'un intitulé (personnel uniquement ; suppression du défaut → désignation automatique de la première entrée) |
| POST | `/api/invoice-titles/{id}/default` | Passage en défaut (mise à zéro des autres lignes du même utilisateur dans une transaction) |

**Liaison à la demande** : POST /api/invoices accepte title_id facultatif — l'intitulé est analysé et porté automatiquement vers invoice_title/tax_no/title_type, sans title_id le chemin de saisie manuelle est conservé.

### 14. Interfaces d'historique de navigation (authentification JWT requise, tour 21)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/browse-history` | Prestations récemment consultées (join nom/couverture/prix/prix d'origine de la prestation, trié par viewed_at décroissant, per_page défaut 15 max 50) |
| DELETE | `/api/browse-history/{item_id}` | Suppression d'une entrée (personnelle uniquement, invalide/autrui 404) |
| DELETE | `/api/browse-history` | Vidage de l'historique (personnel uniquement) |

**Moment d'enregistrement** : enregistrement automatique après accès réussi à l'interface de détail de prestation (sauté sans connexion ; une consultation répétée ne fait que rafraîchir viewed_at sans réinsérer).

### 15. Interfaces d'opérations de remise (tour 22)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/full-reduction-activities` | Liste des opérations de remise en vigueur (status=1 et dans la période de validité, triées par montant de réduction décroissant ; interface publique) |

**Règle de cumul à la commande** : la remise ne s'applique qu'aux commandes standard (sautée pour achats groupés/flash), le seuil (threshold) est jugé sur le montant dû après réduction par bon/carte à forfait, ordre de cumul **bon/carte à forfait → remise → remise de niveau** ; l'activité au plus grand montant de réduction est retenue ; le montant de réduction est intégré à discount_amount, la remarque est complétée par « Remise : X en dessous de Y » ; après remise, le paiement minimal est 0.01 yuan.

### 16. Export ICS de mes réservations (authentification JWT requise, tour 22)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/order/ics` | Export des commandes valides des 90 derniers jours (pending/paid/confirmed/serving) en iCal (RFC5545) |

**Sortie** : `Content-Type: text/calendar; charset=utf-8` + `Content-Disposition: attachment; filename="my-appointments.ics"`. VEVENT : UID=ID de commande, TZID=Asia/Shanghai, résumé « Réservation : nom du service » (dégradé en « Réservation » si absent), description (technicien/boutique/adresse, absents ignorés), LOCATION nom de la boutique ; texte échappé selon RFC5545 (\, \; \\ \n) + repli de ligne à 75 octets. Sans commande, retour d'un calendrier vide valide ; n'exporte que les commandes personnelles.

### 17. Interfaces de pointage des techniciens (authentification JWT requise, tour 22)

| Méthode | Chemin | Description |
|------|------|------|
| POST | `/api/technician/attendance/check-in` | Pointage d'arrivée (doublon du jour 422, index unique en secours contre la concurrence ; >10:00 marqué en retard) |
| POST | `/api/technician/attendance/check-out` | Pointage de départ (non arrivé/déjà parti 422, verrou de ligne anti-concurrence) |
| GET | `/api/technician/attendance` | Liste des pointages du mois + synthèse jours de présence/temps total/temps moyen (?month=YYYY-MM, invalide 422) |

### 18. Interfaces de conformité vie privée (authentification JWT requise, tour 22)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/privacy/data` | Export de données (groupes JSON personal/orders/points/wallet_txns/reviews/addresses/invoices ; les journaux serveur n'enregistrent que le téléphone masqué + le nombre) |
| POST | `/api/privacy/close-request` | Demande de suppression de compte (solde non nul / commandes non terminées / tickets ouverts 422 ; définit close_status=1 + close_requested_at) |
| POST | `/api/privacy/close-cancel` | Annulation de la demande de suppression (close_status 1→0) |
| POST | `/api/privacy/close-confirm` | Confirmation de suppression (72 h requises ; close_status=2 + close_at + anonymisation phone/nickname en user{id} + status=0) |

**Interception de connexion** : la connexion d'un compte avec close_status=2 retourne 403 « compte supprimé ».

### 19. Interfaces de dossier de santé utilisateur (authentification JWT requise, tour 23)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/health-profile` | Consultation de mon dossier de santé (objet vide sans dossier) |
| PUT | `/api/health-profile` | Création/mise à jour (upsert, un seul dossier par personne ; allergies/health_notes max 500 caractères, preferred_technician_id avec validation d'existence ; seuls les champs fournis sont mis à jour, réponse encodée en hashids) |
| DELETE | `/api/health-profile` | Suppression de mon dossier (personnel uniquement) |

Champs : allergies (antécédents d'allergie)/health_notes (remarques de santé)/preferred_technician_id (technicien préféré, nullable).

### 20. Interfaces de mot de passe de paiement du portefeuille (authentification JWT requise, tour 23)

| Méthode | Chemin | Description |
|------|------|------|
| POST | `/api/wallet/pay-password/set` | Définition du mot de passe de paiement (6 chiffres `\d{6}` ; déjà défini : ancien mot de passe requis, sinon 422) |
| POST | `/api/wallet/pay-password/verify` | Vérification du mot de passe de paiement (retourne un booléen correct/incorrect, non enregistré) |
| POST | `/api/wallet/pay-password/check` | Vérification de l'état (set: true/false) |

Stockage : hachage password_hash() + pay_password_set_at, jamais de texte clair.

### 21. Interfaces de chronologie des statuts de commande (authentification JWT requise, tour 23)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/order/{id}/timeline` | Chronologie des changements de statut de commande (ordre décroissant ; personnelle uniquement, commande d'autrui 404 sans fuite d'existence) |

Points d'enregistrement : soumission/paiement (callback WeChat markOrderPaid, point de consommation unique)/annulation/confirmation technicien/demande de remboursement/remboursement approuvé/début du service/fin du service/annulation automatique par dépassement de délai/opération back-office (operator=admin) — 8 types de changements.

### 22. Interfaces de la roue de la fortune à points (authentification JWT requise, tour 23)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/wheel/prizes` | Liste des prix de la roue (champs sensibles weight/stock masqués) |
| POST | `/api/wheel/spin` | Un tirage (Redis NX + verrou de ligne anti-concurrence ; tirage pondéré random_int ; points→flux earn avec date d'expiration, solde→crédit lockForUpdate, bon→émission manuelle pending, rien→lose ; client_token idempotent) |
| GET | `/api/wheel/records` | Mes enregistrements de tirage (pagés) |

### 23. Interfaces du mode invité (tour 24)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/guest/home` | Agrégation d'accueil (bannières/annonces/catégories de prestations/prestations populaires, cache Redis svc:guest:home 300s) |
| GET | `/api/guest/services` | Liste des prestations (?category_id=hashid&sort=newest|sales|price&page/per_page≤50) |
| GET | `/api/guest/services/{id}` | Détail d'une prestation (inexistante 404) |
| GET | `/api/guest/stores` | Liste des boutiques |
| GET | `/api/guest/technicians` | Liste des techniciens (uniquement approuvés ; ?service_id=hashid filtrage ; note décroissante) |

Entrée de navigation sans connexion, sans authentification (middleware ApiVersion uniquement).

### 24. Interfaces de flash (authentification JWT requise, tour 24)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/seckill` | Liste des activités flash (status=1 et dans la fenêtre de temps ; contient la quantité vendue = nombre de commandes erik_order.seckill_id, stock restant) |
| GET | `/api/seckill/{id}` | Détail d'une activité (state=not_started/ongoing/ended) |
| POST | `/api/seckill/{id}/buy` | Commande flash (client_token idempotent + Redis NX 30s anti-concurrence + validation d'activité ; plus de pré-déduction du stock) |

**Règles de commande (depuis 2026-08-26)** : le stock est déduit de manière unifiée par verrou de ligne dans la transaction `/api/order store()`, buy ne fait que la validation d'entrée/l'idempotence ; prix flash = seckill_price (déterminé par la base), pas de cumul de bons/points/cartes de membre ; l'annulation de commande ne restitue pas le stock ; appeler directement `/api/order` avec seckill_id déduit également le stock.

### 25. Interfaces de vérification de version APP (tour 24)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/api/app/version?platform=android|ios` | Vérification de la dernière version (platform invalide 422 ; sans version, objet vide ; interface publique) |

Réponse : id/platform/version_code/version_name/force_update (1=forcé)/changelog/download_url.

---

## II. API du back-office (admin/ :8787)

En-têtes : `Authorization: Bearer <admin_token>`, `API-Version: v1`

### Tableau de bord

**`GET /admin/dashboard`** — Données du tableau de bord

Réponse : user_count / order_count / technician_count / today_revenue + données graphiques (volume de commandes/montants/nouveaux utilisateurs/activité)

### Gestion des utilisateurs

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/user` | Liste des utilisateurs (?keyword/status/page/per_page) |
| POST | `/admin/user` | Nouvel utilisateur |
| GET | `/admin/user/{id}` | Détail d'un utilisateur |
| PUT | `/admin/user/{id}` | Modification d'un utilisateur |
| DELETE | `/admin/user/{id}` | Suppression d'un utilisateur |
| POST | `/admin/user/batch/destroy` | Suppression groupée |
| POST | `/admin/user/batch/status` | Activation/désactivation groupée |

### Gestion des cartes de membre

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/member-cards` | Liste des cartes (?keyword/status/page/per_page) |
| GET | `/admin/member-cards/{id}` | Détail d'une carte |
| POST | `/admin/member-cards` | Nouvelle carte (validation JSON services) |
| PUT | `/admin/member-cards/{id}` | Mise à jour de la carte/mise en ligne ou hors ligne |
| DELETE | `/admin/member-cards/{id}` | Suppression d'une carte (refusée si des utilisateurs la détiennent) |

ID de permission : 365-369.

### Poste de travail des boutiques (tour 15)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/stores/workbench-overview` | Aperçu du poste de travail des boutiques (?store_id=hashid : commandes du jour/chiffre d'affaires du jour/en cours/nombre de techniciens/vérifications du jour, mêmes critères que côté service) |
| GET | `/admin/orders` | Filtre store_id ajouté à la liste des commandes (décodage hashid) |

ID de permission : 372.

### Produits d'échange à points (tour 16)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/points-exchange-goods` | Liste des produits (?keyword/status/page/per_page) |
| POST | `/admin/points-exchange-goods` | Nouveau produit (type=coupon/gift_card/wallet ; coupon avec hashid, wallet/gift_card avec montant en yuans) |
| PUT | `/admin/points-exchange-goods/{id}` | Mise à jour d'un produit |
| DELETE | `/admin/points-exchange-goods/{id}` | Suppression d'un produit |
| POST | `/admin/points-exchange-goods/{id}/toggle-status` | Bascule en ligne/hors ligne |
| GET | `/admin/points-exchange-goods/{id}/exchanges` | Liste des enregistrements d'échange (avec le numéro de téléphone de l'utilisateur + snapshot result) |

ID de permission : 373-378.

### Enregistrements de commission (tour 16)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/referral-rewards` | Enregistrements de commission (?keyword=&page=&limit=, uniquement les commissions versées, filtrage par surnom ou téléphone du parrain/recommandé, encodage hashids) |

ID de permission : 379.

### Niveaux de technicien (tour 17)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/technician-tiers/logs` | Journal des changements de niveau (join nom du technicien et ancien/nouveau niveau, encodage hashids, paginé) |

ID de permission : 380.

**Évaluation automatique** : TierRatingService::evaluate calcule en temps réel (nombre de commandes erik_order completed + note moyenne des évaluations, arrondie à 1 décimale) et réécrit profile.order_count/rating, appariement selon erik_technician_tier_config (min_orders/min_rating) du plus haut au plus bas, sans correspondance → niveau le plus bas. Uniquement promotion, pas de déclassement (le déclassement affecte le taux de commission et le coefficient de prix, géré manuellement en back-office ; allowDowngrade=true pour la réévaluation manuelle) ; idempotent (niveau identique → simple synchronisation des statistiques) ; les changements sont enregistrés dans erik_technician_tier_log + notification interne. Points de déclenchement : WorkController::complete / écriture d'évaluation ReviewController / lecture paresseuse du profil ProfileController.

### Consultation des réponses aux évaluations (tour 18)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/reviews/{id}/reply` | Détail de la réponse à l'évaluation (decodeId → find → 404 → sortie decorate ; sans réponse reply='', reply/replied_at exposés via toArray ; route statique avant resource) |

ID de permission : 381 (slug 'get.admin/reviews/{id}/reply').

### Gestion des factures (tour 20)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/invoices` | Liste des factures (?status=pending/issued/rejected&page=) |
| POST | `/admin/invoices/{id}/issue` | Émission (invoice_no obligatoire, status→issued + issued_at ; idempotent : déjà émise 422) |
| POST | `/admin/invoices/{id}/reject` | Rejet (reject_reason obligatoire, status→rejected ; seul pending peut être rejeté) |

ID de permission : 382 liste / 383 émission / 384 rejet.

### Gestion des tickets (tour 20)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/tickets` | Liste des tickets (?status=&page=, route statique avant resource pour éviter l'ombre) |
| POST | `/admin/tickets/{id}/reply` | Réponse à un ticket (content obligatoire, écriture reply_content/replied_at, le ticket repasse en open) |
| GET | `/admin/tickets/satisfaction` | Synthèse de satisfaction (tour 21) : total/rated_count/unrated_count/average à 1 décimale/distribution 1-5 étoiles avec zéros pour les étoiles manquantes ; route statique avant resource |

ID de permission : 385 réponse aux tickets / 387 consultation des tickets / 388 statistiques de satisfaction.

### Modération des images d'évaluation (tour 21)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/review-audit` | Liste des évaluations avec images (JSON_LENGTH(images)>0, ?status=visible/hidden&page=, join surnom de l'utilisateur et nom du technicien, ID encodés en hashids) |
| POST | `/admin/review-audit/{id}/hide` | Masquage d'une évaluation (seul visible peut être masqué, sinon 422 ; une fois masquée, la liste des évaluations du technicien côté client devient automatiquement invisible) |
| POST | `/admin/review-audit/{id}/restore` | Restauration d'une évaluation (seul hidden peut être restauré, sinon 422) |

ID de permission : 389 liste / 390 masquage / 391 restauration.

### Enregistrements de commission niveau 2 (tour 20)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/referral-level2` | Enregistrements de commission niveau 2 (join surnoms du parrain niveau 1 et niveau 2, paginé) |

ID de permission : 386. Règle de versement : après paiement de la commande, versement au parrain du parrain niveau 1 de paid×level2_rate (configuration système referral.level2_rate, défaut 0.02), clé unique uk_order_referred anti-doublon.

### Gestion des pointages (tour 22)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/attendance` | Enregistrements de pointage (?date=YYYY-MM&name=nom du technicien&page= ; join real_name, ID encodés en hashids) |
| GET | `/admin/attendance/stats` | Statistiques groupées par technicien (jours de présence/temps total/temps moyen ; ?date=YYYY-MM, invalide 422) |

ID de permission : 392 liste / 393 statistiques.

### Gestion des opérations de remise (tour 22)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/full-reduction-activities` | Liste des activités (paginée) |
| POST | `/admin/full-reduction-activities` | Nouvelle activité (threshold/reduction/title/status/start_at/end_at) |
| PUT | `/admin/full-reduction-activities/{id}` | Modification |
| POST | `/admin/full-reduction-activities/{id}/toggle-status` | Mise en ligne/hors ligne |
| DELETE | `/admin/full-reduction-activities/{id}` | Suppression (avec confirmPassword) |

ID de permission : 396 liste / 397 création / 398 modification / 399 bascule / 400 suppression (un enregistrement de permission correspond à un slug method.path, d'où 5 routes pour 5 enregistrements).

### Enregistrements de partage des profits (tour 22)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/profit-sharing` | Enregistrements de partage des profits (leftJoin numéro de commande/surnom du technicien, ?status&order_no&technician_name&page=, encodage hashids) |

ID de permission : 394. Logique serveur : erik_system_config groupe=profit_sharing (enabled/receiver_ratio) ; non activé → dégradé disabled avec simple journalisation ; activé → demande automatique de partage après paiement réussi (montant = paiement réel×receiver_ratio défaut 0.7, même commande pending/success sautée idempotente) ; sans justificatifs, pas d'HTTP, la structure de la demande est journalisée.

### Gestion de la roue de la fortune (tour 23)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/lucky-wheel` | Liste des prix de la roue (avec weight/stock, paginée) |
| POST | `/admin/lucky-wheel` | Nouveau prix (nom/type points/balance/coupon/none/poids/stock/image) |
| GET/PUT | `/admin/lucky-wheel/{id}` | Détail / Modification |
| DELETE | `/admin/lucky-wheel/{id}` | Suppression |
| POST | `/admin/lucky-wheel/{id}/toggle-status` | Mise en ligne/hors ligne |
| GET | `/admin/lucky-wheel/records` | Enregistrements de tirage (?status&page=, avec surnom de l'utilisateur/nom du prix) |

ID de permission : 401-406. Les routes statiques `/lucky-wheel/records` et `/lucky-wheel/{id}/toggle-status` sont enregistrées avant resource pour éviter l'ombre de {id}.

### Gestion des récompenses client fidèle (tour 24)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/return-customer/config` | Consultation de la configuration (interrupteur enabled / ratio) |
| PUT | `/admin/return-customer/config` | Mise à jour de la configuration (enabled in:0,1 ; ratio between:0.01,1) |
| GET | `/admin/return-customer/rewards` | Liste des enregistrements de récompense (?keyword nom du technicien/numéro de commande/surnom de l'utilisateur, type=return_customer paginé) |

ID de permission : 412-414. Règle de récompense : 2e consommation (commande terminée) du même utilisateur chez le même technicien dans les 30 jours → prime = paiement réel × ratio (défaut 0.05), enregistrée dans erik_technician_earnings (type=return_customer, status=pending), réglée avec la chaîne de règlement des commissions ; idempotent, pas de double versement pour la même commande.

### Gestion des opérations flash (tour 24)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/seckill` | Liste des activités (paginée) |
| POST | `/admin/seckill` | Nouvelle activité (name/service_id/seckill_price/original_price/stock/start_at/end_at) |
| GET | `/admin/seckill/{id}` | Détail d'une activité |
| PUT | `/admin/seckill/{id}` | Modification |
| DELETE | `/admin/seckill/{id}` | Suppression |
| POST | `/admin/seckill/{id}/toggle-status` | Mise en ligne/hors ligne |
| GET | `/admin/seckill/{id}/orders` | Liste des commandes flash |

ID de permission : 407-411, 420. Quantité vendue = nombre de commandes erik_order.seckill_id ; déduction du stock sous verrou de ligne, blocage à l'épuisement.

### Gestion des versions APP (tour 24)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/versions` | Liste des versions |
| POST | `/admin/versions` | Nouvelle version (platform/version_code/version_name/force_update/changelog/download_url/status) |
| PUT | `/admin/versions/{id}` | Modification |
| DELETE | `/admin/versions/{id}` | Suppression |

ID de permission : 416-419. L'interface de vérification de mise à jour /api/app/version prend la version la plus récente (updated_at/id maximal) parmi status=1.

### Export des plannings (tour 24)

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/technician-schedule/export` | Export CSV des plannings (BOM UTF-8, ouvert directement dans Excel ; start_date/end_date obligatoires avec écart ≤31 jours ; technician_id facultatif en hashid) |

ID de permission : 415. Colonnes : ID du technicien/nom du technicien/date/détail des créneaux (time_slots JSON analysé en « 09:00-12:00, 14:00-18:00 »).

### Rôles et permissions

| Méthode | Chemin | Description |
|------|------|------|
| GET/POST/PUT/DELETE | `/admin/role` | CRUD des rôles |
| GET/POST/PUT/DELETE | `/admin/permission` | CRUD des permissions (structure arborescente) |

### Configuration système

| Méthode | Chemin | Description |
|------|------|------|
| GET | `/admin/config` | Liste des configurations |
| POST | `/admin/config` | Nouvelle configuration (group/key/value/type/description) |
| PUT | `/admin/config/{id}` | Modification d'une configuration |
| DELETE | `/admin/config/{id}` | Suppression d'une configuration |

### Journaux d'opérations

**`GET /admin/log`** — Consultation des journaux

Paramètres : `?user_id/action/source/start_date/end_date/page`

Champ `source` : web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### Export

| Méthode | Chemin | Description |
|------|------|------|
| POST | `/admin/export/excel` | Export Excel (type: users/technicians/orders/finance). Masquage automatique des champs sensibles |
| POST | `/admin/export/pdf` | Export PDF du panneau (type: dashboard) |

### Téléchargement de fichiers

**`POST /admin/upload`** — Téléchargement de fichiers (multipart/form-data)

### Espace personnel

| Méthode | Chemin | Description |
|------|------|------|
| PUT | `/admin/profile` | Modification du profil |
| PUT | `/admin/profile/password` | Modification du mot de passe |
| POST | `/admin/profile/logout` | Déconnexion |

### Import

**`POST /admin/import/users`** — Import groupé d'utilisateurs (Excel)

### Supervision

| Méthode | Chemin | Authentification | Description |
|------|------|------|------|
| GET | `/health` | Aucune | Vérification de santé |
| GET | `/metrics` | Aucune | Métriques Prometheus |
| GET | `/.well-known/security.txt` | Aucune | Contact de sécurité (RFC 9116) |
| GET | `/api/docs` | Aucune | Documentation API |

---

## III. Notes générales

### Codes d'erreur

| code | Description |
|------|------|
| 0 | Succès |
| 401 | Non connecté ou token expiré |
| 403 | Aucune permission |
| 404 | Ressource inexistante |
| 422 | Échec de validation des paramètres |
| 429 | Requêtes trop fréquentes |

### Encodage des ID

- Tous les champs `id` et `*_id` des réponses API sont encodés en hashids
- Les paramètres `id` envoyés dans les requêtes doivent également utiliser le format hashids
- Le frontend utilise directement les chaînes encodées, sans décodage manuel

### Masquage des numéros de téléphone

Format des numéros de téléphone dans les réponses : `138****8000`. Traitement similaire à l'export Excel.

### Chiffrement des données

- Couche API : les champs sensibles des réponses sont chiffrés via `erikwang2013/encryption`
- Couche DB : numéros de téléphone/pièces d'identité/ID WeChat, etc., chiffrés/déchiffrés automatiquement via `erikwang2013/encryptable`

### Configuration des variables d'environnement

| Variable | Description |
|------|------|
| WECHAT_SUBSCRIBE_TEMPLATE_ID | ID du modèle de message d'abonnement de rappel de réservation |
| WECHAT_SUBSCRIBE_TEMPLATE_PAID | ID du modèle de message d'abonnement de paiement réussi |
| WECHAT_SUBSCRIBE_TEMPLATE_REFUND | ID du modèle de message d'abonnement de remboursement |
| WECHAT_SUBSCRIBE_TEMPLATE_VERIFIED | ID du modèle de message d'abonnement de vérification |
| WECHAT_SUBSCRIBE_TEMPLATE_REMINDER | ID du modèle de message d'abonnement de rappel avant début de service (tour 18) |
| WECHAT_SUBSCRIBE_TEMPLATE_EXPIRY | ID du modèle de message d'abonnement de rappel d'expiration carte de membre/bon (tour 18) |

En l'absence de modèle de message d'abonnement configuré, dégradation automatique vers la notification interne.

**Scénarios de messages d'abonnement** : SCENE_PAY(paiement réussi) / SCENE_REFUND(remboursement reçu) / SCENE_VERIFIED(vérification réussie) / SCENE_RESCHEDULE(report réussi) / SCENE_REMINDER(rappel avant début de service, tour 18) / SCENE_EXPIRY(rappel d'expiration, tour 18). push_sent_at n'est écrit qu'en cas de push réussi, nouvel essai au tour suivant en cas d'échec.

**Notification de recharge reçue (tour 18)** : le callback de recharge WeChat (numéro de bon préfixé R) écrit dans la transaction une notification interne type='wallet_recharge' « Vous avez rechargé ¥X.XX » ; idempotence réutilisée du callback (déclenché uniquement au premier passage pending→paid), commit atomique avec le changement d'état, un échec d'écriture ne bloque pas le flux principal.
