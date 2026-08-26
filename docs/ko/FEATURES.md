# 기능 설명
> **Languages**: [中文](../FEATURES.md) · [English](../en/FEATURES.md) · [Русский](../ru/FEATURES.md) · [Deutsch](../de/FEATURES.md) · [Français](../fr/FEATURES.md) · [Español](../es/FEATURES.md) · [Português](../pt/FEATURES.md) · [हिन्दी](../hi/FEATURES.md) · [العربية](../ar/FEATURES.md) · [বাংলা](../bn/FEATURES.md) · [Bahasa Indonesia](../id/FEATURES.md) · [日本語](../ja/FEATURES.md)

> **프로젝트 상태**: 전체 완료 ✅ | 컨트롤러 109개 | 모델 103개 | 테스트 344개(service 240 / admin 104) | WebSocket | 결제 콜백 | 순번 호출 | 평가 | 커뮤니티

## 1. 사용자단말(위챗 미니프로그램 + Flutter APP)

사용자단말 미니프로그램과 APP의 기능은 완전히 동일합니다. 통합 계정으로 고객/기술자 신원 전환을 지원합니다.

### 1. 인증

| 기능 | 설명 |
|------|------|
| 휴대폰 번호 가입 | 휴대폰 번호+인증코드+비밀번호+비밀번호 확인, 추천 코드 지원 |
| 비밀번호 로그인 | 가입된 휴대폰 번호+비밀번호 |
| 인증코드 로그인 | 가입된 휴대폰 번호+인증코드 |
| 위챗 로그인 | 위챗 인증 로그인, 첫 로그인 시 휴대폰 번호 바인딩 필요 |
| 게스트 모드 | 조회만 가능, 주문은 가입 필요 |
| 비밀번호 찾기 | 인증코드로 비밀번호 변경 |
| 사용자 약관/개인정보 약관 | 관리 백엔드에서 수정 가능, 가입 시 표시 |

### 2. 홈

| 기능 | 설명 |
|------|------|
| LBS 위치 | 현재 지역을 파악해 해당 지역 서비스 표시, 도시 전환 지원 |
| 배너 | 자동 슬라이드, 관리 백엔드에서 점프 설정(웹페이지/상세/동작 없음) |
| 공지 | 스크롤 재생, 클릭해 목록 보기, 관리 백엔드에서 추가 |
| 서비스 카테고리 | 이미지/이름/가격/판매량, 클릭해 상세 진입 |
| 신규 사용자 쿠폰 | 가입 시 자동 지급 |

### 3. 서비스 항목

| 기능 | 설명 |
|------|------|
| 기본 정보 | 이미지/이름/가격/판매량/규격/서비스 시간/항목 상세 |
| 사용자 평가 | 평가 내용 표시, 더 보기 가능 |
| 예약 서비스 | 주문 확인 페이지 진입 |
| 매장 선택 | 방문 매장 주소(내비게이션)/영업 시간/연락처 |
| 기술자 선택 | 기술자 이름/프로필/평점 |
| 서비스 시간 | 예약 시간대 선택 |
| 비수기 9折 할인 | 10-12시/17-18시/21:00 이후 |
| 사전 예약 95折 할인 | 30분 전 예약, 쿠폰과 중첩 불가 |
| 쿠폰 | 사용 가능 금액 표시, 사용/미사용 |
| 메모 | 서비스 요청 메모(글자 수 제한) |
| 서비스 약관 | 제출 전 열람 확인 |

### 4. 상품 검색과 장바구니

| 기능 | 설명 |
|------|------|
| 상품 검색 | 이름 검색 |
| 카테고리 필터 | 분류별 검색 |
| 상품 상세 | 구매 가능 수량/즐겨찾기/공유/장바구니 담기/바로 구매 |
| 장바구니 | 선택/삭제/수량 변경 |

### 5. 주문

| 기능 | 설명 |
|------|------|
| 전체 주문 | 상태 탭별 조회 |
| 결제 대기 | 조회/결제 |
| 발송 대기/직접 수령 | 발송 재촉/주문 취소/조회 |
| 수령 대기 | 물류 정보/수령 확인 |
| 평가 대기 | 주문 상세/텍스트+이미지 평가 |
| 완료 | 주문 정보 조회 |
| 환불 규칙 | 주문 후 15분 내 또는 >6h 100% 환불 / <6h 90% / 시작 후 80% / 확인 후 불가 |

### 6. 기술자(고객 관점)

| 기능 | 설명 |
|------|------|
| 기술자 목록 | 가까운 순/프로필/이름/주문 수/평점/즐겨찾기/거리/예약 가능 시간/바로 예약 |
| 기술자 상세 | 이미지/이름/거리/주문/평가/즐겨찾기/제공 가능 서비스 목록 |
| 기술자 입점 | 정보를 입력해 기술자 신청, 기술자단말 APP 다운로드 |

### 7. 기술자 작업대(기술자 신원 전환 후)

| 기능 | 설명 |
|------|------|
| 오늘의 개요 | 오늘 주문/수입 총괄 |
| 스케줄 설정 | 날짜별 예약 가능 시간대 설정 |
| 내 주문 | 예약됨 미검증/완료 |
| QR 검증 | 사용자 QR 코드 스캔해 횟수 검증 |
| 회원 관리 | 서비스한 회원 목록/수업 소모 데이터/횟수권/프로필 수정 |
| 수익 관리 | 오늘 수입/정산 중/지갑 잔액 |
| 진행 중 자금 | 검증 완료 미정산, 3일 자동 확정 |
| 출금 | 매월 20일, T+1 영점 지갑 입금; 관리단말 심사, 금액 ≥500 2단계 승인(매장장→재무); 신청 시 잔액 진행 중 예약, 승인 이체 전 재확인, 동시 승인 이중 출금 방지(2026-08-26 강화) |
| 근태 | 출근/퇴근 체크인/위생 사진 업로드 |
| 재방문 고객 보상 | 30일 내 2차 소비 보너스 기록 |
| 전문 교육 | 영상 강좌/텍스트 강좌 |
| 오늘의 작업 | WorkController today: 오늘의 할 일 실시간 조회 |
| 완료 기록 | WorkController records: 이력 완료 기록 |
| 서비스 시작/완료 | WorkController start/complete: 행 잠금+상태 머신 가드+멱등, 완료 후 사이트 내 알림 자동 작성 |
| 미니프로그램 기술자 작업대 | tech-work 3개 탭: QR 검증/오늘의 작업/완료 기록 |

### 8. 마이페이지

| 기능 | 설명 |
|------|------|
| 개인 정보 | 프로필/닉네임/휴대폰 번호 |
| 신원 전환 | 고객 ↔ 기술자 |
| 메시지 알림 | 사이트 내 알림(erik_notification); 메시지 센터 페이지: 페이징/당겨서 새로고침/읽음 하이라이트/읽음 표시/전체 읽음 |
| 내 멤버십 카드 | 월간 카드/VIP 연간 카드/횟수권(만료/횟수/사용/잔여) |
| 내 포인트 | 적립 기록/사용 가능 포인트/사용 기록(1:100 선물 카드 교환); 출석/소비 포인트 적립, 환불 시 비율대로 회수, 명세 페이징+type/source 필터 |
| 내 선물 카드 | 현금 카드/실물 선물; cash 유형 교환은 지갑에 직접 충전 |
| 쿠폰 | 수령한 사용 가능/사용됨/만료됨 |
| 내 즐겨찾기 | 즐겨찾기한 서비스 항목 |
| 공식 계정 팔로우 | QR 코드 팝업, 길게 눌러 저장 |
| 사용자 홍보 | 홍보 설명/QR 포스터/추천 사용자 목록/포인트 보상 |
| 의견 피드백 | 텍스트+이미지 제출, 24h 내 답변 |
| 회사 소개 | LOGO/소개/고객센터 전화/홈페이지/이메일 |

### 9. 설정

| 기능 | 설명 |
|------|------|
| 비밀번호 변경 | 현재 비밀번호+새 비밀번호+새 비밀번호 확인 |
| 휴대폰 번호 변경 | 현재 휴대폰 인증코드+새 휴대폰 인증코드 |
| 사용자 약관 | 텍스트 표시, 백엔드에서 수정 가능 |
| 개인정보 약관 | 텍스트 표시, 백엔드에서 수정 가능 |
| 업데이트 확인 | 버전 번호+업데이트 |
| 계정 탈퇴 | 탈퇴 설명+확인 조작 |
| 로그아웃 | 로그인 상태 초기화 |

### 10. 적립 지갑(6차 라운드)

| 기능 | 설명 |
|------|------|
| 지갑 잔액 | GET /api/wallet 잔액+거래 내역(user_wallet/wallet_recharge/wallet_txn 테이블) |
| 충전 | POST /api/wallet/recharge 충전 주문 생성; POST /api/wallet/recharge/{id}/pay 위챗페이 충전, 콜백은 R 접두사 주문번호 사용 |
| 잔액 결제 | 주문 결제 채널 pay_channel=balance |
| 환불 환충 | 위챗/잔액 환불 시 잔액 자동 환충(refundToBalance / creditRefundToWallet) |

### 11. 구독 메시지(6+8차 라운드)

| 기능 | 설명 |
|------|------|
| 구독 시나리오 | 주문 이벤트 3개 시나리오: 결제 성공 / 환불 입금 / 검증 성공 |
| 멱등 | push_sent_at 표시로 중복 푸시 방지 |
| 대체 | 구독 템플릿 미설정 시 사이트 내 알림으로 자동 대체 |

### 12. 횟수권 검증 클로즈드 루프(8차 라운드)

| 기능 | 설명 |
|------|------|
| 내 횟수권 | GET /api/marketing/cards/my 실시간 used_up/expired 계산 |
| 검증 차감 | POST /api/marketing/cards/use: Redis NX 멱등 + lockForUpdate 행 잠금, completed 주문 + OrderItem + OrderPayment(pay_type='card') 직접 생성 |

### 13. 쿠폰 차감(9차 라운드)

| 기능 | 설명 |
|------|------|
| 주문 시 쿠폰 선택 | 주문 시 user_coupon_id 전달 가능, PriceCalculator.applyCoupon 읽기 전용 검증+금액 계산 |
| 할인 유형 | fixed 고정 금액 / percent 백분율, min_amount 만 N 원 문턱 |
| 소비와 반환 | 결제 성공 시 consume used 처리; 환불 시 restoreCouponAndCard 멱등 반환 |

### 14. 선물 카드(9차 라운드)

| 기능 | 설명 |
|------|------|
| 교환 | redeem: cash 유형 지갑 충전(행 잠금 이중 입금 방지, WalletTxn type='gift_card'), gift 유형은 표시만 |
| 내 선물 카드 | GET /api/marketing/gift-cards/my |

### 15. 포인트 체계(9+10차 라운드)

| 기능 | 설명 |
|------|------|
| 출석 포인트 | CheckIn 매일 출석 |
| 소비 포인트 | 검증 시 floor(paid×1), order_id 멱등, balance 스냅샷 |
| 환불 회수 | clawbackOrderPoints 비율별 회수(3곳 연결) |
| 포인트 현금 전환 | 결제 시 use_points 전달, 100포인트=1원(config app.points_rate), SUM 집계 잔액 검증, 소비 거래 내역 source=points_offset 멱등 |
| 포인트 회수(15차 라운드) | 취소/환불 시 points_offset 포인트 반환: refundOffsetPoints 5개 연결점(doCancel 3개 경로/doRefund 위챗 트랜잭션/creditRefundToWallet/completeOneRefundCompensation), source=points_refund 멱등 |
| 포인트 명세 | GET /api/marketing/points 페이징 + type/source 필터, type은 earn으로 통일 |

### 16. 미니프로그램 주문 체인(10차 라운드)

| 기능 | 설명 |
|------|------|
| 서비스 상세 페이지 | service/detail |
| 주문 확인 페이지 | order/confirm: 쿠폰 선택/문턱 회색 처리/클라이언트 예상 금액 → POST /order → 위챗/잔액 결제 |
| 페이지 규모 | 미니프로그램 현재 총 20개 페이지 |

### 17. 사용자 측 3개 진입점(10차 라운드)

| 기능 | 설명 |
|------|------|
| 즐겨찾기 | favorite 즐겨찾기 페이지(user 페이지 진입점) |
| 홍보 | referral: 초대 코드/링크 복사/추천 사용자 목록 |
| 피드백 | feedback 피드백 폼 |

### 18. 구독 메시지 권한(14차 라운드)

| 기능 | 설명 |
|------|------|
| 구독 권한 | utils/subscribe.js에서 템플릿 ID 중앙 관리(키 이름은 서버 erik_system_config.wechat_app.template_ids와 정렬) |
| 트리거 시나리오 | 예약 성공/결제 성공 후 제스처 콜백에서 wx.requestSubscribeMessage 호출, 템플릿 ID 미설정 또는 사용자 거절 시 모두 무음 처리 |
| 서버 체인 | WechatTemplateMessageService 발송 + NotificationReminderService 예약 2h~1h 전 알림 + AutoCancelTimer 프로세스 스캔 |

### 19. 애프터서비스 교환/반품(14차 라운드)

| 기능 | 설명 |
|------|------|
| 애프터서비스 신청 | POST /api/aftersales: type=refund/exchange, 본인 주문/paid+completed/같은 주문 중복 제거 검증 |
| 내 애프터서비스 | GET /api/aftersales 페이징 목록 + GET /api/aftersales/{id} 상세 |
| 심사 흐름 | 관리단말 approve/reject(rejected는 remark 필수); approved는 상태 전이만, 환불은 기존 주문 환불 API 사용 |

### 20. 공동구매/번개세일(15차 라운드)

> 2026-08부터 FLASH_SALE 채널 폐지: PromotionController::index에서 flash_sale 필터, show/join은 400 반환, 번개세일은 통일된 "43. 번개세일(24차 라운드)" 채널로 처리; `Promotion::TYPE_FLASH_SALE` 상수는 이력 데이터 호환을 위해 유지. 본 절 및 "27. 번개세일 주문"은 이력 기록입니다.

| 기능 | 설명 |
|------|------|
| 활동 목록/상세 | GET /api/promotions + /api/promotions/{id}, type 필터 group_buy/flash_sale |
| 참여 | POST /api/promotions/join/{id}: Redis NX 잠금 초과 판매 방지(flash_sale은 max_people이 재고 상한), 중복 참여 422, group_buy 인원 만원 잠금, 만료 미만원 지연 종료(show/join 시 status 0 처리) |
| 참여 목록 | GET /api/promotions/{id}/participants |
| 상태 수정 | PromotionParticipant 상태를 정수 상수 0/1/2/3으로 변경(엄격 모드에서 join 1366 손상 수정) |

### 21. 공동구매 결성 주문(16차 라운드)

| 기능 | 설명 |
|------|------|
| 공동구매가 | join 응답에서 discount_percent/original_price/group_price 반환 |
| 공동구매 주문 | POST /api/order에 promotion_id 전달: group_buy만/활동 유효/호출자가 참여자/미만원/서비스 일치 검증; 공동구매가=원가×discount_percent/100, 쿠폰/횟수권/포인트 중첩 금지(422) |
| 주문 표시 | erik_order에 promotion_id/participant_id 컬럼 + 인덱스 추가 |
| 결성 실패 처리 | 만료 미만원→활동 종료+해당 활동 pending 주문 일괄 취소(멱등); pay()에서 지연 판정으로 이미 종료되면 주문 자동 취소 + 기술자 잠금 해제 |

### 22. 유통 수수료(16차 라운드)

| 기능 | 설명 |
|------|------|
| 지급 규칙 | 추천인의 첫 주문 completed 후 지급: 금액=paid_amount×reward_rate(erik_system_config referral.reward_rate 기본 0.05, 비정상 값은 상수로 폴백), >0일 때만 지급 |
| 연결점 | ReferralRewardService::handleOrderCompleted를 WorkController::complete 트랜잭션 내 연결(serving→completed 유일 진입점, 검증 verify는 serving까지만 가므로 트리거 안 됨), 실패 시 전체 롤백 + 재시도 가능 |
| 멱등 | erik_user_referral 행 잠금 lockForUpdate + rewarded_at 빈값 확인 + 잠금 내 첫 주문 재검증(동시/중복 호출에도 한 번만 지급) |
| 입금 | 지갑 행 잠금 누적 + WalletTxn type='referral_reward'(balance_after + 주문번호 remark); 추천 기록에 reward_type/reward_amount/rewarded_at/first_order_at 작성 |
| 명세 | GET /api/user/referral/earnings 페이징(추천인 닉네임/프로필/주문번호/금액/시간) |

### 23. 포인트 교환 몰(16차 라운드)

| 기능 | 설명 |
|------|------|
| 교환 상품 | erik_points_exchange_goods: type=coupon/gift_card/wallet, points_cost/value(DECIMAL(25,2) 설계 ID 정밀도 손실 방지)/stock/status |
| 상품 목록 | GET /api/marketing/points-exchange: 판매 중 상품 + 실시간 잔여 재고 + 교환 수 |
| 교환 | POST /api/marketing/points-exchange/{id}: Redis NX 잠금 + 상품 행 잠금 초과 교환 방지; 포인트 SUM 검증(부족 422) + UserPoints type='consume' source='exchange' 차감; coupon 발권 / wallet 잔액 입금(WalletTxn points_exchange) / gift_card 카드 키 반환 |
| 멱등 | uk_user_goods 고유 인덱스로 같은 사용자 같은 상품 1회 제한 + 잠금 내 재검증 + 1062 폴백; 교환 기록 스냅샷 erik_user_points_exchange |

### 24. 예약 일정 변경(17차 라운드)

| 기능 | 설명 |
|------|------|
| API | POST /api/order/reschedule/{id}: new_service_time(필수) + reason(선택), 같은 기술자 시간 변경 |
| 규칙 | 본인 주문만(아님 404); appointment 유형이며 상태 pending/paid/confirmed만(그 외 422); 원래 서비스 시작까지 ≥ 6시간(전액 환불 창과 동일) |
| 동시성 방어 | B1 order_lock(pay/cancel/refund와 같은 상호 배타 계열) → 새 시간대 기술자 잠금 Redis SETNX EX 180(동시 일정 변경 초과 판매 방지) → 트랜잭션 내 행 잠금 재조회 + B2 스케줄 충돌 DB 검증(본 주문 제외) |
| 마무리 | service_time 갱신 + erik_order_reschedule 기록(reason 포함) + 원래 시간대 잠금/새 시간대 잠금 본 주문 보유분 해제; 실패 시 트랜잭션 롤백과 동시에 새 시간대 잠금 해제 |
| 알림 | SCENE_RESCHEDULE 구독 메시지(템플릿 미설정 시 사이트 내 알림 "일정 변경 성공"으로 대체) + pushOrderUpdate |

### 25. 쿠폰 양도(17차 라운드)

| 기능 | 설명 |
|------|------|
| API | POST /api/marketing/coupons/transfer(user_coupon_id) 8자리 혼동 문자 제거 고유 양도 코드 생성(uk_code 폴백, 7일 유효); POST /api/marketing/coupons/claim(code) 수령; GET /api/marketing/coupons/transfers 발송(pending/claimed/expired)+수령(claimed) 페이징 |
| 검증 | 쿠폰 본인 소유/available/쿠폰 정의 미만료/양도된 적 없음(422); 자신이 양도한 쿠폰 수령 불가, 수령인은 원소유자 아님 |
| 남용 방지 | Redis NX 잠금 coupon_transfer_claim:{code}(30s) + 트랜잭션 내 행 잠금 재검증 이중 사용 방지; uk_user_coupon 고유 인덱스로 같은 쿠폰 양도 1회 제한; 양도받은 쿠폰 재양도 불가(새 쿠폰은 양도 기록이 없어 자연 차단); 지연 판정 만료 시 expired 처리 + 원래 쿠폰 available 복원 |
| 수령 | 트랜잭션 내 원래 쿠폰 used 처리 + 새 UserCoupon 생성해 수령인 바인딩(coupon_id 동일하므로 유효기간 동일) + 양도 기록 claimed 처리 |

### 26. 포인트 만료(17차 라운드)

| 기능 | 설명 |
|------|------|
| 유효기간 | erik_user_points.expires_at 컬럼; 모든 earn(출석/소비 적립/회수)은 expires_at = now + points.expiry_days(기본 365, ≤0이면 만료 없음)로 저장; consume/use는 빈값 |
| 만료 실행 | PointsExpiryTimer 타이머 프로세스 60초마다 커서 스캔(100/배치) expires_at < now인 earn 행 → type=expire 음수 차감 행 작성(source=expiry + order_id 원래 거래 내역 추적) → 사용자별 집계 사이트 내 알림 "X 포인트 만료됨" |
| 멱등 | ① expire 행 order_id가 원래 earn 거래 내역을 가리키며, 트랜잭션 내 원래 행 lockForUpdate + exists 재검증(동시 프로세스는 행 잠금에서 직렬화) ② id 커서 페이징 ③ 알림은 실제 차감 라운드에서만 생성 |
| 기준 | 사용 가능 잔액 SUM 집계에 expire 음수 행 포함; 만료 포인트는 현금 전환/교환 불가 |

### 27. 번개세일 주문(18차 라운드, 폐지됨)

> 24차 라운드 `/api/seckill` 채널로 대체됨(store() 프로모션 분기는 공동구매만 남음), "43. 번개세일" 참조.

| 기능 | 설명 |
|------|------|
| API | POST /api/order에 promotion_id(flash_sale 유형) 전달: 번개세일가 = round(total × (100 − discount_percent)/100, 2), PromotionController 번개세일가와 동일 기준 |
| 검증 | 유형 화이트리스트 [group_buy, flash_sale](그 외 422); 활동 진행 중; 호출자는 참여자; 주문 서비스와 활동 일치; 매진 participants_count ≥ max_people 422 "품절"; 쿠폰/횟수권/포인트 중첩 금지 422 |
| 만료 | pay()에서 지연 판정 isFlashSaleClosed(isGroupBuyClosed와 같은 패턴): 번개세일 만료 → 활동 0 처리 + 해당 활동 pending 주문 일괄 취소 + 본 주문 자동 취소 + 기술자 잠금 해제 422 |

### 28. 서비스 알림 + 만료 알림(18차 라운드)

| 기능 | 설명 |
|------|------|
| 서비스 시작 전 알림 | ServiceReminderTimer 60초 스캔 service_time ∈ [now+1h, now+1h+60s), status confirmed/serving, appointment 유형 주문 → 사이트 내 알림(type='service_reminder', 서비스/기술자/매장/시간 포함) + SCENE_REMINDER 구독 메시지 |
| 만료 알림 | ExpiryReminderTimer 6시간 스캔 end_at ∈ (now, now+3d+6h]: active 멤버십 카드(type='card_expiry') + available 쿠폰(type='coupon_expiry', whereHas 쿠폰 정의 end_at 연관) + SCENE_EXPIRY 구독 메시지 |
| 멱등 | 모두 id 커서 100/배치 + 트랜잭션 내 행 잠금 재검증 + 알림 중복 검사(order_id 컬럼에 출처 id/주문 id를 중복 방지 키로 기록); 구독 메시지 푸시 성공 시에만 push_sent_at 기록, 실패 시 다음 라운드 재시도 |
| 대체 | 템플릿 미설정(WECHAT_SUBSCRIBE_TEMPLATE_REMINDER / _EXPIRY) 시 사이트 내 알림만으로 자동 대체 |

### 29. 기술자 평가 답글(18차 라운드)

| 기능 | 설명 |
|------|------|
| API | POST /api/technician/review/reply/{order_id}(기술자 신원 미들웨어): 평가 없음/본인 아님 통일 404; 기존 답글 422(멱등 거절, 덮어쓰지 않음); 빈 답글 422 |
| 답글 후 | 사용자에게 사이트 내 알림(type='review_reply', 비차단 try/catch + Log) |
| 데이터 | erik_order_review에 멱등 replied_at 컬럼 추가(reply 컬럼은 테이블 생성 시 존재); 관리단말 평가 list/show는 decorate()->toArray()로 reply/replied_at 노출 |

### 30. 충전 입금 알림(18차 라운드)

| 기능 | 설명 |
|------|------|
| API | 위챗 충전 콜백(R 접두사 주문번호) handleRechargeNotify 트랜잭션 내: WalletTxn 이후 사이트 내 알림 type='wallet_recharge', "¥X.XX 충전 완료"(원 단위 금액, number_format 2자리) |
| 멱등 | 기존 콜백 멱등 재사용(충전 주문 행 lockForUpdate + status 재검증, 최초 pending→paid에서만 알림까지 진행); 알림과 상태 변경은 같은 트랜잭션 원자 커밋, crash 틈 없음; 서명 검증 실패/주문 없음/금액 불일치 시 알림 미작성 |
| 장애 허용 | 알림 작성 try/catch, 실패 시 warning 로그만 기록하고 메인 흐름 차단 안 함 |

### 31. 잔액 이체(19차 라운드)

| 기능 | 설명 |
|------|------|
| API | POST /api/wallet/transfer: 수령인 hashid 디코딩+존재 404, 본인 이체 422, 건당 금액 0.01-1000 422(DECIMAL 비교 float 금지), 잔액 부족 422, 일일 누적 5000원 422 |
| 동시성/멱등 | Redis NX 잠금 wallet_transfer:{from} 30s로 송신자 직렬화; 트랜잭션 내 양쪽 user_id 오름차순 lockForUpdate 지갑 행(고정 순서 데드락 방지); client_token 성공 후 SETNX 24h 중복 제출 방지(실패 요청은 token 미기록, 재시도 가능) |
| 입금 | 송신자 차감 + 수신자 증가 + WalletTxn 이중 거래 내역(transfer_out/transfer_in에 balance_after 스냅샷 포함) + 이체 기록 completed + 수신자 사이트 내 알림 type='balance_received'(실패 시 로그만) |
| 기록 | GET /api/wallet/transfers(direction=out/in 페이징) + GET /transfers/{id}(양쪽만 조회 가능 404) |

### 32. 포인트 양도(19차 라운드)

| 기능 | 설명 |
|------|------|
| API | POST /api/user/points/transfer: 수령인 존재 404, 본인 양도 422, 수량 1-10000 422, 잔액 SUM 집계 부족 422, 일일 누적 10000 한도 422 |
| 동시성/멱등 | Redis NX 잠금 points_transfer:{user} 30s; 트랜잭션 내 양쪽 마지막 거래 내역 lockForUpdate(user_id 오름차순 상호 양도 데드락 방지) + 잠금 내 잔액/한도/수령인 재검증 |
| 거래 내역 규범 | 송신자 type=consume source=points_transfer 음수(balance=이전 스냅샷-이번, points_offset/exchange와 같은 기준); 수신자 type=earn source=points_transfer 양수에 expires_at 포함(PointsExpiryTimer 정상 만료 가능); 트랜잭션 내 양도 기록 작성, commit 후 수신자에게 사이트 내 알림 type='points_received' |
| 기록 | GET /api/user/points/transfers(direction=sent/received 페이징, 상대 닉네임) |

### 33. 평가 추평 + 제출 라우트 보완(19차 라운드)

| 기능 | 설명 |
|------|------|
| 추평 | POST /api/order/review/{order_id}/append: 평가 없음/본인 아님 통일 404, 비-completed 422, 중복 추평 422(append_content/append_at 중 하나라도 비어있지 않으면 거절), 빈 내용 422; 성공 시 append_content/append_images(JSON)/append_at 작성 + 기술자 사이트 내 알림 type='review_append' |
| 평가 제출 | POST /api/order/review/{order_id} 라우트 등록 보완(ReviewController::store 기존 라우트 없음); 잠복 TypeError도 수정: findByOrderId가 int 수신 시 string 시그니처 위반(append의 (string) 변환과 대조), 라우트 등록으로 호출 즉시 500 노출 |
| 데이터 | erik_order_review에 append_content TEXT/append_images JSON/append_at DATETIME 3컬럼 추가(멱등 마이그레이션); 응답에 append 필드 노출 |

### 34. 사용자 물류 추적(19차 라운드)

| 기능 | 설명 |
|------|------|
| API | GET /api/order/logistics/{id}: 본인 product 주문만 조회 가능(아님 404/상품 아님/미발송 통일 404) |
| 데이터 | order.remark JSON 파싱(shipping_company/tracking_no/shipped_at, admin MallOrderController::ship() 발송 시 기록); parseShippingInfo/parseReceiver 이중 파싱으로 구 형식 폴백 |
| 마스킹 | 수령인 휴대폰번호 maskPhone(138****5678), 유출 방지 |

### 35. 알림 수신 설정(19차 라운드)

| 기능 | 설명 |
|------|------|
| 데이터 | erik_user_notify_setting 테이블(user_id+type 복합 고유 키 uk_user_type, 기본 행 없음=기본 켜짐); 5종: service_reminder 서비스 알림 / card_expiry 만료 알림(카드+쿠폰 통합 우산)/ points_expiry 포인트 만료 / marketing 마케팅(예약)/ system 시스템(끌 수 없음, PUT 강제 1) |
| API | GET /api/user/notify-settings 5종 전체 스위치 반환; PUT 일괄 upsert 중복 행 미생성 |
| 게이트 | NotificationReminderService::notifySettingEnabled를 3개 타이머 프로세스에 연결(ServiceReminderTimer/ExpiryReminderTimer 카드+쿠폰/PointsExpiryTimer, 타이머는 erik_notification 테이블에 직접 삽입하므로 서비스 작성 경로를 거치지 않아 각자 동일 게이트 추가) + 구독 이벤트(sendSubscribeForOrderEvent/Notification 시나리오 매핑 PAY/REFUND/VERIFIED/RESCHEDULE→system 항상 발송, REMINDER→service_reminder, EXPIRY→card_expiry); 유형이 꺼져 있으면 사이트 내 알림과 구독 메시지를 모두 건너뜀 |

---

## 2. 관리 백엔드(PC Web)

Flutter Web 싱글 페이지 앱, 총 21개 페이지: dashboard/사용자/역할/설정/로그/검증/스케줄/서비스/기술자/주문/쿠폰/멤버십/횟수권/공지/FAQ/출금/평가/리포트/마이페이지/매장 작업대.

### 1. 홈 대시보드

- 실시간 통계: 사용자 수/주문 총수/기술자 수/서비스 주문 수
- 꺾은선 그래프: 주문량 추세/금액 추세/신규 사용자/활동도
- 빠른 내비게이션: 처리 대기 모듈 버튼
- 사이트 내 메시지: 신규 주문 알림/환불 알림

### 2. 기술자 관리

- 기술자 목록: UID/휴대폰 번호/이름/소속 지역/가입 시간 검색
- 목록 표시: 번호/UID/휴대폰 번호/닉네임/추천인/상태/학생 수/실적/계정 상태/가입 시간/마지막 로그인/소속 지역
- 조작: 내보내기/상위 변경/하위 조회/비밀번호 휴대폰 변경/스케줄 관리/기술 서비스 항목 설정/강좌 진행도 조회
- 추가: 이름/성별/휴대폰 번호/주민등록번호/주민등록번호 사진
- 입점 신청 심사

### 3. 사용자 관리

- 회원 목록: 이름/휴대폰 번호/프로필/등급/소비 금액
- 검색: UID/휴대폰 번호/닉네임/가입 시간
- 조작: 상세/상위 변경/하위 조회/비밀번호 휴대폰 변경/회원 등급 설정

### 4. 매장 관리

- 매장 목록: 활성/비활성/삭제
- 매장 추가: 이름/주소/좌표/전화/영업 시간/이미지

### 5. 서비스 관리

- 서비스 목록: 이름/분류 검색; 번호/이름/유형/할인/최저가/판매량/커버/순서/상태/시간
- 조작: 추가/수정/삭제/카드 구성 설계
- 상품 목록: 유형/이름/할인/최저가/판매량/재고/커버/순서/상태/시간

### 6. 몰 관리

- 몰 주문: 명세/발송/물류/프린팅
- 애프터서비스 주문: 조회/심사/프린팅
- 평가 관리: 조회/심사(show/hide)/삭제(ReviewController index/show/audit/destroy)
- 결제 거래 내역
- 판매 통계

### 7. 주문 관리

- 사용 대기 주문: 다중 조건 검색
- 조작: 상세/플랫폼 취소/완료 확인

### 8. 쿠폰 활동

- 목록: 순서/이미지/유형/이름/상·하품/총수/잔여/관리자/시간/종료 날짜
- 조작: 추가/수정/삭제

### 9. 재무 관리

- 주문 분배금: 검색/상세
- 기술자 출금: WithdrawalController 심사; 금액 ≥500 2단계 승인(매장장 store_approved_at → 재무 finance_approved_at); 상태 머신 pending→approved→completed(rejected/failed)
- 수수료 설정: 수수료율/정산 주기/상벌/잔액 변경
- 수입 지출 거래 내역
- 출금 계좌 관리
- 출금 제한 설정

### 10. 콘텐츠 관리

- 배너 CRUD
- 회사 소개 설정
- SNS 동향 심사
- FAQ CRUD
- 의견 피드백 처리
- 플랫폼 공지 CRUD

### 11. 설정

- 플랫폼 약관 편집(사용자 약관/개인정보 약관/서비스 약관)
- 기술자 통일 수수료 설정
- 시스템 메시지 템플릿(미니프로그램 구독 메시지 템플릿 설정 포함, 미설정 시 사이트 내 알림으로 자동 대체)
- 하위 계정 권한 관리(매장장은 쿠폰 발급+스케줄 가능)

### 12. 확장 기능

- 카드 구성 설계: 항목+상품 조합/수공비/수당 설정
- 시스템 모니터링: CPU/메모리/디스크/Redis/MySQL/대기열 실시간 대시보드
- IP 블랙리스트: security-php 공격 기록 시각화+수동 차단
- 데이터베이스 백업: Web 인터페이스 백업/다운로드/복원
- 고객 페르소나: 360 뷰/소비 선호도/계층 마케팅
- 일괄 푸시: 템플릿 메시지/구간 대량 발송
- 환불 심사 흐름: 2단계 승인(매장장→재무)
- 기술자 등급: junior/senior/expert 자동 평가
- 예약 작업: 자동 취소/정산/만료 처리
- 문자 설정: 알리바바 클라우드/텐센트 클라우드 다중 채널 관리
- 스토리지 설정: 로컬/OSS/COS/CDN
- 리포트 강화: 사용자 지정 필드/정기 이메일 리포트
- 스케줄 내보내기: Excel로 예약 기록/출석 목록 내보내기
- 기술자 성별 제한: 특정 항목 성별 제어
- 기술자 교육: 강좌 관리/학습 진행도 추적
- 매장장 계정: store_id 데이터 격리+전용 권한

### 13. 데이터 리포트(7차 라운드)

- ReportController 3개 엔드포인트: 주문 통계 / 기술자 실적 / 매장 분포
- Redis 캐시 svc:admin_report:{type}:{start}:{end}, TTL 300

### 14. 멤버십 카드 관리(10차 라운드)

- erik_user.member_level 회원 등급 컬럼(마이그레이션 000008)
- MemberCardController 전체 CRUD(권한 365-369): GET/POST/PUT/DELETE /admin/member-cards
- Flutter 멤버십 카드 정의 관리 페이지

### 15. 애프터서비스 관리(14차 라운드)

- erik_order_aftersale 테이블(마이그레이션 000009): type=refund/exchange, status=pending/approved/rejected/completed
- AftersaleController: GET /admin/aftersales(페이징+status/uid/order_no 필터) + POST /admin/aftersales/{id}/review(approve/reject+remark)
- Flutter 애프터서비스 관리 페이지(목록+심사 다이얼로그, 권한 370/371), 레이아웃 등록됨

### 16. 매장장 작업대(15차 라운드)

- service /api/store-manager: overview(오늘 주문/매출/진행 중/기술자 수/검증 수) + orders(페이징+상태 필터) + technicians(오늘 스케줄 포함) + revenue(최근 7일 집계), requireStoreId()로 store_id 강제 격리(매장 없음 403)
- admin StoreController::workbenchOverview(GET /admin/stores/workbench-overview?store_id=, 기준은 service와 동일) + AppointmentOrderController 주문 목록 store_id 필터(hashid 디코딩)
- Flutter 매장 작업대 페이지: 매장 드롭다운 + 상태 필터 + 개요 카드 5장 + 주문 DataTable + 페이징(권한 372)

### 17. 포인트 교환 상품(16차 라운드)

- PointsExchangeGoodsController: GET/POST/PUT/DELETE /admin/points-exchange-goods + POST {id}/toggle-status(상·하품) + GET {id}/exchanges(교환 기록, 휴대폰 번호+result JSON 파싱 포함)
- 마이그레이션 000012(2개 테이블) + 000013(권한 373-378) 적용됨

### 18. 수수료 기록(16차 라운드)

- ReferralRewardController: GET /admin/referral-rewards(rewarded_at 비어있지 않은 기록만, 페이징 + keyword로 추천인/추천받은 사람 닉네임 또는 휴대폰 번호 필터, hashid 인코딩, 권한 379)

### 19. 기술자 등급 자동 평가(17차 라운드)

- TierRatingService::evaluate(technicianId, allowDowngrade=false): 실시간 erik_order completed 주문 수 + erik_order_review 평균(반올림 소수 1자리) 집계해 profile.order_count/rating에 기록, erik_technician_tier_config(min_orders/min_rating)에 따라 높은 등급부터 매칭, 매칭 없으면 최저 등급
- 승·하급 규칙: 승급만 지원(등급은 수수료율과 가격 계수와 연동, 자동 하급은 기술자 수입에 영향으로 분쟁 유발 가능, 하락은 admin 수동으로 처리); allowDowngrade=true(백엔드 수동 재평가 시나리오)일 때만 하급 실행, 하급도 로그 + 알림 기록
- 멱등: 기대 등급과 profile.tier_id가 일치하면 통계 동기화만 하고 로그/알림 미기록
- 로그: 변경 시 erik_technician_tier_log(id/technician_id/old_tier_id/new_tier_id/reason/created_at) + 사이트 내 알림(type='tier')
- 트리거: WorkController::complete / ReviewController 평가 작성 / ProfileController 프로필 조회 지연 판정
- 관리단말: TechnicianTierController 수동 설정 기능 유지; GET /admin/technician-tiers/logs 페이징 변경 로그 조회(기술자 이름과 신·구 등급명 join, ID hashid 인코딩, 권한 380)

### 20. 평가 답글 조회(18차 라운드)

- ReviewController에 reply() 추가: GET /admin/reviews/{id}/reply 답글 상세(decodeId → find → 404 → decorate 출력, 미답글 시 reply='', reply/replied_at은 toArray로 노출)
- 라우트는 정적 라우트(audit 앞에 위치, resource보다 먼저 정의); 권한 시드 id 381(slug 'get.admin/reviews/{id}/reply', type 3, 슈퍼관리자 역할 멱등 연결)
- 권한 포인트: 381

### 21. 예약 월력(20차 라운드)

- CalendarController 월/일 보기: GET /api/calendar/technician/{id}(월 보기) + /day(일 보기)
- 데이터 소스: technician_schedule.time_slots JSON을 요일별 시간 슬롯으로 전개, erik_order의 해당일 예약 시간대 제외(status ∈ pending/paid/confirmed/serving), 남은 예약 가능 슬롯 출력
- 용도: 매장 스케줄 시각적 선택, 프런트엔드가 날짜별 가로 스크롤 + 시간 격자 선택

### 22. 사용자 성장 등급(20차 라운드)

- erik_user_growth(거래 내역) + erik_growth_level(등급 시드 5단계: 브론즈0/실버100/골드500/플래티넘2000/다이아5000)
- 성장값 입금점: 출석 +10(CheckInController); 평가 제출 +20(ReviewController::store, 추평은 미입금); 소비 floor(paid) 1원당 1포인트(WechatPayService::markOrderPaid, 기존 결제 상태 재검증 재사용으로 자연 멱등, 중복 콜백은 중복 입금 없음)
- API: GET /api/growth(현재 등급 개요: balance/level/다음 등급 차액); GET /api/growth/records(거래 내역 페이징); GET /api/growth/levels(공개 등급 목록, 로그인 불필요)
- 실패 전략: 모든 입금점 try/catch 로그 기록, 메인 흐름 영향 없음

### 23. 전자 세금계산서(20차 라운드)

- erik_invoice: uk_order_type(order_id,order_type) 같은 주문 중복 신청 방지(중복 신청 422, MySQL 1062 캡처 폴백 포함); idx_user_created/idx_status
- 사용자단말: POST /api/invoices(신청, 금액/발행자는 서버가 주문에서 산출, 위변조 불가); GET /api/invoices(목록); GET /api/invoices/{id}(상세)
- 관리단말: InvoiceController issue(발행: invoice_no + status=issued + issued_at 기록)/ reject(반려: status=rejected + reject_reason), 권한 382 목록/383 발행/384 반려
- 상태 머신: pending → issued / rejected

### 24. 고객센터 티켓(20차 라운드)

- erik_ticket: 사용자 티켓 제출(title/content), 백엔드 답변 추가(reply_content/replied_at), 사용자 종료 가능(closed_at)
- 사용자단말: POST /api/tickets(제출); GET /api/tickets(목록); GET /api/tickets/{id}(상세, 본인만); POST /api/tickets/{id}/close(종료)
- 관리단말: TicketController index(목록)/ reply(답변), 정적 라우트를 resource보다 먼저 정의해 {id} shadow 방지; 권한 385 티켓 답변/387 티켓 목록 조회
- 상태 머신: open → replied(답변 후 open으로 복귀, 재답변 가능)/ closed

### 25. 다단계 유통-2단계 수수료(20차 라운드)

- ReferralRewardService::payLevel2Reward(paidAmount, orderId): 주문 결제 성공 후 1단계 추천인의 추천인(2단계 추천 관계) 조회, paid×level2_rate(시스템 설정 referral.level2_rate, 기본 0.02) 지급
- 멱등: 트랜잭션 내 행 잠금 + uk_order_referred(order_id, level2_user_id) 고유 키, 중복 결제 콜백/동시에 중복 지급 없음; try/catch 실패는 로그만 기록, 결제 메인 흐름 영향 없음
- 입금: WalletTxn type='referral_level2'(TYPE_REFERRAL_LEVEL2 상수) + 지갑 잔액 누적
- 관리단말: ReferralLevel2Controller index 페이징 기록(권한 386), 2단계 사용자 닉네임 join

### 26. 성장 등급 혜택 실체화(21차 라운드)

- GrowthLevel.benefits JSON 실체화: 마이그레이션 시드 5단계(브론즈 {"discount_rate":1.0,"points_multiplier":1.0}, 실버 0.98/1.1, 골드 0.95/1.2, 플래티넘 0.92/1.3, 다이아 0.9/1.5)
- 등급 할인: OrderController::store applyGrowthDiscount() —— 표준 주문만(promotion_id 비어있음, 공동구매/번개세일 중첩 금지); 순서: 쿠폰/횟수권 할인 후 결제 금액 × discount_rate; 할인액은 discount_amount에 합산, 주문 메모에 "등급 할인: 실버 9.8折, 할인 ¥2.00" 기록으로 추적 가능; 최저가 보호: 할인 후 실결제 ≥0.01원(분제 ≥100), 부족 시 할인 0으로 절사
- 포인트 배율: WechatPayService::markOrderPaid 성장값 floor(paid)에서 floor(paid × points_multiplier)로 변경, 배율은 결제 시점 등급 기준(입금 전 누적, 본 주문은 등급 상승 없음); R20의 try/catch 연결점 완전 유지
- 조회 재사용: GrowthLevel::levelForGrowth() 누적 성장값 기준 등급 산출, 주문/결제에서 재사용; GET /api/growth가 benefits와 next_gap 반환(R20 구현, 수정 불필요)

### 27. 세금계산서 발행자 관리(21차 라운드)

- erik_invoice_title(uk_user_title(user_id, title_type, invoice_title) 중복 방지 + idx_user_default)
- API: POST /api/invoice-titles(저장, company는 tax_no 필수, 중복 422); GET(목록, 기본 최상단); PUT /{id}(수정, 본인만); DELETE /{id}(삭제, 본인만); POST /{id}/default(기본 설정, 트랜잭션으로 같은 사용자 다른 행 초기화)
- 기본 규칙: 첫 저장 자동 기본; 기본 삭제 시 가장 오래된 항목 자동 지정
- 신청 연동: InvoiceController::store 선택 title_id로 발행자 파싱해 invoice_title/tax_no/title_type 반영, title_id 없으면 기존 수동 입력 경로 유지; uk_order_type 중복 방지 로직 미변경

### 28. 티켓 만족도(21차 라운드)

- erik_ticket에 rating TINYINT NULL + rated_at DATETIME NULL 추가(마이그레이션 000303)
- 종료 평가: TicketController::close()에서 선택 rating 1-5 지원(filter_var 정수 검증, 범위 초과/비정수 422; 제공 시 rating+rated_at 기록, 미제공 시 NULL 유지로 구 클라이언트 호환; open 티켓만 종료 규칙 유지)
- 백엔드 통계: GET /admin/tickets/satisfaction(정적 라우트를 resource보다 먼저 정의해 {id} shadow 방지) total/rated_count/unrated_count/average(소수 1자리)/distribution(1-5성 각 수량, 없는 별은 0 보충) 반환; 권한 388

### 29. 평가 이미지 심사(21차 라운드)

- admin ReviewAuditController(신규, 기존 ReviewController 수정 안 함): GET /admin/review-audit 이미지 포함 평가 목록(JSON_LENGTH(images)>0 필터 + leftJoin 사용자 닉네임과 기술자 이름 + status 필터 + hashid 인코딩); POST /{id}/hide 숨김; POST /{id}/restore 복원
- 상태 머신: hide는 visible만 숨길 수 있고, restore는 hidden만 복원 가능(양방향 422); OrderReview 상태는 정수 체계(STATUS_HIDDEN=0/STATUS_VISIBLE=1)
- 적용 체인: 사용자단말 기술자 평가 목록이 status로 필터 → 숨긴 후 자동 비노출
- 권한: 389 목록 / 390 숨김 / 391 복원

### 30. 사용자 조회 이력(21차 라운드)

- erik_browse_history(uk_user_item(user_id, item_id) 고유, 중복 조회는 viewed_at만 갱신, 중복 삽입 없음; idx_user_viewed 정렬)
- 기록 연결: ServiceController::detail() 성공 후 기록(try/catch + Log::warning 메인 흐름 영향 없음; 공개 라우트는 JWT 없음, user_id 빈값 확인으로 익명 스킵)
- API: GET /api/browse-history(erik_service 이름/커버/가격/원가 join, viewed_at 내림차순, per_page 기본 15 상한 50, item_id hashid); DELETE /{item_id}(본인만, 비정상/타인 404); DELETE /(본인만 전체 삭제)

### 31. 만 N 원 할인 마케팅(22차 라운드)

- erik_full_reduction_activity(threshold/reduction/title/status/start_at/end_at + idx_status_status_time)
- 주문 중첩: 표준 주문만(공동구매/번개세일 제외), 쿠폰/횟수권 차감 후 결제 금액으로 문턱 판정, 순서 **쿠폰/횟수권 → 만 N 원 할인 → 등급 할인**; 할인액이 가장 큰 활동 선택; 할인액은 discount_amount에 합산 + 메모 "만 N 원 할인: X 이상 Y 할인"; 할인 후 실결제 하한 0.01원(분제)
- 사용자단말 GET /api/full-reduction-activities(공개, 진행 중인 활동을 할인액 내림차순)
- admin FullReductionController: CRUD + toggle-status 상·하품(destroy는 confirmPassword 포함)
- 권한: 396 목록 / 397 추가 / 398 수정 / 399 상·하품 / 400 삭제(권한 레코드 1개는 method.path slug 1개에 대응, 5개 라우트를 5개로 분리)

### 32. 내 예약 ICS 내보내기(22차 라운드)

- IcsController GET /api/order/ics: 90일 내 pending/paid/confirmed/serving 주문 iCal(RFC5545) 내보내기, 본인만
- VEVENT: UID=주문 ID, DTSTAMP(UTC), TZID=Asia/Shanghai, 기본 시간 1h, 요약 "예약: 서비스명"(없으면 "예약"으로 대체), 설명 기술자/매장/주소(없으면 생략), LOCATION; 텍스트 이스케이프(\, \; \\ \n) + 75바이트 줄 접기
- 주문 없으면 유효한 빈 캘린더(`BEGIN:VCALENDAR` 골격) 반환

### 33. 기술자 근태(22차 라운드)

- erik_technician_attendance(date/check_in_at/check_out_at/status + uk_technician_date 고유 인덱스 동시 중복 체크인 방지)
- 기술자단말(TechnicianAuth): check-in 당일 중복 422; check-out 출근 안 함/퇴근 완료 422 + 행 잠금; >10:00 지각 표시; GET 해당 월 목록 + 출근 일수/총 근무 시간/평균 근무 시간(?month=YYYY-MM 비정상 422)
- admin: GET /admin/attendance(date+기술자 이름 필터, real_name join, hashid) + /stats(기술자별 그룹 통계)
- 권한: 392 목록 / 393 통계

### 34. APP 푸시 서비스(22차 라운드)

- AppPushService(config group=push: enabled 기본 0 / provider jpush/getui/placeholder): 미활성 시 무음 대체 로그만; 활성 시 플랫폼/제목/내용/payload 구조로 Log + erik_push_log(status=sent) 기록; 업체 SDK 연동은 TODO 유지(자격 증명 없으면 실제 발송 안 함)
- 5곳 이벤트 연결: 결제 성공(WechatPayService::markOrderPaid), 자동 환불(autoRefundCancelledOrder), 수동 환불(doRefund/refundToBalance), 환불 보상(completeOneRefundCompensation), 서비스 시작 알림(ServiceReminderTimer); 모두 try/catch로 메인 흐름 차단 없음
- erik_push_log(user_id/title/content/payload JSON/status/provider + idx_user)

### 35. 위챗 공식 분배금(22차 라운드)

- WechatProfitSharingService(config group=profit_sharing: enabled/receiver_ratio, 자격 증명은 wechat_pay 재사용): 미활성 disabled 대체 로그만, DB 미기록; 활성→금액 검증(>0이고 ≤paid, 실결제×0.7 기본) + 멱등(같은 주문 pending/success 스킵) → pending 기록 → "단건 분배금 요청" 구조 구성(자격 증명 없으면 HTTP 미실행, 요청 내용 로그 기록, 기록 pending 유지); HTTP 격리 private doRequest로 테스트 가능
- WechatPayService::markOrderPaid 제출 후 requestSharing 연결(try/catch 실패는 로그만)
- erik_profit_sharing(uk_sharing_no 고유 + idx_order); admin GET /admin/profit-sharing 목록(주문번호/기술자 닉네임 join, 상태/주문번호/기술자 이름 필터)
- 권한: 394

### 36. 개인정보 컴플라이언스(22차 라운드)

- GET /api/privacy/data: 데이터 내보내기(personal/orders/points/wallet_txns/reviews/addresses/invoices 그룹; 로그는 마스킹 휴대폰 번호+건수만 기록)
- 탈퇴 클로즈드 루프: close-request(잔액 0 아님 / 미완료 주문 / 진행 중 티켓 422 → close_status=1) → close-cancel(1→0) → close-confirm(72h 경과 → close_status=2 + close_at + phone/nickname 익명화 user{id} + status=0)
- erik_user에 close_status/close_requested_at/close_at 추가(멱등 ALTER 마이그레이션); AuthController login/loginByCode가 close_status=2에 403 "계정이 탈퇴되었습니다" 반환

### 37. 사용자 건강 프로필(23차 라운드)

- GET/PUT/DELETE /api/health-profile: 1인 1개(uk_user 고유 인덱스), upsert는 제공한 필드만 갱신
- allergies/health_notes 상한 500자, preferred_technician_id 존재성 검증, 응답 hashid 인코딩
- 마이그레이션 000504_user_health_profile; HealthProfileTest 6 tests

### 38. 지갑 결제 비밀번호(23차 라운드)

- POST /api/wallet/pay-password/{set,verify,check}: 6자리 숫자 검증, password_hash 저장 + pay_password_set_at
- 설정된 상태에서 변경 시 기존 비밀번호 필요 422; verify는 검증만, DB 미기록; check는 설정 여부 반환
- 마이그레이션 000502(INFORMATION_SCHEMA 멱등 ALTER 2컬럼); WalletPayPasswordTest 7 tests

### 39. 기술자 일괄 스케줄(23차 라운드)

- POST /api/technician/schedule/batch: 기간 ≤7일 + weekdays 필터, 기존 스케줄 있는 날은 건너뜀
- 단건 설정도 시간대 중복 탐지 활성(422 "기존 스케줄과 시간 충돌: HH:MM-HH:MM")
- ScheduleConflictTest 5 tests

### 40. 주문 상태 타임라인(23차 라운드)

- GET /api/order/{id}/timeline: 본인만 조회 가능(타인 404), 내림차순 반환; admin 주문 상세에 timeline 배열 병합
- OrderStatusLog::record() 정적 로그 8종 변경: 제출/결제/취소/확인/환불 신청/환불 승인/서비스 시작/서비스 완료/시간 초과 자동 취소/백엔드 조작(operator=admin)
- 결제 콜백 markOrderPaid는 단일 소비점; record() 내부 try/catch + Log::warning으로 절대 메인 흐름 차단 안 함
- 마이그레이션 000501_order_status_log; OrderTimelineTest 4 tests

### 41. 포인트 행운의 룰렛(23차 라운드)

- GET /api/wheel/prizes(weight/stock 숨김); POST /api/wheel/spin: Redis NX + 행 잠금 동시성 방지, random_int 가중치 추첨, client_token 멱등
- 상품 입금: 포인트→earn 거래 내역(만료 시간 포함, PointsExpiryTimer 정상 만료 가능), 잔액→lockForUpdate, 쿠폰→pending 수동 발급, 당첨 없음→lose
- GET /api/wheel/records 내 기록 페이징; admin /admin/lucky-wheel CRUD + 상·하품 + 기록(권한 401-406)
- 마이그레이션 000503(erik_lucky_wheel + erik_wheel_record + w60/w40 데모 시드) + 000505(권한 시드); LuckyWheelTest admin 3 + service 6 tests

### 42. 게스트 모드(24차 라운드)

- GET /api/guest/{home,services,services/{id},stores,technicians}: 인증 불필요(ApiVersion 미들웨어만)한 비로그인 조회 진입점
- home은 배너/공지/서비스 분류/인기 서비스 집계, Redis 캐시 svc:guest:home 300s; services는 분류 필터 + newest/sales/price 정렬 지원(page/per_page≤50); technicians는 심사 통과만, service_id 필터 가능, 평점 내림차순
- GuestControllerTest 커버리지

### 43. 번개세일(24차 라운드)

- erik_seckill_activity(name/service_id/seckill_price/original_price/stock/start_at/end_at/status); 판매량 = erik_order.seckill_id 주문 수
- GET /api/seckill(status=1 + 시간 창), /{id}(state=not_started/ongoing/ended), POST /{id}/buy: client_token(8-64자, SETNX 24h) 멱등 + Redis NX 30s 동시성 방지 + 활동 검증(2026-08-26부터 재고 선차감 없음)
- 주문 시 seckill_id 주입해 OrderController::store 재사용; 재고는 store() 트랜잭션 내 행 잠금으로 일괄 차감(/api/order에 seckill_id 직접 전달해도 재고 차감), 번개세일가 = seckill_price(DB 기준), 쿠폰/포인트/멤버십 카드 중첩 불가; 주문 취소는 재고 미복원; 기존 프로모션 FLASH_SALE 채널 삭제(store() 프로모션 분기는 공동구매만, PromotionController index에서 flash_sale 필터, show/join 400), 번개세일은 본 채널만 사용
- admin /admin/seckill CRUD + 상·하품 + 주문 목록(권한 407-411, 420); 마이그레이션 000606 권한 시드; SeckillTest service + admin

### 44. APP 버전 관리와 업데이트 탐지(24차 라운드)

- erik_app_version(platform/version_code/version_name/force_update/changelog/download_url/status)
- GET /api/app/version?platform=android|ios 공개 업데이트 탐지(platform 비정상 422; status=1 중 최신; 없으면 빈 객체)
- admin /admin/versions CRUD(권한 416-419); 마이그레이션 000609 권한 시드; VersionTest service + admin

### 45. 재방문 고객 보상(24차 라운드)

- ReturnCustomerRewardService: 사용자가 같은 기술자에게 30일 내 2차 소비(주문 완료) 시 기술자에게 보너스 = 실결제 paid_amount × ratio(system_config group=return_customer, ratio 기본 0.05, enabled 스위치, 비정상 값은 기본으로 폴백)
- erik_technician_earnings(type=return_customer, status=pending) 기록으로 수수료 정산 체인 재사용, 기술자단말 earnings 집계 자동 포함; 같은 order_id+type 멱등; WorkController::complete 행 잠금 트랜잭션 내 호출
- admin /admin/return-customer/config(GET/PUT) + /rewards(?keyword 기술자 이름/주문번호/사용자 닉네임)(권한 412-414); 마이그레이션 000607 권한 시드; ReturnCustomerRewardServiceTest

### 46. 스케줄 내보내기(24차 라운드)

- GET /admin/technician-schedule/export: CSV(UTF-8 BOM, Excel 직접 열기), 파일명 schedules_{YmdHis}.csv
- start_date/end_date 필수(YYYY-MM-DD, 비정상 422)이고 기간 ≤31일; technician_id 선택(hashid, 비정상 422)
- 컬럼: 기술자 ID/기술자 이름/날짜/시간대 명세(time_slots JSON 파싱 "09:00-12:00, 14:00-18:00")
- 권한: 415; 마이그레이션 000608 권한 시드; ScheduleExportTest 커버리지
