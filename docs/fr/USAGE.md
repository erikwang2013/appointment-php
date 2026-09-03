# Utilisation
> **Languages**: [中文](../USAGE.md) · [English](../en/USAGE.md) · [한국어](../ko/USAGE.md) · [Русский](../ru/USAGE.md) · [Deutsch](../de/USAGE.md) · [Español](../es/USAGE.md) · [Português](../pt/USAGE.md) · [हिन्दी](../hi/USAGE.md) · [العربية](../ar/USAGE.md) · [বাংলা](../bn/USAGE.md) · [Bahasa Indonesia](../id/USAGE.md) · [日本語](../ja/USAGE.md)

## Connexion au back-office

Administrateur par défaut : `admin` / `admin123` | Adresse : `http://localhost:8787`

> Modifiez le mot de passe immédiatement après la première connexion

---

## Processus de configuration du système

### 1. Paramètres de base
Configuration du système → nom de la plateforme / LOGO → À propos de nous → téléphone du support / site web / e-mail → accords de la plateforme → modifier l'accord utilisateur / l'accord de confidentialité

### 2. Boutiques et services
Gestion des boutiques → nouvelle boutique (nom / adresse / coordonnées / téléphone / horaires) → catégories de services → créer une catégorie → prestations → nouvelle prestation (nom / prix / durée / spécifications) → gestion des produits → nouveau produit / bon

### 3. Recrutement des techniciens
Demande côté application technicien → validation dans « Gestion des techniciens » du back-office → après approbation, le technicien définit son planning → peut recevoir des réservations

### 4. Configuration opérationnelle
Bannières → téléchargement + lien de redirection | Annonces → publication de bandeau défilant | Bons de réduction → création de bons nouveaux utilisateurs / bons de réduction | Cartes de membre → mensuel / VIP / à forfait | Commissions → définition du taux de commission des techniciens

---

## Opérations quotidiennes du back-office

### Tableau de bord
Après connexion, la page d'accueil affiche 7 cartes de statistiques rendues dynamiquement (total des utilisateurs / nouveaux aujourd'hui / utilisateurs actifs / journaux d'opérations / réservations du jour / retraits en attente / techniciens en attente), des graphiques de tendance sur 30 jours (volume des commandes / montant / nouveaux utilisateurs / activité), un diagramme circulaire de répartition des statuts utilisateurs (activé/désactivé) et les 10 derniers journaux d'opérations (cache Redis `svc:dashboard` 300 s) ; la navigation rapide mène directement aux modules en attente, et les messages internes livrent les notifications de nouvelles commandes/remboursements.

### Rapports de données
La page Rapports propose 3 types de rapports (plage 7/30 jours, via `GET /admin/reports/orders|technicians|distribution`, cache Redis 300 s) :
- **Statistiques des commandes** — synthèse (nombre de commandes/montant payé/remboursements/revenu net) + tendance journalière
- **Performance des techniciens** — TOP 10 des techniciens (nombre de commandes/chiffre d'affaires/note, noms masqués, tri par nombre ou chiffre d'affaires)
- **Répartition des canaux** — répartition des canaux de paiement (WeChat/Alipay/solde) + répartition des statuts de commande

Les statistiques de vente (`svc:sales_stats` : synthèse des commandes sur la période par magasin/type de service) et les statistiques financières (`svc:finance_stats` : synthèse des revenus/remboursements/retraits/commissions sur la période) sont également disponibles.

---

## Parcours côté utilisateur

### Inscription et connexion
Recherche WeChat / scan du QR code → inscription par téléphone + code de vérification (code de parrainage facultatif) → ou connexion en un clic WeChat → les nouveaux utilisateurs reçoivent automatiquement un bon

### Réservation d'un service
Parcourir les catégories sur la page d'accueil → cliquer sur le service pour voir les détails → consulter le prix / les avis → réserver immédiatement → choisir boutique / technicien / créneau / bon → confirmer la commande → paiement WeChat → paiement réussi

### Gestion des commandes
En attente de paiement : effectuer le paiement | Payée : en attente de la prestation | Terminée : évaluer (étoiles + texte + photos) | Remboursement : calcul automatique du taux de remboursement

### Espace personnel
Commandes / bons / cartes de membre / points / favoris | Centre de parrainage : QR code de parrainage pour gagner des points | Retour d'expérience : texte + photos

---

## Opérations côté technicien

### Changement d'identité
Dans l'application « Moi » → passer en technicien → poste de travail

### Tâches quotidiennes
- **Planification** : définir les créneaux réservables par jour
- **Consulter les réservations** : liste des commandes réservées du jour
- **Vérification par scan** : scanner le QR code du client pour valider les consommations
- **Dossier client** : remplir le dossier dans les 24 h après chaque prestation (sinon pas de commission)
- **Pointage** : entrée / sortie / photo d'hygiène

### Revenus
Consulter les revenus du jour / fonds en transit / solde → retrait le 20 de chaque mois → versement sur le compte WeChat en T+1

### Évolution
Suivre les formations → passer les évaluations → montée de niveau du technicien (influence le taux de commission)

---

## Interfaces API

La documentation des interfaces est maintenue séparément, voir [API.md](API.md) (API métier + API back-office, avec exemples de requêtes/réponses et endpoints OpenAPI).

---

## WebSocket

```
ws://localhost:8282
```

Authentification : `{"type":"auth","token":"<JWT>"}`

Événements : `order_update` / `technician_online` / `system_notice`

---

## Configuration du push

iOS (APNs) : configurer apns_key_id / team_id / bundle_id / fichier .p8  
Android (FCM) : configurer fcm_server_key

Enregistrement de l'appareil APP : `POST /api/v1/user/device/register {"platform":"ios","device_token":"..."}`

---

## Tâches planifiées

| Tâche | Fréquence | Description |
|------|------|------|
| Annulation automatique des commandes | 30 secondes | En attente de paiement au-delà de 30 minutes |
| Règlement automatique des revenus | 3 jours | Règlement des commissions des commandes terminées |
| Expiration des bons | Tous les jours | Marquage expired |
| Expiration des cartes de membre | Tous les jours | Marquage expired |

---

## Règles de remboursement

| Condition | Taux |
|------|------|
| Moins de 15 min après la commande ou >6 h avant le début | 100 % |
| ≤6 h avant le début | 90 % |
| Commencé mais non confirmé | 80 % |
| Après confirmation du début | 0 % |

---

## Surveillance

```bash
GET /health          # Vérification de santé
GET /metrics         # Métriques Prometheus
GET /.well-known/security.txt  # Contact de sécurité
```

## Tests

```bash
admin/ && phpunit --bootstrap tests/bootstrap.php     # 60 tests
service/ && phpunit --configuration phpunit.xml        # 21 tests
```
