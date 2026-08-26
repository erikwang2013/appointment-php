# 아키텍처 설계

## 계층 아키텍처

```
┌─────────────────────────────────────────┐
│             표현 계층 (Presentation)      │
│  위챗 미니프로그램 / Flutter APP / Flutter Web │
├─────────────────────────────────────────┤
│              라우트 계층 (Route)           │
│  config/route.php — 라우트 그룹 + 미들웨어 바인딩 │
├─────────────────────────────────────────┤
│            미들웨어 계층 (Middleware)       │
│  Cors → Security → RateLimit → Auth      │
│  → TechnicianAuth → OperationLog         │
├─────────────────────────────────────────┤
│            컨트롤러 계층 (Controller)       │
│  BaseController → 각 비즈니스 Controller   │
├─────────────────────────────────────────┤
│             서비스 계층 (Service)          │
│  common/ — Snowflake/Hashids/Encryption  │
├─────────────────────────────────────────┤
│             모델 계층 (Model)              │
│  Eloquent ORM + Encryptable + Scout      │
├─────────────────────────────────────────┤
│              데이터 계층 (Data)            │
│  MySQL / Redis / Elasticsearch           │
└─────────────────────────────────────────┘
```

## 미들웨어 설계

### 실행 체인

```
Cors → Security(31종 공격 탐지) → RateLimit → Auth(JWT+사용자 상태)
    → [TechnicianAuth(기술자 신원)] → [AdminPermission(RBAC)] → [OperationLog(8개 단말 출처)]
    → Controller
```

### 미들웨어 역할

| 미들웨어 | 범위 | 기능 |
|--------|------|------|
| Cors | 전역 | OPTIONS 사전 검사 + CORS 응답 헤더 |
| Security | 전역 | erikwang2013/security-php, 31종 공격 탐지 |
| RateLimit | 전역 | Redis 슬라이딩 윈도우 + Lua 원자화 |
| Auth | 라우트 그룹 | JWT 파싱 + 사용자 존재성/상태 검증 |
| TechnicianAuth | 라우트 그룹 | 기술자 프로필 조회 + approved 상태 검증 |
| AdminAuth | 라우트 그룹 | Admin 측 JWT 인증 + 블랙리스트 |
| AdminPermission | 라우트 그룹 | RBAC 권한 검증, Redis 60초 캐시 |
| OperationLog | 라우트 그룹 | 조작 로그 + 8개 단말 출처 자동 탐지 |

### 속도 제한 전략

| 인터페이스 | 제한 |
|------|------|
| 기본 | 60회/분/IP |
| 로그인 | 10회/분 |
| 회원가입 | 5회/분 |
| 인증코드 | 1회/60초/휴대폰 번호 |

## 데이터베이스 설계 원칙

### 기본 키 전략

- 모든 기본 키: BIGINT UNSIGNED NOT NULL, 비자동증가
- `erikwang2013/snowflake-php`가 애플리케이션 계층에서 생성
- Model: `$incrementing = false`, `$keyType = 'string'`

### 테이블 접두사

통일 `erik_` 접두사, `config/database.php`에서 설정. Model은 원본 테이블명을 쓰고 ORM이 자동으로 접두사를 추가합니다.

### 민감 필드 암호화

`erikwang2013/encryptable` trait 사용:

```php
use Erikwang2013\Encryptable\Encryptable;

class User extends Model
{
    use Encryptable;
    protected array $encryptable = [
        'phone', 'wx_openid', 'wx_unionid', 'real_name',
    ];
}
```

암호화 필드 VARCHAR 길이는 500으로 설정(암호화 데이터 팽창).

### 소프트 삭제와 타임스탬프

- Eloquent SoftDeletes: `deleted_at` DATETIME DEFAULT NULL
- 모든 테이블에 `created_at` + `updated_at` 포함

## API ID 암호화/복호화 메커니즘

### 요청: decodeIds()

프런트엔드가 hashids 인코딩 ID 전송 → 컨트롤러가 `$this->decodeIds($request->all())` 호출로 디코딩.

### 응답: encodeIds()

DB 조회 결과의 ID → `BaseController::success()`가 자동으로 `encodeIds()` 인코딩 → hashids 문자열 반환.

### 규칙

배열에서 키 이름이 `id`이거나 `_id`로 끝나는 필드를 재귀 처리.

## 보안 설계

### 심층 방어

```
WAF → Cors → Security(31종 탐지) → RateLimit → Auth(JWT+상태)
    → [신원 검증] → [RBAC] → Controller(Model 암호화) → 응답
```

### 인증 보안

- 비밀번호: bcrypt 해시
- JWT: 7일 유효기간 + 갱신 + 블랙리스트
- 잠금: 5회 실패 → 15분
- 동시성: 최대 3개 Token

### 데이터 보안

- API 계층: erikwang2013/encryption
- DB 계층: erikwang2013/encryptable trait
- 로그: 민감 데이터는 로그에 기록하지 않음

### 조작 보안

- erikwang2013/poster-php: 삭제/심사/출금 전 검증
- Security 미들웨어: XSS/SQL 인젝션/CSRF/경로 탐색 탐지

## Elasticsearch 통합

`erikwang2013/webman-scout`가 모델을 ES에 자동 동기화:

```php
use Erikwang2013\WebmanScout\Searchable;

class Service extends Model
{
    use Searchable;
    public function searchableAs(): string { return 'erik_services'; }
}
```

## Excel/PDF 내보내기

- Excel: PhpSpreadsheet, 민감 필드 자동 마스킹
- PDF: Dashboard 패널 시각화 내보내기

## 8개 단말 출처 탐지

OperationLog가 User-Agent를 파싱:

```
iPad → iPadOS / Mac → macOS / Windows → Windows
Linux → Linux / iPhone → ios / Android → android
HarmonyOS → harmonyOS / 기타 → web
```


## TDD 테스트

| 항목 | 테스트 수 | 상태 |
|------|--------|------|
| admin/ | 60 | ✅ 통과 |
| service/ | 21 | ✅ 통과 |
| 합계 | 81 | ✅ |

테스트 커버리지: 환불 규칙 / 주문 상태 / Hashids / 대기열 시스템 / 암호화 / 인증코드
