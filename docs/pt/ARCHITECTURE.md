> Tradução em português · Original: [中文](../ARCHITECTURE.md)

# Descrição da arquitetura

## Visão geral do sistema

O Sistema de Serviços de Agendamento adota uma arquitetura de três terminais + dois serviços:

```
┌─────────────────────────────────────────────────────┐
│                  Camada de terminais do utilizador                    │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ Miniprograma │  │  Flutter APP  │                │
│  │   WeChat     │  │              │                │
│  │ apps/wechat/ │  │ apps/flutter/ │                │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │   Funcionalmente equivalentes   │                         │
│         └────────┬─────────┘                         │
│                  │  Alternância de identidade cliente/técnico                   │
├──────────────────┼──────────────────────────────────┤
│              Camada de API de negócio                                │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ service/ API  │  │ admin/ API    │                │
│  │ Porta 8787    │  │ Porta 8787    │                │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │                  │                          │
│         └────────┬─────────┘                          │
│                  │  MySQL/Redis/ES partilhados                 │
├──────────────────┼──────────────────────────────────┤
│                  Camada de dados                                │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌──────────┐     │
│  │ MySQL  │ │ Redis  │ │  ES    │ │Serviços de│     │
│  │        │ │        │ │        │ │terceiros │     │
│  └────────┘ └────────┘ └────────┘ └──────────┘     │
└─────────────────────────────────────────────────────┘
```

## Composição do projeto

### service/ — Serviço de API de negócio

Fornece todas as interfaces de negócio ao miniprograma WeChat e à APP Flutter. webman v2, porta 8787.

**Divisão em módulos:**

| Módulo | Caminho | Autenticação | Descrição |
|------|------|------|------|
| API pública | `api/` | Nenhuma | Login/registo/código de verificação/callback WeChat |
| Módulo do utilizador | `user/` | JWT | Perfil/endereços/favoritos/feedback/promoção |
| Módulo do técnico | `technician/` | JWT+técnico | Perfil/agendamentos/bancada de trabalho/verificação/membros/rendimentos/levantamentos |
| Módulo de serviços | `service/` | Mista | Categorias/projetos/pesquisa/lojas |
| Módulo de pedidos | `order/` | JWT | Carrinho/encomenda/pagamento/reembolso/verificação/avaliação (OrderController dividido em 10 traits por domínio de negócio, rotas e nomes de métodos inalterados) |
| Módulo de marketing | `marketing/` | JWT | Cupões/cartões de membro (cartões de vezes)/pontos/cartões-presente/direitos de membro |
| Módulo de carteira | `wallet/` | JWT | Saldo/recarga/movimentos/pagamento com saldo |
| Módulo de conteúdo | `content/` | Mista | Carrosséis/anúncios/notificações |
| Módulo LBS | `lbs/` | Pública | Cidades/lojas próximas |

### admin/ — Painel de administração

Painel de administração PC. webman v2 + Flutter Web, porta 8787.

**Módulos existentes:** Autenticação, dashboard, gestão de utilizadores, papéis e permissões, configuração do sistema, registos de operações, carregamento de ficheiros, proteção de segurança

**Distribuição de modelos:** `admin/app/model/` mantém apenas 6 modelos próprios (AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig); os restantes modelos são partilhados com o service via composer psr-4 (`app\model\` → `../service/app/model/`), evitando a deriva de modelos duplicados; a classe base `support\Model` está alinhada com o service, e o método de relação `UserPointsExchange::user()` foi fundido no modelo da versão service.

**Módulos extensíveis:** Gestão de técnicos, gestão de membros, gestão de lojas, gestão de serviços/produtos, gestão de pedidos, cupões, cartões de membro, aprovação de levantamentos, gestão de avaliações, estatísticas de relatórios, gestão financeira, gestão de conteúdo, definições do sistema

### apps/ — Frontends do utilizador

| Diretório | Tecnologia | Plataforma |
|------|------|------|
| `apps/wechat/` | Miniprograma WeChat nativo | WeChat |
| `apps/flutter/` | Flutter 3.x + GetX + Dio | iOS + Android |

## Componentes principais

### IDs Snowflake

Todas as chaves primárias são geradas por `erikwang2013/snowflake-php`, BIGINT não autoincrementadas, garantindo unicidade global distribuída. `service/support/Model::nextId()` reutiliza uma única instância Snowflake dentro do processo; as 64 cópias de `generateId()` dos modelos foram eliminadas (herança unificada da implementação da classe base).

### Hashids

Os IDs nos pedidos/respostas da API são codificados com `erikwang2013/hashids`, expondo externamente strings hash.

### Autenticação JWT

`erikwang2013/jwt-webman` Bearer Token, validade de 7 dias, com suporte a refresh e lista negra.

### Encriptação de dados

- **Camada API**: `erikwang2013/encryption` encriptação/desencriptação de dados sensíveis
- **Camada DB**: trait `erikwang2013/encryptable` com encriptação/desencriptação automática de campos

### Proteção de segurança

- `erikwang2013/security-php`: deteção de 31 tipos de ataques
- `erikwang2013/poster-php`: verificação aleatória de operações sensíveis
- Bloqueio de login: 5 falhas bloqueiam 15 minutos
- Limite de concorrência: no máximo 3 Tokens válidos

### Documentação da API

`hg/apidoc` gera a documentação da especificação OpenAPI 3.0, separada entre painel de gestão e cliente:

| Terminal | Endereço | Descrição |
|------|------|------|
| Painel de gestão | `admin/ GET /api/docs` | APIs do painel de administração (JWT+RBAC) |
| Cliente | `service/ GET /api/docs` | APIs de negócio (JWT Bearer) |

A documentação é de acesso público, podendo ser importada no Swagger UI para consulta interativa das interfaces.

### Elasticsearch

`erikwang2013/webman-scout` sincroniza automaticamente os modelos para o ES, suportando pesquisa de texto integral.

## Cadeia de execução de middleware

### Middleware do service/

```
API pública:  Cors → Security(31 deteções) → RateLimit → ApiVersion → Controller
API do utilizador:  Cors → Security → RateLimit → Auth(JWT) → Controller
API do técnico:  Cors → Security → RateLimit → ApiVersion → Auth → TechnicianAuth → Controller
```

### Middleware do admin/

```
API pública:  Cors → Security → RateLimit → Controller
API de gestão:  Cors → Security → RateLimit → AdminAuth(JWT) → AdminPermission(RBAC) → OperationLog → Controller
Verificação de saúde: Cors → Security → RateLimit → Controller
```

## Fluxo de dados

### Fluxo de pedidos

```
Cliente → Cors → Security → RateLimit → Auth(JWT) → [TechnicianAuth] → Controller
    → Model(encriptação/desencriptação encryptable) → BaseController(codificação hashids) → Resposta JSON
```

### Fluxo de agendamento

```
Navegar serviços → Selecionar loja/técnico/horário → Submeter pedido → Redis bloqueia técnico 3 minutos
    → Pagamento WeChat → Notificar técnico → Início do serviço → Conclusão do serviço → Avaliação → Pedido concluído
```

## Origem das operações em 8 terminais

## Extensões mais recentes

| Categoria | Funcionalidade |
|------|------|
| Tempo real | Push WebSocket / callbacks de pagamento / APNs+FCM |
| Mensagens | Push de mensagens de subscrição (sendSubscribeMessage, 3 cenários de eventos de pedido) |
| Carteira | Recarga de saldo / pagamento com saldo / reembolso reposto no saldo |
| Loja | Impressão Bluetooth / assinatura eletrónica / fila de espera |
| Técnico | Avaliação online / exibição de vídeos curtos / bancada de trabalho (today/records/start/complete) |
| Comunidade | Publicações/comentários/gostos/auditoria |
| Sistema | Multilíngue (chinês/inglês) / cancelamento automático de pedidos / seeds de dados |

O campo `source` regista a origem da operação: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### Integração de serviços de terceiros

| Serviço | Classe | Capacidade |
|------|------|------|
| Pagamento WeChat | WechatPayService | Encomenda unificada/consulta/reembolso/levantamento para saldo WeChat |
| SMS | SmsService | Canais duplos Alibaba Cloud/Tencent Cloud |
| Mapas | MapService | AMap/Tencent geocódigo inverso/distância/navegação |
| Mensagens de modelo | WechatTemplateMessageService | Push de pedido/reembolso/lembrete + mensagens de subscrição (sendSubscribeMessage, 3 cenários de eventos de pedido) |
