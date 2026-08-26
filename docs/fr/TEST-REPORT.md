# Rapport de l'équipe de test — Audit de couverture des tests

> Généré le : 2026-08-26　Version : v1.3.8
> Équipe : deep-audit (tester-php / tester-api / tester-ui / tester-go / tester-rust)

## 1. Résumé exécutif

| Rôle | Tâche | Résultat |
|------|------|------|
| Ingénieur de test PHP | Tests unitaires/intégration de tous les modules | 70 tests existants + nouveaux de cette vague (voir §3) |
| Ingénieur de test API | Automatisation de toutes les interfaces | Les tests d'intégration au niveau contrôleur constituent la forme d'automatisation API de ce projet (§4) |
| Ingénieur d'automatisation UI | Tests de bout en bout de toutes les pages | Environnement indisponible, conclusion au §5 |
| Ingénieur de test GO | Tests unitaires | **Ignoré : aucun code GO dans le projet** (zéro fichier .go) |
| Ingénieur de test Rust | Tests unitaires | **Ignoré : aucun code Rust dans le projet** (zéro fichier .rs) |

## 2. Pile technique et formes de test

- Backend : PHP 8.3 webman, deux applications (service côté utilisateur / admin back-office), partageant les modèles du service
- Framework de test : PHPUnit + Eloquent, **MySQL réel + rollback de transaction** (pas de mocks), skip automatique si la base est indisponible
- Exécution : `cd service && php -d memory_limit=2G vendor/bin/phpunit`
- Automatisation API = tests d'intégration au niveau contrôleur (construction d'une Request et appel direct des méthodes du contrôleur, base réelle, rollback de transaction)

## 3. Couverture des tests PHP

**Résultat complet : 558 tests / 2508 assertions, 0 échec 0 erreur 0 skip** (2 dépréciations vendor existantes, 2 notices PHPUnit existantes, non introduites par cette vague ; les 4 skips de retrait ont été éliminés via config('withdraw.gate_day') injectable, exécution possible toute la journée)

### Nouveautés de cette vague (tester-php, 6 fichiers 32 cas, base réelle + rollback de transaction)

| Fichier de test | Cas | Couverture |
|---------|------|------|
| CartControllerTest | 4 | Normalisation de l'enregistrement (liste blanche / qty≥1 / entrées invalides ignorées), non-tableau 400, panier vide, vidage |
| PointControllerTest | 4 | Solde = dernier instantané, pagination meta, filtre type/source, liste vide |
| AddressControllerTest | 7 | Ajout + par défaut, champs requis 400, exclusivité du défaut, priorité du défaut, hors périmètre 404, bascule du défaut, suppression + second 404 |
| FavoriteControllerTest | 7 | Favori service/technicien, type invalide 400, doublon 400, incrément/décrément favorite_count, favori orphelin, suppression 404 |
| ReferralControllerTest | 5 | Génération du code de parrainage + statistiques, utilisateur 404, URL du QR code, liste des filleuls, détail des commissions |
| WithdrawControllerTest | 5 | Refus hors jour de retrait (config injectée non aujourd'hui), succès, solde insuffisant, <10 yuans, compte manquant (exécution toute la journée, 0 skip) |

### Couverture existante (70 fichiers, inchangés)

35+ contrôleurs déjà couverts : Auth / machine à états Order / remboursement / vérification / report / callback de paiement / vente flash / offre groupée / bons / cartes cadeaux / points / portefeuille / transfert / cartes de membre / points de croissance / commission / retrait / pointage / planning / facture / logistique / push / message d'abonnement / file d'attente, etc.

### Corrections de cette vague (découvertes par tester-php)

- 【bug】AddressController::show/update/destroy et FavoriteController::destroy n'effectuaient pas le décodage hashids : 404 sur les appels en hashid.
  Correction de la cause racine : `BaseController::decodeId` ajoute la compatibilité de passage direct des chiffres purs (si hashids ne décode pas et ctype_digit, retour tel quel),
  les 89 appels du dépôt en bénéficient ; décodage ajouté à l'entrée des 4 méthodes de contrôleur. Régression complète verte.
- 【bug】avec une longueur minimale de hashids à 0, certains ID numériques nus (ex. 306) coïncidaient avec un encodage hashids valide d'un autre ID,
  decodeId pouvait décoder en erreur (404 sporadique dans AddressControllerTest, reproductible aléatoirement sur plusieurs exécutions complètes).
  Correction de la cause racine : `length` 0→8 sur la connexion main de `config/hashids.php` (service/admin),
  encodage toujours ≥8 caractères, disjoint des ID numériques nus (<8 ou 16 chiffres), ambiguïté éliminée de l'espace d'encodage.
  5 exécutions consécutives d'AddressControllerTest pour vérifier la stabilité, régression complète verte.
- Le jour de retrait codé en dur (le 20) devient `config('withdraw.gate_day')` injectable (config/withdraw.php) :
  les 4 cas « uniquement le 20 du mois » passent en injection réflexive du jour de retrait, exécutables toute la journée, 0 skip.

## 4. Conclusion sur l'automatisation API

- Aucun script de test HTTP indépendant dans ce projet ; les 70 fichiers de test existants sont des tests d'intégration au niveau contrôleur (base réelle),
  couvrant 35+ contrôleurs, équivalents à de l'automatisation d'interfaces
- Matrice de couverture au §3
- **Smoke test HTTP exécuté** (2026-08-26) : 8787 occupé par un autre projet, donc écoute temporairement modifiée sur 8791 dans
  `config/process.php` du service (32 workers webman + websocket + 4 minuteurs tous [OK]),
  mesure réelle `GET /health` → `{"code":0,"message":"ok"}`, `GET /api/guest/services` → HTTP 200
  JSON normal (ID encodés hashids visibles), puis stop et restauration de la configuration, zéro processus résiduel
- Recommandation CI : flutter build web → Playwright E2E des parcours clés du back-office (voir §5)

## 5. Conclusion sur les tests de bout en bout UI

- Côté client : Flutter (apps/flutter côté utilisateur, admin/apps/flutter back-office), mini-programme WeChat (apps/wechat),
  HarmonyOS (apps/harmonyos), admin/apps/weixin
- État actuel : pas de build Flutter Web admin (build/web inexistant) ; aucun service UI en cours sur cette machine ;
  aucun canal d'automatisation navigateur pour mini-programme WeChat / HarmonyOS
- **Conclusion : l'environnement d'automatisation de bout en bout n'est pas disponible**. Recommandation CI : flutter build web → Playwright
  sur les parcours clés du back-office (connexion → liste des commandes → vérification) ; mini-programme / HarmonyOS nécessitent des tests manuels sur appareil/émulateur
- Fourni : admin/public/apidoc (page de documentation des interfaces)

## 6. GO / Rust

Analyse récursive du répertoire racine : **0 fichier .go, 0 fichier .rs** (hors vendor/node_modules/.git).
Outillage installé (go / rustc disponibles) mais aucun objet à tester. Si des services GO/Rust sont introduits, des tests devront être ajoutés.

## 7. Risques résiduels (zones à forte valeur non couvertes)

- Flux principal de commande (déjà couvert par les tests de traits OrderState/OrderRefundFlow etc.)
- Callback WeChat Pay réel (WechatPayService testé en unitaire, sandbox WeChat réel non testé)
- Modules à dépendances externes : impression, LBS, code de vérification

（§3 à compléter au retour de tester-php）
