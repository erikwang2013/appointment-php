# 预约服务系统

三端预约服务管理平台：用户端微信小程序 + Flutter APP（同账号身份切换）、PC管理后台。

> **项目状态**: 全部完成 ✅ | 116 控制器 | 113 模型 | 481 测试（service 372 / admin 109） | 80 数据表 | 303 路由

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
| 预约改期 | POST /api/order/reschedule/{id} 同技师换时间；仅 pending/paid/confirmed 且距原服务开始 ≥6h 可改；order_lock + 新时段技师锁 SETNX(180s) 并发防超卖 + B2 排班冲突校验；落 erik_order_reschedule + SCENE_RESCHEDULE 订阅消息 |
| 优惠券转赠 | 8 位唯一转赠码（uk_code 兜底，7 天有效）；claim 防滥用：Redis NX 锁 + 行锁复验防双花、uk_user_coupon 限转赠一次、被转赠券不可再转、不可自领；懒过期恢复原券 |
| 积分过期 | expires_at（默认 365 天，配置 points.expiry_days）；PointsExpiryTimer 60s 游标扫描写 type=expire 负值扣减（三层幂等）+ 聚合站内通知；过期积分不可抵现/兑换 |
| 技师等级自动评定 | TierRatingService 实时统计订单量+均分回写 profile，按 tier_config 从高到低匹配；仅升级不降级（allowDowngrade 供人工重评）；变更落 erik_technician_tier_log + 站内通知；admin 日志查看（权限 380） |
| 秒杀下单闭环 | store() 传 promotion_id（flash_sale）以秒杀价 round(total×(100−discount_percent)/100,2) 下单；售罄（participants_count≥max_people）422「已抢光」；pay() 懒判定 isFlashSaleClosed 过期自动取消+释放技师锁 |
| 服务开始前提醒 | ServiceReminderTimer 60s 扫描 1h 内开始的 confirmed/serving 订单 → SCENE_REMINDER 订阅消息+站内通知（order_id+type 防重，三层幂等）；模板未配置自动降级站内通知 |
| 到期提醒 | ExpiryReminderTimer 6h 扫描 3 天内到期的会员卡/优惠券 → type=card_expiry/coupon_expiry + SCENE_EXPIRY 订阅消息（order_id 记来源防重） |
| 技师回复评价 | POST /api/technician/review/reply/{order_id}：非本人 404、重复回复 422、回复成功站内通知用户；erik_order_review 补 replied_at；admin 回复详情（权限 381） |
| 充值到账通知 | 微信充值回调事务内写站内通知 type='wallet_recharge'（复用回调幂等，同事务原子提交，失败不阻塞主流程） |
| 余额转账 | POST /api/wallet/transfer 用户间转账：金额 0.01-1000/笔 + 单日 5000 限额；Redis NX 锁 + 双方钱包行锁（user_id 升序防死锁）+ client_token 24h 幂等；WalletTxn transfer_out/transfer_in 双流水含 balance_after 快照；接收方站内通知 type='balance_received' |
| 积分转赠 | POST /api/user/points/transfer 用户间转赠：1-10000 积分 + 单日累计 10000 限额；Redis NX 锁 + 双方最后一条流水 lockForUpdate（升序防死锁）+ 锁内复验；发送方 consume/接收方 earn 双流水（接收含 expires_at 可正常过期）；接收方站内通知 type='points_received' |
| 评价追评 | POST /api/order/review/{order_id}/append：非本人 404/重复 422/空内容 422/非 completed 422，成功写技师站内通知 type='review_append'；erik_order_review 增 append_content/append_images(JSON)/append_at；顺带补注册用户提交评价路由（原 store 无路由不可达）并修复其潜伏 TypeError |
| 用户端物流跟踪 | GET /api/order/logistics/{id}：仅本人 product 订单（404 非本人/非商品/未发货）；读取 order.remark JSON（shipping_company/tracking_no/shipped_at，admin 发货写入）；收货人手机号脱敏 138****5678 |
| 消息偏好设置 | erik_user_notify_setting 表（uk_user_type 唯一键，缺省行=默认开）；GET/PUT /api/user/notify-settings；5 类开关 service_reminder/card_expiry/points_expiry/marketing/system（system 恒开不可关）；notifySettingEnabled 门控 3 定时器 + 订阅事件，关闭则站内通知与订阅消息一并跳过 |

> 第 8 轮运维性修复：移除 12 处 Poster::verify 潜伏 fatal；DashboardController 统计改用 Capsule Manager 查询。
>
> Round-15 补充：积分回补（取消/退款归还 points_offset 积分，refundOffsetPoints 5 挂接点幂等）；PromotionParticipant 状态改整型常量（修复严格模式下 join 1366 损坏）。
>
> Round-16 补充：积分兑换（PointsExchangeController，类型 consume/source=exchange）；拼团下单（erik_order 新增 promotion_id/participant_id 列）；分销返佣（ReferralRewardService 挂接 WorkController::complete）。
>
> Round-17 补充：预约改期（erik_order_reschedule + reschedule 接口）；优惠券转赠（erik_user_coupon_transfer + transfer/claim/transfers）；积分过期（expires_at + PointsExpiryTimer 进程）；技师等级自动评定（TierRatingService + erik_technician_tier_log，权限 380）。
>
> Round-17 修复：AutoCancelTimer 通知插入改用 \support\Model::generateId()（原调用不存在的 Snowflake::generate()，自动取消通知静默失败）。
>
> Round-18 补充：秒杀下单（store() 支持 flash_sale 秒杀价）；服务开始前提醒（ServiceReminderTimer + SCENE_REMINDER）；会员卡/优惠券到期提醒（ExpiryReminderTimer + SCENE_EXPIRY）；技师回复评价（review reply 接口 + replied_at 列 + 权限 381）；充值到账通知（回调事务内 type='wallet_recharge'）。
>
> Round-19 补充：余额转账（erik_wallet_transfer + WalletTransferController，权限内双行锁 + client_token 幂等）；积分转赠（erik_user_points_transfer + PointsTransferController，单日限额 + 双向流水）；评价追评（erik_order_review append 三列 + append 接口 + 补注册 store 路由）；用户端物流跟踪（logistics 接口 + remark JSON 解析 + 手机号脱敏）；消息偏好设置（erik_user_notify_setting + NotifySettingController + 3 定时器门控）。

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
