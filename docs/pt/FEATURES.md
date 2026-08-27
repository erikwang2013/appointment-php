> Tradução em português · Original: [中文](../FEATURES.md)

# Descrição de funcionalidades
> **Languages**: [中文](../FEATURES.md) · [English](../en/FEATURES.md) · [한국어](../ko/FEATURES.md) · [Русский](../ru/FEATURES.md) · [Deutsch](../de/FEATURES.md) · [Français](../fr/FEATURES.md) · [Español](../es/FEATURES.md) · [हिन्दी](../hi/FEATURES.md) · [العربية](../ar/FEATURES.md) · [বাংলা](../bn/FEATURES.md) · [Bahasa Indonesia](../id/FEATURES.md) · [日本語](../ja/FEATURES.md)

> **Estado do projeto**: Concluído ✅ | 109 controladores | 103 modelos | 344 testes (service 240 / admin 104) | WebSocket | Callbacks de pagamento | Chamadas de senha | Avaliações | Comunidade

## I. Lado do utilizador (miniprograma WeChat + APP Flutter)

O miniprograma e a APP do lado do utilizador têm funcionalidades idênticas. A conta unificada suporta a alternância de identidade cliente/técnico.

### 1. Autenticação

| Funcionalidade | Descrição |
|------|------|
| Registo com número de telefone | Telefone + código de verificação + palavra-passe + confirmação de palavra-passe, com suporte a código de recomendação |
| Login com palavra-passe | Telefone registado + palavra-passe |
| Login com código de verificação | Telefone registado + código de verificação |
| Login WeChat | Login autorizado pelo WeChat, com vínculo de telefone obrigatório no primeiro acesso |
| Modo convidado | Pode navegar, mas não encomendar; encomendar requer registo |
| Palavra-passe esquecida | Alteração da palavra-passe com código de verificação |
| Acordo do utilizador/acordo de privacidade | Editável no painel de administração, exibido no registo |

### 2. Página inicial

| Funcionalidade | Descrição |
|------|------|
| Geolocalização LBS | Localiza a região atual, exibe os serviços dessa região, com suporte a troca de cidade |
| Carrossel | Reprodução automática, com saltos configurados no painel de administração (página web/detalhes/sem ação) |
| Anúncios | Reprodução em rolo, clique para ver a lista, adicionados no painel de administração |
| Categorias de serviços | Imagem/nome/preço/volume de vendas, clique para ver os detalhes |
| Cupão para novos utilizadores | Obtido automaticamente no registo |

### 3. Projetos de serviço

| Funcionalidade | Descrição |
|------|------|
| Informações básicas | Imagem/nome/preço/volume de vendas/especificações/duração do serviço/detalhes do projeto |
| Avaliações de utilizadores | Exibição do conteúdo das avaliações, com possibilidade de ver mais |
| Agendar serviço | Entra na página de confirmação de pedido |
| Seleção de loja | Morada da loja do serviço (navegação)/horário de funcionamento/telefone de contacto |
| Seleção de técnico | Nome do técnico/avatar/classificação |
| Horário do serviço | Seleção do período de agendamento |
| 10% de desconto em horas de menor procura | 10h-12h/17h-18h/ após as 21h |
| 5% de desconto por agendamento antecipado | 30 minutos de antecedência, não acumulável com cupões |
| Cupões | Exibição do valor disponível, usar/não usar |
| Observações | Notas sobre as necessidades do serviço (limite de caracteres) |
| Acordo de serviço | Leitura e confirmação antes da submissão |

### 4. Pesquisa de produtos e carrinho de compras

| Funcionalidade | Descrição |
|------|------|
| Pesquisa de produtos | Pesquisa por nome |
| Filtro por categoria | Pesquisa por categoria |
| Detalhes do produto | Quantidade comprável/favoritos/partilha/adicionar ao carrinho/comprar agora |
| Carrinho de compras | Selecionar/eliminar/alterar quantidade |

### 5. Pedidos

| Funcionalidade | Descrição |
|------|------|
| Todos os pedidos | Visualização por separadores de estado |
| A aguardar pagamento | Ver/pagar |
| A aguardar expedição/levantamento | Solicitar expedição/cancelar pedido/ver |
| A aguardar receção | Informações de logística/confirmar receção |
| A aguardar avaliação | Detalhes do pedido/avaliação com texto + imagens |
| Concluídos | Visualização das informações do pedido |
| Regras de reembolso | Dentro de 15 min ou >6h antes do início 100% / <6h 90% / após início 80% / após confirmação sem reembolso |

### 6. Técnicos (perspetiva do cliente)

| Funcionalidade | Descrição |
|------|------|
| Lista de técnicos | Do mais próximo ao mais distante/avatar/nome/número de pedidos/classificação/favoritos/distância/horários disponíveis/agendar agora |
| Detalhes do técnico | Imagem/nome/distância/pedidos/avaliações/favoritos/lista de serviços disponíveis |
| Adesão do técnico | Preenchimento de informações para se candidatar a técnico, descarregar a APP do técnico |

### 7. Bancada de trabalho do técnico (após alternância de identidade)

| Funcionalidade | Descrição |
|------|------|
| Resumo de hoje | Visão geral dos pedidos/rendimentos de hoje |
| Definição de agendamentos | Definição diária dos períodos disponíveis para agendamento |
| Os meus pedidos | Agendados sem verificação/concluídos |
| Verificação por leitura de QR code | Ler o QR code do utilizador para verificar a utilização de vezes |
| Gestão de membros | Lista de membros atendidos/dados de consumo de aulas/cartões de vezes/edição de ficheiros |
| Gestão de rendimentos | Rendimentos de hoje/em liquidação/saldo da carteira |
| Fundos em trânsito | Verificados mas não liquidados, confirmação automática em 3 dias |
| Levantamento | Dia 20 de cada mês, T+1 para o saldo WeChat; aprovação no painel de gestão, montante ≥500 com aprovação em dois níveis (gerente de loja → financeiro); no pedido, reserva do saldo em trânsito, reavaliação antes da transferência na aprovação, aprovação concorrente contra duplo pagamento (reforço 2026-08-26) |
| Assiduidade | Registo de ponto de entrada/saída/carregamento de foto da higiene |
| Recompensa por cliente recorrente | Bónus registado por segundo consumo em 30 dias |
| Formação profissional | Cursos em vídeo/cursos em texto e imagem |
| Tarefas de hoje | WorkController today: obtenção em tempo real das tarefas pendentes de hoje |
| Registo de conclusões | WorkController records: histórico de conclusões |
| Início/conclusão do serviço | WorkController start/complete: bloqueio de linha + guardas da máquina de estados + idempotência, escreve automaticamente notificação interna após conclusão |
| Bancada de trabalho do miniprograma | tech-work com três separadores: verificação por QR code/tarefas de hoje/registo de conclusões |

### 8. Centro pessoal

| Funcionalidade | Descrição |
|------|------|
| Informações pessoais | Avatar/apelido/número de telefone |
| Alternância de identidade | Cliente ↔ técnico |
| Notificações de mensagens | Notificações internas (appointment_notification); página do centro de mensagens: paginação/atualização por arrastar/destaque de lidas/marcar como lida/marcar tudo como lido |
| Os meus cartões de membro | Cartão mensal/cartão anual VIP/cartão de vezes (expiração/vezes/utilizadas/restantes) |
| Os meus pontos | Registo de obtenção/pontos disponíveis/registo de utilização (1:100 para trocar por cartão-presente); pontos por registo diário/consumo, reembolso desconta proporcionalmente, extratos paginados + filtro type/source |
| Os meus cartões-presente | Cartão de valor/cartão físico; o tipo cash no redeem recarrega diretamente na carteira |
| Cupões | Obtidos e disponíveis/utilizados/expirados |
| Os meus favoritos | Projetos de serviço favoritos |
| Seguir a conta oficial | Janela pop-up com QR code, pressionar para guardar |
| Promoção do utilizador | Instruções de promoção/cartaz com QR code/lista de utilizadores recomendados/recompensas em pontos |
| Feedback de opiniões | Submissão com texto + imagens, resposta em 24h |
| Sobre nós | LOGO/apresentação/telefone de apoio/site/e-mail |

### 9. Definições

| Funcionalidade | Descrição |
|------|------|
| Alterar palavra-passe | Palavra-passe atual + nova palavra-passe + confirmação da nova palavra-passe |
| Alterar telefone vinculado | Código de verificação do telefone atual + código de verificação do novo telefone |
| Acordo do utilizador | Exibição de texto, editável no painel |
| Acordo de privacidade | Exibição de texto, editável no painel |
| Verificar atualizações | Número de versão + atualização |
| Cancelamento de conta | Instruções de cancelamento + confirmação da operação |
| Terminar sessão | Limpar o estado de login |

### 10. Carteira de saldo (ronda 6)

| Funcionalidade | Descrição |
|------|------|
| Saldo da carteira | GET /api/wallet saldo + movimentos (tabelas user_wallet/wallet_recharge/wallet_txn) |
| Recarga | POST /api/wallet/recharge cria a ordem de recarga; POST /api/wallet/recharge/{id}/pay pagamento WeChat da recarga, callback com número de ordem com prefixo R |
| Pagamento com saldo | Canal de pagamento do pedido pay_channel=balance |
| Reembolso reposto no saldo | Reembolsos WeChat/saldo repõem automaticamente o saldo (refundToBalance / creditRefundToWallet) |

### 11. Mensagens de subscrição (rondas 6+8)

| Funcionalidade | Descrição |
|------|------|
| Cenários de subscrição | 3 cenários de eventos de pedido: pagamento concluído / reembolso recebido / verificação concluída |
| Idempotência | Marcação push_sent_at contra push duplicado |
| Degradação | Sem modelo de subscrição configurado, degrada automaticamente para notificação interna |

### 12. Ciclo fechado de verificação de cartões de vezes (ronda 8)

| Funcionalidade | Descrição |
|------|------|
| Os meus cartões de vezes | GET /api/marketing/cards/my calcula em tempo real used_up/expired |
| Dedução de vezes na verificação | POST /api/marketing/cards/use: idempotência Redis NX + bloqueio de linha lockForUpdate, cria diretamente pedido completed + OrderItem + OrderPayment(pay_type='card') |

### 13. Desconto com cupões (ronda 9)

| Funcionalidade | Descrição |
|------|------|
| Selecionar cupão na encomenda | No pedido é possível enviar user_coupon_id; PriceCalculator.applyCoupon valida em leitura + calcula o valor |
| Tipos de desconto | fixed montante fixo / percent percentagem, com limite min_amount |
| Consumo e devolução | Pagamento concluído consume define used; reembolso restoreCouponAndCard devolve com idempotência |

### 14. Cartão-presente (ronda 9)

| Funcionalidade | Descrição |
|------|------|
| Troca | redeem: o tipo cash recarrega na carteira (bloqueio de linha contra dupla contabilização, WalletTxn type='gift_card'), o tipo gift é apenas marcado |
| Os meus cartões-presente | GET /api/marketing/gift-cards/my |

### 15. Sistema de pontos (rondas 9+10)

| Funcionalidade | Descrição |
|------|------|
| Pontos por registo diário | CheckIn registo diário |
| Pontos por consumo | Na verificação floor(paid×1), idempotência de order_id, snapshot de balance |
| Desconto no reembolso | clawbackOrderPoints desconta proporcionalmente (3 pontos de ligação) |
| Pontos como pagamento | No pagamento envia use_points, 100 pontos = 1 RMB (config app.points_rate), validação agregada SUM do saldo, movimentação de consumo source=points_offset com idempotência |
| Devolução de pontos (ronda 15) | Cancelamento/reembolso devolve os pontos points_offset: refundOffsetPoints com 5 pontos de ligação (doCancel 3 caminhos/doRefund transação WeChat/creditRefundToWallet/completeOneRefundCompensation), source=points_refund com idempotência |
| Extratos de pontos | GET /api/marketing/points paginado + filtro type/source, type unificado como earn |

### 16. Cadeia de encomenda no miniprograma (ronda 10)

| Funcionalidade | Descrição |
|------|------|
| Página de detalhes do serviço | service/detail |
| Página de confirmação de pedido | order/confirm: seleção de cupão/limite a cinzento/valor estimado no cliente → POST /order → pagamento WeChat/saldo |
| Dimensão de páginas | O miniprograma tem atualmente 20 páginas |

### 17. Três entradas no lado do utilizador (ronda 10)

| Funcionalidade | Descrição |
|------|------|
| Favoritos | Página de favoritos favorite (entrada na página user) |
| Promoção | referral: cópia do código de convite/ligação/lista de utilizadores recomendados |
| Feedback | Formulário de feedback |

### 18. Autorização de mensagens de subscrição (ronda 14)

| Funcionalidade | Descrição |
|------|------|
| Autorização de subscrição | utils/subscribe.js centraliza a gestão dos IDs de modelo (chaves alinhadas com appointment_system_config.wechat_app.template_ids no servidor) |
| Cenários de acionamento | wx.requestSubscribeMessage nos callbacks de gestos após agendamento/pagamento concluídos; sem ID de modelo configurado ou recusa do utilizador, silencioso |
| Cadeia no servidor | Envio por WechatTemplateMessageService + lembrete de NotificationReminderService 2h~1h antes do agendamento + processo de varrimento AutoCancelTimer |

### 19. Pós-venda, devolução e troca (ronda 14)

| Funcionalidade | Descrição |
|------|------|
| Solicitar pós-venda | POST /api/aftersales: type=refund/exchange, valida pedido próprio/paid+completed/desduplicação no mesmo pedido |
| Os meus pós-vendas | GET /api/aftersales lista paginada + GET /api/aftersales/{id} detalhes |
| Fluxo de aprovação | No painel de gestão approve/reject (rejected com remark obrigatório); approved apenas muda o estado, o reembolso reutiliza a interface de reembolso do pedido |

### 20. Compras em grupo/promoções relâmpago (ronda 15)

> Desde 2026-08 o canal FLASH_SALE foi descontinuado: PromotionController::index filtra flash_sale, show/join devolvem 400, as promoções relâmpago seguem exclusivamente o canal da secção "43. Promoções relâmpago (ronda 24)"; a constante `Promotion::TYPE_FLASH_SALE` é mantida para compatibilidade com dados históricos. Esta secção e "27. Encomenda de promoção relâmpago" são registos históricos.

| Funcionalidade | Descrição |
|------|------|
| Lista/detalhes de atividades | GET /api/promotions + /api/promotions/{id}, filtro type group_buy/flash_sale |
| Participação | POST /api/promotions/join/{id}: bloqueio Redis NX contra sobre-venda (flash_sale com max_people como limite de stock), participação repetida 422, bloqueio de group_buy quando cheio, encerramento inativo na expiração sem estar cheio (status definido para 0 em show/join) |
| Lista de participantes | GET /api/promotions/{id}/participants |
| Correção de estados | Estado do PromotionParticipant alterado para constantes inteiras 0/1/2/3 (corrige o dano join 1366 em modo estrito) |

### 21. Encomenda de compra em grupo formada (ronda 16)

| Funcionalidade | Descrição |
|------|------|
| Preço de grupo | A resposta de join devolve discount_percent/original_price/group_price |
| Encomenda de grupo | POST /api/order com promotion_id: valida apenas group_buy/atividade válida/chamador é participante/não cheio/serviço corresponde; preço de grupo = preço original × discount_percent/100, cupões/cartões de vezes/pontos desativados (422) |
| Marcação do pedido | Colunas promotion_id/participant_id novas em appointment_order + índices |
| Tratamento de grupo não formado | Sem cheio na expiração → atividade encerrada + cancelamento em lote dos pedidos pending dessa atividade (idempotente); pay() verifica de forma inativa se encerrado e cancela automaticamente o pedido, libertando o bloqueio do técnico |

### 22. Comissões de distribuição (ronda 16)

| Funcionalidade | Descrição |
|------|------|
| Regra de emissão | Após o primeiro pedido completed do recomendado: montante = paid_amount × reward_rate (appointment_system_config referral.reward_rate, padrão 0.05, valor ilegal recai para a constante), só emite se >0 |
| Ponto de ligação | ReferralRewardService::handleOrderCompleted ligado dentro da transação de WorkController::complete (entrada única serving→completed, a verificação verify só vai até serving sem acionar), falha reverte tudo e pode repetir |
| Idempotência | Bloqueio de linha appointment_user_referral lockForUpdate + verificação de vazio de rewarded_at + reavaliação do primeiro pedido dentro do bloqueio (concorrência/chamadas repetidas emitem apenas uma vez) |
| Contabilização | Bloqueio de linha da carteira + WalletTxn type='referral_reward' (balance_after + remark com número do pedido); o registo de recomendação escreve reward_type/reward_amount/rewarded_at/first_order_at |
| Extratos | GET /api/user/referral/earnings paginado (apelido/avatar do recomendado/número do pedido/montante/hora) |

### 23. Loja de troca de pontos (ronda 16)

| Funcionalidade | Descrição |
|------|------|
| Produtos de troca | appointment_points_exchange_goods: type=coupon/gift_card/wallet, points_cost/value (DECIMAL(25,2) contra perda de precisão de IDs avalanche)/stock/status |
| Lista de produtos | GET /api/marketing/points-exchange: produtos em prateleira + stock restante em tempo real + quantidade trocada |
| Troca | POST /api/marketing/points-exchange/{id}: bloqueio Redis NX + bloqueio de linha do produto contra troca excessiva; validação SUM dos pontos (insuficientes 422) + dedução UserPoints type='consume' source='exchange'; coupon emite cupão / wallet credita saldo (WalletTxn points_exchange) / gift_card devolve código |
| Idempotência | Índice único uk_user_goods limita uma vez por utilizador e produto + revalidação dentro do bloqueio + salvaguarda 1062; snapshot do registo de troca em appointment_user_points_exchange |

### 24. Remarcação de agendamento (ronda 17)

| Funcionalidade | Descrição |
|------|------|
| Interface | POST /api/order/reschedule/{id}: new_service_time (obrigatório) + reason (opcional), altera o horário com o mesmo técnico |
| Regras | Apenas pedido próprio (não próprio 404); apenas tipo appointment e estado pending/paid/confirmed (resto 422); ≥ 6 horas até ao início original do serviço (alinhado com a janela de reembolso total) |
| Proteção de concorrência | B1 order_lock (na mesma família de exclusão mútua de pay/cancel/refund) → bloqueio do técnico para o novo período Redis SETNX EX 180 (remarcação concorrente contra sobre-venda) → releitura com bloqueio de linha na transação + validação DB de conflito de agendamento B2 (exclui este pedido) |
| Conclusão | Atualiza service_time + regista appointment_order_reschedule (com reason) + liberta o bloqueio do período original/novo detido por este pedido; falha reverte a transação e liberta também o bloqueio do novo período |
| Notificações | Mensagem de subscrição SCENE_RESCHEDULE (sem modelo configurado degrada para notificação interna "Remarcação de agendamento concluída") + pushOrderUpdate |

### 25. Oferta de cupões (ronda 17)

| Funcionalidade | Descrição |
|------|------|
| Interface | POST /api/marketing/coupons/transfer (user_coupon_id) gera código de oferta único de 8 caracteres sem caracteres ambíguos (uk_code como salvaguarda, válido 7 dias); POST /api/marketing/coupons/claim (code) para receber; GET /api/marketing/coupons/transfers emitidos (pending/claimed/expired) + recebidos (claimed) paginados |
| Validação | O cupão pertence ao próprio/available/definição do cupão não expirada/nunca foi ofertado (422); não pode receber o próprio cupão ofertado por si, recetor não é o titular original |
| Anti-abuso | Bloqueio Redis NX coupon_transfer_claim:{code} (30s) + revalidação com bloqueio de linha na transação contra gasto duplo; índice único uk_user_coupon limita a oferta do mesmo cupão a uma vez; cupão recebido não pode ser reofertado (o novo cupão sem registo de oferta é naturalmente bloqueado); expiração inativa define expired + devolve o cupão original a available |
| Receção | Na transação, o cupão original é definido como used + gera novo UserCoupon vinculado ao recetor (coupon_id inalterado, ou seja, validade inalterada) + regista a oferta como claimed |

### 26. Expiração de pontos (ronda 17)

| Funcionalidade | Descrição |
|------|------|
| Validade | Coluna appointment_user_points.expires_at; todos os earn (registo diário/consumo/devolução) preenchem expires_at = agora + points.expiry_days (padrão 365, ≤0 nunca expira); consume/use ficam vazios |
| Execução de expiração | Processo programado PointsExpiryTimer varrimento de cursor a cada 60s (100/lote) das linhas earn com expires_at < agora → escreve linha de dedução negativa type=expire (source=expiry + order_id para rastrear a movimentação original) → agrega notificação interna por utilizador "Tem X pontos expirados" |
| Idempotência | ① a linha expire tem order_id a apontar para a movimentação earn original, dentro da transação lockForUpdate + revalidação exists na linha original (processos concorrentes serializam no bloqueio de linha) ② paginação por cursor de id ③ notificação apenas nas rondas com dedução efetiva |
| Critério | O saldo disponível agregado SUM inclui as linhas negativas expire; pontos expirados não podem ser usados para pagamento/troca |

### 27. Encomenda de promoção relâmpago (ronda 18, descontinuado)

> Substituído pelo canal `/api/seckill` da ronda 24 (o ramo de promoção de store() tem apenas compras em grupo), ver "43. Promoções relâmpago".

| Funcionalidade | Descrição |
|------|------|
| Interface | POST /api/order com promotion_id (tipo flash_sale): preço relâmpago = round(total × (100 − discount_percent)/100, 2), alinhado com o critério de preço relâmpago do PromotionController |
| Validação | Lista branca de tipos [group_buy, flash_sale] (resto 422); atividade em curso; chamador é participante; serviço do pedido corresponde à atividade; esgotado participants_count ≥ max_people 422 "Esgotado"; cupões/cartões de vezes/pontos desativados 422 |
| Expiração | pay() verificação inativa isFlashSaleClosed (mesmo padrão do isGroupBuyClosed): promoção relâmpago expirada → atividade definida para 0 + cancelamento em lote dos pedidos pending dessa atividade + cancelamento automático deste pedido + libertação do bloqueio do técnico 422 |

### 28. Lembrete de serviço + lembrete de expiração (ronda 18)

| Funcionalidade | Descrição |
|------|------|
| Lembrete antes do início do serviço | ServiceReminderTimer varrimento de 60s de pedidos com service_time ∈ [agora+1h, agora+1h+60s), status confirmed/serving, tipo appointment → notificação interna (type='service_reminder', com serviço/técnico/loja/horário) + mensagem de subscrição SCENE_REMINDER |
| Lembrete de expiração | ExpiryReminderTimer varrimento de 6h de end_at ∈ (agora, agora+3d+6h]: cartões de membro ativos (type='card_expiry') + cupões available (type='coupon_expiry', whereHas a definição do cupão associada a end_at) + mensagem de subscrição SCENE_EXPIRY |
| Idempotência | Ambos com cursor de id 100/lote + revalidação com bloqueio de linha na transação + verificação de duplicação de notificações (a coluna order_id regista o id de origem/do pedido como chave anti-duplicação); push_sent_at só é escrito após envio bem-sucedido da mensagem de subscrição, falha repete na ronda seguinte |
| Degradação | Sem modelo configurado (WECHAT_SUBSCRIBE_TEMPLATE_REMINDER / _EXPIRY) degrada automaticamente para apenas notificação interna |

### 29. Resposta do técnico a avaliações (ronda 18)

| Funcionalidade | Descrição |
|------|------|
| Interface | POST /api/technician/review/reply/{order_id} (middleware de identidade de técnico): avaliação inexistente/não própria unificados 404; resposta já existente 422 (rejeição idempotente sem sobrepor); resposta vazia 422 |
| Após a resposta | Notificação interna ao utilizador (type='review_reply', try/catch + Log não bloqueantes) |
| Dados | Coluna replied_at adicionada com idempotência a appointment_order_review (a coluna reply já existia na criação da tabela); list/show de avaliações no painel de gestão expõem reply/replied_at via decorate()->toArray() |

### 30. Notificação de recarga recebida (ronda 18)

| Funcionalidade | Descrição |
|------|------|
| Interface | Callback de recarga WeChat (número com prefixo R) handleRechargeNotify dentro da transação: após o WalletTxn escreve notificação interna type='wallet_recharge', "Recarregou com sucesso ¥X.XX" (montante em yuan, number_format com 2 casas) |
| Idempotência | Reutiliza a idempotência do callback existente (lockForUpdate na linha da recarga + revalidação de status, apenas o primeiro pending→paid chega à notificação); a notificação e a alteração de estado são submetidas atomicamente na mesma transação, sem intervalo de crash; falha de verificação de assinatura/ordem inexistente/montante divergente não escreve notificação |
| Tolerância a falhas | Escrita da notificação em try/catch, falha apenas regista warning sem bloquear o fluxo principal |

### 31. Transferência de saldo (ronda 19)

| Funcionalidade | Descrição |
|------|------|
| Interface | POST /api/wallet/transfer: desencriptação do hashid do recetor + existência 404, para si próprio 422, montante 0.01-1000/operação 422 (comparação DECIMAL sem float), saldo insuficiente 422, limite diário acumulado de 5000 RMB 422 |
| Concorrência/idempotência | Bloqueio Redis NX wallet_transfer:{from} 30s serializa o remetente; na transação lockForUpdate das linhas de carteira de ambas as partes por ordem crescente de user_id (ordem fixa contra deadlock); client_token SETNX 24h após sucesso contra submissão repetida (pedidos falhados não registam token e podem repetir) |
| Contabilização | Deduz ao remetente + credita ao recetor + WalletTxn dupla movimentação (transfer_out/transfer_in com snapshot balance_after) + registo de transferência completed + notificação interna ao recetor type='balance_received' (falha apenas regista) |
| Registos | GET /api/wallet/transfers (direction=out/in paginado) + GET /transfers/{id} (visível apenas para ambas as partes, senão 404) |

### 32. Transferência de pontos (ronda 19)

| Funcionalidade | Descrição |
|------|------|
| Interface | POST /api/user/points/transfer: recetor inexistente 404, para si próprio 422, pontos 1-10000 422, saldo agregado SUM insuficiente 422, limite diário acumulado de 10000 422 |
| Concorrência/idempotência | Bloqueio Redis NX points_transfer:{user} 30s; na transação lockForUpdate da última movimentação de ambas as partes (user_id por ordem crescente contra deadlock de transferências mútuas) + revalidação de saldo/limite/recetor dentro do bloqueio |
| Norma de movimentações | Remetente type=consume source=points_transfer com valor negativo (balance = snapshot anterior − atual, mesmo critério de points_offset/exchange); recetor type=earn source=points_transfer com valor positivo incluindo expires_at (PointsExpiryTimer pode expirar normalmente); na transação escreve o registo de transferência, após commit notificação interna ao recetor type='points_received' |
| Registos | GET /api/user/points/transfers (direction=sent/received paginado, com apelido da contraparte) |

### 33. Avaliação complementar + completação da rota de submissão (ronda 19)

| Funcionalidade | Descrição |
|------|------|
| Avaliação complementar | POST /api/order/review/{order_id}/append: avaliação inexistente/não própria unificados 404, não completed 422, avaliação complementar repetida 422 (qualquer um de append_content/append_at não vazio rejeita), conteúdo vazio 422; em sucesso escreve append_content/append_images(JSON)/append_at + notificação interna ao técnico type='review_append' |
| Submissão de avaliação | Registo completado de POST /api/order/review/{order_id} (o store do ReviewController não tinha rota acessível); aproveita para corrigir o TypeError latente: findByOrderId recebia int a violar a assinatura string (comparado com a conversão (string) do append), o registo expunha a chamada a 500 |
| Dados | appointment_order_review acrescenta três colunas append_content TEXT/append_images JSON/append_at DATETIME (migração idempotente); a resposta expõe os campos append |

### 34. Rastreio de logística no lado do utilizador (ronda 19)

| Funcionalidade | Descrição |
|------|------|
| Interface | GET /api/order/logistics/{id}: apenas pedidos de produtos próprios podem consultar (não próprio/não produto/não expedido unificados 404) |
| Dados | Lê o JSON de order.remark (shipping_company/tracking_no/shipped_at, escritos pelo admin MallOrderController::ship() na expedição); parseShippingInfo/parseReceiver com dupla análise de recurso ao formato antigo |
| Mascaramento | Telefone do recetor maskPhone (138****5678), contra fugas |

### 35. Preferências de notificações (ronda 19)

| Funcionalidade | Descrição |
|------|------|
| Dados | Tabela appointment_user_notify_setting (chave única composta user_id+type uk_user_type, linha ausente = ativo por padrão); 5 tipos: service_reminder lembrete de serviço / card_expiry lembrete de expiração (guarda-chuva unificado de cartão+cupão) / points_expiry expiração de pontos / marketing marketing (reservado) / system sistema (não pode ser desligado, PUT força para 1) |
| Interface | GET /api/user/notify-settings devolve os 5 interruptores completos; PUT upsert em lote sem linhas duplicadas |
| Controlo | NotificationReminderService::notifySettingEnabled ligado a 3 processos de temporizadores (ServiceReminderTimer/ExpiryReminderTimer de cartão+cupão/PointsExpiryTimer; os temporizadores inserem diretamente na tabela appointment_notification sem passar pelo serviço de escrita, por isso cada um tem o mesmo controlo) + eventos de subscrição (mapeamento de cenários sendSubscribeForOrderEvent/Notification PAY/REFUND/VERIFIED/RESCHEDULE→system sempre enviado, REMINDER→service_reminder, EXPIRY→card_expiry); quando o tipo está desligado, notificações internas e mensagens de subscrição são ambas ignoradas |

---

## II. Painel de administração (PC Web)

Aplicação de página única Flutter Web, 21 páginas no total: dashboard/utilizadores/papéis/configuração/registos/verificação/agendamentos/serviços/técnicos/pedidos/cupões/membros/cartões de vezes/anúncios/FAQ/levantamentos/avaliações/relatórios/conta pessoal/bancada de trabalho da loja.

### 1. Dashboard da página inicial

- Estatísticas em tempo real: número de utilizadores/total de pedidos/número de técnicos/número de pedidos de serviço
- Gráfico de linhas: tendência de volume de pedidos/tendência de montantes/novos utilizadores/atividade
- Navegação rápida: botões de módulos pendentes
- Mensagens internas: notificações de novos pedidos/notificações de reembolso

### 2. Gestão de técnicos

- Lista de técnicos: pesquisa por UID/telefone/nome/região de origem/data de registo
- Exibição da lista: número/UID/telefone/apelido/recomendador/estado/número de alunos/desempenho/estado da conta/data de registo/último login/região de origem
- Operações: exportar/alterar superior/ver subordinados/alterar palavra-passe e telefone/gestão de agendamentos/definição de itens de serviço técnico/visualização do progresso de cursos
- Novo: nome/sexo/telefone/cartão de cidadão/foto do cartão de cidadão
- Aprovação de candidaturas de adesão

### 3. Gestão de utilizadores

- Lista de membros: nome/telefone/avatar/nível/montante de consumo
- Pesquisa: UID/telefone/apelido/data de registo
- Operações: detalhes/alterar superior/ver subordinados/alterar palavra-passe e telefone/definir nível de membro

### 4. Gestão de lojas

- Lista de lojas: ativar/desativar/eliminar
- Nova loja: nome/morada/coordenadas/telefone/horário de funcionamento/imagens

### 5. Gestão de serviços

- Lista de serviços: pesquisa por nome/categoria; número/nome/tipo/desconto/preço mínimo/volume de vendas/capa/ordem/estado/hora
- Operações: novo/alterar/eliminar/design de cartões
- Lista de produtos: tipo/nome/desconto/preço mínimo/volume de vendas/stock/capa/ordem/estado/hora

### 6. Gestão da loja online

- Pedidos da loja: detalhes/expedição/logística/impressão
- Pedidos de pós-venda: ver/auditar/imprimir
- Gestão de avaliações: ver/auditar (show/hide)/eliminar (ReviewController index/show/audit/destroy)
- Movimentos de pagamento
- Estatísticas de vendas

### 7. Gestão de pedidos

- Pedidos por usar: pesquisa multi-condições
- Operações: detalhes/cancelamento pela plataforma/confirmar conclusão

### 8. Atividades de cupões

- Lista: ordem/imagem/tipo/nome/colocação na prateleira/total/restantes/administrador/hora/data de fim
- Operações: novo/alterar/eliminar

### 9. Gestão financeira

- Divisão de lucros de pedidos: pesquisa/detalhes
- Levantamentos de técnicos: aprovação no WithdrawalController; montante ≥500 com aprovação em dois níveis (gerente de loja store_approved_at → financeiro finance_approved_at); máquina de estados pending→approved→completed (rejected/failed)
- Definição de comissões: alterar taxa de comissão/período de liquidação/prémios e penalizações/saldo
- Movimentos de receitas e despesas
- Gestão de contas de levantamento
- Configuração de limites de levantamento

### 10. Gestão de conteúdo

- CRUD de carrosséis
- Definição de "Sobre nós"
- Auditoria de dinâmicas do círculo de amigos
- CRUD de FAQ
- Tratamento de feedback de opiniões
- CRUD de anúncios da plataforma

### 11. Definições

- Edição dos acordos da plataforma (acordo do utilizador/acordo de privacidade/acordo de serviço)
- Definição unificada de comissão dos técnicos
- Modelos de mensagens do sistema (inclui configuração de modelos de mensagens de subscrição do miniprograma, sem configuração degrada automaticamente para notificação interna)
- Gestão de permissões de subcontas (gerente de loja pode emitir cupões + agendar)

### 12. Funcionalidades extensíveis

- Design de cartões: combinação projeto+produto/custos manuais/definição de comissões
- Monitorização do sistema: painel em tempo real de CPU/memória/disco/Redis/MySQL/filas
- Lista negra de IPs: visualização de ataques do security-php + bloqueio manual
- Backup da base de dados: backup/descarga/restauro pela interface Web
- Perfil do cliente: vista 360/preferências de consumo/marketing segmentado
- Envio em lote: mensagens de modelo/boletins segmentados
- Fluxo de aprovação de reembolsos: aprovação em dois níveis (gerente de loja → financeiro)
- Níveis de técnico: avaliação automática junior/senior/expert
- Tarefas programadas: cancelamento automático/liquidação/tratamento de expirações
- Configuração de SMS: gestão de múltiplos canais Alibaba Cloud/Tencent Cloud
- Configuração de armazenamento: local/OSS/COS/CDN
- Relatórios avançados: campos personalizados/relatórios por e-mail agendados
- Exportação de agendamentos: exportação Excel de registos de agendamentos/listas de assiduidade
- Restrição de sexo do técnico: controlo de sexo em projetos específicos
- Formação de técnicos: gestão de cursos/rastreio do progresso de aprendizagem
- Conta de gerente de loja: isolamento de dados store_id + permissões exclusivas

### 13. Relatórios de dados (ronda 7)

- ReportController com 3 endpoints: estatísticas de pedidos / desempenho de técnicos / distribuição por lojas
- Cache Redis svc:admin_report:{type}:{start}:{end}, TTL 300

### 14. Gestão de cartões de membro (ronda 10)

- Coluna de nível de membro appointment_user.member_level (migração 000008)
- MemberCardController CRUD completo (permissões 365-369): GET/POST/PUT/DELETE /admin/member-cards
- Página Flutter de definição de cartões de membro

### 15. Gestão de pós-venda (ronda 14)

- Tabela appointment_order_aftersale (migração 000009): type=refund/exchange, status=pending/approved/rejected/completed
- AftersaleController: GET /admin/aftersales (paginação + filtro status/uid/order_no) + POST /admin/aftersales/{id}/review (approve/reject+remark)
- Página Flutter de gestão de pós-venda (lista + caixa de diálogo de aprovação, permissões 370/371), layout registado

### 16. Bancada de trabalho do gerente de loja (ronda 15)

- service /api/store-manager: overview (pedidos de hoje/rendimentos/em curso/número de técnicos/número de verificações) + orders (paginação + filtro de estado) + technicians (com agendamentos de hoje) + revenue (agregação dos últimos 7 dias), requireStoreId() força o isolamento store_id (403 sem loja)
- admin StoreController::workbenchOverview (GET /admin/stores/workbench-overview?store_id=, mesmo critério do service) + filtro store_id na lista de pedidos do AppointmentOrderController (desencriptação hashid)
- Página Flutter da bancada de trabalho da loja: lista suspensa de lojas + filtro de estado + 5 cartões de visão geral + DataTable de pedidos + paginação (permissão 372)

### 17. Produtos de troca de pontos (ronda 16)

- PointsExchangeGoodsController: GET/POST/PUT/DELETE /admin/points-exchange-goods + POST {id}/toggle-status (colocar/retirar da prateleira) + GET {id}/exchanges (registos de troca, com telefone + análise do JSON de result)
- Migrações 000012 (duas tabelas) + 000013 (permissões 373-378) aplicadas

### 18. Registos de comissões (ronda 16)

- ReferralRewardController: GET /admin/referral-rewards (apenas registos com rewarded_at não vazio, paginação + filtro keyword de apelido ou telefone do recomendador/recomendado, codificação hashid, permissão 379)

### 19. Avaliação automática de nível do técnico (ronda 17)

- TierRatingService::evaluate(technicianId, allowDowngrade=false): estatísticas em tempo real do número de pedidos completed de appointment_order + média de appointment_order_review (arredondada a 1 casa decimal) escritas de volta em profile.order_count/rating, correspondência de alto para baixo conforme appointment_technician_tier_config (min_orders/min_rating), sem correspondência recai para o nível mais baixo
- Regras de promoção/despromoção: apenas promoção, sem despromoção (o nível está ligado à taxa de comissão e ao coeficiente de preço; despromover automaticamente afeta o rendimento do técnico e pode gerar litígios; a descida é tratada manualmente pelo admin como recurso); allowDowngrade=true (cenário de reavaliação manual no painel) é que executa a despromoção, que também regista log + notificação
- Idempotência: quando o nível devido é igual a profile.tier_id, apenas sincroniza estatísticas, sem escrever log nem enviar notificação
- Registos: alterações escritas em appointment_technician_tier_log (id/technician_id/old_tier_id/new_tier_id/reason/created_at) + notificação interna (type='tier')
- Pontos de acionamento: WorkController::complete / escrita de avaliações no ReviewController / verificação inativa no ProfileController ao consultar o perfil
- Painel de gestão: TechnicianTierController mantém a capacidade de configuração manual; GET /admin/technician-tiers/logs paginado para ver os registos de alterações (join do nome do técnico e dos nomes dos níveis antigo/novo, IDs codificados com hashid, permissão 380)

### 20. Visualização de respostas a avaliações (ronda 18)

- ReviewController adiciona reply(): GET /admin/reviews/{id}/reply detalhes da resposta (decodeId → find → 404 → saída decorate, sem resposta reply='', reply/replied_at expostos via toArray)
- Rota estática (definida antes de audit, antes do resource); seed de permissão id 381 (slug 'get.admin/reviews/{id}/reply', type 3, associação idempotente ao papel de superadministrador)
- Ponto de permissão: 381

### 21. Calendário de agendamentos (ronda 20)

- CalendarController vista mensal/diária: GET /api/calendar/technician/{id} (vista mensal) + /day (vista diária)
- Fonte de dados: time_slots JSON de technician_schedule expandidos em faixas horárias por dia da semana, períodos já agendados de appointment_order nesse dia excluídos (status ∈ pending/paid/confirmed/serving), saída das faixas disponíveis restantes
- Utilização: seleção visual de horários por agendamento da loja, o frontend faz rolagem horizontal por dia + seleção de pontos de horário

### 22. Nível de crescimento do utilizador (ronda 20)

- appointment_user_growth (movimentações) + appointment_growth_level (seed de escalões, 5 níveis: Bronze 0/Prata 100/Ouro 500/Platina 2000/Diamante 5000)
- Pontos de contabilização de crescimento: registo diário +10 (CheckInController); submissão de avaliação +20 (ReviewController::store, avaliação complementar não contabiliza); consumo floor(paid) 1 ponto por cada 1 RMB (WechatPayService::markOrderPaid, reutiliza a revalidação de estado de pagamento existente com idempotência natural, callbacks repetidos não contabilizam de novo)
- Interface: GET /api/growth (visão geral do nível atual: balance/level/diferença para o próximo escalão); GET /api/growth/records (movimentações paginadas); GET /api/growth/levels (lista pública de escalões, sem login)
- Estratégia de falha: qualquer ponto de contabilização em try/catch regista log, sem afetar o fluxo principal

### 23. Fatura eletrónica (ronda 20)

- appointment_invoice: uk_order_type(order_id,order_type) contra pedidos duplicados de fatura no mesmo pedido (pedido duplicado 422, inclui captura de recurso ao MySQL 1062); idx_user_created/idx_status
- Lado do utilizador: POST /api/invoices (pedido, montante/título trazidos pelo servidor do pedido, não alteráveis); GET /api/invoices (lista); GET /api/invoices/{id} (detalhes)
- Painel de gestão: InvoiceController issue (emissão: escreve invoice_no + status=issued + issued_at) / reject (rejeição: status=rejected + reject_reason), permissões 382 lista/383 emissão/384 rejeição
- Máquina de estados: pending → issued / rejected

### 24. Tickets de apoio ao cliente (ronda 20)

- appointment_ticket: o utilizador submete o ticket (title/content), o painel responde por acréscimo (reply_content/replied_at), o utilizador pode encerrar (closed_at)
- Lado do utilizador: POST /api/tickets (submissão); GET /api/tickets (lista); GET /api/tickets/{id} (detalhes, apenas próprio); POST /api/tickets/{id}/close (encerramento)
- Painel de gestão: TicketController index (lista) / reply (resposta), rotas estáticas definidas antes do resource para evitar shadowing de {id}; permissões 385 resposta de tickets/387 visualização da lista de tickets
- Máquina de estados: open → replied (após resposta volta a open, pode responder de novo) / closed

### 25. Distribuição multinível — comissão de segundo nível (ronda 20)

- ReferralRewardService::payLevel2Reward(paidAmount, orderId): após pagamento do pedido, consulta o recomendador do recomendador de primeiro nível (relação de recomendação de segundo nível) e emite paid×level2_rate (configuração do sistema referral.level2_rate, padrão 0.02)
- Idempotência: bloqueio de linha na transação + chave única uk_order_referred(order_id, level2_user_id), callbacks de pagamento repetidos/concorrência não emitem em duplicado; try/catch de falha apenas regista sem afetar o fluxo principal de pagamento
- Contabilização: WalletTxn type='referral_level2' (constante TYPE_REFERRAL_LEVEL2) + acumulação do saldo da carteira
- Painel de gestão: ReferralLevel2Controller index com registos paginados (permissão 386), join dos apelidos dos dois níveis de utilizadores

### 26. Direitos do nível de crescimento concretizados (ronda 21)

- Benefits JSON do GrowthLevel concretizados: migração seed de 5 escalões (Bronze {"discount_rate":1.0,"points_multiplier":1.0}, Prata 0.98/1.1, Ouro 0.95/1.2, Platina 0.92/1.3, Diamante 0.9/1.5)
- Desconto por nível: OrderController::store applyGrowthDiscount() — apenas pedidos padrão (promotion_id vazio, compras em grupo/promoções relâmpago sem sobreposição); ordem: montante a pagar após cupão/cartão de vezes × discount_rate; valor do desconto incorporado em discount_amount, nota do pedido acrescenta "Desconto de nível: Prata 9.8 descontos, desconto de ¥2.00" para rastreabilidade; proteção de preço mínimo: valor efetivo após desconto ≥0.01 RMB (em cêntimos ≥100), caso contrário o desconto é cortado para 0
- Multiplicador de pontos: WechatPayService::markOrderPaid altera o crescimento de floor(paid) para floor(paid × points_multiplier), multiplicador fixado pelo nível no momento do pagamento (acumulado antes da contabilização, este pedido não sobe de nível); o ponto de ligação try/catch do R20 é totalmente mantido
- Reutilização de consultas: GrowthLevel::levelForGrowth() fixa o escalão pelo crescimento acumulado, reutilizado no pedido/pagamento; GET /api/growth já devolve benefits e next_gap (implementação do R20, sem alterações)

### 27. Gestão de dados de fatura (ronda 21)

- appointment_invoice_title (uk_user_title(user_id, title_type, invoice_title) contra duplicados + idx_user_default)
- Interface: POST /api/invoice-titles (guardar, company requer tax_no, duplicado 422); GET (lista, predefinido no topo); PUT /{id} (edição, apenas próprio); DELETE /{id} (eliminação, apenas próprio); POST /{id}/default (definir predefinido, zera os outros do mesmo utilizador em transação)
- Regra do predefinido: o primeiro guardado é automaticamente predefinido; ao eliminar o predefinido, o mais antigo é automaticamente designado
- Ligação ao pedido: InvoiceController::store aceita title_id opcional, resolve o título e traz invoice_title/tax_no/title_type; sem title_id mantém o caminho de preenchimento manual original; a lógica anti-duplicação uk_order_type inalterada

### 28. Satisfação de tickets (ronda 21)

- appointment_ticket acrescenta rating TINYINT NULL + rated_at DATETIME NULL (migração 000303)
- Pontuação no encerramento: TicketController::close() suporta rating opcional 1-5 (validação de inteiro filter_var, fora do intervalo/não inteiro 422; se fornecido escreve rating+rated_at, se não fornecido mantém NULL compatível com clientes antigos; regra de encerrar apenas open mantida)
- Estatísticas no painel: GET /admin/tickets/satisfaction (rota estática antes do resource contra shadowing de {id}) devolve total/rated_count/unrated_count/average (1 casa decimal)/distribution (quantidades de 1-5 estrelas, estrelas em falta preenchidas com 0); permissão 388

### 29. Auditoria de imagens de avaliações (ronda 21)

- admin ReviewAuditController (novo, sem tocar no ReviewController existente): GET /admin/review-audit lista de avaliações com imagens (filtro JSON_LENGTH(images)>0 + leftJoin do apelido do utilizador e nome do técnico + filtro de status + codificação hashid); POST /{id}/hide ocultar; POST /{id}/restore restaurar
- Máquina de estados: hide apenas visible pode ocultar, restore apenas hidden pode restaurar (validação bidirecional 422); estado do OrderReview em sistema inteiro (STATUS_HIDDEN=0/STATUS_VISIBLE=1)
- Cadeia de efeito: a lista de avaliações do técnico no lado do utilizador já filtra por status → após ocultação fica automaticamente invisível
- Permissões: 389 lista / 390 ocultar / 391 restaurar

### 30. Histórico de navegação do utilizador (ronda 21)

- appointment_browse_history (uk_user_item(user_id, item_id) único, navegação repetida apenas atualiza viewed_at sem inserção duplicada; idx_user_viewed para ordenação)
- Ligação de registo: ServiceController::detail() regista após sucesso (try/catch + Log::warning sem afetar o fluxo principal; rota pública sem JWT, user_id vazio ignora anónimos)
- Interface: GET /api/browse-history (join do nome/capa/preço/preço original de appointment_service, ordenação decrescente de viewed_at, per_page padrão 15 máximo 50, item_id com hashid); DELETE /{item_id} (apenas próprio, ilegal/de outro 404); DELETE / (limpar apenas próprio)

### 31. Marketing de desconto por valor mínimo (ronda 22)

- appointment_full_reduction_activity (threshold/reduction/title/status/start_at/end_at + idx_status_status_time)
- Sobreposição no pedido: apenas pedidos padrão (compras em grupo/promoções relâmpago ignoradas), limite avaliado sobre o montante a pagar após cupão/cartão de vezes, ordem **cupão/cartão de vezes → desconto por valor mínimo → desconto de nível**; usa a atividade com maior redução; valor do desconto incorporado em discount_amount + nota "Desconto por valor mínimo: compre X e poupe Y"; após o desconto, pagamento efetivo mínimo de 0.01 RMB (em cêntimos)
- Lado do utilizador GET /api/full-reduction-activities (público, ativas por ordem decrescente de redução)
- admin FullReductionController: CRUD + toggle-status para colocar/retirar da prateleira (destroy com confirmPassword)
- Permissões: 396 lista / 397 novo / 398 edição / 399 prateleira / 400 eliminação (um registo de permissão corresponde apenas a um slug method.path, 5 rotas divididas em 5 registos)

### 32. Exportação ICS dos meus agendamentos (ronda 22)

- IcsController GET /api/order/ics: pedidos pending/paid/confirmed/serving dos últimos 90 dias exportados em iCal (RFC5545), apenas próprio
- VEVENT: UID=ID do pedido, DTSTAMP(UTC), TZID=Asia/Shanghai, duração padrão 1h, resumo "Agendamento: nome do serviço" (ausente degrada para "Agendamento"), descrição técnico/loja/morada (ausentes omitidos), LOCATION; escape de texto (\, \; \\ \n) + quebra de linhas de 75 bytes
- Sem pedidos devolve calendário vazio válido (esqueleto `BEGIN:VCALENDAR`)

### 33. Assiduidade do técnico (ronda 22)

- appointment_technician_attendance (date/check_in_at/check_out_at/status + índice único uk_technician_date contra registo duplicado em concorrência)
- Lado do técnico (TechnicianAuth): check-in repetido no dia 422; check-out sem entrada/já com saída 422 + bloqueio de linha; >10:00 marca atraso; GET lista do mês + dias de presença/total de horas/média de horas (?month=YYYY-MM inválido 422)
- admin: GET /admin/attendance (filtro de data+nome do técnico, join real_name, hashid) + /stats (estatísticas agrupadas por técnico)
- Permissões: 392 lista / 393 estatísticas

### 34. Serviço de push do APP (ronda 22)

- AppPushService (config group=push: enabled padrão 0 / provider jpush/getui/placeholder): não ativado degrada em silêncio apenas com log; ativado constrói a estrutura plataforma/título/conteúdo/payload, regista Log + escreve appointment_push_log (status=sent); integração com SDKs dos fornecedores fica em TODO (sem credenciais não envia de facto)
- 5 eventos ligados: pagamento concluído (WechatPayService::markOrderPaid), reembolso automático (autoRefundCancelledOrder), reembolso manual (doRefund/refundToBalance), compensação de reembolso (completeOneRefundCompensation), lembrete de início de serviço (ServiceReminderTimer); todos com try/catch sem bloquear o fluxo principal
- appointment_push_log (user_id/title/content/payload JSON/status/provider + idx_user)

### 35. Divisão oficial de lucros do WeChat (ronda 22)

- WechatProfitSharingService (config group=profit_sharing: enabled/receiver_ratio, credenciais reutilizam wechat_pay): não ativado degrada como disabled apenas com log sem registar; ativado → validação de montante (>0 e ≤paid, padrão efetivo×0.7) + idempotência (mesmo pedido pending/success ignorado) → regista pendente → constrói a estrutura "pedido de divisão de lucros única" (sem credenciais não executa HTTP, conteúdo do pedido registado em log, registo mantém pending); doRequest privado isolado por HTTP para testabilidade
- WechatPayService::markOrderPaid liga requestSharing após a submissão (try/catch de falha apenas log)
- appointment_profit_sharing (uk_sharing_no único + idx_order); admin GET /admin/profit-sharing lista (join do número do pedido/apelido do técnico, filtros por estado/número do pedido/nome do técnico)
- Permissão: 394

### 36. Privacidade e conformidade (ronda 22)

- GET /api/privacy/data: exportação de dados (agrupados personal/orders/points/wallet_txns/reviews/addresses/invoices; os registos apenas guardam telefone mascarado + contagens)
- Ciclo fechado de cancelamento: close-request (saldo ≠ 0/pedidos por concluir/tickets em curso 422 → close_status=1) → close-cancel (1→0) → close-confirm (após 72h → close_status=2 + close_at + phone/nickname anonimizados user{id} + status=0)
- appointment_user acrescenta close_status/close_requested_at/close_at (migração ALTER idempotente); AuthController login/loginByCode devolve 403 "Conta cancelada" para close_status=2

### 37. Ficha de saúde do utilizador (ronda 23)

- GET/PUT/DELETE /api/health-profile: uma por pessoa (índice único uk_user), upsert apenas atualiza os campos fornecidos
- allergies/health_notes com limite de 500 caracteres, preferred_technician_id valida a existência, resposta codificada com hashid
- Migração 000504_user_health_profile; HealthProfileTest com 6 testes

### 38. Palavra-passe de pagamento da carteira (ronda 23)

- POST /api/wallet/pay-password/{set,verify,check}: validação de 6 dígitos, armazenamento de password_hash + pay_password_set_at
- Já definida, a alteração requer a palavra-passe antiga 422; verify apenas valida sem registar; check devolve se está definida
- Migração 000502 (ALTER idempotente de duas colunas via INFORMATION_SCHEMA); WalletPayPasswordTest com 7 testes

### 39. Agendamento em lote do técnico (ronda 23)

- POST /api/technician/schedule/batch: período de datas ≤7 dias + filtro weekdays, dias com agendamento existente são ignorados
- A definição individual também ativa a deteção de sobreposição de períodos (422 "Conflito de horário com agendamento existente: HH:MM-HH:MM")
- ScheduleConflictTest com 5 testes

### 40. Linha do tempo de estado do pedido (ronda 23)

- GET /api/order/{id}/timeline: apenas próprio pode consultar (outro 404), devolução por ordem decrescente; os detalhes do pedido no admin incorporam o array timeline
- OrderStatusLog::record() com pontos de registo estáticos em 8 tipos de alterações: submissão/pagamento/cancelamento/confirmação/pedido de reembolso/aprovação de reembolso/início do serviço/conclusão do serviço/cancelamento automático por timeout/operação do painel (operator=admin)
- O callback de pagamento markOrderPaid é o ponto único de consumo; record() com try/catch interno + Log::warning que nunca bloqueia o fluxo principal
- Migração 000501_order_status_log; OrderTimelineTest com 4 testes

### 41. Roleta da sorte de pontos (ronda 23)

- GET /api/wheel/prizes (weight/stock ocultos); POST /api/wheel/spin: Redis NX + bloqueio de linha contra concorrência, extração ponderada random_int, idempotência client_token
- Liquidação de prémios: pontos → movimentação earn (com data de expiração, pode ser expirada normalmente pelo PointsExpiryTimer), saldo → lockForUpdate, cupão → pending para emissão manual, sem prémio → lose
- GET /api/wheel/records os meus registos paginados; admin /admin/lucky-wheel CRUD + prateleira + registos (permissões 401-406)
- Migrações 000503 (appointment_lucky_wheel + appointment_wheel_record + seeds de demonstração w60/w40) + 000505 (seeds de permissões); LuckyWheelTest admin 3 + service 6 testes

### 42. Modo convidado (ronda 24)

- GET /api/guest/{home,services,services/{id},stores,technicians}: entradas de navegação sem login sem autenticação (apenas middleware ApiVersion)
- home agrega carrosséis/anúncios/categorias de serviços/serviços populares, cache Redis svc:guest:home 300s; services suporta filtro de categoria + ordenação newest/sales/price (page/per_page≤50); technicians apenas aprovados, filtro opcional por service_id, classificação decrescente
- Coberto por GuestControllerTest

### 43. Promoções relâmpago (ronda 24)

- appointment_seckill_activity (name/service_id/seckill_price/original_price/stock/start_at/end_at/status); quantidade vendida = número de pedidos com appointment_order.seckill_id
- GET /api/seckill (status=1 + janela temporal), /{id} (state=not_started/ongoing/ended), POST /{id}/buy: idempotência client_token (8-64 caracteres, SETNX 24h) + Redis NX 30s contra concorrência + validação da atividade (desde 2026-08-26 sem pré-dedução de stock)
- O pedido injeta seckill_id reutilizando OrderController::store; o stock é deduzido uniformemente com bloqueio de linha dentro da transação do store() (chamar diretamente /api/order com seckill_id também deduz o stock), preço relâmpago = seckill_price (tomado do DB como referência), sem sobreposição de cupões/pontos/cartões de membro; cancelamento do pedido não repõe stock; o antigo canal de promoção FLASH_SALE foi removido (o ramo de promoção de store() tem apenas compras em grupo, PromotionController index filtra flash_sale, show/join 400), as promoções relâmpago seguem apenas este canal
- admin /admin/seckill CRUD + prateleira + lista de pedidos (permissões 407-411, 420); migração 000606 de seeds de permissões; SeckillTest service + admin

### 44. Gestão de versões do APP e verificação de atualizações (ronda 24)

- appointment_app_version (platform/version_code/version_name/force_update/changelog/download_url/status)
- GET /api/app/version?platform=android|ios verificação pública de atualizações (platform inválido 422; toma o mais recente de status=1; sem nenhum devolve objeto vazio)
- admin /admin/versions CRUD (permissões 416-419); migração 000609 de seeds de permissões; VersionTest service + admin

### 45. Recompensa por cliente recorrente (ronda 24)

- ReturnCustomerRewardService: pela 2.ª compra do utilizador ao mesmo técnico em 30 dias (pedido concluído), atribui ao técnico um bónus = valor efetivo paid_amount × ratio (system_config group=return_customer, ratio padrão 0.05, interruptor enabled, valores ilegais recaem no padrão)
- Regista em appointment_technician_earnings (type=return_customer, status=pending) reutilizando a cadeia de liquidação de comissões, o resumo de earnings do lado do técnico inclui automaticamente; idempotência por order_id+type; chamado dentro da transação com bloqueio de linha de WorkController::complete
- admin /admin/return-customer/config (GET/PUT) + /rewards (?keyword nome do técnico/número do pedido/apelido do utilizador) (permissões 412-414); migração 000607 de seeds de permissões; ReturnCustomerRewardServiceTest

### 46. Exportação de agendamentos (ronda 24)

- GET /admin/technician-schedule/export: CSV (UTF-8 BOM, abre diretamente no Excel), nome do ficheiro schedules_{YmdHis}.csv
- start_date/end_date obrigatórios (YYYY-MM-DD, inválido 422) e intervalo ≤31 dias; technician_id opcional (hashid, inválido 422)
- Colunas: ID do técnico/nome do técnico/data/detalhes dos períodos (time_slots JSON analisado como "09:00-12:00, 14:00-18:00")
- Permissão: 415; migração 000608 de seeds de permissões; coberto por ScheduleExportTest
