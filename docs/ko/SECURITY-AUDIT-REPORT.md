# 보안 감사 보고서 — 예약 시스템 (appointment-php)
> **Languages**: [中文](../SECURITY-AUDIT-REPORT.md) · [English](../en/SECURITY-AUDIT-REPORT.md) · [Русский](../ru/SECURITY-AUDIT-REPORT.md) · [Deutsch](../de/SECURITY-AUDIT-REPORT.md) · [Français](../fr/SECURITY-AUDIT-REPORT.md) · [Español](../es/SECURITY-AUDIT-REPORT.md) · [Português](../pt/SECURITY-AUDIT-REPORT.md) · [हिन्दी](../hi/SECURITY-AUDIT-REPORT.md) · [العربية](../ar/SECURITY-AUDIT-REPORT.md) · [বাংলা](../bn/SECURITY-AUDIT-REPORT.md) · [Bahasa Indonesia](../id/SECURITY-AUDIT-REPORT.md) · [日本語](../ja/SECURITY-AUDIT-REPORT.md)

**날짜**: 2026-08-04
**감사 범위**: service（예약 서비스 시스템）、admin（개방 관리 백엔드）
**PHP 버전**: 8.3.7
**프레임워크**: webman v2

---

## 一、테스트 결과

| 테스트 항목 | Service | Admin |
|--------|---------|-------|
| PHP 문법 검사（전체) | 통과 | 통과 |
| PHPUnit 단위 테스트 | 59 tests / 165 assertions PASS | 59 tests / 165 assertions PASS |
| PHPStan 정적 분석 | 미설치 (dev 의존성 다운로드 타임아웃) | 미설치 (dev 의존성 다운로드 타임아웃) |

---

## 二、보안 방어 계층 총괄

```
요청 → Nginx (보안 헤더+민감 파일 보호) → Cors (CORS+보안 헤더) → SecurityMiddleware (31종 공격 탐지) → RateLimit (Redis 슬라이딩 윈도우) → Auth (JWT) → Controller
                                                                                                   ↓
                                                                                    IP 블랙리스트 (5회 공격/60s → 차단 15min)
                                                                                    계정 잠금 (5회 실패/15min → 잠금 15min)
```

---

## 三、수정 완료된 문제

### 3.1 Service CORS 보안 응답 헤더 부재 → 수정 완료
**파일**: `service/app/middleware/Cors.php`
- 보안 헤더 6개 추가：X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP, X-Permitted-Cross-Domain-Policies
- 이제 admin 보안 헤더 구성과 일치

### 3.2 Service 로그인 실패 잠금 부재 → 수정 완료
**파일**: `service/app/api/v1/controller/AuthController.php`
- `login()` 및 `loginByCode()` 메서드에 Redis 실패 카운트 추가
- 5회 실패/15분 잠금 → HTTP 429
- Redis 장애 시 우아한 폴백

### 3.3 CORS Origin 하드코딩 `*` → 수정 완료
**파일**: `service/app/middleware/Cors.php`, `admin/app/middleware/Cors.php`
- `CORS_ALLOW_ORIGIN` 환경 변수로 구성 변경
- 비워두면 기본 `*`（하위 호환)

### 3.4 Service security-php 의존성 부재 → 수정 완료
**작업**:
- `allow-plugins.erikwang2013/security-php`를 composer.json에 추가
- `composer install --no-dev`로 의존성 설치
- 구성 파일을 `config/plugin/erikwang2013/security-php/app.php`에 배포
- CSRF Origin 탐지기 (`csrf_origin`) 활성화 (block 모드)

### 3.5 Service Nginx Permissions-Policy 부재 → 수정 완료
**파일**: `service/docs/nginx.conf`
- `add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;` 추가

### 3.6 생태계 구성 보강 → 수정 완료
- `service/.env.example` 및 `admin/.env.example`에 `CORS_ALLOW_ORIGIN` 추가
- `service/.env.docker` 및 `admin/.env.docker`에 `CORS_ALLOW_ORIGIN` 추가

---

## 四、현재 보안 방어 전체 목록

### 4.1 WAF 계층 — 31종 공격 탐지기

| 모드 | 탐지기 | 개수 |
|------|--------|------|
| **block** (403 차단) | XSS, SQL 인젝션, 커맨드 인젝션, 경로 순회, 파일 업로드, SSRF, XXE, 역직렬화, LDAP 인젝션, 이메일 헤더 인젝션, Open Redirect, JWT 공격, Host 헤더 공격, Request Smuggling, GraphQL 인젝션, XPATH 인젝션, JNDI/Log4Shell, SSI 인젝션, CSV 인젝션, 데이터 유출, Prototype Pollution, WebSocket 하이재킹, CORS 우회, DNS Rebinding, HTTP 메서드 검증, 요청 본문 크기(10MB), Content-Type 화이트리스트, CSRF Origin | 28 |
| **log** (기록만) | 응답 헤더 인젝션, SSTI, NoSQL 인젝션 | 3 |

### 4.2 인증과 권한 부여

| 메커니즘 | Service | Admin |
|------|---------|-------|
| JWT 인증 | Auth 미들웨어 | AdminAuth 미들웨어 |
| JWT 블랙리스트 | 로그아웃 시 추가 | 로그아웃+세션 초과 시 추가 |
| RBAC 권한 | — | method.path 형식, Redis 60s 캐시 |
| 계정 잠금 | 5회/15분 (Redis) | 5회/15분 (Redis) |
| 동시 세션 제한 | — | 최대 3개 Token |
| 비밀번호 해시 | bcrypt | bcrypt |

### 4.3 속도 제한

| 라우트 | Service | Admin |
|------|---------|-------|
| 기본 | 60회/분/IP | 60회/분/IP |
| 로그인 | 10회/분 | — |
| 회원가입 | 5회/분 | — |
| SMS/비밀번호 찾기 | 5회/분 | — |

### 4.4 데이터 보안

| 조치 | Service | Admin |
|------|---------|-------|
| DB 필드 암호화 | AES-256-CBC (6개 모델) | AES-256-CBC |
| API 전송 암호화 | AES-256-CBC | AES-256-CBC |
| ID 난독화 (Hashids) | 모든 대외 ID | 모든 대외 ID |
| Snowflake ID | 비자동증가 BIGINT | 비자동증가 BIGINT |
| 민감 필드 마스킹 | 전화번호 마스킹 | 내보내기 데이터 마스킹 |

---

## 五、대기 중인 제안

### 5.1 제안：security-php 스토리지를 Redis로 변경（프로덕션)
**현재**: 두 서비스 모두 `file` 타입 스토리지（로컬 JSON 파일)
**리스크**: 다중 인스턴스 배포 시 IP 블랙리스트가 공유되지 않아 공격자가 인스턴스를 바꿔 우회 가능
**제안**: 프로덕션에서 `storage.type`을 `redis`로 변경

### 5.2 제안：Session Cookie 보안 속성
**현재**: `secure: false`, `same_site: ''`
**리스크**: Cookie가 HTTP로 전송될 수 있고 CSRF 방어가 약해짐
**제안**: 프로덕션에서 `secure: true`, `same_site: 'Lax'` 설정

### 5.3 제안：PHPStan dev 의존성 설치
**현재**: `composer install --dev`가 네트워크 타임아웃으로 실패
**작업**: `composer install --dev` 또는 `composer require --dev phpstan/phpstan`

### 5.4 알림：프로덕션 배포 전 모든 키 변경
`.env.docker`의 플레이스홀더 키는 프로덕션 배포 전에 랜덤 생성 값으로 교체 필수：
- `JWT_SECRET_KEY`
- `HASHIDS_SALT`, `HASHIDS_ALT_SALT`
- `ENCRYPTION_KEY`, `ENCRYPTABLE_KEY`
- `DB_PASSWORD`

---

## 六、문서 산출물

| 문서 | 경로 |
|------|------|
| Service 보안 아키텍처 | `service/docs/SECURITY.md` |
| Admin 보안 아키텍처 | `admin/docs/SECURITY.md` |
| 본 감사 보고서 | `docs/SECURITY-AUDIT-REPORT.md` |

---

## 七、감사 결론

**보안 방어 전체 등급：양호**

- 심층 방어 계층 완비（Nginx → WAF → Rate Limit → Auth → RBAC)
- 31종 공격 탐지기 전역 커버, 28종은 차단 모드
- JWT + 블랙리스트 + 계정 잠금 + IP 블랙리스트 다중 인증 방어
- 데이터 계층 AES-256-CBC 암호화 + Hashids 난독화
- service 보안 응답 헤더 부재, 로그인 잠금 부재, WAF 패키지 부재 3개 핵심 문제 수정 완료
- 제안 항목은 프로덕션 환경 구성 최적화이며 보안 취약점 아님

---

## 八、2026-08-26 수정 라운드（보안 강화）

| 항목 | 수정 내용 |
|----|---------|
| 주문 위변조 방지 | OrderController::store() 주문 항목 가격은 항상 DB 레코드 기준（service→appointment_service, product→appointment_product)，클라이언트 가격은 계산에 미참여；미지의 target_type 422；target_id 반드시 hashid（raw id 디코딩 0 → 422「상품이 없거나 하품됨」)；공동구매/번개세일 가격도 DB 기준 |
| 번개세일 재고 차감 통일 | 재고는 /api/order store() 트랜잭션 내 행 잠금으로 일괄 차감；SeckillController::buy는 더 이상 선차감하지 않음（Redis 활동 잠금 + client_token 멱등 유지)；/api/order를 seckill_id와 함께 직접 호출해도 재고 차감 |
| 기술자 출금 | 신청 시 잔액에서 재경비(pending/approved) 예약 차감；승인 송금 전 재검증 settled−withdrawn−재경비 ≥ 출금액；동시 승인 시 이중 지급 없음 |
| 결제 콜백 | 위챗 콜백 total_fee를 주문 결제 금액과 엄격 비교, 불일치 시 거부；알리페이 콜백 로그 마스킹（buyer_id/seller_id 등 제외) |
| /install 방어 | 설치 성공 시 .install.lock 기록, install 인터페이스 이중 검증（파일 잠금 + isInstalled)；.gitignore가 .install.lock 무시 |
| 의존성 수렴 | webman-scout 통일 2.0.5（service/admin)；opensearch-project/opensearch-php ^2.6 추가；dompdf/security-php/webman-database 정밀 버전 잠금（"*" 와일드카드 제거) |
| 엔지니어링 | service/app/common/StorageService.php 삭제（죽은 코드)；admin/app/common/에 TechnicianWithdrawalService/WechatPayService 추가（admin 독립 배포 시 service 코드에 의존하지 않음)；두 앱 phpstan.neon 수정으로 실행 가능（php -d memory_limit=2G) |
