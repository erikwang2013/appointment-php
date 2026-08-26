# 아키텍처 설명

## 시스템 개요

예약 서비스 시스템은 3단말 + 이중 서비스 아키텍처를 사용합니다:

```
┌─────────────────────────────────────────────────────┐
│                    사용자 단말 계층                      │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ 위챗 미니프로그램 │  │ Flutter APP   │                │
│  │ apps/wechat/  │  │ apps/flutter/ │                │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │      기능 동등     │                         │
│         └────────┬─────────┘                         │
│                  │ 고객/기술자 신원 전환                 │
├──────────────────┼──────────────────────────────────┤
│              비즈니스 API 계층                          │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ service/ API  │  │ admin/ API    │                │
│  │ 포트 8787      │  │ 포트 8787     │                │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │                  │                          │
│         └────────┬─────────┘                          │
│                  │ 공유 MySQL/Redis/ES                 │
├──────────────────┼──────────────────────────────────┤
│                  데이터 계층                            │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌──────────┐     │
│  │ MySQL  │ │ Redis  │ │  ES    │ │제3자 서비스 │     │
│  └────────┘ └────────┘ └────────┘ └──────────┘     │
└─────────────────────────────────────────────────────┘
```

## 프로젝트 구성

### service/ — 비즈니스 API 서비스

위챗 미니프로그램과 Flutter APP에 모든 비즈니스 인터페이스를 제공합니다. webman v2, 포트 8787.

**모듈 구분:**

| 모듈 | 경로 | 인증 | 설명 |
|------|------|------|------|
| 공개 API | `api/` | 없음 | 로그인/회원가입/인증코드/위챗 콜백 |
| 사용자 모듈 | `user/` | JWT | 프로필/주소/즐겨찾기/피드백/홍보 |
| 기술자 모듈 | `technician/` | JWT+기술자 | 프로필/스케줄/작업대/검증/회원/수익/출금 |
| 서비스 모듈 | `service/` | 혼합 | 분류/항목/검색/매장 |
| 주문 모듈 | `order/` | JWT | 장바구니/주문/결제/환불/검증/평가(OrderController는 비즈니스 도메인별로 10개 trait로 분리, 라우트와 메서드명 동일) |
| 마케팅 모듈 | `marketing/` | JWT | 쿠폰/멤버십 카드(횟수권)/포인트/선물 카드/회원 혜택 |
| 지갑 모듈 | `wallet/` | JWT | 잔액/충전/거래 내역/잔액 결제 |
| 콘텐츠 모듈 | `content/` | 혼합 | 배너/공지/알림 |
| LBS 모듈 | `lbs/` | 공개 | 도시/주변 매장 |

### admin/ — 관리 백엔드

PC 관리 백엔드. webman v2 + Flutter Web, 포트 8787.

**기존 모듈:** 인증, 대시보드, 사용자 관리, 역할 권한, 시스템 설정, 조작 로그, 파일 업로드, 보안 방어

**모델 분포:** `admin/app/model/`에는 6개 특유 모델만 유지(AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig), 나머지 모델은 composer psr-4(`app\model\` → `../service/app/model/`)로 service 버전을 공유해 이중 모델 드리프트 방지; `support\Model` 기본 클래스는 service와 정렬, `UserPointsExchange::user()` 관계 메서드는 service 버전 모델에 통합.

**확장 모듈:** 기술자 관리, 회원 관리, 매장 관리, 서비스/상품 관리, 주문 관리, 쿠폰, 멤버십 카드, 출금 심사, 평가 관리, 리포트 통계, 재무 관리, 콘텐츠 관리, 시스템 설정

### apps/ — 사용자 단말 프런트엔드

| 디렉터리 | 기술 | 플랫폼 |
|------|------|------|
| `apps/wechat/` | 네이티브 위챗 미니프로그램 | 위챗 |
| `apps/flutter/` | Flutter 3.x + GetX + Dio | iOS + Android |

## 핵심 컴포넌트

### Snowflake ID

모든 기본 키는 `erikwang2013/snowflake-php`로 생성되며, BIGINT 비자동증가로 분산 환경에서 전역 고유성을 보장합니다. `service/support/Model::nextId()`는 프로세스 내에서 단일 Snowflake 인스턴스를 재사용하며, 64개 모델의 `generateId()` 복사본은 삭제되었습니다(기본 클래스 구현 통일 상속).

### Hashids

API 요청/응답의 ID는 `erikwang2013/hashids`로 인코딩되어 외부에는 hash 문자열로 노출됩니다.

### JWT 인증

`erikwang2013/jwt-webman` Bearer Token, 7일 유효기간, 갱신과 블랙리스트 지원.

### 데이터 암호화

- **API 계층**: `erikwang2013/encryption` 민감 데이터 암호화/복호화
- **DB 계층**: `erikwang2013/encryptable` trait 자동 필드 암호화/복호화

### 보안 방어

- `erikwang2013/security-php`: 31종 공격 탐지
- `erikwang2013/poster-php`: 민감 작업 무작위 검증
- 로그인 잠금: 5회 실패 시 15분 잠금
- 동시성 제한: 최대 3개 유효 Token

### API 문서

`hg/apidoc`가 OpenAPI 3.0 규격 문서를 생성하며, 관리단말과 클라이언트를 분리합니다:

| 단말 | 주소 | 설명 |
|------|------|------|
| 관리단말 | `admin/ GET /api/docs` | 관리 백엔드 API(JWT+RBAC) |
| 클라이언트 | `service/ GET /api/docs` | 비즈니스 API(JWT Bearer) |

문서는 공개 접근 가능하며, Swagger UI에 가져와 대화형 인터페이스 문서를 볼 수 있습니다.

### Elasticsearch

`erikwang2013/webman-scout` 모델 자동 ES 동기화, 전문 검색 지원.

## 미들웨어 실행 체인

### service/ 미들웨어

```
공개 API:  Cors → Security(31종 탐지) → RateLimit → ApiVersion → Controller
사용자 API:  Cors → Security → RateLimit → Auth(JWT) → Controller
기술자 API:  Cors → Security → RateLimit → ApiVersion → Auth → TechnicianAuth → Controller
```

### admin/ 미들웨어

```
공개 API:  Cors → Security → RateLimit → Controller
관리 API:  Cors → Security → RateLimit → AdminAuth(JWT) → AdminPermission(RBAC) → OperationLog → Controller
헬스 체크: Cors → Security → RateLimit → Controller
```

## 데이터 흐름

### 요청 흐름

```
클라이언트 → Cors → Security → RateLimit → Auth(JWT) → [TechnicianAuth] → Controller
    → Model(encryptable 암호화/복호화) → BaseController(hashids 인코딩) → JSON 응답
```

### 예약 프로세스

```
서비스 조회 → 매장/기술자/시간 선택 → 주문 제출 → Redis 기술자 3분 잠금
    → 위챗 결제 → 기술자 알림 → 서비스 시작 → 서비스 완료 → 평가 → 주문 완료
```

## 8개 조작 출처 단말

## 최신 확장

| 카테고리 | 기능 |
|------|------|
| 실시간 | WebSocket 푸시 / 결제 콜백 / APNs+FCM |
| 메시지 | 구독 메시지 푸시(sendSubscribeMessage 주문 이벤트 3개 시나리오) |
| 지갑 | 잔액 적립 / 잔액 결제 / 환불 환충 |
| 매장 | 블루투스 프린팅 / 전자 서명 / 대기 순번 |
| 기술자 | 온라인 평가 / 숏 영상 표시 / 작업대(today/records/start/complete) |
| 커뮤니티 | 글쓰기/댓글/좋아요/심사 |
| 시스템 | 다국어(중/영) / 주문 자동 취소 / 데이터 시드 |

`source` 필드가 조작 출처를 기록: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### 제3자 서비스 통합

| 서비스 | 클래스 | 기능 |
|------|------|------|
| 위챗 결제 | WechatPayService | 통합 주문/조회/환불/영점 지갑 출금 |
| 문자 | SmsService | 알리바바 클라우드/텐센트 클라우드 이중 채널 |
| 지도 | MapService | Amap/텐센트 역지오코딩/거리/내비게이션 |
| 템플릿 메시지 | WechatTemplateMessageService | 주문/환불/알림 푸시 + 구독 메시지(sendSubscribeMessage 주문 이벤트 3개 시나리오) |
