> Tradução em português · Original: [中文](../API.md)

# Documentação da API
> **Languages**: [中文](../API.md) · [English](../en/API.md) · [한국어](../ko/API.md) · [Русский](../ru/API.md) · [Deutsch](../de/API.md) · [Français](../fr/API.md) · [Español](../es/API.md) · [हिन्दी](../hi/API.md) · [العربية](../ar/API.md) · [বাংলা](../bn/API.md) · [Bahasa Indonesia](../id/API.md) · [日本語](../ja/API.md)

## Visão geral

- **API de negócio** (service/): `http://localhost:8787` — fornece as interfaces de negócio ao miniprograma/APP
- **API do painel de administração** (admin/): `http://localhost:8787` — fornece as interfaces ao Flutter Web do painel
- **Método de autenticação**: Bearer Token (JWT), cabeçalho `Authorization: Bearer <token>`
- **Controlo de versões**: o cabeçalho `API-Version: v1` controla a versão da API, não aparece no URL. Padrão v1
- **Codificação de IDs**: todos os campos de ID em pedidos/respostas são codificados com hashids, ocultando os IDs reais da base de dados
- **Documentação OpenAPI**: gerada com `hg/apidoc`, separada entre painel de gestão e cliente

| Terminal | Endereço da documentação OpenAPI | Descrição |
|------|------|------|
| Painel de gestão | `GET http://localhost:8787/api/docs` | Especificação completa da API do painel (JSON OpenAPI 3.0) |
| Cliente | `GET http://localhost:8787/api/docs` | Especificação completa da API de negócio (JSON OpenAPI 3.0) |

É possível importar os endereços acima em ferramentas como o Swagger UI para ver documentação interativa.

- **Formato de resposta geral**:

```json
{
  "code": 0,
  "message": "操作成功",
  "data": {}
}
```

Resposta paginada:
```json
{
  "code": 0,
  "message": "success",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

---

## I. API de negócio (service/ :8787)

### 1. Interfaces públicas (sem autenticação)

#### 1.1 Código de verificação

**`POST /api/captcha/send`** — envia código de verificação por SMS

Pedido:
```json
{
  "phone": "13800138000"
}
```
Resposta: `{"code":0,"message":"验证码已发送","data":null}`

Limite: apenas 1 envio a cada 60 segundos, código de verificação válido por 5 minutos.

---

#### 1.2 Autenticação

**`POST /api/auth/register`** — registo com número de telefone

Pedido:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "abc123",
  "confirm_password": "abc123",
  "referral_code": "A1B2C3D4"
}
```
Resposta:
```json
{
  "code": 0,
  "message": "注册成功",
  "data": {
    "token": "eyJhbGciOi...",
    "user": {
      "id": "aB3xK9mQ",
      "phone": "138****8000",
      "nickname": "用户138****8000",
      "user_type": "customer",
      "active_role": "customer",
      "referral_code": "E5F6G7H8"
    }
  }
}
```

---

**`POST /api/auth/login`** — login com palavra-passe

Pedido:
```json
{
  "phone": "13800138000",
  "password": "abc123"
}
```
Resposta: igual ao registo, inclui token e informações do utilizador.

---

**`POST /api/auth/login-by-code`** — login com código de verificação

Pedido:
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```
Resposta: igual ao login. Utilizadores não registados criam conta automaticamente.

---

**`POST /api/auth/forget-password`** — palavra-passe esquecida

Pedido:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "newpass123",
  "confirm_password": "newpass123"
}
```

---

**`POST /api/auth/refresh`** — renovar Token

Cabeçalho: `Authorization: Bearer <token antigo>`
Resposta: `{"code":0,"data":{"token":"eyJhbGciOi..."}}`

---

#### 1.3 WeChat

**`POST /api/wechat/mini-login`** — login no miniprograma

Pedido: `{"code":"code do login WeChat"}`
Nota: no primeiro login é necessário chamar `/api/wechat/phone` para vincular o número de telefone.

---

**`POST /api/wechat/phone`** — vincular número de telefone

Pedido: `{"code":"code do componente de telefone WeChat"}`

---

**`POST /api/wechat/oa-login`** — login na conta oficial

Pedido: `{"code":"code de autorização da conta oficial"}`

---

#### 1.4 Serviços comuns

**`GET /api/common/config`** — configuração comum

Resposta: inclui textos de acordos (acordo do utilizador/acordo de privacidade/acordo de serviço), informações "Sobre nós", número de versão.

---

**`GET /api/common/area`** — lista de cidades/regiões

---

#### 1.5 Consulta de serviços

**`GET /api/service/categories`** — lista de categorias

Parâmetros: `?parent_id=0`

---

**`GET /api/service/items`** — lista de projetos de serviço

Parâmetros: `?category_id=&page=1&per_page=10&sort=sales`

---

**`GET /api/service/detail/{id}`** — detalhes do serviço

A resposta inclui: imagens/nome/preço/especificações/duração/volume de vendas/lista de avaliações.

---

**`GET /api/service/products`** — lista de produtos

**`GET /api/service/stores`** — lista de lojas

Parâmetros: `?lat=&lng=&city=`

---

#### 1.6 Consulta de técnicos

**`GET /api/technician/list`** — lista de técnicos

Parâmetros: `?lat=&lng=&service_id=&page=1`
Ordenação por distância do mais próximo ao mais distante, devolve: avatar/nome/classificação/número de pedidos/número de favoritos/distância/horário disponível mais próximo/disponibilidade de serviço.

---

**`GET /api/technician/detail/{id}`** — detalhes do técnico

A resposta inclui: imagens/nome/apresentação/classificação/distância/lista de projetos de serviço disponíveis/avaliações.

---

**`GET /api/technician/schedule/{id}`** — agendamento do técnico

Parâmetros: `?date=2026-05-26`
Devolve os períodos de agendamento disponíveis nessa data e o estado de disponibilidade.

---

#### 1.7 Conteúdo

**`GET /api/content/banners`** — carrossel

Parâmetros: `?position=home`

**`GET /api/content/articles`** — lista de anúncios/artigos

Parâmetros: `?type=announcement&page=1`

**`GET /api/content/article/{id}`** — detalhes do artigo

---

#### 1.8 LBS

**`GET /api/lbs/nearby-stores`** — lojas próximas

Parâmetros: `?lat=&lng=&radius=5000`

**`GET /api/lbs/geocode`** — geocódigo inverso

Parâmetros: `?lat=&lng=`

---

### 2. Interfaces do utilizador (requerem autenticação JWT)

Todas as interfaces usam o cabeçalho `Authorization: Bearer <token>`

#### 2.1 Perfil pessoal

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/user/profile` | Obter informações pessoais |
| PUT | `/api/user/profile` | Atualizar apelido/avatar/sexo |
| POST | `/api/user/change-password` | Alterar palavra-passe (old_password/new_password/confirm_password) |
| POST | `/api/user/change-phone` | Alterar telefone vinculado (old_code/new_phone/new_code) |
| POST | `/api/user/cancel-account` | Cancelar conta (requer verificação de palavra-passe) |
| POST | `/api/user/logout` | Terminar sessão (token adicionado à lista negra) |
| POST | `/api/user/switch-role` | Alternar identidade (role: customer/technician) |

Alternar para technician requer um perfil de técnico com estado approved.

#### 2.2 Gestão de endereços

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/user/addresses` | Lista de endereços |
| POST | `/api/user/addresses` | Novo endereço (contact_name/contact_phone/province/city/district/detail/lat/lng/is_default) |
| GET | `/api/user/addresses/{id}` | Detalhes do endereço |
| PUT | `/api/user/addresses/{id}` | Atualizar endereço |
| DELETE | `/api/user/addresses/{id}` | Eliminar endereço |

Ao definir como predefinido, os outros endereços predefinidos são cancelados automaticamente.

#### 2.3 Favoritos

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/user/favorites` | Lista de favoritos (?type=service/technician) |
| POST | `/api/user/favorites` | Adicionar favorito (target_type/target_id) |
| DELETE | `/api/user/favorites/{id}` | Remover favorito |

#### 2.4 Feedback de opiniões

`POST /api/user/feedback` — submeter feedback (content + array images)

#### 2.5 Promoção e recomendação

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/user/referral` | Informações de promoção (código de recomendação/n.º de recomendados/n.º de primeiros pedidos/pontos obtidos) |
| GET | `/api/user/referral/qrcode` | QR code de promoção (código de recomendação + ligação de convite) |
| GET | `/api/user/referral/referred-users` | Lista de utilizadores recomendados |
| GET | `/api/user/referral/earnings` | Extratos de comissões de distribuição (paginado: apelido/avatar do recomendado/número do pedido/montante/hora de emissão) |

**Comissões de distribuição**: emitidas após o primeiro pedido completed do recomendado, montante = paid_amount × reward_rate (appointment_system_config referral.reward_rate, padrão 0.05, valor ilegal recai para a constante). Tripla idempotência com bloqueio de linha + verificação de vazio de rewarded_at + reavaliação do primeiro pedido; contabilização em WalletTxn type=referral_reward.

#### 2.6 Transferência de pontos (ronda 19)

| Método | Caminho | Descrição |
|------|------|------|
| POST | `/api/user/points/transfer` | Transferência de pontos (to_user_id hashid/points) |
| GET | `/api/user/points/transfers` | Registos de transferências (?direction=sent/received&page=1) |

**Transferência de pontos**: desencriptação do hashid do recetor + existência 404, para si próprio 422, pontos 1-10000 422, saldo agregado SUM insuficiente 422, limite diário acumulado de 10000 422. Proteção de concorrência: bloqueio Redis NX points_transfer:{user} 30s → dentro da transação lockForUpdate da última movimentação de ambas as partes (user_id por ordem crescente contra deadlock de transferências mútuas) → revalidação de saldo/limite/recetor dentro do bloqueio. Norma de movimentações: remetente type=consume/source=points_transfer com valor negativo (balance = snapshot anterior − atual), recetor type=earn/source=points_transfer com valor positivo incluindo expires_at (PointsExpiryTimer pode expirar normalmente); após commit, notificação interna ao recetor type='points_received' (falha apenas warn).

#### 2.7 Preferências de notificações (ronda 19)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/user/notify-settings` | Consultar interruptores de notificações (5 tipos completos) |
| PUT | `/api/user/notify-settings` | Atualização em lote dos interruptores (types: {service_reminder: 0/1, ...}) |

**Interruptores de notificações**: tabela appointment_user_notify_setting (chave única composta user_id+type, linha ausente = ativo por padrão). 5 tipos: service_reminder lembrete de serviço / card_expiry lembrete de expiração (guarda-chuva unificado de cartão+cupão) / points_expiry expiração de pontos / marketing marketing (reservado) / system sistema (não pode ser desligado, PUT força para 1). Controlo: notifySettingEnabled ligado aos 3 processos de temporizadores ServiceReminderTimer/ExpiryReminderTimer/PointsExpiryTimer + mapeamento de cenários de eventos de subscrição (PAY/REFUND/VERIFIED/RESCHEDULE→system sempre enviado, REMINDER→service_reminder, EXPIRY→card_expiry); quando o tipo está desligado, notificações internas e mensagens de subscrição são ambas ignoradas.

---

### 3. Interfaces do técnico (requerem JWT + identidade de técnico)

#### 3.1 Perfil do técnico

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/technician/profile` | Obter o perfil do técnico |
| PUT | `/api/technician/profile` | Atualizar o perfil (avatar/intro/real_name/gender/id_card/id_card_front/id_card_back) |

O primeiro preenchimento completo é considerado candidatura de adesão, com status=pending a aguardar aprovação.

#### 3.2 Agendamentos

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/technician/schedule` | Consulta de agendamentos (?start_date=&end_date=) |
| PUT | `/api/technician/schedule` | Definir agendamento (date/time_slots/status), sobreposição de períodos 422 "Conflito de horário com agendamento existente" |
| POST | `/api/technician/schedule/batch` | Agendamento em lote (ronda 23): período de datas ≤7 dias + filtro weekdays, dias com agendamento existente ignorados, resposta created/skipped |

#### 3.3 Pedidos do técnico

`GET /api/technician/orders` — lista de pedidos (?status=&page=1)

#### 3.4 Rendimentos

`GET /api/technician/earnings` — resumo de rendimentos (today_income/pending_settlement/balance + lista de movimentos)

#### 3.5 Levantamento

`POST /api/technician/withdraw` — pedido de levantamento (amount)
Regras: levantamento no dia 20 de cada mês, T+1 para o saldo, montante mínimo/limite de centenas configurados no painel.

**Reserva em trânsito (2026-08-26)**: no pedido, o saldo é imediatamente deduzido como reserva em trânsito (pending/approved); antes da transferência na aprovação, reavalia settled − withdrawn − em trânsito ≥ montante do levantamento; aprovação concorrente não faz pagamento duplo.

#### 3.6 Resposta a avaliações (ronda 18)

`POST /api/technician/review/reply/{order_id}` — resposta do técnico a avaliações (reply). Avaliação inexistente/não própria unificados 404 (não revela existência); resposta já existente 422 (rejeição idempotente sem sobrepor); resposta vazia 422. Resposta bem-sucedida envia notificação interna ao utilizador (type='review_reply').

#### 3.6 Bancada de trabalho

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/technician/work/today` | Lista de tarefas de hoje |
| GET | `/api/technician/work/records` | Registos de conclusões paginados |
| POST | `/api/technician/work/{id}/start` | Iniciar serviço |
| POST | `/api/technician/work/{id}/complete` | Concluir serviço |

**Tarefas de hoje**: status ∈ [confirmed, serving], service_time de hoje ou vazio, devolve service_name/price/nickname/avatar.

**Registos de conclusões**: status ∈ [serving, completed], ordenação decrescente por service_end_at, resposta paginada com meta.

**Iniciar/concluir serviço**: bloqueio de linha + validação da máquina de estados, operação idempotente. Iniciar serviço escreve service_start_at; concluir serviço escreve service_end_at e envia notificação interna. Códigos de erro: não próprio 403, estado incorreto 422, hashid inválido 422.

---

### 4. Interfaces de pedidos (requerem autenticação JWT)

| Método | Caminho | Descrição |
|------|------|------|
| POST | `/api/order` | Criar pedido (order_type/items/store_id/technician_id/service_time/coupon_id/user_coupon_id/promotion_id/remark) |
| GET | `/api/order/list` | Lista de pedidos (?status=&page=1) |
| GET | `/api/order/detail/{id}` | Detalhes do pedido |
| POST | `/api/order/cancel/{id}` | Cancelar pedido (reason) |
| POST | `/api/order/pay/{id}` | Iniciar pagamento (pay_channel: wechat/balance, use_points: pontos opcionais como pagamento) |
| POST | `/api/order/refund/{id}` | Pedir reembolso |
| POST | `/api/order/verify/{id}` | Verificação (code: valor do QR code) |
| POST | `/api/order/reschedule/{id}` | Remarcar agendamento (new_service_time obrigatório/reason opcional) |
| GET | `/api/order/logistics/{id}` | Rastreio logístico (ronda 19, pedidos de produtos) |
| POST | `/api/order/review/{order_id}` | Submeter avaliação (rating 1-5/content/images) (registo completado na ronda 19) |
| POST | `/api/order/review/{order_id}/append` | Avaliação complementar (content/images separados por vírgulas) (ronda 19) |

**Estados do pedido**: pending(por pagar) → paid(pago) → confirmed(confirmado) → serving(em serviço) → completed(concluído)

**Ao criar o pedido**: o Redis SETNX bloqueia o técnico por 3 minutos; sair da página ou timeout liberta.

**Proteção de preços contra adulteração (2026-08-26)**: os montantes dos itens do pedido são sempre tomados dos registos da base de dados (target_type=service consulta appointment_service, product consulta appointment_product), os preços enviados pelo cliente não entram no cálculo; target_type desconhecido 422; target_id deve ser enviado em valor codificado com hashid (enviar raw id desencripta para 0 → 422 "Produto inexistente ou removido"); preços de compras em grupo/promoções relâmpago também tomados do DB.

**Regras de reembolso**: dentro de 15 min após a encomenda ou >6h até ao início 100% / ≤6h 90% / já iniciado 80% / após confirmação do início sem reembolso.

**Desconto com cupões**: ao criar o pedido é possível enviar user_coupon_id (hashid). Códigos de erro: cupão de outro 404, limite não atingido/expirado/removido/já usado 422, hashid inválido 422. Desconto em duas fases: no pedido, PriceCalculator.applyCoupon valida em leitura e calcula o valor do desconto, escrevendo discount_amount; após pagamento concluído, consume define o cupão como used; no reembolso, restoreCouponAndCard devolve com idempotência.

**Pagamento com saldo e reembolso**: no corpo do pedido de pagamento envie `pay_channel: "balance"` para usar o saldo da carteira; reembolsos WeChat e de saldo repõem ambos o montante no saldo da carteira.

**Pontos como pagamento**: no corpo do pedido de pagamento envie `use_points` (inteiro) de forma opcional. Validação agregada SUM do saldo de pontos (a coluna balance de appointment_user_points é um snapshot incremental único, não pode ser tratada diretamente como saldo), valor do desconto = floor(use_points / config('app.points_rate', 100)) RMB, montante efetivo = montante a pagar original − valor do desconto (mínimo 0.01, se exceder o montante a pagar aplica-se o máximo sem desperdiçar pontos). Em sucesso escreve movimentação de consumo type=consume/source=points_offset (idempotente, repetição não deduz de novo). Saldo insuficiente 422.

**Devolução de pontos**: no cancelamento/reembolso devolve os pontos consumidos com points_offset (type=earn/source=points_refund): cancelamento total, reembolso proporcional, idempotência em 5 pontos de ligação (refundOffsetPoints).

**Encomenda de compra em grupo (ronda 16)**: ao criar o pedido é possível enviar `promotion_id` (hashid). Validações: apenas tipo group_buy, atividade dentro do período de validade, chamador é participante, não cheio (grupo já formado bloqueia 422), serviço do pedido corresponde à atividade; preço de grupo = preço original × discount_percent/100, cupões/cartões de vezes/pontos desativados (enviar qualquer um é 422). O pedido regista promotion_id/participant_id; o pagamento reutiliza totalmente `POST /api/order/pay/{id}`; no pay, verificação inativa se a atividade foi encerrada (expirada sem formar grupo) → o pedido é cancelado automaticamente e o bloqueio do técnico é libertado.

**Encomenda de promoção relâmpago (ronda 18, descontinuado)**: ~~ao criar o pedido envia `promotion_id` (tipo flash_sale)~~ — desde 2026-08 o antigo canal de promoção FLASH_SALE foi removido, o ramo de promoção de store() tem apenas compras em grupo GROUP_BUY (promotion não grupo 422); as promoções relâmpago seguem exclusivamente o canal `/api/seckill` da ronda 24 (seckill_id injetado no store com dedução de stock por bloqueio de linha na transação), PromotionController::index filtra flash_sale, show/join devolvem 400, a constante `Promotion::TYPE_FLASH_SALE` é mantida para compatibilidade com dados históricos.

**Remarcação de agendamento (ronda 17)**: `POST /api/order/reschedule/{id}` envia new_service_time (obrigatório) + reason (opcional), altera o horário com o mesmo técnico. Regras: apenas pedido próprio (não próprio 404), apenas tipo appointment e estado pending/paid/confirmed (resto 422), ≥ 6 horas até ao início original do serviço (alinhado com a janela de reembolso total). Proteção de concorrência: B1 order_lock (mesma família de exclusão mútua de pay/cancel/refund) → bloqueio do técnico para o novo período Redis SETNX EX 180 (remarcação concorrente contra sobre-venda) → releitura com bloqueio de linha na transação + validação DB de conflito de agendamento B2 (exclui este pedido) → atualização de service_time + registo em appointment_order_reschedule → libertação do bloqueio do período original, o novo período fica detido por este pedido → mensagem de subscrição SCENE_RESCHEDULE (sem configuração degrada para notificação interna). No caminho de falha, a transação reverte e o bloqueio do novo período é também libertado.

**Rastreio logístico (ronda 19)**: `GET /api/order/logistics/{id}` — apenas pedidos de produtos próprios podem consultar (não próprio/não produto/não expedido unificados 404). Lê o JSON de order.remark (shipping_company/tracking_no/shipped_at, escritos pelo admin MallOrderController::ship() na expedição), parseShippingInfo/parseReceiver com dupla análise de recurso ao formato antigo; telefone do recetor mascarado 138****5678.

**Avaliação (ronda 19)**: `POST /api/order/review/{order_id}` submete avaliação (rating obrigatório 1-5, content/images opcionais): não próprio 404, não completed 422, avaliação repetida 400. `POST /api/order/review/{order_id}/append` avaliação complementar (content obrigatório, images separadas por vírgulas): avaliação inexistente/não própria unificados 404, não completed 422, complementar repetida 422, conteúdo vazio 422; em sucesso escreve append_content/append_images(JSON)/append_at e notificação interna ao técnico type='review_append', a resposta expõe os campos append.

### 4.1 Interfaces de pós-venda (requerem autenticação JWT)

| Método | Caminho | Descrição |
|------|------|------|
| POST | `/api/aftersales` | Pedir pós-venda (order_id hashid/type: refund\|exchange/reason), valida pedido próprio 404, apenas estado paid+completed pode pedir 422, pós-venda em curso no mesmo pedido deduplicada 422 |
| GET | `/api/aftersales` | Lista dos meus pós-vendas (?status=&page=1&limit=) |
| GET | `/api/aftersales/{id}` | Detalhes do pós-venda (verificação de pertença 404) |

**Estados do pós-venda**: pending(por aprovar) → approved(aprovado) / rejected(rejeitado). approved apenas altera o estado; a ação de reembolso reutiliza `POST /api/order/refund/{id}`.

---

### 4.2 Interfaces de compras em grupo/promoções (requerem autenticação JWT; FLASH_SALE descontinuado)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/promotions` | Lista de atividades (?type=group_buy; flash_sale filtrado e não devolvido) |
| GET | `/api/promotions/{id}` | Detalhes da atividade (inclui número de participantes/se grupo formado; tipo flash_sale 400) |
| GET | `/api/promotions/{id}/participants` | Lista de participantes |
| POST | `/api/promotions/join/{id}` | Participar na atividade (ronda 15 aperfeiçoada: resposta inclui discount_percent/original_price/group_price; tipo flash_sale 400) |

**Regras de participação**: group_buy cheio (≥min_people) bloqueado, após grupo formado novas participações 422; expirado sem cheio encerra de forma inativa (status definido para 0 em show/join). Após join, encomendar ao preço de grupo ver "Encomenda de compra em grupo (ronda 16)". As promoções relâmpago já não usam este canal, ver "24. Interfaces de promoções relâmpago".

---

### 5. Interfaces de marketing (requerem autenticação JWT)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/marketing/coupons` | Lista de cupões (?status=available/used/expired) |
| POST | `/api/marketing/coupons/receive` | Receber cupão (coupon_id) |
| GET | `/api/marketing/cards` | Lista de cartões de membro |
| POST | `/api/marketing/cards/buy` | Comprar cartão de membro (card_id) |
| GET | `/api/marketing/cards/my` | Lista dos meus cartões de vezes |
| POST | `/api/marketing/cards/use` | Verificar cartão de vezes (user_card_id/service_id/remark?) |
| GET | `/api/marketing/gift-cards` | Lista de cartões-presente |
| GET | `/api/marketing/gift-cards/my` | Os meus cartões-presente (registos redeem) |
| POST | `/api/marketing/gift-cards/redeem` | Trocar cartão-presente (o tipo cash após troca recarrega o saldo da carteira) |
| GET | `/api/marketing/points` | Movimentos de pontos (?type=earn/use/expire&source=order/referral/gift_card/check_in/admin) |
| GET | `/api/marketing/points-exchange` | Lista de produtos de troca de pontos (em prateleira + stock restante em tempo real + quantidade trocada) |
| POST | `/api/marketing/points-exchange/{id}` | Troca (type=coupon emite cupão / wallet credita saldo / gift_card devolve código) |
| POST | `/api/marketing/coupons/transfer` | Gerar código de oferta (user_coupon_id: código único de 8 caracteres/válido 7 dias) |
| POST | `/api/marketing/coupons/claim` | Receber cupão ofertado (code) |
| GET | `/api/marketing/coupons/transfers` | Registos de ofertas (emitidas pending/claimed/expired + recebidas claimed) |

**Cartões de vezes**: cards/my devolve card_id/name/type/services/total_times/used_times/remaining_times/start_at/end_at/status (calculados em tempo real). Verificação bem-sucedida devolve {order_id, usage_id, remaining_times}; códigos de erro: hashid inválido 422, vezes insuficientes 422, expirado 400, não próprio 404, anti-duplicação Redis 400.

**Cartões-presente**: gift-cards/my devolve registos redeem (type/amount/gift_name/status/used_at).

**Regras de pontos**: extratos paginados, filtro type (earn/use/expire), filtro source (order/referral/gift_card/check_in/admin). Pontos por registo diário (CheckIn, type=earn); pontos por consumo floor(paid_amount×1), emitidos na verificação com idempotência; reembolso desconta pontos proporcionalmente.

**Expiração de pontos (ronda 17)**: coluna appointment_user_points.expires_at (config points.expiry_days, padrão 365 dias, ≤0 nunca expira), todos os earn preenchem a validade na escrita; processo programado PointsExpiryTimer varrimento de cursor a cada 60s das linhas earn expiradas, escreve linha de dedução negativa type=expire (source=expiry + order_id para rastrear a movimentação original, idempotência de três níveis) + notificação interna agregada "Tem X pontos expirados"; o saldo disponível agregado SUM inclui as linhas expire negativas, pontos expirados não podem ser usados para pagamento/troca.

**Oferta de cupões (ronda 17)**: transfer valida que o cupão pertence ao próprio/available/definição do cupão não expirada/nunca foi ofertado, gera código de oferta único de 8 caracteres sem caracteres ambíguos (índice único uk_code como salvaguarda), válido 7 dias. claim anti-abuso: bloqueio Redis NX (coupon_transfer_claim:{code} 30s) + revalidação com bloqueio de linha contra gasto duplo, índice único uk_user_coupon limita a oferta do mesmo cupão a uma vez, cupão recebido não pode ser reofertado (o novo cupão sem registo de oferta é naturalmente bloqueado), não pode receber o próprio cupão ofertado por si 422, recetor não é o titular original; expiração inativa define expired e devolve o cupão original a available. No claim, dentro da transação o cupão original é definido como used + gera novo UserCoupon vinculado ao recetor (coupon_id inalterado, ou seja, validade inalterada) + regista claimed.

---

### 6. Interfaces de notificações (requerem autenticação JWT)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/notification` | Lista de notificações (?type=order/system&page=1) |
| PUT | `/api/notification/read/{id}` | Marcar como lida |
| PUT | `/api/notification/read-all` | Marcar tudo como lido |

---

### 7. Interfaces de carteira (requerem autenticação JWT)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/wallet` | Saldo da carteira + movimentos paginados |
| POST | `/api/wallet/recharge` | Criar ordem de recarga (amount: RMB) |
| POST | `/api/wallet/recharge/{id}/pay` | Iniciar pagamento da ordem de recarga (WeChat) |
| POST | `/api/wallet/transfer` | Transferência de saldo (to_user_id hashid/amount/remark opcional/client_token opcional) (ronda 19) |
| GET | `/api/wallet/transfers` | Registos de transferências (?direction=out/in&page=1) (ronda 19) |
| GET | `/api/wallet/transfers/{id}` | Detalhes da transferência (visível apenas para ambas as partes, outro 404) (ronda 19) |

**Movimentos**: tipos de wallet_txn: recharge / consume / refund / gift_card / referral_reward (comissão de distribuição) / referral_level2 (comissão de segundo nível) / points_exchange (contabilização de troca de pontos), devolvidos paginados.

**Recarga**: `POST /api/wallet/recharge` envia amount (RMB) para criar a ordem de recarga, devolve o hashid da ordem. `POST /api/wallet/recharge/{id}/pay` inicia o pagamento WeChat, a resposta inclui sign_params (mesmo padrão do pagamento de pedidos); o callback de pagamento distingue ordens de recarga de pedidos pelo out_trade_no com prefixo R.

**Pagamento com saldo**: no corpo do pedido de pagamento envie `pay_channel: "balance"` para usar o saldo da carteira; reembolsos WeChat e de saldo repõem ambos o montante no saldo da carteira.

**Transferência de saldo (ronda 19)**: `POST /api/wallet/transfer` — desencriptação do hashid do recetor + existência 404, para si próprio 422, montante 0.01-1000/operação 422 (comparação DECIMAL sem float), saldo insuficiente 422, limite diário acumulado de 5000 RMB 422. Concorrência/idempotência: bloqueio Redis NX wallet_transfer:{from} 30s serializa o remetente → dentro da transação lockForUpdate das linhas de carteira de ambas as partes por ordem crescente de user_id (ordem fixa contra deadlock) → deduz ao remetente + credita ao recetor + WalletTxn dupla movimentação (transfer_out/transfer_in com snapshot balance_after) + registo de transferência completed + notificação interna ao recetor type='balance_received' (falha apenas regista). client_token opcional: após sucesso SETNX 24h contra submissão repetida (pedidos falhados não registam token e podem repetir).

---

### 8. Interfaces da bancada de trabalho do gerente de loja (requerem autenticação JWT)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/store-manager/overview` | Resumo de hoje (n.º de pedidos de hoje/rendimento de hoje/em curso/n.º de técnicos/n.º de verificações) |
| GET | `/api/store-manager/orders` | Lista de pedidos da loja (?status=&page=&limit=) |
| GET | `/api/store-manager/technicians` | Lista de técnicos (inclui agendamentos de hoje) |
| GET | `/api/store-manager/revenue` | Agregação de rendimentos dos últimos 7 dias |

**Isolamento store_id**: requireStoreId() força o utilizador atual a estar vinculado a uma loja (appointment_user.store_id), sem loja 403; todas as consultas filtram por store_id.

---

### 9. Interfaces de nível de crescimento (requerem autenticação JWT, ronda 20)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/growth` | Resumo de crescimento atual (balance/nível/diferença para o próximo escalão/nome do nível) |
| GET | `/api/growth/records` | Movimentos de crescimento paginados (?page=&limit=) |
| GET | `/api/growth/levels` | Lista de escalões (pública, sem login) |

**Contabilização de crescimento**: registo diário +10; submissão de avaliação +20 (avaliação complementar não contabiliza); consumo floor(paid) 1 ponto por cada 1 RMB (no callback de pagamento reutiliza a revalidação de estado com idempotência, callbacks repetidos não contabilizam de novo).

### 10. Interfaces de fatura (requerem autenticação JWT, ronda 20)

| Método | Caminho | Descrição |
|------|------|------|
| POST | `/api/invoices` | Pedir fatura (order_id hashid/order_type: service=serviço/points_exchange=troca de pontos/order_type padrão service; montante e título trazidos pelo servidor, não alteráveis) |
| GET | `/api/invoices` | Lista de faturas (?status=&page=) |
| GET | `/api/invoices/{id}` | Detalhes da fatura (apenas próprio) |

**Anti-duplicação**: chave única uk_order_type(order_id, order_type), pedido duplicado do mesmo tipo no mesmo pedido 422 (inclui captura de recurso ao MySQL 1062).

### 11. Interfaces de tickets de apoio ao cliente (requerem autenticação JWT, ronda 20)

| Método | Caminho | Descrição |
|------|------|------|
| POST | `/api/tickets` | Submeter ticket (title/content obrigatórios) |
| GET | `/api/tickets` | Lista de tickets (?status=open/closed&page=) |
| GET | `/api/tickets/{id}` | Detalhes do ticket (apenas próprio, outro 404) |
| POST | `/api/tickets/{id}/close` | Encerrar ticket (apenas próprio/apenas open; rating opcional 1-5 de satisfação, fora do intervalo/não inteiro 422, não fornecido compatível com NULL) |

### 12. Interfaces de calendário de agendamentos (requerem autenticação JWT, ronda 20)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/calendar/technician/{id}` | Vista mensal (?month=YYYY-MM): time_slots do agendamento expandidos em faixas horárias + exclusão de já agendados |
| GET | `/api/calendar/technician/{id}/day` | Vista diária (?date=YYYY-MM-DD): detalhes das faixas disponíveis/já agendadas/indisponíveis do dia |

### 13. Interfaces de dados de fatura (requerem autenticação JWT, ronda 21)

| Método | Caminho | Descrição |
|------|------|------|
| POST | `/api/invoice-titles` | Guardar título (title_type: personal/company; company requer tax_no; mesmo título do mesmo utilizador repetido 422; o primeiro é automaticamente predefinido) |
| GET | `/api/invoice-titles` | Lista de títulos (predefinido no topo) |
| PUT | `/api/invoice-titles/{id}` | Editar título (apenas próprio) |
| DELETE | `/api/invoice-titles/{id}` | Eliminar título (apenas próprio; ao eliminar o predefinido, o mais antigo é automaticamente designado) |
| POST | `/api/invoice-titles/{id}/default` | Definir predefinido (zera os outros do mesmo utilizador em transação) |

**Ligação ao pedido**: POST /api/invoices suporta title_id opcional — resolve o título e traz automaticamente invoice_title/tax_no/title_type; sem title_id mantém o caminho de preenchimento manual original.

### 14. Interfaces de histórico de navegação (requerem autenticação JWT, ronda 21)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/browse-history` | Serviços navegados recentemente (join do nome/capa/preço/preço original do serviço, ordenação decrescente de viewed_at, per_page padrão 15 máximo 50) |
| DELETE | `/api/browse-history/{item_id}` | Eliminar um (apenas próprio, ilegal/de outro 404) |
| DELETE | `/api/browse-history` | Limpar histórico (apenas próprio) |

**Momento do registo**: registo automático após acesso bem-sucedido à interface de detalhes do serviço (sem login ignorado; navegação repetida apenas atualiza viewed_at sem inserção duplicada).

### 15. Interfaces de atividades de desconto por valor mínimo (ronda 22)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/full-reduction-activities` | Lista de atividades de desconto por valor mínimo em vigor (status=1 e dentro do período de validade, ordenação decrescente por valor da redução; interface pública) |

**Regras de sobreposição no pedido**: o desconto por valor mínimo só se aplica a pedidos padrão (compras em grupo/promoções relâmpago ignoradas), o limite (threshold) é avaliado sobre o montante a pagar após cupão/cartão de vezes, ordem de sobreposição **cupão/cartão de vezes → desconto por valor mínimo → desconto de nível**; usa a atividade com maior redução; valor do desconto incorporado em discount_amount, nota acrescenta "Desconto por valor mínimo: compre X e poupe Y"; após o desconto, pagamento efetivo mínimo de 0.01 RMB.

### 16. Exportação ICS dos meus agendamentos (requer autenticação JWT, ronda 22)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/order/ics` | Exportar pedidos válidos dos últimos 90 dias (pending/paid/confirmed/serving) em iCal (RFC5545) |

**Saída**: `Content-Type: text/calendar; charset=utf-8` + `Content-Disposition: attachment; filename="my-appointments.ics"`. VEVENT: UID=ID do pedido, TZID=Asia/Shanghai, resumo "Agendamento: nome do serviço" (ausente degrada para "Agendamento"), descrição (técnico/loja/morada, ausentes omitidos), LOCATION nome da loja; escape de texto conforme RFC5545 (\, \; \\ \n) + quebra de linhas de 75 bytes. Sem pedidos devolve calendário vazio válido; exporta apenas pedidos próprios.

### 17. Interfaces de assiduidade do técnico (requer autenticação JWT, ronda 22)

| Método | Caminho | Descrição |
|------|------|------|
| POST | `/api/technician/attendance/check-in` | Registo de ponto de entrada (repetido no dia 422, índice único como salvaguarda de concorrência; >10:00 marca atraso) |
| POST | `/api/technician/attendance/check-out` | Registo de ponto de saída (sem entrada/já com saída 422, bloqueio de linha na concorrência) |
| GET | `/api/technician/attendance` | Lista de assiduidade do mês + resumo de dias de presença/total de horas/média de horas (?month=YYYY-MM, inválido 422) |

### 18. Interfaces de privacidade e conformidade (requerem autenticação JWT, ronda 22)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/privacy/data` | Exportação de dados (JSON agrupado personal/orders/points/wallet_txns/reviews/addresses/invoices; os registos do servidor apenas guardam telefone mascarado + contagens) |
| POST | `/api/privacy/close-request` | Pedir cancelamento (saldo ≠ 0/pedidos por concluir/tickets em curso 422; define close_status=1 + close_requested_at) |
| POST | `/api/privacy/close-cancel` | Cancelar o pedido de cancelamento (close_status 1→0) |
| POST | `/api/privacy/close-confirm` | Confirmar cancelamento (apenas após 72h; close_status=2 + close_at + phone/nickname anonimizados em user{id} + status=0) |

**Interceção de login**: contas com close_status=2 devolvem 403 "Conta cancelada" no login.

### 19. Interfaces de ficha de saúde do utilizador (requerem autenticação JWT, ronda 23)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/health-profile` | Consultar a minha ficha de saúde (sem ficha devolve objeto vazio) |
| PUT | `/api/health-profile` | Criar/atualizar (upsert, uma por pessoa; allergies/health_notes limite de 500 caracteres, preferred_technician_id valida a existência; atualiza apenas os campos fornecidos, resposta codificada com hashid) |
| DELETE | `/api/health-profile` | Eliminar a minha ficha (apenas próprio) |

Campos: allergies (histórico de alergias)/health_notes (observações de saúde)/preferred_technician_id (técnico preferido, anulável).

### 20. Interfaces de palavra-passe de pagamento da carteira (requerem autenticação JWT, ronda 23)

| Método | Caminho | Descrição |
|------|------|------|
| POST | `/api/wallet/pay-password/set` | Definir palavra-passe de pagamento (6 dígitos `\d{6}`; já definida requer a palavra-passe antiga, interceptada 422) |
| POST | `/api/wallet/pay-password/verify` | Validar palavra-passe de pagamento (correta/errada devolve booleano, não regista) |
| POST | `/api/wallet/pay-password/check` | Consultar se está definida (set: true/false) |

Armazenamento: hash password_hash() + pay_password_set_at, nunca armazena texto simples.

### 21. Interfaces de linha do tempo de estado do pedido (requerem autenticação JWT, ronda 23)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/order/{id}/timeline` | Linha do tempo de alterações de estado do pedido (ordem decrescente; apenas próprio, pedido de outro 404 sem revelar existência) |

Pontos de registo: submissão/pagamento (callback WeChat markOrderPaid como ponto único de consumo)/cancelamento/confirmação do técnico/pedido de reembolso/aprovação de reembolso/início do serviço/conclusão do serviço/cancelamento automático por timeout/operação do painel (operator=admin), 8 tipos de alterações no total.

### 22. Interfaces da roleta da sorte de pontos (requerem autenticação JWT, ronda 23)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/wheel/prizes` | Lista de prémios da roleta (campos sensíveis weight/stock ocultos) |
| POST | `/api/wheel/spin` | Sortear uma vez (Redis NX + bloqueio de linha contra concorrência; extração ponderada random_int; pontos→movimentação earn com data de expiração, saldo→contabilização lockForUpdate, cupão→pending para emissão manual, sem prémio→lose; idempotência client_token) |
| GET | `/api/wheel/records` | Os meus registos de sorteios (paginados) |

### 23. Interfaces de modo convidado (ronda 24)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/guest/home` | Agregação da página inicial (carrosséis/anúncios/categorias de serviços/serviços populares, cache Redis svc:guest:home 300s) |
| GET | `/api/guest/services` | Lista de serviços (?category_id=hashid&sort=newest\|sales\|price&page/per_page≤50) |
| GET | `/api/guest/services/{id}` | Detalhes do serviço (inexistente 404) |
| GET | `/api/guest/stores` | Lista de lojas |
| GET | `/api/guest/technicians` | Lista de técnicos (apenas aprovados; ?service_id=hashid filtro; classificação decrescente) |

Entradas de navegação sem login, sem autenticação (apenas middleware ApiVersion).

### 24. Interfaces de promoções relâmpago (requerem autenticação JWT, ronda 24)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/seckill` | Lista de atividades de promoção relâmpago (status=1 e dentro da janela temporal; inclui quantidade vendida = n.º de pedidos com appointment_order.seckill_id, stock restante) |
| GET | `/api/seckill/{id}` | Detalhes da atividade (state=not_started/ongoing/ended) |
| POST | `/api/seckill/{id}/buy` | Encomenda relâmpago (idempotência client_token + Redis NX 30s contra concorrência + validação da atividade; sem pré-dedução de stock) |

**Regras de encomenda (desde 2026-08-26)**: o stock é deduzido uniformemente com bloqueio de linha dentro da transação de `/api/order store()`; buy faz apenas a validação de entrada/idempotência; preço relâmpago = seckill_price (tomado do DB como referência), sem sobreposição de cupões/pontos/cartões de membro; cancelamento do pedido não repõe stock; chamar diretamente `/api/order` com seckill_id também deduz o stock.

### 25. Interfaces de verificação de versões do APP (ronda 24)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/api/app/version?platform=android|ios` | Verificação da versão mais recente (platform inválido 422; sem versão devolve objeto vazio; interface pública) |

Resposta: id/platform/version_code/version_name/force_update (1=obrigatória)/changelog/download_url.

---

## II. API do painel de administração (admin/ :8787)

Cabeçalhos: `Authorization: Bearer <admin_token>`, `API-Version: v1`

### Dashboard

**`GET /admin/dashboard`** — dados do dashboard

Resposta: user_count / order_count / technician_count / today_revenue + dados de gráficos (volume de pedidos/montantes/novos utilizadores/atividade)

### Gestão de utilizadores

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/user` | Lista de utilizadores (?keyword/status/page/per_page) |
| POST | `/admin/user` | Novo utilizador |
| GET | `/admin/user/{id}` | Detalhes do utilizador |
| PUT | `/admin/user/{id}` | Editar utilizador |
| DELETE | `/admin/user/{id}` | Eliminar utilizador |
| POST | `/admin/user/batch/destroy` | Eliminação em lote |
| POST | `/admin/user/batch/status` | Ativar/desativar em lote |

### Gestão de cartões de membro

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/member-cards` | Lista de cartões (?keyword/status/page/per_page) |
| GET | `/admin/member-cards/{id}` | Detalhes do cartão |
| POST | `/admin/member-cards` | Novo cartão (validação JSON de services) |
| PUT | `/admin/member-cards/{id}` | Atualizar cartão/colocar ou retirar da prateleira |
| DELETE | `/admin/member-cards/{id}` | Eliminar cartão (rejeitado com utilizadores a deter o cartão) |

IDs de permissão: 365-369.

### Bancada de trabalho da loja (ronda 15)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/stores/workbench-overview` | Visão geral da bancada de trabalho da loja (?store_id=hashid: n.º de pedidos de hoje/rendimento de hoje/em curso/n.º de técnicos/verificações de hoje, mesmo critério do lado service) |
| GET | `/admin/orders` | Lista de pedidos com novo filtro store_id (desencriptação hashid) |

IDs de permissão: 372.

### Produtos de troca de pontos (ronda 16)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/points-exchange-goods` | Lista de produtos (?keyword/status/page/per_page) |
| POST | `/admin/points-exchange-goods` | Novo produto (type=coupon/gift_card/wallet; coupon envia hashid, wallet/gift_card enviam montante em RMB) |
| PUT | `/admin/points-exchange-goods/{id}` | Atualizar produto |
| DELETE | `/admin/points-exchange-goods/{id}` | Eliminar produto |
| POST | `/admin/points-exchange-goods/{id}/toggle-status` | Alternar colocação na prateleira |
| GET | `/admin/points-exchange-goods/{id}/exchanges` | Lista de registos de troca (inclui telefone do utilizador + snapshot de result) |

IDs de permissão: 373-378.

### Registos de comissões (ronda 16)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/referral-rewards` | Registos de comissões (?keyword=&page=&limit=, apenas registos já emitidos, filtro por apelido ou telefone do recomendador/recomendado, codificação hashid) |

ID de permissão: 379.

### Níveis de técnico (ronda 17)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/technician-tiers/logs` | Registos de alterações de nível (join do nome do técnico e dos nomes dos níveis antigo/novo, codificação hashid, paginado) |

ID de permissão: 380.

**Avaliação automática**: TierRatingService::evaluate estatísticas em tempo real (n.º de pedidos completed de appointment_order + média de avaliações, arredondada a 1 casa decimal) escritas de volta em profile.order_count/rating, correspondência de alto para baixo conforme appointment_technician_tier_config (min_orders/min_rating), sem correspondência recai para o nível mais baixo. Apenas promoção, sem despromoção (a despromoção afeta a taxa de comissão e o coeficiente de preço, tratada manualmente no painel como recurso; allowDowngrade=true para reavaliação manual); idempotente (nível igual apenas sincroniza estatísticas); alterações registadas em appointment_technician_tier_log + notificação interna. Pontos de acionamento: WorkController::complete / escrita de avaliações no ReviewController / verificação inativa no ProfileController ao consultar o perfil.

### Visualização de respostas a avaliações (ronda 18)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/reviews/{id}/reply` | Detalhes da resposta à avaliação (decodeId → find → 404 → saída decorate; sem resposta reply='', reply/replied_at expostos via toArray; rota estática antes do resource) |

ID de permissão: 381 (slug 'get.admin/reviews/{id}/reply').

### Gestão de faturas (ronda 20)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/invoices` | Lista de faturas (?status=pending/issued/rejected&page=) |
| POST | `/admin/invoices/{id}/issue` | Emitir fatura (invoice_no obrigatório, status→issued + issued_at; idempotente: já emitida 422) |
| POST | `/admin/invoices/{id}/reject` | Rejeitar (reject_reason obrigatório, status→rejected; apenas pending pode ser rejeitado) |

IDs de permissão: 382 lista / 383 emissão / 384 rejeição.

### Gestão de tickets (ronda 20)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/tickets` | Lista de tickets (?status=&page=, rota estática antes do resource contra shadow) |
| POST | `/admin/tickets/{id}/reply` | Responder ao ticket (content obrigatório, escreve reply_content/replied_at, o ticket volta a open) |
| GET | `/admin/tickets/satisfaction` | Resumo de satisfação (ronda 21): total/rated_count/unrated_count/average 1 casa decimal/distribution de 1-5 estrelas com estrelas em falta preenchidas com 0; rota estática antes do resource |

IDs de permissão: 385 resposta de tickets / 387 visualização da lista de tickets / 388 estatísticas de satisfação de tickets.

### Auditoria de imagens de avaliações (ronda 21)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/review-audit` | Lista de avaliações com imagens (JSON_LENGTH(images)>0, ?status=visible/hidden&page=, join do apelido do utilizador e nome do técnico, IDs codificados com hashid) |
| POST | `/admin/review-audit/{id}/hide` | Ocultar avaliação (apenas visible pode ocultar, senão 422; após ocultação, a lista de avaliações do técnico no lado do utilizador fica automaticamente invisível) |
| POST | `/admin/review-audit/{id}/restore` | Restaurar avaliação (apenas hidden pode restaurar, senão 422) |

IDs de permissão: 389 lista / 390 ocultar / 391 restaurar.

### Registos de comissão de segundo nível (ronda 20)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/referral-level2` | Registos de comissão de segundo nível (join dos apelidos do recomendador de primeiro e segundo nível, paginado) |

ID de permissão: 386. Regra de emissão: após pagamento do pedido, envia paid×level2_rate ao recomendador do recomendador de primeiro nível (configuração do sistema referral.level2_rate, padrão 0.02), uk_order_referred idempotente contra duplicação.

### Gestão de assiduidade (ronda 22)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/attendance` | Registos de assiduidade (?date=YYYY-MM&name=nome do técnico&page=; join real_name, IDs codificados com hashid) |
| GET | `/admin/attendance/stats` | Estatísticas agrupadas por técnico (dias de presença/total de horas/média de horas; ?date=YYYY-MM, inválido 422) |

IDs de permissão: 392 lista / 393 estatísticas.

### Gestão de atividades de desconto por valor mínimo (ronda 22)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/full-reduction-activities` | Lista de atividades (paginada) |
| POST | `/admin/full-reduction-activities` | Nova atividade (threshold/reduction/title/status/start_at/end_at) |
| PUT | `/admin/full-reduction-activities/{id}` | Editar |
| POST | `/admin/full-reduction-activities/{id}/toggle-status` | Colocar/retirar da prateleira |
| DELETE | `/admin/full-reduction-activities/{id}` | Eliminar (com confirmPassword) |

IDs de permissão: 396 lista / 397 novo / 398 edição / 399 prateleira / 400 eliminação (um registo de permissão corresponde a um slug method.path, por isso 5 rotas 5 registos).

### Registos de divisão de lucros (ronda 22)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/profit-sharing` | Registos de divisão de lucros (leftJoin do número do pedido/apelido do técnico, ?status&order_no&technician_name&page=, codificação hashid) |

ID de permissão: 394. Lógica do servidor: appointment_system_config group=profit_sharing (enabled/receiver_ratio); não ativado degrada como disabled apenas com log; ativado, pede automaticamente a divisão de lucros após pagamento concluído (montante = efetivo×receiver_ratio, padrão 0.7, mesmo pedido pending/success ignorado com idempotência); sem credenciais não executa HTTP, a estrutura do pedido é registada em log.

### Gestão da roleta de pontos (ronda 23)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/lucky-wheel` | Lista de prémios da roleta (inclui weight/stock, paginada) |
| POST | `/admin/lucky-wheel` | Novo prémio (nome/tipo points/balance/coupon/none/peso/stock/imagem) |
| GET/PUT | `/admin/lucky-wheel/{id}` | Detalhes / edição |
| DELETE | `/admin/lucky-wheel/{id}` | Eliminar |
| POST | `/admin/lucky-wheel/{id}/toggle-status` | Colocar/retirar da prateleira |
| GET | `/admin/lucky-wheel/records` | Registos de sorteios (?status&page=, inclui apelido do utilizador/nome do prémio) |

IDs de permissão: 401-406. As rotas estáticas `/lucky-wheel/records` e `/lucky-wheel/{id}/toggle-status` são registadas antes do resource contra shadow de {id}.

### Gestão de recompensas por cliente recorrente (ronda 24)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/return-customer/config` | Ver configuração (interruptor enabled / proporção ratio) |
| PUT | `/admin/return-customer/config` | Atualizar configuração (enabled in:0,1; ratio between:0.01,1) |
| GET | `/admin/return-customer/rewards` | Lista de registos de recompensas (?keyword nome do técnico/número do pedido/apelido do utilizador, type=return_customer paginado) |

IDs de permissão: 412-414. Regra de recompensa: pela 2.ª compra do utilizador ao mesmo técnico em 30 dias (pedido concluído) é atribuído um bónus = efetivo × ratio (padrão 0.05), registado em appointment_technician_earnings (type=return_customer, status=pending), liquidado em conjunto pela cadeia de liquidação de comissões; idempotente por pedido, sem emissão duplicada.

### Gestão de atividades de promoção relâmpago (ronda 24)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/seckill` | Lista de atividades (paginada) |
| POST | `/admin/seckill` | Nova atividade (name/service_id/seckill_price/original_price/stock/start_at/end_at) |
| GET | `/admin/seckill/{id}` | Detalhes da atividade |
| PUT | `/admin/seckill/{id}` | Editar |
| DELETE | `/admin/seckill/{id}` | Eliminar |
| POST | `/admin/seckill/{id}/toggle-status` | Colocar/retirar da prateleira |
| GET | `/admin/seckill/{id}/orders` | Lista de pedidos de promoção relâmpago |

IDs de permissão: 407-411, 420. Quantidade vendida = n.º de pedidos com appointment_order.seckill_id; stock deduzido com bloqueio de linha, esgotado interceptado.

### Gestão de versões do APP (ronda 24)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/versions` | Lista de versões |
| POST | `/admin/versions` | Nova versão (platform/version_code/version_name/force_update/changelog/download_url/status) |
| PUT | `/admin/versions/{id}` | Editar |
| DELETE | `/admin/versions/{id}` | Eliminar |

IDs de permissão: 416-419. A interface de verificação de atualizações /api/app/version toma a versão mais recente (updated_at/id maior) de status=1.

### Exportação de agendamentos (ronda 24)

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/technician-schedule/export` | Exportação CSV de agendamentos (UTF-8 BOM, abre diretamente no Excel; start_date/end_date obrigatórios e intervalo ≤31 dias; technician_id opcional hashid) |

ID de permissão: 415. Colunas: ID do técnico/nome do técnico/data/detalhes dos períodos (time_slots JSON analisado como "09:00-12:00, 14:00-18:00").

### Papéis e permissões

| Método | Caminho | Descrição |
|------|------|------|
| GET/POST/PUT/DELETE | `/admin/role` | CRUD de papéis |
| GET/POST/PUT/DELETE | `/admin/permission` | CRUD de permissões (estrutura em árvore) |

### Configuração do sistema

| Método | Caminho | Descrição |
|------|------|------|
| GET | `/admin/config` | Lista de configurações |
| POST | `/admin/config` | Nova configuração (group/key/value/type/description) |
| PUT | `/admin/config/{id}` | Editar configuração |
| DELETE | `/admin/config/{id}` | Eliminar configuração |

### Registos de operações

**`GET /admin/log`** — consulta de registos

Parâmetros: `?user_id/action/source/start_date/end_date/page`

Campo `source`: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### Exportação

| Método | Caminho | Descrição |
|------|------|------|
| POST | `/admin/export/excel` | Exportação Excel (type: users/technicians/orders/finance). Campos sensíveis mascarados automaticamente |
| POST | `/admin/export/pdf` | Exportação de painel PDF (type: dashboard) |

### Carregamento de ficheiros

**`POST /admin/upload`** — carregamento de ficheiros (multipart/form-data)

### Conta pessoal

| Método | Caminho | Descrição |
|------|------|------|
| PUT | `/admin/profile` | Alterar perfil pessoal |
| PUT | `/admin/profile/password` | Alterar palavra-passe |
| POST | `/admin/profile/logout` | Terminar sessão |

### Importação

**`POST /admin/import/users`** — importação em lote de utilizadores (Excel)

### Monitorização

| Método | Caminho | Autenticação | Descrição |
|------|------|------|------|
| GET | `/health` | Nenhuma | Verificação de saúde |
| GET | `/metrics` | Nenhuma | Métricas Prometheus |
| GET | `/.well-known/security.txt` | Nenhuma | Contacto de segurança (RFC 9116) |
| GET | `/api/docs` | Nenhuma | Documentação da API |

---

## III. Notas gerais

### Códigos de erro

| code | Descrição |
|------|------|
| 0 | Sucesso |
| 401 | Sem login ou Token expirado |
| 403 | Sem permissão |
| 404 | Recurso inexistente |
| 422 | Falha na validação de parâmetros |
| 429 | Pedidos demasiado frequentes |

### Codificação de IDs

- Todos os campos `id` e `*_id` nas respostas da API são codificados com hashids
- Os parâmetros `id` enviados nos pedidos também devem usar o formato codificado com hashids
- O frontend usa diretamente as strings codificadas, sem desencriptação manual

### Mascaramento de números de telefone

Formato do telefone nas respostas: `138****8000`. Tratamento idêntico na exportação Excel.

### Encriptação de dados

- Camada API: os campos sensíveis nas respostas são encriptados com `erikwang2013/encryption`
- Camada DB: telefone/cartão de cidadão/IDs WeChat, etc., são encriptados/desencriptados automaticamente com `erikwang2013/encryptable`

### Configuração de variáveis de ambiente

| Variável | Descrição |
|------|------|
| WECHAT_SUBSCRIBE_TEMPLATE_ID | ID do modelo de mensagem de subscrição de lembrete de agendamento |
| WECHAT_SUBSCRIBE_TEMPLATE_PAID | ID do modelo de mensagem de subscrição de pagamento concluído |
| WECHAT_SUBSCRIBE_TEMPLATE_REFUND | ID do modelo de mensagem de subscrição de reembolso |
| WECHAT_SUBSCRIBE_TEMPLATE_VERIFIED | ID do modelo de mensagem de subscrição de verificação concluída |
| WECHAT_SUBSCRIBE_TEMPLATE_REMINDER | ID do modelo de mensagem de subscrição de lembrete antes do início do serviço (ronda 18) |
| WECHAT_SUBSCRIBE_TEMPLATE_EXPIRY | ID do modelo de mensagem de subscrição de lembrete de expiração de cartões de membro/cupões (ronda 18) |

Sem modelo de mensagem de subscrição configurado, degrada automaticamente para notificação interna.

**Cenários de mensagens de subscrição**: SCENE_PAY (pagamento concluído) / SCENE_REFUND (reembolso recebido) / SCENE_VERIFIED (verificação concluída) / SCENE_RESCHEDULE (remarcação concluída) / SCENE_REMINDER (lembrete antes do início do serviço, ronda 18) / SCENE_EXPIRY (lembrete de expiração, ronda 18). push_sent_at só é escrito após envio bem-sucedido; falha repete na ronda seguinte.

**Notificação de recarga recebida (ronda 18)**: no callback de recarga WeChat (número com prefixo R), dentro da transação, escreve notificação interna type='wallet_recharge' "Recarregou com sucesso ¥X.XX"; reutiliza a idempotência do callback (apenas o primeiro pending→paid aciona), submetida atomicamente com a alteração de estado na mesma transação, falha de escrita não bloqueia o fluxo principal.
