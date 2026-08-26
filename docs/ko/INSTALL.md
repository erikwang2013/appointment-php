# 예약 서비스 시스템 — 설치 가이드
> **Languages**: [中文](../INSTALL.md) · [English](../en/INSTALL.md) · [Русский](../ru/INSTALL.md) · [Deutsch](../de/INSTALL.md) · [Français](../fr/INSTALL.md) · [Español](../es/INSTALL.md) · [Português](../pt/INSTALL.md) · [हिन्दी](../hi/INSTALL.md) · [العربية](../ar/INSTALL.md) · [বাংলা](../bn/INSTALL.md) · [Bahasa Indonesia](../id/INSTALL.md) · [日本語](../ja/INSTALL.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 환경 요구사항

| 구성 요소 | 최소 버전 | 설명 |
|------|----------|------|
| PHP | 8.3+ | 확장: bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | 테이블 접두사 `erik_`, 문자셋 utf8mb4 |
| Redis | 6.0+ | 캐시 / 속도 제한 / Session / 인증코드 저장 |
| Composer | 2.x | PHP 의존성 관리 |
| Elasticsearch | 8.x (선택) | 전문 검색, 미설치 시 핵심 기능에 영향 없음 |

---

## 1. Web 설치 마법사(권장)

관리 백엔드를 시작한 후 브라우저에서 `/install`에 접속하면 원클릭 설치 마법사가 실행됩니다:

```bash
# 1. 의존성 설치 및 시작
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # 기본 포트 8787
```

브라우저에서 `http://localhost:8787/install`을 열고 4단계로 완료합니다:

1. **환경 검사** — PHP 버전, 필수 확장, 파일 권한 자동 탐지
2. **데이터베이스 설정** — MySQL 연결 정보 입력, 연결 테스트 클릭
3. **관리자 계정** — 애플리케이션 이름, 관리자 사용자 이름과 비밀번호 설정
4. **설치 실행** — SQL 자동 가져오기 → 관리자 생성 → .env 설정 작성

설치 완료 후 설정한 사용자 이름과 비밀번호로 로그인합니다. 설치 성공 시 `.install.lock` 파일이 작성되며, `/install` 인터페이스는 이중 검증(파일 잠금 + isInstalled)으로 재설치를 방지합니다; `.install.lock`은 `.gitignore`에 포함되어 있습니다. 운영 환경에서는 `admin/config/route.php`의 `/install` 라우트 삭제를 권장합니다.

---

## 2. 수동 설치

### 2.1 프로젝트 클론

```bash
git clone <repo-url> appointment-php
cd appointment-php
```

### 2.2 PHP 의존성 설치

```bash
# 비즈니스 API 서비스
cd service/
cp .env.example .env
composer install --no-dev --optimize-autoloader

# 관리 백엔드
cd ../admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

### 2.3 환경 변수 설정

`service/.env`(비즈니스 API)와 `admin/.env`(관리 백엔드)를 편집해 다음 핵심 설정을 수정합니다:

```bash
# 데이터베이스 연결
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment          # service는 appointment, admin은 open_admin 사용
DB_USERNAME=root
DB_PASSWORD=your-password

# Redis 연결
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# JWT 키 — 운영 환경에서 반드시 64자리 랜덤 문자열로 변경
JWT_SECRET_KEY=your-64-char-random-string

# 암호화 키 — 운영 환경에서 반드시 변경
ENCRYPTION_KEY=your-32-byte-key
ENCRYPTABLE_KEY=your-32-byte-key

# Hashids 솔트 — 운영 환경에서 반드시 변경
HASHIDS_SALT=your-random-salt

# 디버그 모드 — 운영 환경에서 반드시 false
APP_DEBUG=false
```

> 전체 변수 설명은 `service/.env.example`과 `admin/.env.example` 참조.

### 2.4 데이터베이스 생성 및 가져오기

```bash
# 데이터베이스 생성(service와 admin은 같은 DB를 써도 되고 분리해도 됨)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 통합 설치 스크립트 가져오기(54+개 전체 테이블 + 권한 데이터 + 데모 데이터 포함)
mysql -u root -p appointment < ../install.sql
mysql -u root -p open_admin < ../install.sql
```

> `../install.sql`은 모든 마이그레이션 파일을 병합한 것으로 총 2723행이며, 관리 백엔드와 비즈니스 서비스의 전체 테이블 구조와 시드 데이터를 포함합니다. 신규 설치 시 1회 실행; 기존 DB에 재실행하면 기본 키/컬럼 충돌로 중단되므로, 업그레이드 시나리오에서는 먼저 백업하거나 충돌을 수동 처리하세요.

### 2.5 서비스 시작

```bash
# 비즈니스 API 서비스 시작(기본 포트 8787)
cd service/
php start.php start -d

# 관리 백엔드 시작(기본 포트 8787)
cd ../admin/
php start.php start -d
```

### 2.6 설치 검증

```bash
# 비즈니스 API
curl http://localhost:8787/api/common/config

# 관리 백엔드 헬스 체크
curl http://localhost:8787/health

# 관리 백엔드 로그인(기본 계정 비밀번호는 아래 참조)
curl -X POST http://localhost:8787/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 2.7 기본 계정

| 역할 | 사용자 이름 | 비밀번호 | 설명 |
|------|--------|------|------|
| 슈퍼 관리자 | `admin` | `admin123` | 전체 권한 보유 |

> 첫 로그인 후 비밀번호를 즉시 변경하세요.

---

## 3. Docker 배포

### 3.1 비즈니스 API 서비스

```bash
cd service/
cp .env.docker .env
# .env 편집, 키와 비밀번호 수정
docker-compose up -d
```

오케스트레이션: nginx (80/443) + app (8787) + mysql (3306) + redis (6379) + elasticsearch (9200)

### 3.2 관리 백엔드

```bash
cd admin/
cp .env.docker .env
docker-compose up -d
```

### 3.3 Docker 환경 데이터베이스 가져오기

```bash
# install.sql을 컨테이너에 복사해 실행
docker cp ../install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## 4. 데이터베이스 구조 개요

| 도메인 | 테이블 수 | 핵심 테이블 |
|----|------|--------|
| 관리 백엔드 | 8 | `erik_admin_user`, `erik_admin_role`, `erik_admin_permission`, `erik_operation_log` |
| 사용자 도메인 | 4 | `erik_user`, `erik_user_address`, `erik_user_favorite`, `erik_user_device` |
| 기술자 도메인 | 8 | `erik_technician_profile`, `erik_technician_schedule`, `erik_technician_earning`, `erik_technician_withdrawal`, `erik_technician_tier_config` |
| 서비스 도메인 | 4 | `erik_service_category`, `erik_service`, `erik_service_package`, `erik_service_record` |
| 주문 도메인 | 5 | `erik_order`, `erik_order_item`, `erik_order_payment`, `erik_order_refund`, `erik_order_review` |
| 마케팅 도메인 | 8 | `erik_coupon`, `erik_member_card`, `erik_gift_card`, `erik_user_points`, `erik_promotion` |
| 대기열 | 1 | `erik_queue_number` |
| 콘텐츠 도메인 | 5 | `erik_banner`, `erik_announcement`, `erik_faq`, `erik_feedback`, `erik_platform_agreement` |
| 커뮤니티 도메인 | 3 | `erik_post`, `erik_comment`, `erik_moment` |
| 매장 | 1 | `erik_store` |
| 교육 | 2 | `erik_training_course`, `erik_training_progress` |
| 평가 | 3 | `erik_exam`, `erik_exam_question`, `erik_exam_attempt` |
| 시스템 | 3 | `erik_system_config`, `erik_notification`, `erik_signature` |
| **합계** | **55** | |

모든 테이블은 `erik_` 접두사, 기본 키 `id`는 BIGINT 비자동증가(snowflake-php가 애플리케이션 계층에서 생성).

---

## 5. 테스트 실행

```bash
# 비즈니스 API 테스트(21 tests)
cd service/
php vendor/bin/phpunit

# 관리 백엔드 테스트(59 tests)
cd admin/
php vendor/bin/phpunit

# 정적 분석
php vendor/bin/phpstan analyse --level=5 app/

# 코드 스타일 검사
php vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## 6. 제3자 서비스 설정

관리 백엔드 「시스템 설정」에서 다음 설정 그룹을 입력합니다:

| 설정 그룹 | 용도 | 필수 여부 |
|--------|------|------|
| `wechat_pay` | 위챗페이 가맹점 번호 / API 키 / 인증서 | 결제 기능에 필요 |
| `wechat_app` | 위챗 미니프로그램 AppID / AppSecret | 위챗 로그인에 필요 |
| `sms` | 문자 서비스 업체 (aliyun/tencent) + 서명/템플릿 | 문자 인증코드에 필요 |
| `map_service` | 지도 서비스 (amap/tencent) + API Key | LBS 기능에 필요 |
| `storage` | 객체 스토리지 (oss/cos) + AccessKey/Endpoint | 파일 업로드에 필요 |

---

## 7. 자주 묻는 질문

**Q: 시작 시 `Class 'support\Model' not found` 오류**
A: `composer dump-autoload` 실행.

**Q: 데이터베이스 연결 실패 `SQLSTATE[HY000] [2002]`**
A: `.env`의 `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` 설정 확인.

**Q: SQL 가져오기 시 인코딩 오류**
A: `mysql -u root -p --default-character-set=utf8mb4 < ../install.sql` 사용

**Q: Redis 연결 실패**
A: Redis가 실행 중인지 확인하고, `REDIS_HOST`/`REDIS_PORT` 설정 확인.

**Q: 포트가 점유됨**
A: `config/server.php`의 `listen` 포트 수정.

**Q: 인증코드가 표시되지 않음**
A: GD 확장이 설치됐는지 확인하고, `POSTER_CAPTCHA_STORAGE` 설정이 올바른지 확인(로컬은 `file` 가능, 운영은 `redis`).

**Q: Elasticsearch가 동작하지 않음**
A: ES는 선택 구성 요소, `SCOUT_HOSTS` 설정이 올바르고 ES 서비스가 실행 중인지 확인.

---

## 8. 디렉터리 구조

```
appointment-php/
├── admin/                    # 관리 백엔드 (webman v2)
│   ├── app/                  # 컨트롤러 / 모델 / 미들웨어
│   ├── config/               # 라우트 / 데이터베이스 / 미들웨어 설정
│   ├── database/             # 백업 스크립트(테이블 구조와 시드 데이터는 docs/install.sql 통일)
│   ├── tests/                # PHPUnit 테스트 (59 tests)
│   ├── .env.example          # 환경 변수 템플릿
│   ├── .env.docker           # Docker 환경 변수
│   ├── Dockerfile            # Docker 빌드 파일
│   └── docker-compose.yml    # Docker 오케스트레이션
├── service/                  # 비즈니스 API 서비스 (webman v2)
│   ├── app/                  # 컨트롤러 / 모델 / 미들웨어
│   ├── config/               # 보안 / 라우트 / 데이터베이스 설정
│   ├── seed.php              # 데모 데이터 시드 러너(../install.sql 데모 데이터 구간 읽기)
│   ├── tests/                # PHPUnit 테스트 (21 tests)
│   ├── .env.example          # 환경 변수 템플릿
│   ├── .env.docker           # Docker 환경 변수
│   ├── Dockerfile            # Docker 빌드 파일
│   └── docker-compose.yml    # Docker 오케스트레이션
├── docs/                     # 문서
│   ├── INSTALL.md            # 본 설치 가이드
│   ├── install.sql           # 통합 데이터베이스 설치 스크립트(2723행)
│   ├── ARCHITECTURE.md       # 아키텍처 설계 문서
│   ├── API.md                # API 참조 문서
│   └── AUDIT-REPORT.md       # 감사 보고서
└── .github/workflows/        # CI/CD 파이프라인
    └── ci.yml
```
