# Diagrammes de cycle de vie

## 1. Cycle de vie d'une commande (machine à états)

```mermaid
stateDiagram-v2
    [*] --> pending: L'utilisateur soumet la commande

    pending --> paid: Paiement réussi<br/>(WeChat / solde / gratuit : trois canaux)

    pending --> cancelled: Annulation par expiration (15 min)<br/>annulation par l'utilisateur

    paid --> confirmed: Le technicien confirme la prise en charge<br/>consommation atomique du callback<br/>déduction du coupon / de la carte à forfait
    paid --> cancelled: Annulation par l'utilisateur<br/>(selon les règles de remboursement)
    paid --> refunding: L'utilisateur demande un remboursement
    paid --> aftersale: Demande après-vente<br/>(remboursement / échange)

    confirmed --> serving: Début du service

    serving --> completed: Service terminé + validation<br/>déduction de la carte à forfait

    serving --> refunding: Remboursement exceptionnel<br/>(80 %)

    completed --> reviewed: Évaluation de l'utilisateur
    completed --> aftersale: Demande après-vente<br/>(remboursement / échange)

    refunding --> refunded: Approbation<br/>reversement d'origine / recharge du solde<br/>restitution du coupon + crédit de points
    refunding --> paid: Rejet de l'approbation

    aftersale --> refunded: Approbation - remboursement<br/>réutilise l'interface de remboursement de commande
    aftersale --> paid: Rejet de l'approbation
    aftersale --> [*]: Approbation - échange<br/>cycle de statut terminé

    reviewed --> [*]
    cancelled --> [*]
    refunded --> [*]

    note right of pending: Verrouillage du technicien pendant 3 minutes
    note right of refunding: Approbation à deux niveaux : gérant → comptable
```

## 2. Cycle de vie d'une carte de membre

```mermaid
stateDiagram-v2
    [*] --> active: L'utilisateur achète une carte de membre

    active --> used_up: Épuisement des séances de la carte à forfait

    active --> expired: Expiration (carte mensuelle / VIP)

    active --> frozen: Gel pour non-respect des règles (action back-office)

    frozen --> active: Dégel

    used_up --> [*]
    expired --> [*]
```

## 3. Cycle de vie de l'inscription d'un technicien

```mermaid
stateDiagram-v2
    [*] --> applied: Soumission de la demande d'inscription

    applied --> approved: Approbation par le back-office
    applied --> rejected: Rejet de la demande

    rejected --> applied: Modification puis nouvelle soumission

    approved --> active: Première connexion à l'espace technicien

    active --> suspended: Suspension pour non-respect des règles
    suspended --> active: Rétablissement
    active --> banned: Bannissement définitif

    banned --> [*]
```

## 4. Cycle de vie d'un coupon

```mermaid
stateDiagram-v2
    [*] --> draft: Création par le back-office

    draft --> published: Mise en ligne

    published --> claimed: Obtention par l'utilisateur

    claimed --> used: Utilisation à la commande
    claimed --> expired: Dépassement de la durée de validité

    published --> ended: Stock épuisé / retrait à expiration

    used --> [*]
    expired --> [*]
    ended --> [*]
```

## 5. Cycle de vie d'un retrait de technicien

```mermaid
stateDiagram-v2
    [*] --> pending: Soumission de la demande de retrait

    pending --> approved: Approbation par le gérant
    pending --> rejected: Rejet de la demande

    rejected --> [*]: Retour

    approved --> processing: Confirmation par le comptable

    processing --> completed: Réception dans le portefeuille WeChat (T+1)

    completed --> [*]
```

## 6. Cycle de vie de l'authentification Token

```mermaid
stateDiagram-v2
    [*] --> issued: Connexion réussie de l'utilisateur

    issued --> active: Requêtes API avec le Token

    active --> refreshed: Proche de l'expiration, rafraîchissement du Token

    refreshed --> active: Poursuite avec le nouveau Token

    active --> blacklisted: Déconnexion volontaire<br/>modification du mot de passe<br/>dépassement de concurrence (> 3)

    active --> expired: Inutilisé pendant 7 jours

    blacklisted --> [*]
    expired --> [*]

    note right of blacklisted: Ajout à la liste noire JWT<br/>invalidation immédiate
```

## 7. Cycle de vie d'une campagne groupée

```mermaid
stateDiagram-v2
    [*] --> ongoing: Création et mise en ligne par le back-office

    ongoing --> full: Participants ≥ min_people<br/>(verrouillage à pleine capacité, refus de nouveaux participants)

    ongoing --> closed: Expiration sans atteindre la capacité<br/>(détermination paresseuse : fermeture lors de show/join)

    full --> closed: Expiration

    ongoing --> joined: Participation de l'utilisateur : join<br/>(Redis NX anti-survente, participation en double : 422)

    joined --> group_paid: Commande et paiement au prix groupé<br/>(prix groupé = prix normal × discount_percent)

    joined --> cancelled: Fermeture de la campagne sans formation de groupe<br/>(annulation automatique des commandes, libération du verrou du technicien)

    group_paid --> [*]: Cycle de vie normal d'une commande
    cancelled --> [*]
    closed --> [*]

    note right of joined: Les commandes groupées désactivent le cumul coupons / cartes à forfait / points
    note right of closed: Les participants existants reçoivent le message « Groupe non formé »
```

## 8. Cycle de vie d'un don de coupon

```mermaid
stateDiagram-v2
    [*] --> available: Obtention par l'utilisateur / émission système

    available --> transferred: Génération du code de don<br/>(code unique à 8 chiffres, valable 7 jours)

    transferred --> claimed: Obtention par le bénéficiaire<br/>(verrou Redis NX + verrou de ligne anti-double-consommation<br/>coupon d'origine passé à used, nouveau coupon lié au bénéficiaire)

    transferred --> expired: Non récupéré sous 7 jours<br/>(détermination paresseuse, retour du coupon d'origine à available)

    claimed --> used: Utilisation par le bénéficiaire à la commande
    claimed --> expired2: Expiration sans utilisation par le bénéficiaire

    used --> [*]
    expired --> [*]
    expired2 --> [*]

    note right of transferred: Un coupon ne peut être donné qu'une fois<br/>(index unique uk_user_coupon)
    note right of claimed: Un coupon reçu en don ne peut pas être redonné
```

## 9. Cycle de vie de l'expiration des points

```mermaid
stateDiagram-v2
    [*] --> earned: Connexion / crédit à l'achat / compensation<br/>(expires_at = now + 365 jours)

    earned --> used: Utilisation en réduction / échange

    earned --> expired: Arrivée à expiration sans utilisation<br/>(PointsExpiryTimer : scan toutes les 60 s<br/>écriture d'une ligne de déduction négative type=expire)

    expired --> [*]: Notification interne « Points expirés »
    used --> [*]

    note right of expired: Triple idempotence : re-vérification du verrou de ligne d'origine<br/>+ pagination par curseur d'id + notifications émises uniquement par la passe de déduction
```

## 10. Cycle de vie des transferts (19e itération : transfert de solde + don de points)

```mermaid
stateDiagram-v2
    [*] --> validating: Lancement du transfert<br/>(transfert de solde : 0,01–1 000 ¥ par opération, 5 000 ¥ par jour<br/>don de points : 1–10 000 points, 10 000 points par jour)

    validating --> locked: Contrôle réussi<br/>(verrou Redis NX 30 s + verrous de ligne des deux parties<br/>ordre croissant de user_id pour éviter l'interblocage)

    locked --> completed: Validation de la transaction<br/>(débit de l'émetteur + crédit du bénéficiaire<br/>double écriture transfer_out/in ou consume/earn<br/>enregistrement du transfert status=completed)

    locked --> failed: Échec de la re-vérification sous verrou<br/>(solde insuffisant / limite dépassée / bénéficiaire disparu)
    locked --> idempotent: client_token dupliqué<br/>(blocage SETNX 24 h, transfert de solde)

    completed --> notified: Notification interne au bénéficiaire<br/>(balance_received / points_received)
    completed --> [*]
    failed --> [*]
    idempotent --> [*]
    notified --> [*]

    note right of completed: Le flux de crédit de points contient expires_at<br/>et peut expirer normalement via PointsExpiryTimer
```

## 11. Cycle de vie d'un ticket d'assistance (20e itération)

```mermaid
stateDiagram-v2
    [*] --> open: Soumission du ticket par l'utilisateur<br/>(title/content)

    open --> open: Réponse du back-office<br/>(ajout de reply_content / replied_at)

    open --> closed: Clôture par l'utilisateur<br/>(uniquement par son propriétaire / uniquement à l'état open, notation facultative 1–5)

    closed --> [*]

    note right of closed: La note de satisfaction est enregistrée dans rating/rated_at<br/>le back-office agrège la moyenne et la répartition
```

## 12. Cycle de vie d'une facture électronique (20e itération)

```mermaid
stateDiagram-v2
    [*] --> pending: Demande de l'utilisateur<br/>(uk_order_type anti-doublon,<br/>montant fourni par le serveur)

    pending --> issued: Émission par le back-office<br/>(invoice_no + issued_at)

    pending --> rejected: Rejet par le back-office<br/>(reject_reason)

    issued --> [*]
    rejected --> [*]
```

## 13. Cycle de vie d'une promotion à seuil (22e itération)

```mermaid
stateDiagram-v2
    [*] --> draft: Création par le back-office (retirée par défaut)

    draft --> published: Mise en ligne (status=1)

    published --> ended: Expiration (end_at) / retrait manuel

    published --> used: Déclenchement à la commande<br/>(montant après coupon ≥ threshold : réduction automatique<br/>application de la promotion au montant le plus avantageux)

    used --> [*]: Cycle de vie normal d'une commande<br/>(plancher de paiement effectif après réduction : 0,01 ¥)

    ended --> published: Remise en ligne<br/>(non expirée)
    ended --> [*]

    note right of used: Uniquement pour les commandes standard<br/>groupées et flash exclues
```

## 15. Cycle de vie du tirage à la roue (23e itération)

```mermaid
stateDiagram-v2
    [*] --> on: Création des lots et mise en ligne par le back-office

    on --> spun: Tirage de l'utilisateur : spin<br/>(Redis NX + verrou de ligne anti-concurrence<br/>tirage pondéré random_int<br/>client_token idempotent)

    spun --> points: Lot = points<br/>(écriture earn avec expires_at<br/>expirable via PointsExpiryTimer)

    spun --> balance: Lot = solde<br/>(crédit lockForUpdate)

    spun --> coupon: Lot = coupon<br/>(distribution manuelle en pending)

    spun --> lose: Aucun lot<br/>(enregistrement type=none)

    points --> [*]
    balance --> [*]
    coupon --> [*]
    lose --> [*]

    note right of on: Contrôle de mise en ligne / de retrait via toggle-status<br/>les lots retirés ne participent pas au tirage
```

## 14. Cycle de vie de la suppression du compte (22e itération)

```mermaid
stateDiagram-v2
    [*] --> active: Utilisation normale

    active --> requested: Demande de suppression<br/>(solde / commandes non terminées / tickets en cours : blocage 422)

    requested --> active: Annulation de la demande (close-cancel)

    requested --> closing: Confirmation de suppression<br/>(close-confirm après 72 h)

    closing --> [*]: Anonymisation phone/nickname<br/>+ status=0 désactivation

    note right of requested: La connexion reste possible
    note right of closing: close_status=2 : blocage de connexion 403
```

## 16. Cycle de vie d'une vente flash (24e itération)

```mermaid
stateDiagram-v2
    [*] --> published: Création + mise en ligne par le back-office (status=1)

    published --> ongoing: Entrée dans la fenêtre horaire<br/>(start_at ≤ now ≤ end_at)

    ongoing --> sold_out: Verrou de ligne stock-1 jusqu'à 0<br/>(restitution du stock en cas d'échec de commande)

    ongoing --> ended: Expiration (end_at)

    sold_out --> ended: Expiration / retrait manuel

    ended --> published: Remise en ligne (non expirée)

    ongoing --> seckill_order: Commande flash de l'utilisateur<br/>(Redis NX 30 s anti-concurrence<br/>client_token idempotent<br/>injection de seckill_id)

    seckill_order --> [*]: Réutilise le processus de création / paiement de commande<br/>(le prix flash ne cumule ni coupons ni points ni cartes)

    note right of ongoing: L'annulation d'une commande ne restitue pas le stock
```

## 17. Cycle de vie de la récompense client fidèle (24e itération)

```mermaid
stateDiagram-v2
    [*] --> completed: Commande terminée<br/>(WorkController::complete : transaction avec verrou de ligne)

    completed --> checked: Détermination d'un second achat avec le même technicien sous 30 jours

    checked --> none: Premier achat / interrupteur désactivé<br/>(enabled=0)

    checked --> pending: Second achat<br/>(prime = montant payé × ratio<br/>idempotent par order_id+type)

    pending --> settled: Règlement via la chaîne de règlement des commissions<br/>(erik_technician_earnings<br/>type=return_customer)

    settled --> [*]
    none --> [*]

    note right of pending: status=pending<br/>inclus automatiquement dans le récapitulatif des revenus du technicien
```
