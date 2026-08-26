# Diagrama de flujo del negocio principal

## 1. Flujo de reserva de servicios

```mermaid
flowchart TD
    A["El usuario explora los servicios"] --> B["Elegir tienda/técnico/hora"]
    B --> C["Rellenar notas"]
    C --> D{"¿Usar cupón?"}
    D -->|"Sí"| E["Cupón descuenta el importe"]
    D -->|"No"| F["Pedido a precio original"]
    E --> G["Calcular precio del pedido (sin consumir)<br/>PriceCalculator cálculo puro<br/>Cupón fixed/percent + tarjeta por uso times<br/>min_amount basado en el precio original"]
    F --> G
    G --> H["Leer el acuerdo de servicio"]
    H --> I["Enviar pedido"]
    I --> J{"Bloqueo del técnico en Redis<br/>SETNX 3 minutos"}
    J -->|"Bloqueo correcto"| K["Crear pedido pending"]
    J -->|"Ya bloqueado"| L["Avisar de que el técnico está ocupado"]
    K --> M{"¿Importe a pagar?"}
    M -->|"Cero"| N["Paso directo FREE<br/>transaction_id = 'FREE'+número de pago<br/>Pedido → paid"]
    M -->|"Pago con saldo"| B1["Descuento del saldo de la billetera<br/>Registro en wallet_txn<br/>Pedido → paid"]
    M -->|"Importe > 0"| O{"Método de pago"}
    O -->|"WeChat"| OW["Llamar a WeChat Pay<br/>pay_lock evita pagos duplicados concurrentes"]
    O -->|"Saldo"| B1
    OW --> P{"Resultado del pago"}
    B1 --> S
    P -->|"Correcto"| Q["Consumo en la devolución de llamada de pago<br/>markOrderPaid punto de consumo único<br/>Descuento atómico de cupón/tarjeta por uso<br/>Pedido → paid"]
    P -->|"Fallo/cancelado"| R["El pedido permanece pending<br/>Cancelación automática a los 15 minutos"]
    N --> S["El técnico confirma el inicio del servicio"]
    Q --> S
    S --> T["Pedido → serving"]
    T --> U["Servicio completado"]
    U --> V["El técnico verifica con escaneo"]
    V --> W["Pedido → completed"]
    W --> X["Evaluación del usuario (texto + imágenes)"]
    X --> Y["Pedido → reviewed ✅"]

    style A fill:#e3f2fd,stroke:#1565c0,color:#333
    style Y fill:#c8e6c9,stroke:#2e7d32,color:#333
    style L fill:#ffcdd2,stroke:#c62828,color:#333
    style R fill:#fff9c4,stroke:#f9a825,color:#333
    style N fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 2. Flujo de pago y reembolso

```mermaid
flowchart TD
    subgraph 支付流程["Flujo de pago directo"]
        P1["Crear registro de pago"] --> P2["Pedido unificado WeChat<br/>pay_lock evita concurrencia<br/>out_trade_no = order_no idempotente"]
        P2 --> P3["El frontend inicia el pago<br/>Elegir método de pago"]
        P3 -->|"Saldo"| PB["Descuento del saldo de la billetera<br/>Registro en wallet_txn<br/>Idempotente, solo se descuenta una vez"]
        P3 -->|"WeChat"| P4["Devolución de llamada de WeChat notify"]
        P4 --> P5["Verificación de firma correcta"]
        PB --> P6["markOrderPaid idempotente<br/>Cupón/tarjeta por uso solo se consume aquí"]
        P5 --> P6
        P6 --> P7["Pedido → paid<br/>Notificar al usuario + técnico"]
    end

    subgraph 退款流程["Flujo de reembolso"]
        R1["El usuario solicita el reembolso<br/>refund_lock evita concurrencia"] --> R2{"Determinación de la regla de reembolso"}
        R2 -->|"Pedido ≤15 min o a >6 h del inicio"| R3["Reembolso 100%"]
        R2 -->|"A ≤6 h del inicio"| R4["Reembolso 90%"]
        R2 -->|"Empezado sin confirmar"| R5["Reembolso 80%"]
        R2 -->|"Tras confirmar el servicio"| R6["Sin reembolso"]
        R3 --> R7["Pedido → refunding"]
        R4 --> R7
        R5 --> R7
        R7 --> R8["Aprobación de dos niveles<br/>Gerente de tienda → Finanzas"]
        R8 --> R9["Reembolso en dos fases<br/>Registro de reembolso dentro de la transacción<br/>IO del reembolso WeChat fuera de la transacción"]
        R9 -->|"Fallo de WeChat"| R10["Reversión del pedido a PAID<br/>Reembolso reintentable"]
        R9 -->|"Reembolso correcto"| R11["Pedido → refunded<br/>Devolución original de WeChat / reintegro al saldo<br/>Devolución del cupón + descuento de puntos"]
    end

    style P6 fill:#c8e6c9,stroke:#2e7d32,color:#333
    style R6 fill:#ffcdd2,stroke:#c62828,color:#333
    style R11 fill:#c8e6c9,stroke:#2e7d32,color:#333
    style R10 fill:#fff9c4,stroke:#f9a825,color:#333
```

## 3. Flujo de retiro del técnico

```mermaid
flowchart TD
    A["El técnico solicita el retiro"] --> B{"poster-php<br/>Verificación de operación"}
    B -->|"Verificación correcta"| C{"Comprobación de condiciones de retiro"}
    B -->|"Verificación fallida"| X["Rechazar la operación"]
    C -->|"Día 20 de cada mes"| D["Crear registro de retiro"]
    C -->|"Fuera del día de retiro"| Y["Avisar de que se puede retirar el día 20 de cada mes"]
    D --> E["Auditoría del panel"]
    E --> F{"Resultado de la auditoría"}
    F -->|"Aprobado"| G["Ejecutar el retiro"]
    F -->|"Rechazado"| H["Devolver la solicitud<br/>Con motivo del rechazo"]
    G --> I["Pago de empresa WeChat a la billetera"]
    I --> J["Llegada T+1"]
    J --> K["Generar historial financiero<br/>Registrar ingresos y gastos"]

    style K fill:#c8e6c9,stroke:#2e7d32,color:#333
    style X fill:#ffcdd2,stroke:#c62828,color:#333
    style Y fill:#fff9c4,stroke:#f9a825,color:#333
    style H fill:#ffcdd2,stroke:#c62828,color:#333
```

## 4. Flujo de cambio de identidad

```mermaid
flowchart TD
    A["Identidad actual: cliente"] --> B["Hacer clic para cambiar a técnico"]
    B --> C{"Estado del expediente del técnico"}
    C -->|"approved"| D["active_role = technician<br/>La página cambia al puesto de trabajo del técnico"]
    C -->|"No incorporado/en auditoría"| E["Guiar hacia la solicitud de incorporación"]
    E --> F["Rellenar la información del técnico<br/>Nombre/sexo/teléfono<br/>DNI/fotos"]
    F --> G["Enviar para auditoría"]
    G --> H{"Auditoría del panel"}
    H -->|"Aprobado"| D
    H -->|"Rechazado"| I["Modificar y reenviar"]

    J["Identidad actual: técnico"] --> K["Hacer clic para cambiar a cliente"]
    K --> L["active_role = customer<br/>La página cambia a la interfaz de cliente"]

    style D fill:#c8e6c9,stroke:#2e7d32,color:#333
    style L fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 5. Flujo de recarga de la billetera / abono de tarjeta regalo

```mermaid
flowchart TD
    A["El usuario recarga / canjea una tarjeta regalo"] --> B{"Método de abono"}
    B -->|"Recarga WeChat"| C["Devolución de llamada de WeChat Pay<br/>Registro wallet_recharge<br/>Abono idempotente"]
    B -->|"Canje de tarjeta regalo"| D["GiftCard redeem verifica el código<br/>El importe se abona al saldo de la billetera"]
    C --> E["Aumento del saldo de la billetera<br/>Registro en wallet_txn"]
    D --> E
    E --> F["Pago de pedidos con saldo<br/>o reintegro de reembolso al saldo"]
    F --> G["Abono/reintegro completado ✅"]

    style G fill:#c8e6c9,stroke:#2e7d32,color:#333
```
