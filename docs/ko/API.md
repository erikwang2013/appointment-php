# API 설명 문서
> **Languages**: [中文](../API.md) · [English](../en/API.md) · [Русский](../ru/API.md) · [Deutsch](../de/API.md) · [Français](../fr/API.md) · [Español](../es/API.md) · [Português](../pt/API.md) · [हिन्दी](../hi/API.md) · [العربية](../ar/API.md) · [বাংলা](../bn/API.md) · [Bahasa Indonesia](../id/API.md) · [日本語](../ja/API.md)

## 개요

- **비즈니스 API** (service/): `http://localhost:8787` — 미니프로그램/APP에 비즈니스 인터페이스 제공
- **관리 백엔드 API** (admin/): `http://localhost:8787` — 관리 백엔드 Flutter Web에 인터페이스 제공
- **인증 방식**: Bearer Token (JWT), 요청 헤더 `Authorization: Bearer <token>`
- **버전 제어**: 버전은 URL 경로 접두사 `/api/v1`에 고정됨(예: `POST /api/v1/auth/login`), URL에 버전 접두사가 없으면 404
- **ID 인코딩**: 모든 요청/응답의 ID 필드는 hashids 인코딩 사용, 실제 데이터베이스 ID 외부 노출 방지
- **OpenAPI 문서**: `hg/apidoc`로 생성, 관리단말과 클라이언트 분리

| 단말 | OpenAPI 문서 주소 | 설명 |
|------|------|------|
| 관리단말 | `GET http://localhost:8787/api/docs` | 관리 백엔드 API 전체 규격(OpenAPI 3.0 JSON) |
| 클라이언트 | `GET http://localhost:8787/api/docs` | 비즈니스 API 전체 규격(OpenAPI 3.0 JSON) |

Swagger UI 등의 도구로 위 주소를 가져와 대화형 문서를 볼 수 있습니다.

- **공통 응답 형식**:

```json
{
  "code": 0,
  "message": "조작 성공",
  "data": {}
}
```

페이징 응답:
```json
{
  "code": 0,
  "message": "success",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

---

## 1. 비즈니스 API (service/ :8787)

### 1. 공개 인터페이스(인증 불필요)

#### 1.1 인증코드

**`POST /api/v1/captcha/send`** — 문자 인증코드 발송

요청:
```json
{
  "phone": "13800138000"
}
```
응답: `{"code":0,"message":"인증코드가 발송되었습니다","data":null}`

제한: 60초마다 1회만 발송 가능, 인증코드는 5분 유효.

---

#### 1.2 인증

**`POST /api/v1/auth/register`** — 휴대폰 번호 가입

요청:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "abc123",
  "confirm_password": "abc123",
  "referral_code": "A1B2C3D4"
}
```
응답:
```json
{
  "code": 0,
  "message": "가입 성공",
  "data": {
    "token": "eyJhbGciOi...",
    "user": {
      "id": "aB3xK9mQ",
      "phone": "138****8000",
      "nickname": "사용자 138****8000",
      "user_type": "customer",
      "active_role": "customer",
      "referral_code": "E5F6G7H8"
    }
  }
}
```

---

**`POST /api/v1/auth/login`** — 비밀번호 로그인

요청:
```json
{
  "phone": "13800138000",
  "password": "abc123"
}
```
응답: 가입 응답과 동일, token과 user 정보 포함.

---

**`POST /api/v1/auth/login-by-code`** — 인증코드 로그인

요청:
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```
응답: 로그인과 동일. 미가입 사용자는 자동으로 계정 생성.

---

**`POST /api/v1/auth/forget-password`** — 비밀번호 찾기

요청:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "newpass123",
  "confirm_password": "newpass123"
}
```

---

**`POST /api/v1/auth/refresh`** — Token 갱신

요청 헤더: `Authorization: Bearer <기존 token>`
응답: `{"code":0,"data":{"token":"eyJhbGciOi..."}}`

---

#### 1.3 위챗

**`POST /api/v1/wechat/mini-login`** — 미니프로그램 로그인

요청: `{"code":"위챗 로그인 code"}`
설명: 첫 로그인 후 `/api/v1/wechat/phone`을 호출해 휴대폰 번호를 바인딩해야 합니다.

---

**`POST /api/v1/wechat/phone`** — 휴대폰 번호 바인딩

요청: `{"code":"위챗 휴대폰 번호 컴포넌트 code"}`

---

**`POST /api/v1/wechat/oa-login`** — 공식 계정 로그인

요청: `{"code":"공식 계정 인증 code"}`

---

#### 1.4 공용 서비스

**`GET /api/v1/common/config`** — 공용 설정

응답: 약관 텍스트(사용자 약관/개인정보 약관/서비스 약관), 회사 소개 정보, 버전 번호 포함.

---

**`GET /api/v1/common/area`** — 도시 지역 목록

---

#### 1.5 서비스 조회

**`GET /api/v1/service/categories`** — 분류 목록

파라미터: `?parent_id=0`

---

**`GET /api/v1/service/items`** — 서비스 항목 목록

파라미터: `?category_id=&page=1&per_page=10&sort=sales`

---

**`GET /api/v1/service/detail/{id}`** — 서비스 상세

응답 포함: 이미지/이름/가격/규격/시간/판매량/평가 목록.

---

**`GET /api/v1/service/products`** — 상품 목록

**`GET /api/v1/service/stores`** — 매장 목록

파라미터: `?lat=&lng=&city=`

---

#### 1.6 기술자 조회

**`GET /api/v1/technician/list`** — 기술자 목록

파라미터: `?lat=&lng=&service_id=&page=1`
거리 가까운 순으로 정렬, 반환: 프로필/이름/평점/주문 수/즐겨찾기 수/거리/최초 예약 가능 시간/서비스 가능 여부.

---

**`GET /api/v1/technician/detail/{id}`** — 기술자 상세

응답 포함: 이미지/이름/소개/평점/거리/제공 가능 서비스 항목 목록/평가.

---

**`GET /api/v1/technician/schedule/{id}`** — 기술자 스케줄

파라미터: `?date=2026-05-26`
해당 날짜의 예약 가능 시간대와 사용 가능 상태 반환.

---

#### 1.7 콘텐츠

**`GET /api/v1/content/banners`** — 배너

파라미터: `?position=home`

**`GET /api/v1/content/articles`** — 공지/게시글 목록

파라미터: `?type=announcement&page=1`

**`GET /api/v1/content/article/{id}`** — 게시글 상세

---

#### 1.8 LBS

**`GET /api/v1/lbs/nearby-stores`** — 주변 매장

파라미터: `?lat=&lng=&radius=5000`

**`GET /api/v1/lbs/geocode`** — 역지오코딩

파라미터: `?lat=&lng=`

---

### 2. 사용자 인터페이스(JWT 인증 필요)

모든 인터페이스 요청 헤더에 `Authorization: Bearer <token>` 포함

#### 2.1 개인 정보

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/user/profile` | 개인 정보 조회 |
| PUT | `/api/v1/user/profile` | 닉네임/프로필/성별 갱신 |
| POST | `/api/v1/user/change-password` | 비밀번호 변경 (old_password/new_password/confirm_password) |
| POST | `/api/v1/user/change-phone` | 휴대폰 번호 변경 (old_code/new_phone/new_code) |
| POST | `/api/v1/user/cancel-account` | 계정 탈퇴 (비밀번호 검증 필요) |
| POST | `/api/v1/user/logout` | 로그아웃 (token 블랙리스트 추가) |
| POST | `/api/v1/user/switch-role` | 신원 전환 (role: customer/technician) |

technician으로 전환하려면 approved 상태의 기술자 프로필이 있어야 합니다.

#### 2.2 주소 관리

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/user/addresses` | 주소 목록 |
| POST | `/api/v1/user/addresses` | 주소 추가 (contact_name/contact_phone/province/city/district/detail/lat/lng/is_default) |
| GET | `/api/v1/user/addresses/{id}` | 주소 상세 |
| PUT | `/api/v1/user/addresses/{id}` | 주소 갱신 |
| DELETE | `/api/v1/user/addresses/{id}` | 주소 삭제 |

기본으로 설정하면 다른 기본 주소가 자동 해제됩니다.

#### 2.3 즐겨찾기

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/user/favorites` | 즐겨찾기 목록 (?type=service/technician) |
| POST | `/api/v1/user/favorites` | 즐겨찾기 추가 (target_type/target_id) |
| DELETE | `/api/v1/user/favorites/{id}` | 즐겨찾기 취소 |

#### 2.4 의견 피드백

`POST /api/v1/user/feedback` — 피드백 제출 (content + images 배열)

#### 2.5 홍보 추천

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/user/referral` | 홍보 정보 (추천 코드/추천 인원/첫 주문 인원/획득 포인트) |
| GET | `/api/v1/user/referral/qrcode` | 홍보 QR 코드 (추천 코드+초대 링크) |
| GET | `/api/v1/user/referral/referred-users` | 추천한 사용자 목록 |
| GET | `/api/v1/user/referral/earnings` | 유통 수수료 명세 (페이징: 추천받은 사람 닉네임/프로필/주문번호/금액/지급 시간) |

**유통 수수료**: 추천인의 첫 주문 completed 후 지급, 금액 = paid_amount × reward_rate(appointment_system_config referral.reward_rate, 기본 0.05, 비정상 값은 상수로 폴백). 행 잠금 + rewarded_at 빈값 확인 + 첫 주문 재검증 3중 멱등; 입금은 WalletTxn type=referral_reward.

#### 2.6 포인트 양도(19차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| POST | `/api/v1/user/points/transfer` | 포인트 양도 (to_user_id hashid/points) |
| GET | `/api/v1/user/points/transfers` | 양도 기록 (?direction=sent/received&page=1) |

**포인트 양도**: 수령인 hashid 디코딩+존재 404, 본인 양도 422, 수량 1-10000 422, 잔액 SUM 집계 부족 422, 일일 누적 10000 한도 422. 동시성 방어: Redis NX 잠금 points_transfer:{user} 30s → 트랜잭션 내 양쪽 마지막 거래 내역 lockForUpdate(user_id 오름차순 상호 양도 데드락 방지) → 잠금 내 잔액/한도/수령인 재검증. 거래 내역 규범: 송신자 type=consume/source=points_transfer 음수(balance=이전 스냅샷-이번), 수신자 type=earn/source=points_transfer 양수에 expires_at 포함(PointsExpiryTimer 정상 만료 가능); commit 후 수신자에게 사이트 내 알림 type='points_received'(실패 시 warn만).

#### 2.7 알림 수신 설정(19차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/user/notify-settings` | 알림 스위치 조회(5종 전체) |
| PUT | `/api/v1/user/notify-settings` | 스위치 일괄 갱신 (types: {service_reminder: 0/1, ...}) |

**알림 스위치**: appointment_user_notify_setting 테이블(user_id+type 복합 고유 키, 기본 행 없음=기본 켜짐). 5종: service_reminder 서비스 알림 / card_expiry 만료 알림(카드+쿠폰 통합 우산)/ points_expiry 포인트 만료 / marketing 마케팅(예약)/ system 시스템(끌 수 없음, PUT 강제 1). 게이트: notifySettingEnabled를 ServiceReminderTimer/ExpiryReminderTimer/PointsExpiryTimer 3개 타이머 프로세스 + 구독 이벤트 시나리오 매핑에 연결(PAY/REFUND/VERIFIED/RESCHEDULE→system 항상 발송, REMINDER→service_reminder, EXPIRY→card_expiry); 유형이 꺼져 있으면 사이트 내 알림과 구독 메시지를 모두 건너뜀.

---

### 3. 기술자 인터페이스(JWT + 기술자 신원 필요)

#### 3.1 기술자 프로필

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/technician/profile` | 기술자 프로필 조회 |
| PUT | `/api/v1/technician/profile` | 프로필 갱신 (avatar/intro/real_name/gender/id_card/id_card_front/id_card_back) |

최초 전체 입력은 입점 신청으로 간주, status=pending 심사 대기.

#### 3.2 스케줄

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/technician/schedule` | 스케줄 조회 (?start_date=&end_date=) |
| PUT | `/api/v1/technician/schedule` | 스케줄 설정 (date/time_slots/status), 시간대 중복 422「기존 스케줄과 시간 충돌」 |
| POST | `/api/v1/technician/schedule/batch` | 일괄 스케줄(23차 라운드): 기간 ≤7일 + weekdays 필터, 기존 스케줄 있는 날은 건너뜀, 응답 created/skipped |

#### 3.3 기술자 주문

`GET /api/v1/technician/orders` — 주문 목록 (?status=&page=1)

#### 3.4 수익

`GET /api/v1/technician/earnings` — 수익 개요 (today_income/pending_settlement/balance + 거래 내역 목록)

#### 3.5 출금

`POST /api/v1/technician/withdraw` — 출금 신청 (amount)
규칙: 매월 20일 출금 가능, T+1 입금, 최저 금액/100원 단위 제한은 백엔드 설정.

**진행 중 예약(2026-08-26)**: 신청 시 잔액에서 바로 진행 중(pending/approved) 예약을 차감; 승인 이체 전 settled − withdrawn − 진행 중 ≥ 출금액 재확인; 동시 승인 시 이중 출금 없음.

#### 3.6 평가 답글(18차 라운드)

`POST /api/v1/technician/review/reply/{order_id}` — 기술자 평가 답글 (reply). 평가 없음/본인 아님 통일 404(존재성 비노출); 기존 답글 422(멱등 거절, 덮어쓰지 않음); 빈 답글 422. 답글 성공 시 사용자에게 사이트 내 알림(type='review_reply').

#### 3.7 작업대

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/technician/work/today` | 오늘의 작업 목록 |
| GET | `/api/v1/technician/work/records` | 완료 기록 페이징 |
| POST | `/api/v1/technician/work/{id}/start` | 서비스 시작 |
| POST | `/api/v1/technician/work/{id}/complete` | 서비스 완료 |

**오늘의 작업**: status ∈ [confirmed, serving], service_time이 오늘이거나 빈값, service_name/price/nickname/avatar 반환.

**완료 기록**: status ∈ [serving, completed], service_end_at 내림차순, 페이징 응답에 meta 포함.

**서비스 시작/완료**: 행 잠금+상태 머신 검증, 멱등 조작. 시작 시 service_start_at 기록; 완료 시 service_end_at 기록 + 사이트 내 알림 발송. 오류 코드: 본인 아님 403, 상태 오류 422, 잘못된 hashid 422.

---

### 4. 주문 인터페이스(JWT 인증 필요)

| 메서드 | 경로 | 설명 |
|------|------|------|
| POST | `/api/v1/order` | 주문 생성 (order_type/items/store_id/technician_id/service_time/coupon_id/user_coupon_id/promotion_id/remark) |
| GET | `/api/v1/order/list` | 주문 목록 (?status=&page=1) |
| GET | `/api/v1/order/detail/{id}` | 주문 상세 |
| POST | `/api/v1/order/cancel/{id}` | 주문 취소 (reason) |
| POST | `/api/v1/order/pay/{id}` | 결제 요청 (pay_channel: wechat/balance, use_points: 포인트 현금 전환 선택) |
| POST | `/api/v1/order/refund/{id}` | 환불 신청 |
| POST | `/api/v1/order/verify/{id}` | 검증 (code: QR 코드 값) |
| POST | `/api/v1/order/reschedule/{id}` | 예약 일정 변경 (new_service_time 필수/reason 선택) |
| GET | `/api/v1/order/logistics/{id}` | 물류 추적(19차 라운드, product 주문) |
| POST | `/api/v1/order/review/{order_id}` | 평가 제출 (rating 1-5/content/images)(19차 라운드 라우트 등록) |
| POST | `/api/v1/order/review/{order_id}/append` | 평가 추평 (content/images 콤마 구분)(19차 라운드) |

**주문 상태**: pending(결제 대기) → paid(결제됨) → confirmed(확인됨) → serving(서비스 중) → completed(완료)

**주문 생성 시**: Redis SETNX로 기술자 3분 잠금, 페이지 이탈 또는 시간 초과 시 해제.

**가격 위변조 방지(2026-08-26)**: 주문 항목 금액은 모두 데이터베이스 기록 기준(target_type=service는 appointment_service 조회, product는 appointment_product 조회), 클라이언트 전달 가격은 계산에 참여하지 않음; 알 수 없는 target_type 422; target_id는 반드시 hashid 인코딩 값 전달(raw id 전달 시 디코딩 0 → 422「상품이 존재하지 않거나 판매 중지됨」); 공동구매/번개세일가도 DB 기준.

**환불 규칙**: 주문 후 15분 내 또는 시작까지 >6h 100% 환불 / ≤6h 90% / 시작 후 80% / 시작 확인 후 환불 불가.

**쿠폰 차감**: 주문 생성 시 user_coupon_id(hashid) 선택 전달. 오류 코드: 타인 쿠폰 404, 문턱 부족/이미 만료/판매 중지/이미 사용 422, 잘못된 hashid 422. 차감 2단계: 주문 시 PriceCalculator.applyCoupon이 읽기 전용 검증과 차감 금액 계산을 해 discount_amount에 기록; 결제 성공 후 consume이 쿠폰을 used 처리; 환불 시 restoreCouponAndCard 멱등 반환.

**잔액 결제와 환불**: 결제 요청 본문에 `pay_channel: "balance"` 전달 시 지갑 잔액 사용; 위챗 환불과 잔액 환불 모두 금액을 지갑 잔액으로 환충.

**포인트 현금 전환**: 결제 요청 본문에 `use_points`(정수) 선택 전달. SUM 집계로 포인트 잔액 검증(appointment_user_points의 balance 컬럼은 단일 증분 스냅샷이므로 바로 잔액으로 사용 불가), 차감액 = floor(use_points / config('app.points_rate', 100))원, 실결제 금액 = 기존 결제 금액 − 차감액(하한 0.01, 결제 금액 초과 시 결제 금액만큼만 차감해 포인트 낭비 없음). 성공 시 type=consume/source=points_offset 소비 거래 내역 작성(멱등, 재시도 중복 차감 없음). 잔액 부족 422.

**포인트 회수**: 취소/환불 시 points_offset으로 소비한 포인트 반환(type=earn/source=points_refund): 취소는 전액, 환불은 비율대로, 5개 연결점 멱등(refundOffsetPoints).

**공동구매 주문(16차 라운드)**: 주문 생성 시 `promotion_id`(hashid) 선택 전달. 검증: group_buy 유형만, 활동 유효 기간 내, 호출자는 참여자, 미만원(결성 후 잠금 422), 주문 서비스와 활동 일치; 공동구매가 = 원가 × discount_percent/100, 쿠폰/횟수권/포인트 중첩 금지(하나라도 전달 시 422). 주문에 promotion_id/participant_id 저장; 결제는 `POST /api/v1/order/pay/{id}` 완전 재사용, pay 시 활동 종료를 지연 판정(만료 미결성) → 주문 자동 취소 + 기술자 잠금 해제.

**번개세일 주문(18차 라운드, 폐지됨)**: ~~주문 생성 시 `promotion_id`(flash_sale 유형) 전달~~ — 2026-08부터 기존 프로모션 FLASH_SALE 채널 삭제, store() 프로모션 분기는 공동구매 GROUP_BUY만(비공동구매 promotion 422); 번개세일은 통일된 24차 라운드 `/api/v1/seckill` 채널로 처리(seckill_id를 store 트랜잭션 내 행 잠금 재고 차감에 주입), PromotionController::index에서 flash_sale 필터, show/join은 400 반환, `Promotion::TYPE_FLASH_SALE` 상수는 이력 데이터 호환을 위해 유지.

**예약 일정 변경(17차 라운드)**: `POST /api/v1/order/reschedule/{id}`에 new_service_time(필수) + reason(선택) 전달, 같은 기술자 시간 변경. 규칙: 본인 주문만(아님 404), appointment 유형이며 상태 pending/paid/confirmed만 변경 가능(그 외 422), 원래 서비스 시작까지 ≥ 6시간(전액 환불 창과 동일)일 때만 변경 가능. 동시성 방어: B1 order_lock(pay/cancel/refund와 같은 상호 배타 계열) → 새 시간대 기술자 잠금 Redis SETNX EX 180(동시 일정 변경 초과 판매 방지) → 트랜잭션 내 행 잠금 재조회 + B2 스케줄 충돌 DB 검증(본 주문 제외) → service_time 갱신 + appointment_order_reschedule 기록 → 원래 시간대 잠금 해제, 새 시간대 잠금은 본 주문이 보유 → SCENE_RESCHEDULE 구독 메시지(미설정 시 사이트 내 알림으로 대체). 실패 경로는 트랜잭션 롤백과 동시에 새 시간대 잠금 해제.

**물류 추적(19차 라운드)**: `GET /api/v1/order/logistics/{id}` — 본인 product 주문만 조회 가능(아님/상품 아님/미발송 통일 404). order.remark JSON 파싱(shipping_company/tracking_no/shipped_at, admin MallOrderController::ship() 발송 시 기록), parseShippingInfo/parseReceiver 이중 파싱으로 구 형식 폴백; 수령인 휴대폰 번호 마스킹 138****5678.

**평가(19차 라운드)**: `POST /api/v1/order/review/{order_id}` 평가 제출(rating 필수 1-5, content/images 선택): 본인 아님 404, 비-completed 422, 중복 평가 400. `POST /api/v1/order/review/{order_id}/append` 추평(content 필수, images 콤마 구분): 평가 없음/본인 아님 통일 404, 비-completed 422, 중복 추평 422, 빈 내용 422; 성공 시 append_content/append_images(JSON)/append_at 기록 + 기술자에게 사이트 내 알림 type='review_append', 응답에 append 필드 노출.

### 4.1 애프터서비스 인터페이스(JWT 인증 필요)

| 메서드 | 경로 | 설명 |
|------|------|------|
| POST | `/api/v1/aftersales` | 애프터서비스 신청 (order_id hashid/type: refund|exchange/reason), 본인 주문 검증 404, 상태 paid+completed만 신청 가능 422, 같은 주문 진행 중 애프터서비스 중복 제거 422 |
| GET | `/api/v1/aftersales` | 내 애프터서비스 목록 (?status=&page=1&limit=) |
| GET | `/api/v1/aftersales/{id}` | 애프터서비스 상세(소유권 검증 404) |

**애프터서비스 상태**: pending(심사 대기) → approved(승인) / rejected(거절). approved는 상태 전이만, 환불 동작은 `POST /api/v1/order/refund/{id}` 사용.

---

### 4.2 공동구매/프로모션 인터페이스(JWT 인증 필요; FLASH_SALE 폐지)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/promotions` | 활동 목록 (?type=group_buy; flash_sale은 필터되어 미반환) |
| GET | `/api/v1/promotions/{id}` | 활동 상세(참여 인원/결성 여부 포함; flash_sale 유형 400) |
| GET | `/api/v1/promotions/{id}/participants` | 참여 목록 |
| POST | `/api/v1/promotions/join/{id}` | 활동 참여(15차 라운드 보완: 응답에 discount_percent/original_price/group_price 포함; flash_sale 유형 400) |

**참여 규칙**: group_buy 만원(≥min_people) 잠금, 결성 후 새 참여 422; 만료 미만원 지연 종료(show/join 시 status 0 처리). join 후 공동구매가 주문은 「공동구매 주문(16차 라운드)」 참조. 번개세일은 더 이상 본 채널을 사용하지 않음, 「24. 번개세일 인터페이스」 참조.

---

### 5. 마케팅 인터페이스(JWT 인증 필요)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/marketing/coupons` | 쿠폰 목록 (?status=available/used/expired) |
| POST | `/api/v1/marketing/coupons/receive` | 쿠폰 수령 (coupon_id) |
| GET | `/api/v1/marketing/cards` | 멤버십 카드 목록 |
| POST | `/api/v1/marketing/cards/buy` | 멤버십 카드 구매 (card_id) |
| GET | `/api/v1/marketing/cards/my` | 내 횟수권 목록 |
| POST | `/api/v1/marketing/cards/use` | 횟수권 검증 (user_card_id/service_id/remark?) |
| GET | `/api/v1/marketing/gift-cards` | 선물 카드 목록 |
| GET | `/api/v1/marketing/gift-cards/my` | 내 선물 카드 (redeem 기록) |
| POST | `/api/v1/marketing/gift-cards/redeem` | 선물 카드 교환 (cash 유형 교환 후 지갑 잔액 충전) |
| GET | `/api/v1/marketing/points` | 포인트 거래 내역 (?type=earn/use/expire&source=order/referral/gift_card/check_in/admin) |
| GET | `/api/v1/marketing/points-exchange` | 포인트 교환 상품 목록(판매 중 + 실시간 잔여 재고 + 교환 수) |
| POST | `/api/v1/marketing/points-exchange/{id}` | 교환 (type=coupon 발권 / wallet 입금 / gift_card 카드 키 반환) |
| POST | `/api/v1/marketing/coupons/transfer` | 양도 코드 생성 (user_coupon_id: 8자리 고유 코드/7일 유효) |
| POST | `/api/v1/marketing/coupons/claim` | 양도 쿠폰 수령 (code) |
| GET | `/api/v1/marketing/coupons/transfers` | 양도 기록 (발송 pending/claimed/expired + 수령 claimed) |

**횟수권**: cards/my가 card_id/name/type/services/total_times/used_times/remaining_times/start_at/end_at/status 반환(실시간 계산). 검증 성공 시 {order_id, usage_id, remaining_times} 반환; 오류 코드: 잘못된 hashid 422, 횟수 부족 422, 이미 만료 400, 본인 아님 404, Redis 중복 방지 400.

**선물 카드**: gift-cards/my가 redeem 기록 반환 (type/amount/gift_name/status/used_at).

**포인트 규칙**: 명세 페이징, type 필터 (earn/use/expire), source 필터 (order/referral/gift_card/check_in/admin). 출석 포인트 적립(CheckIn, type=earn); 소비 포인트 적립 floor(paid_amount×1), 검증 시 지급하며 멱등; 환불 시 비율대로 포인트 회수.

**포인트 만료(17차 라운드)**: appointment_user_points.expires_at 컬럼(설정 points.expiry_days, 기본 365일, ≤0이면 만료 없음), 모든 earn은 유효기간 포함 저장; PointsExpiryTimer 타이머 프로세스 60초마다 커서 스캔으로 만료 earn 행을 찾아 type=expire 음수 차감 행 작성(source=expiry + order_id로 원래 거래 내역 추적, 3중 멱등) + 집계 사이트 내 알림「X 포인트 만료됨」; 사용 가능 잔액 SUM 기준에 expire 음수 행 포함, 만료 포인트는 현금 전환/교환 불가.

**쿠폰 양도(17차 라운드)**: transfer는 쿠폰 본인 소유/available/쿠폰 정의 미만료/양도된 적 없음을 검증하고, 8자리 혼동 문자 제거 고유 양도 코드 생성(uk_code 고유 인덱스 폴백), 7일 유효. claim 남용 방지: Redis NX 잠금(coupon_transfer_claim:{code} 30s) + 행 잠금 재검증 이중 사용 방지, uk_user_coupon 고유 인덱스로 같은 쿠폰 1회 양도 제한, 양도받은 쿠폰 재양도 불가(새 쿠폰은 양도 기록이 없어 자연 차단), 자신이 양도한 쿠폰 수령 불가 422, 수령인은 원소유자 아님; 지연 판정 만료 시 expired 처리 + 원래 쿠폰 available 복원. claim 트랜잭션 내 원래 쿠폰 used 처리 + 새 UserCoupon 생성해 수령인 바인딩(coupon_id 동일하므로 유효기간 동일) + 기록 claimed 처리.

---

### 6. 알림 인터페이스(JWT 인증 필요)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/notification` | 알림 목록 (?type=order/system&page=1) |
| PUT | `/api/v1/notification/read/{id}` | 읽음 표시 |
| PUT | `/api/v1/notification/read-all` | 전체 읽음 |

---

### 7. 지갑 인터페이스(JWT 인증 필요)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/wallet` | 지갑 잔액 + 거래 내역 페이징 |
| POST | `/api/v1/wallet/recharge` | 충전 주문 생성 (amount: 원) |
| POST | `/api/v1/wallet/recharge/{id}/pay` | 충전 주문 결제 요청 (위챗) |
| POST | `/api/v1/wallet/transfer` | 잔액 이체 (to_user_id hashid/amount/remark 선택/client_token 선택)(19차 라운드) |
| GET | `/api/v1/wallet/transfers` | 이체 기록 (?direction=out/in&page=1)(19차 라운드) |
| GET | `/api/v1/wallet/transfers/{id}` | 이체 상세(양쪽만 조회 가능, 타인 404)(19차 라운드) |

**거래 내역**: wallet_txn 유형: recharge / consume / refund / gift_card / referral_reward(유통 수수료) / referral_level2(2단계 수수료) / points_exchange(포인트 교환 입금), 페이징 반환.

**충전**: `POST /api/v1/wallet/recharge`에 amount(원) 전달해 충전 주문 생성, 충전 주문 hashid 반환. `POST /api/v1/wallet/recharge/{id}/pay`로 위챗 결제 요청, 응답에 sign_params 포함(주문 결제 방식과 동일); 결제 콜백은 R 접두사 out_trade_no로 충전 주문과 주문을 구분.

**잔액 결제**: 주문 결제 요청 본문에 `pay_channel: "balance"` 전달 시 지갑 잔액 사용; 위챗 환불과 잔액 환불 모두 금액을 지갑 잔액으로 환충.

**잔액 이체(19차 라운드)**: `POST /api/v1/wallet/transfer` — 수령인 hashid 디코딩+존재 404, 본인 이체 422, 건당 금액 0.01-1000 422(DECIMAL 비교 float 금지), 잔액 부족 422, 일일 누적 5000원 422. 동시성/멱등: Redis NX 잠금 wallet_transfer:{from} 30s로 송신자 직렬화 → 트랜잭션 내 양쪽 지갑 행을 user_id 오름차순 lockForUpdate(고정 순서 데드락 방지) → 송신자 차감 + 수신자 증가 + WalletTxn 이중 거래 내역(transfer_out/transfer_in에 balance_after 스냅샷 포함) + 이체 기록 completed + 수신자 사이트 내 알림 type='balance_received'(실패 시 로그만). client_token 선택: 성공 후 SETNX 24h 중복 제출 방지(실패 요청은 token 미기록, 재시도 가능).

---

### 8. 매장장 작업대 인터페이스(JWT 인증 필요)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/store-manager/overview` | 오늘의 개요 (오늘 주문 수/오늘 매출/진행 중/기술자 수/검증 수) |
| GET | `/api/v1/store-manager/orders` | 매장 주문 목록 (?status=&page=&limit=) |
| GET | `/api/v1/store-manager/technicians` | 기술자 목록(오늘 스케줄 포함) |
| GET | `/api/v1/store-manager/revenue` | 최근 7일 매출 집계 |

**store_id 격리**: requireStoreId()가 현재 사용자의 매장 바인딩을 강제(appointment_user.store_id), 매장 없음 403; 모든 조회는 store_id로 필터.

---

### 9. 성장 등급 인터페이스(JWT 인증 필요, 20차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/growth` | 현재 성장 개요 (balance/등급/다음 등급 차액/등급 이름) |
| GET | `/api/v1/growth/records` | 성장값 거래 내역 페이징 (?page=&limit=) |
| GET | `/api/v1/growth/levels` | 등급 목록(공개, 로그인 불필요) |

**성장값 입금**: 출석 +10; 평가 제출 +20(추평은 미입금); 소비 floor(paid) 1원당 1포인트(결제 콜백 내 상태 재검증 멱등 재사용, 중복 콜백 중복 입금 없음).

### 10. 세금계산서 인터페이스(JWT 인증 필요, 20차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| POST | `/api/v1/invoices` | 세금계산서 신청 (order_id hashid/order_type: service=서비스/points_exchange=포인트 교환/order_type 기본 service; 금액과 발행자는 서버가 산출, 위변조 불가) |
| GET | `/api/v1/invoices` | 세금계산서 목록 (?status=&page=) |
| GET | `/api/v1/invoices/{id}` | 세금계산서 상세(본인만) |

**중복 방지**: uk_order_type(order_id, order_type) 고유 키, 같은 주문 같은 유형 중복 신청 422(MySQL 1062 캡처 폴백 포함).

### 11. 고객센터 티켓 인터페이스(JWT 인증 필요, 20차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| POST | `/api/v1/tickets` | 티켓 제출 (title/content 필수) |
| GET | `/api/v1/tickets` | 티켓 목록 (?status=open/closed&page=) |
| GET | `/api/v1/tickets/{id}` | 티켓 상세(본인만, 타인 404) |
| POST | `/api/v1/tickets/{id}/close` | 티켓 종료(본인만/open만; 선택 rating 1-5 만족도 평가, 범위 초과/비정수 422, 미제공 시 NULL 호환) |

### 12. 예약 월력 인터페이스(JWT 인증 필요, 20차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/calendar/technician/{id}` | 월 보기 (?month=YYYY-MM): 스케줄 time_slots 시간 슬롯 전개 + 예약 제외 |
| GET | `/api/v1/calendar/technician/{id}/day` | 일 보기 (?date=YYYY-MM-DD): 당일 예약 가능/예약됨/불가 슬롯 명세 |

### 13. 세금계산서 발행자 인터페이스(JWT 인증 필요, 21차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| POST | `/api/v1/invoice-titles` | 발행자 저장 (title_type: personal/company; company는 tax_no 필수; 같은 사용자 같은 발행자 중복 422; 첫 항목 자동 기본) |
| GET | `/api/v1/invoice-titles` | 발행자 목록(기본 최상단) |
| PUT | `/api/v1/invoice-titles/{id}` | 발행자 수정(본인만) |
| DELETE | `/api/v1/invoice-titles/{id}` | 발행자 삭제(본인만; 기본 삭제 시 가장 오래된 항목 자동 지정) |
| POST | `/api/v1/invoice-titles/{id}/default` | 기본 설정(트랜잭션으로 같은 사용자 다른 행 초기화) |

**신청 연동**: POST /api/v1/invoices에서 선택 title_id 지원 — 발행자 파싱으로 invoice_title/tax_no/title_type 자동 반영, title_id 없으면 기존 수동 입력 경로 유지.

### 14. 조회 이력 인터페이스(JWT 인증 필요, 21차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/browse-history` | 최근 조회 서비스(서비스 이름/커버/가격/원가 join, viewed_at 내림차순, per_page 기본 15 상한 50) |
| DELETE | `/api/v1/browse-history/{item_id}` | 단건 삭제(본인만, 비정상/타인 404) |
| DELETE | `/api/v1/browse-history` | 이력 전체 삭제(본인만) |

**기록 시점**: 서비스 상세 인터페이스 접근 성공 후 자동 기록(비로그인 스킵; 중복 조회는 viewed_at만 갱신, 중복 삽입 없음).

### 15. 만 N 원 할인 활동 인터페이스(22차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/full-reduction-activities` | 진행 중인 만 N 원 할인 활동 목록(status=1이고 유효 기간 내, 할인액 내림차순; 공개 인터페이스) |

**주문 중첩 규칙**: 만 N 원 할인은 표준 주문만 적용(공동구매/번개세일 제외), 쿠폰/횟수권 차감 후 결제 금액으로 문턱(threshold) 판정, 중첩 순서 **쿠폰/횟수권 → 만 N 원 할인 → 등급 할인**; 할인액이 가장 큰 활동 선택; 할인액은 discount_amount에 합산, 메모에「만 N 원 할인: X 이상 Y 할인」추가; 할인 후 실결제 하한 0.01원.

### 16. 내 예약 ICS 내보내기(JWT 인증 필요, 22차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/order/ics` | 90일 내 유효 주문(pending/paid/confirmed/serving)을 iCal(RFC5545)로 내보내기 |

**출력**: `Content-Type: text/calendar; charset=utf-8` + `Content-Disposition: attachment; filename="my-appointments.ics"`. VEVENT: UID=주문 ID, TZID=Asia/Shanghai, 요약「예약: 서비스명」(없으면「예약」으로 대체), 설명(기술자/매장/주소, 없으면 생략), LOCATION 매장 이름; 텍스트는 RFC5545 이스케이프(\, \; \\ \n) + 75바이트 줄 접기. 주문 없으면 유효한 빈 캘린더 반환; 본인 주문만 내보냄.

### 17. 기술자 근태 인터페이스(JWT 인증 필요, 22차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| POST | `/api/v1/technician/attendance/check-in` | 출근 체크인(당일 중복 422, 고유 인덱스 동시성 폴백; >10:00 지각 표시) |
| POST | `/api/v1/technician/attendance/check-out` | 퇴근 체크인(출근 안 함/퇴근 완료 422, 행 잠금 동시성) |
| GET | `/api/v1/technician/attendance` | 해당 월 근태 목록 + 출근 일수/총 근무 시간/평균 근무 시간 집계(?month=YYYY-MM, 비정상 422) |

### 18. 개인정보 컴플라이언스 인터페이스(JWT 인증 필요, 22차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/privacy/data` | 데이터 내보내기(personal/orders/points/wallet_txns/reviews/addresses/invoices 그룹 JSON; 서버 로그는 마스킹 휴대폰 번호+건수만 기록) |
| POST | `/api/v1/privacy/close-request` | 탈퇴 신청(잔액 0 아님 / 미완료 주문 / 진행 중 티켓 422; close_status=1 + close_requested_at 설정) |
| POST | `/api/v1/privacy/close-cancel` | 탈퇴 신청 취소(close_status 1→0) |
| POST | `/api/v1/privacy/close-confirm` | 탈퇴 확인(72h 경과 후 가능; close_status=2 + close_at + phone/nickname 익명화 user{id} + status=0) |

**로그인 차단**: close_status=2인 계정 로그인 시 403「계정이 탈퇴되었습니다」.

### 19. 사용자 건강 프로필 인터페이스(JWT 인증 필요, 23차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/health-profile` | 내 건강 프로필 조회(프로필 없으면 빈 객체) |
| PUT | `/api/v1/health-profile` | 생성/갱신(upsert, 1인 1개; allergies/health_notes 상한 500자, preferred_technician_id 존재성 검증; 제공한 필드만 갱신, 응답 hashid 인코딩) |
| DELETE | `/api/v1/health-profile` | 내 프로필 삭제(본인만) |

필드: allergies(알레르기 이력)/health_notes(건강 메모)/preferred_technician_id(선호 기술자, 빈값 가능).

### 20. 지갑 결제 비밀번호 인터페이스(JWT 인증 필요, 23차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| POST | `/api/v1/wallet/pay-password/set` | 결제 비밀번호 설정(6자리 숫자 `\d{6}`; 설정된 상태에서 변경 시 기존 비밀번호 필요 422 차단) |
| POST | `/api/v1/wallet/pay-password/verify` | 결제 비밀번호 검증(정확/오류 불리언 반환, DB 미기록) |
| POST | `/api/v1/wallet/pay-password/check` | 설정 여부 조회(set: true/false) |

저장: password_hash() 해시 + pay_password_set_at, 절대 평문 저장 안 함.

### 21. 주문 상태 타임라인 인터페이스(JWT 인증 필요, 23차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/order/{id}/timeline` | 주문 상태 변경 타임라인(내림차순; 본인만, 타인 주문 404 존재성 비노출) |

로그: 제출/결제(위챗 콜백 markOrderPaid 단일 소비점)/취소/기술자 확인/환불 신청/환불 승인/서비스 시작/서비스 완료/시간 초과 자동 취소/백엔드 조작(operator=admin) 총 8종 변경.

### 22. 포인트 행운의 룰렛 인터페이스(JWT 인증 필요, 23차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/wheel/prizes` | 룰렛 상품 목록(weight/stock 민감 필드 숨김) |
| POST | `/api/v1/wheel/spin` | 1회 추첨(Redis NX + 행 잠금 동시성 방지; random_int 가중치 추첨; 포인트→earn 거래 내역 만료 시간 포함, 잔액→lockForUpdate 입금, 쿠폰→pending 수동 발급, 당첨 없음→lose; client_token 멱등) |
| GET | `/api/v1/wheel/records` | 내 추첨 기록(페이징) |

### 23. 게스트 모드 인터페이스(24차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/guest/home` | 홈 집계(배너/공지/서비스 분류/인기 서비스, Redis 캐시 svc:guest:home 300s) |
| GET | `/api/v1/guest/services` | 서비스 목록 (?category_id=hashid&sort=newest|sales|price&page/per_page≤50) |
| GET | `/api/v1/guest/services/{id}` | 서비스 상세(없음 404) |
| GET | `/api/v1/guest/stores` | 매장 목록 |
| GET | `/api/v1/guest/technicians` | 기술자 목록(심사 통과만; ?service_id=hashid 필터; 평점 내림차순) |

인증 불필요(공개 인터페이스)한 비로그인 조회 진입점.

### 24. 번개세일 인터페이스(JWT 인증 필요, 24차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/seckill` | 번개세일 활동 목록(status=1이고 시간 창 내; 판매량 = appointment_order.seckill_id 주문 수, 잔여 재고 포함) |
| GET | `/api/v1/seckill/{id}` | 활동 상세(state=not_started/ongoing/ended) |
| POST | `/api/v1/seckill/{id}/buy` | 번개세일 주문(client_token 멱등 + Redis NX 30s 동시성 방지 + 활동 검증; 재고 선차감 없음) |

**주문 규칙(2026-08-26부터)**: 재고는 통일적으로 `/api/v1/order store()` 트랜잭션 내 행 잠금으로 차감, buy는 진입 검증/멱등만 수행; 번개세일가 = seckill_price(DB 기준), 쿠폰/포인트/멤버십 카드 중첩 불가; 주문 취소는 재고 미복원; `/api/v1/order`에 seckill_id를 직접 전달해도 재고 차감.

### 25. APP 버전 확인 인터페이스(24차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/api/v1/app/version?platform=android|ios` | 최신 버전 확인(platform 비정상 422; 버전 없으면 빈 객체; 공개 인터페이스) |

응답: id/platform/version_code/version_name/force_update(1=강제)/changelog/download_url.

---

## 2. 관리 백엔드 API (admin/ :8787)

요청 헤더: `Authorization: Bearer <admin_token>`; 공개 인증 인터페이스 버전은 URL 접두사 `/api/v1`을 따름

### 대시보드

**`GET /admin/dashboard`** — 대시보드 데이터

응답: user_count / order_count / technician_count / today_revenue + 차트 데이터(주문량/금액/신규 사용자/활동도)

### 사용자 관리

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/user` | 사용자 목록 (?keyword/status/page/per_page) |
| POST | `/admin/user` | 사용자 추가 |
| GET | `/admin/user/{id}` | 사용자 상세 |
| PUT | `/admin/user/{id}` | 사용자 수정 |
| DELETE | `/admin/user/{id}` | 사용자 삭제 |
| POST | `/admin/user/batch/destroy` | 일괄 삭제 |
| POST | `/admin/user/batch/status` | 일괄 활성/비활성 |

### 멤버십 카드 관리

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/member-cards` | 카드 목록 (?keyword/status/page/per_page) |
| GET | `/admin/member-cards/{id}` | 카드 상세 |
| POST | `/admin/member-cards` | 카드 추가 (services JSON 검증) |
| PUT | `/admin/member-cards/{id}` | 카드 갱신/상·하품 |
| DELETE | `/admin/member-cards/{id}` | 카드 삭제 (사용자가 보유 중이면 거절) |

권한 ID: 365-369.

### 매장 작업대(15차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/stores/workbench-overview` | 매장 작업대 개요 (?store_id=hashid: 오늘 주문 수/오늘 매출/진행 중/기술자 수/오늘 검증, 기준은 service단말과 동일) |
| GET | `/admin/orders` | 주문 목록에 store_id 필터 추가(hashid 디코딩) |

권한 ID: 372.

### 포인트 교환 상품(16차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/points-exchange-goods` | 상품 목록 (?keyword/status/page/per_page) |
| POST | `/admin/points-exchange-goods` | 상품 추가 (type=coupon/gift_card/wallet; coupon은 hashid, wallet/gift_card는 금액 원 전달) |
| PUT | `/admin/points-exchange-goods/{id}` | 상품 갱신 |
| DELETE | `/admin/points-exchange-goods/{id}` | 상품 삭제 |
| POST | `/admin/points-exchange-goods/{id}/toggle-status` | 상·하품 전환 |
| GET | `/admin/points-exchange-goods/{id}/exchanges` | 교환 기록 목록(사용자 휴대폰 번호 + result 스냅샷 포함) |

권한 ID: 373-378.

### 수수료 기록(16차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/referral-rewards` | 수수료 기록 (?keyword=&page=&limit=, 지급된 기록만, 추천인/추천받은 사람 닉네임 또는 휴대폰 번호 필터, hashid 인코딩) |

권한 ID: 379.

### 기술자 등급(17차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/technician-tiers/logs` | 등급 변경 로그(기술자 이름과 신·구 등급명 join, hashid 인코딩, 페이징) |

권한 ID: 380.

**자동 평가**: TierRatingService::evaluate 실시간 집계(appointment_order completed 주문 수 + 평가 평균, 반올림 소수 1자리)로 profile.order_count/rating 기록, appointment_technician_tier_config(min_orders/min_rating)에 따라 높은 등급부터 매칭, 매칭 없으면 최저 등급. 승급만 지원(하급은 수수료율과 가격 계수에 영향, 백엔드 수동으로 처리; allowDowngrade=true는 수동 재평가용); 멱등(등급 일치 시 통계만 동기화); 변경 시 appointment_technician_tier_log + 사이트 내 알림. 트리거: WorkController::complete / ReviewController 평가 작성 / ProfileController 프로필 조회 지연 판정.

### 평가 답글 조회(18차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/reviews/{id}/reply` | 평가 답글 상세(decodeId → find → 404 → decorate 출력; 미답글 시 reply='', reply/replied_at은 toArray로 노출; 정적 라우트를 resource보다 먼저) |

권한 ID: 381(slug 'get.admin/reviews/{id}/reply').

### 세금계산서 관리(20차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/invoices` | 세금계산서 목록 (?status=pending/issued/rejected&page=) |
| POST | `/admin/invoices/{id}/issue` | 발행 (invoice_no 필수, status→issued + issued_at; 멱등: 이미 발행 422) |
| POST | `/admin/invoices/{id}/reject` | 반려 (reject_reason 필수, status→rejected; pending만 반려 가능) |

권한 ID: 382 목록 / 383 발행 / 384 반려.

### 티켓 관리(20차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/tickets` | 티켓 목록 (?status=&page=, 정적 라우트를 resource보다 먼저 정의해 shadow 방지) |
| POST | `/admin/tickets/{id}/reply` | 티켓 답변 (content 필수, reply_content/replied_at 기록, 티켓 open으로 복귀) |
| GET | `/admin/tickets/satisfaction` | 만족도 집계(21차 라운드): total/rated_count/unrated_count/average 소수 1자리/1-5성 distribution 없는 별 0 보충; 정적 라우트를 resource보다 먼저 |

권한 ID: 385 티켓 답변 / 387 티켓 목록 조회 / 388 티켓 만족도 통계.

### 평가 이미지 심사(21차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/review-audit` | 이미지 포함 평가 목록(JSON_LENGTH(images)>0, ?status=visible/hidden&page=, 사용자 닉네임과 기술자 이름 join, ID hashid 인코딩) |
| POST | `/admin/review-audit/{id}/hide` | 평가 숨김(visible만 숨길 수 있고, 아니면 422; 숨긴 후 사용자단말 기술자 평가 목록 자동 비노출) |
| POST | `/admin/review-audit/{id}/restore` | 평가 복원(hidden만 복원 가능, 아니면 422) |

권한 ID: 389 목록 / 390 숨김 / 391 복원.

### 2단계 수수료 기록(20차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/referral-level2` | 2단계 수수료 기록(1단계 추천인과 2단계 추천인 닉네임 join, 페이징) |

권한 ID: 386. 지급 규칙: 주문 결제 후 1단계 추천인의 추천인에게 paid×level2_rate(시스템 설정 referral.level2_rate 기본 0.02) 지급, uk_order_referred 멱등 중복 방지.

### 근태 관리(22차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/attendance` | 근태 기록 (?date=YYYY-MM&name=기술자 이름&page=; real_name join, ID hashid 인코딩) |
| GET | `/admin/attendance/stats` | 기술자별 그룹 통계(체크인 일수/총 근무 시간/평균 근무 시간; ?date=YYYY-MM, 비정상 422) |

권한 ID: 392 목록 / 393 통계.

### 만 N 원 할인 활동 관리(22차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/full-reduction-activities` | 활동 목록(페이징) |
| POST | `/admin/full-reduction-activities` | 추가(threshold/reduction/title/status/start_at/end_at) |
| PUT | `/admin/full-reduction-activities/{id}` | 수정 |
| POST | `/admin/full-reduction-activities/{id}/toggle-status` | 상·하품 |
| DELETE | `/admin/full-reduction-activities/{id}` | 삭제(confirmPassword 포함) |

권한 ID: 396 목록 / 397 추가 / 398 수정 / 399 상·하품 / 400 삭제(권한 레코드 1개는 method.path slug 1개에 대응하므로 5개 라우트 5개).

### 분배금 기록(22차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/profit-sharing` | 분배금 기록(leftJoin 주문번호/기술자 닉네임, ?status&order_no&technician_name&page=, hashid 인코딩) |

권한 ID: 394. 서버 로직: appointment_system_config group=profit_sharing(enabled/receiver_ratio); 미활성 disabled 대체 로그만; 활성 시 결제 성공 자동 분배금 요청(금액=실결제×receiver_ratio 기본 0.7, 같은 주문 pending/success 멱등 스킵); 자격 증명 없으면 HTTP 미실행, 요청 구조 로그 기록.

### 포인트 룰렛 관리(23차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/lucky-wheel` | 룰렛 상품 목록(weight/stock 포함, 페이징) |
| POST | `/admin/lucky-wheel` | 상품 추가(이름/유형 points/balance/coupon/none/가중치/재고/이미지) |
| GET/PUT | `/admin/lucky-wheel/{id}` | 상세 / 수정 |
| DELETE | `/admin/lucky-wheel/{id}` | 삭제 |
| POST | `/admin/lucky-wheel/{id}/toggle-status` | 상·하품 |
| GET | `/admin/lucky-wheel/records` | 추첨 기록(?status&page=, 사용자 닉네임/상품명 포함) |

권한 ID: 401-406. 정적 라우트 `/lucky-wheel/records`와 `/lucky-wheel/{id}/toggle-status`는 resource보다 먼저 등록해 {id} shadow 방지.

### 재방문 고객 보상 관리(24차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/return-customer/config` | 설정 조회(enabled 스위치 / ratio 비율) |
| PUT | `/admin/return-customer/config` | 설정 갱신(enabled in:0,1; ratio between:0.01,1) |
| GET | `/admin/return-customer/rewards` | 보상 기록 목록(?keyword 기술자 이름/주문번호/사용자 닉네임, type=return_customer 페이징) |

권한 ID: 412-414. 보상 규칙: 사용자가 같은 기술자에게 30일 내 2차 소비(주문 완료) 시 보너스 = 실결제 × ratio(기본 0.05), appointment_technician_earnings(type=return_customer, status=pending) 기록으로 수수료 정산 체인과 통일 정산; 같은 주문 멱등 중복 지급 없음.

### 번개세일 활동 관리(24차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/seckill` | 활동 목록(페이징) |
| POST | `/admin/seckill` | 활동 추가(name/service_id/seckill_price/original_price/stock/start_at/end_at) |
| GET | `/admin/seckill/{id}` | 활동 상세 |
| PUT | `/admin/seckill/{id}` | 수정 |
| DELETE | `/admin/seckill/{id}` | 삭제 |
| POST | `/admin/seckill/{id}/toggle-status` | 상·하품 |
| GET | `/admin/seckill/{id}/orders` | 번개세일 주문 목록 |

권한 ID: 407-411, 420. 판매량 = appointment_order.seckill_id 주문 수; 재고 행 잠금 차감, 매진 차단.

### APP 버전 관리(24차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/versions` | 버전 목록 |
| POST | `/admin/versions` | 버전 추가(platform/version_code/version_name/force_update/changelog/download_url/status) |
| PUT | `/admin/versions/{id}` | 수정 |
| DELETE | `/admin/versions/{id}` | 삭제 |

권한 ID: 416-419. 업데이트 확인 인터페이스 /api/v1/app/version은 status=1 중 최신(updated_at/id 최대) 버전 조회.

### 스케줄 내보내기(24차 라운드)

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/technician-schedule/export` | 스케줄 CSV 내보내기(UTF-8 BOM, Excel 직접 열기; start_date/end_date 필수이고 기간 ≤31일; technician_id 선택 hashid) |

권한 ID: 415. 컬럼: 기술자 ID/기술자 이름/날짜/시간대 명세(time_slots JSON 파싱 "09:00-12:00, 14:00-18:00").

### 역할 권한

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET/POST/PUT/DELETE | `/admin/role` | 역할 CRUD |
| GET/POST/PUT/DELETE | `/admin/permission` | 권한 CRUD(트리 구조) |

### 시스템 설정

| 메서드 | 경로 | 설명 |
|------|------|------|
| GET | `/admin/config` | 설정 목록 |
| POST | `/admin/config` | 설정 추가 (group/key/value/type/description) |
| PUT | `/admin/config/{id}` | 설정 수정 |
| DELETE | `/admin/config/{id}` | 설정 삭제 |

### 조작 로그

**`GET /admin/log`** — 로그 조회

파라미터: `?user_id/action/source/start_date/end_date/page`

`source` 필드: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### 내보내기

| 메서드 | 경로 | 설명 |
|------|------|------|
| POST | `/admin/export/excel` | Excel 내보내기 (type: users/technicians/orders/finance). 민감 필드 자동 마스킹 |
| POST | `/admin/export/pdf` | PDF 패널 내보내기 (type: dashboard) |

### 파일 업로드

**`POST /admin/upload`** — 파일 업로드 (multipart/form-data)

### 마이페이지

| 메서드 | 경로 | 설명 |
|------|------|------|
| PUT | `/admin/profile` | 개인 정보 수정 |
| PUT | `/admin/profile/password` | 비밀번호 변경 |
| POST | `/admin/profile/logout` | 로그아웃 |

### 가져오기

**`POST /admin/import/users`** — 사용자 일괄 가져오기 (Excel)

### 모니터링

| 메서드 | 경로 | 인증 | 설명 |
|------|------|------|------|
| GET | `/health` | 없음 | 헬스 체크 |
| GET | `/metrics` | 없음 | Prometheus 지표 |
| GET | `/.well-known/security.txt` | 없음 | 보안 연락처(RFC 9116) |
| GET | `/api/docs` | 없음 | API 문서 |

---

## 3. 공통 설명

### 오류 코드

| code | 설명 |
|------|------|
| 0 | 성공 |
| 401 | 미로그인 또는 Token 만료 |
| 403 | 권한 없음 |
| 404 | 리소스 없음 |
| 422 | 파라미터 검증 실패 |
| 429 | 요청이 너무 빈번함 |

### ID 인코딩

- 모든 API 응답의 `id`와 `*_id` 필드는 hashids 인코딩
- 요청에 포함되는 `id` 파라미터도 hashids 인코딩 형식 사용
- 프런트엔드는 인코딩 문자열을 직접 사용, 수동 디코딩 불필요

### 휴대폰 번호 마스킹

응답의 휴대폰 번호 형식: `138****8000`. Excel 내보내기도 동일 처리.

### 데이터 암호화

- API 계층: 응답의 민감 필드는 `erikwang2013/encryption`으로 암호화
- DB 계층: 휴대폰 번호/주민등록번호/위챗 ID 등은 `erikwang2013/encryptable`로 자동 암호화/복호화

### 환경 변수 설정

| 변수 | 설명 |
|------|------|
| WECHAT_SUBSCRIBE_TEMPLATE_ID | 예약 알림 구독 메시지 템플릿 ID |
| WECHAT_SUBSCRIBE_TEMPLATE_PAID | 결제 성공 구독 메시지 템플릿 ID |
| WECHAT_SUBSCRIBE_TEMPLATE_REFUND | 환불 구독 메시지 템플릿 ID |
| WECHAT_SUBSCRIBE_TEMPLATE_VERIFIED | 검증 구독 메시지 템플릿 ID |
| WECHAT_SUBSCRIBE_TEMPLATE_REMINDER | 서비스 시작 전 알림 구독 메시지 템플릿 ID(18차 라운드) |
| WECHAT_SUBSCRIBE_TEMPLATE_EXPIRY | 멤버십 카드/쿠폰 만료 알림 구독 메시지 템플릿 ID(18차 라운드) |

구독 메시지 템플릿 미설정 시 사이트 내 알림으로 자동 대체.

**구독 메시지 시나리오**: SCENE_PAY(결제 성공) / SCENE_REFUND(환불 입금) / SCENE_VERIFIED(검증 성공) / SCENE_RESCHEDULE(일정 변경 성공) / SCENE_REMINDER(서비스 시작 전 알림, 18차 라운드) / SCENE_EXPIRY(만료 알림, 18차 라운드). 푸시 성공 시에만 push_sent_at 기록, 실패 시 다음 라운드 재시도.

**충전 입금 알림(18차 라운드)**: 위챗 충전 콜백(R 접두사 주문번호) 트랜잭션 내 사이트 내 알림 type='wallet_recharge'「¥X.XX 충전 완료」; 콜백 멱등 재사용(최초 pending→paid만 트리거), 상태 변경과 같은 트랜잭션 원자 커밋, 작성 실패 시 메인 흐름 차단 안 함.
