# 预约服务系统 — 文档索引

> **项目状态**: 全部完成 ✅ | 143 控制器（service 69 / admin 74） | 87 模型 | 722 测试（service 558 / admin 164） | 95 数据表 | 388 路由（service 227 / admin 161）

## 核心文档

| 文档 | 说明 |
|------|------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | 架构说明：系统总览、项目组成、核心组件、中间件链、数据流 |
| [FEATURES.md](FEATURES.md) | 功能说明：用户端 + 技师工作台 + 管理后台 完整功能清单 |
| [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) | 架构设计：分层架构、中间件设计、数据库设计、安全设计、ES集成 |
| [FEATURE-DESIGN.md](FEATURE-DESIGN.md) | 功能设计：购买流程、订单状态机、退款规则、会员卡设计、身份切换 |
| [STRUCTURE.md](STRUCTURE.md) | 项目结构：四端完整目录布局、中间件执行链、数据库表清单 |
| [INSTALL.md](INSTALL.md) | 安装说明：Web安装向导、手动安装、Docker部署、环境变量、FAQ |
| [USAGE.md](USAGE.md) | 使用说明：管理后台 / 用户端 / 技师端操作（API 接口见 [API.md](API.md)） |
| [API.md](API.md) | API文档：业务API + 管理后台API，含请求响应示例 + OpenAPI端点 |

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
| [TEST-REPORT.md](TEST-REPORT.md) | 测试报告：全量 558 用例 / 2508 断言覆盖审计 + HTTP 冒烟记录 |
| [AUDIT-REPORT.md](AUDIT-REPORT.md) | 审查报告：测试结果、生态配置评分、问题修复记录、代码架构分析 |
| [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) | 安全审计报告 |

## 数据库与运维

| 文档 | 说明 |
|------|------|
| [install.sql](install.sql) | 统一安装脚本：67 个迁移合并，2723 行，95 表 / 285 权限 / 38 配置 + 演示数据 |

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
| 简体中文 | [docs/README.md](README.md)（本页） |
| English | [docs/en/README.md](en/README.md) |
| 한국어 | [docs/ko/README.md](ko/README.md) |
| Русский | [docs/ru/README.md](ru/README.md) |
| Deutsch | [docs/de/README.md](de/README.md) |
| Français | [docs/fr/README.md](fr/README.md) |
| Español | [docs/es/README.md](es/README.md) |
| Português | [docs/pt/README.md](pt/README.md) |
| हिन्दी | [docs/hi/README.md](hi/README.md) |
| العربية | [docs/ar/README.md](ar/README.md) |
| বাংলা | [docs/bn/README.md](bn/README.md) |
| Bahasa Indonesia | [docs/id/README.md](id/README.md) |
| 日本語 | [docs/ja/README.md](ja/README.md) |

### 虚拟币打赏 (Crypto Donation)

如果这个项目对你有帮助，欢迎扫描二维码打赏支持，谢谢！

| 主网 (Network) | 二维码 (QR Code) | 钱包地址 (Wallet Address) |
|---|---|---|
| BNB Smart Chain (BEP20) | [<img src="./coin/1.jpg" width="150" alt="BNB Smart Chain (BEP20)">](./coin/1.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Tron (TRC20) | [<img src="./coin/2.jpg" width="150" alt="Tron (TRC20)">](./coin/2.jpg) | `TEdDHWLajt1XvqtPDWmQctdrJaC3pzZZzz` |
| Ethereum (ERC20) | [<img src="./coin/3.jpg" width="150" alt="Ethereum (ERC20)">](./coin/3.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Aptos | [<img src="./coin/4.jpg" width="150" alt="Aptos">](./coin/4.jpg) | `0x836e3780edfc3f7b2372b39e2a1a3a5d7adfaccd96c726f21cfde1b50dd68030` |
| Plasma | [<img src="./coin/5.jpg" width="150" alt="Plasma">](./coin/5.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Polygon POS | [<img src="./coin/6.jpg" width="150" alt="Polygon POS">](./coin/6.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| Solana | [<img src="./coin/7.jpg" width="150" alt="Solana">](./coin/7.jpg) | `2hfhboHdmdrYsY25XfQSsEWxq5ip4EQsR7f4AzSRMUyr` |
| The Open Network (TON) | [<img src="./coin/8.jpg" width="150" alt="The Open Network (TON)">](./coin/8.jpg) | `UQB9kFQohzmXUir9QSSZq01iwl9aQZIDdBpNmDklljRtCoGK` |
| Arbitrum One | [<img src="./coin/9.jpg" width="150" alt="Arbitrum One">](./coin/9.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |
| AVAX C-Chain | [<img src="./coin/10.jpg" width="150" alt="AVAX C-Chain">](./coin/10.jpg) | `0x355d429f97511897ccb4e271ec888205f9ab6629` |

