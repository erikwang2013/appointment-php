# 预约服务系统 — 项目结构

## 仓库总览

```
appointment-php/
├── admin/              # 管理后台 (webman v2 + Flutter Web)
├── service/            # 业务API服务 (webman v2)
├── apps/               # 用户端前端应用
│   ├── wechat/         #   微信小程序（原生）
│   └── flutter/        #   Flutter APP（iOS + Android）
├── docs/               # 项目文档
└── .claude/            # Claude Code 配置
```

## 项目关系

```
┌──────────────────────────────────────────────┐
│                   apps/                       │
│  ┌─────────────┐  ┌──────────────────┐       │
│  │ wechat/      │  │ flutter/          │       │
│  │ 微信小程序    │  │ iOS/Android APP   │       │
│  └──────┬──────┘  └────────┬─────────┘       │
│         │         功能完全相同 │               │
│         └──────────┬─────────┘               │
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

## admin/ — 管理后台

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
│   │   └── TechnicianTierController #   技师等级
│   ├── api/v1/controller/      # 公开API v1
│   │   ├── AuthController
│   │   └── CaptchaController
│   ├── common/                 # 公共工具
│   │   ├── HashidsService
│   │   ├── SnowflakeService
│   │   └── EncryptionService
│   ├── middleware/             # 中间件
│   │   ├── Cors
│   │   ├── RateLimit
│   │   ├── ApiVersion
│   │   ├── AdminAuth
│   │   ├── AdminPermission
│   │   └── OperationLog
│   ├── model/                  # 数据模型
│   ├── queue/                  # 队列任务
│   └── process/                # 进程
├── apps/
│   └── flutter/                # Flutter Web 管理后台前端
│       └── lib/app/
│           ├── pages/           #   页面（20个）
│           │   ├── dashboard/   #   仪表盘
│           │   ├── login/       #   登录
│           │   ├── user/        #   用户管理
│           │   ├── member/      #   会员管理
│           │   ├── role/        #   角色权限
│           │   ├── config/      #   系统配置
│           │   ├── log/         #   操作日志
│           │   ├── profile/     #   个人中心
│           │   ├── technician/  #   技师管理
│           │   ├── schedule/    #   排班
│           │   ├── service/     #   服务/产品管理
│           │   ├── service_card/#   卡项设计
│           │   ├── order/       #   订单管理
│           │   ├── verification/#   核销记录
│           │   ├── coupon/      #   优惠券
│           │   ├── withdrawal/  #   提现审核
│           │   ├── report/      #   报表统计
│           │   ├── review/      #   评价管理
│           │   ├── announcement/#   公告
│           │   └── faq/         #   常见问题
│           ├── services/        #   API服务层
│           ├── layouts/         #   布局
│           └── theme/           #   主题
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
│   ├── migrations/             # SQL迁移
│   └── backup/                 # 备份脚本
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

## service/ — 业务API

```
service/
├── app/
│   ├── api/v1/controller/       # 公开API v1
│   │   ├── AuthController          # 登录/注册/忘记密码/刷新/身份切换
│   │   ├── CaptchaController       # 短信验证码(Redis限流)
│   │   ├── CommonController        # 公共配置/协议/区域
│   │   ├── ContentController       # 轮播图/公告/文章
│   │   ├── DocsController          # OpenAPI文档(hg/apidoc)
│   │   ├── LbsController           # 附近门店(Haversine)/逆地理
│   │   ├── SearchController        # ES全文搜索
│   │   ├── ShareController          # 服务分享
│   │   ├── ServicePackageController # 服务套餐
│   │   ├── PromotionController      # 拼团秒杀
│   │   └── ServiceController       # 服务分类/项目/产品/门店
│   ├── user/v1/controller/      # 用户模块 v1
│   │   ├── ProfileController       # 个人信息/密码/手机/注销/登出
│   │   ├── AddressController       # 地址CRUD(默认地址管理)
│   │   ├── FavoriteController      # 收藏(服务/技师)
│   │   ├── FeedbackController      # 意见反馈(文字+图片)
│   │   └── ReferralController      # 推广/二维码/已推荐用户
│   ├── technician/v1/controller/ # 技师模块 v1
│   │   ├── ProfileController       # 技师档案/入驻申请
│   │   ├── ScheduleController      # 排班查询/设置
│   │   ├── OrderController         # 技师订单列表
│   │   ├── WorkController          # 工作台(today/records/start/complete)
│   │   ├── EarningController       # 收益概况+流水
│   │   ├── WithdrawController      # 提现申请(每月20号)
│   │   ├── ServiceRecordController # 服务记录
│   │   └── ExamController          # 在线考核
│   ├── order/v1/controller/     # 订单模块 v1
│   │   └── OrderController         # 下单(锁技师)/列表/详情/取消/支付/退款/核销
│   ├── wallet/v1/controller/    # 钱包模块 v1
│   │   └── WalletController        # 余额/充值/交易流水/余额支付
│   ├── marketing/v1/controller/ # 营销模块 v1
│   │   ├── CouponController        # 优惠券列表/领取/下单抵扣
│   │   ├── CardController          # 会员卡列表/购买/次卡 my/use
│   │   ├── PointController         # 积分流水/消费回扣
│   │   ├── GiftCardController      # 礼品卡/兑换 redeem
│   │   └── MemberBenefitController # 会员权益
│   ├── notification/v1/controller/ # 通知模块 v1
│   │   └── NotificationController  # 通知列表/标记已读
│   ├── common/                  # 公共能力
│   ├── middleware/              # 中间件
│   │   ├── ApiVersion              # API版本控制(API-Version头)
│   │   ├── Auth                    # JWT认证+用户状态校验
│   │   ├── Cors                    # 跨域处理
│   │   ├── Security                # 安全检测(security-php)
│   │   └── TechnicianAuth          # 技师身份校验
│   └── model/                   # 数据模型(36个)
│       ├── User.php → erik_user
│       ├── TechnicianProfile.php → erik_technician_profile
│       ├── Service.php → erik_service (ES: erik_services)
│       ├── Product.php → erik_product (ES: erik_products)
│       ├── Store.php → erik_store
│       ├── Order.php → erik_order (含退款规则/状态机)
│       ├── Coupon.php → erik_coupon
│       ├── MemberCard.php → erik_member_card
│       ├── Notification.php → erik_notification
│       └── ... (共36个模型文件)
├── config/                     # 配置文件
├── public/                     # 入口
├── runtime/                    # 运行时
├── vendor/                     # 依赖
├── start.php
├── composer.json
└── Dockerfile
```

## apps/ — 用户端前端

### apps/wechat/ — 微信小程序

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

## 中间件执行链

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

## 数据库表清单

所有表使用 `erik_` 前缀，BIGINT 非自增主键（Snowflake生成）。

| 域 | 表名 | 说明 |
|----|------|------|
| 用户 | erik_user | 统一用户表 |
| 用户 | erik_user_address | 收货地址 |
| 技师 | erik_technician_profile | 技师档案 |
| 技师 | erik_technician_schedule | 技师排班 |
| 技师 | erik_technician_service | 技师可服务项目 |
| 技师 | erik_technician_earnings | 技师收益流水 |
| 技师 | erik_technician_withdrawal | 技师提现记录 |
| 技师 | erik_technician_attendance | 技师考勤 |
| 技师 | erik_technician_member_note | 会员档案 |
| 服务 | erik_service_category | 服务分类 |
| 服务 | erik_service | 服务项目 |
| 服务 | erik_product | 产品 |
| 服务 | erik_store | 门店 |
| 订单 | erik_order | 订单主表 |
| 订单 | erik_order_item | 订单明细 |
| 订单 | erik_order_payment | 支付记录 |
| 订单 | erik_order_refund | 退款记录 |
| 订单 | erik_order_review | 服务评价 |
| 订单 | erik_order_verification | 核销记录 |
| 订单 | erik_order_reschedule | 预约改期记录（第17轮） |
| 营销 | erik_coupon | 优惠券定义 |
| 营销 | erik_user_coupon | 用户优惠券 |
| 营销 | erik_user_coupon_transfer | 优惠券转赠记录（第17轮） |
| 营销 | erik_user_points_transfer | 积分转赠记录（第19轮） |
| 营销 | erik_technician_tier_log | 技师等级变更日志（第17轮） |
| 营销 | erik_member_card | 会员卡定义 |
| 营销 | erik_user_member_card | 用户会员卡 |
| 营销 | erik_member_card_usage | 次卡使用记录 |
| 营销 | erik_user_points | 积分流水 |
| 营销 | erik_gift_card | 礼品卡 |
| 营销 | erik_user_referral | 用户推广 |
| 营销 | erik_user_favorite | 用户收藏 |
| 钱包 | erik_user_wallet | 用户钱包余额 |
| 钱包 | erik_wallet_recharge | 钱包充值记录 |
| 钱包 | erik_wallet_txn | 钱包交易流水 |
| 钱包 | erik_wallet_transfer | 用户间转账记录（第19轮） |
| 用户 | erik_user_notify_setting | 消息偏好设置（第19轮） |
| 内容 | erik_banner | 轮播图 |
| 内容 | erik_announcement | 公告 |
| 内容 | erik_platform_agreement | 平台协议 |
| 内容 | erik_faq | 常见问题 |
| 内容 | erik_feedback | 意见反馈 |
| 内容 | erik_moment | 朋友圈动态 |
| 内容 | erik_notification | 消息通知 |
| 财务 | erik_finance_transaction | 收支流水 |
| 财务 | erik_technician_commission_config | 佣金配置 |
| 财务 | erik_withdrawal_account | 提现账号 |
| 财务 | erik_withdrawal_config | 提现限制配置 |
| 系统 | erik_admin_user | 管理用户（已建） |
| 系统 | erik_admin_role | 角色（已建） |
| 系统 | erik_admin_permission | 权限（已建） |
| 系统 | erik_admin_user_role | 用户角色关联（已建） |
| 系统 | erik_admin_role_permission | 角色权限关联（已建） |
| 系统 | erik_system_config | 系统配置（已建） |
| 系统 | erik_operation_log | 操作日志（已建） |
| 用户 | erik_user_growth | 成长值流水（第20轮） |
| 用户 | erik_growth_level | 成长等级档位（第20轮） |
| 订单 | erik_invoice | 电子发票（第20轮） |
| 用户 | erik_ticket | 客服工单（第20轮） |
| 营销 | erik_referral_level2_reward | 二级返佣记录（第20轮） |
| 用户 | erik_invoice_title | 发票抬头库（第21轮） |
| 用户 | erik_browse_history | 浏览足迹（第21轮） |
| 营销 | erik_full_reduction_activity | 满减活动（第22轮） |
| 技师 | erik_technician_attendance | 技师考勤（第22轮） |
| 系统 | erik_push_log | APP推送记录（第22轮） |
| 财务 | erik_profit_sharing | 微信分账记录（第22轮） |

## 外部服务预留

| 服务 | 用途 | 对接点 |
|------|------|--------|
| 微信开放平台 | 微信登录/UnionID | WechatAuthService |
| 微信支付 | 支付/退款/提现 | WechatPayService |
| 短信服务商 | 验证码/通知 | SmsService |
| 地图服务 | LBS定位/导航/距离计算 | MapService |
