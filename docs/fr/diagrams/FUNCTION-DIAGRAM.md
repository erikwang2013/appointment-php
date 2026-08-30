# Schéma des fonctions du système
> **Languages**: [中文](../../diagrams/FUNCTION-DIAGRAM.md) · [English](../../en/diagrams/FUNCTION-DIAGRAM.md) · [한국어](../../ko/diagrams/FUNCTION-DIAGRAM.md) · [Русский](../../ru/diagrams/FUNCTION-DIAGRAM.md) · [Deutsch](../../de/diagrams/FUNCTION-DIAGRAM.md) · [Español](../../es/diagrams/FUNCTION-DIAGRAM.md) · [Português](../../pt/diagrams/FUNCTION-DIAGRAM.md) · [हिन्दी](../../hi/diagrams/FUNCTION-DIAGRAM.md) · [العربية](../../ar/diagrams/FUNCTION-DIAGRAM.md) · [বাংলা](../../bn/diagrams/FUNCTION-DIAGRAM.md) · [Bahasa Indonesia](../../id/diagrams/FUNCTION-DIAGRAM.md) · [日本語](../../ja/diagrams/FUNCTION-DIAGRAM.md)

```mermaid
mindmap
  root((Système de réservation de services))
    Côté utilisateur
      Authentification
        Inscription / connexion par numéro de mobile
        Connexion par code de vérification
        Connexion via autorisation WeChat
        Mode invité
        Mot de passe oublié
        Contrat utilisateur / politique de confidentialité
      Page d'accueil
        Localisation LBS et changement de ville
        Carrousel / annonces
        Entrées des catégories de services
        Coupon de bienvenue
      Réservation de service
        Choix de la boutique avec navigation
        Choix du technicien avec notes
        Choix du créneau horaire
        10 % de réduction hors pointe / 5 % de réduction en réservation anticipée
        Utilisation des coupons
        Remarques et contrat de service
      Boutique de produits
        Recherche et filtres de produits
        Détails et favoris des produits
        Gestion du panier
        Achat immédiat
      Gestion des commandes
        Consultation de toutes les commandes par onglet
        En attente de paiement / d'expédition / de réception
        Annulation / relance d'expédition / confirmation de réception
        Demande de remboursement
        Demande après-vente : retour / échange, suivi du statut
        Points en réduction au paiement
        Commande groupée : passage de commande au prix groupé après participation
        Commande flash : prix flash, blocage en cas d'épuisement
        Report de rendez-vous : changement de créneau avec le même technicien, début ≥ 6 h
        Calendrier des rendez-vous : vues mensuelle / journalière du planning, créneaux réservés exclus
        Rappel avant le début du service : message d'abonnement + notification interne 1 h avant
        Évaluation texte + photos
        Évaluation complémentaire : ajout de contenu / photos, une fois
        Suivi logistique : statut d'expédition / destinataire masqué
        Facture électronique : demande / liste / détails, anti-doublon
        Export du calendrier ICS : export iCal des rendez-vous à 90 jours
        Chronologie de la commande : historique des changements de statut, visible uniquement par son propriétaire
        Coordonnées de facturation : bibliothèque d'habitudes / défaut
        Préférences de notification : interrupteurs de notification / gating par minuteur
      Module technicien
        Liste des techniciens triée par distance
        Détails du technicien et favoris
        Demande d'inscription
        Planification en masse : période ≤ 7 jours / détection de chevauchements
      Centre marketing
        Coupons : obtention / réduction à la commande
        Don de coupon : code de don à 8 chiffres / anti-double-consommation / valable 7 jours
        Carte de membre : mensuelle / VIP / à forfait
        Validation de la carte à forfait : my/use
        Obtention et échange de points / crédit de points à l'achat
        Expiration des points : validité 365 jours / déduction planifiée
        Boutique d'échange de points : coupons / solde / cartes cadeau
        Groupe / flash : participation / verrouillage à pleine capacité / commande à la formation du groupe
        Rappel d'expiration des cartes et coupons : notification à moins de 3 jours
        Carte cadeau : crédit en espèces / en nature / par échange
        Don de points : entre utilisateurs / plafond quotidien / flux bidirectionnel
        Commission à deux niveaux : parrain de niveau 2, commission de 2 %
        Promotion à seuil : achat de X, réduction de Y / cumul automatique à la commande
        Roue des points : tirage pondéré / points, solde, coupons / lose
      Portefeuille
        Consultation du solde
        Recharge : notification interne à la réception
        Paiement par solde
        Recharge du solde lors d'un remboursement
        Transfert de solde : entre utilisateurs / verrous sur les deux lignes / historique des transferts
        Code de paiement : à 6 chiffres, paramétrage / vérification / modification
      Espace personnel
        Avatar / pseudo / numéro de mobile
        Changement d'identité : client ↔ technicien
        Notifications de messages
        Mes favoris
        Historique de navigation : services consultés récemment
        Dossier santé : antécédents allergiques / technicien préféré
        Suivi du compte officiel
        Parrainage : affiche QR code / détail des commissions
        Niveau de croissance : connexions / évaluations / achats, 5 paliers
        Avantages du niveau : remise à la commande / multiplicateur de points
        Ticket d'assistance : soumission / liste / détails / clôture
        Satisfaction des tickets : notation à la clôture / synthèse back-office
        Retour d'expérience
      Paramètres
        Modification du mot de passe
        Changement du numéro de mobile
        Consultation des contrats
        Vérification des mises à jour
        Confidentialité et conformité : export des données / boucle de suppression du compte de 72 h
        Suppression du compte

    Espace de travail du technicien
      Pointage
        Pointage d'arrivée avec marqueur de retard
        Pointage de départ
      Boucle de l'espace de travail
        today : commandes du jour
        records : historique des services
        start : début du service
        complete : validation à la fin
      Aperçu du jour
        Nombre de commandes du jour
        Aperçu des revenus
      Gestion des plannings
        Créneaux horaires définis par jour
        Publication des créneaux réservables
      Traitement des commandes
        Liste des rendez-vous non validés
        Liste des commandes terminées
        Validation par scan de code QR
      Gestion des membres
        Membres servis
        Données des séances consommées
        Historique des cartes à forfait
        Édition du dossier du membre
      Interaction avec les évaluations
        Réponse aux évaluations : 404 / doublon 422 / notification interne
      Gestion des revenus
        Revenus du jour
        Montant en cours de règlement
        Solde du portefeuille
        Fonds en transit : confirmation automatique sous 3 jours
      Retrait
        Demande le 20 de chaque mois
        Réception sous T+1 dans le portefeuille WeChat
        Plancher / réserve / multiples de 100
      Récompense client fidèle
        Prime pour un second achat sous 30 jours
      Formation professionnelle
        Cours vidéo
        Cours illustrés

    Back-office de gestion
      Tableau de bord
        7 cartes de statistiques  total utilisateurs/nouveaux aujourd'hui/actifs/journal des opérations/rendez-vous du jour/retraits en attente/techniciens en attente de validation
        Graphiques de tendance 30 jours  volume de commandes/montant/nouveaux utilisateurs/activité
        Camembert de répartition des statuts utilisateur  activé/désactivé
        Derniers journaux d'opérations 10
        Navigation rapide
        Messages internes
      Gestion des techniciens
        Liste et recherche de techniciens
        Ajout / export
        Examen des demandes d'inscription
        Plannings / paramétrage des services
        Suivi de la progression des cours
        Évaluation automatique du niveau : volume de commandes + note moyenne / uniquement à la hausse / journal des modifications
        Statistiques de pointage : par mois / regroupement par technicien / retards
      Gestion des utilisateurs
        Liste et recherche de membres
        Détails / paramétrage du niveau
        Modification du parrain / du mot de passe / du mobile
      Gestion des boutiques
        CRUD des boutiques
        Contrôle d'activation / de désactivation
        Configuration des coordonnées cartographiques
        Espace de travail de la boutique : aperçu / filtrage des commandes
      Services et produits
        CRUD des services
        CRUD des produits
        Gestion arborescente des catégories
        Conception des cartes : combinaison service + produit
      Gestion de la boutique
        Commandes / expédition / logistique
        Examen des commandes après-vente
        Gestion des évaluations
        Modération des photos d'évaluation : masquage / restauration, permissions 389-391
        Flux de paiements
        Statistiques de vente
      Commandes de réservation
        Recherche multi-critères
        Annulation plateforme / confirmation de fin
        Consultation des détails
      Campagnes de coupons
        CRUD des coupons
        Contrôle de mise en ligne / de retrait
        Statistiques d'obtention
      Promotions à seuil
        CRUD « achat de X, réduction de Y »
        Contrôle de mise en ligne / de retrait
      Roue des points
        CRUD des lots
        Contrôle de mise en ligne / de retrait
        Consultation des tirages
      Ventes flash
        CRUD des campagnes
        Contrôle de mise en ligne / de retrait
        Consultation des commandes flash
      Échange de points
        CRUD des produits à échanger
        Contrôle de mise en ligne / de retrait
        Consultation des échanges
      Gestion des cartes de membre
        CRUD des définitions de cartes de membre
        à forfait / mensuelle / VIP
      Gestion après-vente
        Liste après-vente : filtres par statut / utilisateur / commande
        Examen : approbation / rejet avec remarque
      Évaluations et rapports
        Gestion des évaluations de service
        Rapports de données  statistiques de commandes/TOP10 techniciens/répartition des canaux plage de 7-30 jours Redis 300s
        Statistiques de ventes  résumé des commandes sur la période/magasin/type de service
      Gestion financière
        Répartition des commandes
        Examen des retraits des techniciens
        Paramétrage des commissions, récompenses et pénalités
        Flux d'entrées et de sorties
        Statistiques financières  revenus/remboursements/retraits/commissions résumé sur la période
        Comptes de retrait / configuration des limites
        Approbation à deux niveaux des remboursements
        Historique des commissions de distribution
        Historique des commissions à deux niveaux, permission 386
        Historique des répartitions : WeChat Partage / filtres par statut
        Vérification des factures : émission / rejet, permissions 382-384
        Récompense client fidèle : interrupteur / taux / historique des primes, permissions 412-414
      Gestion de contenu
        CRUD des carrousels
        CRUD et publication des annonces
        Édition des contrats
        CRUD de la FAQ
        Traitement des retours d'expérience
        Réponse aux tickets d'assistance, permissions 385/387
        Statistiques de satisfaction des tickets, permission 388
        Modération des Moments
        Paramètres « À propos »
      Paramètres système
        Gestion des accords de la plateforme
        Commission uniforme des techniciens
        Modèles de messages système
        Push APP : piloté par configuration / 5 événements intégrés
        Messages d'abonnement : 3 scénarios d'événements de commande
        Gestion des versions APP : CRUD des versions / mise à jour forcée
        Permissions des sous-comptes : RBAC
      Fonctions étendues
        Surveillance système : CPU / mémoire / Redis / MySQL
        Gestion de la liste noire IP
        Sauvegarde / restauration de la base de données
        Profil client : vue 360°
        Envoi groupé de messages
        Gestion des tâches planifiées
        Configuration du double canal SMS
        Configuration du stockage : local / OSS / COS
        Export Excel des plannings
        Comptes gérants : isolation par store_id
```
