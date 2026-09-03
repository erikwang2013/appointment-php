# Sistem Layanan Janji Temu — Struktur Proyek
> **Languages**: [中文](../STRUCTURE.md) · [English](../en/STRUCTURE.md) · [한국어](../ko/STRUCTURE.md) · [Русский](../ru/STRUCTURE.md) · [Deutsch](../de/STRUCTURE.md) · [Français](../fr/STRUCTURE.md) · [Español](../es/STRUCTURE.md) · [Português](../pt/STRUCTURE.md) · [हिन्दी](../hi/STRUCTURE.md) · [العربية](../ar/STRUCTURE.md) · [বাংলা](../bn/STRUCTURE.md) · [日本語](../ja/STRUCTURE.md)

> Terjemahan bahasa Indonesia · Asli: [中文](../../docs/STRUCTURE.md)

## Ringkasan Repositori

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

## Hubungan Proyek

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

## admin/ — Panel Admin

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

## service/ — API Bisnis

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
│   │   ├── Auth                    # JWT认证+用户状态校验
│   │   ├── Cors                    # 跨域处理
│   │   ├── Security                # 安全检测(security-php)
│   │   └── TechnicianAuth          # 技师身份校验
│   └── model/                   # 数据模型(81个)
│       ├── User.php → appointment_user
│       ├── TechnicianProfile.php → appointment_technician_profile
│       ├── Service.php → appointment_service (ES: appointment_services)
│       ├── Product.php → appointment_product (ES: appointment_products)
│       ├── Store.php → appointment_store
│       ├── Order.php → appointment_order (含退款规则/状态机)
│       ├── Coupon.php → appointment_coupon
│       ├── MemberCard.php → appointment_member_card
│       ├── Notification.php → appointment_notification
│       └── ... (共81个模型文件；admin 另有 6 个特有模型，合计 87)
├── config/                     # 配置文件
├── public/                     # 入口
├── runtime/                    # 运行时
├── vendor/                     # 依赖
├── start.php
├── composer.json
└── Dockerfile
```

## apps/ — Frontend Sisi Pengguna

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

## Rantai Eksekusi Middleware

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

## Daftar Tabel Basis Data

Semua tabel memakai prefiks `appointment_`, primary key BIGINT non-auto-increment (dihasilkan Snowflake).

| Domain | Nama tabel | Keterangan |
|----|------|------|
| Pengguna | appointment_user | tabel pengguna terpadu |
| Pengguna | appointment_user_address | alamat pengiriman |
| Teknisi | appointment_technician_profile | arsip teknisi |
| Teknisi | appointment_technician_schedule | jadwal teknisi |
| Teknisi | appointment_technician_service | item layanan yang bisa dilayani teknisi |
| Teknisi | appointment_technician_earnings | transaksi pendapatan teknisi |
| Teknisi | appointment_technician_withdrawal | catatan penarikan dana teknisi |
| Teknisi | appointment_technician_attendance | kehadiran teknisi |
| Teknisi | appointment_technician_member_note | arsip member |
| Layanan | appointment_service_category | kategori layanan |
| Layanan | appointment_service | item layanan |
| Layanan | appointment_product | produk |
| Layanan | appointment_store | toko |
| Pesanan | appointment_order | tabel utama pesanan (kolom relasi seckill_id, ronde ke-24) |
| Pesanan | appointment_order_item | detail pesanan |
| Pesanan | appointment_order_payment | catatan pembayaran |
| Pesanan | appointment_order_refund | catatan refund |
| Pesanan | appointment_order_review | ulasan layanan |
| Pesanan | appointment_order_verification | catatan verifikasi |
| Pesanan | appointment_order_reschedule | catatan penggantian jadwal (ronde ke-17) |
| Pemasaran | appointment_coupon | definisi kupon |
| Pemasaran | appointment_user_coupon | kupon pengguna |
| Pemasaran | appointment_user_coupon_transfer | catatan transfer kupon (ronde ke-17) |
| Pemasaran | appointment_user_points_transfer | catatan transfer poin (ronde ke-19) |
| Pemasaran | appointment_technician_tier_log | log perubahan level teknisi (ronde ke-17) |
| Pemasaran | appointment_member_card | definisi kartu member |
| Pemasaran | appointment_user_member_card | kartu member pengguna |
| Pemasaran | appointment_member_card_usage | catatan penggunaan kartu kunjungan |
| Pemasaran | appointment_user_points | transaksi poin |
| Pemasaran | appointment_gift_card | kartu hadiah |
| Pemasaran | appointment_user_referral | promosi pengguna |
| Pemasaran | appointment_user_favorite | favorit pengguna |
| Dompet | appointment_user_wallet | saldo dompet pengguna |
| Dompet | appointment_wallet_recharge | catatan top-up dompet |
| Dompet | appointment_wallet_txn | transaksi dompet |
| Dompet | appointment_wallet_transfer | catatan transfer antar pengguna (ronde ke-19) |
| Pengguna | appointment_user_notify_setting | pengaturan preferensi pesan (ronde ke-19) |
| Konten | appointment_banner | banner |
| Konten | appointment_announcement | pengumuman |
| Konten | appointment_platform_agreement | perjanjian platform |
| Konten | appointment_faq | pertanyaan umum |
| Konten | appointment_feedback | masukan |
| Konten | appointment_moment | postingan momen |
| Konten | appointment_notification | notifikasi pesan |
| Keuangan | appointment_finance_transaction | transaksi pemasukan pengeluaran |
| Keuangan | appointment_technician_commission_config | konfigurasi komisi |
| Keuangan | appointment_withdrawal_account | akun penarikan |
| Keuangan | appointment_withdrawal_config | konfigurasi batas penarikan |
| Sistem | appointment_admin_user | pengguna admin (sudah dibuat) |
| Sistem | appointment_admin_role | peran (sudah dibuat) |
| Sistem | appointment_admin_permission | izin (sudah dibuat) |
| Sistem | appointment_admin_user_role | relasi pengguna peran (sudah dibuat) |
| Sistem | appointment_admin_role_permission | relasi peran izin (sudah dibuat) |
| Sistem | appointment_system_config | konfigurasi sistem (sudah dibuat) |
| Sistem | appointment_operation_log | log operasi (sudah dibuat) |
| Pengguna | appointment_user_growth | transaksi nilai pertumbuhan (ronde ke-20) |
| Pengguna | appointment_growth_level | tingkatan level pertumbuhan (ronde ke-20) |
| Pesanan | appointment_invoice | faktur elektronik (ronde ke-20) |
| Pengguna | appointment_ticket | tiket layanan pelanggan (ronde ke-20) |
| Pemasaran | appointment_referral_level2_reward | catatan komisi referral level dua (ronde ke-20) |
| Pengguna | appointment_invoice_title | pustaka judul faktur (ronde ke-21) |
| Pengguna | appointment_browse_history | jejak penjelajahan (ronde ke-21) |
| Pemasaran | appointment_full_reduction_activity | aktivitas potongan (ronde ke-22) |
| Teknisi | appointment_technician_attendance | kehadiran teknisi (ronde ke-22) |
| Sistem | appointment_push_log | catatan push APP (ronde ke-22) |
| Keuangan | appointment_profit_sharing | catatan bagi hasil WeChat (ronde ke-22) |
| Pesanan | appointment_order_status_log | garis waktu status pesanan (ronde ke-23) |
| Pengguna | appointment_user_health_profile | arsip kesehatan pengguna (ronde ke-23) |
| Pemasaran | appointment_lucky_wheel | definisi hadiah roda (ronde ke-23) |
| Pemasaran | appointment_wheel_record | catatan undian roda (ronde ke-23) |
| Pemasaran | appointment_seckill_activity | aktivitas flash sale (ronde ke-24) |
| Sistem | appointment_app_version | versi APP (ronde ke-24) |

### Daftar Pelengkap (bagian 95 tabel docs/install.sql yang belum tercantum di atas, daftar resmi lengkap mengacu install.sql)

| Domain | Nama tabel | Keterangan |
|----|------|------|
| Pemasaran | appointment_card_transfer | transfer kartu kunjungan |
| Pengguna | appointment_check_in | check-in |
| Konten | appointment_community_post | postingan komunitas |
| Konten | appointment_community_comment | komentar komunitas |
| Teknisi | appointment_exam | ujian |
| Teknisi | appointment_exam_question | soal ujian |
| Teknisi | appointment_exam_attempt | lembar jawaban ujian |
| Sistem | appointment_operation_log_detail | detail log operasi |
| Pesanan | appointment_order_aftersale | purna jual pesanan |
| Pemasaran | appointment_points_exchange_goods | barang tukar poin |
| Pemasaran | appointment_promotion | aktivitas belanja bersama |
| Pemasaran | appointment_promotion_participant | peserta belanja bersama |
| Pesanan | appointment_queue_number | nomor antrean |
| Layanan | appointment_service_package | paket layanan |
| Teknisi | appointment_service_record | catatan layanan |
| Konten | appointment_share | catatan berbagi |
| Pesanan | appointment_signature | tanda tangan |
| Teknisi | appointment_technician_tier_config | konfigurasi level teknisi |
| Teknisi | appointment_training_course | kursus pelatihan |
| Teknisi | appointment_training_progress | progres pelatihan |
| Pengguna | appointment_user_device | perangkat pengguna |
| Pemasaran | appointment_user_points_exchange | catatan tukar poin |
| Konten | appointment_video_post | postingan video |
| Pesanan | appointment_waitlist | daftar tunggu |

## Reservasi Layanan Eksternal

| Layanan | Kegunaan | Titik integrasi |
|------|------|--------|
| WeChat Open Platform | login WeChat/UnionID | WechatAuthService |
| WeChat Pay | pembayaran/refund/penarikan | WechatPayService |
| Penyedia SMS | kode verifikasi/notifikasi | SmsService |
| Layanan peta | lokasi LBS/navigasi/perhitungan jarak | MapService |
