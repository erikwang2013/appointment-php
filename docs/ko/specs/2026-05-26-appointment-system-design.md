# 예약 서비스 시스템 설계 규범
> **Languages**: [中文](../../superpowers/specs/2026-05-26-appointment-system-design.md) · [English](../../en/specs/2026-05-26-appointment-system-design.md) · [Русский](../../ru/specs/2026-05-26-appointment-system-design.md) · [Deutsch](../../de/specs/2026-05-26-appointment-system-design.md) · [Français](../../fr/specs/2026-05-26-appointment-system-design.md) · [Español](../../es/specs/2026-05-26-appointment-system-design.md) · [Português](../../pt/specs/2026-05-26-appointment-system-design.md) · [हिन्दी](../../hi/specs/2026-05-26-appointment-system-design.md) · [العربية](../../ar/specs/2026-05-26-appointment-system-design.md) · [বাংলা](../../bn/specs/2026-05-26-appointment-system-design.md) · [Bahasa Indonesia](../../id/specs/2026-05-26-appointment-system-design.md) · [日本語](../../ja/specs/2026-05-26-appointment-system-design.md)

## 개요

3단 예약 서비스 시스템：사용자단（위챗 미니프로그램 + Flutter APP）+ 기술자 작업대（같은 APP 내 신분 전환）+ 관리 백엔드（PC Web).

## 아키텍처 결정

| 결정 | 방안 |
|------|------|
| 백엔드 아키텍처 | `admin/`（관리 백엔드 API）+ `service/`（비즈니스 API），두 서비스가 MySQL/Redis 공유 |
| 사용자단 미니프로그램 | 네이티브 위챗 미니프로그램 `apps/wechat/` |
| 사용자단 APP | Flutter `apps/flutter/`（iOS + Android) |
| 사용자 신분 | 통일 계정, 고객/기술자 신분 전환 가능 |
| 미니프로그램과 APP 관계 | 기능 완전 동일, 플랫폼 차이만 존재 |
| 관리 백엔드 프런트 | 기존 Flutter Web (`admin/apps/flutter/`) 확장 |
| 관리 백엔드 백엔드 | 기존 webman v2 (`admin/`) 확장 비즈니스 모듈 |
| 제3자 서비스 | 위챗 로그인/결제/SMS/지도 — 예약 연동 방안 |

## 시스템 아키텍처 다이어그램

```
┌──────────────────────────────────────────────────────────┐
│                       사용자 터미널 계층                     │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ 위챗 미니프로그램  │  │ Flutter APP       │              │
│  │ apps/wechat/      │  │ apps/flutter/     │              │
│  │ (네이티브 WXML/WXSS)│  │ (iOS + Android)   │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │          기능 완전 동일  │                        │
│           └──────────┬──────────┘                        │
│                      │ 고객 신분 / 기술자 신분 전환          │
├──────────────────────┼──────────────────────────────────┤
│               비즈니스 API 게이트웨이                        │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ service/ API      │  │ admin/ API        │              │
│  │ (webman v2)       │  │ (webman v2)       │              │
│  │ 사용자/주문/결제/   │  │ 관리 백엔드 인터페이스 │              │
│  │ 기술자/매장/마케팅...│  │ (구축됨 + 확장)    │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │                      │                        │
│           └──────────┬───────────┘                        │
│                      │                                    │
├──────────────────────┼──────────────────────────────────┤
│                  데이터 계층                                │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────────────┐    │
│  │ MySQL  │ │ Redis  │ │  ES    │ │ 제3자 서비스     │    │
│  │ 8.0    │ │ 캐시/   │ │ 검색   │ │ 위챗/SMS/지도   │    │
│  │        │ │ 속도제한/ │ │        │ │ (예약 연동)     │    │
│  │        │ │ Session │ │        │ │                │    │
│  └────────┘ └────────┘ └────────┘ └────────────────┘    │
└──────────────────────────────────────────────────────────┘
```

## 데이터베이스 핵심 테이블

모든 테이블은 `appointment_` 접두사, BIGINT 비자동증가 기본 키（Snowflake 생성). 민감 필드는 encryptable trait로 암복호화.

### 사용자와 신분 도메인

| 테이블명 | 설명 | 핵심 필드 |
|------|------|----------|
| `appointment_user` | 통일 사용자 테이블 | phone, password, wx_openid, wx_unionid, avatar, nickname, user_type(customer/technician), status。technician 사용자는 고객 기능도 동시 보유, 현재 활성 신분 자유 전환 가능 |
| `appointment_user_address` | 사용자 주소 | user_id, contact_name, contact_phone, province, city, district, detail, is_default |
| `appointment_technician_profile` | 기술자 프로필 | user_id, real_name, gender, id_card, id_card_front, id_card_back, avatar, rating, order_count, status(pending/approved/rejected), intro |
| `appointment_technician_schedule` | 기술자 배차 | technician_id, date, time_slots(JSON), status |
| `appointment_technician_service` | 기술자 서비스 가능 항목 | technician_id, service_id |
| `appointment_technician_earnings` | 기술자 수익 내역 | technician_id, order_id, type(commission/bonus/penalty), amount, status |
| `appointment_technician_withdrawal` | 기술자 출금 기록 | technician_id, amount, actual_amount, commission_fee, account_info, status, reviewed_at |
| `appointment_technician_attendance` | 기술자 근태 | technician_id, date, check_in_at, check_out_at, clean_photo |
| `appointment_technician_member_note` | 회원 프로필 | technician_id, user_id, content, written_at |

### 서비스와 상품 도메인

| 테이블명 | 설명 | 핵심 필드 |
|------|------|----------|
| `appointment_service_category` | 서비스 분류 | name, icon, parent_id, sort, status |
| `appointment_service` | 서비스 항목 | category_id, name, description, cover_image, images(JSON), price, duration, sales_volume, specs(JSON), status |
| `appointment_product` | 상품 | category_id, name, cover_image, price, stock, sales_volume, type, status |
| `appointment_store` | 매장 | name, address, lat, lng, phone, business_hours(JSON), images, status |

### 주문 도메인

| 테이블명 | 설명 | 핵심 필드 |
|------|------|----------|
| `appointment_order` | 주문 메인 테이블 | order_no, user_id, technician_id, store_id, total_amount, discount_amount, paid_amount, status, service_time, cancel_reason, remark |
| `appointment_order_item` | 주문 상세 | order_id, service_id, product_id, type, name, price, quantity, spec_info |
| `appointment_order_payment` | 결제 기록 | order_id, pay_type(wechat), transaction_id, amount, status, paid_at |
| `appointment_order_refund` | 환불 기록 | order_id, payment_id, refund_no, amount, ratio, reason, status |
| `appointment_order_review` | 서비스 평가 | order_id, user_id, technician_id, rating, content, images |
| `appointment_order_verification` | 핵소 기록 | order_id, code, verified_at, verified_by, location |

### 마케팅 도메인

| 테이블명 | 설명 | 핵심 필드 |
|------|------|----------|
| `appointment_coupon` | 쿠폰 정의 | name, type, amount, min_amount, total_qty, remain_qty, start_at, end_at, status |
| `appointment_user_coupon` | 사용자 쿠폰 | user_id, coupon_id, status(available/used/expired), used_at |
| `appointment_member_card` | 멤버십 카드 정의 | name, type(month/vip/times), price, duration_days, total_times, services(JSON) |
| `appointment_user_member_card` | 사용자 멤버십 카드 | user_id, card_id, start_at, end_at, total_times, used_times, status |
| `appointment_member_card_usage` | 횟수권 사용 기록 | user_card_id, order_id, service_id, used_at |
| `appointment_user_points` | 포인트 내역 | user_id, type(earn/use), points, source, order_id |
| `appointment_gift_card` | 기프트 카드 | code, type, amount_or_gift, status, used_by, used_at |
| `appointment_user_referral` | 사용자 홍보 | referrer_id, referred_user_id, reward_type, reward_amount, registered_at, first_order_at |

### 콘텐츠와 알림 도메인

| 테이블명 | 설명 | 핵심 필드 |
|------|------|----------|
| `appointment_banner` | 캐러셀 | position, image, jump_type(url/detail/none), jump_value, sort, status |
| `appointment_announcement` | 공지 | content, status, published_at |
| `appointment_platform_agreement` | 플랫폼 약관 | type(user_agreement/privacy_policy/service_agreement), title, content, version |
| `appointment_faq` | 자주 묻는 질문 | title, content, sort |
| `appointment_feedback` | 의견 피드백 | user_id, content, images, handler_reply, status(pending/handled) |
| `appointment_moment` | 모먼트 동향 | content, images, published_at |
| `appointment_notification` | 메시지 알림 | user_id, type(order/system), title, content, is_read, created_at |

### 재무 도메인（admin 측)

| 테이블명 | 설명 | 핵심 필드 |
|------|------|----------|
| `appointment_finance_transaction` | 수지 내역 | user_id, order_id, type, direction(income/expense), amount, actual_amount, commission, status |
| `appointment_technician_commission_config` | 수수료 구성 | technician_id, commission_rate, settlement_cycle |
| `appointment_withdrawal_account` | 출금 계좌 | user_id, type(wechat), account_name, account_no |
| `appointment_withdrawal_config` | 출금 제한 구성 | min_amount, reserve_amount, round_to_hundred |

## Service API 모듈

### 공개 API（인증 불필요)
- **AuthController** — 로그인/회원가입/비밀번호 찾기/게스트 모드/신분 전환
- **CaptchaController** — SMS 인증코드
- **WechatController** — 위챗 인증/로그인/결제 콜백
- **CommonController** — 약관 텍스트/회사 소개/버전 정보

### 사용자 모듈 `user/`（인증 필요)
- **ProfileController** — 개인정보/비밀번호 변경/휴대폰 재바인딩/회원탈퇴
- **AddressController** — 배송지 CRUD
- **FavoriteController** — 즐겨찾기
- **FeedbackController** — 의견 피드백
- **ReferralController** — 홍보/추천 사용자 목록

### 기술자 모듈 `technician/`（기술자 신분 + TechnicianAuth 미들웨어 필요)
- **ProfileController** — 기술자 프로필/입점 신청
- **ScheduleController** — 배차 설정
- **OrderController** — 예약 미핵소/완료/QR 스캔 핵소
- **MemberController** — 내 회원/회원 프로필
- **EarningsController** — 수익/재경비 자금
- **WithdrawalController** — 출금
- **AttendanceController** — 근태/위생 사진

### 서비스 모듈 `service/`
- **CategoryController** — 서비스 분류
- **ItemController** — 서비스/상품 목록과 상세
- **SearchController** — 검색
- **StoreController** — 매장 목록/상세

### 주문 모듈 `order/`（인증 필요)
- **CartController** — 장바구니
- **OrderController** — 주문/주문 목록/상세/취소
- **PaymentController** — 결제/환불
- **VerificationController** — QR 코드 핵소
- **ReviewController** — 평가

### 마케팅 모듈 `marketing/`（인증 필요)
- **CouponController** — 쿠폰 목록/수령/사용
- **MemberCardController** — 멤버십 카드/횟수권
- **PointsController** — 포인트
- **GiftCardController** — 기프트 카드

### 콘텐츠 모듈 `content/`
- **BannerController** — 캐러셀
- **AnnouncementController** — 공지
- **NotificationController** — 메시지 알림

### LBS 모듈
- **LocationController** — 위치/도시 전환/주변 매장

### 공통 기능 `common/`
- SnowflakeService — ID 생성
- HashidsService — ID 암복호화
- EncryptionService — 민감 데이터 암복호화
- WechatPayService — 위챗페이（예약)
- WechatAuthService — 위챗 로그인（예약)
- SmsService — SMS 서비스（예약)
- MapService — 지도 서비스（예약)

### 미들웨어
- Auth — JWT 인증（admin과 erikwang2013/jwt-webman 패키지 공유)
- TechnicianAuth — 기술자 신분 검증
- RateLimit — 속도 제한（admin과 공유)

## Admin 관리 백엔드 확장

기존 프레임워크 기반으로 컨트롤러 추가：

### 기술자 관리
- **TechnicianController** — 기술자 목록/검색/내보내기/심사/배차 관리/기술 서비스 항목 설정/강의 학습 진행도

### 사용자 관리 확장
- **MemberController** — 회원 목록/등급 설정/소비 통계

### 매장 관리
- **StoreController** — 매장 CRUD/활성·비활성화

### 서비스 관리
- **ServiceController** — 서비스 목록/CRUD/카드 항목 설계
- **ServiceCategoryController** — 분류 관리
- **ProductController** — 상품 목록/CRUD

### 쇼핑몰 관리
- **MallOrderController** — 쇼핑몰 주문/발송/애프터서비스/평가
- **SalesStatsController** — 판매 통계

### 주문 관리
- **AppointmentOrderController** — 대기 사용 주문/취소/완료 확인

### 쿠폰 활동
- **CouponController** — 쿠폰 CRUD/발급

### 재무 관리
- **FinanceController** — 주문 분배/수지 내역
- **WithdrawalController** — 기술자 출금 심사/완료
- **CommissionController** — 수수료 설정/상벌/잔액 조회
- **WithdrawalAccountController** — 출금 계좌 관리
- **WithdrawalConfigController** — 출금 제한 구성

### 콘텐츠 관리
- **BannerController** — 캐러셀 CRUD
- **AnnouncementController** — 공지 CRUD
- **FaqController** — FAQ CRUD
- **FeedbackController** — 의견 피드백 처리
- **MomentController** — 모먼트 동향 심사
- **AgreementController** — 약관 편집（사용자 약관/개인정보 약관/서비스 약관)
- **AboutController** — 회사 소개 설정

### 설정
- **SystemMessageController** — 시스템 메시지 설정
- **AdminUserController** — 서브 계정 관리（기존 RBAC 기반)

### Dashboard 확장
- 실시간 통계 카드：사용자 수/주문 총수/기술자 수/서비스 주문 수
- 꺾은선 차트：주문량/금액/일 신규 사용자/활성도
- 빠른 내비게이션：대기 처리 모듈 버튼
- 사이트 내 메시지：신규 주문 알림/환불 알림

## 사용자단 페이지 구조

위챗 미니프로그램과 Flutter APP 기능 완전 동일.

### auth/ — 인증
- login — 로그인（휴대폰/인증코드/위챗/게스트 진입)
- register — 회원가입（휴대폰+인증코드+비밀번호+추천 코드)
- forget-password — 비밀번호 찾기
- agreement — 약관 열람

### home/ — 홈
- index — 홈（캐러셀+공지+서비스 분류+추천)
- search — 검색 페이지

### service/ — 서비스
- list — 서비스 목록（분류별 필터)
- detail — 서비스 상세（기본 정보+평가+바로 예약)
- product-list — 상품 목록

### order/ — 주문
- confirm — 주문 확인（매장/기술자/시간/쿠폰/메모/약관)
- payment — 결제 페이지
- payment-success — 결제 성공
- list — 전체 주문（상태 Tab 필터)
- detail — 주문 상세
- review — 서비스 평가
- verification — QR 코드 핵소

### cart/ — 장바구니
- index — 장바구니 목록

### technician/ — 기술자（고객 시점)
- list — 기술자 목록（거리 가까운 순 정렬)
- detail — 기술자 상세（평가/서비스 가능 항목/바로 예약)
- apply — 기술자 입점 신청

### tech-work/ — 기술자 작업대（기술자 신분)
- index — 작업대 홈（오늘 주문/수입 개요)
- schedule — 배차 설정
- order-list — 내 주문（예약 미핵소/완료)
- scan-verify — QR 스캔 핵소
- member-list — 내 회원
- member-detail — 회원 상세/프로필 편집
- earnings — 내 수익
- withdrawal — 출금
- transaction-list — 거래 내역
- attendance — 근태/위생 사진 업로드
- training — 전문 교육

### user/ — 개인센터
- index — 개인정보（아바타/닉네임/멤버십 카드/즐겨찾기/쿠폰 진입)
- settings — 설정（비밀번호 변경/휴대폰 재바인딩/약관/업데이트/회원탈퇴/로그아웃)
- switch-role — 신분 전환（고객 ↔ 기술자)

### marketing/ — 마케팅
- coupon-list — 쿠폰 목록
- member-card — 내 멤버십 카드
- points — 내 포인트
- gift-card — 내 기프트 카드
- referral — 홍보（설명+QR 코드 포스터+추천 사용자 목록)

### 기타 페이지
- message/ — 메시지 목록/상세
- store/list, store/detail — 매장 목록（LBS 정렬)/상세（내비게이션)
- other/about — 회사 소개
- other/feedback — 의견 피드백
- other/official-account — 공식 계정 팔로우

### 공통 컴포넌트
- navbar, tabbar, service-card, technician-card
- coupon-popup, lbs-selector, empty-state, loading

### 신분 전환 로직
- 고객 신분 하단 내비게이션：홈 / 서비스 / 장바구니 / 주문 / 마이
- 기술자 신분 하단 내비게이션：작업대 / 주문 / 회원 / 수익 / 마이
- 「마이」 페이지에서 신분 전환 진입 제공
- 아직 기술자가 아닌 사용자가 기술자 신분으로 전환 시 입점 신청 페이지 안내

## 구매 플로우 설명

시스템에는 두 가지 상이한 구매 플로우가 있음：

### 서비스 예약 플로우（직접 주문, 장바구니 없음)
- 서비스 항목 상세 페이지 → 주문 확인（매장/기술자/시간 선택) → 결제 → 핵소
- 기술자 리소스 독점：주문 확인 페이지 진입 시 기술자 3분 잠금
- 마사지, 뷰티 등 오프라인 서비스 항목에 사용

### 상품 구매 플로우（장바구니 모드)
- 상품 목록 → 장바구니 담기 → 장바구니 확인 → 주문 제출 → 결제 → 발송/수령
- 수량 변경, 상품 삭제 지원
- 실물 상품 또는 카드권 판매에 사용

## 핵심 비즈니스 규칙

### 기술자 잠금 메커니즘
- 같은 시간에 여러 명이 한 기술자를 동시 예약 불가
- 사용자가 주문 확인 페이지 진입 시 Redis SETNX로 기술자 3분 잠금
- 예약 페이지 이탈 또는 타임아웃 시 자동 잠금 해제

### 환불 규칙
| 조건 | 환불 비율 |
|------|----------|
| 주문 후 15분 이내 또는 시작까지 >6시간 | 100% |
| 시작까지 ≤6시간 | 90% |
| 시작했지만 서비스 미확인 | 80% |
| 서비스 시작 확인 후 | 0%（환불 불가)|

### 할인 규칙
- 비수기 시간대（10-12시/17-18시/21:00 이후) 9折
- 30분 전 사전 예약 95折（쿠폰과 중첩 불가)

### 기술자 출금
- 매월 20일 출금 가능, T+1 영업일 입금
- 위챗 잔액으로 출금 지원
- 핵소 완료 미정산 주문은 3일 내 시스템 자동 확정
- 24시간 내 회원 프로필 작성 필수, 아니면 수당 없음

### 재방문 고객 보상
- 30일 내 같은 기술자 2차 소비 → 보너스 기록
- 서비스 후 위생 사진 업로드

### 포인트 규칙
- 1:100 기프트 카드 교환（백엔드 구성 가능)
- 추천 사용자가 회원가입 성공 후 주문 시 지정 포인트 획득（백엔드 설정)
