# 예약 서비스 시스템 — 프로젝트 구조

## 저장소 개요

```
appointment-php/
├── admin/              # 관리 백엔드 (webman v2 + Flutter Web)
├── service/            # 비즈니스 API 서비스 (webman v2)
├── apps/               # 사용자 단말 프런트엔드 앱
│   ├── wechat/         #   위챗 미니프로그램(네이티브)
│   ├── flutter/        #   Flutter APP(iOS + Android)
│   └── harmonyos/      #   HarmonyOS APP(하모니OS 네이티브)
├── docs/               # 프로젝트 문서
└── .claude/            # Claude Code 설정
```

## 프로젝트 관계

```
┌──────────────────────────────────────────────┐
│                   apps/                       │
│  ┌─────────────┐  ┌──────────┐  ┌─────────┐  │
│  │ wechat/      │  │ flutter/  │  │harmonyos/│  │
│  │ 위챗 미니프로그램│  │iOS/Android│  │ 하모니OS APP│  │
│  └──────┬──────┘  └────┬─────┘  └────┬────┘  │
│         │      기능 완전 동일     │            │
│         └──────────┬─────────┘            │
│                    │ HTTP API                 │
├────────────────────┼─────────────────────────┤
│              service/                         │
│         비즈니스 API (webman v2)               │
│             포트: 8787                         │
│                    │                          │
│                    │ 공유 MySQL/Redis/ES       │
│                    │                          │
│              admin/                           │
│        관리 백엔드 API (webman v2)             │
│             포트: 8787                         │
│                    │                          │
│         ┌──────────┴──────────┐               │
│         │                     │               │
│    admin/apps/flutter/    Flutter Web         │
│    관리 백엔드 프런트엔드 (PC)                  │
└──────────────────────────────────────────────┘
```

## admin/ — 관리 백엔드

```
admin/
├── app/
│   ├── admin/controller/       # 관리단말 컨트롤러
│   │   ├── BaseController          # 기본 컨트롤러
│   │   ├── DashboardController     # 대시보드
│   │   ├── UserController          # 사용자 관리
│   │   ├── RoleController          # 역할 관리
│   │   ├── PermissionController    # 권한 관리
│   │   ├── ConfigController        # 시스템 설정
│   │   ├── LogController           # 조작 로그
│   │   ├── ProfileController       # 마이페이지
│   │   ├── ExportController        # 내보내기
│   │   ├── ImportController        # 가져오기
│   │   ├── UploadController        # 파일 업로드
│   │   ├── HealthController        # 헬스 체크
│   │   ├── DocsController          # API 문서
│   │   ├── MetricsController       # Prometheus 지표
│   │   │                            # ✅ 구현된 비즈니스 모듈:
│   │   ├── TechnicianController    #   기술자 관리(목록/심사/스케줄/내보내기)
│   │   ├── MemberController        #   회원 관리(등급/소비)
│   │   ├── StoreController         #   매장 CRUD
│   │   ├── ServiceController       #   서비스 항목 CRUD
│   │   ├── ServiceCategoryController # 서비스 분류 CRUD(트리)
│   │   ├── ProductController       #   상품 CRUD
│   │   ├── MallOrderController     #   몰 주문/발송/애프터서비스
│   │   ├── SalesStatsController    #   판매 통계(Redis 캐시)
│   │   ├── AppointmentOrderController  # 예약 주문(취소/완료)
│   │   ├── MemberCardController    #   멤버십 카드 정의 CRUD
│   │   ├── ReviewController        #   서비스 평가 관리
│   │   ├── ReportController        #   데이터 리포트 통계
│   │   ├── CouponController        #   쿠폰 CRUD
│   │   ├── FinanceController       #   재무 거래 내역/통계
│   │   ├── WithdrawalController    #   출금 심사(승인/반려/완료)
│   │   ├── CommissionController    #   수수료 설정/상벌
│   │   ├── WithdrawalAccountController # 출금 계좌 관리
│   │   ├── WithdrawalConfigController  # 출금 제한 설정
│   │   ├── BannerController        #   배너 CRUD
│   │   ├── AnnouncementController  #   공지 CRUD/발행
│   │   ├── FaqController           #   FAQ CRUD
│   │   ├── FeedbackController      #   의견 피드백/답변
│   │   ├── MomentController        #   SNS 동향 심사
│   │   ├── AgreementController     #   약관 편집/발행
│   │   ├── AboutController         #   회사 소개 설정
│   │   └── SystemMessageController #   시스템 메시지 템플릿/발송
│   │   │                            # ✅ 확장 모듈:
│   │   ├── ServiceCardController    #   카드 구성 설계
│   │   ├── SystemMonitorController  #   시스템 모니터링
│   │   ├── IpBlacklistController    #   IP 블랙리스트 관리
│   │   ├── DbBackupController       #   데이터베이스 백업
│   │   ├── SmsConfigController      #   문자 설정
│   │   ├── StorageConfigController  #   스토리지 설정
│   │   ├── StoreManagerController   #   매장장 계정
│   │   ├── TrainingController       #   기술자 교육
│   │   ├── ScheduledTaskController  #   예약 작업
│   │   ├── CustomerProfileController #  고객 페르소나
│   │   ├── BatchMessageController   #   일괄 푸시
│   │   ├── RefundWorkflowController #   환불 심사
│   │   ├── TechnicianTierController #   기술자 등급
│   │   │                            # ✅ 22-25차 라운드 신규:
│   │   ├── FullReductionController  #   만 N 원 할인 활동
│   │   ├── AttendanceController     #   기술자 근태
│   │   ├── ProfitSharingController  #   위챗 분배금
│   │   ├── LuckyWheelController     #   포인트 룰렛
│   │   ├── PointsExchangeGoodsController # 포인트 교환 상품
│   │   ├── ReviewAuditController    #   평가 이미지 심사
│   │   ├── InvoiceController        #   전자 세금계산서
│   │   ├── TicketController         #   고객센터 티켓
│   │   ├── ReferralRewardController #   1단계 수수료 기록
│   │   ├── ReferralLevel2Controller #   2단계 수수료 기록
│   │   ├── ReturnCustomerController #   재방문 고객 보상
│   │   ├── SeckillController        #   번개세일 활동
│   │   ├── VersionController        #   APP 버전 관리
│   │   ├── TechnicianScheduleController # 스케줄 관리/CSV 내보내기
│   │   ├── AftersaleController      #   애프터서비스 처리
│   │   ├── OrderVerificationController # 검증 기록
│   │   ├── CommunityModerationController # 커뮤니티 심사
│   │   ├── VideoAuditController     #   영상 심사
│   │   └── InstallController        #   설치 마법사
│   ├── api/v1/controller/      # 공개 API v1
│   │   ├── AuthController
│   │   └── CaptchaController
│   ├── common/                 # 공용 도구
│   │   ├── HashidsService
│   │   ├── SnowflakeService
│   │   ├── EncryptionService
│   │   ├── TechnicianWithdrawalService
│   │   └── WechatPayService
│   ├── middleware/             # 미들웨어
│   │   ├── Cors
│   │   ├── RateLimit
│   │   ├── ApiVersion
│   │   ├── AdminAuth
│   │   ├── AdminPermission
│   │   └── OperationLog
│   ├── model/                  # 데이터 모델(특유 모델 6개만: AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig; 나머지는 psr-4로 service 버전 공유)
│   ├── queue/                  # 대기열 작업
│   └── process/                # 프로세스
├── apps/
│   ├── flutter/                # Flutter Web 관리 백엔드 프런트엔드
│   │   └── lib/app/
│   │       ├── pages/           #   페이지(20개)
│   │       │   ├── dashboard/   #   대시보드
│   │       │   ├── login/       #   로그인
│   │       │   ├── user/        #   사용자 관리
│   │       │   ├── member/      #   회원 관리
│   │       │   ├── role/        #   역할 권한
│   │       │   ├── config/      #   시스템 설정
│   │       │   ├── log/         #   조작 로그
│   │       │   ├── profile/     #   마이페이지
│   │       │   ├── technician/  #   기술자 관리
│   │       │   ├── schedule/    #   스케줄
│   │       │   ├── service/     #   서비스/상품 관리
│   │       │   ├── service_card/#   카드 구성 설계
│   │       │   ├── order/       #   주문 관리
│   │       │   ├── verification/#   검증 기록
│   │       │   ├── coupon/      #   쿠폰
│   │       │   ├── withdrawal/  #   출금 심사
│   │       │   ├── report/      #   리포트 통계
│   │       │   ├── review/      #   평가 관리
│   │       │   ├── announcement/#   공지
│   │       │   └── faq/         #   FAQ
│   │       ├── services/        #   API 서비스 계층
│   │       ├── layouts/         #   레이아웃
│   │       └── theme/           #   테마
│   ├── harmonyos/               # HarmonyOS 관리단말(ArkTS)
│   └── weixin/                  # 위챗 관리단말
├── config/                     # 설정 파일
│   ├── route.php
│   ├── middleware.php
│   ├── database.php
│   ├── jwt.php
│   ├── snowflake.php
│   ├── hashids.php
│   ├── encryption.php
│   ├── encryptable.php
│   └── ...
├── database/
│   └── backup/                 # 백업 스크립트(테이블 구조와 시드 데이터는 docs/install.sql 통일)
├── docs/                       # 관리 백엔드 문서
├── public/                     # 진입 파일
├── runtime/                    # 런타임
├── tests/                      # 테스트
├── vendor/                     # 의존성
├── CLAUDE.md
├── composer.json
├── Dockerfile
└── docker-compose.yml
```

## service/ — 비즈니스 API

```
service/
├── app/
│   ├── api/v1/controller/       # 공개 API v1(컨트롤러 26개)
│   │   ├── AuthController          # 로그인/회원가입/비밀번호 찾기/갱신/신원 전환
│   │   ├── CaptchaController       # 문자 인증코드(Redis 속도 제한)
│   │   ├── CommonController        # 공용 설정/약관/지역
│   │   ├── ContentController       # 배너/공지/게시글
│   │   ├── DocsController          # OpenAPI 문서(hg/apidoc)
│   │   ├── LbsController           # 주변 매장(Haversine)/역지오코딩
│   │   ├── GuestController         # 게스트 모드(비로그인 읽기 전용 조회, Redis 캐시)
│   │   ├── SeckillController       # 번개세일 활동/선착(독립 채널)
│   │   ├── PromotionController     # 공동구매(기존 flash_sale 채널 폐지)
│   │   ├── ServiceController       # 서비스 분류/항목/상품/매장
│   │   ├── ServicePackageController # 서비스 패키지
│   │   ├── StoreManagerController  # 매장장 작업대(overview/orders/technicians/revenue)
│   │   ├── TechnicianController    # 기술자 공개 정보
│   │   ├── BrowseHistoryController # 조회 이력
│   │   ├── CalendarController      # 예약 월력(월/일 보기)
│   │   ├── CommunityController     # 커뮤니티 동향
│   │   ├── CommunityCommentController # 커뮤니티 댓글
│   │   ├── FullReductionController # 만 N 원 할인 활동
│   │   ├── PaymentNotifyController # 결제 콜백(위챗/알리페이)
│   │   ├── PrintController         # 프린팅
│   │   ├── PrivacyController       # 개인정보 컴플라이언스(데이터 내보내기/탈퇴)
│   │   ├── QueueController         # 대기 순번 호출
│   │   ├── VersionController       # APP 버전 관리/업데이트 탐지
│   │   ├── VideoController         # 영상
│   │   ├── WechatController        # 위챗 관련
│   │   └── WheelController         # 포인트 행운의 룰렛
│   ├── user/v1/controller/      # 사용자 모듈 v1(컨트롤러 14개)
│   │   ├── ProfileController       # 개인 정보/비밀번호/휴대폰/탈퇴/로그아웃
│   │   ├── AddressController       # 주소 CRUD(기본 주소 관리)
│   │   ├── FavoriteController      # 즐겨찾기(서비스/기술자)
│   │   ├── FeedbackController      # 의견 피드백(텍스트+이미지)
│   │   ├── ReferralController      # 홍보/QR 코드/추천한 사용자
│   │   ├── CheckInController       # 출석 체크인
│   │   ├── DeviceController        # 사용자 기기 관리
│   │   ├── GrowthController        # 성장 등급(개요/records/levels)
│   │   ├── HealthProfileController # 건강 프로필
│   │   ├── InvoiceController       # 전자 세금계산서 신청/목록/상세
│   │   ├── InvoiceTitleController  # 세금계산서 발행자 라이브러리
│   │   ├── NotifySettingController # 알림 수신 설정
│   │   ├── PointsTransferController# 포인트 양도
│   │   └── TicketController        # 고객센터 티켓
│   ├── technician/v1/controller/ # 기술자 모듈 v1(컨트롤러 10개)
│   │   ├── ProfileController       # 기술자 프로필/입점 신청
│   │   ├── ScheduleController      # 스케줄 조회/설정
│   │   ├── OrderController         # 기술자 주문 목록
│   │   ├── WorkController          # 작업대(today/records/start/complete)
│   │   ├── EarningController       # 수익 개요+거래 내역
│   │   ├── WithdrawController      # 출금 신청(매월 config('withdraw.gate_day')일, 설정 가능)
│   │   ├── ServiceRecordController # 서비스 기록
│   │   ├── ExamController          # 온라인 평가
│   │   ├── AttendanceController    # 출퇴근 체크인 근태
│   │   └── ReviewController        # 기술자 평가 답글
│   ├── order/v1/controller/     # 주문 모듈 v1(컨트롤러 8개 + trait 9개)
│   │   ├── OrderController         # 주문(기술자 잠금)/목록/상세/취소/결제/환불/검증(집계 진입점, 38행, 메서드는 모두 trait에서)
│   │   ├── OrderCreateTrait        # 주문 생성 store/가격 계산 보조 (475행)
│   │   ├── OrderQueryTrait         # 주문 조회 목록/상세/물류 (205행)
│   │   ├── OrderPayTrait           # 결제 pay/잔액 결제/포인트 차감 (415행)
│   │   ├── OrderCancelTrait        # 주문 취소 (272행)
│   │   ├── OrderRefundTrait        # 환불 신청 (379행)
│   │   ├── OrderCompensateTrait    # 환불 보상 스캔+할인/포인트 반환 (345행)
│   │   ├── OrderVerifyTrait        # 검증 수수료/포인트 적립 (256행)
│   │   ├── OrderRescheduleTrait    # 예약 일정 변경 (181행)
│   │   ├── OrderNotifyTrait        # 알림 구독/템플릿/사이트 내/WebSocket (195행)
│   │   └── OrderLockTrait          # 분산 잠금 도구 (80행)
│   │   ├── AftersaleController     # 애프터서비스
│   │   ├── CartController          # 장바구니
│   │   ├── IcsController           # ICS 캘린더 내보내기
│   │   ├── ReviewController        # 평가/추평
│   │   ├── SignatureController     # 서명
│   │   ├── TimelineController      # 주문 상태 타임라인
│   │   └── WaitlistController      # 대기 명단
│   ├── wallet/v1/controller/    # 지갑 모듈 v1(컨트롤러 2개)
│   │   ├── WalletController        # 잔액/충전/거래 내역/잔액 결제
│   │   └── WalletTransferController# 사용자 간 이체
│   ├── marketing/v1/controller/ # 마케팅 모듈 v1(컨트롤러 7개)
│   │   ├── CouponController        # 쿠폰 목록/수령/주문 차감
│   │   ├── CardController          # 멤버십 카드 목록/구매/횟수권 my/use
│   │   ├── PointController         # 포인트 거래 내역/소비 회수
│   │   ├── GiftCardController      # 선물 카드/교환 redeem
│   │   ├── MemberBenefitController # 회원 혜택
│   │   ├── MemberCardController    # 멤버십 카드 정의
│   │   └── PointsExchangeController# 포인트 교환 몰
│   ├── notification/v1/controller/ # 알림 모듈 v1(컨트롤러 1개)
│   │   └── NotificationController  # 알림 목록/읽음 표시
│   ├── common/                  # 공용 기능(BaseController 등)
│   ├── middleware/              # 미들웨어
│   │   ├── ApiVersion              # API 버전 제어(API-Version 헤더)
│   │   ├── Auth                    # JWT 인증+사용자 상태 검증
│   │   ├── Cors                    # 크로스 도메인 처리
│   │   ├── Security                # 보안 탐지(security-php)
│   │   └── TechnicianAuth          # 기술자 신원 검증
│   └── model/                   # 데이터 모델(81개)
│       ├── User.php → erik_user
│       ├── TechnicianProfile.php → erik_technician_profile
│       ├── Service.php → erik_service (ES: erik_services)
│       ├── Product.php → erik_product (ES: erik_products)
│       ├── Store.php → erik_store
│       ├── Order.php → erik_order (환불 규칙/상태 머신 포함)
│       ├── Coupon.php → erik_coupon
│       ├── MemberCard.php → erik_member_card
│       ├── Notification.php → erik_notification
│       └── ... (모델 파일 총 81개; admin에 특유 모델 6개 추가, 합계 87)
├── config/                     # 설정 파일
├── public/                     # 진입점
├── runtime/                    # 런타임
├── vendor/                     # 의존성
├── start.php
├── composer.json
└── Dockerfile
```

## apps/ — 사용자 단말 프런트엔드

### apps/wechat/ — 위챗 미니프로그램

```
apps/wechat/
├── app.js                      # 앱 진입점
├── app.json                    # 전역 설정
├── app.wxss                    # 전역 스타일
├── pages/
│   ├── auth/                   # 인증
│   │   ├── login               #   로그인
│   │   ├── register            #   회원가입
│   │   ├── forget-password     #   비밀번호 찾기
│   │   └── agreement           #   약관 조회
│   ├── home/                   # 홈(배너/공지/분류/검색)
│   ├── service/                # 서비스
│   │   ├── list                #   서비스 목록
│   │   └── detail              #   서비스 상세
│   ├── order/                  # 주문
│   │   ├── list                #   주문 목록
│   │   ├── detail              #   주문 상세
│   │   └── confirm             #   주문 확인
│   ├── cart/                   # 장바구니
│   ├── cards/                  # 멤버십 카드(구매/내 카드/횟수권 사용 my/use)
│   ├── gift-cards/             # 선물 카드(교환 redeem/입금)
│   ├── points/                 # 포인트(거래 내역/교환)
│   ├── marketing/              # 마케팅(쿠폰 등)
│   ├── favorite/               # 즐겨찾기
│   ├── feedback/               # 의견 피드백
│   ├── referral/               # 홍보
│   ├── message/                # 메시지
│   │   ├── list                #   메시지 목록
│   │   └── detail              #   메시지 상세
│   ├── tech-work/              # 기술자 작업대
│   │   ├── index               #   작업대 홈(today/records/start/complete)
│   │   ├── schedule            #   스케줄
│   │   ├── order-list          #   주문
│   │   ├── scan-verify         #   QR 검증
│   │   ├── member-list         #   회원 목록
│   │   ├── member-detail       #   회원 상세
│   │   ├── earnings            #   수익
│   │   ├── withdrawal          #   출금
│   │   ├── transaction-list    #   거래 명세
│   │   └── training            #   교육
│   ├── user/                   # 마이페이지
│   │   ├── index               #   개인 정보
│   │   ├── settings            #   설정
│   │   └── switch-role         #   신원 전환
│   └── wallet/                 # 지갑(잔액/충전/거래 내역)
├── components/                 # 공용 컴포넌트
│   ├── navbar
│   ├── tabbar
│   ├── service-card
│   ├── technician-card
│   ├── coupon-popup
│   └── lbs-selector
├── utils/                      # 도구
│   ├── api.js                  #   HTTP 요청
│   ├── auth.js                 #   인증 관리
│   ├── location.js             #   LBS 위치
│   └── constants.js            #   상수
├── styles/                     # 공용 스타일
└── images/                     # 이미지 리소스
```

### apps/flutter/ — Flutter APP

```
apps/flutter/
├── lib/
│   ├── main.dart               # 진입점
│   ├── app.dart                # App 설정/라우트/테마
│   ├── pages/                  # 페이지(미니프로그램 구조와 동일)
│   │   ├── auth/
│   │   ├── home/
│   │   ├── service/
│   │   ├── order/
│   │   ├── cart/
│   │   ├── technician/
│   │   ├── tech_work/
│   │   ├── user/
│   │   ├── marketing/
│   │   ├── message/
│   │   ├── store/
│   │   └── other/
│   ├── widgets/                # 공용 컴포넌트
│   ├── services/               # API 서비스
│   │   ├── api_service         #   HTTP (Dio)
│   │   ├── auth_service        #   인증
│   │   └── location_service    #   위치
│   ├── models/                 # 데이터 모델
│   ├── state/                  # 상태 관리
│   └── utils/                  # 도구
├── android/                    # Android 프로젝트
├── ios/                        # iOS 프로젝트
├── pubspec.yaml
└── ...
```

## 미들웨어 실행 체인

### service/

```
공개 API:  Cors → Security → RateLimit → Controller
사용자 API:  Cors → Security → RateLimit → Auth → Controller
기술자 API:  Cors → Security → RateLimit → Auth → TechnicianAuth → Controller
결제 콜백: Cors → Security → Controller
```

### admin/

```
공개 API:  Cors → Security → RateLimit → Controller
관리 API:  Cors → Security → RateLimit → AdminAuth → AdminPermission → OperationLog → Controller
헬스 체크: Cors → Security → RateLimit → Controller
```

## 데이터베이스 테이블 목록

모든 테이블은 `erik_` 접두사, BIGINT 비자동증가 기본 키(Snowflake 생성).

| 도메인 | 테이블명 | 설명 |
|----|------|------|
| 사용자 | erik_user | 통일 사용자 테이블 |
| 사용자 | erik_user_address | 배송 주소 |
| 기술자 | erik_technician_profile | 기술자 프로필 |
| 기술자 | erik_technician_schedule | 기술자 스케줄 |
| 기술자 | erik_technician_service | 기술자 제공 가능 서비스 항목 |
| 기술자 | erik_technician_earnings | 기술자 수익 거래 내역 |
| 기술자 | erik_technician_withdrawal | 기술자 출금 기록 |
| 기술자 | erik_technician_attendance | 기술자 근태 |
| 기술자 | erik_technician_member_note | 회원 프로필 |
| 서비스 | erik_service_category | 서비스 분류 |
| 서비스 | erik_service | 서비스 항목 |
| 서비스 | erik_product | 상품 |
| 서비스 | erik_store | 매장 |
| 주문 | erik_order | 주문 메인 테이블(번개세일 seckill_id 연관 컬럼, 24차 라운드) |
| 주문 | erik_order_item | 주문 명세 |
| 주문 | erik_order_payment | 결제 기록 |
| 주문 | erik_order_refund | 환불 기록 |
| 주문 | erik_order_review | 서비스 평가 |
| 주문 | erik_order_verification | 검증 기록 |
| 주문 | erik_order_reschedule | 예약 일정 변경 기록(17차 라운드) |
| 마케팅 | erik_coupon | 쿠폰 정의 |
| 마케팅 | erik_user_coupon | 사용자 쿠폰 |
| 마케팅 | erik_user_coupon_transfer | 쿠폰 양도 기록(17차 라운드) |
| 마케팅 | erik_user_points_transfer | 포인트 양도 기록(19차 라운드) |
| 마케팅 | erik_technician_tier_log | 기술자 등급 변경 로그(17차 라운드) |
| 마케팅 | erik_member_card | 멤버십 카드 정의 |
| 마케팅 | erik_user_member_card | 사용자 멤버십 카드 |
| 마케팅 | erik_member_card_usage | 횟수권 사용 기록 |
| 마케팅 | erik_user_points | 포인트 거래 내역 |
| 마케팅 | erik_gift_card | 선물 카드 |
| 마케팅 | erik_user_referral | 사용자 홍보 |
| 마케팅 | erik_user_favorite | 사용자 즐겨찾기 |
| 지갑 | erik_user_wallet | 사용자 지갑 잔액 |
| 지갑 | erik_wallet_recharge | 지갑 충전 기록 |
| 지갑 | erik_wallet_txn | 지갑 거래 내역 |
| 지갑 | erik_wallet_transfer | 사용자 간 이체 기록(19차 라운드) |
| 사용자 | erik_user_notify_setting | 알림 수신 설정(19차 라운드) |
| 콘텐츠 | erik_banner | 배너 |
| 콘텐츠 | erik_announcement | 공지 |
| 콘텐츠 | erik_platform_agreement | 플랫폼 약관 |
| 콘텐츠 | erik_faq | FAQ |
| 콘텐츠 | erik_feedback | 의견 피드백 |
| 콘텐츠 | erik_moment | SNS 동향 |
| 콘텐츠 | erik_notification | 메시지 알림 |
| 재무 | erik_finance_transaction | 수입 지출 거래 내역 |
| 재무 | erik_technician_commission_config | 수수료 설정 |
| 재무 | erik_withdrawal_account | 출금 계좌 |
| 재무 | erik_withdrawal_config | 출금 제한 설정 |
| 시스템 | erik_admin_user | 관리 사용자(생성됨) |
| 시스템 | erik_admin_role | 역할(생성됨) |
| 시스템 | erik_admin_permission | 권한(생성됨) |
| 시스템 | erik_admin_user_role | 사용자 역할 연관(생성됨) |
| 시스템 | erik_admin_role_permission | 역할 권한 연관(생성됨) |
| 시스템 | erik_system_config | 시스템 설정(생성됨) |
| 시스템 | erik_operation_log | 조작 로그(생성됨) |
| 사용자 | erik_user_growth | 성장값 거래 내역(20차 라운드) |
| 사용자 | erik_growth_level | 성장 등급 단계(20차 라운드) |
| 주문 | erik_invoice | 전자 세금계산서(20차 라운드) |
| 사용자 | erik_ticket | 고객센터 티켓(20차 라운드) |
| 마케팅 | erik_referral_level2_reward | 2단계 수수료 기록(20차 라운드) |
| 사용자 | erik_invoice_title | 세금계산서 발행자 라이브러리(21차 라운드) |
| 사용자 | erik_browse_history | 조회 이력(21차 라운드) |
| 마케팅 | erik_full_reduction_activity | 만 N 원 할인 활동(22차 라운드) |
| 기술자 | erik_technician_attendance | 기술자 근태(22차 라운드) |
| 시스템 | erik_push_log | APP 푸시 기록(22차 라운드) |
| 재무 | erik_profit_sharing | 위챗 분배금 기록(22차 라운드) |
| 주문 | erik_order_status_log | 주문 상태 타임라인(23차 라운드) |
| 사용자 | erik_user_health_profile | 사용자 건강 프로필(23차 라운드) |
| 마케팅 | erik_lucky_wheel | 룰렛 상품 정의(23차 라운드) |
| 마케팅 | erik_wheel_record | 룰렛 추첨 기록(23차 라운드) |
| 마케팅 | erik_seckill_activity | 번개세일 활동(24차 라운드) |
| 시스템 | erik_app_version | APP 버전(24차 라운드) |

### 보충 목록(docs/install.sql 95개 테이블 중 위에 나열되지 않은 부분, 완전한 권위 목록은 install.sql 기준)

| 도메인 | 테이블명 | 설명 |
|----|------|------|
| 마케팅 | erik_card_transfer | 횟수권 양도 |
| 사용자 | erik_check_in | 출석 체크인 |
| 콘텐츠 | erik_community_post | 커뮤니티 동향 |
| 콘텐츠 | erik_community_comment | 커뮤니티 댓글 |
| 기술자 | erik_exam | 평가 |
| 기술자 | erik_exam_question | 평가 문제 |
| 기술자 | erik_exam_attempt | 평가 답안 |
| 시스템 | erik_operation_log_detail | 조작 로그 상세 |
| 주문 | erik_order_aftersale | 주문 애프터서비스 |
| 마케팅 | erik_points_exchange_goods | 포인트 교환 상품 |
| 마케팅 | erik_promotion | 공동구매 활동 |
| 마케팅 | erik_promotion_participant | 공동구매 참여자 |
| 주문 | erik_queue_number | 대기 순번 호출 |
| 서비스 | erik_service_package | 서비스 패키지 |
| 기술자 | erik_service_record | 서비스 기록 |
| 콘텐츠 | erik_share | 공유 기록 |
| 주문 | erik_signature | 서명 |
| 기술자 | erik_technician_tier_config | 기술자 등급 설정 |
| 기술자 | erik_training_course | 교육 강좌 |
| 기술자 | erik_training_progress | 교육 진행도 |
| 사용자 | erik_user_device | 사용자 기기 |
| 마케팅 | erik_user_points_exchange | 포인트 교환 기록 |
| 콘텐츠 | erik_video_post | 영상 동향 |
| 주문 | erik_waitlist | 대기 명단 |

## 외부 서비스 예약

| 서비스 | 용도 | 연동점 |
|------|------|--------|
| 위챗 오픈 플랫폼 | 위챗 로그인/UnionID | WechatAuthService |
| 위챗페이 | 결제/환불/출금 | WechatPayService |
| 문자 서비스 업체 | 인증코드/알림 | SmsService |
| 지도 서비스 | LBS 위치/내비게이션/거리 계산 | MapService |
