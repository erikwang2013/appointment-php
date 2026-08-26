> Tradução em português · Original: [中文](../STRUCTURE.md)

# Sistema de serviços de agendamento — estrutura do projeto
> **Languages**: [中文](../STRUCTURE.md) · [English](../en/STRUCTURE.md) · [한국어](../ko/STRUCTURE.md) · [Русский](../ru/STRUCTURE.md) · [Deutsch](../de/STRUCTURE.md) · [Français](../fr/STRUCTURE.md) · [Español](../es/STRUCTURE.md) · [हिन्दी](../hi/STRUCTURE.md) · [العربية](../ar/STRUCTURE.md) · [বাংলা](../bn/STRUCTURE.md) · [Bahasa Indonesia](../id/STRUCTURE.md) · [日本語](../ja/STRUCTURE.md)

## Visão geral do repositório

```
appointment-php/
├── admin/              # Painel de administração (webman v2 + Flutter Web)
├── service/            # Serviço de API de negócio (webman v2)
├── apps/               # Aplicações frontend do lado do utilizador
│   ├── wechat/         #   Miniprograma WeChat (nativo)
│   ├── flutter/        #   APP Flutter (iOS + Android)
│   └── harmonyos/      #   APP HarmonyOS (nativo ArkTS)
├── docs/               # Documentação do projeto
└── .claude/            # Configuração do Claude Code
```

## Relações do projeto

```
┌──────────────────────────────────────────────┐
│                   apps/                       │
│  ┌─────────────┐  ┌──────────┐  ┌─────────┐  │
│  │ wechat/      │  │ flutter/  │  │harmonyos/│  │
│  │ Miniprograma │  │iOS/Android│  │ APP     │  │
│  │ WeChat       │  │           │  │HarmonyOS│  │
│  └──────┬──────┘  └────┬─────┘  └────┬────┘  │
│         │   funcionalidades idênticas │      │
│         └──────────┬─────────┘            │
│                    │ HTTP API                 │
├────────────────────┼─────────────────────────┤
│              service/                         │
│          API de negócio (webman v2)           │
│              Porta: 8787                      │
│                    │                          │
│                    │ MySQL/Redis/ES partilhados│
│                    │                          │
│              admin/                           │
│          API do painel (webman v2)            │
│              Porta: 8787                      │
│                    │                          │
│         ┌──────────┴──────────┐               │
│         │                     │               │
│    admin/apps/flutter/    Flutter Web         │
│     Frontend do painel (PC)                   │
└──────────────────────────────────────────────┘
```

## admin/ — Painel de administração

```
admin/
├── app/
│   ├── admin/controller/       # Controladores do lado da gestão
│   │   ├── BaseController          # Controlador base
│   │   ├── DashboardController     # Dashboard
│   │   ├── UserController          # Gestão de utilizadores
│   │   ├── RoleController          # Gestão de papéis
│   │   ├── PermissionController    # Gestão de permissões
│   │   ├── ConfigController        # Configuração do sistema
│   │   ├── LogController           # Registos de operações
│   │   ├── ProfileController       # Centro pessoal
│   │   ├── ExportController        # Exportação
│   │   ├── ImportController        # Importação
│   │   ├── UploadController        # Carregamento de ficheiros
│   │   ├── HealthController        # Verificação de saúde
│   │   ├── DocsController          # Documentação da API
│   │   ├── MetricsController       # Métricas Prometheus
│   │   │                            # ✅ Módulos de negócio implementados:
│   │   ├── TechnicianController    #   Gestão de técnicos (lista/aprovação/horário/exportação)
│   │   ├── MemberController        #   Gestão de membros (nível/consumo)
│   │   ├── StoreController         #   CRUD de lojas
│   │   ├── ServiceController       #   CRUD de serviços
│   │   ├── ServiceCategoryController # CRUD de categorias de serviço (árvore)
│   │   ├── ProductController       #   CRUD de produtos
│   │   ├── MallOrderController     #   Encomendas do comércio/expedição/pós-venda
│   │   ├── SalesStatsController    #   Estatísticas de vendas (cache Redis)
│   │   ├── AppointmentOrderController  # Encomendas de marcação (cancelar/concluir)
│   │   ├── MemberCardController    #   CRUD de definições de cartões de membro
│   │   ├── ReviewController        #   Gestão de avaliações de serviço
│   │   ├── ReportController        #   Relatórios e estatísticas de dados
│   │   ├── CouponController        #   CRUD de cupões
│   │   ├── FinanceController       #   Movimentos financeiros/estatísticas
│   │   ├── WithdrawalController    #   Aprovação de levantamentos (aprovar/rejeitar/concluir)
│   │   ├── CommissionController    #   Definição de comissões/recompensas e penalizações
│   │   ├── WithdrawalAccountController # Gestão de contas de levantamento
│   │   ├── WithdrawalConfigController  # Configuração de limites de levantamento
│   │   ├── BannerController        #   CRUD de carrosséis
│   │   ├── AnnouncementController  #   CRUD/publicação de anúncios
│   │   ├── FaqController           #   CRUD de perguntas frequentes
│   │   ├── FeedbackController      #   Feedback/respostas
│   │   ├── MomentController        #   Aprovação de publicações de momentos
│   │   ├── AgreementController     #   Edição/publicação de acordos
│   │   ├── AboutController         #   Definição «Sobre nós»
│   │   └── SystemMessageController #   Modelos/envio de mensagens do sistema
│   │   │                            # ✅ Módulos de extensão:
│   │   ├── ServiceCardController    #   Design de cartões de serviço
│   │   ├── SystemMonitorController  #   Monitorização do sistema
│   │   ├── IpBlacklistController    #   Gestão de lista negra de IPs
│   │   ├── DbBackupController       #   Backup da base de dados
│   │   ├── SmsConfigController      #   Configuração de SMS
│   │   ├── StorageConfigController  #   Configuração de armazenamento
│   │   ├── StoreManagerController   #   Contas de gerentes de loja
│   │   ├── TrainingController       #   Formação de técnicos
│   │   ├── ScheduledTaskController  #   Tarefas agendadas
│   │   ├── CustomerProfileController #  Perfis de clientes
│   │   ├── BatchMessageController   #   Envio em massa
│   │   ├── RefundWorkflowController #   Aprovação de reembolsos
│   │   ├── TechnicianTierController #   Níveis de técnicos
│   │   │                            # ✅ Novos nas rondas 22-25:
│   │   ├── FullReductionController  #   Atividades de desconto direto
│   │   ├── AttendanceController     #   Presenças de técnicos
│   │   ├── ProfitSharingController  #   Partilha de lucros WeChat
│   │   ├── LuckyWheelController     #   Roda da sorte de pontos
│   │   ├── PointsExchangeGoodsController # Mercadorias de troca por pontos
│   │   ├── ReviewAuditController    #   Aprovação de imagens de avaliações
│   │   ├── InvoiceController        #   Fatura eletrónica
│   │   ├── TicketController         #   Tickets de apoio ao cliente
│   │   ├── ReferralRewardController #   Registos de comissão de primeiro nível
│   │   ├── ReferralLevel2Controller #   Registos de comissão de segundo nível
│   │   ├── ReturnCustomerController #   Recompensas de clientes recorrentes
│   │   ├── SeckillController        #   Atividades de vendas relâmpago
│   │   ├── VersionController        #   Gestão de versões da APP
│   │   ├── TechnicianScheduleController # Gestão de horários/exportação CSV
│   │   ├── AftersaleController      #   Tratamento de pós-venda
│   │   ├── OrderVerificationController # Registos de verificação
│   │   ├── CommunityModerationController # Aprovação de comunidade
│   │   ├── VideoAuditController     #   Aprovação de vídeos
│   │   └── InstallController        #   Assistente de instalação
│   ├── api/v1/controller/      # API pública v1
│   │   ├── AuthController
│   │   └── CaptchaController
│   ├── common/                 # Utilitários comuns
│   │   ├── HashidsService
│   │   ├── SnowflakeService
│   │   ├── EncryptionService
│   │   ├── TechnicianWithdrawalService
│   │   └── WechatPayService
│   ├── middleware/             # Middleware
│   │   ├── Cors
│   │   ├── RateLimit
│   │   ├── ApiVersion
│   │   ├── AdminAuth
│   │   ├── AdminPermission
│   │   └── OperationLog
│   ├── model/                  # Modelos de dados (apenas 6 modelos específicos: AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig; os restantes partilham a versão do service via psr-4)
│   ├── queue/                  # Tarefas de fila
│   └── process/                # Processos
├── apps/
│   ├── flutter/                # Frontend do painel de administração Flutter Web
│   │   └── lib/app/
│   │       ├── pages/           #   Páginas (20)
│   │       │   ├── dashboard/   #   Dashboard
│   │       │   ├── login/       #   Início de sessão
│   │       │   ├── user/        #   Gestão de utilizadores
│   │       │   ├── member/      #   Gestão de membros
│   │       │   ├── role/        #   Papéis e permissões
│   │       │   ├── config/      #   Configuração do sistema
│   │       │   ├── log/         #   Registos de operações
│   │       │   ├── profile/     #   Centro pessoal
│   │       │   ├── technician/  #   Gestão de técnicos
│   │       │   ├── schedule/    #   Horários
│   │       │   ├── service/     #   Gestão de serviços/produtos
│   │       │   ├── service_card/#   Design de cartões de serviço
│   │       │   ├── order/       #   Gestão de encomendas
│   │       │   ├── verification/#   Registos de verificação
│   │       │   ├── coupon/      #   Cupões
│   │       │   ├── withdrawal/  #   Aprovação de levantamentos
│   │       │   ├── report/      #   Relatórios e estatísticas
│   │       │   ├── review/      #   Gestão de avaliações
│   │       │   ├── announcement/#   Anúncios
│   │       │   └── faq/         #   Perguntas frequentes
│   │       ├── services/        #   Camada de serviços API
│   │       ├── layouts/         #   Layouts
│   │       └── theme/           #   Temas
│   ├── harmonyos/               # Lado da gestão HarmonyOS (ArkTS)
│   └── weixin/                  # Lado da gestão WeChat
├── config/                     # Ficheiros de configuração
│   ├── route.php
│   ├── middleware.php
│   ├── database.php
│   ├── jwt.php
│   ├── snowflake.php
│   ├── hashids.php
│   ├── encryption.php
│   ├── encryptable.php
│   └── ...
├── database/
│   └── backup/                 # Scripts de backup (estrutura de tabelas e seeds unificados em docs/install.sql)
├── docs/                       # Documentação do painel de administração
├── public/                     # Ficheiros de entrada
├── runtime/                    # Runtime
├── tests/                      # Testes
├── vendor/                     # Dependências
├── CLAUDE.md
├── composer.json
├── Dockerfile
└── docker-compose.yml
```

## service/ — API de negócio

```
service/
├── app/
│   ├── api/v1/controller/       # API pública v1 (26 controladores)
│   │   ├── AuthController          # Início de sessão/registo/esquecer palavra-passe/refresh/alternância de identidade
│   │   ├── CaptchaController       # Códigos de verificação SMS (limitação Redis)
│   │   ├── CommonController        # Configuração pública/acordos/regiões
│   │   ├── ContentController       # Carrosséis/anúncios/artigos
│   │   ├── DocsController          # Documentação OpenAPI (hg/apidoc)
│   │   ├── LbsController           # Lojas próximas (Haversine)/geocódigo inverso
│   │   ├── GuestController         # Modo convidado (navegação só-leitura sem início de sessão, cache Redis)
│   │   ├── SeckillController       # Atividades de vendas relâmpago/compras (canal independente)
│   │   ├── PromotionController     # Compras em grupo (o antigo canal flash_sale foi descontinuado)
│   │   ├── ServiceController       # Categorias de serviço/projetos/produtos/lojas
│   │   ├── ServicePackageController # Pacotes de serviços
│   │   ├── StoreManagerController  # Secretária do gerente de loja (overview/orders/technicians/revenue)
│   │   ├── TechnicianController    # Informação pública de técnicos
│   │   ├── BrowseHistoryController # Histórico de navegação
│   │   ├── CalendarController      # Calendário de marcações (vista mensal/diária)
│   │   ├── CommunityController     # Publicações de comunidade
│   │   ├── CommunityCommentController # Comentários de comunidade
│   │   ├── FullReductionController # Atividades de desconto direto
│   │   ├── PaymentNotifyController # Callbacks de pagamento (WeChat/Alipay)
│   │   ├── PrintController         # Impressão
│   │   ├── PrivacyController       # Conformidade de privacidade (exportação de dados/eliminação de conta)
│   │   ├── QueueController         # Fila com chamada de números
│   │   ├── VersionController       # Gestão de versões da APP/deteção de atualizações
│   │   ├── VideoController         # Vídeos
│   │   ├── WechatController        # Relacionado com WeChat
│   │   └── WheelController         # Roda da sorte de pontos
│   ├── user/v1/controller/      # Módulo de utilizador v1 (14 controladores)
│   │   ├── ProfileController       # Informação pessoal/palavra-passe/telemóvel/eliminação de conta/término de sessão
│   │   ├── AddressController       # CRUD de endereços (gestão de endereço predefinido)
│   │   ├── FavoriteController      # Favoritos (serviços/técnicos)
│   │   ├── FeedbackController      # Feedback (texto + imagens)
│   │   ├── ReferralController      # Divulgação/código QR/utilizadores recomendados
│   │   ├── CheckInController       # Check-in diário
│   │   ├── DeviceController        # Gestão de dispositivos do utilizador
│   │   ├── GrowthController        # Nível de crescimento (overview/records/levels)
│   │   ├── HealthProfileController # Ficheiro de saúde
│   │   ├── InvoiceController       # Pedido/lista/detalhes de faturas eletrónicas
│   │   ├── InvoiceTitleController  # Biblioteca de títulos de fatura
│   │   ├── NotifySettingController # Preferências de notificações
│   │   ├── PointsTransferController# Transferência de pontos
│   │   └── TicketController        # Tickets de apoio ao cliente
│   ├── technician/v1/controller/ # Módulo de técnico v1 (10 controladores)
│   │   ├── ProfileController       # Perfil de técnico/candidatura de adesão
│   │   ├── ScheduleController      # Consulta/definição de horários
│   │   ├── OrderController         # Lista de encomendas do técnico
│   │   ├── WorkController          # Secretária de trabalho (today/records/start/complete)
│   │   ├── EarningController       # Visão geral de rendimentos + movimentos
│   │   ├── WithdrawController      # Pedido de levantamento (dia `config('withdraw.gate_day')` de cada mês, configurável)
│   │   ├── ServiceRecordController # Registos de serviço
│   │   ├── ExamController          # Exame online
│   │   ├── AttendanceController    # Check-in/out de presenças
│   │   └── ReviewController        # Resposta do técnico a avaliações
│   ├── order/v1/controller/     # Módulo de encomendas v1 (8 controladores + 9 traits)
│   │   ├── OrderController         # Criar encomenda (bloqueio de técnico)/lista/detalhes/cancelar/pagar/reembolsar/verificar (entrada agregada, 38 linhas, métodos todos de traits)
│   │   ├── OrderCreateTrait        # Criação de encomendas store/auxiliares de cálculo (475 linhas)
│   │   ├── OrderQueryTrait         # Consulta de encomendas lista/detalhes/logística (205 linhas)
│   │   ├── OrderPayTrait           # Pagamento pay/saldo/pontos (415 linhas)
│   │   ├── OrderCancelTrait        # Cancelamento de encomendas (272 linhas)
│   │   ├── OrderRefundTrait        # Pedido de reembolso (379 linhas)
│   │   ├── OrderCompensateTrait    # Scan de compensação de reembolsos+devolução de descontos/pontos (345 linhas)
│   │   ├── OrderVerifyTrait        # Verificação comissão/devolução de pontos (256 linhas)
│   │   ├── OrderRescheduleTrait    # Remarcação de marcações (181 linhas)
│   │   ├── OrderNotifyTrait        # Notificações subscrição/modelos/intra-site/WebSocket (195 linhas)
│   │   └── OrderLockTrait          # Utilitário de bloqueios distribuídos (80 linhas)
│   │   ├── AftersaleController     # Pós-venda
│   │   ├── CartController          # Carrinho de compras
│   │   ├── IcsController           # Exportação de calendário ICS
│   │   ├── ReviewController        # Avaliações/avaliações de seguimento
│   │   ├── SignatureController     # Assinaturas
│   │   ├── TimelineController      # Linha temporal de estados da encomenda
│   │   └── WaitlistController      # Lista de espera
│   ├── wallet/v1/controller/    # Módulo de carteira v1 (2 controladores)
│   │   ├── WalletController        # Saldo/recarga/movimentos de transações/pagamento com saldo
│   │   └── WalletTransferController# Transferências entre utilizadores
│   ├── marketing/v1/controller/ # Módulo de marketing v1 (7 controladores)
│   │   ├── CouponController        # Lista de cupões/obtenção/desconto ao encomendar
│   │   ├── CardController          # Lista de cartões de membro/compra/cartão de vezes my/use
│   │   ├── PointController         # Movimentos de pontos/consumo de recompensa
│   │   ├── GiftCardController      # Cartões-presente/troca redeem
│   │   ├── MemberBenefitController # Benefícios de membro
│   │   ├── MemberCardController    # Definições de cartões de membro
│   │   └── PointsExchangeController# Loja de troca por pontos
│   ├── notification/v1/controller/ # Módulo de notificações v1 (1 controlador)
│   │   └── NotificationController  # Lista de notificações/marcar como lida
│   ├── common/                  # Capacidades comuns (BaseController, etc.)
│   ├── middleware/              # Middleware
│   │   ├── ApiVersion              # Controlo de versão da API (cabeçalho API-Version)
│   │   ├── Auth                    # Autenticação JWT + validação do estado do utilizador
│   │   ├── Cors                    # Tratamento de cross-domain
│   │   ├── Security                # Deteção de segurança (security-php)
│   │   └── TechnicianAuth          # Validação de identidade do técnico
│   └── model/                   # Modelos de dados (81)
│       ├── User.php → erik_user
│       ├── TechnicianProfile.php → erik_technician_profile
│       ├── Service.php → erik_service (ES: erik_services)
│       ├── Product.php → erik_product (ES: erik_products)
│       ├── Store.php → erik_store
│       ├── Order.php → erik_order (inclui regras de reembolso/máquina de estados)
│       ├── Coupon.php → erik_coupon
│       ├── MemberCard.php → erik_member_card
│       ├── Notification.php → erik_notification
│       └── ... (81 ficheiros de modelos no total; o admin tem mais 6 modelos específicos, total 87)
├── config/                     # Ficheiros de configuração
├── public/                     # Entrada
├── runtime/                    # Runtime
├── vendor/                     # Dependências
├── start.php
├── composer.json
└── Dockerfile
```

## apps/ — Frontend do lado do utilizador

### apps/wechat/ — Miniprograma WeChat

```
apps/wechat/
├── app.js                      # Entrada da aplicação
├── app.json                    # Configuração global
├── app.wxss                    # Estilos globais
├── pages/
│   ├── auth/                   # Autenticação
│   │   ├── login               #   Início de sessão
│   │   ├── register            #   Registo
│   │   ├── forget-password     #   Esquecer palavra-passe
│   │   └── agreement           #   Visualização de acordos
│   ├── home/                   # Página inicial (carrosséis/anúncios/categorias/pesquisa)
│   ├── service/                # Serviços
│   │   ├── list                #   Lista de serviços
│   │   └── detail              #   Detalhes do serviço
│   ├── order/                  # Encomendas
│   │   ├── list                #   Lista de encomendas
│   │   ├── detail              #   Detalhes da encomenda
│   │   └── confirm             #   Confirmar encomenda
│   ├── cart/                   # Carrinho de compras
│   ├── cards/                  # Cartões de membro (comprar/os meus/uso do cartão de vezes my/use)
│   ├── gift-cards/             # Cartões-presente (troca redeem/registo)
│   ├── points/                 # Pontos (movimentos/troca)
│   ├── marketing/              # Marketing (cupões, etc.)
│   ├── favorite/               # Favoritos
│   ├── feedback/               # Feedback
│   ├── referral/               # Divulgação
│   ├── message/                # Mensagens
│   │   ├── list                #   Lista de mensagens
│   │   └── detail              #   Detalhes da mensagem
│   ├── tech-work/              # Secretária do técnico
│   │   ├── index               #   Página inicial da secretária (today/records/start/complete)
│   │   ├── schedule            #   Horários
│   │   ├── order-list          #   Encomendas
│   │   ├── scan-verify         #   Verificação por leitura de código QR
│   │   ├── member-list         #   Lista de membros
│   │   ├── member-detail       #   Detalhes do membro
│   │   ├── earnings            #   Rendimentos
│   │   ├── withdrawal          #   Levantamentos
│   │   ├── transaction-list    #   Movimentos de transações
│   │   └── training            #   Formação
│   ├── user/                   # Centro pessoal
│   │   ├── index               #   Informação pessoal
│   │   ├── settings            #   Definições
│   │   └── switch-role         #   Alternância de identidade
│   └── wallet/                 # Carteira (saldo/recarga/movimentos de transações)
├── components/                 # Componentes comuns
│   ├── navbar
│   ├── tabbar
│   ├── service-card
│   ├── technician-card
│   ├── coupon-popup
│   └── lbs-selector
├── utils/                      # Utilitários
│   ├── api.js                  #   Pedidos HTTP
│   ├── auth.js                 #   Gestão de autenticação
│   ├── location.js             #   Localização LBS
│   └── constants.js            #   Constantes
├── styles/                     # Estilos comuns
└── images/                     # Recursos de imagem
```

### apps/flutter/ — APP Flutter

```
apps/flutter/
├── lib/
│   ├── main.dart               # Entrada
│   ├── app.dart                # Configuração da App/rotas/temas
│   ├── pages/                  # Páginas (estrutura consistente com o miniprograma)
│   │   ├── auth/
│   │   ├── home/
│   │   ├── service/
│   │   ├── order/
│   │   ├── cart/
│   │   ├── technician/
│   │   ├── tech_work/
│   │   ├── user/
│   │   ├── marketing/
│   │   ├── message/
│   │   ├── store/
│   │   └── other/
│   ├── widgets/                # Componentes comuns
│   ├── services/               # Serviços API
│   │   ├── api_service         #   HTTP (Dio)
│   │   ├── auth_service        #   Autenticação
│   │   └── location_service    #   Localização
│   ├── models/                 # Modelos de dados
│   ├── state/                  # Gestão de estado
│   └── utils/                  # Utilitários
├── android/                    # Projeto Android
├── ios/                        # Projeto iOS
├── pubspec.yaml
└── ...
```

## Cadeias de execução do middleware

### service/

```
API pública:  Cors → Security → RateLimit → Controller
API do utilizador:  Cors → Security → RateLimit → Auth → Controller
API do técnico:  Cors → Security → RateLimit → Auth → TechnicianAuth → Controller
Callback de pagamento: Cors → Security → Controller
```

### admin/

```
API pública:  Cors → Security → RateLimit → Controller
API de gestão:  Cors → Security → RateLimit → AdminAuth → AdminPermission → OperationLog → Controller
Verificação de saúde: Cors → Security → RateLimit → Controller
```

## Lista de tabelas da base de dados

Todas as tabelas usam o prefixo `erik_`, chave primária BIGINT não autoincrementada (gerada pelo Snowflake).

| Domínio | Nome da tabela | Descrição |
|----|------|------|
| Utilizador | erik_user | Tabela de utilizadores unificada |
| Utilizador | erik_user_address | Endereços de envio |
| Técnico | erik_technician_profile | Perfis de técnicos |
| Técnico | erik_technician_schedule | Horários de técnicos |
| Técnico | erik_technician_service | Serviços que o técnico pode realizar |
| Técnico | erik_technician_earnings | Movimentos de rendimentos de técnicos |
| Técnico | erik_technician_withdrawal | Registos de levantamentos de técnicos |
| Técnico | erik_technician_attendance | Presenças de técnicos |
| Técnico | erik_technician_member_note | Ficheiros de membros |
| Serviço | erik_service_category | Categorias de serviço |
| Serviço | erik_service | Serviços |
| Serviço | erik_product | Produtos |
| Serviço | erik_store | Lojas |
| Encomenda | erik_order | Tabela principal de encomendas (coluna de associação seckill_id, ronda 24) |
| Encomenda | erik_order_item | Detalhes da encomenda |
| Encomenda | erik_order_payment | Registos de pagamento |
| Encomenda | erik_order_refund | Registos de reembolso |
| Encomenda | erik_order_review | Avaliações de serviço |
| Encomenda | erik_order_verification | Registos de verificação |
| Encomenda | erik_order_reschedule | Registos de remarcação (ronda 17) |
| Marketing | erik_coupon | Definições de cupões |
| Marketing | erik_user_coupon | Cupões do utilizador |
| Marketing | erik_user_coupon_transfer | Registos de oferta de cupões (ronda 17) |
| Marketing | erik_user_points_transfer | Registos de transferência de pontos (ronda 19) |
| Marketing | erik_technician_tier_log | Registos de alteração de nível de técnico (ronda 17) |
| Marketing | erik_member_card | Definições de cartões de membro |
| Marketing | erik_user_member_card | Cartões de membro do utilizador |
| Marketing | erik_member_card_usage | Registos de uso do cartão de vezes |
| Marketing | erik_user_points | Movimentos de pontos |
| Marketing | erik_gift_card | Cartões-presente |
| Marketing | erik_user_referral | Divulgação do utilizador |
| Marketing | erik_user_favorite | Favoritos do utilizador |
| Carteira | erik_user_wallet | Saldo da carteira do utilizador |
| Carteira | erik_wallet_recharge | Registos de recarga da carteira |
| Carteira | erik_wallet_txn | Movimentos de transações da carteira |
| Carteira | erik_wallet_transfer | Registos de transferências entre utilizadores (ronda 19) |
| Utilizador | erik_user_notify_setting | Preferências de notificações (ronda 19) |
| Conteúdo | erik_banner | Carrosséis |
| Conteúdo | erik_announcement | Anúncios |
| Conteúdo | erik_platform_agreement | Acordos da plataforma |
| Conteúdo | erik_faq | Perguntas frequentes |
| Conteúdo | erik_feedback | Feedback |
| Conteúdo | erik_moment | Publicações de momentos |
| Conteúdo | erik_notification | Notificações |
| Financeiro | erik_finance_transaction | Movimentos de receitas e despesas |
| Financeiro | erik_technician_commission_config | Configuração de comissões |
| Financeiro | erik_withdrawal_account | Contas de levantamento |
| Financeiro | erik_withdrawal_config | Configuração de limites de levantamento |
| Sistema | erik_admin_user | Utilizadores de gestão (criada) |
| Sistema | erik_admin_role | Papéis (criada) |
| Sistema | erik_admin_permission | Permissões (criada) |
| Sistema | erik_admin_user_role | Associação utilizador-papel (criada) |
| Sistema | erik_admin_role_permission | Associação papel-permissão (criada) |
| Sistema | erik_system_config | Configuração do sistema (criada) |
| Sistema | erik_operation_log | Registos de operações (criada) |
| Utilizador | erik_user_growth | Movimentos de valor de crescimento (ronda 20) |
| Utilizador | erik_growth_level | Níveis de crescimento (ronda 20) |
| Encomenda | erik_invoice | Faturas eletrónicas (ronda 20) |
| Utilizador | erik_ticket | Tickets de apoio ao cliente (ronda 20) |
| Marketing | erik_referral_level2_reward | Registos de comissão de segundo nível (ronda 20) |
| Utilizador | erik_invoice_title | Biblioteca de títulos de fatura (ronda 21) |
| Utilizador | erik_browse_history | Histórico de navegação (ronda 21) |
| Marketing | erik_full_reduction_activity | Atividades de desconto direto (ronda 22) |
| Técnico | erik_technician_attendance | Presenças de técnicos (ronda 22) |
| Sistema | erik_push_log | Registos de push da APP (ronda 22) |
| Financeiro | erik_profit_sharing | Registos de partilha de lucros WeChat (ronda 22) |
| Encomenda | erik_order_status_log | Linha temporal de estados da encomenda (ronda 23) |
| Utilizador | erik_user_health_profile | Ficheiros de saúde do utilizador (ronda 23) |
| Marketing | erik_lucky_wheel | Definições de prémios da roda da sorte (ronda 23) |
| Marketing | erik_wheel_record | Registos de sorteios da roda (ronda 23) |
| Marketing | erik_seckill_activity | Atividades de vendas relâmpago (ronda 24) |
| Sistema | erik_app_version | Versões da APP (ronda 24) |

### Lista complementar (parte das 95 tabelas do docs/install.sql não listada acima; a lista completa e autoritativa é o install.sql)

| Domínio | Nome da tabela | Descrição |
|----|------|------|
| Marketing | erik_card_transfer | Oferta de cartões de vezes |
| Utilizador | erik_check_in | Check-in diário |
| Conteúdo | erik_community_post | Publicações de comunidade |
| Conteúdo | erik_community_comment | Comentários de comunidade |
| Técnico | erik_exam | Exames |
| Técnico | erik_exam_question | Perguntas de exame |
| Técnico | erik_exam_attempt | Respostas de exame |
| Sistema | erik_operation_log_detail | Detalhes de registos de operações |
| Encomenda | erik_order_aftersale | Pós-venda de encomendas |
| Marketing | erik_points_exchange_goods | Mercadorias de troca por pontos |
| Marketing | erik_promotion | Atividades de compras em grupo |
| Marketing | erik_promotion_participant | Participantes de compras em grupo |
| Encomenda | erik_queue_number | Fila com chamada de números |
| Serviço | erik_service_package | Pacotes de serviços |
| Técnico | erik_service_record | Registos de serviço |
| Conteúdo | erik_share | Registos de partilha |
| Encomenda | erik_signature | Assinaturas |
| Técnico | erik_technician_tier_config | Configuração de níveis de técnicos |
| Técnico | erik_training_course | Cursos de formação |
| Técnico | erik_training_progress | Progresso de formação |
| Utilizador | erik_user_device | Dispositivos do utilizador |
| Marketing | erik_user_points_exchange | Registos de troca por pontos |
| Conteúdo | erik_video_post | Publicações de vídeo |
| Encomenda | erik_waitlist | Listas de espera |

## Serviços externos reservados

| Serviço | Utilização | Ponto de integração |
|------|------|--------|
| Plataforma aberta WeChat | Início de sessão WeChat/UnionID | WechatAuthService |
| WeChat Pay | Pagamento/reembolso/levantamento | WechatPayService |
| Fornecedor de SMS | Códigos de verificação/notificações | SmsService |
| Serviço de mapas | Localização LBS/navegação/cálculo de distâncias | MapService |
