# 预约服务系统

双端预约服务管理平台：用户端微信小程序、PC 管理后台。

> **项目状态**: 全部完成 ✅ | 115 控制器（service 53 / admin 62） | 65 模型 | 93 测试（service 68 / admin 25） | 73 数据表 | 311 路由（service 173 / admin 138）

## 项目介绍

<img src="docs/diagrams/mascot.svg" alt="预约服务系统吉祥物——日历精灵「小约」（SVG 动画）" width="200" align="right">

**预约服务系统**是一套面向生活服务行业的双端预约管理平台：用户端为**微信小程序**，配合 **PC 管理后台**，实现"用户预约 → 技师接单 → 后台运营"全流程数字化闭环。无论门店预约、技师服务、会员营销还是财务结算，一套系统全部搞定。

**一站式预约体验**

用户端体验流畅：按日历直观选时预约、优惠券/次卡/积分抵扣、拼团优惠、微信/余额支付，订单状态全程可追踪——改期、取消、退款、售后全流程在线完成；技师端提供工作台、上下班打卡、批量排班、服务核销与提现审批，运营效率一目了然。

**全链路营销增长**

内置拼团、优惠券转赠、积分商城、会员卡/成长等级权益、两级分销返佣、回头客奖励等营销工具，配合消息订阅推送与 APP 推送，帮助商家持续拉新、留存与复购。

**企业级安全与合规**

采用自研安全组件：JWT 鉴权、ID 混淆、31 类攻击检测、敏感数据双层加密、价格服务端校验、支付回调严格比对与幂等防重，同时支持微信官方分账、隐私数据导出与账号注销，满足合规要求。

**成熟的技术底座**

基于 PHP 8.3 + webman 高性能常驻框架，MySQL 8.0 + Redis + Elasticsearch 支撑；73 张数据表、311 个接口、93 项自动化测试全部通过，并有完善的中英文架构文档与一键安装向导，开箱即用、易于二次开发。

无论是单店预约还是多门店连锁，预约服务系统都能为您提供稳定、安全、可扩展的一体化解决方案。

## 项目结构

```
appointment-php/
├── admin/                     # 管理后台 (webman v2 + Flutter Web，独立部署 :8787)
│   ├── app/                   #   admin(后台控制器)/api/v1/model/middleware/process/view
│   ├── apps/                  #   Flutter Web 后台 / 微信管理端
│   ├── config/                #   路由/数据库/进程/插件配置
│   ├── database/              #   备份脚本（表结构与种子数据统一见 docs/install.sql）
│   ├── tests/                 #   PHPUnit（#[\Test] 属性风格）
│   └── start.php
├── service/                   # 业务API服务 (webman v2，独立部署 :8787)
│   ├── app/                   #   api/user/technician/order/wallet/marketing/notification 等模块
│   ├── config/                #   路由/数据库/进程/支付等配置
│   ├── support/               #   Model 基类（generateId）/Request/Response
│   ├── tests/                 #   PHPUnit
│   └── start.php
├── apps/                      # 用户端前端应用
│   └── wechat/                #   微信小程序（原生）
└── docs/                      # 项目文档
    ├── API.md / FEATURES.md / STRUCTURE.md / install.sql / README.md ...
    └── diagrams/              #   架构/流程图（SVG + mermaid）
```

**项目结构图**（双端目录 + 模块明细，完整版见 [STRUCTURE.md](docs/STRUCTURE.md)）：

<img src="docs/diagrams/cn-project-structure.svg" alt="cn-project-structure.svg" width="100%">

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

# 2. 一键导入数据库（含全部 73 张表 + 权限/配置种子）
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
| 数据库 | MySQL 8.0 | 表前缀 `appointment_` |
| 缓存 | Redis | 缓存/限流/Session/队列 |
| 搜索 | Elasticsearch | 全文检索（via webman-scout） |
| 管理后台前端 | Flutter Web | PC管理后台风格 |
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

### 架构设计（分层 / 中间件 / 数据库）

7 层分层架构自顶向下单向依赖、中间件按作用域叠加的执行链、限流策略与数据库设计原则，完整说明见 [ARCHITECTURE-DESIGN.md](docs/ARCHITECTURE-DESIGN.md)：

<img src="docs/diagrams/cn-architecture-design.svg" alt="cn-architecture-design.svg" width="100%">

## 核心流程

### 服务预约流程

<img src="docs/diagrams/cn-appointment-flow.svg" alt="cn-appointment-flow.svg" width="100%">

### 支付与退款流程

<img src="docs/diagrams/cn-payment-refund.svg" alt="cn-payment-refund.svg" width="100%">

## 订单生命周期

<img src="docs/diagrams/cn-order-lifecycle.svg" alt="cn-order-lifecycle.svg" width="100%">

### 全量生命周期总览

除订单外，系统另有 10 条业务生命周期（交易 / 资产权益 / 身份账号 / 运营营销四类），完整状态机见 [LIFECYCLE-DIAGRAM.md](docs/diagrams/LIFECYCLE-DIAGRAM.md)：


## 安全架构

### 纵深防御七层体系

<img src="docs/diagrams/cn-security-defense.svg" alt="cn-security-defense.svg" width="100%">

> 更多详细图示：[流程图](docs/diagrams/FLOWCHART.md)（含技师提现/身份切换）| [功能脑图](docs/diagrams/FUNCTION-DIAGRAM.md) | [全部生命周期](docs/diagrams/LIFECYCLE-DIAGRAM.md) | [完整安全架构](docs/diagrams/SECURITY-ARCHITECTURE.md)

## 功能设计

三大功能域、购买流程（服务预约 / 产品购物车）、交易规则（技师锁定 / 退款阶梯 / 折扣）、资产权益（会员卡 / 积分 / 余额 / 新人奖励）、技师结算、身份切换与支付设计的完整设计说明见 [FEATURE-DESIGN.md](docs/FEATURE-DESIGN.md)：

<img src="docs/diagrams/cn-feature-design.svg" alt="cn-feature-design.svg" width="100%">

## 核心功能亮点（第 6-24 轮）

| 功能 | 说明 |
|------|------|
| 储值钱包 | user_wallet / wallet_recharge / wallet_txn 表；余额+流水、微信支付充值（回调 R 前缀单号）、订单余额支付（pay_channel=balance）、微信/余额退款自动回充余额 |
| 管理后台 UI 完整补齐 | Flutter Web 21 页面：仪表盘/用户/角色/配置/日志/核销/排班/服务/技师/订单/优惠券/会员/次卡/公告/FAQ/提现/评价/报表/售后/门店工作台/个人中心 |
| 仪表盘实时统计 | 管理后台首页统计卡 7 项动态渲染（用户总数/今日新增/活跃用户/操作日志/今日预约/待审核提现/待审技师）+ 近30天趋势图（订单量/金额/新增用户/活跃度）+ 用户状态分布饼图 + 最近操作日志，Redis svc:dashboard 缓存 300s |
| 数据报表 | ReportController 3 端点：订单统计 / 技师TOP10业绩 / 渠道分布（GET /admin/reports/orders\|technicians\|distribution，7/30 天范围，Redis 缓存 300s）+ 销售统计（svc:sales_stats 区间汇总）+ 财务统计（svc:finance_stats 收入/退款/提现/佣金） |
| 小程序订阅消息 | 订单 3 场景订阅推送（支付成功/退款到账/核销成功）；push_sent_at 幂等；未配置模板自动降级站内通知 |
| 技师提现 | 管理端审核；金额 ≥500 两级审批（店长→财务）；状态机 pending→approved→completed（rejected/failed） |
| 次卡核销闭环 | 我的次卡实时计算 used_up/expired；核销 Redis NX 幂等 + 行锁扣次，直建 completed 订单 + OrderItem + OrderPayment(pay_type='card') |
| 技师工作台 | 今日任务/完成记录/开始·完成（行锁+状态机守卫+幂等，完成后写站内通知）；小程序 tech-work 三 Tab |
| 优惠券抵扣 | PriceCalculator：applyCoupon 只读算额 / consume 支付置 used / restoreCouponAndCard 退款幂等归还；fixed/percent + min_amount 门槛 |
| 礼品卡 | redeem 时 cash 类型充值到钱包（行锁防双入账，WalletTxn type='gift_card'），gift 类型仅标记 |
| 积分体系 | 签到返积分；核销消费返积分 floor(paid×1)（order_id 幂等，balance 快照）；退款按比例回扣；明细分页 + type/source 过滤 |
| 会员管理 | appointment_user.member_level 列（迁移 000008）；管理端会员卡完整 CRUD（权限 365-369） |
| 小程序下单链路 | 服务详情 → 确认订单（选券/门槛置灰/客户端预估金额）→ POST /order → 微信/余额支付；小程序共 20 个页面 |
| 拼团闭环 | join 重复参与 422 + 满员锁定 + 到期惰性关闭；成团下单 store 传 promotion_id 以拼团价（discount_percent）下单，禁用优惠券/次卡/积分叠加，未成团自动取消订单并释放技师锁（旧 FLASH_SALE 促销通道已下线） |
| 店长工作台 | service /api/v1/store-manager 4 接口（overview/orders/technicians/revenue）store_id 强制隔离（无门店 403）；admin 门店工作台概览 + 订单 store_id 筛选 + Flutter 页面 + 权限 372 |
| 分销返佣 | 被推荐人首单 completed 后按 paid_amount × reward_rate（系统配置，默认 0.05）给推荐人返佣入钱包（WalletTxn referral_reward）；行锁+判空+首单复查三重幂等；earnings 明细 + admin 记录查看（权限 379） |
| 积分兑换商城 | 兑换商品/兑换记录两表；兑换接口 Redis NX + 行锁防超兑 + uk_user_goods 同用户限一次；coupon 发券 / wallet 入账 / gift_card 卡密三结果；admin CRUD + 上下架 + 记录（权限 373-378） |
| 预约改期 | POST /api/v1/order/reschedule/{id} 同技师换时间；仅 pending/paid/confirmed 且距原服务开始 ≥6h 可改；order_lock + 新时段技师锁 SETNX(180s) 并发防超卖 + B2 排班冲突校验；落 appointment_order_reschedule + SCENE_RESCHEDULE 订阅消息 |
| 优惠券转赠 | 8 位唯一转赠码（uk_code 兜底，7 天有效）；claim 防滥用：Redis NX 锁 + 行锁复验防双花、uk_user_coupon 限转赠一次、被转赠券不可再转、不可自领；懒过期恢复原券 |
| 积分过期 | expires_at（默认 365 天，配置 points.expiry_days）；PointsExpiryTimer 60s 游标扫描写 type=expire 负值扣减（三层幂等）+ 聚合站内通知；过期积分不可抵现/兑换 |
| 技师等级自动评定 | TierRatingService 实时统计订单量+均分回写 profile，按 tier_config 从高到低匹配；仅升级不降级（allowDowngrade 供人工重评）；变更落 appointment_technician_tier_log + 站内通知；admin 日志查看（权限 380） |
| 服务开始前提醒 | ServiceReminderTimer 60s 扫描 1h 内开始的 confirmed/serving 订单 → SCENE_REMINDER 订阅消息+站内通知（order_id+type 防重，三层幂等）；模板未配置自动降级站内通知 |
| 到期提醒 | ExpiryReminderTimer 6h 扫描 3 天内到期的会员卡/优惠券 → type=card_expiry/coupon_expiry + SCENE_EXPIRY 订阅消息（order_id 记来源防重） |
| 技师回复评价 | POST /api/v1/technician/review/reply/{order_id}：非本人 404、重复回复 422、回复成功站内通知用户；appointment_order_review 补 replied_at；admin 回复详情（权限 381） |
| 充值到账通知 | 微信充值回调事务内写站内通知 type='wallet_recharge'（复用回调幂等，同事务原子提交，失败不阻塞主流程） |
| 评价追评 | POST /api/v1/order/review/{order_id}/append：非本人 404/重复 422/空内容 422/非 completed 422，成功写技师站内通知 type='review_append'；appointment_order_review 增 append_content/append_images(JSON)/append_at；顺带补注册用户提交评价路由（原 store 无路由不可达）并修复其潜伏 TypeError |
| 用户端物流跟踪 | GET /api/v1/order/logistics/{id}：仅本人 product 订单（404 非本人/非商品/未发货）；读取 order.remark JSON（shipping_company/tracking_no/shipped_at，admin 发货写入）；收货人手机号脱敏 138****5678 |
| 消息偏好设置 | appointment_user_notify_setting 表（uk_user_type 唯一键，缺省行=默认开）；GET/PUT /api/v1/user/notify-settings；5 类开关 service_reminder/card_expiry/points_expiry/marketing/system（system 恒开不可关）；notifySettingEnabled 门控 3 定时器 + 订阅事件，关闭则站内通知与订阅消息一并跳过 |
| 预约月历 | GET /api/v1/calendar/technician/{id}（月视图）+ /day（日视图）：time_slots JSON 展开小时槽、appointment_order 已约时段排除；门店排班可视化选时 |
| 用户成长等级 | appointment_user_growth + appointment_growth_level（青铜0/白银100/黄金500/铂金2000/钻石5000）；签到+10、评价+20、消费每1元1点（复用既有状态复验天然幂等）；GET /api/v1/growth（概览/records/levels 公开档位） |
| 多级分销-二级返佣 | 订单支付后给一级推荐人的推荐人发 paid×level2_rate（配置 0.02）：事务行锁 + uk_order_referred 幂等防重复发放；WalletTxn TYPE_REFERRAL_LEVEL2；admin 记录查看（权限 386） |
| 成长等级权益 | GrowthLevel.benefits 空壳落地：下单按等级 discount_rate 折扣（仅标准订单，券/次卡→等级折扣叠加，折扣额入 discount_amount + 备注可追溯，下限保护截断为 0）；支付回调成长值 floor(paid×points_multiplier) 倍率入账（支付时点取档，不抬级） |
| 评价图片审核 | admin ReviewAuditController：带图评价列表（JSON_LENGTH 过滤 + join 用户/技师名）、隐藏/恢复（hide 仅 visible、restore 仅 hidden，422 双向校验）；隐藏后技师评价列表自动不可见（权限 389-391） |
| 浏览足迹 | appointment_browse_history（uk_user_item 重复浏览只刷 viewed_at）：服务详情挂接记录（try/catch 不阻塞主流程、未登录跳过）；列表 join 服务信息 + hashid；删单条/清空仅本人 |

> 第 8 轮运维性修复：移除 12 处 Poster::verify 潜伏 fatal；DashboardController 统计改用 Capsule Manager 查询。
>
> Round-15 补充：积分回补（取消/退款归还 points_offset 积分，refundOffsetPoints 5 挂接点幂等）；PromotionParticipant 状态改整型常量（修复严格模式下 join 1366 损坏）。
>
> Round-16 补充：积分兑换（PointsExchangeController，类型 consume/source=exchange）；拼团下单（appointment_order 新增 promotion_id/participant_id 列）；分销返佣（ReferralRewardService 挂接 WorkController::complete）。
>
> Round-17 补充：预约改期（appointment_order_reschedule + reschedule 接口）；优惠券转赠（appointment_user_coupon_transfer + transfer/claim/transfers）；积分过期（expires_at + PointsExpiryTimer 进程）；技师等级自动评定（TierRatingService + appointment_technician_tier_log，权限 380）。
>
> Round-17 修复：AutoCancelTimer 通知插入改用 \support\Model::generateId()（原调用不存在的 Snowflake::generate()，自动取消通知静默失败）。
>
> Round-18 补充：服务开始前提醒（ServiceReminderTimer + SCENE_REMINDER）；会员卡/优惠券到期提醒（ExpiryReminderTimer + SCENE_EXPIRY）；技师回复评价（review reply 接口 + replied_at 列 + 权限 381）；充值到账通知（回调事务内 type='wallet_recharge'）。
>
> Round-19 补充：评价追评（appointment_order_review append 三列 + append 接口 + 补注册 store 路由）；用户端物流跟踪（logistics 接口 + remark JSON 解析 + 手机号脱敏）；消息偏好设置（appointment_user_notify_setting + NotifySettingController + 3 定时器门控）。
>
> Round-20 补充：预约月历（CalendarController 月/日视图 + 已约排除）；用户成长等级（appointment_user_growth + appointment_growth_level 5 档 + 签到/评价/消费挂接）；多级分销-二级返佣（payLevel2Reward 事务行锁 + uk_order_referred 幂等，权限 386）。
>
> Round-21 补充：成长等级权益落地（下单 discount_rate 折扣 + 支付 points_multiplier 积分倍率，迁移种子 5 档 benefits）；评价图片审核（ReviewAuditController 隐藏/恢复，权限 389-391）；用户浏览足迹（appointment_browse_history + 详情挂接 + 列表/删除/清空）。
>
> Round-22 补充：ICS 日历导出（RFC5545 我的预约）；技师打卡考勤（appointment_technician_attendance 上下班打卡 + 迟到标记 + admin 统计，权限 392-393）；APP 推送服务（配置驱动抽象 + 5 处事件接入，appointment_push_log）；微信官方分账（appointment_profit_sharing_log 配置驱动 + 降级，权限 394）；隐私合规（数据导出 + 账号注销 72h 状态机 close_status）。
>
> Round-23 补充：用户健康档案（appointment_user_health_profile）；钱包支付密码（appointment_user_wallet pay_password 设置/校验）；技师批量排班（batch 导入 + 重叠冲突检测）；订单状态时间线（appointment_order_status_log 8 状态埋点 + 用户端/后台展示）；积分有效期（points.expiry_days 配置 + 新 earn 流水带 expires_at）。
>
> Round-24 补充：游客模式（/api/v1/guest/* 未登录只读浏览 + Redis 缓存）；APP 版本管理与检测更新（appointment_app_version + /api/v1/app/version，权限 416-419）；回头客奖励（30 天二次消费奖金 type=return_customer，权限 412-414）；排班 CSV 导出（UTF-8 BOM + 时间槽明细，权限 415）。
>
> 2026-08-26 安全加固：下单接口订单项价格一律以数据库记录为准（客户端价格不可信，未知 target_type 422，target_id 必须 hashid），技师提现申请时在途预留、审批转账前复核、并发审批防双打款；微信支付回调 total_fee 与订单应付严格比对、支付宝回调日志脱敏；/install 安装成功写 .install.lock 双重校验防重装；依赖版本收敛（webman-scout 2.0.5 / opensearch-php ^2.6 / dompdf、security-php、webman-database 精确锁定）；两应用 phpstan.neon 修复可运行（php -d memory_limit=2G）。

## 文档导航

| 文档 | 说明 |
|------|------|
| [架构说明](docs/ARCHITECTURE.md) | 系统架构、前后端关系、技术组件、数据流 |
| [功能说明](docs/FEATURES.md) | 用户端/技师端/管理后台完整功能清单 |
| [架构设计](docs/ARCHITECTURE-DESIGN.md) | 分层设计、中间件链、数据库设计、安全设计 |
| [功能设计](docs/FEATURE-DESIGN.md) | 核心业务流程、业务规则、状态机、退款规则 |
| [API文档](docs/API.md) | 业务API + 管理后台API，含请求/响应示例 + OpenAPI端点 |
| [安装说明](docs/INSTALL.md) | 环境要求、Docker部署、环境变量、第三方配置、常见问题 |
| [使用说明](docs/USAGE.md) | 管理后台配置、用户端/技师端操作、退款规则（API 接口见 API.md） |
| [项目结构](docs/STRUCTURE.md) | 完整目录布局、中间件执行链、数据库表清单 |
| [测试报告](docs/TEST-REPORT.md) | 全量测试覆盖审计（当前 93 个测试文件） |
| [设计规范](docs/superpowers/specs/2026-05-26-appointment-system-design.md) | 系统设计规范 |
| [实现计划](docs/superpowers/plans/2026-05-26-appointment-system-plan.md) | 分阶段实现计划 |
| [文档索引](docs/README.md) | 全部文档索引（中英文） |

## 多语言文档 / Languages

| 语言 | 入口 |
|------|------|
| 简体中文 | [README.md](README.md) |
| English | [README_EN.md](README_EN.md) |

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

## 开源版 vs 商业版

开源版是面向个人学习与研究的精简版本；商业版由作者单独提供，保留完整功能与 Flutter 用户端。两者同源，商业版是开源版的严格超集——开源版没有商业版缺失的控制器、模型或数据表。

| 指标 | 开源版 | 商业版 |
|------|--------|--------|
| 控制器 | 115（service 53 / admin 62） | 143（service 69 / admin 74） |
| 数据模型 | 65 | 87 |
| 数据表 | 73 | 95 |
| 路由 | 311（service 173 / admin 138） | 479（service 229 / admin 163） |
| PHP 文件 / 行数 | 437 / 67,898 | 503 / 78,467 |
| 测试文件 | 93 | 109（README 标注 757 个用例） |
| 客户端 | 微信小程序 + PC 管理后台 | 微信小程序 + Flutter 用户端 + PC 管理后台 |

**商业版额外包含**
- **社区与内容**：社区帖子/评论、朋友圈、短视频及内容审核。
- **技师成长**：培训课程/进度、考试与题库、服务记录。
- **门店排队**：排队叫号、候补、电子签名。
- **票据与客服**：电子发票、发票抬头、客服工单与满意度。
- **资产流转**：用户间余额转账、积分转赠。
- **营销活动**：满减、秒杀、积分幸运转盘。
- **用户端**：Flutter APP（16 个页面）；README 另标注 HarmonyOS 用户端，但当前检出目录无源码。
- 另含商业版保留的 SMS、存储、退款流程等管理端配置控制器（部分为遗留入口）。

**开源版保留**
预约与订单、支付/退款/售后、技师工作台/排班/考勤/提现、会员/优惠券/拼团/积分/成长等级、分销返佣、内容公告/FAQ、报表、RBAC/审计、安全防护，以及微信小程序 + PC 管理后台。

**授权**
- 开源版：仅限个人学习与研究；商业使用需取得书面授权。
- 商业版：全功能版本；商业授权、交付与支持条款请联系 **erik@erik.xyz**。

## 项目授权 / Project License

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

- 本项目仅供个人学习、研究使用。
- 未经作者书面授权，禁止任何形式的商业使用（包括但不限于商业部署、付费服务、二次销售、SaaS 运营及以本项目为基础提供商业服务）。
- 商业授权请联系 **erik@erik.xyz**。
- 未经授权的商业使用，作者将在全球范围内依法追究法律责任，并保留终身追责的权利。
- 第三方组件仍遵循其各自原有的许可证；视觉资产适用仓库内的 ASSETS-LICENSE.md（如适用）。

**English:** This project is provided for personal study and research only. Commercial use of any kind requires prior written authorization from the author (erik@erik.xyz). Unauthorized commercial use will be pursued for legal liability worldwide, and the author reserves the right to pursue such liability for life. Third-party components remain under their original licenses; visual assets are governed by ASSETS-LICENSE.md where applicable.

## 项目宠物表情形态 · 小约

<table align="center">
<tr><td align="center"><img src="docs/pet-expressions/smile.svg" width="88" alt="笑"><br><b>笑</b><br><code>:emoji-smile:</code></td><td align="center"><img src="docs/pet-expressions/joy.svg" width="88" alt="欢乐"><br><b>欢乐</b><br><code>:emoji-joy:</code></td><td align="center"><img src="docs/pet-expressions/cry.svg" width="88" alt="哭"><br><b>哭</b><br><code>:emoji-cry:</code></td><td align="center"><img src="docs/pet-expressions/depressed.svg" width="88" alt="沮丧"><br><b>沮丧</b><br><code>:emoji-depressed:</code></td><td align="center"><img src="docs/pet-expressions/terrified.svg" width="88" alt="惊恐"><br><b>惊恐</b><br><code>:emoji-terrified:</code></td><td align="center"><img src="docs/pet-expressions/angry.svg" width="88" alt="愤怒"><br><b>愤怒</b><br><code>:emoji-angry:</code></td></tr>
<tr><td align="center"><img src="docs/pet-expressions/coquettish.svg" width="88" alt="撒娇"><br><b>撒娇</b><br><code>:emoji-coquettish:</code></td><td align="center"><img src="docs/pet-expressions/cute.svg" width="88" alt="卖萌"><br><b>卖萌</b><br><code>:emoji-cute:</code></td><td align="center"><img src="docs/pet-expressions/pleasing.svg" width="88" alt="讨好"><br><b>讨好</b><br><code>:emoji-pleasing:</code></td><td align="center"><img src="docs/pet-expressions/knocking.svg" width="88" alt="敲打"><br><b>敲打</b><br><code>:emoji-knocking:</code></td><td align="center"><img src="docs/pet-expressions/facepalm.svg" width="88" alt="捂脸"><br><b>捂脸</b><br><code>:emoji-facepalm:</code></td><td align="center"><img src="docs/pet-expressions/tongue.svg" width="88" alt="吐舌头"><br><b>吐舌头</b><br><code>:emoji-tongue:</code></td></tr>
</table>

> 12 种彩色动态形态：笑 / 欢乐 / 哭 / 沮丧 / 惊恐 / 愤怒 / 撒娇 / 卖萌 / 讨好 / 敲打 / 捂脸 / 吐舌头。每个 SVG 自带表情、情绪特效与整体动效，并遵循 `prefers-reduced-motion`。所有宠物素材均为透明背景，可直接叠加到任意底色界面。
<!-- pet-expressions:end -->

<!-- assets-license:start -->
## 视觉资产授权 / Visual Assets License

本项目的项目宠物、图片、图标、表情（包括 `docs/pet-expressions/` 等目录下的
SVG / PNG）**不是开源资产**，版权归作者所有。未经书面许可不得复制、修改、再分发或
用于商业用途；商业使用需取得商业授权，联系 **erik@erik.xyz**。详见
[ASSETS-LICENSE.md](ASSETS-LICENSE.md)。

> 代码部分仍遵循仓库原有 `LICENSE`；第三方素材（如明确标注的 Go Gopher 等）仍遵循其原许可证。
<!-- assets-license:end -->
