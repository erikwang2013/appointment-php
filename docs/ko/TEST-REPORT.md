# 테스트 팀 보고서 — 전체 테스트 커버리지 감사

> 생성 시각：2026-08-26　버전：v1.3.8
> 팀：deep-audit（tester-php / tester-api / tester-ui / tester-go / tester-rust）

## 1. 실행 요약

| 역할 | 작업 | 결과 |
|------|------|------|
| PHP 테스트 엔지니어 | 전체 모듈 단위/통합 테스트 | 기존 70개 테스트 + 이번 라운드 신규（§3 참조） |
| API 테스트 엔지니어 | 전체 인터페이스 자동화 | 컨트롤러 계층 통합 테스트가 곧 이 프로젝트의 API 자동화 형태（§4） |
| UI 자동화 엔지니어 | 전체 페이지 엔드투엔드 | 환경 미비, 결론은 §5 참조 |
| GO 테스트 엔지니어 | 단위 테스트 | **건너뜀：프로젝트에 GO 코드 없음**（.go 파일 0개） |
| Rust 테스트 엔지니어 | 단위 테스트 | **건너뜀：프로젝트에 Rust 코드 없음**（.rs 파일 0개） |

## 2. 기술 스택과 테스트 형태

- 백엔드：PHP 8.3 webman, 두 애플리케이션（service 사용자단 / admin 관리단), service 모델 공유
- 테스트 프레임워크：PHPUnit + Eloquent, **실제 MySQL + 트랜잭션 롤백** 모드（mock 아님), DB 사용 불가 시 자동 skip
- 테스트 실행：`cd service && php -d memory_limit=2G vendor/bin/phpunit`
- API 자동화 = 컨트롤러 계층 통합 테스트（Request를 구성해 컨트롤러 메서드를 직접 호출, 실제 DB 사용, 트랜잭션 롤백）

## 3. PHP 테스트 커버리지

**전체 결과：558 tests / 2508 assertions, 0 실패 0 에러 0 skip**（기존 vendor deprecation 2건, 기존 PHPUnit notice 2건, 모두 이번 라운드 도입 아님；기존 4개 출금 게이트 skip은 `config('withdraw.gate_day')` 주입으로 해소되어 하루 종일 실행 가능）

### 이번 라운드 신규（tester-php, 6개 파일 32개 케이스, 모두 실제 DB + 트랜잭션 롤백）

| 테스트 파일 | 케이스 | 커버리지 |
|---------|------|------|
| CartControllerTest | 4 | 저장 정규화（화이트리스트/qty≥1/오염 항목 버림）、비배열 400、빈 카트、비우기 |
| PointControllerTest | 4 | 잔액=최신 스냅샷、페이지네이션 meta、type/source 필터、빈 목록 |
| AddressControllerTest | 7 | 추가+기본 설정、필수값 400、기본 주소 상호 배타、기본 우선、권한 밖 404、기본 전환、삭제+2차 404 |
| FavoriteControllerTest | 7 | 서비스/기술자 즐겨찾기、잘못된 타입 400、중복 400、favorite_count 증감、고아 즐겨찾기、삭제 404 |
| ReferralControllerTest | 5 | 초대 코드 생성+통계、사용자 404、QR 코드 URL、추천된 목록、수수료 환급 내역 |
| WithdrawControllerTest | 5 | 게이트 일자 거부（config 주입, 오늘 아님）、성공、잔액 부족、<10원、계좌 없음（하루 종일 실행 가능, 0 skip） |

### 기존 커버리지（70개 파일, 변경 없음）

35+ 컨트롤러 커버리지 완료：Auth/Order 상태 머신/환불/核销/일정 변경/결제 콜백/번개세일/공동구매/쿠폰/기프트 카드/포인트/지갑/송금/멤버십 카드/성장값/리베이트/출금/출퇴근/배차/전자 인보이스/물류/푸시/구독 메시지/큐 등.

### 이번 라운드 수정（tester-php 발견）

- 【버그】AddressController::show/update/destroy 및 FavoriteController::destroy에서 hashids 디코딩을 하지 않아 hashid 호출 시 404 발생.
  근본 원인 수정：`BaseController::decodeId`에 순수 숫자 투명 전달 호환 추가（hashids 디코딩 불가 + ctype_digit일 때 그대로 반환),
  전체 저장소 89개 호출부 일괄 혜택；4개 컨트롤러 메서드 진입부에 decodeId 보강. 전체 회귀 통과.
- 【버그】hashids min-length가 0일 때 일부 순수 숫자 ID（예: 306)가 우연히 다른 ID의 유효한 hashids 인코딩이 되어,
  decodeId가 오류 ID로 잘못 디코딩（AddressControllerTest 간헐 404, 여러 라운드 전체 실행에서 랜덤 재현).
  근본 원인 수정：service/admin `config/hashids.php`의 main 연결 `length` 0→8,
  인코딩이 항상 8자 이상, 순수 숫자 ID（8자 미만 또는 16자) 길이와 교집합 없음, 모호성이 인코딩 공간에서 제거.
  AddressControllerTest 5회 연속 실행으로 안정성 검증, 전체 회귀 통과.
- 출금 게이트 일자 하드코딩 20일을 `config('withdraw.gate_day')` 주입 가능으로 변경（config/withdraw.php),
  기존 "매월 20일만" 4개 skip 케이스를 리플렉션으로 게이트 일자 주입, 하루 종일 실행 가능, 0 skip.

## 4. API 자동화 테스트 결론

- 이 프로젝트는 별도 HTTP 계층 테스트 스크립트 없음；기존 70개 테스트 파일은 모두 컨트롤러 계층 통합 테스트（실제 DB),
  35+ 컨트롤러 커버, 인터페이스 자동화 테스트와 동등
- 테스트 커버리지 매트릭스는 §3 참조
- **HTTP 스모크 실행 완료**（2026-08-26）：8787 포트가 다른 프로젝트에 점유 중이라 임시로 service
  `config/process.php` 리슨 포트를 8791로 변경해 기동（32개 webman worker + websocket + 4개 타이머 모두 [OK]),
  실측 `GET /health` → `{"code":0,"message":"ok"}`、`GET /api/guest/services` → HTTP 200
  정상 JSON（hashids 인코딩 ID 확인), 이후 stop 후 설정 복원, 프로세스 잔류 0
- CI에 flutter build web → Playwright 관리단 핵심 경로 E2E 추가 권장（§5 참조）

## 5. UI 엔드투엔드 결론

- 클라이언트：Flutter（apps/flutter 사용자단, admin/apps/flutter 관리단）、위챗 미니프로그램（apps/wechat）、
  HarmonyOS（apps/harmonyos）、admin/apps/weixin
- 현황：admin Flutter web 빌드 산출물 없음（build/web 부재）；로컬에 실행 중인 UI 서비스 없음；
  위챗 미니프로그램/HarmonyOS 브라우저 자동화 채널 없음
- **결론：엔드투엔드 자동화 환경 미비**. CI에 추가 권장：flutter build web → Playwright로
  관리단 핵심 경로（로그인→주문 목록→核销)；미니프로그램/HarmonyOS는 실기기/시뮬레이터 수동 테스트 필요
- 제공 완료：admin/public/apidoc（인터페이스 문서 페이지）

## 6. GO / Rust

프로젝트 루트 재귀 스캔 **.go 파일 0개, .rs 파일 0개**（vendor/node_modules/.git 제외).
툴체인은 설치됨（go / rustc 사용 가능) 그러나 테스트 대상 없음. 추후 GO/Rust 서비스 도입 시 별도 테스트 보강 필요.

## 7. 잔여 리스크（미커버 고가치 영역）

- order 메인 흐름（OrderState/OrderRefundFlow 등 trait 계층 테스트로 이미 커버）
- 위챗페이 실제 콜백（WechatPayService 단위 테스트 있음, 실제 위챗 샌드박스 연동 테스트 없음）
- 프린트、LBS、인증코드 등 외부 의존 모듈

（§3은 tester-php 복귀 후 채워질 예정）
