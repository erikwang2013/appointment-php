# 预约服务系统设计规范
> **多语言**：[English](en/specs/2026-05-26-appointment-system-design.md) · [한국어](ko/specs/2026-05-26-appointment-system-design.md) · [Русский](ru/specs/2026-05-26-appointment-system-design.md) · [Deutsch](de/specs/2026-05-26-appointment-system-design.md) · [Français](fr/specs/2026-05-26-appointment-system-design.md) · [Español](es/specs/2026-05-26-appointment-system-design.md) · [Português](pt/specs/2026-05-26-appointment-system-design.md) · [हिन्दी](hi/specs/2026-05-26-appointment-system-design.md) · [العربية](ar/specs/2026-05-26-appointment-system-design.md) · [বাংলা](bn/specs/2026-05-26-appointment-system-design.md) · [Bahasa Indonesia](id/specs/2026-05-26-appointment-system-design.md) · [日本語](ja/specs/2026-05-26-appointment-system-design.md)

## 概述

三端预约服务系统：用户端（微信小程序 + Flutter APP）+ 技师工作台（同APP内身份切换）+ 管理后台（PC Web）。

## 架构决策

| 决策 | 方案 |
|------|------|
| 后端架构 | `admin/`（管理后台API）+ `service/`（业务API），双服务共享 MySQL/Redis |
| 用户端小程序 | 原生微信小程序 `apps/wechat/` |
| 用户端APP | Flutter `apps/flutter/`（iOS + Android） |
| 用户身份 | 统一账号，客户/技师身份可切换 |
| 小程序与APP关系 | 功能完全相同，仅平台差异 |
| 管理后台前端 | 现有 Flutter Web (`admin/apps/flutter/`) 扩展 |
| 管理后台后端 | 现有 webman v2 (`admin/`) 扩展业务模块 |
| 第三方服务 | 微信登录/支付/短信/地图 — 预留对接方案 |

## 系统架构图

```
┌──────────────────────────────────────────────────────────┐
│                      用户终端层                            │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ 微信小程序        │  │ Flutter APP       │              │
│  │ apps/wechat/      │  │ apps/flutter/     │              │
│  │ (原生WXML/WXSS)   │  │ (iOS + Android)   │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │         功能完全相同  │                        │
│           └──────────┬──────────┘                        │
│                      │ 客户身份 / 技师身份切换              │
├──────────────────────┼──────────────────────────────────┤
│              业务API网关                                   │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ service/ API      │  │ admin/ API        │              │
│  │ (webman v2)       │  │ (webman v2)       │              │
│  │ 用户/订单/支付/    │  │ 管理后台接口       │              │
│  │ 技师/门店/营销...   │  │ (已建 + 扩展)     │              │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │                      │                        │
│           └──────────┬───────────┘                        │
│                      │                                    │
├──────────────────────┼──────────────────────────────────┤
│                  数据层                                    │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────────────┐    │
│  │ MySQL  │ │ Redis  │ │  ES    │ │ 第三方服务      │    │
│  │ 8.0    │ │ 缓存/   │ │ 搜索   │ │ 微信/短信/地图  │    │
│  │        │ │ 限流/   │ │        │ │ (预留对接)     │    │
│  │        │ │ Session │ │        │ │                │    │
│  └────────┘ └────────┘ └────────┘ └────────────────┘    │
└──────────────────────────────────────────────────────────┘
```

## 数据库核心表

所有表使用 `erik_` 前缀，BIGINT 非自增主键（Snowflake生成）。敏感字段使用 encryptable trait 加解密。

### 用户与身份域

| 表名 | 说明 | 核心字段 |
|------|------|----------|
| `erik_user` | 统一用户表 | phone, password, wx_openid, wx_unionid, avatar, nickname, user_type(customer/technician), status。technician用户同时拥有客户功能，可自由切换当前活跃身份 |
| `erik_user_address` | 用户地址 | user_id, contact_name, contact_phone, province, city, district, detail, is_default |
| `erik_technician_profile` | 技师档案 | user_id, real_name, gender, id_card, id_card_front, id_card_back, avatar, rating, order_count, status(pending/approved/rejected), intro |
| `erik_technician_schedule` | 技师排班 | technician_id, date, time_slots(JSON), status |
| `erik_technician_service` | 技师可服务项目 | technician_id, service_id |
| `erik_technician_earnings` | 技师收益流水 | technician_id, order_id, type(commission/bonus/penalty), amount, status |
| `erik_technician_withdrawal` | 技师提现记录 | technician_id, amount, actual_amount, commission_fee, account_info, status, reviewed_at |
| `erik_technician_attendance` | 技师考勤 | technician_id, date, check_in_at, check_out_at, clean_photo |
| `erik_technician_member_note` | 会员档案 | technician_id, user_id, content, written_at |

### 服务与产品域

| 表名 | 说明 | 核心字段 |
|------|------|----------|
| `erik_service_category` | 服务分类 | name, icon, parent_id, sort, status |
| `erik_service` | 服务项目 | category_id, name, description, cover_image, images(JSON), price, duration, sales_volume, specs(JSON), status |
| `erik_product` | 产品 | category_id, name, cover_image, price, stock, sales_volume, type, status |
| `erik_store` | 门店 | name, address, lat, lng, phone, business_hours(JSON), images, status |

### 订单域

| 表名 | 说明 | 核心字段 |
|------|------|----------|
| `erik_order` | 订单主表 | order_no, user_id, technician_id, store_id, total_amount, discount_amount, paid_amount, status, service_time, cancel_reason, remark |
| `erik_order_item` | 订单明细 | order_id, service_id, product_id, type, name, price, quantity, spec_info |
| `erik_order_payment` | 支付记录 | order_id, pay_type(wechat), transaction_id, amount, status, paid_at |
| `erik_order_refund` | 退款记录 | order_id, payment_id, refund_no, amount, ratio, reason, status |
| `erik_order_review` | 服务评价 | order_id, user_id, technician_id, rating, content, images |
| `erik_order_verification` | 核销记录 | order_id, code, verified_at, verified_by, location |

### 营销域

| 表名 | 说明 | 核心字段 |
|------|------|----------|
| `erik_coupon` | 优惠券定义 | name, type, amount, min_amount, total_qty, remain_qty, start_at, end_at, status |
| `erik_user_coupon` | 用户优惠券 | user_id, coupon_id, status(available/used/expired), used_at |
| `erik_member_card` | 会员卡定义 | name, type(month/vip/times), price, duration_days, total_times, services(JSON) |
| `erik_user_member_card` | 用户会员卡 | user_id, card_id, start_at, end_at, total_times, used_times, status |
| `erik_member_card_usage` | 次卡使用记录 | user_card_id, order_id, service_id, used_at |
| `erik_user_points` | 积分流水 | user_id, type(earn/use), points, source, order_id |
| `erik_gift_card` | 礼品卡 | code, type, amount_or_gift, status, used_by, used_at |
| `erik_user_referral` | 用户推广 | referrer_id, referred_user_id, reward_type, reward_amount, registered_at, first_order_at |

### 内容与通知域

| 表名 | 说明 | 核心字段 |
|------|------|----------|
| `erik_banner` | 轮播图 | position, image, jump_type(url/detail/none), jump_value, sort, status |
| `erik_announcement` | 公告 | content, status, published_at |
| `erik_platform_agreement` | 平台协议 | type(user_agreement/privacy_policy/service_agreement), title, content, version |
| `erik_faq` | 常见问题 | title, content, sort |
| `erik_feedback` | 意见反馈 | user_id, content, images, handler_reply, status(pending/handled) |
| `erik_moment` | 朋友圈动态 | content, images, published_at |
| `erik_notification` | 消息通知 | user_id, type(order/system), title, content, is_read, created_at |

### 财务域（admin侧）

| 表名 | 说明 | 核心字段 |
|------|------|----------|
| `erik_finance_transaction` | 收支流水 | user_id, order_id, type, direction(income/expense), amount, actual_amount, commission, status |
| `erik_technician_commission_config` | 佣金配置 | technician_id, commission_rate, settlement_cycle |
| `erik_withdrawal_account` | 提现账号 | user_id, type(wechat), account_name, account_no |
| `erik_withdrawal_config` | 提现限制配置 | min_amount, reserve_amount, round_to_hundred |

## Service API 模块

### 公开API（无需认证）
- **AuthController** — 登录/注册/忘记密码/游客模式/身份切换
- **CaptchaController** — 短信验证码
- **WechatController** — 微信授权/登录/支付回调
- **CommonController** — 协议文本/关于我们/版本信息

### 用户模块 `user/`（需认证）
- **ProfileController** — 个人信息/修改密码/换绑手机/注销
- **AddressController** — 收货地址CRUD
- **FavoriteController** — 收藏
- **FeedbackController** — 意见反馈
- **ReferralController** — 推广/推荐用户列表

### 技师模块 `technician/`（需技师身份 + TechnicianAuth中间件）
- **ProfileController** — 技师档案/入驻申请
- **ScheduleController** — 排班设置
- **OrderController** — 已约未核销/已完成/扫码核销
- **MemberController** — 我的会员/会员档案
- **EarningsController** — 收益/在途资金
- **WithdrawalController** — 提现
- **AttendanceController** — 考勤/卫生照片

### 服务模块 `service/`
- **CategoryController** — 服务分类
- **ItemController** — 服务/产品列表与详情
- **SearchController** — 搜索
- **StoreController** — 门店列表/详情

### 订单模块 `order/`（需认证）
- **CartController** — 购物车
- **OrderController** — 下单/订单列表/详情/取消
- **PaymentController** — 支付/退款
- **VerificationController** — 二维码核销
- **ReviewController** — 评价

### 营销模块 `marketing/`（需认证）
- **CouponController** — 优惠券列表/领取/使用
- **MemberCardController** — 会员卡/次卡
- **PointsController** — 积分
- **GiftCardController** — 礼品卡

### 内容模块 `content/`
- **BannerController** — 轮播图
- **AnnouncementController** — 公告
- **NotificationController** — 消息通知

### LBS模块
- **LocationController** — 定位/城市切换/附近门店

### 公共能力 `common/`
- SnowflakeService — ID生成
- HashidsService — ID加解密
- EncryptionService — 敏感数据加解密
- WechatPayService — 微信支付（预留）
- WechatAuthService — 微信登录（预留）
- SmsService — 短信服务（预留）
- MapService — 地图服务（预留）

### 中间件
- Auth — JWT认证（与admin共享 erikwang2013/jwt-webman 包）
- TechnicianAuth — 技师身份校验
- RateLimit — 限流（与admin共享）

## Admin 管理后台扩展

在现有框架基础上新增控制器：

### 技师管理
- **TechnicianController** — 技师列表/搜索/导出/审核/排班管理/技术服务项设置/课程学习进度

### 用户管理扩展
- **MemberController** — 会员列表/等级设置/消费统计

### 门店管理
- **StoreController** — 门店CRUD/启禁用

### 服务管理
- **ServiceController** — 服务列表/CRUD/卡项设计
- **ServiceCategoryController** — 分类管理
- **ProductController** — 产品列表/CRUD

### 商城管理
- **MallOrderController** — 商城订单/发货/售后/评价
- **SalesStatsController** — 销售统计

### 订单管理
- **AppointmentOrderController** — 待使用订单/取消/确认完成

### 优惠券活动
- **CouponController** — 优惠券CRUD/发放

### 财务管理
- **FinanceController** — 订单分账/收支流水
- **WithdrawalController** — 技师提现审核/完成
- **CommissionController** — 佣金设置/奖罚/余额查询
- **WithdrawalAccountController** — 提现账号管理
- **WithdrawalConfigController** — 提现限制配置

### 内容管理
- **BannerController** — 轮播图CRUD
- **AnnouncementController** — 公告CRUD
- **FaqController** — FAQ CRUD
- **FeedbackController** — 意见反馈处理
- **MomentController** — 朋友圈动态审核
- **AgreementController** — 协议编辑（用户协议/隐私协议/服务协议）
- **AboutController** — 关于我们设置

### 设置
- **SystemMessageController** — 系统消息设置
- **AdminUserController** — 子账号管理（基于现有RBAC）

### Dashboard 扩展
- 实时统计卡片：用户人数/订单总数/技师人数/服务订单数
- 折线图：订单量/金额/日新增用户/活跃度
- 快速导航：待处理模块按钮
- 站内消息：新订单通知/退款通知

## 用户端页面结构

微信小程序和 Flutter APP 功能完全相同。

### auth/ — 认证
- login — 登录（手机号/验证码/微信/游客入口）
- register — 注册（手机号+验证码+密码+推荐码）
- forget-password — 忘记密码
- agreement — 协议查看

### home/ — 首页
- index — 首页（轮播图+公告+服务分类+推荐）
- search — 搜索页

### service/ — 服务
- list — 服务列表（按分类筛选）
- detail — 服务详情（基础信息+评价+立即预约）
- product-list — 产品列表

### order/ — 订单
- confirm — 确认订单（门店/技师/时间/优惠券/备注/协议）
- payment — 支付页
- payment-success — 支付成功
- list — 全部订单（按状态Tab筛选）
- detail — 订单详情
- review — 服务评价
- verification — 二维码核销

### cart/ — 购物车
- index — 购物车列表

### technician/ — 技师（客户视角）
- list — 技师列表（距离近到远排序）
- detail — 技师详情（评价/可服务项目/立即预约）
- apply — 技师入驻申请

### tech-work/ — 技师工作台（技师身份）
- index — 工作台首页（今日订单/收入概况）
- schedule — 排班设置
- order-list — 我的订单（已约未核销/已完成）
- scan-verify — 扫码核销
- member-list — 我的会员
- member-detail — 会员详情/档案编辑
- earnings — 我的收益
- withdrawal — 提现
- transaction-list — 交易明细
- attendance — 考勤/卫生照片上传
- training — 专业培训

### user/ — 个人中心
- index — 个人信息（头像/昵称/会员卡/收藏/优惠券入口）
- settings — 设置（修改密码/换绑手机/协议/更新/注销/退出）
- switch-role — 身份切换（客户 ↔ 技师）

### marketing/ — 营销
- coupon-list — 优惠券列表
- member-card — 我的会员卡
- points — 我的积分
- gift-card — 我的礼品卡
- referral — 推广（说明+二维码海报+推荐用户列表）

### 其他页面
- message/ — 消息列表/详情
- store/list, store/detail — 门店列表（LBS排序）/详情（导航）
- other/about — 关于我们
- other/feedback — 意见反馈
- other/official-account — 关注公众号

### 公共组件
- navbar, tabbar, service-card, technician-card
- coupon-popup, lbs-selector, empty-state, loading

### 身份切换逻辑
- 客户身份底部导航：首页 / 服务 / 购物车 / 订单 / 我的
- 技师身份底部导航：工作台 / 订单 / 会员 / 收益 / 我的
- 「我的」页面提供身份切换入口
- 尚未成为技师的用户切换至技师身份时引导至入驻申请页

## 购买流程说明

系统有两套不同的购买流程：

### 服务预约流程（直接下单，无购物车）
- 服务项目详情页 → 确认订单（选门店/技师/时间）→ 支付 → 核销
- 技师资源独占：进入确认订单页面时锁定技师 3 分钟
- 用于推拿、美容等线下服务项目

### 产品购买流程（购物车模式）
- 产品列表 → 加入购物车 → 购物车确认 → 提交订单 → 支付 → 发货/收货
- 支持修改数量、删除商品
- 用于实物商品或卡券销售

## 关键业务规则

### 技师锁定机制
- 同一时间不能多人同时约一位技师
- 用户进入确认订单页面时，通过 Redis SETNX 锁定技师 3 分钟
- 退出预约页面或超时自动释放锁

### 退款规则
| 条件 | 退款比例 |
|------|----------|
| 下单15分钟内 或 距离开始 >6小时 | 100% |
| 距离开始 ≤6小时 | 90% |
| 已开始但未确认服务 | 80% |
| 服务确认开始后 | 0%（不予退款）|

### 折扣规则
- 低峰时段（10-12点/17-18点/21:00后）9折
- 提前30分钟预约 95折（不可与优惠券叠加）

### 技师提现
- 每月20号可提现，T+1工作日到账
- 支持提现至微信零钱
- 已核销未结算订单，3天内系统自动确认
- 24小时内须完成会员档案，否则无提成

### 回头客奖励
- 30天内同一技师第二次消费 → 记录奖金
- 服务后上传卫生照片

### 积分规则
- 1:100 兑换礼品卡（后台可配置）
- 推荐用户注册成功并下单后获得指定积分（后台设置）
