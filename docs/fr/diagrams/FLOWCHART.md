# Diagramme de flux métier

## 1. Processus de réservation de service

```mermaid
flowchart TD
    A["L'utilisateur parcourt les services proposés"] --> B["Choix de la boutique / du technicien / du créneau"]
    B --> C["Saisie des remarques"]
    C --> D{"Utiliser un coupon ?"}
    D -->|"Oui"| E["Réduction du montant par le coupon"]
    D -->|"Non"| F["Commande au prix normal"]
    E --> G["Calcul du prix de la commande (sans consommation)<br/>PriceCalculator : calcul pur<br/>coupon fixed/percent + carte à forfait times<br/>min_amount basé sur le prix normal"]
    F --> G
    G --> H["Lecture du contrat de service"]
    H --> I["Soumission de la commande"]
    I --> J{"Verrouillage du technicien via Redis<br/>SETNX 3 minutes"}
    J -->|"Verrouillage réussi"| K["Création de la commande pending"]
    J -->|"Déjà verrouillé"| L["Message : technicien occupé"]
    K --> M{"Montant à payer ?"}
    M -->|"Zéro"| N["Passage direct FREE<br/>transaction_id = 'FREE' + numéro de paiement<br/>commande → paid"]
    M -->|"Paiement par solde"| B1["Débit du solde du portefeuille<br/>écriture wallet_txn<br/>commande → paid"]
    M -->|"Montant > 0"| O{"Mode de paiement"}
    O -->|"WeChat"| OW["Appel à WeChat Pay<br/>pay_lock contre les paiements simultanés"]
    O -->|"Solde"| B1
    OW --> P{"Résultat du paiement"}
    B1 --> S
    P -->|"Succès"| Q["Consommation du callback de paiement<br/>markOrderPaid : point de consommation unique<br/>débit atomique du coupon / de la carte à forfait<br/>commande → paid"]
    P -->|"Échec / Annulation"| R["La commande reste pending<br/>annulation automatique après 15 minutes"]
    N --> S["Le technicien confirme le début du service"]
    Q --> S
    S --> T["Commande → serving"]
    T --> U["Service terminé"]
    U --> V["Validation par scan du code QR par le technicien"]
    V --> W["Commande → completed"]
    W --> X["Évaluation de l'utilisateur (texte + photos)"]
    X --> Y["Commande → reviewed ✅"]

    style A fill:#e3f2fd,stroke:#1565c0,color:#333
    style Y fill:#c8e6c9,stroke:#2e7d32,color:#333
    style L fill:#ffcdd2,stroke:#c62828,color:#333
    style R fill:#fff9c4,stroke:#f9a825,color:#333
    style N fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 2. Processus de paiement et de remboursement

```mermaid
flowchart TD
    subgraph 支付流程["Processus de paiement"]
        P1["Création de l'enregistrement de paiement"] --> P2["Commande unifiée WeChat<br/>pay_lock anti-concurrence<br/>out_trade_no = order_no idempotent"]
        P2 --> P3["Le frontend déclenche le paiement<br/>choix du mode de paiement"]
        P3 -->|"Solde"| PB["Débit du solde du portefeuille<br/>écriture wallet_txn<br/>idempotent : débit unique"]
        P3 -->|"WeChat"| P4["Callback WeChat notify"]
        P4 --> P5["Vérification de la signature réussie"]
        PB --> P6["markOrderPaid idempotent<br/>consommation unique du coupon / de la carte à forfait"]
        P5 --> P6
        P6 --> P7["Commande → paid<br/>notification à l'utilisateur et au technicien"]
    end

    subgraph 退款流程["Processus de remboursement"]
        R1["L'utilisateur demande un remboursement<br/>refund_lock anti-concurrence"] --> R2{"Application des règles de remboursement"}
        R2 -->|"Commande ≤ 15 min ou début > 6 h"| R3["Remboursement 100 %"]
        R2 -->|"Début ≤ 6 h"| R4["Remboursement 90 %"]
        R2 -->|"Service commencé, non confirmé"| R5["Remboursement 80 %"]
        R2 -->|"Après confirmation du service"| R6["Aucun remboursement"]
        R3 --> R7["Commande → refunding"]
        R4 --> R7
        R5 --> R7
        R7 --> R8["Approbation à deux niveaux<br/>gérant → comptable"]
        R8 --> R9["Remboursement en deux temps<br/>enregistrement du remboursement dans la transaction<br/>IO de remboursement WeChat hors transaction"]
        R9 -->|"Échec WeChat"| R10["Retour de la commande à PAID<br/>remboursement réessayable"]
        R9 -->|"Remboursement réussi"| R11["Commande → refunded<br/>reversement WeChat d'origine / recharge du solde<br/>restitution du coupon + crédit de points"]
    end

    style P6 fill:#c8e6c9,stroke:#2e7d32,color:#333
    style R6 fill:#ffcdd2,stroke:#c62828,color:#333
    style R11 fill:#c8e6c9,stroke:#2e7d32,color:#333
    style R10 fill:#fff9c4,stroke:#f9a825,color:#333
```

## 3. Processus de retrait du technicien

```mermaid
flowchart TD
    A["Le technicien demande un retrait"] --> B{"poster-php<br/>validation de l'opération"}
    B -->|"Validation réussie"| C{"Contrôle des conditions de retrait"}
    B -->|"Échec de la validation"| X["Opération refusée"]
    C -->|"Le 20 de chaque mois"| D["Création de la demande de retrait"]
    C -->|"Hors jour de retrait"| Y["Message : retrait possible le 20 de chaque mois"]
    D --> E["Examen par le back-office"]
    E --> F{"Résultat de l'examen"}
    F -->|"Approuvé"| G["Exécution du retrait"]
    F -->|"Rejeté"| H["Demande rejetée<br/>motif du rejet joint"]
    G --> I["Paiement WeChat entreprise vers le portefeuille"]
    I --> J["Réception sous T+1"]
    J --> K["Génération de la ligne comptable<br/>enregistrement des entrées et sorties"]

    style K fill:#c8e6c9,stroke:#2e7d32,color:#333
    style X fill:#ffcdd2,stroke:#c62828,color:#333
    style Y fill:#fff9c4,stroke:#f9a825,color:#333
    style H fill:#ffcdd2,stroke:#c62828,color:#333
```

## 4. Processus de changement d'identité

```mermaid
flowchart TD
    A["Identité actuelle : client"] --> B["Clic sur « Passer au technicien »"]
    B --> C{"Statut du dossier du technicien"}
    C -->|"approved"| D["active_role = technician<br/>passage à l'espace de travail du technicien"]
    C -->|"Non enregistré / en cours d'examen"| E["Accompagnement vers la demande d'inscription"]
    E --> F["Remplissage des informations du technicien<br/>nom / sexe / numéro de mobile<br/>carte d'identité / photo"]
    F --> G["Envoi pour examen"]
    G --> H{"Examen par le back-office"}
    H -->|"Approuvé"| D
    H -->|"Rejeté"| I["Modification puis nouvelle soumission"]

    J["Identité actuelle : technicien"] --> K["Clic sur « Passer au client »"]
    K --> L["active_role = customer<br/>passage à l'interface client"]

    style D fill:#c8e6c9,stroke:#2e7d32,color:#333
    style L fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 5. Processus de recharge du portefeuille / de crédit de carte cadeau

```mermaid
flowchart TD
    A["Recharge de l'utilisateur / échange d'une carte cadeau"] --> B{"Mode de crédit"}
    B -->|"Recharge WeChat"| C["Callback WeChat Pay<br/>enregistrement wallet_recharge<br/>crédit idempotent"]
    B -->|"Échange de carte cadeau"| D["GiftCard redeem : validation du code<br/>crédit du montant sur le solde du portefeuille"]
    C --> E["Augmentation du solde du portefeuille<br/>écriture wallet_txn"]
    D --> E
    E --> F["Paiement de commande par solde<br/>ou recharge du solde lors d'un remboursement"]
    F --> G["Crédit / recharge terminés ✅"]

    style G fill:#c8e6c9,stroke:#2e7d32,color:#333
```
