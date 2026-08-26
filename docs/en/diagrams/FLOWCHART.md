# Core Business Flowcharts
> **Languages**: [中文](../../diagrams/FLOWCHART.md) · [한국어](../../ko/diagrams/FLOWCHART.md) · [Русский](../../ru/diagrams/FLOWCHART.md) · [Deutsch](../../de/diagrams/FLOWCHART.md) · [Français](../../fr/diagrams/FLOWCHART.md) · [Español](../../es/diagrams/FLOWCHART.md) · [Português](../../pt/diagrams/FLOWCHART.md) · [हिन्दी](../../hi/diagrams/FLOWCHART.md) · [العربية](../../ar/diagrams/FLOWCHART.md) · [বাংলা](../../bn/diagrams/FLOWCHART.md) · [Bahasa Indonesia](../../id/diagrams/FLOWCHART.md) · [日本語](../../ja/diagrams/FLOWCHART.md)

## 1. Service Appointment Flow

```mermaid
flowchart TD
    A["User browses service items"] --> B["Select store/technician/time"]
    B --> C["Fill in remarks"]
    C --> D{"Use a coupon?"}
    D -->|"Yes"| E["Coupon deducts amount"]
    D -->|"No"| F["Order at full price"]
    E --> G["Order pricing (no consumption)<br/>PriceCalculator pure calculation<br/>coupon fixed/percent + session card times<br/>min_amount based on original price"]
    F --> G
    G --> H["Read service agreement"]
    H --> I["Submit order"]
    I --> J{"Redis lock technician<br/>SETNX 3 minutes"}
    J -->|"Lock acquired"| K["Create order pending"]
    J -->|"Already locked"| L["Prompt: technician busy"]
    K --> M{"Payable amount?"}
    M -->|"Zero yuan"| N["FREE pass-through<br/>transaction_id = 'FREE'+payment no.<br/>order → paid"]
    M -->|"Balance payment"| B1["Deduct wallet balance<br/>wallet_txn recorded<br/>order → paid"]
    M -->|"Amount > 0"| O{"Payment method"}
    O -->|"WeChat"| OW["Call WeChat Pay<br/>pay_lock prevents concurrent duplicate payment"]
    O -->|"Balance"| B1
    OW --> P{"Payment result"}
    B1 --> S
    P -->|"Success"| Q["Payment success callback consumed<br/>markOrderPaid single consumption point<br/>atomic coupon/session-card deduction<br/>order → paid"]
    P -->|"Failed/cancelled"| R["Order stays pending<br/>auto-cancelled after 15 minutes"]
    N --> S["Technician confirms service start"]
    Q --> S
    S --> T["Order → serving"]
    T --> U["Service completed"]
    U --> V["Technician scans QR to verify"]
    V --> W["Order → completed"]
    W --> X["User review (text + images)"]
    X --> Y["Order → reviewed ✅"]

    style A fill:#e3f2fd,stroke:#1565c0,color:#333
    style Y fill:#c8e6c9,stroke:#2e7d32,color:#333
    style L fill:#ffcdd2,stroke:#c62828,color:#333
    style R fill:#fff9c4,stroke:#f9a825,color:#333
    style N fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 2. Payment and Refund Flow

```mermaid
flowchart TD
    subgraph 支付流程["Forward payment flow"]
        P1["Create payment record"] --> P2["WeChat unified order<br/>pay_lock prevents concurrency<br/>out_trade_no = order_no idempotent"]
        P2 --> P3["Frontend initiates payment<br/>select payment method"]
        P3 -->|"Balance"| PB["Deduct wallet balance<br/>wallet_txn recorded<br/>idempotent, deducted only once"]
        P3 -->|"WeChat"| P4["WeChat callback notify"]
        P4 --> P5["Signature verified"]
        PB --> P6["markOrderPaid idempotent<br/>coupon/session card consumed only here"]
        P5 --> P6
        P6 --> P7["Order → paid<br/>notify user + technician"]
    end

    subgraph 退款流程["Refund flow"]
        R1["User applies for refund<br/>refund_lock prevents concurrency"] --> R2{"Refund rule check"}
        R2 -->|"Ordered ≤15min or >6h before start"| R3["Refund 100%"]
        R2 -->|"≤6h before start"| R4["Refund 90%"]
        R2 -->|"Started but not confirmed"| R5["Refund 80%"]
        R2 -->|"After service confirmed"| R6["No refund"]
        R3 --> R7["Order → refunding"]
        R4 --> R7
        R5 --> R7
        R7 --> R8["Two-level approval<br/>store manager → finance"]
        R8 --> R9["Two-stage refund<br/>refund record inside transaction<br/>WeChat refund IO outside transaction"]
        R9 -->|"WeChat failed"| R10["Roll back order to PAID<br/>refund retryable"]
        R9 -->|"Refund success"| R11["Order → refunded<br/>WeChat original route / balance credit<br/>coupon returned + points clawed back"]
    end

    style P6 fill:#c8e6c9,stroke:#2e7d32,color:#333
    style R6 fill:#ffcdd2,stroke:#c62828,color:#333
    style R11 fill:#c8e6c9,stroke:#2e7d32,color:#333
    style R10 fill:#fff9c4,stroke:#f9a825,color:#333
```

## 3. Technician Withdrawal Flow

```mermaid
flowchart TD
    A["Technician applies for withdrawal"] --> B{"poster-php<br/>operation verification"}
    B -->|"Verified"| C{"Withdrawal conditions"}
    B -->|"Verification failed"| X["Reject operation"]
    C -->|"20th of month"| D["Create withdrawal record"]
    C -->|"Not a withdrawal day"| Y["Prompt: withdrawal available on the 20th"]
    D --> E["Admin review"]
    E --> F{"Review result"}
    F -->|"Approved"| G["Execute withdrawal"]
    F -->|"Rejected"| H["Return application<br/>with rejection reason"]
    G --> I["WeChat enterprise payment to balance"]
    I --> J["T+1 arrival"]
    J --> K["Generate financial record<br/>log income/expense"]

    style K fill:#c8e6c9,stroke:#2e7d32,color:#333
    style X fill:#ffcdd2,stroke:#c62828,color:#333
    style Y fill:#fff9c4,stroke:#f9a825,color:#333
    style H fill:#ffcdd2,stroke:#c62828,color:#333
```

## 4. Identity Switching Flow

```mermaid
flowchart TD
    A["Current identity: customer"] --> B["Tap: switch to technician"]
    B --> C{"Technician profile status"}
    C -->|"approved"| D["active_role = technician<br/>page switches to technician workbench"]
    C -->|"Not onboarded / under review"| E["Guide to onboarding application"]
    E --> F["Fill in technician info<br/>name/gender/phone<br/>ID card/photo"]
    F --> G["Submit for review"]
    G --> H{"Admin review"}
    H -->|"Approved"| D
    H -->|"Rejected"| I["Modify and resubmit"]

    J["Current identity: technician"] --> K["Tap: switch to customer"]
    K --> L["active_role = customer<br/>page switches to customer UI"]

    style D fill:#c8e6c9,stroke:#2e7d32,color:#333
    style L fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 5. Wallet Top-Up / Gift Card Credit Flow

```mermaid
flowchart TD
    A["User tops up / redeems gift card"] --> B{"Credit method"}
    B -->|"WeChat top-up"| C["WeChat payment callback<br/>wallet_recharge record<br/>idempotent credit"]
    B -->|"Gift card redemption"| D["GiftCard redeem verifies card secret<br/>amount credited to wallet balance"]
    C --> E["Wallet balance increases<br/>wallet_txn recorded"]
    D --> E
    E --> F["Pay orders with balance<br/>or refunds credit back to balance"]
    F --> G["Credit/refund complete ✅"]

    style G fill:#c8e6c9,stroke:#2e7d32,color:#333
```
