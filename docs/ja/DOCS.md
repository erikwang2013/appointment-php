# 予約サービスシステム — ドキュメントインデックス
> **Languages**: [中文](../README.md) · [English](../en/DOCS.md) · [한국어](../ko/DOCS.md) · [Русский](../ru/DOCS.md) · [Deutsch](../de/DOCS.md) · [Français](../fr/DOCS.md) · [Español](../es/DOCS.md) · [Português](../pt/DOCS.md) · [हिन्दी](../hi/DOCS.md) · [العربية](../ar/DOCS.md) · [বাংলা](../bn/DOCS.md) · [Bahasa Indonesia](../id/DOCS.md)

> **プロジェクトステータス**: すべて完了 ✅ | 143 コントローラー（service 69 / admin 74） | 87 モデル | 757 テスト（service 579 / admin 178） | 95 データテーブル | 479 ルート（service 221 / admin 258）

## コアドキュメント

| ドキュメント | 説明 |
|------|------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | アーキテクチャ説明：システム概要、プロジェクト構成、コアコンポーネント、ミドルウェアチェーン、データフロー |
| [FEATURES.md](FEATURES.md) | 機能説明：ユーザー端 + スタッフワークベンチ + 管理バックエンドの完全な機能リスト |
| [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) | アーキテクチャ設計：階層アーキテクチャ、ミドルウェア設計、データベース設計、セキュリティ設計、ES統合 |
| [FEATURE-DESIGN.md](FEATURE-DESIGN.md) | 機能設計：購入フロー、注文ステートマシン、返金ルール、会員カード設計、身分切替 |
| [STRUCTURE.md](STRUCTURE.md) | プロジェクト構成：四端の完全なディレクトリレイアウト、ミドルウェア実行チェーン、データベーステーブルリスト |
| [INSTALL.md](INSTALL.md) | インストール説明：Web インストールウィザード、手動インストール、Docker デプロイ、環境変数、FAQ |
| [USAGE.md](USAGE.md) | 使用説明：管理バックエンド / ユーザー端 / スタッフ端の操作（API は [API.md](API.md) 参照） |
| [API.md](API.md) | APIドキュメント：業務API + 管理バックエンドAPI、リクエスト/レスポンス例 + OpenAPI エンドポイント |

## 図解（SVG）

すべての図解は [diagrams/](diagrams/) にあり、この言語のミラーセットです。中国語 `cn-*` / 英語 `en-*` のマスターは `docs/diagrams/` に、12 言語それぞれのミラーは `docs/<lang>/diagrams/` にあります：

| 図解 | 説明 | Mermaid ソース |
|------|------|-----------|
| [ja-architecture.svg](diagrams/ja-architecture.svg) | システムアーキテクチャ：四端の階層トポロジー + ミドルウェア + データ層 + 第三者サービス | [ARCHITECTURE-DIAGRAM.md](diagrams/ARCHITECTURE-DIAGRAM.md) |
| [ja-architecture-design.svg](diagrams/ja-architecture-design.svg) | アーキテクチャ設計：7 層 + ミドルウェア実行チェーン + レート制限 + データベース設計原則 + セキュリティ設計 | [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) |
| [ja-feature-design.svg](diagrams/ja-feature-design.svg) | 機能設計：三大機能領域 + 購入フロー + 取引ルール + 資産と権益 + 技術者精算 + 身元切替 + 決済 | [FEATURE-DESIGN.md](FEATURE-DESIGN.md) |
| [ja-project-structure.svg](diagrams/ja-project-structure.svg) | プロジェクト構成：四端のディレクトリツリー + モジュール詳細 | [STRUCTURE.md](STRUCTURE.md) |
| [ja-appointment-flow.svg](diagrams/ja-appointment-flow.svg) | サービス予約フロー | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [ja-payment-refund.svg](diagrams/ja-payment-refund.svg) | 支払いと返金フロー | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [ja-order-lifecycle.svg](diagrams/ja-order-lifecycle.svg) | 注文ライフサイクルのステートマシン | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [ja-lifecycle-overview.svg](diagrams/ja-lifecycle-overview.svg) | 全ライフサイクル一覧（17 件、4 分類） | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [ja-security-defense.svg](diagrams/ja-security-defense.svg) | 多層防御七層体制 | [SECURITY-ARCHITECTURE.md](diagrams/SECURITY-ARCHITECTURE.md) |
| [mascot.svg](diagrams/mascot.svg) | プロジェクトマスコット「カレンダーの精霊ユエ」（SMIL アニメーション、外部依存なし） | — |

## テストとセキュリティ

| ドキュメント | 説明 |
|------|------|
| [TEST-REPORT.md](TEST-REPORT.md) | テストレポート：全量 558 ケース / 2508 アサーションのカバレッジ監査 + HTTP スモーク記録 |
| [AUDIT-REPORT.md](AUDIT-REPORT.md) | 審査レポート：テスト結果、エコシステム設定評価、問題修正記録、コードアーキテクチャ分析 |
| [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) | セキュリティ監査レポート |

## データベースと運用

| ドキュメント | 説明 |
|------|------|
| [install.sql](../install.sql) | 統一インストールスクリプト：67 のマイグレーション統合、2723 行、95 テーブル / 285 権限 / 38 設定 + デモデータ |

## 仕様と計画

| ドキュメント | 説明 |
|------|------|
| [superpowers/specs/2026-05-26-appointment-system-design.md](specs/2026-05-26-appointment-system-design.md) | システム設計仕様 |
| [superpowers/plans/2026-05-26-appointment-system-plan.md](plans/2026-05-26-appointment-system-plan.md) | 実装計画 |

## 管理バックエンドドキュメント

`admin/` 独自ドキュメント：ARCHITECTURE.md、DESIGN.md、SECURITY.md、API.md、nginx-security.conf。
