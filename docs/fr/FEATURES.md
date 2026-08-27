# Fonctionnalités
> **Languages**: [中文](../FEATURES.md) · [English](../en/FEATURES.md) · [한국어](../ko/FEATURES.md) · [Русский](../ru/FEATURES.md) · [Deutsch](../de/FEATURES.md) · [Español](../es/FEATURES.md) · [Português](../pt/FEATURES.md) · [हिन्दी](../hi/FEATURES.md) · [العربية](../ar/FEATURES.md) · [বাংলা](../bn/FEATURES.md) · [Bahasa Indonesia](../id/FEATURES.md) · [日本語](../ja/FEATURES.md)

> **État du projet** : Tout est terminé ✅ | 109 contrôleurs | 103 modèles | 344 tests (service 240 / admin 104) | WebSocket | callback de paiement | file d'attente | évaluation | communauté

## I. Côté utilisateur (mini-programme WeChat + application Flutter)

Le mini-programme et l'application ont exactement les mêmes fonctionnalités. Un compte unifié permet de basculer entre les identités client/technicien.

### 1. Authentification

| Fonction | Description |
|------|------|
| Inscription par téléphone | Téléphone + code de vérification + mot de passe + confirmation, code de parrainage pris en charge |
| Connexion par mot de passe | Téléphone enregistré + mot de passe |
| Connexion par code | Téléphone enregistré + code de vérification |
| Connexion WeChat | Autorisation WeChat, première connexion avec liaison du téléphone |
| Mode invité | Consultation possible, commande impossible sans inscription |
| Mot de passe oublié | Modification du mot de passe par code de vérification |
| Accord utilisateur / accord de confidentialité | Modifiable en back-office, affiché à l'inscription |

### 2. Page d'accueil

| Fonction | Description |
|------|------|
| Localisation LBS | Localisation de la zone, affichage des services de la zone, changement de ville |
| Bannières | Carrousel automatique, redirection configurée en back-office (page web / détail / aucune) |
| Annonces | Bandeau défilant, liste au clic, ajout en back-office |
| Catégories de services | Image / nom / prix / ventes, détail au clic |
| Bon nouvel utilisateur | Obtention automatique à l'inscription |

### 3. Prestations

| Fonction | Description |
|------|------|
| Informations de base | Image / nom / prix / ventes / spécifications / durée / détails de la prestation |
| Avis utilisateurs | Affichage des avis, consultation de davantage |
| Réservation | Accès à la page de confirmation de commande |
| Choix de la boutique | Adresse de la boutique (navigation) / horaires / téléphone |
| Choix du technicien | Nom / avatar / note |
| Horaire du service | Choix du créneau de réservation |
| -10 % hors pointe | 10-12 h / 17-18 h / après 21:00 |
| -5 % réservation anticipée | 30 min à l'avance, non cumulable avec un bon |
| Bon | Affiche le montant utilisable, utiliser / ne pas utiliser |
| Remarque | Besoins particuliers (limite de caractères) |
| Accord de service | Lecture et confirmation avant soumission |

### 4. Recherche de produits et panier

| Fonction | Description |
|------|------|
| Recherche de produits | Recherche par nom |
| Filtre par catégorie | Recherche par catégorie |
| Détail du produit | Quantité achetable / favori / partage / ajout au panier / achat immédiat |
| Panier | Sélection / suppression / modification de quantité |

### 5. Commandes

| Fonction | Description |
|------|------|
| Toutes les commandes | Consultation par onglet de statut |
| En attente de paiement | Consultation / paiement |
| En attente d'expédition / retrait | Relance d'expédition / annulation / consultation |
| En attente de réception | Informations logistiques / confirmation de réception |
| En attente d'avis | Détail de commande / avis texte + photos |
| Terminées | Consultation des informations |
| Règles de remboursement | ≤15 min après commande ou >6 h avant : 100 % / <6 h avant : 90 % / après début : 80 % / après confirmation : aucun |

### 6. Techniciens (vue client)

| Fonction | Description |
|------|------|
| Liste des techniciens | Du plus proche au plus loin / avatar / nom / nombre de commandes / note / favori / distance / créneaux disponibles / réserver immédiatement |
| Détail du technicien | Photo / nom / distance / commandes / avis / favori / liste des prestations proposées |
| Adhésion technicien | Remplir le formulaire pour devenir technicien, télécharger l'application technicien |

### 7. Poste de travail du technicien (après bascule d'identité)

| Fonction | Description |
|------|------|
| Aperçu du jour | Commandes du jour / vue d'ensemble des revenus |
| Planification | Définition des créneaux réservables par jour |
| Mes commandes | Réservées non vérifiées / terminées |
| Vérification par scan | Scan du QR code du client pour valider les consommations |
| Gestion des membres | Liste des membres servis / données de consommation / cartes à forfait / édition des dossiers |
| Gestion des revenus | Revenus du jour / en cours de règlement / solde du portefeuille |
| Fonds en transit | Vérifiés non réglés, confirmation automatique à 3 jours |
| Retrait | Le 20 de chaque mois, versement T+1 sur le compte WeChat ; validation back-office, ≥500 avec approbation en deux niveaux (gérant → finances) ; réserve en transit à la demande, re-vérification avant transfert, approbation concurrente anti-double versement (durci 2026-08-26) |
| Pointage | Entrée / sortie / photo d'hygiène |
| Récompense du client fidèle | Seconde consommation sous 30 jours, prime enregistrée |
| Formation professionnelle | Cours vidéo / cours illustrés |
| Tâches du jour | WorkController today : récupération en temps réel des tâches du jour |
| Historique | WorkController records : historique des prestations terminées |
| Début / fin de prestation | WorkController start/complete : verrou de ligne + garde de machine à états + idempotence, notification interne automatique après la fin |
| Poste de travail mini-programme | tech-work à 3 onglets : vérification par scan / tâches du jour / historique |

### 8. Espace personnel

| Fonction | Description |
|------|------|
| Informations personnelles | Avatar / pseudo / téléphone |
| Bascule d'identité | Client ↔ technicien |
| Notifications | Notifications internes (appointment_notification) ; page centre de notifications : pagination / rafraîchissement par glissement / surbrillance des lus / marquer lu / tout marquer lu |
| Mes cartes de membre | Mensuelle / VIP annuelle / à forfait (expiration / nombre / utilisées / restantes) |
| Mes points | Historique d'obtention / points disponibles / historique d'utilisation (échange 1:100 contre une carte cadeau) ; points de présence / de consommation, reprise proportionnelle au remboursement, historique paginé + filtre type/source |
| Mes cartes cadeaux | Carte monétaire / cadeau physique ; type cash : l'échange recharge directement le portefeuille |
| Bons | Reçus disponibles / utilisés / expirés |
| Mes favoris | Prestations favorites |
| Suivre le compte officiel | QR code en popup, appui long pour enregistrer |
| Parrainage utilisateur | Explications / poster QR code / liste des filleuls / récompense en points |
| Retour d'expérience | Texte + photos, réponse sous 24 h |
| À propos | LOGO / présentation / téléphone du support / site web / e-mail |

### 9. Paramètres

| Fonction | Description |
|------|------|
| Modifier le mot de passe | Mot de passe actuel + nouveau + confirmation |
| Changer de téléphone | Code du téléphone actuel + code du nouveau |
| Accord utilisateur | Affichage texte, modifiable en back-office |
| Accord de confidentialité | Affichage texte, modifiable en back-office |
| Vérifier les mises à jour | Numéro de version + mise à jour |
| Suppression de compte | Explications + confirmation |
| Déconnexion | Effacement de l'état de connexion |

### 10. Portefeuille rechargeable (tour 6)

| Fonction | Description |
|------|------|
| Solde du portefeuille | GET /api/wallet, solde + historique (tables user_wallet/wallet_recharge/wallet_txn) |
| Recharge | POST /api/wallet/recharge, création de la demande ; POST /api/wallet/recharge/{id}/pay, paiement WeChat, callback avec numéro préfixé R |
| Paiement par solde | Canal de paiement de commande pay_channel=balance |
| Recrédit au remboursement | Les remboursements WeChat/solde recréditent automatiquement le solde (refundToBalance / creditRefundToWallet) |

### 11. Messages d'abonnement (tours 6+8)

| Fonction | Description |
|------|------|
| Scénarios d'abonnement | 3 scénarios d'événements de commande : paiement réussi / remboursement reçu / vérification réussie |
| Idempotence | Marquage push_sent_at anti-double envoi |
| Repli | Modèle d'abonnement non configuré → repli automatique sur notification interne |

### 12. Boucle de vérification carte à forfait (tour 8)

| Fonction | Description |
|------|------|
| Mes cartes à forfait | GET /api/marketing/cards/my, calcul en temps réel used_up/expired |
| Vérification avec décompte | POST /api/marketing/cards/use : idempotence Redis NX + verrou de ligne lockForUpdate, création directe d'une commande completed + OrderItem + OrderPayment(pay_type='card') |

### 13. Déduction par bon (tour 9)

| Fonction | Description |
|------|------|
| Choix du bon à la commande | user_coupon_id facultatif, PriceCalculator.applyCoupon : validation en lecture seule + calcul |
| Types de remise | fixed montant fixe / percent pourcentage, seuil min_amount |
| Consommation et restitution | Consommation à la réussite du paiement (used) ; restitution idempotente au remboursement (restoreCouponAndCard) |

### 14. Cartes cadeaux (tour 9)

| Fonction | Description |
|------|------|
| Échange | redeem : type cash rechargé sur le portefeuille (verrou de ligne anti-double écriture, WalletTxn type='gift_card'), type gift : simple marquage |
| Mes cartes cadeaux | GET /api/marketing/gift-cards/my |

### 15. Système de points (tours 9+10)

| Fonction | Description |
|------|------|
| Points de présence | CheckIn quotidien |
| Points de consommation | À la vérification floor(paid×1), idempotence order_id, instantané balance |
| Reprise au remboursement | clawbackOrderPoints, reprise proportionnelle (3 points d'accroche) |
| Points en déduction | use_points au paiement, 100 points = 1 yuan (config app.points_rate), contrôle du solde par agrégation SUM, écriture de consommation source=points_offset idempotente |
| Reprise de points (tour 15) | Annulation/remboursement restitue les points points_offset : refundOffsetPoints, 5 points d'accroche (doCancel 3 chemins / doRefund transaction WeChat / creditRefundToWallet / completeOneRefundCompensation), source=points_refund idempotent |
| Détail des points | GET /api/marketing/points, pagination + filtre type/source, type unifié earn |

### 16. Chaîne de commande mini-programme (tour 10)

| Fonction | Description |
|------|------|
| Page détail du service | service/detail |
| Page de confirmation | order/confirm : choix du bon / seuil grisé / estimation côté client → POST /order → paiement WeChat/solde |
| Taille des pages | 20 pages au total dans le mini-programme |

### 17. Trois entrées côté utilisateur (tour 10)

| Fonction | Description |
|------|------|
| Favoris | favorite, page des favoris (entrée depuis la page user) |
| Parrainage | referral : code / copie du lien / liste des filleuls |
| Retour d'expérience | feedback, formulaire |

### 18. Autorisation de messages d'abonnement (tour 14)

| Fonction | Description |
|------|------|
| Autorisation d'abonnement | utils/subscribe.js centralise les ID de modèles (clés alignées sur appointment_system_config.wechat_app.template_ids côté serveur) |
| Scénarios de déclenchement | wx.requestSubscribeMessage dans les gestes de réussite de réservation/paiement, silencieux si modèle non configuré ou refus |
| Chaîne serveur | WechatTemplateMessageService (envoi) + NotificationReminderService (rappel 2 h ~ 1 h avant) + processus AutoCancelTimer |

### 19. Après-vente : retour/échange (tour 14)

| Fonction | Description |
|------|------|
| Demande d'après-vente | POST /api/aftersales : type=refund/exchange, validation commande personnelle/paid+completed/déduplication par commande |
| Mes après-ventes | GET /api/aftersales, liste paginée + GET /api/aftersales/{id}, détail |
| Flux de validation | Approbation/rejet côté admin (rejected avec remark obligatoire) ; approved : simple transition d'état, le remboursement réutilise l'interface de remboursement de commande |

### 20. Offres groupées / ventes flash (tour 15)

> Depuis 2026-08, le canal FLASH_SALE est retiré : PromotionController::index filtre flash_sale, show/join renvoient 400, la vente flash passe par le canal « 43. Vente flash (tour 24) » ; la constante `Promotion::TYPE_FLASH_SALE` est conservée pour la compatibilité des données historiques. Cette section et « 27. Commande flash » sont des enregistrements historiques.

| Fonction | Description |
|------|------|
| Liste / détail des activités | GET /api/promotions + /api/promotions/{id}, filtre type group_buy/flash_sale |
| Participation | POST /api/promotions/join/{id} : verrou Redis NX anti-survente (flash_sale : max_people comme plafond de stock), participation en double 422, verrouillage à effectif complet group_buy, fermeture paresseuse à expiration sans effectif complet (status mis à 0 lors de show/join) |
| Liste des participants | GET /api/promotions/{id}/participants |
| Correction d'état | PromotionParticipant passe en constantes entières 0/1/2/3 (corrige la corruption join 1366 en mode strict) |

### 21. Commande de groupe constitué (tour 16)

| Fonction | Description |
|------|------|
| Prix groupé | La réponse de join renvoie discount_percent/original_price/group_price |
| Commande groupée | POST /api/order avec promotion_id : validation uniquement group_buy / activité active / l'appelant est participant / effectif non atteint / prestation correspondante ; prix groupé = prix plein × discount_percent/100, cumul bon/carte à forfait/points interdit (422) |
| Marquage de commande | Nouvelles colonnes appointment_order promotion_id/participant_id + index |
| Groupe non constitué | À l'expiration sans effectif complet : fermeture de l'activité + annulation par lot des commandes pending de l'activité (idempotent) ; pay() détecte paresseusement la fermeture et annule la commande en libérant le verrou technicien |

### 22. Commission de parrainage (tour 16)

| Fonction | Description |
|------|------|
| Règle de versement | Après la première commande completed du filleul : montant = paid_amount × reward_rate (appointment_system_config referral.reward_rate, défaut 0.05, repli sur constante si invalide), versé uniquement si >0 |
| Point d'accroche | ReferralRewardService::handleOrderCompleted accroché dans la transaction de WorkController::complete (seule entrée serving→completed, la vérification verify s'arrête à serving sans déclencher), échec → rollback global et nouvelle tentative |
| Idempotence | Verrou de ligne appointment_user_referral lockForUpdate + contrôle rewarded_at + re-vérification de la première commande sous verrou (appels concurrents/doublons : un seul versement) |
| Écriture | Verrou de ligne portefeuille + WalletTxn type='referral_reward' (balance_after + n° de commande en remark) ; l'enregistrement de parrainage écrit reward_type/reward_amount/rewarded_at/first_order_at |
| Détail | GET /api/user/referral/earnings, pagination (pseudo/avatar du filleul, n° de commande, montant, date) |

### 23. Boutique d'échange de points (tour 16)

| Fonction | Description |
|------|------|
| Produits d'échange | appointment_points_exchange_goods : type=coupon/gift_card/wallet, points_cost/value (DECIMAL(25,2) contre la perte de précision des ID avalanche)/stock/status |
| Liste des produits | GET /api/marketing/points-exchange : produits en ligne + stock restant en temps réel + nombre déjà échangé |
| Échange | POST /api/marketing/points-exchange/{id} : verrou Redis NX + verrou de ligne produit anti-surdébit ; contrôle du solde par agrégation SUM (insuffisant 422) + débit UserPoints type='consume' source='exchange' ; coupon délivré / solde wallet crédité (WalletTxn points_exchange) / clé de carte cadeau renvoyée |
| Idempotence | Index unique uk_user_goods (un même utilisateur, un même produit, une seule fois) + re-vérification sous verrou + repli 1062 ; enregistrement d'échange instantané appointment_user_points_exchange |

### 24. Report de rendez-vous (tour 17)

| Fonction | Description |
|------|------|
| Interface | POST /api/order/reschedule/{id} : new_service_time (obligatoire) + reason (facultatif), changement d'horaire avec le même technicien |
| Règles | Commande personnelle uniquement (404 sinon) ; uniquement type appointment et statut pending/paid/confirmed (422 sinon) ; ≥6 h avant le début du service d'origine (même fenêtre que le remboursement intégral) |
| Protection concurrentielle | B1 order_lock (même famille d'exclusion que pay/cancel/refund) → verrou technicien Redis SETNX EX 180 sur le nouveau créneau (anti-survente en report concurrent) → relecture par verrou de ligne en transaction + contrôle DB de conflit de planning B2 (hors commande courante) |
| Fin | Mise à jour service_time + enregistrement appointment_order_reschedule (avec reason) + libération des verrous du créneau d'origine/du nouveau créneau détenus par la commande ; en cas d'échec, rollback de la transaction et libération du verrou du nouveau créneau |
| Notifications | Message d'abonnement SCENE_RESCHEDULE (repli notification interne « Report de rendez-vous réussi » si modèle non configuré) + pushOrderUpdate |

### 25. Transfert de bon (tour 17)

| Fonction | Description |
|------|------|
| Interfaces | POST /api/marketing/coupons/transfer (user_coupon_id) : génération d'un code de transfert unique à 8 caractères sans ambiguïté (repli uk_code, valable 7 jours) ; POST /api/marketing/coupons/claim (code) : réclamation ; GET /api/marketing/coupons/transfers : émis (pending/claimed/expired) + reçus (claimed), pagination |
| Validations | Le bon appartient à l'utilisateur / available / définition du bon non expirée / jamais transféré (422) ; impossible de réclamer son propre bon, le destinataire n'est pas le détenteur d'origine |
| Anti-abus | Verrou Redis NX coupon_transfer_claim:{code} (30 s) + re-vérification par verrou de ligne en transaction anti-double dépense ; index unique uk_user_coupon (un seul transfert par bon) ; un bon transféré ne peut pas être re-transféré (le nouveau bon sans historique est naturellement bloqué) ; expiration paresseuse → expired + restauration du bon d'origine en available |
| Réclamation | En transaction : bon d'origine → used + génération d'un nouveau UserCoupon lié au destinataire (coupon_id inchangé, donc validité inchangée) + enregistrement de transfert → claimed |

### 26. Expiration des points (tour 17)

| Fonction | Description |
|------|------|
| Validité | Colonne appointment_user_points.expires_at ; toute écriture earn (présence/consommation/reprise) remplit expires_at = now + points.expiry_days (défaut 365, ≤0 : jamais expiré) ; les comportements consume/use laissent vide |
| Exécution de l'expiration | Processus planifié PointsExpiryTimer toutes les 60 s, scan curseur (100/lot) des lignes earn avec expires_at < now → écriture de débit négatif type=expire (source=expiry + order_id traçant la ligne d'origine) → notification interne agrégée par utilisateur « Vous avez X points expirés » |
| Idempotence | ① la ligne expire porte order_id vers la ligne earn d'origine, lockForUpdate sur la ligne d'origine en transaction + re-vérification exists (les processus concurrents se sérialisent sur le verrou de ligne) ② pagination curseur par id ③ la notification n'est émise qu'aux tours de débit effectifs |
| Périmètre | Le solde disponible (agrégation SUM) inclut les lignes négatives expire ; les points expirés ne sont plus utilisables en déduction/échange |

### 27. Commande flash (tour 18, retiré)

> Remplacé par le canal `/api/seckill` du tour 24 (la branche promotion de store() ne conserve que le group_buy), voir « 43. Vente flash ».

| Fonction | Description |
|------|------|
| Interface | POST /api/order avec promotion_id (type flash_sale) : prix flash = round(total × (100 − discount_percent)/100, 2), cohérent avec le calcul du prix flash de PromotionController |
| Validations | Liste blanche de types [group_buy, flash_sale] (422 sinon) ; activité en cours ; l'appelant est participant ; la prestation de la commande correspond ; épuisé si participants_count ≥ max_people, 422 « Tout est épuisé » ; cumul bon/carte à forfait/points interdit 422 |
| Expiration | pay() détecte paresseusement isFlashSaleClosed (même modèle qu'isGroupBuyClosed) : flash expiré → activité à 0 + annulation par lot des commandes pending de l'activité + annulation de la commande + libération du verrou technicien 422 |

### 28. Rappel de service + rappel d'expiration (tour 18)

| Fonction | Description |
|------|------|
| Rappel avant début de service | ServiceReminderTimer 60 s scan des commandes service_time ∈ [now+1h, now+1h+60s), status confirmed/serving, type appointment → notification interne (type='service_reminder', avec service/technicien/boutique/horaire) + message d'abonnement SCENE_REMINDER |
| Rappel d'expiration | ExpiryReminderTimer 6 h scan des end_at ∈ (now, now+3d+6h] : cartes de membre actives (type='card_expiry') + bons available (type='coupon_expiry', whereHas définition du bon end_at) + message d'abonnement SCENE_EXPIRY |
| Idempotence | Curseur id 100/lot + re-vérification par verrou de ligne en transaction + déduplication des notifications (colonne order_id enregistrant l'id de provenance comme clé anti-doublon) ; push_sent_at écrit uniquement après succès du message d'abonnement, nouvel essai au tour suivant en cas d'échec |
| Repli | Modèle non configuré (WECHAT_SUBSCRIBE_TEMPLATE_REMINDER / _EXPIRY) → repli automatique sur notification interne seule |

### 29. Réponse du technicien aux avis (tour 18)

| Fonction | Description |
|------|------|
| Interface | POST /api/technician/review/reply/{order_id} (middleware d'identité technicien) : avis inexistant / pas le sien → 404 unifié ; réponse existante 422 (refus idempotent sans écrasement) ; réponse vide 422 |
| Après réponse | Notification interne à l'utilisateur (type='review_reply', try/catch non bloquant + Log) |
| Données | Colonne replied_at ajoutée de manière idempotente à appointment_order_review (colonne reply présente dès la création) ; les listes/détails d'avis admin exposent reply/replied_at via decorate()->toArray() |

### 30. Notification de recharge reçue (tour 18)

| Fonction | Description |
|------|------|
| Interface | Callback de recharge WeChat (numéro préfixé R) handleRechargeNotify, dans la transaction : après WalletTxn, notification interne type='wallet_recharge' « Vous avez rechargé avec succès ¥X.XX » (montant en yuans, number_format 2 chiffres) |
| Idempotence | Réutilise l'idempotence du callback existant (verrou de ligne sur la demande de recharge + re-vérification du statut, seule la première transition pending→paid atteint la notification) ; la notification et le changement d'état sont validés atomiquement dans la même transaction, aucune fenêtre de crash ; échec de vérification de signature / demande inexistante / montant incohérent → pas de notification |
| Tolérance | Écriture de notification en try/catch, échec → simple log warning sans bloquer le flux principal |

### 31. Transfert de solde (tour 19)

| Fonction | Description |
|------|------|
| Interface | POST /api/wallet/transfer : destinataire décodé hashid + existence 404, transfert à soi-même 422, montant 0,01-1000/opération 422 (comparaison DECIMAL, float interdit), solde insuffisant 422, cumul journalier 5000 yuans 422 |
| Concurrence / idempotence | Verrou Redis NX wallet_transfer:{from} 30 s sérialisant l'émetteur ; en transaction, lockForUpdate des lignes de portefeuille des deux parties par user_id croissant (ordre fixe anti-interblocage) ; client_token après succès SETNX 24 h anti-soumission en double (les requêtes échouées ne posent pas de token, nouvelle tentative possible) |
| Écriture | Débit de l'émetteur + crédit du destinataire + double écriture WalletTxn (transfer_out/transfer_in avec instantané balance_after) + enregistrement de transfert completed + notification interne au destinataire type='balance_received' (échec : simple log) |
| Historique | GET /api/wallet/transfers (direction=out/in paginée) + GET /transfers/{id} (visible uniquement par les deux parties, 404 sinon) |

### 32. Transfert de points (tour 19)

| Fonction | Description |
|------|------|
| Interface | POST /api/user/points/transfer : destinataire inexistant 404, à soi-même 422, quantité 1-10000 422, solde agrégé insuffisant 422, plafond journalier 10000 422 |
| Concurrence / idempotence | Verrou Redis NX points_transfer:{user} 30 s ; en transaction, lockForUpdate de la dernière écriture des deux comptes (user_id croissant anti-interblocage en transferts croisés) + re-vérification du solde/plafond/destinataire sous verrou |
| Conventions d'écriture | Émetteur : type=consume source=points_transfer négatif (balance = dernier instantané − montant, même périmètre que points_offset/exchange) ; destinataire : type=earn source=points_transfer positif avec expires_at (PointsExpiryTimer l'expire normalement) ; enregistrement de transfert écrit en transaction, notification interne au destinataire type='points_received' après commit |
| Historique | GET /api/user/points/transfers (direction=sent/received paginée, pseudo de l'autre partie) |

### 33. Avis complémentaire + complément de route de soumission (tour 19)

| Fonction | Description |
|------|------|
| Avis complémentaire | POST /api/order/review/{order_id}/append : avis inexistant / pas le sien → 404 unifié, non completed 422, doublon 422 (append_content/append_at non vides → refus), contenu vide 422 ; succès : écriture append_content/append_images(JSON)/append_at + notification interne technicien type='review_append' |
| Soumission d'avis | Enregistrement de POST /api/order/review/{order_id} (ReviewController::store n'avait pas de route, inaccessible) ; correction du TypeError latent : findByOrderId recevait un int en violation de la signature string (aligné sur la conversion (string) de append), l'enregistrement de la route exposait un 500 à l'appel |
| Données | appointment_order_review ajoute 3 colonnes append_content TEXT / append_images JSON / append_at DATETIME (migration idempotente) ; les réponses exposent les champs append |

### 34. Suivi logistique côté utilisateur (tour 19)

| Fonction | Description |
|------|------|
| Interface | GET /api/order/logistics/{id} : uniquement les commandes product personnelles (pas le sien / pas un produit / non expédié → 404 unifié) |
| Données | Lecture du JSON order.remark (shipping_company/tracking_no/shipped_at, écrit par admin MallOrderController::ship() à l'expédition) ; parseShippingInfo/parseReceiver, double analyse de repli pour l'ancien format |
| Masquage | Téléphone du destinataire masqué par maskPhone (138****5678), anti-fuite |

### 35. Préférences de notification (tour 19)

| Fonction | Description |
|------|------|
| Données | Table appointment_user_notify_setting (clé composite unique user_id+type uk_user_type, ligne absente = activé par défaut) ; 5 types : service_reminder rappel de service / card_expiry rappel d'expiration (parapluie cartes + bons) / points_expiry expiration des points / marketing marketing (réservé) / system système (non désactivable, PUT forcé à 1) |
| Interfaces | GET /api/user/notify-settings renvoie les 5 interrupteurs ; PUT en upsert par lot sans lignes en double |
| Contrôle | NotificationReminderService::notifySettingEnabled accroché à 3 processus planifiés (ServiceReminderTimer / ExpiryReminderTimer cartes + bons / PointsExpiryTimer ; les minuteurs écrivent directement dans appointment_notification sans passer par le chemin de service, donc chacun ajoute son propre contrôle) + événements d'abonnement (sendSubscribeForOrderEvent, scénarios Notification PAY/REFUND/VERIFIED/RESCHEDULE → system toujours envoyé, REMINDER → service_reminder, EXPIRY → card_expiry) ; type désactivé → notification interne et message d'abonnement tous deux ignorés |

---

## II. Back-office (Web PC)

Application monopage Flutter Web, 21 pages : dashboard/Utilisateurs/Rôles/Configuration/Journaux/Vérification/Planification/Services/Techniciens/Commandes/Bons/Membres/Cartes à forfait/Annonces/FAQ/Retraits/Avis/Rapports/Espace personnel/Poste de travail boutique.

### 1. Tableau de bord d'accueil

- Statistiques en temps réel : nombre d'utilisateurs / total de commandes / nombre de techniciens / nombre de commandes de services
- Graphiques : tendance des commandes / tendance des montants / nouveaux utilisateurs / activité
- Navigation rapide : boutons des modules en attente
- Messages internes : notification de nouvelle commande / notification de remboursement

### 2. Gestion des techniciens

- Liste : recherche par UID / téléphone / nom / région / date d'inscription
- Affichage : numéro / UID / téléphone / pseudo / parrain / statut / nombre d'élèves / performance / état du compte / date d'inscription / dernière connexion / région
- Actions : export / modification du supérieur / consultation des subordonnés / modification mot de passe téléphone / gestion du planning / réglage des prestations techniques / consultation de la progression des cours
- Ajout : nom / sexe / téléphone / carte d'identité / photo de la carte d'identité
- Validation des demandes d'adhésion

### 3. Gestion des utilisateurs

- Liste des membres : nom / téléphone / avatar / niveau / montant consommé
- Recherche : UID / téléphone / pseudo / date d'inscription
- Actions : détail / modification du supérieur / consultation des subordonnés / modification mot de passe téléphone / réglage du niveau de membre

### 4. Gestion des boutiques

- Liste des boutiques : activation/désactivation / suppression
- Nouvelle boutique : nom / adresse / coordonnées / téléphone / horaires / image

### 5. Gestion des services

- Liste des services : recherche par nom / catégorie ; numéro / nom / type / remise / prix minimum / ventes / couverture / ordre / statut / horaires
- Actions : ajout / modification / suppression / conception de la carte
- Liste des produits : type / nom / remise / prix minimum / ventes / stock / couverture / ordre / statut / horaires

### 6. Gestion de la boutique en ligne

- Commandes de la boutique : détail / expédition / logistique / impression
- Commandes après-vente : consultation / validation / impression
- Gestion des avis : consultation / validation (show/hide) / suppression (ReviewController index/show/audit/destroy)
- Flux de paiement
- Statistiques de vente

### 7. Gestion des commandes

- Commandes en attente d'utilisation : recherche multicritère
- Actions : détail / annulation plateforme / confirmation de fin

### 8. Activités de bons

- Liste : numéro / image / type / nom / mise en ligne/sous ligne / total / restant / administrateur / horaires / date de fin
- Actions : ajout / modification / suppression

### 9. Gestion financière

- Règlement des commandes : recherche / détail
- Retraits techniciens : validation WithdrawalController ; ≥500 avec approbation en deux niveaux (gérant store_approved_at → finances finance_approved_at) ; machine à états pending → approved → completed (rejected/failed)
- Réglage des commissions : taux de commission / cycle de règlement / récompenses et pénalités / solde
- Flux de revenus et dépenses
- Gestion des comptes de retrait
- Configuration des limites de retrait

### 10. Gestion de contenu

- CRUD bannières
- Paramètres « À propos de nous »
- Modération des publications (moments)
- CRUD FAQ
- Traitement des retours d'expérience
- CRUD annonces

### 11. Paramètres

- Édition des accords de la plateforme (utilisateur / confidentialité / service)
- Commission unifiée des techniciens
- Modèles de messages système (dont modèles de messages d'abonnement mini-programme, repli automatique sur notification interne si non configuré)
- Gestion des permissions des sous-comptes (le gérant peut émettre des bons + planifier)

### 12. Fonctions étendues

- Conception de cartes : combinaison prestations+produits / main-d'œuvre / réglage des commissions
- Supervision système : tableau de bord temps réel CPU / mémoire / disque / Redis / MySQL / file d'attente
- Liste noire IP : visualisation des attaques security-php + bannissement manuel
- Sauvegarde de base : sauvegarde / téléchargement / restauration via interface Web
- Profil client : vue 360 / préférences de consommation / marketing par segmentation
- Envoi groupé : messages de modèles / diffusion par segments
- Flux de validation des remboursements : approbation en deux niveaux (gérant → finances)
- Niveaux technicien : évaluation automatique junior/senior/expert
- Tâches planifiées : annulation automatique / règlement / traitement des expirations
- Configuration SMS : gestion multi-canaux Aliyun/Tencent Cloud
- Configuration du stockage : local / OSS / COS / CDN
- Rapports enrichis : champs personnalisés / rapports e-mail planifiés
- Export du planning : export Excel des enregistrements de réservation / listes de présence
- Restriction de sexe technicien : contrôle du sexe pour certains services
- Formation technicien : gestion des cours / suivi de la progression
- Compte gérant : isolation des données store_id + permissions dédiées

### 13. Rapports de données (tour 7)

- ReportController, 3 endpoints : statistiques des commandes / performance des techniciens / répartition des boutiques
- Cache Redis svc:admin_report:{type}:{start}:{end}, TTL 300

### 14. Gestion des cartes de membre (tour 10)

- Colonne appointment_user.member_level (migration 000008)
- MemberCardController CRUD complet (permissions 365-369) : GET/POST/PUT/DELETE /admin/member-cards
- Page Flutter de gestion des définitions de cartes de membre

### 15. Gestion après-vente (tour 14)

- Table appointment_order_aftersale (migration 000009) : type=refund/exchange, status=pending/approved/rejected/completed
- AftersaleController : GET /admin/aftersales (pagination + filtres status/uid/order_no) + POST /admin/aftersales/{id}/review (approve/reject+remark)
- Page Flutter de gestion après-vente (liste + boîte de dialogue de validation, permissions 370/371), layout enregistré

### 16. Poste de travail du gérant (tour 15)

- service /api/store-manager : overview (commandes du jour / revenus / en cours / nombre de techniciens / nombre de vérifications) + orders (pagination + filtre de statut) + technicians (avec planning du jour) + revenue (agrégation des 7 derniers jours), requireStoreId() impose l'isolation store_id (403 sans boutique)
- admin StoreController::workbenchOverview (GET /admin/stores/workbench-overview?store_id=, même périmètre que service) + filtre store_id sur la liste des commandes AppointmentOrderController (décodage hashid)
- Page Flutter poste de travail boutique : sélecteur de boutique + filtre de statut + 5 cartes d'aperçu + DataTable des commandes + pagination (permission 372)

### 17. Produits d'échange de points (tour 16)

- PointsExchangeGoodsController : GET/POST/PUT/DELETE /admin/points-exchange-goods + POST {id}/toggle-status (mise en ligne/sous ligne) + GET {id}/exchanges (enregistrements d'échange, avec téléphone + analyse du JSON result)
- Migrations 000012 (deux tables) + 000013 (permissions 373-378) appliquées

### 18. Enregistrements de commission (tour 16)

- ReferralRewardController : GET /admin/referral-rewards (uniquement les enregistrements avec rewarded_at non vide, pagination + filtre keyword par pseudo ou téléphone du parrain/filleul, encodage hashid, permission 379)

### 19. Évaluation automatique du niveau technicien (tour 17)

- TierRatingService::evaluate(technicianId, allowDowngrade=false) : statistiques temps réel des commandes appointment_order completed + moyenne des avis appointment_order_review (arrondie à 1 décimale) écrites sur profile.order_count/rating, correspondance du plus haut au plus bas selon appointment_technician_tier_config (min_orders/min_rating), niveau le plus bas si aucune correspondance
- Règle de montée/descente : montée uniquement (le niveau lie taux de commission et coefficient de prix, une descente automatique affecte les revenus du technicien et crée des litiges, la baisse est gérée manuellement par l'admin) ; allowDowngrade=true (scénario de réévaluation manuelle) exécute la descente, journalisée et notifiée elle aussi
- Idempotence : si le niveau attendu est identique à profile.tier_id, synchronisation des statistiques seule, sans journal ni notification
- Journal : changement écrit dans appointment_technician_tier_log (id/technician_id/old_tier_id/new_tier_id/reason/created_at) + notification interne (type='tier')
- Points de déclenchement : WorkController::complete / écriture d'avis ReviewController / consultation du profil ProfileController avec évaluation paresseuse
- Côté admin : TechnicianTierController conserve la configuration manuelle ; GET /admin/technician-tiers/logs, consultation paginée des journaux de changement (jointure nom technicien et noms des anciens/nouveaux niveaux, ID encodés hashid, permission 380)

### 20. Consultation des réponses d'avis (tour 18)

- ReviewController ajoute reply() : GET /admin/reviews/{id}/reply, détail de la réponse (decodeId → find → 404 → sortie decorate, reply='' si pas de réponse, reply/replied_at exposés via toArray)
- Route statique (placée avant audit, définie avant la resource) ; seed de permission id 381 (slug 'get.admin/reviews/{id}/reply', type 3, association idempotente au rôle super admin)
- Point de permission : 381

### 21. Calendrier de réservation (tour 20)

- CalendarController, vues mensuelle/journalière : GET /api/calendar/technician/{id} (vue mensuelle) + /day (vue journalière)
- Source de données : time_slots JSON de technician_schedule déplié en créneaux horaires par jour de semaine, exclusion des créneaux déjà réservés appointment_order du jour (status ∈ pending/paid/confirmed/serving), sortie des créneaux restants
- Usage : sélection visuelle du créneau selon le planning de la boutique, défilement horizontal par jour + sélection des créneaux côté frontend

### 22. Niveau de croissance utilisateur (tour 20)

- appointment_user_growth (historique) + appointment_growth_level (seed de 5 niveaux : Bronze 0 / Argent 100 / Or 500 / Platine 2000 / Diamant 5000)
- Points d'entrée des points de croissance : présence +10 (CheckInController) ; soumission d'avis +20 (ReviewController::store, pas pour un avis complémentaire) ; consommation floor(paid), 1 point par yuan (WechatPayService::markOrderPaid, réutilise la re-vérification de statut de paiement existante, idempotence naturelle, pas de double écriture sur callback répété)
- Interfaces : GET /api/growth (aperçu du niveau actuel : balance/level/écart vers le niveau suivant) ; GET /api/growth/records (historique paginé) ; GET /api/growth/levels (liste publique des niveaux, sans connexion)
- Stratégie d'échec : chaque point d'entrée en try/catch avec log, sans affecter le flux principal

### 23. Facture électronique (tour 20)

- appointment_invoice : uk_order_type(order_id,order_type) anti-demande en double pour la même commande (422, avec capture de repli MySQL 1062) ; idx_user_created/idx_status
- Côté utilisateur : POST /api/invoices (demande, montant/intitulé fournis par le serveur depuis la commande, infalsifiables) ; GET /api/invoices (liste) ; GET /api/invoices/{id} (détail)
- Côté admin : InvoiceController issue (émission : écriture invoice_no + status=issued + issued_at) / reject (rejet : status=rejected + reject_reason), permissions 382 liste / 383 émission / 384 rejet
- Machine à états : pending → issued / rejected

### 24. Tickets de support (tour 20)

- appointment_ticket : l'utilisateur soumet (title/content), le back-office répond en ajout (reply_content/replied_at), l'utilisateur peut fermer (closed_at)
- Côté utilisateur : POST /api/tickets (soumission) ; GET /api/tickets (liste) ; GET /api/tickets/{id} (détail, personnel uniquement) ; POST /api/tickets/{id}/close (fermeture)
- Côté admin : TicketController index (liste) / reply (réponse), routes statiques définies avant la resource pour éviter l'ombre {id} ; permissions 385 réponse / 387 consultation de liste
- Machine à états : open → replied (retour à open après réponse, nouvelle réponse possible) / closed

### 25. Parrainage multi-niveaux — commission niveau 2 (tour 20)

- ReferralRewardService::payLevel2Reward(paidAmount, orderId) : après paiement réussi, recherche du parrain du parrain de niveau 1 (relation de parrainage niveau 2), versement paid×level2_rate (configuration système referral.level2_rate, défaut 0.02)
- Idempotence : verrou de ligne transactionnel + clé unique uk_order_referred(order_id, level2_user_id), callback de paiement répété/concurrent sans double versement ; échec try/catch : simple log sans affecter le flux de paiement
- Écriture : WalletTxn type='referral_level2' (constante TYPE_REFERRAL_LEVEL2) + accumulation du solde du portefeuille
- Côté admin : ReferralLevel2Controller index, enregistrements paginés (permission 386), jointure des pseudos des deux niveaux d'utilisateurs

### 26. Avantages du niveau de croissance (tour 21)

- Bénéfices GrowthLevel.benefits JSON mis en œuvre : seed de 5 niveaux (Bronze {"discount_rate":1.0,"points_multiplier":1.0}, Argent 0.98/1.1, Or 0.95/1.2, Platine 0.92/1.3, Diamant 0.9/1.5)
- Remise de niveau : OrderController::store applyGrowthDiscount() — commandes standard uniquement (promotion_id vide, cumul interdit pour group_buy/flash) ; ordre : montant dû après bon/carte à forfait × discount_rate ; remise intégrée à discount_amount, la remarque de commande ajoute « Remise de niveau : Argent 9,8, réduction ¥2.00 » traçable ; protection de prix plancher : paiement effectif ≥0,01 yuan (≥100 en centimes), sinon remise tronquée à 0
- Multiplicateur de points : WechatPayService::markOrderPaid, les points de croissance passent de floor(paid) à floor(paid × points_multiplier), multiplicateur figé au niveau au moment du paiement (accumulation avant écriture, pas de montée pour la commande courante) ; les points d'accroche try/catch de R20 sont entièrement conservés
- Réutilisation des requêtes : GrowthLevel::levelForGrowth() détermine le niveau selon les points de croissance cumulés, réutilisé par la commande/le paiement ; GET /api/growth renvoie déjà benefits et next_gap (implémentation R20, inchangée)

### 27. Gestion des intitulés de facture (tour 21)

- appointment_invoice_title (uk_user_title(user_id, title_type, invoice_title) anti-doublon + idx_user_default)
- Interfaces : POST /api/invoice-titles (enregistrement, company exige tax_no, doublon 422) ; GET (liste, défaut en tête) ; PUT /{id} (édition, personnel uniquement) ; DELETE /{id} (suppression, personnel uniquement) ; POST /{id}/default (définition du défaut, remise à zéro transactionnelle des autres lignes du même utilisateur)
- Règle du défaut : le premier enregistrement est automatiquement le défaut ; la suppression du défaut désigne automatiquement le plus ancien
- Liaison de demande : InvoiceController::store accepte un title_id facultatif, l'intitulé est résolu et reporté sur invoice_title/tax_no/title_type ; sans title_id, le chemin de saisie manuelle est conservé ; la logique anti-doublon uk_order_type est inchangée

### 28. Satisfaction des tickets (tour 21)

- appointment_ticket ajoute rating TINYINT NULL + rated_at DATETIME NULL (migration 000303)
- Note à la fermeture : TicketController::close() accepte un rating facultatif 1-5 (validation entière filter_var, hors bornes/non entier 422 ; si fourni, écriture rating+rated_at, sinon NULL conservé pour compatibilité avec les anciens clients ; la règle de fermeture open uniquement est conservée)
- Statistiques back-office : GET /admin/tickets/satisfaction (route statique avant la resource pour éviter l'ombre {id}) renvoie total/rated_count/unrated_count/average (1 décimale)/distribution (nombre par étoile 1-5, 0 si absent) ; permission 388

### 29. Modération des images d'avis (tour 21)

- admin ReviewAuditController (nouveau, sans toucher à ReviewController) : GET /admin/review-audit, liste des avis avec images (filtre JSON_LENGTH(images)>0 + leftJoin pseudo utilisateur et nom technicien + filtre de statut + encodage hashid) ; POST /{id}/hide masquage ; POST /{id}/restore restauration
- Machine à états : hide uniquement si visible, restore uniquement si hidden (double contrôle 422) ; OrderReview en système d'entiers (STATUS_HIDDEN=0/STATUS_VISIBLE=1)
- Chaîne d'effet : la liste des avis du technicien côté utilisateur est déjà filtrée par statut → masquage = invisibilité automatique
- Permissions : 389 liste / 390 masquage / 391 restauration

### 30. Historique de navigation utilisateur (tour 21)

- appointment_browse_history (uk_user_item(user_id, item_id) unique, les visites répétées ne rafraîchissent que viewed_at sans réinsertion ; idx_user_viewed pour le tri)
- Enregistrement : ServiceController::detail() après succès (try/catch + Log::warning sans affecter le flux principal ; route publique sans JWT, user_id vide → anonyme ignoré)
- Interfaces : GET /api/browse-history (jointure appointment_service nom/couverture/prix/prix plein, viewed_at décroissant, per_page défaut 15 max 50, item_id hashid) ; DELETE /{item_id} (personnel uniquement, invalide/autre 404) ; DELETE / (vidage personnel uniquement)

### 31. Marketing par réduction directe (tour 22)

- appointment_full_reduction_activity (threshold/reduction/title/status/start_at/end_at + idx_status_status_time)
- Cumul à la commande : commandes standard uniquement (group_buy/flash ignorés), seuil évalué sur le montant dû après déduction du bon/carte à forfait, ordre **bon/carte à forfait → réduction directe → remise de niveau** ; activité au plus grand montant de réduction ; montant intégré à discount_amount + remarque « Réduction directe : à partir de X, -Y » ; plancher de paiement effectif après réduction 0,01 yuan (centimes)
- Côté utilisateur GET /api/full-reduction-activities (public, actives triées par montant de réduction décroissant)
- admin FullReductionController : CRUD + toggle-status mise en ligne/sous ligne (destroy avec confirmPassword)
- Permissions : 396 liste / 397 ajout / 398 édition / 399 mise en ligne/sous ligne / 400 suppression (un enregistrement de permission correspond à un slug method.path, 5 routes → 5 enregistrements)

### 32. Export ICS de mes réservations (tour 22)

- IcsController GET /api/order/ics : export iCal (RFC5545) des commandes pending/paid/confirmed/serving sous 90 jours, personnel uniquement
- VEVENT : UID=ID de commande, DTSTAMP(UTC), TZID=Asia/Shanghai, durée par défaut 1 h, résumé « Réservation : nom du service » (dégradation « Réservation » si absent), description technicien/boutique/adresse (ignorés si absents), LOCATION ; échappement de texte (\, \; \\ \n) + repli de ligne à 75 octets
- Sans commande : calendrier vide valide (squelette `BEGIN:VCALENDAR`)

### 33. Pointage technicien (tour 22)

- appointment_technician_attendance (date/check_in_at/check_out_at/status + index unique uk_technician_date anti-pointage concurrent en double)
- Côté technicien (TechnicianAuth) : check-in en double le même jour 422 ; check-out sans pointage d'entrée / déjà sorti 422 + verrou de ligne ; >10:00 marqué en retard ; GET liste du mois + jours de présence / heures totales / heures moyennes (?month=YYYY-MM, invalide 422)
- admin : GET /admin/attendance (filtre date + nom technicien, jointure real_name, hashid) + /stats (statistiques groupées par technicien)
- Permissions : 392 liste / 393 statistiques

### 34. Service de push applicatif (tour 22)

- AppPushService (config group=push : enabled défaut 0 / provider jpush/getui/placeholder) : non activé → repli silencieux sur simple log ; activé → construction de la structure plateforme/titre/contenu/payload avec Log + écriture appointment_push_log (status=sent) ; l'intégration des SDK fournisseurs est laissée en TODO (aucun envoi réel sans identifiants)
- 5 points d'événement : paiement réussi (WechatPayService::markOrderPaid), remboursement automatique (autoRefundCancelledOrder), remboursement manuel (doRefund/refundToBalance), compensation de remboursement (completeOneRefundCompensation), rappel de début de service (ServiceReminderTimer) ; tous en try/catch sans bloquer le flux principal
- appointment_push_log (user_id/title/content/payload JSON/status/provider + idx_user)

### 35. Répartition officielle WeChat (tour 22)

- WechatProfitSharingService (config group=profit_sharing : enabled/receiver_ratio, identifiants réutilisés depuis wechat_pay) : non activé → repli disabled sur simple log sans écriture ; activé → validation du montant (>0 et ≤paid, défaut paid×0.7) + idempotence (même commande pending/success ignorée) → écriture de l'enregistrement pending → construction de la structure « demande de répartition unique » (sans identifiants, pas d'exécution HTTP, contenu de la requête journalisé, enregistrement conservé pending) ; HTTP isolé dans doRequest privé, testable
- WechatPayService::markOrderPaid accroche requestSharing après soumission (échec try/catch : simple log)
- appointment_profit_sharing (uk_sharing_no unique + idx_order) ; admin GET /admin/profit-sharing liste (jointure n° de commande/pseudo technicien, filtres statut/n° de commande/nom technicien)
- Permission : 394

### 36. Conformité vie privée (tour 22)

- GET /api/privacy/data : export des données (groupes personal/orders/points/wallet_txns/reviews/addresses/invoices ; le journal n'enregistre que le téléphone masqué + le nombre)
- Boucle de suppression de compte : close-request (solde non nul / commandes non terminées / tickets en cours 422 → close_status=1) → close-cancel (1→0) → close-confirm (72 h écoulées → close_status=2 + close_at + anonymisation phone/nickname en user{id} + status=0)
- appointment_user ajoute close_status/close_requested_at/close_at (migration ALTER idempotente) ; AuthController login/loginByCode renvoient 403 « Compte supprimé » pour close_status=2

### 37. Dossier santé utilisateur (tour 23)

- GET/PUT/DELETE /api/health-profile : un dossier par personne (index unique uk_user), upsert ne met à jour que les champs fournis
- allergies/health_notes limités à 500 caractères, preferred_technician_id avec contrôle d'existence, réponse encodée hashid
- Migration 000504_user_health_profile ; HealthProfileTest 6 tests

### 38. Mot de passe de paiement du portefeuille (tour 23)

- POST /api/wallet/pay-password/{set,verify,check} : validation 6 chiffres, stockage password_hash + pay_password_set_at
- Si déjà défini, la modification exige l'ancien mot de passe 422 ; verify ne fait que vérifier sans écrire ; check renvoie si le mot de passe est défini
- Migration 000502 (ALTER idempotent INFORMATION_SCHEMA de deux colonnes) ; WalletPayPasswordTest 7 tests

### 39. Planification par lots du technicien (tour 23)

- POST /api/technician/schedule/batch : plage de dates ≤7 jours + filtre weekdays, les jours déjà planifiés sont ignorés
- La définition individuelle active aussi la détection de chevauchement de créneaux (422 « Conflit avec le planning existant : HH:MM-HH:MM »)
- ScheduleConflictTest 5 tests

### 40. Chronologie des statuts de commande (tour 23)

- GET /api/order/{id}/timeline : personnel uniquement (404 sinon), retour décroissant ; le détail de commande admin intègre le tableau timeline
- OrderStatusLog::record() points de trace statiques, 8 types de changements : soumission / paiement / annulation / confirmation / demande de remboursement / remboursement approuvé / début de service / fin de service / annulation par expiration / opération back-office (operator=admin)
- Le callback de paiement markOrderPaid est le point de consommation unique ; record() en try/catch interne + Log::warning, ne bloque jamais le flux principal
- Migration 000501_order_status_log ; OrderTimelineTest 4 tests

### 41. Roue de la chance à points (tour 23)

- GET /api/wheel/prizes (weight/stock masqués) ; POST /api/wheel/spin : Redis NX + verrou de ligne anti-concurrence, tirage pondéré random_int, idempotence client_token
- Attribution des prix : points → écriture earn (avec date d'expiration, normalement expirable par PointsExpiryTimer), solde → lockForUpdate, bon → pending en attente de délivrance manuelle, pas de prix → lose
- GET /api/wheel/records mes enregistrements paginés ; admin /admin/lucky-wheel CRUD + mise en ligne/sous ligne + enregistrements (permissions 401-406)
- Migrations 000503 (appointment_lucky_wheel + appointment_wheel_record + seeds de démo w60/w40) + 000505 (seeds de permissions) ; LuckyWheelTest admin 3 + service 6 tests

### 42. Mode invité (tour 24)

- GET /api/guest/{home,services,services/{id},stores,technicians} : entrées de consultation sans connexion (middleware ApiVersion uniquement)
- home agrège bannières/annonces/catégories de services/services populaires, cache Redis svc:guest:home 300 s ; services prend en charge le filtre de catégorie + tris newest/sales/price (page/per_page≤50) ; technicians uniquement approuvés, filtre service_id possible, note décroissante
- Couvert par GuestControllerTest

### 43. Vente flash (tour 24)

- appointment_seckill_activity (name/service_id/seckill_price/original_price/stock/start_at/end_at/status) ; quantité vendue = nombre de commandes appointment_order.seckill_id
- GET /api/seckill (status=1 + fenêtre de temps), /{id} (state=not_started/ongoing/ended), POST /{id}/buy : idempotence client_token (8-64 caractères, SETNX 24 h) + Redis NX 30 s anti-concurrence + validation de l'activité (depuis 2026-08-26, plus de pré-débit de stock)
- Injection seckill_id à la commande, réutilisation d'OrderController::store ; le stock est décrémenté uniquement par verrou de ligne dans la transaction de store() (un appel direct à /api/order avec seckill_id décrémente aussi), prix flash = seckill_price (selon la base), pas de cumul bon/points/carte de membre ; l'annulation de commande ne rend pas le stock ; l'ancien canal FLASH_SALE est supprimé (la branche promotion de store() ne conserve que le group_buy, PromotionController index filtre flash_sale, show/join 400), la vente flash passe uniquement par ce canal
- admin /admin/seckill CRUD + mise en ligne/sous ligne + liste des commandes (permissions 407-411, 420) ; migration 000606 seeds de permissions ; SeckillTest service + admin

### 44. Gestion des versions APP et détection de mise à jour (tour 24)

- appointment_app_version (platform/version_code/version_name/force_update/changelog/download_url/status)
- GET /api/app/version?platform=android|ios détection publique des mises à jour (platform invalide 422 ; plus récente parmi status=1 ; objet vide si aucune)
- admin /admin/versions CRUD (permissions 416-419) ; migration 000609 seeds de permissions ; VersionTest service + admin

### 45. Récompense du client fidèle (tour 24)

- ReturnCustomerRewardService : à la 2e consommation du même utilisateur auprès du même technicien sous 30 jours (commande terminée), prime au technicien = paid_amount × ratio (system_config group=return_customer, ratio défaut 0.05, interrupteur enabled, repli sur défaut si invalide)
- Écriture appointment_technician_earnings (type=return_customer, status=pending) réutilisant la chaîne de règlement des commissions, le récapitulatif des revenus du technicien l'inclut automatiquement ; idempotence par order_id+type ; appelé dans la transaction à verrou de ligne de WorkController::complete
- admin /admin/return-customer/config (GET/PUT) + /rewards (?keyword nom technicien/n° de commande/pseudo utilisateur) (permissions 412-414) ; migration 000607 seeds de permissions ; ReturnCustomerRewardServiceTest

### 46. Export du planning (tour 24)

- GET /admin/technician-schedule/export : CSV (BOM UTF-8, ouverture directe dans Excel), nom de fichier schedules_{YmdHis}.csv
- start_date/end_date obligatoires (YYYY-MM-DD, invalide 422) avec écart ≤31 jours ; technician_id facultatif (hashid, invalide 422)
- Colonnes : ID technicien / nom technicien / date / détail des créneaux (time_slots JSON analysé en « 09:00-12:00, 14:00-18:00 »)
- Permission : 415 ; migration 000608 seeds de permissions ; couvert par ScheduleExportTest
