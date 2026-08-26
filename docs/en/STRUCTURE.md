# Appointment Service System — Project Structure
> **Languages**: [中文](../STRUCTURE.md) · [한국어](../ko/STRUCTURE.md) · [Русский](../ru/STRUCTURE.md) · [Deutsch](../de/STRUCTURE.md) · [Français](../fr/STRUCTURE.md) · [Español](../es/STRUCTURE.md) · [Português](../pt/STRUCTURE.md) · [हिन्दी](../hi/STRUCTURE.md) · [العربية](../ar/STRUCTURE.md) · [বাংলা](../bn/STRUCTURE.md) · [Bahasa Indonesia](../id/STRUCTURE.md) · [日本語](../ja/STRUCTURE.md)

## Repository Overview

```
appointment-php/
├── admin/              # 管理后台 (webman v2 + Flutter Web)
├── service/            # 业务API服务 (webman v2)
├── apps/               # 用户端前端应用
│   ├── wechat/         #   微信小程序（原生）
│   ├── flutter/        #   Flutter APP（iOS + Android）
│   └── harmonyos/      #   HarmonyOS APP（鸿蒙原生）
├── docs/               # 项目文档
└── .claude/            # Claude Code 配置
```

## Project Relationships

```
┌──────────────────────────────────────────────┐
│                   apps/                       │
│  ┌─────────────┐  ┌──────────┐  ┌─────────┐  │
│  │ wechat/      │  │ flutter/  │  │harmonyos/│  │
│  │ 微信小程序    │  │iOS/Android│  │ 鸿蒙 APP │  │
│  └──────┬──────┘  └────┬─────┘  └────┬────┘  │
│         │     功能完全相同      │            │
│         └──────────┬─────────┘            │
│                    │ HTTP API                 │
├────────────────────┼─────────────────────────┤
│              service/                         │
│         业务API (webman v2)                    │
│             端口: 8787                         │
│                    │                          │
│                    │ 共享 MySQL/Redis/ES       │
│                    │                          │
│              admin/                           │
│         管理后台API (webman v2)                 │
│             端口: 8787                         │
│                    │                          │
│         ┌──────────┴──────────┐               │
│         │                     │               │
│    admin/apps/flutter/    Flutter Web         │
│    管理后台前端 (PC)                           │
└──────────────────────────────────────────────┘
```

## admin/ — Admin Dashboard

```
admin/
├── app/
│   ├── admin/controller/       # 管理端控制器
│   │   ├── BaseController          # 基础控制器
│   │   ├── DashboardController     # 仪表盘
│   │   ├── UserController          # 用户管理
│   │   ├── RoleController          # 角色管理
│   │   ├── PermissionController    # 权限管理
│   │   ├── ConfigController        # 系统配置
│   │   ├── LogController           # 操作日志
│   │   ├── ProfileController       # 个人中心
│   │   ├── ExportController        # 导出
│   │   ├── ImportController        # 导入
│   │   ├── UploadController        # 文件上传
│   │   ├── HealthController        # 健康检查
│   │   ├── DocsController          # API文档
│   │   ├── MetricsController       # Prometheus指标
│   │   │                            # ✅ 已实现的业务模块:
│   │   ├── TechnicianController    #   技师管理(列表/审核/排班/导出)
│   │   ├── MemberController        #   会员管理(等级/消费)
│   │   ├── StoreController         #   门店CRUD
│   │   ├── ServiceController       #   服务项目CRUD
│   │   ├── ServiceCategoryController # 服务分类CRUD(树形)
│   │   ├── ProductController       #   产品CRUD
│   │   ├── MallOrderController     #   商城订单/发货/售后
│   │   ├── SalesStatsController    #   销售统计(Redis缓存)
│   │   ├── AppointmentOrderController  # 预约订单(取消/完成)
│   │   ├── MemberCardController    #   会员卡定义CRUD
│   │   ├── ReviewController        #   服务评价管理
│   │   ├── ReportController        #   数据报表统计
│   │   ├── CouponController        #   优惠券CRUD
│   │   ├── FinanceController       #   财务流水/统计
│   │   ├── WithdrawalController    #   提现审核(通过/驳回/完成)
│   │   ├── CommissionController    #   佣金设置/奖罚
│   │   ├── WithdrawalAccountController # 提现账号管理
│   │   ├── WithdrawalConfigController  # 提现限制配置
│   │   ├── BannerController        #   轮播图CRUD
│   │   ├── AnnouncementController  #   公告CRUD/发布
│   │   ├── FaqController           #   常见问题CRUD
│   │   ├── FeedbackController      #   意见反馈/回复
│   │   ├── MomentController        #   朋友圈审核
│   │   ├── AgreementController     #   协议编辑/发布
│   │   ├── AboutController         #   关于我们设置
│   │   └── SystemMessageController #   系统消息模板/发送
│   │   │                            # ✅ 扩展模块:
│   │   ├── ServiceCardController    #   卡项设计
│   │   ├── SystemMonitorController  #   系统监控
│   │   ├── IpBlacklistController    #   IP黑名单管理
│   │   ├── DbBackupController       #   数据库备份
│   │   ├── SmsConfigController      #   短信配置
│   │   ├── StorageConfigController  #   存储配置
│   │   ├── StoreManagerController   #   店长账号
│   │   ├── TrainingController       #   技师培训
│   │   ├── ScheduledTaskController  #   定时任务
│   │   ├── CustomerProfileController #  客户画像
│   │   ├── BatchMessageController   #   批量推送
│   │   ├── RefundWorkflowController #   退款审核
│   │   ├── TechnicianTierController #   技师等级
│   │   │                            # ✅ 第22-25轮新增:
│   │   ├── FullReductionController  #   满减活动
│   │   ├── AttendanceController     #   技师考勤
│   │   ├── ProfitSharingController  #   微信分账
│   │   ├── LuckyWheelController     #   积分转盘
│   │   ├── PointsExchangeGoodsController # 积分兑换商品
│   │   ├── ReviewAuditController    #   评价图片审核
│   │   ├── InvoiceController        #   电子发票
│   │   ├── TicketController         #   客服工单
│   │   ├── ReferralRewardController #   一级返佣记录
│   │   ├── ReferralLevel2Controller #   二级返佣记录
│   │   ├── ReturnCustomerController #   回头客奖励
│   │   ├── SeckillController        #   秒杀活动
│   │   ├── VersionController        #   APP版本管理
│   │   ├── TechnicianScheduleController # 排班管理/CSV导出
│   │   ├── AftersaleController      #   售后处理
│   │   ├── OrderVerificationController # 核销记录
│   │   ├── CommunityModerationController # 社区审核
│   │   ├── VideoAuditController     #   视频审核
│   │   └── InstallController        #   安装向导
│   ├── api/v1/controller/      # 公开API v1
│   │   ├── AuthController
│   │   └── CaptchaController
│   ├── common/                 # 公共工具
│   │   ├── HashidsService
│   │   ├── SnowflakeService
│   │   ├── EncryptionService
│   │   ├── TechnicianWithdrawalService
│   │   └── WechatPayService
│   ├── middleware/             # 中间件
│   │   ├── Cors
│   │   ├── RateLimit
│   │   ├── ApiVersion
│   │   ├── AdminAuth
│   │   ├── AdminPermission
│   │   └── OperationLog
│   ├── model/                  # 数据模型（仅 6 个特有模型：AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig；其余 psr-4 共享 service 版）
│   ├── queue/                  # 队列任务
│   └── process/                # 进程
├── apps/
│   ├── flutter/                # Flutter Web 管理后台前端
│   │   └── lib/app/
│   │       ├── pages/           #   页面（20个）
│   │       │   ├── dashboard/   #   仪表盘
│   │       │   ├── login/       #   登录
│   │       │   ├── user/        #   用户管理
│   │       │   ├── member/      #   会员管理
│   │       │   ├── role/        #   角色权限
│   │       │   ├── config/      #   系统配置
│   │       │   ├── log/         #   操作日志
│   │       │   ├── profile/     #   个人中心
│   │       │   ├── technician/  #   技师管理
│   │       │   ├── schedule/    #   排班
│   │       │   ├── service/     #   服务/产品管理
│   │       │   ├── service_card/#   卡项设计
│   │       │   ├── order/       #   订单管理
│   │       │   ├── verification/#   核销记录
│   │       │   ├── coupon/      #   优惠券
│   │       │   ├── withdrawal/  #   提现审核
│   │       │   ├── report/      #   报表统计
│   │       │   ├── review/      #   评价管理
│   │       │   ├── announcement/#   公告
│   │       │   └── faq/         #   常见问题
│   │       ├── services/        #   API服务层
│   │       ├── layouts/         #   布局
│   │       └── theme/           #   主题
│   ├── harmonyos/               # HarmonyOS 管理端（ArkTS）
│   └── weixin/                  # 微信管理端
├── config/                     # 配置文件
│   ├── route.php
│   ├── middleware.php
│   ├── database.php
│   ├── jwt.php
│   ├── snowflake.php
│   ├── hashids.php
│   ├── encryption.php
│   ├── encryptable.php
│   └── ...
├── database/
│   └── backup/                 # 备份脚本（表结构与种子数据统一见 docs/install.sql）
├── docs/                       # 管理后台文档
├── public/                     # 入口文件
├── runtime/                    # 运行时
├── tests/                      # 测试
├── vendor/                     # 依赖
├── CLAUDE.md
├── composer.json
├── Dockerfile
└── docker-compose.yml
```

## service/ — Business API

```
service/
├── app/
│   ├── api/v1/controller/       # 公开API v1（26 控制器）
│   │   ├── AuthController          # 登录/注册/忘记密码/刷新/身份切换
│   │   ├── CaptchaController       # 短信验证码(Redis限流)
│   │   ├── CommonController        # 公共配置/协议/区域
│   │   ├── ContentController       # 轮播图/公告/文章
│   │   ├── DocsController          # OpenAPI文档(hg/apidoc)
│   │   ├── LbsController           # 附近门店(Haversine)/逆地理
│   │   ├── GuestController         # 游客模式（未登录只读浏览，Redis缓存）
│   │   ├── SeckillController       # 秒杀活动/抢购（独立通道）
│   │   ├── PromotionController     # 拼团（旧 flash_sale 通道已下线）
│   │   ├── ServiceController       # 服务分类/项目/产品/门店
│   │   ├── ServicePackageController # 服务套餐
│   │   ├── StoreManagerController  # 店长工作台（overview/orders/technicians/revenue）
│   │   ├── TechnicianController    # 技师公开信息
│   │   ├── BrowseHistoryController # 浏览足迹
│   │   ├── CalendarController      # 预约月历（月/日视图）
│   │   ├── CommunityController     # 社区动态
│   │   ├── CommunityCommentController # 社区评论
│   │   ├── FullReductionController # 满减活动
│   │   ├── PaymentNotifyController # 支付回调（微信/支付宝）
│   │   ├── PrintController         # 打印
│   │   ├── PrivacyController       # 隐私合规（数据导出/注销）
│   │   ├── QueueController         # 排队叫号
│   │   ├── VersionController       # APP版本管理/检测更新
│   │   ├── VideoController         # 视频
│   │   ├── WechatController        # 微信相关
│   │   └── WheelController         # 积分幸运转盘
│   ├── user/v1/controller/      # 用户模块 v1（14 控制器）
│   │   ├── ProfileController       # 个人信息/密码/手机/注销/登出
│   │   ├── AddressController       # 地址CRUD(默认地址管理)
│   │   ├── FavoriteController      # 收藏(服务/技师)
│   │   ├── FeedbackController      # 意见反馈(文字+图片)
│   │   ├── ReferralController      # 推广/二维码/已推荐用户
│   │   ├── CheckInController       # 签到打卡
│   │   ├── DeviceController        # 用户设备管理
│   │   ├── GrowthController        # 成长等级（概览/records/levels）
│   │   ├── HealthProfileController # 健康档案
│   │   ├── InvoiceController       # 电子发票申请/列表/详情
│   │   ├── InvoiceTitleController  # 发票抬头库
│   │   ├── NotifySettingController # 消息偏好设置
│   │   ├── PointsTransferController# 积分转赠
│   │   └── TicketController        # 客服工单
│   ├── technician/v1/controller/ # 技师模块 v1（10 控制器）
│   │   ├── ProfileController       # 技师档案/入驻申请
│   │   ├── ScheduleController      # 排班查询/设置
│   │   ├── OrderController         # 技师订单列表
│   │   ├── WorkController          # 工作台(today/records/start/complete)
│   │   ├── EarningController       # 收益概况+流水
│   │   ├── WithdrawController      # 提现申请（每月 config('withdraw.gate_day') 号，可配置）
│   │   ├── ServiceRecordController # 服务记录
│   │   ├── ExamController          # 在线考核
│   │   ├── AttendanceController    # 上下班打卡考勤
│   │   └── ReviewController        # 技师回复评价
│   ├── order/v1/controller/     # 订单模块 v1（8 控制器 + 9 trait）
│   │   ├── OrderController         # 下单(锁技师)/列表/详情/取消/支付/退款/核销（聚合入口，38行，方法全部来自 trait）
│   │   ├── OrderCreateTrait        # 订单创建 store/计价辅助 (475行)
│   │   ├── OrderQueryTrait         # 订单查询 列表/详情/物流 (205行)
│   │   ├── OrderPayTrait           # 支付 pay/余额支付/积分抵扣 (415行)
│   │   ├── OrderCancelTrait        # 取消订单 (272行)
│   │   ├── OrderRefundTrait        # 申请退款 (379行)
│   │   ├── OrderCompensateTrait    # 退款补偿扫描+优惠/积分归还 (345行)
│   │   ├── OrderVerifyTrait        # 核销 佣金/返积分 (256行)
│   │   ├── OrderRescheduleTrait    # 预约改期 (181行)
│   │   ├── OrderNotifyTrait        # 通知 订阅/模板/站内/WebSocket (195行)
│   │   └── OrderLockTrait          # 分布式锁工具 (80行)
│   │   ├── AftersaleController     # 售后
│   │   ├── CartController          # 购物车
│   │   ├── IcsController           # ICS日历导出
│   │   ├── ReviewController        # 评价/追评
│   │   ├── SignatureController     # 签名
│   │   ├── TimelineController      # 订单状态时间线
│   │   └── WaitlistController      # 候补名单
│   ├── wallet/v1/controller/    # 钱包模块 v1（2 控制器）
│   │   ├── WalletController        # 余额/充值/交易流水/余额支付
│   │   └── WalletTransferController# 用户间转账
│   ├── marketing/v1/controller/ # 营销模块 v1（7 控制器）
│   │   ├── CouponController        # 优惠券列表/领取/下单抵扣
│   │   ├── CardController          # 会员卡列表/购买/次卡 my/use
│   │   ├── PointController         # 积分流水/消费回扣
│   │   ├── GiftCardController      # 礼品卡/兑换 redeem
│   │   ├── MemberBenefitController # 会员权益
│   │   ├── MemberCardController    # 会员卡定义
│   │   └── PointsExchangeController# 积分兑换商城
│   ├── notification/v1/controller/ # 通知模块 v1（1 控制器）
│   │   └── NotificationController  # 通知列表/标记已读
│   ├── common/                  # 公共能力（BaseController 等）
│   ├── middleware/              # 中间件
│   │   ├── ApiVersion              # API版本控制(API-Version头)
│   │   ├── Auth                    # JWT认证+用户状态校验
│   │   ├── Cors                    # 跨域处理
│   │   ├── Security                # 安全检测(security-php)
│   │   └── TechnicianAuth          # 技师身份校验
│   └── model/                   # 数据模型(81个)
│       ├── User.php → erik_user
│       ├── TechnicianProfile.php → erik_technician_profile
│       ├── Service.php → erik_service (ES: erik_services)
│       ├── Product.php → erik_product (ES: erik_products)
│       ├── Store.php → erik_store
│       ├── Order.php → erik_order (含退款规则/状态机)
│       ├── Coupon.php → erik_coupon
│       ├── MemberCard.php → erik_member_card
│       ├── Notification.php → erik_notification
│       └── ... (共81个模型文件；admin 另有 6 个特有模型，合计 87)
├── config/                     # 配置文件
├── public/                     # 入口
├── runtime/                    # 运行时
├── vendor/                     # 依赖
├── start.php
├── composer.json
└── Dockerfile
```

## apps/ — User-Side Frontend

### apps/wechat/ — WeChat Mini Program

```
apps/wechat/
├── app.js                      # 应用入口
├── app.json                    # 全局配置
├── app.wxss                    # 全局样式
├── pages/
│   ├── auth/                   # 认证
│   │   ├── login               #   登录
│   │   ├── register            #   注册
│   │   ├── forget-password     #   忘记密码
│   │   └── agreement           #   协议查看
│   ├── home/                   # 首页（轮播图/公告/分类/搜索）
│   ├── service/                # 服务
│   │   ├── list                #   服务列表
│   │   └── detail              #   服务详情
│   ├── order/                  # 订单
│   │   ├── list                #   订单列表
│   │   ├── detail              #   订单详情
│   │   └── confirm             #   确认订单
│   ├── cart/                   # 购物车
│   ├── cards/                  # 会员卡（购买/我的/次卡使用 my/use）
│   ├── gift-cards/             # 礼品卡（兑换 redeem/入账）
│   ├── points/                 # 积分（流水/兑换）
│   ├── marketing/              # 营销（优惠券等）
│   ├── favorite/               # 收藏
│   ├── feedback/               # 意见反馈
│   ├── referral/               # 推广
│   ├── message/                # 消息
│   │   ├── list                #   消息列表
│   │   └── detail              #   消息详情
│   ├── tech-work/              # 技师工作台
│   │   ├── index               #   工作台首页(today/records/start/complete)
│   │   ├── schedule            #   排班
│   │   ├── order-list          #   订单
│   │   ├── scan-verify         #   扫码核销
│   │   ├── member-list         #   会员列表
│   │   ├── member-detail       #   会员详情
│   │   ├── earnings            #   收益
│   │   ├── withdrawal          #   提现
│   │   ├── transaction-list    #   交易明细
│   │   └── training            #   培训
│   ├── user/                   # 个人中心
│   │   ├── index               #   个人信息
│   │   ├── settings            #   设置
│   │   └── switch-role         #   身份切换
│   └── wallet/                 # 钱包（余额/充值/交易流水）
├── components/                 # 公共组件
│   ├── navbar
│   ├── tabbar
│   ├── service-card
│   ├── technician-card
│   ├── coupon-popup
│   └── lbs-selector
├── utils/                      # 工具
│   ├── api.js                  #   HTTP请求
│   ├── auth.js                 #   认证管理
│   ├── location.js             #   LBS定位
│   └── constants.js            #   常量
├── styles/                     # 公共样式
└── images/                     # 图片资源
```

### apps/flutter/ — Flutter APP

```
apps/flutter/
├── lib/
│   ├── main.dart               # 入口
│   ├── app.dart                # App配置/路由/主题
│   ├── pages/                  # 页面（与小程序结构一致）
│   │   ├── auth/
│   │   ├── home/
│   │   ├── service/
│   │   ├── order/
│   │   ├── cart/
│   │   ├── technician/
│   │   ├── tech_work/
│   │   ├── user/
│   │   ├── marketing/
│   │   ├── message/
│   │   ├── store/
│   │   └── other/
│   ├── widgets/                # 公共组件
│   ├── services/               # API服务
│   │   ├── api_service         #   HTTP (Dio)
│   │   ├── auth_service        #   认证
│   │   └── location_service    #   定位
│   ├── models/                 # 数据模型
│   ├── state/                  # 状态管理
│   └── utils/                  # 工具
├── android/                    # Android工程
├── ios/                        # iOS工程
├── pubspec.yaml
└── ...
```

## Middleware Execution Chain

### service/

```
公开API:  Cors → Security → RateLimit → Controller
用户API:  Cors → Security → RateLimit → Auth → Controller
技师API:  Cors → Security → RateLimit → Auth → TechnicianAuth → Controller
支付回调: Cors → Security → Controller
```

### admin/

```
公开API:  Cors → Security → RateLimit → Controller
管理API:  Cors → Security → RateLimit → AdminAuth → AdminPermission → OperationLog → Controller
健康检查: Cors → Security → RateLimit → Controller
```

## Database Table List

All tables use the `erik_` prefix, BIGINT non-auto-increment primary keys (Snowflake-generated).

| Domain | Table | Description |
|--------|-------|-------------|
| User | erik_user | Unified user table |
| User | erik_user_address | Shipping addresses |
| Technician | erik_technician_profile | Technician profiles |
| Technician | erik_technician_schedule | Technician schedules |
| Technician | erik_technician_service | Services a technician can provide |
| Technician | erik_technician_earnings | Technician earnings transactions |
| Technician | erik_technician_withdrawal | Technician withdrawal records |
| Technician | erik_technician_attendance | Technician attendance |
| Technician | erik_technician_member_note | Member profiles |
| Service | erik_service_category | Service categories |
| Service | erik_service | Service items |
| Service | erik_product | Products |
| Service | erik_store | Stores |
| Order | erik_order | Order master table (seckill_id link column, Round 24) |
| Order | erik_order_item | Order items |
| Order | erik_order_payment | Payment records |
| Order | erik_order_refund | Refund records |
| Order | erik_order_review | Service reviews |
| Order | erik_order_verification | Verification records |
| Order | erik_order_reschedule | Rescheduling records (Round 17) |
| Marketing | erik_coupon | Coupon definitions |
| Marketing | erik_user_coupon | User coupons |
| Marketing | erik_user_coupon_transfer | Coupon transfer records (Round 17) |
| Marketing | erik_user_points_transfer | Points transfer records (Round 19) |
| Marketing | erik_technician_tier_log | Technician tier change log (Round 17) |
| Marketing | erik_member_card | Member card definitions |
| Marketing | erik_user_member_card | User member cards |
| Marketing | erik_member_card_usage | Session card usage records |
| Marketing | erik_user_points | Points transactions |
| Marketing | erik_gift_card | Gift cards |
| Marketing | erik_user_referral | User referrals |
| Marketing | erik_user_favorite | User favorites |
| Wallet | erik_user_wallet | User wallet balance |
| Wallet | erik_wallet_recharge | Wallet top-up records |
| Wallet | erik_wallet_txn | Wallet transactions |
| Wallet | erik_wallet_transfer | User-to-user transfer records (Round 19) |
| User | erik_user_notify_setting | Notification preferences (Round 19) |
| Content | erik_banner | Banners |
| Content | erik_announcement | Announcements |
| Content | erik_platform_agreement | Platform agreements |
| Content | erik_faq | FAQs |
| Content | erik_feedback | Feedback |
| Content | erik_moment | Moments (posts) |
| Content | erik_notification | Notifications |
| Finance | erik_finance_transaction | Income/expense transactions |
| Finance | erik_technician_commission_config | Commission config |
| Finance | erik_withdrawal_account | Withdrawal accounts |
| Finance | erik_withdrawal_config | Withdrawal limit config |
| System | erik_admin_user | Admin users (created) |
| System | erik_admin_role | Roles (created) |
| System | erik_admin_permission | Permissions (created) |
| System | erik_admin_user_role | User-role relations (created) |
| System | erik_admin_role_permission | Role-permission relations (created) |
| System | erik_system_config | System config (created) |
| System | erik_operation_log | Operation logs (created) |
| User | erik_user_growth | Growth value transactions (Round 20) |
| User | erik_growth_level | Growth level tiers (Round 20) |
| Order | erik_invoice | E-invoices (Round 20) |
| User | erik_ticket | Customer service tickets (Round 20) |
| Marketing | erik_referral_level2_reward | Level-2 referral commission records (Round 20) |
| User | erik_invoice_title | Invoice title library (Round 21) |
| User | erik_browse_history | Browse history (Round 21) |
| Marketing | erik_full_reduction_activity | Full-reduction activities (Round 22) |
| Technician | erik_technician_attendance | Technician attendance (Round 22) |
| System | erik_push_log | APP push records (Round 22) |
| Finance | erik_profit_sharing | WeChat profit-sharing records (Round 22) |
| Order | erik_order_status_log | Order status timeline (Round 23) |
| User | erik_user_health_profile | User health profiles (Round 23) |
| Marketing | erik_lucky_wheel | Wheel prize definitions (Round 23) |
| Marketing | erik_wheel_record | Wheel draw records (Round 23) |
| Marketing | erik_seckill_activity | Seckill activities (Round 24) |
| System | erik_app_version | APP versions (Round 24) |

### Supplementary List (tables among the 95 in docs/install.sql not listed above; the authoritative full list is install.sql)

| Domain | Table | Description |
|--------|-------|-------------|
| Marketing | erik_card_transfer | Session card transfers |
| User | erik_check_in | Check-in |
| Content | erik_community_post | Community posts |
| Content | erik_community_comment | Community comments |
| Technician | erik_exam | Assessments |
| Technician | erik_exam_question | Assessment questions |
| Technician | erik_exam_attempt | Assessment attempts |
| System | erik_operation_log_detail | Operation log details |
| Order | erik_order_aftersale | Order after-sales |
| Marketing | erik_points_exchange_goods | Points exchange goods |
| Marketing | erik_promotion | Group-buy activities |
| Marketing | erik_promotion_participant | Group-buy participants |
| Order | erik_queue_number | Queue numbers |
| Service | erik_service_package | Service packages |
| Technician | erik_service_record | Service records |
| Content | erik_share | Share records |
| Order | erik_signature | Signatures |
| Technician | erik_technician_tier_config | Technician tier config |
| Technician | erik_training_course | Training courses |
| Technician | erik_training_progress | Training progress |
| User | erik_user_device | User devices |
| Marketing | erik_user_points_exchange | Points exchange records |
| Content | erik_video_post | Video posts |
| Order | erik_waitlist | Waitlists |

## External Service Reservations

| Service | Purpose | Integration Point |
|---------|---------|-------------------|
| WeChat Open Platform | WeChat login/UnionID | WechatAuthService |
| WeChat Pay | Payment/refund/withdrawal | WechatPayService |
| SMS provider | Captcha/notifications | SmsService |
| Map service | LBS location/navigation/distance | MapService |
