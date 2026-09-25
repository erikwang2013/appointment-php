> Traduction française · Original : [中文](../README.md)

# Système de réservation de services — Index de la documentation
> **Languages**: [中文](../README.md) · [English](../en/DOCS.md) · [한국어](../ko/DOCS.md) · [Русский](../ru/DOCS.md) · [Deutsch](../de/DOCS.md) · [Español](../es/DOCS.md) · [Português](../pt/DOCS.md) · [हिन्दी](../hi/DOCS.md) · [العربية](../ar/DOCS.md) · [বাংলা](../bn/DOCS.md) · [Bahasa Indonesia](../id/DOCS.md) · [日本語](../ja/DOCS.md)

> **État du projet** : Tout est terminé ✅ | 143 contrôleurs (service 69 / admin 74) | 87 modèles | 757 tests (service 579 / admin 178) | 95 tables | 479 routes (service 221 / admin 258)

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

## Schémas (SVG)

Tous les schémas se trouvent dans [diagrams/](diagrams/) : les originaux chinois `cn-*` et anglais `en-*` sont dans `docs/diagrams/`, chaque langue dispose de son propre jeu miroir dans `docs/<lang>/diagrams/` :

| Schéma | Description | Source Mermaid |
|------|------|-----------|
| [fr-architecture.svg](diagrams/fr-architecture.svg) | Architecture système : topologie des quatre couches clientes + middleware + couche de données + services tiers | [ARCHITECTURE-DIAGRAM.md](diagrams/ARCHITECTURE-DIAGRAM.md) |
| [fr-architecture-design.svg](diagrams/fr-architecture-design.svg) | Conception de l'architecture : 7 couches + chaîne de middleware + limitation de débit + règles de conception de la base de données + conception de la sécurité | [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) |
| [fr-feature-design.svg](diagrams/fr-feature-design.svg) | Conception fonctionnelle : trois domaines fonctionnels + parcours d'achat + règles commerciales + actifs et droits + règlement des techniciens + changement de rôle + paiement | [FEATURE-DESIGN.md](FEATURE-DESIGN.md) |
| [fr-project-structure.svg](diagrams/fr-project-structure.svg) | Structure du projet : arborescence des quatre plateformes + détail des modules | [STRUCTURE.md](STRUCTURE.md) |
| [fr-appointment-flow.svg](diagrams/fr-appointment-flow.svg) | Processus de réservation d'un service | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [fr-payment-refund.svg](diagrams/fr-payment-refund.svg) | Processus de paiement et de remboursement | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [fr-order-lifecycle.svg](diagrams/fr-order-lifecycle.svg) | Machine à états du cycle de vie d'une commande | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [fr-lifecycle-overview.svg](diagrams/fr-lifecycle-overview.svg) | Vue d'ensemble de tous les cycles de vie (17, en quatre groupes) | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [fr-security-defense.svg](diagrams/fr-security-defense.svg) | Défense en profondeur en sept couches | [SECURITY-ARCHITECTURE.md](diagrams/SECURITY-ARCHITECTURE.md) |
| [mascot.svg](diagrams/mascot.svg) | Mascotte du projet « esprit du calendrier Yue » (animation SMIL, sans dépendances externes) | — |

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
| [superpowers/specs/2026-05-26-appointment-system-design.md](specs/2026-05-26-appointment-system-design.md) | Spécifications du système |
| [superpowers/plans/2026-05-26-appointment-system-plan.md](plans/2026-05-26-appointment-system-plan.md) | Plan d'implémentation |

## Documentation du back-office

Les documents propres à `admin/` : ARCHITECTURE.md, DESIGN.md, SECURITY.md, API.md, nginx-security.conf.
