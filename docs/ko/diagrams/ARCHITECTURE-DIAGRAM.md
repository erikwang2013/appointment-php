# 시스템 아키텍처 다이어그램
> **Languages**: [中文](../../diagrams/ARCHITECTURE-DIAGRAM.md) · [English](../../en/diagrams/ARCHITECTURE-DIAGRAM.md) · [Русский](../../ru/diagrams/ARCHITECTURE-DIAGRAM.md) · [Deutsch](../../de/diagrams/ARCHITECTURE-DIAGRAM.md) · [Français](../../fr/diagrams/ARCHITECTURE-DIAGRAM.md) · [Español](../../es/diagrams/ARCHITECTURE-DIAGRAM.md) · [Português](../../pt/diagrams/ARCHITECTURE-DIAGRAM.md) · [हिन्दी](../../hi/diagrams/ARCHITECTURE-DIAGRAM.md) · [العربية](../../ar/diagrams/ARCHITECTURE-DIAGRAM.md) · [বাংলা](../../bn/diagrams/ARCHITECTURE-DIAGRAM.md) · [Bahasa Indonesia](../../id/diagrams/ARCHITECTURE-DIAGRAM.md) · [日本語](../../ja/diagrams/ARCHITECTURE-DIAGRAM.md)

```mermaid
graph TB
    subgraph 사용자_터미널_계층["사용자 터미널 계층"]
        WX["위챗 미니프로그램<br/>apps/wechat/<br/>네이티브 WXML/WXSS/JS"]
        APP["Flutter APP<br/>apps/flutter/<br/>iOS + Android<br/>GetX + Dio"]
    end

    subgraph 비즈니스_서비스_계층["비즈니스 서비스 계층 :8787"]
        direction TB
        MW1["미들웨어 체인<br/>Cors → Security → RateLimit"]
        subgraph API_모듈["API 라우트 모듈"]
            PUB["공개 API<br/>api/<br/>로그인/회원가입/인증코드"]
            USER["사용자 모듈<br/>user/<br/>프로필/주소/즐겨찾기"]
            TECH["기술자 모듈<br/>technician/<br/>배차/작업대/핵소/수익/출금"]
            SVC["서비스 모듈<br/>service/<br/>분류/항목/검색"]
            ORD["주문 모듈<br/>order/<br/>장바구니/주문/결제/환불/핵소"]
            MKT["마케팅 모듈<br/>marketing/<br/>쿠폰/멤버십 카드(횟수권)/포인트<br/>기프트 카드/회원 혜택"]
            WALLET["지갑 모듈<br/>wallet/<br/>잔액/충전/거래 내역<br/>잔액 결제"]
            CTN["콘텐츠 모듈<br/>content/<br/>캐러셀/공지/알림"]
            LBS["LBS 모듈<br/>lbs/<br/>도시/주변 매장"]
            CACHE["Redis 목록 캐시<br/>svc:* 접두사 setex 300s<br/>분류/항목/상품/기술자/콘텐츠<br/>카드 항목/마케팅 목록 인터페이스<br/>admin 쓰기 경로 clearSvcCache() 무효화"]
            RES["응답 계약<br/>success/paginate code=0<br/>오류 코드 0 아님<br/>미니프로그램 규약과 일치"]
        end
    end

    subgraph 관리_백엔드_계층["관리 백엔드 계층 :8787"]
        MW2["미들웨어 체인<br/>Cors → Security → RateLimit → AdminAuth → RBAC → OperationLog"]
        ADMIN_API["관리 API<br/>admin/controller/<br/>대시보드/사용자/기술자/매장/서비스<br/>주문/쿠폰/멤버십 카드/출금/평가<br/>리포트/재무/콘텐츠/설정"]
        FLUTTER_WEB["Flutter Web 프런트<br/>admin/apps/flutter/<br/>PC 관리 백엔드 UI"]
        MODEL["모델 공유<br/>admin/app/model<br/>39개 symlink<br/>→ service/app/model 동일 구현"]
    end

    subgraph 데이터_계층["데이터 계층"]
        MySQL[("MySQL 8.0<br/>55+ 테이블 · appointment_ 접두사<br/>BIGINT Snowflake 기본 키")]
        Redis[("Redis<br/>캐시/속도제한/Session<br/>큐/기술자 잠금<br/>svc:* 목록 캐시")]
        ES[("Elasticsearch<br/>전문 검색<br/>webman-scout 자동 동기화")]
    end

    subgraph 외부_서비스["제3자 서비스"]
        WXPAY["위챗페이<br/>통합 주문/환불/출금"]
        SMS["SMS 서비스<br/>알리바바 클라우드/텐센트 클라우드"]
        MAP["지도 서비스<br/>아맵/텐센트<br/>역지오코딩/내비게이션"]
        OSS["객체 스토리지<br/>로컬/OSS/COS/CDN"]
        SUBMSG["위챗 구독 메시지<br/>WechatTemplateMessageService<br/>sendSubscribeMessage<br/>주문 이벤트 3개 시나리오"]
    end

    subgraph 보안_컴포넌트["보안 컴포넌트 계층"]
        SEC["Security-PHP<br/>31종 공격 탐지"]
        JWT["JWT 인증<br/>7일 유효기간+블랙리스트"]
        ENC["이중 암호화<br/>API 계층+DB 계층"]
        POSTER["작업 검증<br/>민감 작업 랜덤 검증"]
    end

    WX -->|"HTTP API<br/>기능 동등"| MW1
    APP -->|"HTTP API<br/>기능 동등"| MW1
    MW1 --> API_모듈

    FLUTTER_WEB -->|"HTTP API"| MW2
    MW2 --> ADMIN_API

    API_모듈 --> MySQL
    API_모듈 --> Redis
    API_모듈 --> ES
    ADMIN_API --> MySQL
    ADMIN_API --> Redis
    ADMIN_API --> ES

    보안_컴포넌트 -.->|방어| 비즈니스_서비스_계층
    보안_컴포넌트 -.->|방어| 관리_백엔드_계층

    API_모듈 -.->|호출| 외부_서비스
    ADMIN_API -.->|호출| 외부_서비스

    classDef terminal fill:#e1f5fe,stroke:#0288d1,stroke-width:2px,color:#01579b
    classDef service fill:#fff3e0,stroke:#f57c00,stroke-width:2px,color:#e65100
    classDef admin fill:#e8f5e9,stroke:#388e3c,stroke-width:2px,color:#1b5e20
    classDef data fill:#fce4ec,stroke:#c62828,stroke-width:2px,color:#880e4f
    classDef external fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px,color:#4a148c
    classDef security fill:#fff8e1,stroke:#f9a825,stroke-width:2px,color:#f57f17

    class WX,APP terminal
    class MW1,API_모듈,PUB,USER,TECH,SVC,ORD,MKT,WALLET,CTN,LBS,CACHE,RES service
    class MW2,ADMIN_API,FLUTTER_WEB,MODEL admin
    class MySQL,Redis,ES data
    class WXPAY,SMS,MAP,OSS,SUBMSG external
    class SEC,JWT,ENC,POSTER security
```
