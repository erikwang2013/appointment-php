> Tradução em português · Original: [中文](../README.md)

# Sistema de Serviços de Agendamento — Índice de documentação
> **Languages**: [中文](../README.md) · [English](../en/DOCS.md) · [한국어](../ko/DOCS.md) · [Русский](../ru/DOCS.md) · [Deutsch](../de/DOCS.md) · [Français](../fr/DOCS.md) · [Español](../es/DOCS.md) · [हिन्दी](../hi/DOCS.md) · [العربية](../ar/DOCS.md) · [বাংলা](../bn/DOCS.md) · [Bahasa Indonesia](../id/DOCS.md) · [日本語](../ja/DOCS.md)

> **Estado do projeto**: Concluído ✅ | 143 controladores (service 69 / admin 74) | 87 modelos | 757 testes (service 579 / admin 178) | 95 tabelas de dados | 479 rotas (service 221 / admin 258)

## Documentação principal

| Documento | Descrição |
|------|------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Descrição da arquitetura: visão geral do sistema, composição do projeto, componentes principais, cadeia de middleware, fluxo de dados |
| [FEATURES.md](FEATURES.md) | Descrição de funcionalidades: lista completa do lado do utilizador + bancada de trabalho do técnico + painel de administração |
| [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) | Design da arquitetura: arquitetura em camadas, design de middleware, design da base de dados, design de segurança, integração ES |
| [FEATURE-DESIGN.md](FEATURE-DESIGN.md) | Design de funcionalidades: fluxo de compra, máquina de estados do pedido, regras de reembolso, design de cartões de membro, alternância de identidade |
| [STRUCTURE.md](STRUCTURE.md) | Estrutura do projeto: layout completo de diretórios dos quatro terminais, cadeia de execução de middleware, lista de tabelas da base de dados |
| [INSTALL.md](INSTALL.md) | Instruções de instalação: assistente de instalação Web, instalação manual, implantação Docker, variáveis de ambiente, FAQ |
| [USAGE.md](USAGE.md) | Instruções de utilização: operações do painel de administração / lado do utilizador / lado do técnico (interfaces de API em [API.md](API.md)) |
| [API.md](API.md) | Documentação da API: APIs de negócio + APIs do painel de administração, com exemplos de pedido/resposta + endpoint OpenAPI |

## Diagramas (SVG)

Todos os diagramas estão em [diagrams/](diagrams/): os originais em chinês `cn-*` e inglês `en-*` estão em `docs/diagrams/`, e cada um dos 12 idiomas tem o seu próprio conjunto replicado em `docs/<lang>/diagrams/`:

| Diagrama | Descrição | Fonte Mermaid |
|------|------|-----------|
| [pt-architecture.svg](diagrams/pt-architecture.svg) | Arquitetura do sistema: topologia em camadas dos quatro terminais + middleware + camada de dados + serviços de terceiros | [ARCHITECTURE-DIAGRAM.md](diagrams/ARCHITECTURE-DIAGRAM.md) |
| [pt-architecture-design.svg](diagrams/pt-architecture-design.svg) | Design da arquitetura: arquitetura em 7 camadas + cadeia de execução de middleware + limitação de pedidos + princípios de design da base de dados + design de segurança | [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) |
| [pt-feature-design.svg](diagrams/pt-feature-design.svg) | Design de funcionalidades: os três domínios funcionais + fluxos de compra + regras de transação + ativos e direitos + liquidação de técnicos + alternância de identidade + pagamento | [FEATURE-DESIGN.md](FEATURE-DESIGN.md) |
| [pt-project-structure.svg](diagrams/pt-project-structure.svg) | Estrutura do projeto: árvore de diretórios dos quatro terminais + detalhe dos módulos | [STRUCTURE.md](STRUCTURE.md) |
| [pt-appointment-flow.svg](diagrams/pt-appointment-flow.svg) | Fluxo de agendamento de serviço | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [pt-payment-refund.svg](diagrams/pt-payment-refund.svg) | Fluxo de pagamento e reembolso | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [pt-order-lifecycle.svg](diagrams/pt-order-lifecycle.svg) | Máquina de estados do ciclo de vida do pedido | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [pt-lifecycle-overview.svg](diagrams/pt-lifecycle-overview.svg) | Todos os ciclos de vida em resumo (17 no total, em quatro grupos) | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [pt-security-defense.svg](diagrams/pt-security-defense.svg) | Sistema de defesa em profundidade em sete camadas | [SECURITY-ARCHITECTURE.md](diagrams/SECURITY-ARCHITECTURE.md) |
| [mascot.svg](diagrams/mascot.svg) | Mascote do projeto "Coelhinho das Marcações" (animação SMIL, sem dependências externas) | — |

## Testes e segurança

| Documento | Descrição |
|------|------|
| [TEST-REPORT.md](TEST-REPORT.md) | Relatório de testes: auditoria de cobertura de 558 casos / 2508 asserções + registo de smoke test HTTP |
| [AUDIT-REPORT.md](AUDIT-REPORT.md) | Relatório de revisão: resultados de testes, pontuação da configuração do ecossistema, registo de correções de problemas, análise da arquitetura do código |
| [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) | Relatório de auditoria de segurança |

## Base de dados e operações

| Documento | Descrição |
|------|------|
| [install.sql](../install.sql) | Script de instalação unificado: 67 migrações fundidas, 2723 linhas, 95 tabelas / 285 permissões / 38 configurações + dados de demonstração |

## Especificações e planos

| Documento | Descrição |
|------|------|
| [specs/2026-05-26-appointment-system-design.md](specs/2026-05-26-appointment-system-design.md) | Especificação de design do sistema |
| [plans/2026-05-26-appointment-system-plan.md](plans/2026-05-26-appointment-system-plan.md) | Plano de implementação |

## Documentação do painel de administração

Documentos próprios de `admin/`: ARCHITECTURE.md, DESIGN.md, SECURITY.md, API.md, nginx-security.conf.
