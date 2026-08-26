> Tradução em português · Original: [中文](../../superpowers/specs/2026-05-26-appointment-system-design.md)

# Especificação de design do sistema de serviços de agendamento
> **Languages**: [中文](../../superpowers/specs/2026-05-26-appointment-system-design.md) · [English](../../en/specs/2026-05-26-appointment-system-design.md) · [한국어](../../ko/specs/2026-05-26-appointment-system-design.md) · [Русский](../../ru/specs/2026-05-26-appointment-system-design.md) · [Deutsch](../../de/specs/2026-05-26-appointment-system-design.md) · [Français](../../fr/specs/2026-05-26-appointment-system-design.md) · [Español](../../es/specs/2026-05-26-appointment-system-design.md) · [हिन्दी](../../hi/specs/2026-05-26-appointment-system-design.md) · [العربية](../../ar/specs/2026-05-26-appointment-system-design.md) · [বাংলা](../../bn/specs/2026-05-26-appointment-system-design.md) · [Bahasa Indonesia](../../id/specs/2026-05-26-appointment-system-design.md) · [日本語](../../ja/specs/2026-05-26-appointment-system-design.md)

## Visão geral

Sistema de serviços de agendamento em três frentes: lado do utilizador (miniprograma WeChat + APP Flutter) + secretária de trabalho do técnico (alternância de identidade na mesma APP) + painel de administração (Web para PC).

## Decisões de arquitetura

| Decisão | Solução |
|------|------|
| Arquitetura do backend | `admin/` (API do painel de administração) + `service/` (API de negócio), dois serviços partilham MySQL/Redis |
| Miniprograma do utilizador | Miniprograma WeChat nativo `apps/wechat/` |
| APP do utilizador | Flutter `apps/flutter/` (iOS + Android) |
| Identidade do utilizador | Conta unificada, identidade de cliente/técnico alternável |
| Relação miniprograma-APP | Funcionalidades idênticas, apenas diferenças de plataforma |
| Frontend do painel de administração | Extensão do Flutter Web existente (`admin/apps/flutter/`) |
| Backend do painel de administração | Extensão dos módulos de negócio do webman v2 existente (`admin/`) |
| Serviços de terceiros | Início de sessão WeChat/pagamento/SMS/mapas — solução de integração reservada |

## Diagrama da arquitetura do sistema

```
┌──────────────────────────────────────────────────────────┐
│                       Camada de terminais                  │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ Miniprograma     │  │ APP Flutter      │              │
│  │ WeChat           │  │                  │              │
│  │ apps/wechat/     │  │ apps/flutter/    │              │
│  │ (WXML/WXSS nativo)│  │ (iOS + Android)  │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │   Funcionalidades idênticas  │                │
│           └──────────┬──────────┘                        │
│                      │ Alternância de identidade          │
│                      │ cliente / técnico                  │
├──────────────────────┼──────────────────────────────────┤
│               Gateway de API de negócio                   │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ service/ API      │  │ admin/ API        │              │
│  │ (webman v2)       │  │ (webman v2)       │              │
│  │ Utilizador/       │  │ Interfaces do     │              │
│  │ Encomenda/        │  │ painel de gestão  │              │
│  │ Pagamento/        │  │ (existentes +     │              │
│  │ Técnico/          │  │  extensões)       │              │
│  │ Loja/Marketing... │  │                   │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │                      │                        │
│           └──────────┬───────────┘                        │
│                      │                                    │
├──────────────────────┼──────────────────────────────────┤
│                   Camada de dados                          │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────────────┐    │
│  │ MySQL  │ │ Redis  │ │  ES    │ │ Serviços de    │    │
│  │ 8.0    │ │ Cache/ │ │ Pesquisa│ │ terceiros      │    │
│  │        │ │ Limite/ │ │        │ │ WeChat/SMS/    │    │
│  │        │ │ Session │ │        │ │ Mapas          │    │
│  │        │ │        │ │        │ │ (integração    │    │
│  │        │ │        │ │        │ │  reservada)    │    │
│  └────────┘ └────────┘ └────────┘ └────────────────┘    │
└──────────────────────────────────────────────────────────┘
```

## Tabelas principais da base de dados

Todas as tabelas usam o prefixo `erik_`, chave primária BIGINT não autoincrementada (gerada pelo Snowflake). Os campos sensíveis usam a trait encryptable para encriptação/desencriptação.

### Domínio de utilizadores e identidades

| Nome da tabela | Descrição | Campos principais |
|------|------|----------|
| `erik_user` | Tabela de utilizadores unificada | phone, password, wx_openid, wx_unionid, avatar, nickname, user_type(customer/technician), status. Os utilizadores técnicos também têm funcionalidades de cliente e podem alternar livremente a identidade ativa atual |
| `erik_user_address` | Endereços do utilizador | user_id, contact_name, contact_phone, province, city, district, detail, is_default |
| `erik_technician_profile` | Perfil de técnico | user_id, real_name, gender, id_card, id_card_front, id_card_back, avatar, rating, order_count, status(pending/approved/rejected), intro |
| `erik_technician_schedule` | Horário do técnico | technician_id, date, time_slots(JSON), status |
| `erik_technician_service` | Serviços que o técnico pode realizar | technician_id, service_id |
| `erik_technician_earnings` | Movimentos de rendimentos do técnico | technician_id, order_id, type(commission/bonus/penalty), amount, status |
| `erik_technician_withdrawal` | Registos de levantamento do técnico | technician_id, amount, actual_amount, commission_fee, account_info, status, reviewed_at |
| `erik_technician_attendance` | Presenças do técnico | technician_id, date, check_in_at, check_out_at, clean_photo |
| `erik_technician_member_note` | Ficheiro de membro | technician_id, user_id, content, written_at |

### Domínio de serviços e produtos

| Nome da tabela | Descrição | Campos principais |
|------|------|----------|
| `erik_service_category` | Categorias de serviço | name, icon, parent_id, sort, status |
| `erik_service` | Serviços | category_id, name, description, cover_image, images(JSON), price, duration, sales_volume, specs(JSON), status |
| `erik_product` | Produtos | category_id, name, cover_image, price, stock, sales_volume, type, status |
| `erik_store` | Lojas | name, address, lat, lng, phone, business_hours(JSON), images, status |

### Domínio de encomendas

| Nome da tabela | Descrição | Campos principais |
|------|------|----------|
| `erik_order` | Tabela principal de encomendas | order_no, user_id, technician_id, store_id, total_amount, discount_amount, paid_amount, status, service_time, cancel_reason, remark |
| `erik_order_item` | Detalhes da encomenda | order_id, service_id, product_id, type, name, price, quantity, spec_info |
| `erik_order_payment` | Registos de pagamento | order_id, pay_type(wechat), transaction_id, amount, status, paid_at |
| `erik_order_refund` | Registos de reembolso | order_id, payment_id, refund_no, amount, ratio, reason, status |
| `erik_order_review` | Avaliações de serviço | order_id, user_id, technician_id, rating, content, images |
| `erik_order_verification` | Registos de verificação | order_id, code, verified_at, verified_by, location |

### Domínio de marketing

| Nome da tabela | Descrição | Campos principais |
|------|------|----------|
| `erik_coupon` | Definições de cupões | name, type, amount, min_amount, total_qty, remain_qty, start_at, end_at, status |
| `erik_user_coupon` | Cupões do utilizador | user_id, coupon_id, status(available/used/expired), used_at |
| `erik_member_card` | Definições de cartões de membro | name, type(month/vip/times), price, duration_days, total_times, services(JSON) |
| `erik_user_member_card` | Cartões de membro do utilizador | user_id, card_id, start_at, end_at, total_times, used_times, status |
| `erik_member_card_usage` | Registos de uso do cartão de vezes | user_card_id, order_id, service_id, used_at |
| `erik_user_points` | Movimentos de pontos | user_id, type(earn/use), points, source, order_id |
| `erik_gift_card` | Cartões-presente | code, type, amount_or_gift, status, used_by, used_at |
| `erik_user_referral` | Divulgação do utilizador | referrer_id, referred_user_id, reward_type, reward_amount, registered_at, first_order_at |

### Domínio de conteúdos e notificações

| Nome da tabela | Descrição | Campos principais |
|------|------|----------|
| `erik_banner` | Carrosséis | position, image, jump_type(url/detail/none), jump_value, sort, status |
| `erik_announcement` | Anúncios | content, status, published_at |
| `erik_platform_agreement` | Acordos da plataforma | type(user_agreement/privacy_policy/service_agreement), title, content, version |
| `erik_faq` | Perguntas frequentes | title, content, sort |
| `erik_feedback` | Feedback | user_id, content, images, handler_reply, status(pending/handled) |
| `erik_moment` | Publicações de momentos | content, images, published_at |
| `erik_notification` | Notificações | user_id, type(order/system), title, content, is_read, created_at |

### Domínio financeiro (lado admin)

| Nome da tabela | Descrição | Campos principais |
|------|------|----------|
| `erik_finance_transaction` | Movimentos de receitas e despesas | user_id, order_id, type, direction(income/expense), amount, actual_amount, commission, status |
| `erik_technician_commission_config` | Configuração de comissões | technician_id, commission_rate, settlement_cycle |
| `erik_withdrawal_account` | Contas de levantamento | user_id, type(wechat), account_name, account_no |
| `erik_withdrawal_config` | Configuração de limites de levantamento | min_amount, reserve_amount, round_to_hundred |

## Módulos da API do Service

### API pública (sem autenticação)
- **AuthController** — início de sessão/registo/esquecer palavra-passe/modo convidado/alternância de identidade
- **CaptchaController** — códigos de verificação SMS
- **WechatController** — autorização WeChat/início de sessão/callback de pagamento
- **CommonController** — textos de acordos/sobre nós/informação de versão

### Módulo de utilizador `user/` (requer autenticação)
- **ProfileController** — informação pessoal/alterar palavra-passe/alterar telemóvel/eliminar conta
- **AddressController** — CRUD de endereços de envio
- **FavoriteController** — favoritos
- **FeedbackController** — feedback
- **ReferralController** — divulgação/lista de utilizadores recomendados

### Módulo de técnico `technician/` (requer identidade de técnico + middleware TechnicianAuth)
- **ProfileController** — perfil de técnico/candidatura de adesão
- **ScheduleController** — definição de horários
- **OrderController** — marcados sem verificação/concluídos/verificação por código QR
- **MemberController** — os meus membros/ficheiros de membros
- **EarningsController** — rendimentos/fundos em trânsito
- **WithdrawalController** — levantamentos
- **AttendanceController** — presenças/fotografias de higiene

### Módulo de serviços `service/`
- **CategoryController** — categorias de serviço
- **ItemController** — listas e detalhes de serviços/produtos
- **SearchController** — pesquisa
- **StoreController** — lista/detalhes de lojas

### Módulo de encomendas `order/` (requer autenticação)
- **CartController** — carrinho de compras
- **OrderController** — criar encomenda/lista de encomendas/detalhes/cancelar
- **PaymentController** — pagamento/reembolso
- **VerificationController** — verificação por código QR
- **ReviewController** — avaliações

### Módulo de marketing `marketing/` (requer autenticação)
- **CouponController** — lista de cupões/obtenção/uso
- **MemberCardController** — cartões de membro/cartão de vezes
- **PointsController** — pontos
- **GiftCardController** — cartões-presente

### Módulo de conteúdos `content/`
- **BannerController** — carrosséis
- **AnnouncementController** — anúncios
- **NotificationController** — notificações

### Módulo LBS
- **LocationController** — localização/alternância de cidade/lojas próximas

### Capacidades comuns `common/`
- SnowflakeService — geração de IDs
- HashidsService — encriptação/desencriptação de IDs
- EncryptionService — encriptação/desencriptação de dados sensíveis
- WechatPayService — pagamento WeChat (reservado)
- WechatAuthService — início de sessão WeChat (reservado)
- SmsService — serviço de SMS (reservado)
- MapService — serviço de mapas (reservado)

### Middleware
- Auth — autenticação JWT (partilhado com o admin através do pacote erikwang2013/jwt-webman)
- TechnicianAuth — validação de identidade do técnico
- RateLimit — limitação de tráfego (partilhada com o admin)

## Extensões do painel de administração Admin

Novos controladores adicionados sobre o framework existente:

### Gestão de técnicos
- **TechnicianController** — lista de técnicos/pesquisa/exportação/aprovação/gestão de horários/definição de serviços técnicos/progresso de cursos

### Extensão da gestão de utilizadores
- **MemberController** — lista de membros/definição de níveis/estatísticas de consumo

### Gestão de lojas
- **StoreController** — CRUD de lojas/ativação e desativação

### Gestão de serviços
- **ServiceController** — lista de serviços/CRUD/design de cartões de serviço
- **ServiceCategoryController** — gestão de categorias
- **ProductController** — lista de produtos/CRUD

### Gestão do comércio
- **MallOrderController** — encomendas do comércio/expedição/pós-venda/avaliações
- **SalesStatsController** — estatísticas de vendas

### Gestão de encomendas
- **AppointmentOrderController** — encomendas por utilizar/cancelar/confirmar conclusão

### Atividades de cupões
- **CouponController** — CRUD de cupões/emissão

### Gestão financeira
- **FinanceController** — partilha de lucros de encomendas/movimentos de receitas e despesas
- **WithdrawalController** — aprovação/conclusão de levantamentos de técnicos
- **CommissionController** — definição de comissões/recompensas e penalizações/consulta de saldo
- **WithdrawalAccountController** — gestão de contas de levantamento
- **WithdrawalConfigController** — configuração de limites de levantamento

### Gestão de conteúdos
- **BannerController** — CRUD de carrosséis
- **AnnouncementController** — CRUD de anúncios
- **FaqController** — CRUD de FAQ
- **FeedbackController** — tratamento de feedback
- **MomentController** — aprovação de publicações de momentos
- **AgreementController** — edição de acordos (acordo do utilizador/acordo de privacidade/acordo de serviço)
- **AboutController** — definição «Sobre nós»

### Definições
- **SystemMessageController** — definições de mensagens do sistema
- **AdminUserController** — gestão de subcontas (baseada no RBAC existente)

### Extensões do Dashboard
- Cartões de estatísticas em tempo real: número de utilizadores/total de encomendas/número de técnicos/encomendas de serviços
- Gráficos de linha: volume de encomendas/montantes/novos utilizadores por dia/atividade
- Navegação rápida: botões de módulos pendentes de tratamento
- Mensagens intra-site: notificações de novas encomendas/notificações de reembolsos

## Estrutura de páginas do lado do utilizador

O miniprograma WeChat e o APP Flutter têm funcionalidades idênticas.

### auth/ — Autenticação
- login — início de sessão (telemóvel/código de verificação/WeChat/entrada de convidado)
- register — registo (telemóvel + código de verificação + palavra-passe + código de recomendação)
- forget-password — esquecer palavra-passe
- agreement — visualização de acordos

### home/ — Página inicial
- index — página inicial (carrosséis + anúncios + categorias de serviço + recomendações)
- search — página de pesquisa

### service/ — Serviços
- list — lista de serviços (filtro por categoria)
- detail — detalhes do serviço (informação básica + avaliações + marcar já)
- product-list — lista de produtos

### order/ — Encomendas
- confirm — confirmar encomenda (loja/técnico/horário/cupão/observações/acordo)
- payment — página de pagamento
- payment-success — pagamento bem-sucedido
- list — todas as encomendas (filtro por separadores de estado)
- detail — detalhes da encomenda
- review — avaliação do serviço
- verification — verificação por código QR

### cart/ — Carrinho de compras
- index — lista do carrinho

### technician/ — Técnicos (perspetiva do cliente)
- list — lista de técnicos (ordenada por proximidade)
- detail — detalhes do técnico (avaliações/serviços que pode realizar/marcar já)
- apply — candidatura de adesão do técnico

### tech-work/ — Secretária do técnico (identidade de técnico)
- index — página inicial da secretária (encomendas de hoje/visão geral de rendimentos)
- schedule — definição de horários
- order-list — as minhas encomendas (marcadas sem verificação/concluídas)
- scan-verify — verificação por leitura de código QR
- member-list — os meus membros
- member-detail — detalhes do membro/edição do ficheiro
- earnings — os meus rendimentos
- withdrawal — levantamentos
- transaction-list — movimentos de transações
- attendance — presenças/carregamento de fotografias de higiene
- training — formação profissional

### user/ — Centro pessoal
- index — informação pessoal (avatar/pseudónimo/cartão de membro/favoritos/entrada de cupões)
- settings — definições (alterar palavra-passe/alterar telemóvel/acordos/atualizações/eliminar conta/sair)
- switch-role — alternância de identidade (cliente ↔ técnico)

### marketing/ — Marketing
- coupon-list — lista de cupões
- member-card — os meus cartões de membro
- points — os meus pontos
- gift-card — os meus cartões-presente
- referral — divulgação (descrição + cartaz com código QR + lista de utilizadores recomendados)

### Outras páginas
- message/ — lista/detalhes de mensagens
- store/list, store/detail — lista de lojas (ordenação LBS)/detalhes (navegação)
- other/about — sobre nós
- other/feedback — feedback
- other/official-account — seguir a conta oficial

### Componentes comuns
- navbar, tabbar, service-card, technician-card
- coupon-popup, lbs-selector, empty-state, loading

### Lógica de alternância de identidade
- Navegação inferior do cliente: Início / Serviços / Carrinho / Encomendas / Eu
- Navegação inferior do técnico: Secretária / Encomendas / Membros / Rendimentos / Eu
- A página «Eu» fornece a entrada de alternância de identidade
- Utilizadores que ainda não são técnicos são orientados para a página de candidatura de adesão ao alternar para a identidade de técnico

## Descrição dos fluxos de compra

O sistema tem dois fluxos de compra diferentes:

### Fluxo de marcação de serviços (encomenda direta, sem carrinho)
- Página de detalhes do serviço → confirmar encomenda (selecionar loja/técnico/horário) → pagamento → verificação
- Exclusividade do recurso do técnico: o técnico é bloqueado durante 3 minutos ao entrar na página de confirmação da encomenda
- Utilizado para serviços offline como massagem, beleza, etc.

### Fluxo de compra de produtos (modo carrinho)
- Lista de produtos → adicionar ao carrinho → confirmação no carrinho → submeter encomenda → pagamento → expedição/receção
- Suporta alteração de quantidade, eliminação de produtos
- Utilizado para venda de bens físicos ou cartões e cupões

## Regras de negócio principais

### Mecanismo de bloqueio do técnico
- Várias pessoas não podem marcar o mesmo técnico ao mesmo tempo
- Ao entrar na página de confirmação da encomenda, o utilizador bloqueia o técnico durante 3 minutos via Redis SETNX
- Sair da página de marcação ou timeout liberta o bloqueio automaticamente

### Regras de reembolso
| Condição | Percentagem de reembolso |
|------|----------|
| No prazo de 15 minutos após a encomenda ou >6 horas até ao início | 100% |
| ≤6 horas até ao início | 90% |
| Já iniciado mas serviço não confirmado | 80% |
| Após confirmação do início do serviço | 0% (sem reembolso) |

### Regras de desconto
- Períodos de menor procura (10-12h/17-18h/após as 21:00) 9% de desconto (90% do preço)
- Marcação com 30 minutos de antecedência 95% do preço (não acumulável com cupões)

### Levantamento do técnico
- Levantamento disponível no dia 20 de cada mês, T+1 dia útil até cair na conta
- Suporta levantamento para a carteira WeChat
- Encomendas verificadas mas não liquidadas são confirmadas automaticamente pelo sistema no prazo de 3 dias
- O ficheiro de membro deve ser preenchido no prazo de 24 horas, caso contrário não há comissão

### Recompensa de cliente recorrente
- Segunda compra ao mesmo técnico no prazo de 30 dias → registo de bónus
- Carregar fotografia de higiene após o serviço

### Regras de pontos
- 1:100 para trocar por cartão-presente (configurável no painel)
- Após o registo bem-sucedido e a realização de uma encomenda do utilizador recomendado, o recomendador recebe os pontos definidos (definido no painel)
