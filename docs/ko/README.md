# 예약 서비스 시스템

> 한국어 번역 · 원본: [中文](../../README.md)

4개 단말 예약 서비스 관리 플랫폼: 사용자용 위챗 미니프로그램 + Flutter APP + HarmonyOS APP(동일 계정으로 신원 전환) + PC 관리 백엔드.

> **프로젝트 상태**: 전체 완료 ✅ | 컨트롤러 143개(service 69 / admin 74) | 모델 87개 | 테스트 722개(service 558 / admin 164) | 데이터 테이블 95개 | 라우트 388개(service 227 / admin 161)

## 프로젝트 소개

<img src="../../docs/diagrams/mascot.svg" alt="예약 서비스 시스템 마스코트 — 예약 토끼(SVG 애니메이션)" width="200" align="right">

**예약 서비스 시스템**은 생활 서비스 업종을 위한 4단말 예약 관리 플랫폼입니다. 사용자 단말은 **위챗 미니프로그램, Flutter APP, HarmonyOS APP** 3개 단말을 지원하며, 동일 계정으로 단말 간 자유롭게 전환할 수 있습니다. 여기에 **PC 관리 백엔드**를 더해 "사용자 예약 → 기술자 수주 → 백엔드 운영" 전 과정의 디지털 클로즈드 루프를 구현합니다. 매장 예약, 기술자 서비스, 회원 마케팅, 재무 정산까지 하나의 시스템으로 모두 처리합니다.

**원스톱 예약 경험**

사용자 3개 단말은 동일한 경험을 제공합니다. 캘린더에서 직관적으로 시간을 골라 예약하고, 쿠폰/횟수권/포인트 차감, 번개세일과 공동구매 할인, 위챗/잔액 결제를 지원하며, 주문 상태는 전 과정 추적이 가능합니다. 일정 변경, 취소, 환불, 애프터서비스, 전자 세금계산서까지 전 과정을 온라인으로 처리합니다. 기술자 단말은 작업대, 출퇴근 체크인, 일괄 스케줄 설정, 서비스 검증, 수익 출금을 제공하여 운영 효율을 한눈에 파악할 수 있습니다.

**전체 경로 마케팅 성장**

만 N 원 단위 할인 행사, 번개세일, 공동구매, 쿠폰 양도, 포인트 몰과 행운의 룰렛, 멤버십 카드/성장 등급 혜택, 2단계 유통 수수료, 재방문 고객 보상 등 10여 가지 마케팅 도구가 내장되어 있으며, 구독 메시지 푸시와 APP 푸시를 결합해 가맹점의 신규 유치, 유지, 재구매를 지속 지원합니다.

**엔터프라이즈급 보안과 컴플라이언스**

자체 개발 보안 컴포넌트를 적용합니다: JWT 인증, ID 난독화, 31종 공격 탐지, 민감 데이터 이중 암호화, 가격 서버 측 검증, 결제 콜백 엄격 비교와 멱등성 중복 방지. 또한 위챗 공식 분배금, 개인정보 데이터 내보내기, 계정 탈퇴를 지원하여 컴플라이언스 요구를 충족합니다.

**성숙한 기술 기반**

PHP 8.3 + webman 고성능 상주 프레임워크 기반, MySQL 8.0 + Redis + Elasticsearch 지원. 데이터 테이블 95개, 인터페이스 388개, 세분화된 권한 포인트 285개, 자동화 테스트 722개가 모두 통과했으며, 완성도 높은 중/영문 아키텍처 문서와 원클릭 설치 스크립트를 갖추고 있어 즉시 사용 가능하고 2차 개발이 쉽습니다.

단일 매장 예약이든 다점포 체인 규모든, 예약 서비스 시스템이 안정적이고 안전하며 확장 가능한 통합 솔루션을 제공합니다.

## 프로젝트 구조

```
appointment-php/
├── admin/                     # 관리 백엔드 (webman v2 + Flutter Web, 독립 배포 :8787)
│   ├── app/                   #   admin(백엔드 컨트롤러)/api/model/middleware/process/view
│   ├── apps/                  #   Flutter Web 백엔드 / HarmonyOS / 위챗 관리단말
│   ├── config/                #   라우트/데이터베이스/프로세스/플러그인 설정
│   ├── database/              #   백업 스크립트(테이블 구조와 시드 데이터는 docs/install.sql 통일)
│   ├── tests/                 #   PHPUnit(#[\Test] 속성 스타일)
│   └── start.php
├── service/                   # 비즈니스 API 서비스 (webman v2, 독립 배포 :8787)
│   ├── app/                   #   api/user/technician/order/wallet/marketing/notification 등 모듈
│   ├── config/                #   라우트/데이터베이스/프로세스/결제 등 설정
│   ├── support/               #   Model 기본 클래스(generateId)/Request/Response
│   ├── tests/                 #   PHPUnit
│   └── start.php
├── apps/                      # 사용자 단말 프런트엔드 앱
│   ├── wechat/                #   위챗 미니프로그램(네이티브)
│   ├── flutter/               #   Flutter APP(iOS + Android)
│   └── harmonyos/             #   HarmonyOS APP(하모니OS 네이티브)
└── docs/                      # 프로젝트 문서
    ├── API.md / FEATURES.md / STRUCTURE.md / install.sql / README.md ...
    └── diagrams/              #   아키텍처/흐름도(SVG + mermaid)
```

## 빠른 시작

### 환경 요구사항

- PHP 8.3+
- MySQL 8.0+
- Redis
- Composer

### Web 설치 마법사(권장)

```bash
cd admin/
cp .env.example .env
composer install
php start.php start -d
```

브라우저에서 `http://localhost:8787/install`을 열고 안내에 따라 데이터베이스와 관리자 계정을 입력하면 설치가 완료됩니다.

### 수동 설치

```bash
# 1. 의존성 설치
cd service/ && cp .env.example .env && composer install
cd ../admin/ && cp .env.example .env && composer install

# 2. 데이터베이스 원클릭 가져오기(95개 전체 테이블 + 권한/설정 시드 포함)
mysql -u root -p < ../install.sql

# 3. 서비스 시작
cd service/ && php start.php start -d   # 비즈니스 API → :8787
cd ../admin/ && php start.php start -d  # 관리 백엔드 → :8787
```

### Docker 배포

```bash
cd admin/ && cp .env.docker .env && docker-compose up -d
cd ../service/ && cp .env.docker .env && docker-compose up -d
```

## 기술 스택

| 계층 | 기술 | 설명 |
|------|------|------|
| 백엔드 프레임워크 | webman v2 (PHP 8.3+) | 고성능 상주 메모리 HTTP 서비스 |
| 데이터베이스 | MySQL 8.0 | 테이블 접두사 `erik_` |
| 캐시 | Redis | 캐시/속도 제한/Session/대기열 |
| 검색 | Elasticsearch | 전문 검색(via webman-scout) |
| 관리 백엔드 프런트엔드 | Flutter Web | PC 관리 백엔드 스타일 |
| 사용자 APP | Flutter | iOS + Android |
| 사용자 미니프로그램 | 네이티브 위챗 미니프로그램 | WXML/WXSS/JS |
| 사용자 하모니OS APP | HarmonyOS ArkTS | 네이티브 @ohos.net.http |
| ID 생성 | erikwang2013/snowflake-php | BIGINT 비자동증가 기본 키 |
| API ID 암호화/복호화 | erikwang2013/hashids | 실제 ID 외부 노출 방지 |
| JWT 인증 | erikwang2013/jwt-webman | Bearer Token |
| 민감 데이터 암호화 | erikwang2013/encryption + encryptable | API + DB 이중 암호화 |
| 보안 방어 | erikwang2013/security-php | 31종 공격 탐지 |
| 조작 검증 | erikwang2013/poster-php | 민감 작업 무작위 검증 |
| 국가 국기 | erikwang2013/season | 국기 아이콘 |
| ES 동기화 | erikwang2013/webman-scout | 모델 자동 동기화 |

## 시스템 아키텍처

<img src="../../docs/diagrams/ko-architecture.svg" alt="ko-architecture.svg" width="100%">

## 핵심 프로세스

### 서비스 예약 프로세스

<img src="../../docs/diagrams/ko-appointment-flow.svg" alt="ko-appointment-flow.svg" width="100%">

### 결제와 환불 프로세스

<img src="../../docs/diagrams/ko-payment-refund.svg" alt="ko-payment-refund.svg" width="100%">

## 주문 생애주기

<img src="../../docs/diagrams/ko-order-lifecycle.svg" alt="ko-order-lifecycle.svg" width="100%">

## 보안 아키텍처

### 심층 방어 7계층 체계

<img src="../../docs/diagrams/ko-security-defense.svg" alt="ko-security-defense.svg" width="100%">

> 더 많은 상세 그림: [흐름도](ARCHITECTURE-DIAGRAM.md)(기술자 출금/신원 전환 포함) | [기능 마인드맵](FUNCTION-DIAGRAM.md) | [전체 생애주기](LIFECYCLE-DIAGRAM.md) | [전체 보안 아키텍처](SECURITY-ARCHITECTURE.md)

## 핵심 기능 하이라이트(6-24차 라운드)

| 기능 | 설명 |
|------|------|
| 적립 지갑 | user_wallet / wallet_recharge / wallet_txn 테이블; 잔액+거래 내역, 위챗페이 충전(콜백 R 접두사 주문번호), 주문 잔액 결제(pay_channel=balance), 위챗/잔액 환불 시 잔액 자동 환충 |
| 관리 백엔드 UI 완비 | Flutter Web 20개 페이지: dashboard/사용자/역할/설정/로그/검증/스케줄/서비스/기술자/주문/쿠폰/멤버십/횟수권/공지/FAQ/출금/평가/리포트/마이페이지 |
| 미니프로그램 구독 메시지 | 주문 3개 시나리오 구독 푸시(결제 성공/환불 입금/검증 성공); push_sent_at 멱등; 템플릿 미설정 시 사이트 내 알림으로 자동 대체 |
| 기술자 출금 | 관리단말 심사; 금액 ≥500은 2단계 승인(매장장→재무); 상태 머신 pending→approved→completed(rejected/failed) |
| 횟수권 검증 클로즈드 루프 | 내 횟수권 실시간 used_up/expired 계산; 검증 Redis NX 멱등 + 행 잠금 차감, completed 주문 + OrderItem + OrderPayment(pay_type='card') 직접 생성 |
| 기술자 작업대 | 오늘의 작업/완료 기록/시작·완료(행 잠금+상태 머신 가드+멱등, 완료 후 사이트 내 알림 작성); 미니프로그램 tech-work 3개 탭 |
| 쿠폰 차감 | PriceCalculator: applyCoupon 읽기 전용 금액 계산 / consume 결제 시 used 처리 / restoreCouponAndCard 환불 멱등 반환; fixed/percent + min_amount 문턱 |
| 선물 카드 | redeem 시 cash 타입을 지갑에 충전(행 잠금 이중 입금 방지, WalletTxn type='gift_card'), gift 타입은 표시만 |
| 포인트 체계 | 출석체크 포인트 지급; 검증 소비 시 floor(paid×1) 포인트 지급(order_id 멱등, balance 스냅샷); 환불 시 비율대로 회수; 명세 페이징 + type/source 필터 |
| 멤버십 관리 | erik_user.member_level 컬럼(마이그레이션 000008); 관리단말 멤버십 카드 전체 CRUD(권한 365-369) |
| 미니프로그램 주문 체인 | 서비스 상세 → 주문 확인(쿠폰 선택/문턱 회색 처리/클라이언트 예상 금액) → POST /order → 위챗/잔액 결제; 미니프로그램 총 20개 페이지 |
| 공동구매 클로즈드 루프 | join 중복 참여 422 + 인원 만원 잠금 + 만료 시 지연 종료; 결성 후 주문 시 store에 promotion_id 전달해 공동구매가(discount_percent)로 주문, 쿠폰/횟수권/포인트 중첩 금지, 결성 실패 시 주문 자동 취소 + 기술자 잠금 해제(기존 FLASH_SALE 프로모션 채널 폐지, 번개세일은 별도 채널) |
| 매장장 작업대 | service /api/store-manager 4개 API(overview/orders/technicians/revenue) store_id 강제 격리(매장 없음 403); admin 매장 작업대 개요 + 주문 store_id 필터 + Flutter 페이지 + 권한 372 |
| 유통 수수료 | 추천인의 첫 주문 completed 후 paid_amount × reward_rate(시스템 설정, 기본 0.05)를 추천인 지갑에 수수료 입금(WalletTxn referral_reward); 행 잠금+빈값 확인+첫 주문 재검증 3중 멱등; earnings 명세 + admin 기록 조회(권한 379) |
| 포인트 교환 몰 | 교환 상품/교환 기록 2개 테이블; 교환 API Redis NX + 행 잠금 초과 교환 방지 + uk_user_goods 동일 사용자 1회 제한; coupon 발권 / wallet 입금 / gift_card 카드 키 3가지 결과; admin CRUD + 상/하품 + 기록(권한 373-378) |
| 예약 일정 변경 | POST /api/order/reschedule/{id} 동일 기술자 시간 변경; pending/paid/confirmed 상태이며 원래 서비스 시작까지 ≥6h일 때만 가능; order_lock + 새 시간대 기술자 잠금 SETNX(180s) 동시성 초과 판매 방지 + B2 스케줄 충돌 검증; erik_order_reschedule + SCENE_RESCHEDULE 구독 메시지 기록 |
| 쿠폰 양도 | 8자리 고유 양도 코드(uk_code 폴백, 7일 유효); claim 남용 방지: Redis NX 잠금 + 행 잠금 재검증 이중 사용 방지, uk_user_coupon 양도 1회 제한, 양도받은 쿠폰 재양도 불가, 자기 자신 수령 불가; 지연 만료 시 원래 쿠폰 복원 |
| 포인트 만료 | expires_at(기본 365일, points.expiry_days 설정); PointsExpiryTimer 60초 커서 스캔으로 type=expire 음수 차감(3중 멱등) + 집계 사이트 내 알림; 만료 포인트는 현금 전환/교환 불가 |
| 기술자 등급 자동 평가 | TierRatingService 실시간 주문량+평균 평점 집계 후 profile에 기록, tier_config에 따라 높은 등급부터 매칭; 승급만 지원(allowDowngrade는 수동 재평가용); 변경 시 erik_technician_tier_log + 사이트 내 알림; admin 로그 조회(권한 380) |
| 번개세일 주문 클로즈드 루프 | /api/seckill 행사 + buy 멱등/동시성 방지, 주문 시 seckill_id 주입해 store() 재사용, 재고는 트랜잭션 내 행 잠금으로 일괄 차감(번개세일가 = seckill_price DB 기준), 매진 시 422 "품절", 취소 시 재고 미복원; 기존 promotion flash_sale 채널 폐지 |
| 서비스 시작 전 알림 | ServiceReminderTimer 60초 스캔으로 1시간 내 시작되는 confirmed/serving 주문 → SCENE_REMINDER 구독 메시지 + 사이트 내 알림(order_id+type 중복 방지, 3중 멱등); 템플릿 미설정 시 사이트 내 알림으로 자동 대체 |
| 만료 알림 | ExpiryReminderTimer 6시간 스캔으로 3일 내 만료되는 멤버십 카드/쿠폰 → type=card_expiry/coupon_expiry + SCENE_EXPIRY 구독 메시지(order_id로 출처 기록, 중복 방지) |
| 기술자 평가 답글 | POST /api/technician/review/reply/{order_id}: 본인 아님 404, 중복 답글 422, 답글 성공 시 사이트 내 알림; erik_order_review에 replied_at 추가; admin 답글 상세(권한 381) |
| 충전 입금 알림 | 위챗 충전 콜백 트랜잭션 내 사이트 내 알림 type='wallet_recharge' 작성(콜백 멱등 재사용, 동일 트랜잭션 원자 커밋, 실패해도 메인 흐름 차단 안 함) |
| 잔액 이체 | POST /api/wallet/transfer 사용자 간 이체: 건당 0.01-1000 + 일일 5000 한도; Redis NX 잠금 + 양쪽 지갑 행 잠금(user_id 오름차순 데드락 방지) + client_token 24시간 멱등; WalletTxn transfer_out/transfer_in 이중 거래 내역에 balance_after 스냅샷 포함; 수신자 사이트 내 알림 type='balance_received' |
| 포인트 양도 | POST /api/user/points/transfer 사용자 간 양도: 1-10000 포인트 + 일일 누적 10000 한도; Redis NX 잠금 + 양쪽 마지막 거래 내역 lockForUpdate(오름차순 데드락 방지) + 잠금 내 재검증; 송신자 consume/수신자 earn 이중 거래 내역(수신에는 expires_at 포함, 정상 만료 가능); 수신자 사이트 내 알림 type='points_received' |
| 평가 추평 | POST /api/order/review/{order_id}/append: 본인 아님 404/중복 422/빈 내용 422/비-completed 422, 성공 시 기술자 사이트 내 알림 type='review_append'; erik_order_review에 append_content/append_images(JSON)/append_at 추가; 회원 평가 제출 라우트 등록(기존 store 라우트 없음) 및 잠복 TypeError 수정 |
| 사용자 물류 추적 | GET /api/order/logistics/{id}: 본인 product 주문만(아님 404/상품 아님/발송 안 함); order.remark JSON 파싱(shipping_company/tracking_no/shipped_at, admin 발송 시 기록); 수령인 휴대폰번호 마스킹 138****5678 |
| 알림 수신 설정 | erik_user_notify_setting 테이블(uk_user_type 고유 키, 기본 행 없음 = 기본 켜짐); GET/PUT /api/user/notify-settings; 5개 스위치 service_reminder/card_expiry/points_expiry/marketing/system(system은 항상 켜짐, 끌 수 없음); notifySettingEnabled로 3개 타이머 + 구독 이벤트 게이트, 끄면 사이트 내 알림과 구독 메시지를 모두 건너뜀 |
| 예약 월력 | GET /api/calendar/technician/{id}(월 보기) + /day(일 보기): time_slots JSON으로 시간 슬롯 전개, erik_order의 예약된 시간대 제외; 매장 스케줄을 시각적으로 선택 |
| 사용자 성장 등급 | erik_user_growth + erik_growth_level(브론즈0/실버100/골드500/플래티넘2000/다이아5000); 출석 +10, 평가 +20, 소비 1원당 1포인트(기존 상태 재검증 재사용, 자연 멱등); GET /api/growth(개요/records/levels 공개 등급) |
| 전자 세금계산서 | POST/GET /api/invoices(신청/목록/상세): uk_order_type(order_id,order_type) 중복 신청 방지, 금액은 서버에서 산출; admin 발행/반려(권한 382-384) |
| 고객센터 티켓 | POST/GET /api/tickets + /{id}/close: 사용자 제출/목록/상세/종료; admin 답변(권한 385/387) |
| 다단계 유통-2단계 수수료 | 주문 결제 후 1단계 추천인의 추천인에게 paid×level2_rate(설정 0.02) 지급: 트랜잭션 행 잠금 + uk_order_referred 멱등 중복 지급 방지; WalletTxn TYPE_REFERRAL_LEVEL2; admin 기록 조회(권한 386) |
| 성장 등급 혜택 | GrowthLevel.benefits 실체화: 주문 시 등급 discount_rate 할인(일반 주문만, 쿠폰/횟수권→등급 할인 중첩, 할인액은 discount_amount + 메모에 기록, 하한 보호로 0 절사); 결제 콜백 성장값 floor(paid×points_multiplier) 배율 입금(결제 시점 등급 기준, 등급 올리지 않음) |
| 세금계산서 발행자 관리 | erik_invoice_title 자주 쓰는 발행자 라이브러리: 저장/수정/삭제/기본 설정(첫 항목 자동 기본, 기본 삭제 시 자동 이전, 기본 설정 트랜잭션 초기화); 신청 시 title_id 선택 반영, 수동 입력 호환 유지 |
| 티켓 만족도 | 종료 티켓 1-5점 평가(범위 초과 422, 미제공 시 NULL 호환); admin 만족도 집계: 평균/1-5성 분포/평가·미평가 카운트(권한 388) |
| 평가 이미지 심사 | admin ReviewAuditController: 이미지 포함 평가 목록(JSON_LENGTH 필터 + 사용자/기술자 이름 join), 숨김/복원(hide는 visible만, restore는 hidden만, 422 양방향 검증); 숨긴 후 기술자 평가 목록에서 자동 비노출(권한 389-391) |
| 조회 이력 | erik_browse_history(uk_user_item 중복 조회는 viewed_at만 갱신): 서비스 상세 연결 기록(try/catch로 메인 흐름 차단 안 함, 비로그인 스킵); 목록 서비스 정보 join + hashid; 단건 삭제/전체 삭제는 본인만 |

> 8차 라운드 운영성 수정: Poster::verify 잠복 fatal 12곳 제거; DashboardController 통계를 Capsule Manager 쿼리로 변경.
>
> Round-15 보충: 포인트 회수(취소/환불 시 points_offset 포인트 반환, refundOffsetPoints 5개 연결점 멱등); PromotionParticipant 상태를 정수 상수로 변경(엄격 모드에서 join 1366 손상 수정).
>
> Round-16 보충: 포인트 교환(PointsExchangeController, 타입 consume/source=exchange); 공동구매 주문(erik_order에 promotion_id/participant_id 컬럼 추가); 유통 수수료(ReferralRewardService를 WorkController::complete에 연결).
>
> Round-17 보충: 예약 일정 변경(erik_order_reschedule + reschedule API); 쿠폰 양도(erik_user_coupon_transfer + transfer/claim/transfers); 포인트 만료(expires_at + PointsExpiryTimer 프로세스); 기술자 등급 자동 평가(TierRatingService + erik_technician_tier_log, 권한 380).
>
> Round-17 수정: AutoCancelTimer 알림 삽입을 \support\Model::generateId()로 변경(기존에는 존재하지 않는 Snowflake::generate() 호출로 자동 취소 알림이 조용히 실패).
>
> Round-18 보충: 번개세일 주문(store()가 flash_sale 번개세일가 지원); 서비스 시작 전 알림(ServiceReminderTimer + SCENE_REMINDER); 멤버십 카드/쿠폰 만료 알림(ExpiryReminderTimer + SCENE_EXPIRY); 기술자 평가 답글(review reply API + replied_at 컬럼 + 권한 381); 충전 입금 알림(콜백 트랜잭션 내 type='wallet_recharge').
>
> Round-19 보충: 잔액 이체(erik_wallet_transfer + WalletTransferController, 권한 내 이중 행 잠금 + client_token 멱등); 포인트 양도(erik_user_points_transfer + PointsTransferController, 일일 한도 + 양방향 거래 내역); 평가 추평(erik_order_review append 3컬럼 + append API + store 라우트 등록); 사용자 물류 추적(logistics API + remark JSON 파싱 + 휴대폰번호 마스킹); 알림 수신 설정(erik_user_notify_setting + NotifySettingController + 3개 타이머 게이트).
>
> Round-20 보충: 예약 월력(CalendarController 월/일 보기 + 예약 제외); 사용자 성장 등급(erik_user_growth + erik_growth_level 5등급 + 출석/평가/소비 연결); 전자 세금계산서(erik_invoice + uk_order_type 중복 방지 + 백엔드 발행/반려, 권한 382-384); 고객센터 티켓(erik_ticket 제출/목록/상세/종료 + 백엔드 답변, 권한 385/387); 다단계 유통-2단계 수수료(payLevel2Reward 트랜잭션 행 잠금 + uk_order_referred 멱등, 권한 386).
>
> Round-21 보충: 성장 등급 혜택 실체화(주문 discount_rate 할인 + 결제 points_multiplier 포인트 배율, 마이그레이션 시드 5등급 benefits); 세금계산서 발행자 관리(erik_invoice_title 발행자 라이브러리 + 신청 title_id 연동); 티켓 만족도(종료 평가 rating/rated_at + admin 집계 통계, 권한 388); 평가 이미지 심사(ReviewAuditController 숨김/복원, 권한 389-391); 사용자 조회 이력(erik_browse_history + 상세 연결 + 목록/삭제/전체 삭제).
>
> Round-22 보충: 만 N 원 할인 행사(erik_full_reduction 자동 할인 + 문턱 검증, 권한 396-400); ICS 캘린더 내보내기(RFC5545 내 예약); 기술자 체크인 근태(erik_technician_attendance 출퇴근 체크인 + 지각 표시 + admin 통계, 권한 392-393); APP 푸시 서비스(설정 기반 추상화 + 5곳 이벤트 연결, erik_push_log); 위챗 공식 분배금(erik_profit_sharing_log 설정 기반 + 대체, 권한 394); 개인정보 컴플라이언스(데이터 내보내기 + 계정 탈퇴 72h 상태 머신 close_status).
>
> Round-23 보충: 사용자 건강 프로필(erik_user_health_profile); 지갑 결제 비밀번호(erik_user_wallet pay_password 설정/검증); 기술자 일괄 스케줄(batch 가져오기 + 중복 충돌 탐지); 주문 상태 타임라인(erik_order_status_log 8개 상태 로그 + 사용자단말/백엔드 표시); 포인트 행운의 룰렛(erik_lucky_wheel + erik_wheel_record 가중치 추첨, 권한 401-406); 포인트 유효기간(points.expiry_days 설정 + 신규 earn 거래 내역에 expires_at 포함).
>
> Round-24 보충: 게스트 모드(/api/guest/* 비로그인 읽기 전용 조회 + Redis 캐시); 번개세일(erik_seckill_activity + Redis NX 행 잠금 선착 + erik_order.seckill_id 주입 주문, 권한 407-411/420); APP 버전 관리와 업데이트 탐지(erik_app_version + /api/app/version, 권한 416-419); 재방문 고객 보상(30일 내 2차 소비 보너스 type=return_customer, 권한 412-414); 스케줄 CSV 내보내기(UTF-8 BOM + 시간 슬롯 명세, 권한 415).
>
> 2026-08-26 보안 강화: 주문 API의 주문 항목 가격은 모두 데이터베이스 기록 기준(클라이언트 가격 신뢰 불가, 알 수 없는 target_type 422, target_id는 hashid 필수), 공동구매/번개세일가도 DB 기준; 번개세일 재고는 /api/order store() 트랜잭션 내 행 잠금으로 일괄 차감(SeckillController::buy가 선차감하지 않고, Redis 활동 잠금 + client_token 멱등 유지); 기술자 출금 신청 시 진행 중 예약, 승인 이체 전 재확인, 동시 승인 이중 출금 방지; 위챗페이 콜백 total_fee와 주문 결제 금액 엄격 비교, 알리페이 콜백 로그 마스킹; /install 설치 성공 시 .install.lock 이중 검증으로 재설치 방지; 의존성 버전 수렴(webman-scout 2.0.5 / opensearch-php ^2.6 / dompdf, security-php, webman-database 정밀 고정); 두 앱 phpstan.neon 수리 완료(php -d memory_limit=2G 실행 가능).

## 문서 내비게이션

| 문서 | 설명 |
|------|------|
| [아키텍처 설명](ARCHITECTURE.md) | 시스템 아키텍처, 세 단말 관계, 기술 컴포넌트, 데이터 흐름 |
| [기능 설명](FEATURES.md) | 사용자단말/기술자단말/관리 백엔드 전체 기능 목록 |
| [아키텍처 설계](ARCHITECTURE-DESIGN.md) | 계층 설계, 미들웨어 체인, 데이터베이스 설계, 보안 설계 |
| [기능 설계](FEATURE-DESIGN.md) | 핵심 비즈니스 프로세스, 비즈니스 규칙, 상태 머신, 환불 규칙 |
| [API 문서](API.md) | 비즈니스 API + 관리 백엔드 API, 요청/응답 예시 + OpenAPI 엔드포인트 포함 |
| [설치 설명](INSTALL.md) | 환경 요구사항, Docker 배포, 환경 변수, 제3자 설정, 자주 묻는 질문 |
| [사용 설명](USAGE.md) | 관리 백엔드 설정, 사용자단말/기술자단말 조작, 환불 규칙(API 인터페이스는 API.md 참조) |
| [프로젝트 구조](STRUCTURE.md) | 전체 디렉터리 레이아웃, 미들웨어 실행 체인, 데이터베이스 테이블 목록 |
| [테스트 보고서](TEST-REPORT.md) | 전체 테스트 커버리지 감사(558개 케이스 / 2508개 단언) |
| [설계 규격](specs/2026-05-26-appointment-system-design.md) | 시스템 설계 규격 |
| [구현 계획](plans/2026-05-26-appointment-system-plan.md) | 단계별 구현 계획 |

## 지원하기 / Support

이 프로젝트가 도움이 되셨다면 응원해 주세요! 감사합니다 :heart:

If this project helps you, your support is welcome and appreciated!

<table>
  <tr>
    <td align="center" width="50%">
      <img src="../../docs/weixinpay.png" alt="위챗페이 / WeChat Pay" width="130" height="130"><br>
      <b>위챗페이</b><br>WeChat Pay
    </td>
    <td align="center" width="50%">
      <img src="../../docs/alipay.png" alt="알리페이 / Alipay" width="130" height="130"><br>
      <b>알리페이</b><br>Alipay
    </td>
  </tr>
</table>

### 글로벌 송금 / Global Bank Transfer

글로벌 송금 기부를 지원합니다(홍콩 달러 / 위안화 / 미국 달러 / 기타 통화). 후원해 주셔서 감사합니다 :heart:

Global bank transfer donations are welcome (HKD / CNY / USD / other currencies). Thank you for your generosity!

| 항목 Item | 정보 Details |
|-----------|-------------|
| 수취인 이름 Beneficiary Name | WANG KEXUN |
| 수취 계좌 번호 Account Number | 881015918251 |
| 수취 은행 Bank | ZA Bank Limited（SWIFT Code：AABLHKHHXXX，은행 코드 Bank Code：387） |
| 은행 주소 Bank Address | Core F, Cyberport 3, 100 Cyberport Road, Hong Kong |

> **국경 간 송금 중개 은행(필요 시) / Intermediary Bank (if required)**
> 이 정보는 국경 간 송금 중개 은행(중계 은행) 정보이며 수취 은행 정보가 아닙니다. 송금 은행에 필요 여부를 문의하세요.
> Note: this is intermediary bank information, not the receiving bank. Please check with your remitting bank whether it is required.
>
> - 홍콩 달러·위안화·미국 달러 송금(For HKD / CNY / USD): **Citibank N.A. Hong Kong** — SWIFT Code：CITIHKHXXXX，은행 코드 Bank Code：006，지점 이름 Branch：Hong Kong Branch，지점 코드 Branch Code：391，주소 Address：Citibank Tower, Citibank Plaza, 3 Garden Road, Central, Hong Kong
> - 기타 통화 송금(For other currencies): **The Bank of New York Mellon** — SWIFT Code：IRVTUS3NXXX，주소 Address：240 Greenwich Street, New York, United States

## 저작권

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
