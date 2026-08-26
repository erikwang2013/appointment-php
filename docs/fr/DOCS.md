> Traduction française · Original : [中文](../README.md)

# Système de réservation de services — Index de la documentation

> **État du projet** : Tout est terminé ✅ | 143 contrôleurs (service 69 / admin 74) | 87 modèles | 722 tests (service 558 / admin 164) | 95 tables | 388 routes (service 227 / admin 161)

## Documentation principale

| Document | Description |
|------|------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Architecture : vue d'ensemble du système, composition du projet, composants clés, chaîne de middleware, flux de données |
| [FEATURES.md](FEATURES.md) | Fonctionnalités : liste complète côté utilisateur + poste de travail technicien + back-office |
| [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) | Conception de l'architecture : architecture par couches, conception du middleware, conception de la base de données, conception de la sécurité, intégration ES |
| [FEATURE-DESIGN.md](FEATURE-DESIGN.md) | Conception des fonctionnalités : parcours d'achat, machine à états des commandes, règles de remboursement, cartes de membre, changement d'identité |
| [STRUCTURE.md](STRUCTURE.md) | Structure du projet : arborescence complète des quatre applications, chaîne d'exécution du middleware, liste des tables |
| [INSTALL.md](INSTALL.md) | Installation : assistant Web, installation manuelle, déploiement Docker, variables d'environnement, FAQ |
| [USAGE.md](USAGE.md) | Utilisation : back-office / côté utilisateur / côté technicien (interfaces API dans [API.md](API.md)) |
| [API.md](API.md) | Documentation API : API métier + API back-office, exemples de requêtes/réponses + endpoints OpenAPI |

## Tests et sécurité

| Document | Description |
|------|------|
| [TEST-REPORT.md](TEST-REPORT.md) | Rapport de test : audit de couverture complet 558 cas / 2508 assertions + enregistrement du smoke test HTTP |
| [AUDIT-REPORT.md](AUDIT-REPORT.md) | Rapport d'audit : résultats de test, évaluation de la configuration de l'écosystème, enregistrement des corrections, analyse de l'architecture du code |
| [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) | Rapport d'audit de sécurité |

## Base de données et exploitation

| Document | Description |
|------|------|
| [install.sql](../install.sql) | Script d'installation unifié : 67 migrations fusionnées, 2723 lignes, 95 tables / 285 permissions / 38 configurations + données de démonstration |

## Spécifications et plans

| Document | Description |
|------|------|
| [superpowers/specs/2026-05-26-appointment-system-design.md](superpowers/specs/2026-05-26-appointment-system-design.md) | Spécifications du système |
| [superpowers/plans/2026-05-26-appointment-system-plan.md](superpowers/plans/2026-05-26-appointment-system-plan.md) | Plan d'implémentation |

## Documentation du back-office

Les documents propres à `admin/` : ARCHITECTURE.md, DESIGN.md, SECURITY.md, API.md, nginx-security.conf.
