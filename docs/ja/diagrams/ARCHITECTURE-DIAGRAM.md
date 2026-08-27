# システムアーキテクチャ図
> **Languages**: [中文](../../diagrams/ARCHITECTURE-DIAGRAM.md) · [English](../../en/diagrams/ARCHITECTURE-DIAGRAM.md) · [한국어](../../ko/diagrams/ARCHITECTURE-DIAGRAM.md) · [Русский](../../ru/diagrams/ARCHITECTURE-DIAGRAM.md) · [Deutsch](../../de/diagrams/ARCHITECTURE-DIAGRAM.md) · [Français](../../fr/diagrams/ARCHITECTURE-DIAGRAM.md) · [Español](../../es/diagrams/ARCHITECTURE-DIAGRAM.md) · [Português](../../pt/diagrams/ARCHITECTURE-DIAGRAM.md) · [हिन्दी](../../hi/diagrams/ARCHITECTURE-DIAGRAM.md) · [العربية](../../ar/diagrams/ARCHITECTURE-DIAGRAM.md) · [বাংলা](../../bn/diagrams/ARCHITECTURE-DIAGRAM.md) · [Bahasa Indonesia](../../id/diagrams/ARCHITECTURE-DIAGRAM.md)

```mermaid
graph TB
    subgraph 用户终端层["ユーザー端末層"]
        WX["微信小程序<br/>apps/wechat/<br/>ネイティブ WXML/WXSS/JS"]
        APP["Flutter APP<br/>apps/flutter/<br/>iOS + Android<br/>GetX + Dio"]
    end

    subgraph 业务服务层["業務サービス層 :8787"]
        direction TB
        MW1["ミドルウェアチェーン<br/>Cors → Security → RateLimit"]
        subgraph API模块["API ルートモジュール"]
            PUB["公開API<br/>api/<br/>ログイン/登録/验证码"]
            USER["ユーザーモジュール<br/>user/<br/>プロフィール/住所/お気に入り"]
            TECH["スタッフモジュール<br/>technician/<br/>排班/ワークベンチ/核销/収益/出金"]
            SVC["サービスモジュール<br/>service/<br/>カテゴリ/メニュー/検索"]
            ORD["注文モジュール<br/>order/<br/>カート/注文/支払い/返金/核销"]
            MKT["マーケティングモジュール<br/>marketing/<br/>クーポン/会員カード(回数券)/ポイント<br/>礼品卡/会員特典"]
            WALLET["ウォレットモジュール<br/>wallet/<br/>残高/チャージ/取引明細<br/>残高支払い"]
            CTN["コンテンツモジュール<br/>content/<br/>カルーセル画像/お知らせ/通知"]
            LBS["LBSモジュール<br/>lbs/<br/>都市/近くの店舗"]
            CACHE["Redis リストキャッシュ<br/>svc:* プレフィックス setex 300s<br/>カテゴリ/メニュー/製品/スタッフ/コンテンツ<br/>カード項目/マーケティングリストAPI<br/>admin 書き込みパス clearSvcCache() で失効"]
            RES["レスポンス契約<br/>success/paginate code=0<br/>エラーコードは 0 以外<br/>小程序との約定に一致"]
        end
    end

    subgraph 管理后台层["管理バックエンド層 :8787"]
        MW2["ミドルウェアチェーン<br/>Cors → Security → RateLimit → AdminAuth → RBAC → OperationLog"]
        ADMIN_API["管理API<br/>admin/controller/<br/>ダッシュボード/ユーザー/スタッフ/店舗/サービス<br/>注文/クーポン/会員カード/出金/評価<br/>レポート/財務/コンテンツ/設定"]
        FLUTTER_WEB["Flutter Web フロントエンド<br/>admin/apps/flutter/<br/>PC管理バックエンド画面"]
        MODEL["モデル共有<br/>admin/app/model<br/>39 個の symlink<br/>→ service/app/model 同一実装"]
    end

    subgraph 数据层["データ層"]
        MySQL[("MySQL 8.0<br/>55+ テーブル · appointment_ プレフィックス<br/>BIGINT Snowflake 主キー")]
        Redis[("Redis<br/>キャッシュ/レート制限/Session<br/>キュー/スタッフロック<br/>svc:* リストキャッシュ")]
        ES[("Elasticsearch<br/>全文検索<br/>webman-scout 自動同期")]
    end

    subgraph 外部服务["サードパーティサービス"]
        WXPAY["微信支払い<br/>統一下単/返金/出金"]
        SMS["短信サービス<br/>阿里云/腾讯云"]
        MAP["地図サービス<br/>高德/腾讯<br/>逆ジオコーディング/ナビ"]
        OSS["オブジェクトストレージ<br/>ローカル/OSS/COS/CDN"]
        SUBMSG["微信購読メッセージ<br/>WechatTemplateMessageService<br/>sendSubscribeMessage<br/>注文イベント3シナリオ"]
    end

    subgraph 安全组件["セキュリティコンポーネント層"]
        SEC["Security-PHP<br/>31種の攻撃検知"]
        JWT["JWT認証<br/>7日間有効+ブラックリスト"]
        ENC["二層暗号化<br/>API層+DB層"]
        POSTER["操作検証<br/>機密操作のランダム検証"]
    end

    WX -->|"HTTP API<br/>機能同等"| MW1
    APP -->|"HTTP API<br/>機能同等"| MW1
    MW1 --> API模块

    FLUTTER_WEB -->|"HTTP API"| MW2
    MW2 --> ADMIN_API

    API模块 --> MySQL
    API模块 --> Redis
    API模块 --> ES
    ADMIN_API --> MySQL
    ADMIN_API --> Redis
    ADMIN_API --> ES

    安全组件 -.->|防御| 业务服务层
    安全组件 -.->|防御| 管理后台层

    API模块 -.->|呼び出し| 外部服务
    ADMIN_API -.->|呼び出し| 外部服务

    classDef terminal fill:#e1f5fe,stroke:#0288d1,stroke-width:2px,color:#01579b
    classDef service fill:#fff3e0,stroke:#f57c00,stroke-width:2px,color:#e65100
    classDef admin fill:#e8f5e9,stroke:#388e3c,stroke-width:2px,color:#1b5e20
    classDef data fill:#fce4ec,stroke:#c62828,stroke-width:2px,color:#880e4f
    classDef external fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px,color:#4a148c
    classDef security fill:#fff8e1,stroke:#f9a825,stroke-width:2px,color:#f57f17

    class WX,APP terminal
    class MW1,API模块,PUB,USER,TECH,SVC,ORD,MKT,WALLET,CTN,LBS,CACHE,RES service
    class MW2,ADMIN_API,FLUTTER_WEB,MODEL admin
    class MySQL,Redis,ES data
    class WXPAY,SMS,MAP,OSS,SUBMSG external
    class SEC,JWT,ENC,POSTER security
```
