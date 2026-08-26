# Conception des fonctionnalités

## Parcours d'achat

### Processus de réservation d'un service (commande directe)

```
Détail du service → confirmation de commande (boutique/technicien/créneau/bon/remarque) → acceptation de l'accord de service
    → soumission de la commande → verrou Redis du technicien 3 min → paiement WeChat → paiement réussi
    → notification à l'utilisateur + au technicien → heure du service → confirmation de début par le technicien
    → fin du service → vérification par QR code → avis de l'utilisateur → commande terminée
```

### Processus d'achat d'un produit (mode panier)

```
Liste des produits → ajout au panier → confirmation du panier (quantité/suppression)
    → soumission de la commande → paiement → expédition → réception → terminé
```

## Machine à états des commandes

```
pending (en attente de paiement) → paid (payée) → confirmed (confirmée)
    → serving (en cours de prestation) → completed (terminée) → reviewed (évaluée)

pending → cancelled (annulée)
paid → cancelled
paid → refunding (remboursement en cours) → refunded (remboursée)
```

## Mécanisme de verrouillage du technicien

L'utilisateur entre sur la page de confirmation → verrouillage Redis SETNX 3 minutes. Libération à la sortie / expiration.

```
SETNX lock:tech:123:2026-05-26-14:00 user_456 EX 180
 → Succès : poursuivre la commande
 → Échec : technicien déjà verrouillé
```

## Règles de remboursement

| Condition | Taux de remboursement |
|------|----------|
| Moins de 15 min après la commande ou >6 h avant le début | 100 % |
| ≤6 h avant le début | 90 % |
| Commencé mais service non confirmé | 80 % |
| Après confirmation du début du service | 0 % (aucun remboursement) |

## Règles de remise

| Type | Condition | Remise | Cumul |
|------|------|------|------|
| Remise hors pointe | 10-12 h / 17-18 h / après 21:00 | -10 % | Cumulable avec un bon |
| Réservation anticipée | Plus de 30 min à l'avance | -5 % | Non cumulable avec un bon |

## Retrait du technicien

- Retrait possible le 20 de chaque mois, versement T+1 sur le compte WeChat
- Vérifié non réglé : confirmation automatique à 3 jours
- Montant minimum / montant conservé / plafonnement aux centaines : configuré en back-office

### Processus de retrait

```
Demande de retrait → vérification poster-php → validation back-office (approuver/rejeter)
    → retrait effectué → versement sur compte WeChat → génération de l'écriture financière
```

### Types de revenus

| Type | Description |
|------|------|
| commission | Commission de prestation |
| bonus | Prime (client fidèle / pointage) |
| penalty | Pénalité (dossier client non rempli sous 24 h) |
| subsidy | Subvention |
| attendance | Récompense d'assiduité |

### Récompense du client fidèle

Seconde consommation sous 30 jours avec le même technicien → prime enregistrée

### Dossier client

Le dossier doit être rempli dans les 24 h après chaque prestation terminée, sinon pas de commission

## Conception des points

- Obtention par consommation, obtention par parrainage (configurable en back-office)
- Échange 1:100 contre une carte cadeau (configurable en back-office)
- La table d'historique des points enregistre chaque variation + le solde

## Conception des cartes de membre

| Type | Facturation | Description |
|------|------|------|
| month | Par jour | Carte mensuelle classique |
| vip | Par jour | Carte VIP annuelle |
| times | À l'utilisation | Carte à forfait, combinaisons de prestations libres |

Carte à forfait : au moment de l'achat, choix de la combinaison de prestations (A×3+B×5), chaque utilisation consomme 1 fois la prestation correspondante. Épuisée → used_up, expirée → expired.

## Changement d'identité

```
Client → passer en technicien → contrôle du statut approved du profil technicien
    → Oui : active_role=technician, bascule de la page
    → Non : guide vers la demande d'adhésion

Technicien → passer en client → active_role=customer, bascule de la page
```

## Récompense de nouvel utilisateur

```
Inscription → génération du code de parrainage → présence d'un parrain → création de l'enregistrement de promotion
    → envoi automatique d'un bon nouvel utilisateur (Phase 5)
    → le parrain reçoit des points (après la première commande du filleul)
```

## Conception du paiement (WeChat Pay)

```
POST /api/order/pay/{id}
    → création de l'enregistrement de paiement → appel de la commande unifiée WeChat (WechatPayService réservé)
    → retour des paramètres de paiement → le frontend lance le paiement
    → callback WeChat /api/wechat/notify → vérification de signature → mise à jour du statut paid
    → notification à l'utilisateur + au technicien
```
