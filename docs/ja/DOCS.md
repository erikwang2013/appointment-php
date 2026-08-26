# 予約サービスシステム — ドキュメントインデックス
> **Languages**: [中文](../README.md) · [English](../en/DOCS.md) · [한국어](../ko/DOCS.md) · [Русский](../ru/DOCS.md) · [Deutsch](../de/DOCS.md) · [Français](../fr/DOCS.md) · [Español](../es/DOCS.md) · [Português](../pt/DOCS.md) · [हिन्दी](../hi/DOCS.md) · [العربية](../ar/DOCS.md) · [বাংলা](../bn/DOCS.md) · [Bahasa Indonesia](../id/DOCS.md)

> **プロジェクトステータス**: すべて完了 ✅ | 143 コントローラー（service 69 / admin 74） | 87 モデル | 722 テスト（service 558 / admin 164） | 95 データテーブル | 388 ルート（service 227 / admin 161）

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
| [superpowers/specs/2026-05-26-appointment-system-design.md](superpowers/specs/2026-05-26-appointment-system-design.md) | システム設計仕様 |
| [superpowers/plans/2026-05-26-appointment-system-plan.md](superpowers/plans/2026-05-26-appointment-system-plan.md) | 実装計画 |

## 管理バックエンドドキュメント

`admin/` 独自ドキュメント：ARCHITECTURE.md、DESIGN.md、SECURITY.md、API.md、nginx-security.conf。
