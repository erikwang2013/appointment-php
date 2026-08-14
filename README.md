# 预约服务系统

三端预约服务管理平台：用户端微信小程序 + Flutter APP（同账号身份切换）、PC管理后台。

> **项目状态**: 全部完成 ✅ | 113 控制器 | 107 模型 | 388 测试（service 282 / admin 106） | 74 数据表 | 286 路由

## 项目结构

```
appointment-php/
├── admin/              # 管理后台 (webman v2 + Flutter Web)
├── service/            # 业务API服务 (webman v2)
├── apps/               # 用户端前端应用
│   ├── wechat/         #   微信小程序（原生）
│   └── flutter/        #   Flutter APP（iOS + Android）
└── docs/               # 项目文档
```

## 快速开始

### 环境要求

- PHP 8.3+
- MySQL 8.0+
- Redis
- Composer

### Web 安装向导（推荐）

```bash
cd admin/
cp .env.example .env
composer install
php start.php start -d
```

浏览器打开 `http://localhost:8787/install`，按指引填写数据库和管理员账号即可完成安装。

### 手动安装

```bash
# 1. 安装依赖
cd service/ && cp .env.example .env && composer install
cd ../admin/ && cp .env.example .env && composer install

# 2. 一键导入数据库（含全部 55 张表 + 演示数据）
mysql -u root -p < docs/install.sql

# 3. 启动服务
cd service/ && php start.php start -d   # 业务API → :8787
cd ../admin/ && php start.php start -d  # 管理后台 → :8787
```

### Docker 部署

```bash
cd admin/ && cp .env.docker .env && docker-compose up -d
cd ../service/ && cp .env.docker .env && docker-compose up -d
```

## 技术栈

| 层级 | 技术 | 说明 |
|------|------|------|
| 后端框架 | webman v2 (PHP 8.3+) | 高性能常驻内存HTTP服务 |
| 数据库 | MySQL 8.0 | 表前缀 `erik_` |
| 缓存 | Redis | 缓存/限流/Session/队列 |
| 搜索 | Elasticsearch | 全文检索（via webman-scout） |
| 管理后台前端 | Flutter Web | PC管理后台风格 |
| 用户端APP | Flutter | iOS + Android |
| 用户端小程序 | 原生微信小程序 | WXML/WXSS/JS |
| ID生成 | erikwang2013/snowflake-php | BIGINT非自增主键 |
| API ID加解密 | erikwang2013/hashids | 对外隐藏真实ID |
| JWT认证 | erikwang2013/jwt-webman | Bearer Token |
| 敏感数据加密 | erikwang2013/encryption + encryptable | API + DB双层加密 |
| 安全防护 | erikwang2013/security-php | 31种攻击检测 |
| 操作验证 | erikwang2013/poster-php | 敏感操作随机验证 |
| 国家旗帜 | erikwang2013/season | 国旗图标 |
| ES同步 | erikwang2013/webman-scout | 模型自动同步 |

## 系统架构

<img src="docs/diagrams/cn-architecture.svg" alt="cn-architecture.svg" width="100%">

## 核心流程

### 服务预约流程

<img src="docs/diagrams/cn-appointment-flow.svg" alt="cn-appointment-flow.svg" width="100%">

### 支付与退款流程

<img src="docs/diagrams/cn-payment-refund.svg" alt="cn-payment-refund.svg" width="100%">

## 订单生命周期

<img src="docs/diagrams/cn-order-lifecycle.svg" alt="cn-order-lifecycle.svg" width="100%">

## 安全架构

### 纵深防御七层体系

<img src="docs/diagrams/cn-security-defense.svg" alt="cn-security-defense.svg" width="100%">

> 更多详细图示：[流程图](docs/diagrams/FLOWCHART.md)（含技师提现/身份切换）| [功能脑图](docs/diagrams/FUNCTION-DIAGRAM.md) | [全部生命周期](docs/diagrams/LIFECYCLE-DIAGRAM.md) | [完整安全架构](docs/diagrams/SECURITY-ARCHITECTURE.md)

## 核心功能亮点（第 6-10 轮）

| 功能 | 说明 |
|------|------|
| 储值钱包 | user_wallet / wallet_recharge / wallet_txn 表；余额+流水、微信支付充值（回调 R 前缀单号）、订单余额支付（pay_channel=balance）、微信/余额退款自动回充余额 |
| 管理后台 UI 完整补齐 | Flutter Web 20 页面：dashboard/用户/角色/配置/日志/核销/排班/服务/技师/订单/优惠券/会员/次卡/公告/FAQ/提现/评价/报表/个人中心 |
| 小程序订阅消息 | 订单 3 场景订阅推送（支付成功/退款到账/核销成功）；push_sent_at 幂等；未配置模板自动降级站内通知 |
| 技师提现 | 管理端审核；金额 ≥500 两级审批（店长→财务）；状态机 pending→approved→completed（rejected/failed） |
| 次卡核销闭环 | 我的次卡实时计算 used_up/expired；核销 Redis NX 幂等 + 行锁扣次，直建 completed 订单 + OrderItem + OrderPayment(pay_type='card') |
| 技师工作台 | 今日任务/完成记录/开始·完成（行锁+状态机守卫+幂等，完成后写站内通知）；小程序 tech-work 三 Tab |
| 优惠券抵扣 | PriceCalculator：applyCoupon 只读算额 / consume 支付置 used / restoreCouponAndCard 退款幂等归还；fixed/percent + min_amount 门槛 |
| 礼品卡 | redeem 时 cash 类型充值到钱包（行锁防双入账，WalletTxn type='gift_card'），gift 类型仅标记 |
| 积分体系 | 签到返积分；核销消费返积分 floor(paid×1)（order_id 幂等，balance 快照）；退款按比例回扣；明细分页 + type/source 过滤 |
| 会员管理 | erik_user.member_level 列（迁移 000008）；管理端会员卡完整 CRUD（权限 365-369） |
| 小程序下单链路 | 服务详情 → 确认订单（选券/门槛置灰/客户端预估金额）→ POST /order → 微信/余额支付；小程序共 20 个页面 |
| 拼团/秒杀闭环 | join Redis NX 防超卖 + 重复参与 422 + 满员锁定 + 到期惰性关闭；成团下单 store 传 promotion_id 以拼团价（discount_percent）下单，禁用优惠券/次卡/积分叠加，未成团自动取消订单并释放技师锁 |
| 店长工作台 | service /api/store-manager 4 接口（overview/orders/technicians/revenue）store_id 强制隔离（无门店 403）；admin 门店工作台概览 + 订单 store_id 筛选 + Flutter 页面 + 权限 372 |
| 分销返佣 | 被推荐人首单 completed 后按 paid_amount × reward_rate（系统配置，默认 0.05）给推荐人返佣入钱包（WalletTxn referral_reward）；行锁+判空+首单复查三重幂等；earnings 明细 + admin 记录查看（权限 379） |
| 积分兑换商城 | 兑换商品/兑换记录两表；兑换接口 Redis NX + 行锁防超兑 + uk_user_goods 同用户限一次；coupon 发券 / wallet 入账 / gift_card 卡密三结果；admin CRUD + 上下架 + 记录（权限 373-378） |

> 第 8 轮运维性修复：移除 12 处 Poster::verify 潜伏 fatal；DashboardController 统计改用 Capsule Manager 查询。
>
> Round-15 补充：积分回补（取消/退款归还 points_offset 积分，refundOffsetPoints 5 挂接点幂等）；PromotionParticipant 状态改整型常量（修复严格模式下 join 1366 损坏）。
>
> Round-16 补充：积分兑换（PointsExchangeController，类型 consume/source=exchange）；拼团下单（erik_order 新增 promotion_id/participant_id 列）；分销返佣（ReferralRewardService 挂接 WorkController::complete）。

## 文档导航

| 文档 | 说明 |
|------|------|
| [架构说明](docs/ARCHITECTURE.md) | 系统架构、三端关系、技术组件、数据流 |
| [功能说明](docs/FEATURES.md) | 用户端/技师端/管理后台完整功能清单 |
| [架构设计](docs/ARCHITECTURE-DESIGN.md) | 分层设计、中间件链、数据库设计、安全设计 |
| [功能设计](docs/FEATURE-DESIGN.md) | 核心业务流程、业务规则、状态机、退款规则 |
| [API文档](docs/API.md) | 业务API + 管理后台API，含请求/响应示例 + OpenAPI端点 |
| [安装说明](docs/INSTALL.md) | 环境要求、Docker部署、环境变量、第三方配置、常见问题 |
| [使用说明](docs/USAGE.md) | 管理后台配置、用户端/技师端操作、API示例、退款规则 |
| [项目结构](docs/STRUCTURE.md) | 完整目录布局、中间件执行链、数据库表清单 |
| [设计规范](docs/superpowers/specs/2026-05-26-appointment-system-design.md) | 系统设计规范 |
| [实现计划](docs/superpowers/plans/2026-05-26-appointment-system-plan.md) | 分阶段实现计划 |

## 支持项目 / Support

如果这个项目对你有帮助，欢迎支持！感谢你的鼓励 :heart:

If this project helps you, your support is welcome and appreciated!

<table>
  <tr>
    <td align="center" width="50%">
      <img src="docs/weixinpay.png" alt="微信支付 / WeChat Pay" width="130" height="130"><br>
      <b>微信支付</b><br>WeChat Pay
    </td>
    <td align="center" width="50%">
      <img src="docs/alipay.png" alt="支付宝 / Alipay" width="130" height="130"><br>
      <b>支付宝</b><br>Alipay
    </td>
  </tr>
</table>

## 版权

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
