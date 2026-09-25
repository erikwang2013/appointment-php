# 예약 서비스 시스템 — 문서 색인
> **Languages**: [中文](../README.md) · [English](../en/DOCS.md) · [Русский](../ru/DOCS.md) · [Deutsch](../de/DOCS.md) · [Français](../fr/DOCS.md) · [Español](../es/DOCS.md) · [Português](../pt/DOCS.md) · [हिन्दी](../hi/DOCS.md) · [العربية](../ar/DOCS.md) · [বাংলা](../bn/DOCS.md) · [Bahasa Indonesia](../id/DOCS.md) · [日本語](../ja/DOCS.md)

> 한국어 번역 · 원본: [中文](../README.md)

> **프로젝트 상태**: 전체 완료 ✅ | 컨트롤러 143개(service 69 / admin 74) | 모델 87개 | 테스트 757개(service 579 / admin 178) | 데이터 테이블 95개 | 라우트 479개(service 221 / admin 258)

## 핵심 문서

| 문서 | 설명 |
|------|------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | 아키텍처 설명: 시스템 개요, 프로젝트 구성, 핵심 컴포넌트, 미들웨어 체인, 데이터 흐름 |
| [FEATURES.md](FEATURES.md) | 기능 설명: 사용자단말 + 기술자 작업대 + 관리 백엔드 전체 기능 목록 |
| [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) | 아키텍처 설계: 계층 아키텍처, 미들웨어 설계, 데이터베이스 설계, 보안 설계, ES 통합 |
| [FEATURE-DESIGN.md](FEATURE-DESIGN.md) | 기능 설계: 구매 프로세스, 주문 상태 머신, 환불 규칙, 멤버십 카드 설계, 신원 전환 |
| [STRUCTURE.md](STRUCTURE.md) | 프로젝트 구조: 4단말 전체 디렉터리 레이아웃, 미들웨어 실행 체인, 데이터베이스 테이블 목록 |
| [INSTALL.md](INSTALL.md) | 설치 설명: Web 설치 마법사, 수동 설치, Docker 배포, 환경 변수, FAQ |
| [USAGE.md](USAGE.md) | 사용 설명: 관리 백엔드 / 사용자단말 / 기술자단말 조작(API 인터페이스는 [API.md](API.md) 참조) |
| [API.md](API.md) | API 문서: 비즈니스 API + 관리 백엔드 API, 요청 응답 예시 + OpenAPI 엔드포인트 포함 |

## 다이어그램(SVG)

모든 그림은 [diagrams/](diagrams/)에 있으며 이 언어의 미러 세트입니다. 중국어 `cn-*` / 영어 `en-*` 원본은 `docs/diagrams/`에 있고, 12개 언어별 미러는 `docs/<lang>/diagrams/`에 있습니다:

| 그림 | 설명 | Mermaid 소스 |
|------|------|-----------|
| [ko-architecture.svg](diagrams/ko-architecture.svg) | 시스템 아키텍처: 4단말 계층 토폴로지 + 미들웨어 + 데이터 계층 + 제3자 서비스 | [ARCHITECTURE-DIAGRAM.md](diagrams/ARCHITECTURE-DIAGRAM.md) |
| [ko-architecture-design.svg](diagrams/ko-architecture-design.svg) | 아키텍처 설계: 7계층 + 미들웨어 실행 체인 + 요청 제한 + 데이터베이스 설계 원칙 + 보안 설계 | [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) |
| [ko-feature-design.svg](diagrams/ko-feature-design.svg) | 기능 설계: 3대 기능 영역 + 구매 흐름 + 거래 규칙 + 자산과 권익 + 기술자 정산 + 신원 전환 + 결제 | [FEATURE-DESIGN.md](FEATURE-DESIGN.md) |
| [ko-project-structure.svg](diagrams/ko-project-structure.svg) | 프로젝트 구조: 4단말 디렉터리 트리 + 모듈 상세 | [STRUCTURE.md](STRUCTURE.md) |
| [ko-appointment-flow.svg](diagrams/ko-appointment-flow.svg) | 서비스 예약 프로세스 | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [ko-payment-refund.svg](diagrams/ko-payment-refund.svg) | 결제와 환불 프로세스 | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [ko-order-lifecycle.svg](diagrams/ko-order-lifecycle.svg) | 주문 생애주기 상태 머신 | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [ko-lifecycle-overview.svg](diagrams/ko-lifecycle-overview.svg) | 전체 생애주기 개요(17개, 네 가지 분류) | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [ko-security-defense.svg](diagrams/ko-security-defense.svg) | 심층 방어 7계층 체계 | [SECURITY-ARCHITECTURE.md](diagrams/SECURITY-ARCHITECTURE.md) |
| [mascot.svg](diagrams/mascot.svg) | 프로젝트 마스코트 「예약 토끼」(SMIL 애니메이션, 외부 의존성 없음) | — |

## 테스트와 보안

| 문서 | 설명 |
|------|------|
| [TEST-REPORT.md](TEST-REPORT.md) | 테스트 보고서: 전체 558개 케이스 / 2508개 단언 커버리지 감사 + HTTP 스모크 기록 |
| [AUDIT-REPORT.md](AUDIT-REPORT.md) | 감사 보고서: 테스트 결과, 생태계 설정 점수, 문제 수정 기록, 코드 아키텍처 분석 |
| [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) | 보안 감사 보고서 |

## 데이터베이스와 운영

| 문서 | 설명 |
|------|------|
| [install.sql](../install.sql) | 통합 설치 스크립트: 67개 마이그레이션 병합, 2723행, 95개 테이블 / 285개 권한 / 38개 설정 + 데모 데이터 |

## 규격과 계획

| 문서 | 설명 |
|------|------|
| [superpowers/specs/2026-05-26-appointment-system-design.md](specs/2026-05-26-appointment-system-design.md) | 시스템 설계 규격 |
| [superpowers/plans/2026-05-26-appointment-system-plan.md](plans/2026-05-26-appointment-system-plan.md) | 구현 계획 |

## 관리 백엔드 문서

`admin/` 자체 문서: ARCHITECTURE.md, DESIGN.md, SECURITY.md, API.md, nginx-security.conf.
