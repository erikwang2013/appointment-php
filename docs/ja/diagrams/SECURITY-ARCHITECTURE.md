# セキュリティアーキテクチャ図
> **Languages**: [中文](../../diagrams/SECURITY-ARCHITECTURE.md) · [English](../../en/diagrams/SECURITY-ARCHITECTURE.md) · [한국어](../../ko/diagrams/SECURITY-ARCHITECTURE.md) · [Русский](../../ru/diagrams/SECURITY-ARCHITECTURE.md) · [Deutsch](../../de/diagrams/SECURITY-ARCHITECTURE.md) · [Français](../../fr/diagrams/SECURITY-ARCHITECTURE.md) · [Español](../../es/diagrams/SECURITY-ARCHITECTURE.md) · [Português](../../pt/diagrams/SECURITY-ARCHITECTURE.md) · [हिन्दी](../../hi/diagrams/SECURITY-ARCHITECTURE.md) · [العربية](../../ar/diagrams/SECURITY-ARCHITECTURE.md) · [বাংলা](../../bn/diagrams/SECURITY-ARCHITECTURE.md) · [Bahasa Indonesia](../../id/diagrams/SECURITY-ARCHITECTURE.md)

## 1. 多層防御体系

```mermaid
graph TB
    subgraph 边界防护["第1層：境界防御"]
        WAF["WAF / Nginx<br/>セキュリティレスポンスヘッダー<br/>機密ファイル保護<br/>TLS 1.3"]
    end

    subgraph 接入防护["第2層：接続防御"]
        CORS["Cors ミドルウェア<br/>CORS_ALLOW_ORIGIN ホワイトリスト<br/>* エコー · 未設定時は同一オリジンのみ<br/>6個のセキュリティレスポンスヘッダー<br/>OPTIONS プリフライト"]
    end

    subgraph 攻击检测["第3層：攻撃検知"]
        SEC["Security ミドルウェア<br/>erikwang2013/security-php<br/>31種の攻撃検知器<br/>XSS / SQL注入 / CSRF<br/>パストラバーサル / ファイルインクルード<br/>CSRF Origin 検知(block)"]
        BLOCK["自動封禁<br/>60sで5回の攻撃<br/>→ IPブラックリスト 15min"]
    end

    subgraph 流量控制["第4層：トラフィック制御"]
        RL["RateLimit ミドルウェア<br/>Redis スライディングウィンドウ + Lua 原子的<br/>デフォルト: 60回/min/IP<br/>ログイン: 10回/min<br/>登録: 5回/min<br/>验证码: 60sで1回/携帯番号"]
    end

    subgraph 身份认证["第5層：身份認証"]
        AUTH["Auth ミドルウェア<br/>JWT Bearer Token (7日)<br/>JWT_SECRET_KEY 強制設定<br/>欠落/公開デフォルト値は起動拒否<br/>パスワード bcrypt ハッシュ<br/>Token リフレッシュ + ブラックリスト<br/>ログインロック: 5回失敗→15min<br/>並行制限: 最大3個のToken"]
        TECH_AUTH["TechnicianAuth<br/>スタッフプロフィール検証<br/>approved 状態チェック"]
        ADMIN_AUTH["AdminAuth<br/>Admin端JWT認証<br/>Tokenブラックリスト"]
    end

    subgraph 权限控制["第6層：権限制御"]
        RBAC["AdminPermission<br/>RBAC ロール権限検証<br/>Redis 60s キャッシュ<br/>ユーザー→ロール→権限"]
        POSTER["Poster検証<br/>erikwang2013/poster-php<br/>削除/審査/出金<br/>機密操作のランダム検証"]
    end

    subgraph 数据安全["第7層：データセキュリティ"]
        ENC_API["API層暗号化<br/>erikwang2013/encryption<br/>機密フィールドの加復号"]
        ENC_DB["DB層暗号化<br/>erikwang2013/encryptable<br/>Model trait 自動加復号<br/>real_name/id_card などのみ暗号化<br/>phone/wx_openid は平文保存必須<br/>(ログイン/重複チェックは平文クエリ依存)"]
        HASHID["ID加復号<br/>erikwang2013/hashids<br/>対外的に実IDを秘匿<br/>再帰エンコード/デコード"]
        SLOG["セキュリティログ<br/>M3 例外は一括脱敏<br/>汎用文案 + Log::error<br/>機密データをログに残さない<br/>OperationLog 8端来源"]
    end

    subgraph 管理端防护["第8層：管理端防御"]
        EXCEL["エクスポート防御<br/>safeCellValue()<br/>= + - @ / Tab/CR 先頭<br/>プレフィックス ' で式注入をエスケープ"]
        UPLOAD["アップロード検証<br/>finfo magic bytes<br/>MIME と拡張子の不一致<br/>→ 422 拒否"]
        INSTALL["インストールロック<br/>インストール済み(installed=1<br/>または管理者が存在)<br/>→ 404 でインストールウィザード無効化"]
    end

    请求["HTTP Request"] --> WAF
    WAF --> CORS
    CORS --> SEC
    SEC -->|"通過"| RL
    SEC -->|"攻撃を検知"| BLOCK
    BLOCK -.->|"拒否"| 拒绝["HTTP 403/429<br/>攻撃ログを記録"]
    RL -->|"通過"| AUTH
    RL -->|"超過"| 限流拒绝["HTTP 429<br/>Retry-After"]
    AUTH --> TECH_AUTH
    AUTH --> ADMIN_AUTH
    TECH_AUTH --> RBAC
    ADMIN_AUTH --> RBAC
    RBAC --> POSTER
    POSTER --> ENC_API
    ENC_API --> ENC_DB
    ENC_DB --> HASHID
    HASHID --> SLOG
    SLOG --> EXCEL
    EXCEL --> UPLOAD
    UPLOAD --> INSTALL
    INSTALL --> 响应["HTTP Response<br/>データは暗号化+エンコード済み"]

    classDef layer1 fill:#e3f2fd,stroke:#1565c0,stroke-width:2px,color:#01579b
    classDef layer2 fill:#bbdefb,stroke:#1976d2,stroke-width:2px,color:#01579b
    classDef layer3 fill:#ffcdd2,stroke:#c62828,stroke-width:2px,color:#b71c1c
    classDef layer4 fill:#fff9c4,stroke:#f9a825,stroke-width:2px,color:#f57f17
    classDef layer5 fill:#c8e6c9,stroke:#2e7d32,stroke-width:2px,color:#1b5e20
    classDef layer6 fill:#e1bee7,stroke:#7b1fa2,stroke-width:2px,color:#4a148c
    classDef layer7 fill:#d7ccc8,stroke:#5d4037,stroke-width:2px,color:#3e2723
    classDef layer8 fill:#cfd8dc,stroke:#37474f,stroke-width:2px,color:#263238
    classDef reject fill:#ff5252,stroke:#b71c1c,stroke-width:2px,color:#fff

    class WAF layer1
    class CORS layer2
    class SEC,BLOCK layer3
    class RL layer4
    class AUTH,TECH_AUTH,ADMIN_AUTH layer5
    class RBAC,POSTER layer6
    class ENC_API,ENC_DB,HASHID,SLOG layer7
    class EXCEL,UPLOAD,INSTALL layer8
    class 拒绝,限流拒绝 reject
```

## 2. セキュリティコンポーネントマトリクス

```mermaid
graph LR
    subgraph 组件["セキュリティコンポーネント"]
        C1["security-php<br/>━━━━━━━━<br/>31種の攻撃検知<br/>XSS/SQL注入/CSRF<br/>パストラバーサル/ファイルインクルード<br/>CSRF Origin検知"]
        C2["encryption<br/>━━━━━━━━<br/>AES-256-CBC<br/>API層加復号<br/>鍵ローテーション対応"]
        C3["encryptable<br/>━━━━━━━━<br/>DBフィールド自動加復号<br/>real_name/id_card などのみ暗号化<br/>phone/wx_openid は平文保存<br/>VARCHAR(500) 暗号化膨張対応"]
        C4["hashids<br/>━━━━━━━━<br/>IDエンコード/デコード<br/>関連データを再帰処理<br/>対外的に実IDを秘匿"]
        C5["jwt-webman<br/>━━━━━━━━<br/>Bearer Token<br/>JWT_SECRET_KEY 強制設定<br/>欠落/デフォルト値は起動拒否<br/>7日+リフレッシュ+ブラックリスト<br/>並行≤3個"]
        C6["poster-php<br/>━━━━━━━━<br/>操作前のランダム検証<br/>削除/審査/出金<br/>誤操作防止"]
        C7["snowflake-php<br/>━━━━━━━━<br/>BIGINT分散ID<br/>非自動採番で走査防止<br/>グローバル一意"]
    end

    subgraph 攻击面["防御する攻撃面"]
        A1["注入攻撃<br/>SQL/コマンド/LDAP"]
        A2["XSS/CSRF<br/>クロスサイトスクリプト/リクエスト偽造"]
        A3["パストラバーサル<br/>ディレクトリトラバーサル/ファイルインクルード"]
        A4["ブルートフォース<br/>ログイン総当たり/验证码総当たり"]
        A5["データ漏えい<br/>ID走査/機密フィールド"]
        A6["越権操作<br/>水平/垂直越権"]
        A7["並行悪用<br/>Token乱発/API連打"]
    end

    C1 -.->|防御| A1
    C1 -.->|防御| A2
    C1 -.->|防御| A3
    C2 -.->|防御| A5
    C3 -.->|防御| A5
    C4 -.->|防御| A5
    C5 -.->|防御| A4
    C5 -.->|防御| A7
    C6 -.->|防御| A6
    C7 -.->|防御| A5

    classDef comp fill:#e8eaf6,stroke:#3949ab,stroke-width:2px,color:#1a237e
    classDef attack fill:#ffebee,stroke:#c62828,stroke-width:1px,color:#b71c1c

    class C1,C2,C3,C4,C5,C6,C7 comp
    class A1,A2,A3,A4,A5,A6,A7 attack
```

## 3. 認証と認可フロー

```mermaid
flowchart TD
    A["クライアントリクエスト"] --> B{"Tokenあり?"}
    B -->|"なし"| C["401 を返す<br/>ログインを促す"]
    B -->|"あり"| D["JWT Tokenを解析"]
    D --> E{"Token有効?"}
    E -->|"期限切れ"| F{"Refresh Token?"}
    F -->|"あり"| G["Tokenをリフレッシュ<br/>旧Tokenをブラックリストへ"]
    F -->|"なし"| C
    G --> H["新Tokenを返す"]
    E -->|"有効"| I{"ブラックリストチェック"}
    I -->|"ブラックリスト入り"| C
    I -->|"正常"| J["ユーザー情報を照会"]
    J --> K{"ユーザーが存在し有効?"}
    K -->|"否"| L["403 を返す<br/>アカウントが無効化"]
    K -->|"是"| M{"ログイン失敗回数?"}
    M -->|"≥5回/15min"| N["429 を返す<br/>アカウントがロック"]
    M -->|"正常"| O{"並行Token数?"}
    O -->|">3個"| P["旧Tokenを自動失効<br/>ブラックリストへ"]
    O -->|"≤3個"| Q{"スタッフ身份が必要?"}
    Q -->|"是"| R{"スタッフプロフィールapproved?"}
    R -->|"否"| S["403 を返す<br/>スタッフでないか審査中"]
    R -->|"是"| T{"RBACが必要?"}
    Q -->|"否"| T
    T -->|"是"| U{"権限検証"}
    U -->|"権限なし"| V["403 を返す<br/>操作権限なし"]
    U -->|"権限あり"| W["業務ロジックを実行"]
    T -->|"否"| W
    W --> X["レスポンスを返す<br/>IDはエンコード済み<br/>機密データは暗号化済み"]

    style C fill:#ffcdd2,stroke:#c62828,color:#333
    style L fill:#ffcdd2,stroke:#c62828,color:#333
    style N fill:#ffcdd2,stroke:#c62828,color:#333
    style S fill:#ffcdd2,stroke:#c62828,color:#333
    style V fill:#ffcdd2,stroke:#c62828,color:#333
    style W fill:#c8e6c9,stroke:#2e7d32,color:#333
    style X fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 4. データセキュリティの流れ

```mermaid
flowchart LR
    subgraph 输入["ユーザー入力"]
        I1["平文の携帯番号"]
        I2["平文の身分証"]
        I3["平文のOpenID"]
        I4["平文の氏名"]
    end

    subgraph API加密["API層 (encryption)"]
        E1["encrypt(id_card)<br/>→ ciphertext"]
        E2["encrypt(real_name)<br/>→ ciphertext"]
    end

    subgraph DB存储["DB層ストレージ"]
        D1["erik_user.phone<br/>平文保存<br/>ログイン/重複チェックは平文クエリ依存"]
        D2["erik_technician_profile<br/>.id_card VARCHAR(500)<br/>encryptable 暗号化"]
        D3["erik_user.wx_openid<br/>平文保存"]
        D4["erik_user.real_name<br/>encryptable 暗号化"]
    end

    subgraph ID处理["ID処理 (hashids + snowflake)"]
        H1["Snowflake生成<br/>1860000000000001"]
        H2["Hashidsエンコード<br/>→ 'Kx9mP2vR'"]
        H3["APIレスポンス<br/>id: 'Kx9mP2vR'"]
    end

    subgraph 输出["対外出力"]
        O1["IDはエンコード済み<br/>走査不可"]
        O2["機密フィールドは脱敏済み<br/>ログに平文を含めない"]
        O3["レスポンスヘッダーにセキュリティポリシー<br/>CSP/CORS/HSTS"]
    end

    I1 --> D1
    I2 --> E1 --> D2
    I3 --> D3
    I4 --> E2 --> D4
    D1 --> H1 --> H2 --> H3
    H3 --> O1
    D1 --> O2
    O1 --> O3

    classDef input fill:#e3f2fd,stroke:#1565c0,color:#333
    classDef encrypt fill:#fff3e0,stroke:#f57c00,color:#333
    classDef db fill:#fce4ec,stroke:#c62828,color:#333
    classDef id fill:#e8f5e9,stroke:#2e7d32,color:#333
    classDef output fill:#f3e5f5,stroke:#7b1fa2,color:#333

    class I1,I2,I3,I4 input
    class E1,E2 encrypt
    class D1,D2,D3,D4 db
    class H1,H2,H3 id
    class O1,O2,O3 output
```
