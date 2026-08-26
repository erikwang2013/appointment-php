# Diagram Alur Bisnis Inti
> **Languages**: [中文](../../diagrams/FLOWCHART.md) · [English](../../en/diagrams/FLOWCHART.md) · [한국어](../../ko/diagrams/FLOWCHART.md) · [Русский](../../ru/diagrams/FLOWCHART.md) · [Deutsch](../../de/diagrams/FLOWCHART.md) · [Français](../../fr/diagrams/FLOWCHART.md) · [Español](../../es/diagrams/FLOWCHART.md) · [Português](../../pt/diagrams/FLOWCHART.md) · [हिन्दी](../../hi/diagrams/FLOWCHART.md) · [العربية](../../ar/diagrams/FLOWCHART.md) · [বাংলা](../../bn/diagrams/FLOWCHART.md) · [日本語](../../ja/diagrams/FLOWCHART.md)

> Terjemahan bahasa Indonesia · Asli: [中文](../../docs/diagrams/FLOWCHART.md)

## 1. Alur Janji Temu Layanan

```mermaid
flowchart TD
    A["用户浏览服务项目"] --> B["选择门店/技师/时间"]
    B --> C["填写备注"]
    C --> D{"选择优惠券?"}
    D -->|"使用"| E["优惠券抵扣金额"]
    D -->|"不用"| F["原价下单"]
    E --> G["下单算价（不消费）<br/>PriceCalculator 纯计算<br/>券 fixed/percent + 次卡 times<br/>min_amount 基于原价"]
    F --> G
    G --> H["阅读服务协议"]
    H --> I["提交订单"]
    I --> J{"Redis 锁定技师<br/>SETNX 3分钟"}
    J -->|"锁定成功"| K["创建订单 pending"]
    J -->|"已被锁定"| L["提示技师繁忙"]
    K --> M{"应付金额?"}
    M -->|"零元"| N["FREE 直通<br/>transaction_id = 'FREE'+支付单号<br/>订单 → paid"]
    M -->|"余额支付"| B1["钱包余额扣减<br/>wallet_txn 入账<br/>订单 → paid"]
    M -->|"金额 > 0"| O{"支付方式"}
    O -->|"微信"| OW["调用微信支付<br/>pay_lock 防并发重复支付"]
    O -->|"余额"| B1
    OW --> P{"支付结果"}
    B1 --> S
    P -->|"成功"| Q["支付成功回调消费<br/>markOrderPaid 单一消费点<br/>原子扣减券/次卡<br/>订单 → paid"]
    P -->|"失败/取消"| R["订单保持 pending<br/>15分钟后自动取消"]
    N --> S["技师确认服务开始"]
    Q --> S
    S --> T["订单 → serving"]
    T --> U["服务完成"]
    U --> V["技师扫码核销"]
    V --> W["订单 → completed"]
    W --> X["用户评价（文字+图片）"]
    X --> Y["订单 → reviewed ✅"]

    style A fill:#e3f2fd,stroke:#1565c0,color:#333
    style Y fill:#c8e6c9,stroke:#2e7d32,color:#333
    style L fill:#ffcdd2,stroke:#c62828,color:#333
    style R fill:#fff9c4,stroke:#f9a825,color:#333
    style N fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 2. Alur Pembayaran dan Refund

```mermaid
flowchart TD
    subgraph 支付流程["正向支付流程"]
        P1["创建支付记录"] --> P2["微信统一下单<br/>pay_lock 防并发<br/>out_trade_no = order_no 幂等"]
        P2 --> P3["前端调起支付<br/>选择支付方式"]
        P3 -->|"余额"| PB["钱包余额扣减<br/>wallet_txn 入账<br/>幂等 仅扣一次"]
        P3 -->|"微信"| P4["微信回调 notify"]
        P4 --> P5["验签通过"]
        PB --> P6["markOrderPaid 幂等<br/>券/次卡仅此一次消费"]
        P5 --> P6
        P6 --> P7["订单 → paid<br/>通知用户+技师"]
    end

    subgraph 退款流程["退款流程"]
        R1["用户申请退款<br/>refund_lock 防并发"] --> R2{"退款规则判定"}
        R2 -->|"下单≤15min 或 距开始>6h"| R3["退款 100%"]
        R2 -->|"距开始≤6h"| R4["退款 90%"]
        R2 -->|"已开始未确认"| R5["退款 80%"]
        R2 -->|"服务确认后"| R6["不予退款"]
        R3 --> R7["订单 → refunding"]
        R4 --> R7
        R5 --> R7
        R7 --> R8["两级审批<br/>店长→财务"]
        R8 --> R9["两段式退款<br/>事务内建退款记录<br/>事务外微信退款 IO"]
        R9 -->|"微信失败"| R10["回滚订单 PAID<br/>可重试退款"]
        R9 -->|"退款成功"| R11["订单 → refunded<br/>微信原路退回 / 余额回充<br/>优惠券归还 + 积分回扣"]
    end

    style P6 fill:#c8e6c9,stroke:#2e7d32,color:#333
    style R6 fill:#ffcdd2,stroke:#c62828,color:#333
    style R11 fill:#c8e6c9,stroke:#2e7d32,color:#333
    style R10 fill:#fff9c4,stroke:#f9a825,color:#333
```

## 3. Alur Penarikan Dana Teknisi

```mermaid
flowchart TD
    A["技师申请提现"] --> B{"poster-php<br/>操作验证"}
    B -->|"验证通过"| C{"提现条件检查"}
    B -->|"验证失败"| X["拒绝操作"]
    C -->|"每月20号"| D["创建提现记录"]
    C -->|"非提现日"| Y["提示每月20号可提现"]
    D --> E["后台审核"]
    E --> F{"审核结果"}
    F -->|"通过"| G["执行提现"]
    F -->|"驳回"| H["退回申请<br/>附驳回原因"]
    G --> I["微信企业付款到零钱"]
    I --> J["T+1 到账"]
    J --> K["生成财务流水<br/>记录收支"]

    style K fill:#c8e6c9,stroke:#2e7d32,color:#333
    style X fill:#ffcdd2,stroke:#c62828,color:#333
    style Y fill:#fff9c4,stroke:#f9a825,color:#333
    style H fill:#ffcdd2,stroke:#c62828,color:#333
```

## 4. Alur Peralihan Identitas

```mermaid
flowchart TD
    A["当前身份: 客户"] --> B["点击切换技师"]
    B --> C{"技师档案状态"}
    C -->|"approved"| D["active_role = technician<br/>页面切换为技师工作台"]
    C -->|"未入驻/审核中"| E["引导入驻申请"]
    E --> F["填写技师信息<br/>姓名/性别/手机号<br/>身份证/照片"]
    F --> G["提交审核"]
    G --> H{"后台审核"}
    H -->|"通过"| D
    H -->|"驳回"| I["修改重新提交"]

    J["当前身份: 技师"] --> K["点击切换客户"]
    K --> L["active_role = customer<br/>页面切换为客户界面"]

    style D fill:#c8e6c9,stroke:#2e7d32,color:#333
    style L fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 5. Alur Isi Saldo Dompet/Kartu Hadiah

```mermaid
flowchart TD
    A["用户充值 / 兑换礼品卡"] --> B{"入账方式"}
    B -->|"微信充值"| C["微信支付回调<br/>wallet_recharge 记录<br/>幂等入账"]
    B -->|"礼品卡兑换"| D["GiftCard redeem 核销卡密<br/>金额入账钱包余额"]
    C --> E["钱包余额增加<br/>wallet_txn 入账"]
    D --> E
    E --> F["余额支付订单<br/>或 退款回充余额"]
    F --> G["入账/回充完成 ✅"]

    style G fill:#c8e6c9,stroke:#2e7d32,color:#333
```
