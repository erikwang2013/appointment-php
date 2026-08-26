> Tradução em português · Original: [中文](../FEATURE-DESIGN.md)

# Design de funcionalidades

## Fluxo de compra

### Fluxo de agendamento de serviço (encomenda direta)

```
Detalhes do serviço → Confirmar pedido (loja/técnico/horário/cupão/observações) → Ler o acordo de serviço
    → Submeter pedido → Redis bloqueia técnico 3 minutos → Pagamento WeChat → Pagamento concluído
    → Notificar utilizador+técnico → Hora do serviço → Técnico confirma início
    → Serviço concluído → Verificação por QR code → Avaliação do utilizador → Pedido concluído
```

### Fluxo de compra de produtos (modo carrinho)

```
Lista de produtos → Adicionar ao carrinho → Confirmação no carrinho (alterar quantidade/eliminar)
    → Submeter pedido → Pagamento → Expedição → Receção → Concluído
```

## Máquina de estados do pedido

```
pending(por pagar) → paid(pago) → confirmed(confirmado)
    → serving(em serviço) → completed(concluído) → reviewed(avaliado)

pending → cancelled(cancelado)
paid → cancelled
paid → refunding(em reembolso) → refunded(reembolsado)
```

## Mecanismo de bloqueio do técnico

O utilizador entra na página de confirmação do pedido → Redis SETNX bloqueia 3 minutos. Sair/timeout liberta.

```
SETNX lock:tech:123:2026-05-26-14:00 user_456 EX 180
 → Sucesso: continua a encomenda
 → Falha: técnico já bloqueado
```

## Regras de reembolso

| Condição | Percentagem de reembolso |
|------|----------|
| Dentro de 15 minutos após a encomenda ou >6 horas até ao início | 100% |
| ≤6 horas até ao início | 90% |
| Já iniciado mas serviço não confirmado | 80% |
| Após confirmação do início do serviço | 0% (sem reembolso) |

## Regras de desconto

| Tipo | Condição | Desconto | Sobreposição |
|------|------|------|------|
| Desconto de menor procura | 10h-12h/17h-18h/ após as 21h | 10% de desconto | Acumulável com cupões |
| Agendamento antecipado | 30+ minutos de antecedência | 5% de desconto | Não acumulável com cupões |

## Levantamento do técnico

- Levantamento no dia 20 de cada mês, T+1 para o saldo WeChat
- Verificado não liquidado: confirmação automática em 3 dias
- Montante mínimo/montante de retenção/limite de centenas configuráveis no painel

### Fluxo de levantamento

```
Pedido de levantamento → verificação poster-php → aprovação no painel (aprovado/rejeitado)
    → levantamento concluído → saldo WeChat creditado → gera movimento financeiro
```

### Tipos de rendimentos

| Tipo | Descrição |
|------|------|
| commission | Comissão de serviço |
| bonus | Bónus (cliente recorrente/assiduidade) |
| penalty | Multa (não preencheu o ficheiro em 24h) |
| subsidy | Subsídio |
| attendance | Recompensa de assiduidade total |

### Recompensa por cliente recorrente

2.º consumo ao mesmo técnico em 30 dias → regista bónus

### Ficheiro de membro

Após cada pedido concluído, o ficheiro deve ser preenchido dentro de 24h, caso contrário não há comissão

## Design de pontos

- Obtidos por consumo, por recomendação (configurável no painel)
- 1:100 para trocar por cartão-presente (configurável no painel)
- A tabela de movimentos de pontos regista cada alteração + saldo

## Design de cartões de membro

| Tipo | Cobrança | Descrição |
|------|------|------|
| month | Por dia | Cartão mensal normal |
| vip | Por dia | Cartão anual VIP |
| times | Por utilização | Cartão de vezes, combinação livre de projetos de serviço |

Cartão de vezes: na compra, escolher a combinação de serviços (A×3+B×5), cada utilização consome 1 vez do respetivo projeto. Esgotado → used_up, expirado → expired.

## Alternância de identidade

```
Cliente → alternar para técnico → verificar se o perfil do técnico é approved
    → Sim: active_role=technician, a página alterna
    → Não: orientar para a candidatura de adesão

Técnico → alternar para cliente → active_role=customer, a página alterna
```

## Recompensa de novo utilizador

```
Registo → gerar código de recomendação → com recomendador → criar registo de promoção
    → enviar automaticamente cupão de novo utilizador (Fase 5)
    → o recomendador recebe pontos (após o primeiro pedido do recomendado)
```

## Design de pagamento (reserva para pagamento WeChat)

```
POST /api/order/pay/{id}
    → criar registo de pagamento → chamar encomenda unificada do WeChat (WechatPayService reservado)
    → devolver parâmetros de pagamento → o frontend invoca o pagamento
    → callback WeChat /api/wechat/notify → verificação de assinatura → atualizar estado para paid
    → notificar utilizador+técnico
```
