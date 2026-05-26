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
│             端口: 8788                         │
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
│   │   │                            # ↓ 待新增（业务模块）
│   │   ├── TechnicianController    #   技师管理
│   │   ├── MemberController        #   会员管理
│   │   ├── StoreController         #   门店管理
│   │   ├── ServiceController       #   服务管理
│   │   ├── ServiceCategoryController
│   │   ├── ProductController       #   产品管理
│   │   ├── MallOrderController     #   商城订单
│   │   ├── SalesStatsController    #   销售统计
│   │   ├── AppointmentOrderController  # 预约订单
│   │   ├── CouponController        #   优惠券管理
│   │   ├── FinanceController       #   财务管理
│   │   ├── WithdrawalController    #   提现审核
│   │   ├── CommissionController    #   佣金设置
│   │   ├── WithdrawalAccountController
│   │   ├── WithdrawalConfigController
│   │   ├── BannerController        #   轮播图
│   │   ├── AnnouncementController  #   公告
│   │   ├── FaqController           #   常见问题
│   │   ├── FeedbackController      #   意见反馈
│   │   ├── MomentController        #   朋友圈动态
│   │   ├── AgreementController     #   协议编辑
│   │   ├── AboutController         #   关于我们
│   │   ├── SystemMessageController #   系统消息
│   │   └── AdminUserController     #   子账号管理
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
│           ├── pages/           #   页面
│           │   ├── dashboard/   #   仪表盘
│           │   ├── login/       #   登录
│           │   ├── user/        #   用户管理
│           │   ├── role/        #   角色权限
│           │   ├── config/      #   系统配置
│           │   ├── log/         #   操作日志
│           │   └── profile/     #   个人中心
│           │                    #   ↓ 待新增
│           │   ├── technician/  #   技师管理
│           │   ├── member/      #   会员管理
│           │   ├── store/       #   门店管理
│           │   ├── service/     #   服务/产品管理
│           │   ├── order/       #   订单管理
│           │   ├── coupon/      #   优惠券
│           │   ├── finance/     #   财务管理
│           │   ├── content/     #   内容管理
│           │   └── settings/    #   系统设置
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
│   ├── api/                    # 公开API（无需认证）
│   │   ├── AuthController          # 登录/注册/忘记密码/游客/身份切换
│   │   ├── CaptchaController       # 短信验证码
│   │   ├── WechatController        # 微信授权/登录/支付回调
│   │   ├── CommonController        # 协议/关于/版本
│   │   └── DocsController          # OpenAPI文档（hg/apidoc）
│   ├── user/                   # 用户模块（需认证）
│   │   ├── ProfileController       # 个人信息/密码/手机/注销
│   │   ├── AddressController       # 地址CRUD
│   │   ├── FavoriteController      # 收藏
│   │   ├── FeedbackController      # 意见反馈
│   │   └── ReferralController      # 推广/推荐
│   ├── technician/             # 技师模块（需技师身份）
│   │   ├── ProfileController       # 技师档案/入驻
│   │   ├── ScheduleController      # 排班
│   │   ├── OrderController         # 订单/核销
│   │   ├── MemberController        # 会员/档案
│   │   ├── EarningsController      # 收益/在途资金
│   │   ├── WithdrawalController    # 提现
│   │   └── AttendanceController    # 考勤
│   ├── service/                # 服务模块
│   │   ├── CategoryController      # 分类
│   │   ├── ItemController          # 服务/产品列表与详情
│   │   ├── SearchController        # 搜索
│   │   └── StoreController         # 门店
│   ├── order/                  # 订单模块（需认证）
│   │   ├── CartController          # 购物车
│   │   ├── OrderController         # 下单/列表/详情/取消
│   │   ├── PaymentController       # 支付/退款
│   │   ├── VerificationController  # 核销
│   │   └── ReviewController        # 评价
│   ├── marketing/              # 营销模块（需认证）
│   │   ├── CouponController        # 优惠券
│   │   ├── MemberCardController    # 会员卡
│   │   ├── PointsController        # 积分
│   │   └── GiftCardController      # 礼品卡
│   ├── content/                # 内容模块
│   │   ├── BannerController        # 轮播图
│   │   ├── AnnouncementController  # 公告
│   │   └── NotificationController  # 消息通知
│   ├── lbs/                    # LBS模块
│   │   └── LocationController      # 定位/城市/附近门店
│   ├── common/                 # 公共能力
│   │   ├── SnowflakeService
│   │   ├── HashidsService
│   │   ├── EncryptionService
│   │   ├── WechatPayService        # 微信支付（预留）
│   │   ├── WechatAuthService       # 微信登录（预留）
│   │   ├── SmsService              # 短信（预留）
│   │   └── MapService              # 地图（预留）
│   ├── middleware/             # 中间件
│   │   ├── Auth                    # JWT认证
│   │   ├── TechnicianAuth          # 技师身份校验
│   │   └── RateLimit               # 限流
│   └── model/                  # 数据模型
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
│   ├── home/                   # 首页
│   │   ├── index               #   首页（轮播图/公告/分类）
│   │   └── search              #   搜索
│   ├── service/                # 服务
│   │   ├── list                #   服务列表
│   │   ├── detail              #   服务详情
│   │   └── product-list        #   产品列表
│   ├── order/                  # 订单
│   │   ├── confirm             #   确认订单
│   │   ├── payment             #   支付
│   │   ├── payment-success     #   支付成功
│   │   ├── list                #   全部订单
│   │   ├── detail              #   订单详情
│   │   ├── review              #   评价
│   │   └── verification        #   核销
│   ├── cart/                   # 购物车
│   │   └── index
│   ├── technician/             # 技师（客户视角）
│   │   ├── list                #   技师列表
│   │   ├── detail              #   技师详情
│   │   └── apply               #   入驻申请
│   ├── tech-work/              # 技师工作台
│   │   ├── index               #   工作台首页
│   │   ├── schedule            #   排班
│   │   ├── order-list          #   订单
│   │   ├── scan-verify         #   扫码核销
│   │   ├── member-list         #   会员列表
│   │   ├── member-detail       #   会员详情
│   │   ├── earnings            #   收益
│   │   ├── withdrawal          #   提现
│   │   ├── transaction-list    #   交易明细
│   │   ├── attendance          #   考勤
│   │   └── training            #   培训
│   ├── user/                   # 个人中心
│   │   ├── index               #   个人信息
│   │   ├── settings            #   设置
│   │   └── switch-role         #   身份切换
│   ├── marketing/              # 营销
│   │   ├── coupon-list         #   优惠券
│   │   ├── member-card         #   会员卡
│   │   ├── points              #   积分
│   │   ├── gift-card           #   礼品卡
│   │   └── referral            #   推广
│   ├── message/                # 消息
│   │   ├── list                #   消息列表
│   │   └── detail              #   消息详情
│   ├── store/                  # 门店
│   │   ├── list                #   门店列表
│   │   └── detail              #   门店详情
│   └── other/                  # 其他
│       ├── about               #   关于我们
│       ├── feedback            #   意见反馈
│       └── official-account    #   关注公众号
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
| 营销 | erik_coupon | 优惠券定义 |
| 营销 | erik_user_coupon | 用户优惠券 |
| 营销 | erik_member_card | 会员卡定义 |
| 营销 | erik_user_member_card | 用户会员卡 |
| 营销 | erik_member_card_usage | 次卡使用记录 |
| 营销 | erik_user_points | 积分流水 |
| 营销 | erik_gift_card | 礼品卡 |
| 营销 | erik_user_referral | 用户推广 |
| 营销 | erik_user_favorite | 用户收藏 |
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

## 外部服务预留

| 服务 | 用途 | 对接点 |
|------|------|--------|
| 微信开放平台 | 微信登录/UnionID | WechatAuthService |
| 微信支付 | 支付/退款/提现 | WechatPayService |
| 短信服务商 | 验证码/通知 | SmsService |
| 地图服务 | LBS定位/导航/距离计算 | MapService |
