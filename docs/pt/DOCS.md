> Tradução em português · Original: [中文](../README.md)

# Sistema de Serviços de Agendamento — Índice de documentação

> **Estado do projeto**: Concluído ✅ | 143 controladores (service 69 / admin 74) | 87 modelos | 722 testes (service 558 / admin 164) | 95 tabelas de dados | 388 rotas (service 227 / admin 161)

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
