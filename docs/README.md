# 预约服务系统 — 文档索引

> **项目状态**: 全部完成 ✅ | 124 控制器（service 62 / admin 62） | 65 模型 | 96 测试（service 70 / admin 26） | 73 数据表 | 309 路由（service 171 / admin 138）

## 核心文档

| 文档 | 说明 |
|------|------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | 架构说明：系统总览、项目组成、核心组件、中间件链、数据流 |
| [FEATURES.md](FEATURES.md) | 功能说明：用户端 + 技师工作台 + 管理后台 完整功能清单 |
| [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) | 架构设计：分层架构、中间件设计、数据库设计、安全设计、ES集成 |
| [FEATURE-DESIGN.md](FEATURE-DESIGN.md) | 功能设计：购买流程、订单状态机、退款规则、会员卡设计、身份切换 |
| [STRUCTURE.md](STRUCTURE.md) | 项目结构：双端完整目录布局、中间件执行链、数据库表清单 |
| [INSTALL.md](INSTALL.md) | 安装说明：Web安装向导、手动安装、Docker部署、环境变量、FAQ |
| [USAGE.md](USAGE.md) | 使用说明：管理后台 / 用户端 / 技师端操作（API 接口见 [API.md](API.md)） |
| [API.md](API.md) | API文档：业务API + 管理后台API，含请求响应示例 + OpenAPI端点 |

## 图示（SVG）

全部图示位于 [diagrams/](diagrams/)，中文 `cn-*` / 英文 `en-*` 双份：

| 图示 | 说明 | Mermaid 源 |
|------|------|-----------|
| [cn-architecture.svg](diagrams/cn-architecture.svg) | 系统架构：双端分层拓扑 + 中间件 + 数据层 + 第三方服务 | [ARCHITECTURE-DIAGRAM.md](diagrams/ARCHITECTURE-DIAGRAM.md) |
| [cn-architecture-design.svg](diagrams/cn-architecture-design.svg) | 架构设计：7 层分层 + 中间件执行链 + 限流 + 数据库设计原则 + 安全设计 | [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) |
| [cn-feature-design.svg](diagrams/cn-feature-design.svg) | 功能设计：三大功能域 + 购买流程 + 交易规则 + 资产权益 + 技师结算 + 身份切换 + 支付 | [FEATURE-DESIGN.md](FEATURE-DESIGN.md) |
| [cn-project-structure.svg](diagrams/cn-project-structure.svg) | 项目结构：双端目录树 + 模块明细 | [STRUCTURE.md](STRUCTURE.md) |
| [cn-appointment-flow.svg](diagrams/cn-appointment-flow.svg) | 服务预约流程 | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [cn-payment-refund.svg](diagrams/cn-payment-refund.svg) | 支付与退款流程 | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [cn-order-lifecycle.svg](diagrams/cn-order-lifecycle.svg) | 订单生命周期状态机 | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [cn-security-defense.svg](diagrams/cn-security-defense.svg) | 纵深防御七层体系 | [SECURITY-ARCHITECTURE.md](diagrams/SECURITY-ARCHITECTURE.md) |
| [mascot.svg](diagrams/mascot.svg) | 项目宠物——日历精灵「小约」（SMIL 动画，无外部依赖，透明背景） | — |
| [pet-expressions/](pet-expressions/) | 项目宠物 12 种表情形态（透明背景，CSS 动画，遵循 `prefers-reduced-motion`） | — |

## 快速安装

推荐使用 **Web 安装向导**（详细步骤见 [INSTALL.md](INSTALL.md)）：

```bash
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # 默认端口 8787
```

浏览器打开 `http://localhost:8787/install`，按 4 步完成：**环境检查** → **数据库配置** → **管理员账号** → **执行安装**（自动导入 SQL + 创建管理员 + 写入 .env）。安装成功后访问 `http://localhost:8787` 登录管理后台（默认 `admin` / `admin123`，首次登录请立即改密）。也支持手动安装（`mysql < install.sql`）与 Docker 部署，见 [INSTALL.md](INSTALL.md)。

## 测试与安全

| 文档 | 说明 |
|------|------|
| [TEST-REPORT.md](TEST-REPORT.md) | 测试报告：全量 675 用例（96 个测试文件）覆盖审计 + HTTP 冒烟记录 |
| [AUDIT-REPORT.md](AUDIT-REPORT.md) | 审查报告：测试结果、生态配置评分、问题修复记录、代码架构分析 |
| [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) | 安全审计报告 |

## 数据库与运维

| 文档 | 说明 |
|------|------|
| [install.sql](install.sql) | 统一安装脚本：58 个迁移合并，2250 行，73 表 / 权限与配置种子 + 演示数据 |

## 规范与计划

| 文档 | 说明 |
|------|------|
| [superpowers/specs/2026-05-26-appointment-system-design.md](superpowers/specs/2026-05-26-appointment-system-design.md) | 系统设计规范 |
| [superpowers/plans/2026-05-26-appointment-system-plan.md](superpowers/plans/2026-05-26-appointment-system-plan.md) | 实现计划 |

## 管理后台文档

`admin/` 自有文档：ARCHITECTURE.md、DESIGN.md、SECURITY.md、API.md、nginx-security.conf。

## 多语言文档 / Languages

| 语言 | 入口 |
|------|------|
| 简体中文 | [docs/README.md](README.md) |
| English | [docs/en/README.md](en/README.md) |
