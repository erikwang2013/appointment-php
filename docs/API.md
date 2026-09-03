# API 说明文档
> **多语言**：[English](en/API.md) · [한국어](ko/API.md) · [Русский](ru/API.md) · [Deutsch](de/API.md) · [Français](fr/API.md) · [Español](es/API.md) · [Português](pt/API.md) · [हिन्दी](hi/API.md) · [العربية](ar/API.md) · [বাংলা](bn/API.md) · [Bahasa Indonesia](id/API.md) · [日本語](ja/API.md)

## 概述

- **业务API** (service/): `http://localhost:8787` — 为小程序/APP提供业务接口
- **管理后台API** (admin/): `http://localhost:8787` — 为管理后台Flutter Web提供接口
- **认证方式**: Bearer Token (JWT), 请求头 `Authorization: Bearer <token>`
- **版本控制**: 版本固化在 URL 路径前缀 `/api/v1` 中（如 `POST /api/v1/auth/login`），URL 不带版本前缀即 404
- **ID编码**: 所有请求/响应中的ID字段使用hashids编码，对外隐藏真实数据库ID
- **OpenAPI文档**: 使用 `hg/apidoc` 生成，管理端和客户端分开

| 端 | OpenAPI文档地址 | 说明 |
|------|------|------|
| 管理端 | `GET http://localhost:8787/api/docs` | 管理后台API完整规范（OpenAPI 3.0 JSON） |
| 客户端 | `GET http://localhost:8787/api/docs` | 业务API完整规范（OpenAPI 3.0 JSON） |

可通过 Swagger UI 等工具导入上述地址查看交互式文档。

- **通用响应格式**:

```json
{
  "code": 0,
  "message": "操作成功",
  "data": {}
}
```

分页响应:
```json
{
  "code": 0,
  "message": "success",
  "data": [],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

---

## 一、业务API (service/ :8787)

### 1. 公开接口（无需认证）

#### 1.1 验证码

**`POST /api/v1/captcha/send`** — 发送短信验证码

请求:
```json
{
  "phone": "13800138000"
}
```
响应: `{"code":0,"message":"验证码已发送","data":null}`

限制: 每60秒仅可发送1次，验证码5分钟有效。

---

#### 1.2 认证

**`POST /api/v1/auth/register`** — 手机号注册

请求:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "abc123",
  "confirm_password": "abc123",
  "referral_code": "A1B2C3D4"
}
```
响应:
```json
{
  "code": 0,
  "message": "注册成功",
  "data": {
    "token": "eyJhbGciOi...",
    "user": {
      "id": "aB3xK9mQ",
      "phone": "138****8000",
      "nickname": "用户138****8000",
      "user_type": "customer",
      "active_role": "customer",
      "referral_code": "E5F6G7H8"
    }
  }
}
```

---

**`POST /api/v1/auth/login`** — 密码登录

请求:
```json
{
  "phone": "13800138000",
  "password": "abc123"
}
```
响应: 同注册响应，包含token和user信息。

---

**`POST /api/v1/auth/login-by-code`** — 验证码登录

请求:
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```
响应: 同登录。未注册用户自动创建账号。

---

**`POST /api/v1/auth/forget-password`** — 忘记密码

请求:
```json
{
  "phone": "13800138000",
  "code": "123456",
  "password": "newpass123",
  "confirm_password": "newpass123"
}
```

---

**`POST /api/v1/auth/refresh`** — 刷新Token

请求头: `Authorization: Bearer <旧token>`
响应: `{"code":0,"data":{"token":"eyJhbGciOi..."}}`

---

#### 1.3 微信

**`POST /api/v1/wechat/mini-login`** — 小程序登录

请求: `{"code":"微信登录code"}`
说明: 首次登录需后续调用 `/api/v1/wechat/phone` 绑定手机号。

---

**`POST /api/v1/wechat/phone`** — 绑定手机号

请求: `{"code":"微信手机号组件code"}`

---

**`POST /api/v1/wechat/oa-login`** — 公众号登录

请求: `{"code":"公众号授权code"}`

---

#### 1.4 公共服务

**`GET /api/v1/common/config`** — 公共配置

响应: 包含协议文本(用户协议/隐私协议/服务协议)、关于我们信息、版本号。

---

**`GET /api/v1/common/area`** — 城市区域列表

---

#### 1.5 服务查询

**`GET /api/v1/service/categories`** — 分类列表

参数: `?parent_id=0`

---

**`GET /api/v1/service/items`** — 服务项目列表

参数: `?category_id=&page=1&per_page=10&sort=sales`

---

**`GET /api/v1/service/detail/{id}`** — 服务详情

响应包含: 图片/名称/价格/规格/时长/销量/评价列表。

---

**`GET /api/v1/service/products`** — 产品列表

**`GET /api/v1/service/stores`** — 门店列表

参数: `?lat=&lng=&city=`

---

#### 1.6 技师查询

**`GET /api/v1/technician/list`** — 技师列表

参数: `?lat=&lng=&service_id=&page=1`
按距离由近到远排序，返回: 头像/名字/评分/订单数/收藏数/距离/最早可约时间/是否可服务。

---

**`GET /api/v1/technician/detail/{id}`** — 技师详情

响应包含: 图片/名字/介绍/评分/距离/可服务项目列表/评价。

---

**`GET /api/v1/technician/schedule/{id}`** — 技师排班

参数: `?date=2026-05-26`
返回该日期可预约时间段及可用状态。

---

#### 1.7 内容

**`GET /api/v1/content/banners`** — 轮播图

参数: `?position=home`

**`GET /api/v1/content/articles`** — 公告/文章列表

参数: `?type=announcement&page=1`

**`GET /api/v1/content/article/{id}`** — 文章详情

---

#### 1.8 LBS

**`GET /api/v1/lbs/nearby-stores`** — 附近门店

参数: `?lat=&lng=&radius=5000`

**`GET /api/v1/lbs/geocode`** — 逆地理编码

参数: `?lat=&lng=`

---

### 2. 用户接口（需JWT认证）

所有接口请求头携带 `Authorization: Bearer <token>`

#### 2.1 个人资料

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/user/profile` | 获取个人信息 |
| PUT | `/api/v1/user/profile` | 更新昵称/头像/性别 |
| POST | `/api/v1/user/change-password` | 修改密码 (old_password/new_password/confirm_password) |
| POST | `/api/v1/user/change-phone` | 换绑手机 (old_code/new_phone/new_code) |
| POST | `/api/v1/user/cancel-account` | 注销账号 (需验证密码) |
| POST | `/api/v1/user/logout` | 退出登录 (token加入黑名单) |
| POST | `/api/v1/user/switch-role` | 切换身份 (role: customer/technician) |

切换为technician需已有approved状态的技师档案。

#### 2.2 地址管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/user/addresses` | 地址列表 |
| POST | `/api/v1/user/addresses` | 新增地址 (contact_name/contact_phone/province/city/district/detail/lat/lng/is_default) |
| GET | `/api/v1/user/addresses/{id}` | 地址详情 |
| PUT | `/api/v1/user/addresses/{id}` | 更新地址 |
| DELETE | `/api/v1/user/addresses/{id}` | 删除地址 |

设为默认时自动取消其他默认地址。

#### 2.3 收藏

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/user/favorites` | 收藏列表 (?type=service/technician) |
| POST | `/api/v1/user/favorites` | 添加收藏 (target_type/target_id) |
| DELETE | `/api/v1/user/favorites/{id}` | 取消收藏 |

#### 2.4 意见反馈

`POST /api/v1/user/feedback` — 提交反馈 (content + images数组)

#### 2.5 推广推荐

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/user/referral` | 推广信息 (推荐码/推荐人数/首单人数/获得积分) |
| GET | `/api/v1/user/referral/qrcode` | 推广二维码 (推荐码+邀请链接) |
| GET | `/api/v1/user/referral/referred-users` | 已推荐用户列表 |
| GET | `/api/v1/user/referral/earnings` | 分销返佣明细 (分页: 被推荐人昵称/头像/订单号/金额/发放时间) |

**分销返佣**: 被推荐人首单 completed 后发放，金额 = paid_amount × reward_rate（appointment_system_config referral.reward_rate，默认 0.05，非法值回落常量）。行锁 + rewarded_at 判空 + 首单复查三重幂等；入账 WalletTxn type=referral_reward。

#### 2.6 积分转赠（第19轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/v1/user/points/transfer` | 积分转赠 (to_user_id hashid/points) |
| GET | `/api/v1/user/points/transfers` | 转赠记录 (?direction=sent/received&page=1) |

**积分转赠**: 接收人 hashid 解码+存在性 404、转自己 422、点数 1-10000 422、余额 SUM 聚合不足 422、单日累计 10000 限额 422。并发防护：Redis NX 锁 points_transfer:{user} 30s → 事务内双方最后一条流水 lockForUpdate（user_id 升序防互转死锁）→ 锁内复验余额/限额/接收人。流水规范：发送方 type=consume/source=points_transfer 负值（balance=上条快照-本次），接收方 type=earn/source=points_transfer 正值含 expires_at（PointsExpiryTimer 可正常过期）；commit 后站内通知接收方 type='points_received'（失败仅 warn）。

#### 2.7 消息偏好设置（第19轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/user/notify-settings` | 查询通知开关（5 类全量） |
| PUT | `/api/v1/user/notify-settings` | 批量更新开关 (types: {service_reminder: 0/1, ...}) |

**通知开关**: appointment_user_notify_setting 表（user_id+type 复合唯一键，缺省行=默认开）。5 类：service_reminder 服务提醒 / card_expiry 到期提醒（卡+券统一伞形）/ points_expiry 积分过期 / marketing 营销（预留）/ system 系统（不可关，PUT 强制为 1）。门控：notifySettingEnabled 挂接 ServiceReminderTimer/ExpiryReminderTimer/PointsExpiryTimer 3 个定时器进程 + 订阅事件场景映射（PAY/REFUND/VERIFIED/RESCHEDULE→system 恒发，REMINDER→service_reminder，EXPIRY→card_expiry）；类型关闭时站内通知与订阅消息一并跳过。

---

### 3. 技师接口（需JWT + 技师身份）

#### 3.1 技师档案

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/technician/profile` | 获取技师档案 |
| PUT | `/api/v1/technician/profile` | 更新档案 (avatar/intro/real_name/gender/id_card/id_card_front/id_card_back) |

首次完整填写视为入驻申请，status=pending等待审核。

#### 3.2 排班

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/technician/schedule` | 排班查询 (?start_date=&end_date=) |
| PUT | `/api/v1/technician/schedule` | 设置排班 (date/time_slots/status)，时间段重叠 422「与已有排班时间冲突」 |
| POST | `/api/v1/technician/schedule/batch` | 批量排班（第23轮）：日期段 ≤7 天 + weekdays 过滤，已有排班的天跳过，响应 created/skipped |

#### 3.3 技师订单

`GET /api/v1/technician/orders` — 订单列表 (?status=&page=1)

#### 3.4 收益

`GET /api/v1/technician/earnings` — 收益概况 (today_income/pending_settlement/balance + 流水列表)

#### 3.5 提现

`POST /api/v1/technician/withdraw` — 申请提现 (amount)
规则: 每月20号可提，T+1到账，最低金额/整百限制由后台配置。

**在途预留（2026-08-26）**: 申请时余额即扣除在途（pending/approved）预留；审批转账前复核 settled − withdrawn − 在途 ≥ 提现额；并发审批不会双打款。

#### 3.6 评价回复（第18轮）

`POST /api/v1/technician/review/reply/{order_id}` — 技师回复评价 (reply)。评价不存在/非本人统一 404（不泄露存在性）；已有回复 422（幂等拒绝不覆盖）；空回复 422。回复成功站内通知用户（type='review_reply'）。

#### 3.6 工作台

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/technician/work/today` | 今日任务列表 |
| GET | `/api/v1/technician/work/records` | 完成记录分页 |
| POST | `/api/v1/technician/work/{id}/start` | 开始服务 |
| POST | `/api/v1/technician/work/{id}/complete` | 完成服务 |

**今日任务**: status ∈ [confirmed, serving]，service_time 为今日或空，返回 service_name/price/nickname/avatar。

**完成记录**: status ∈ [serving, completed]，按 service_end_at 倒序，分页响应含 meta。

**开始/完成服务**: 行锁+状态机校验，幂等操作。开始服务写入 service_start_at；完成服务写入 service_end_at 并发送站内通知。错误码: 非本人 403、状态错误 422、无效 hashid 422。

---

### 4. 订单接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/v1/order` | 创建订单 (order_type/items/store_id/technician_id/service_time/coupon_id/user_coupon_id/promotion_id/remark) |
| GET | `/api/v1/order/list` | 订单列表 (?status=&page=1) |
| GET | `/api/v1/order/detail/{id}` | 订单详情 |
| POST | `/api/v1/order/cancel/{id}` | 取消订单 (reason) |
| POST | `/api/v1/order/pay/{id}` | 发起支付 (pay_channel: wechat/balance, use_points: 可选积分抵现) |
| POST | `/api/v1/order/refund/{id}` | 申请退款 |
| POST | `/api/v1/order/verify/{id}` | 核销 (code: 二维码值) |
| POST | `/api/v1/order/reschedule/{id}` | 预约改期 (new_service_time 必填/reason 可选) |
| GET | `/api/v1/order/logistics/{id}` | 物流跟踪（第19轮，product 订单） |
| POST | `/api/v1/order/review/{order_id}` | 提交评价 (rating 1-5/content/images)（第19轮补注册） |
| POST | `/api/v1/order/review/{order_id}/append` | 评价追评 (content/images 逗号分隔)（第19轮） |

**订单状态**: pending(待支付) → paid(已支付) → confirmed(已确认) → serving(服务中) → completed(已完成)

**创建订单时**: Redis SETNX 锁定技师3分钟，退出页面或超时释放。

**价格防篡改（2026-08-26）**: 订单项金额一律以数据库记录为准（target_type=service 查 appointment_service、product 查 appointment_product），客户端传价不参与计算；未知 target_type 422；target_id 必须传 hashid 编码值（传 raw id 解码为 0 → 422「商品不存在或已下架」）；拼团/秒杀价同样以 DB 为准。

**退款规则**: 下单15min内或距开始>6h退100% / ≤6h退90% / 已开始退80% / 确认开始后不退。

**优惠券抵扣**: 创建订单可选传 user_coupon_id（hashid）。错误码: 他人券 404、门槛不足/已过期/已下架/已使用 422、非法 hashid 422。抵扣两段式：下单时 PriceCalculator.applyCoupon 只读校验并计算抵扣金额写入 discount_amount；支付成功后 consume 将优惠券置为 used；退款时 restoreCouponAndCard 幂等归还。

**余额支付与退款**: 支付请求体传 `pay_channel: "balance"` 使用钱包余额；微信退款与余额退款均将金额回充至钱包余额。

**积分抵现**: 支付请求体可选传 `use_points`（整数）。SUM 聚合校验积分余额（appointment_user_points 的 balance 列为单次增量快照，不可直接当余额），抵扣额 = floor(use_points / config('app.points_rate', 100)) 元，实付金额 = 原应付 - 抵扣额（下限 0.01，超出应付按应付满减不浪费积分）。成功时写 type=consume/source=points_offset 消费流水（幂等，重试不重复扣）。余额不足 422。

**积分回补**: 取消/退款时归还 points_offset 消耗的积分（type=earn/source=points_refund）：取消全额、退款按比例，5 挂接点幂等（refundOffsetPoints）。

**拼团下单（第16轮）**: 创建订单可选传 `promotion_id`（hashid）。校验：仅 group_buy 类型、活动有效期内、调用者是参与者、未满员（已成团锁定 422）、订单服务与活动匹配；拼团价 = 原价 × discount_percent/100，禁用优惠券/次卡/积分叠加（传任一即 422）。订单落库 promotion_id/participant_id；支付完全复用 `POST /api/v1/order/pay/{id}`，pay 时懒判定活动已关闭（到期未成团）→ 订单自动取消并释放技师锁。

**秒杀下单（第18轮，已下线）**: ~~创建订单传 `promotion_id`（flash_sale 类型）~~ —— 2026-08 起旧促销 FLASH_SALE 通道删除，store() 促销分支仅剩拼团 GROUP_BUY（非拼团 promotion 422）；秒杀统一走第24轮 `/api/v1/seckill` 通道（seckill_id 注入 store 事务内行锁扣库存），PromotionController::index 过滤 flash_sale、show/join 对其返回 400，`Promotion::TYPE_FLASH_SALE` 常量保留兼容历史数据。

**预约改期（第17轮）**: `POST /api/v1/order/reschedule/{id}` 传 new_service_time（必填）+ reason（可选），同技师换时间。规则：仅本人订单（非本人 404）、仅 appointment 类型且状态 pending/paid/confirmed 可改（其余 422）、距原服务开始 ≥ 6 小时（与全额退款窗口一致）方可改期。并发防护：B1 order_lock（与 pay/cancel/refund 同一互斥族）→ 新时段技师锁 Redis SETNX EX 180（并发改期防超卖）→ 事务内行锁重读 + B2 排班冲突 DB 校验（排除本单）→ 更新 service_time + 落 appointment_order_reschedule 记录 → 释放原时段锁、新时段锁由本单持有 → SCENE_RESCHEDULE 订阅消息（未配置降级站内通知）。失败路径事务回滚同时释放新时段锁。

**物流跟踪（第19轮）**: `GET /api/v1/order/logistics/{id}` — 仅本人 product 订单可查（非本人/非商品/未发货统一 404）。读取 order.remark JSON（shipping_company/tracking_no/shipped_at，admin MallOrderController::ship() 发货时写入），parseShippingInfo/parseReceiver 双解析兜底旧格式；收货人手机号脱敏 138****5678。

**评价（第19轮）**: `POST /api/v1/order/review/{order_id}` 提交评价（rating 必填 1-5、content/images 可选）：非本人 404、非 completed 422、重复评价 400。`POST /api/v1/order/review/{order_id}/append` 追评（content 必填、images 逗号分隔）：评价不存在/非本人统一 404、非 completed 422、重复追评 422、空内容 422；成功写 append_content/append_images(JSON)/append_at 并站内通知技师 type='review_append'，响应透出 append 字段。

### 4.1 售后接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/v1/aftersales` | 申请售后 (order_id hashid/type: refund|exchange/reason)，校验本人订单 404、状态 paid+completed 才可申请 422、同单进行中售后去重 422 |
| GET | `/api/v1/aftersales` | 我的售后列表 (?status=&page=1&limit=) |
| GET | `/api/v1/aftersales/{id}` | 售后详情（归属校验 404） |

**售后状态**: pending(待审核) → approved(通过) / rejected(拒绝)。approved 仅状态流转，退款动作沿用 `POST /api/v1/order/refund/{id}`。

---

### 4.2 拼团/促销接口（需JWT认证；FLASH_SALE 已下线）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/promotions` | 活动列表 (?type=group_buy；flash_sale 已过滤不返回) |
| GET | `/api/v1/promotions/{id}` | 活动详情（含参与人数/是否成团；flash_sale 类型 400） |
| GET | `/api/v1/promotions/{id}/participants` | 参与列表 |
| POST | `/api/v1/promotions/join/{id}` | 参与活动（第15轮完善：响应含 discount_percent/original_price/group_price；flash_sale 类型 400） |

**参与规则**: group_buy 满员（≥min_people）锁定、已成团后新参与 422；到期未满员惰性关闭（show/join 时 status 置 0）。join 后按拼团价下单见「拼团下单（第16轮）」。秒杀不再走本通道，见「24. 秒杀接口」。

---

### 5. 营销接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/marketing/coupons` | 优惠券列表 (?status=available/used/expired) |
| POST | `/api/v1/marketing/coupons/receive` | 领取优惠券 (coupon_id) |
| GET | `/api/v1/marketing/cards` | 会员卡列表 |
| POST | `/api/v1/marketing/cards/buy` | 购买会员卡 (card_id) |
| GET | `/api/v1/marketing/cards/my` | 我的次卡列表 |
| POST | `/api/v1/marketing/cards/use` | 核销次卡 (user_card_id/service_id/remark?) |
| GET | `/api/v1/marketing/gift-cards` | 礼品卡列表 |
| GET | `/api/v1/marketing/gift-cards/my` | 我的礼品卡 (redeem记录) |
| POST | `/api/v1/marketing/gift-cards/redeem` | 兑换礼品卡 (cash类型兑换后充值钱包余额) |
| GET | `/api/v1/marketing/points` | 积分流水 (?type=earn/use/expire&source=order/referral/gift_card/check_in/admin) |
| GET | `/api/v1/marketing/points-exchange` | 积分兑换商品列表（上架 + 实时剩余库存 + 已兑数） |
| POST | `/api/v1/marketing/points-exchange/{id}` | 兑换 (type=coupon 发券 / wallet 入账 / gift_card 卡密返回) |
| POST | `/api/v1/marketing/coupons/transfer` | 生成转赠码 (user_coupon_id: 8位唯一码/7天有效) |
| POST | `/api/v1/marketing/coupons/claim` | 领取转赠券 (code) |
| GET | `/api/v1/marketing/coupons/transfers` | 转赠记录 (发出 pending/claimed/expired + 收到 claimed) |

**次卡**: cards/my 返回 card_id/name/type/services/total_times/used_times/remaining_times/start_at/end_at/status（实时计算）。核销成功返回 {order_id, usage_id, remaining_times}；错误码: 无效 hashid 422、次数不足 422、已过期 400、非本人 404、Redis 防重 400。

**礼品卡**: gift-cards/my 返回 redeem 记录 (type/amount/gift_name/status/used_at)。

**积分规则**: 明细分页，type 过滤 (earn/use/expire)，source 过滤 (order/referral/gift_card/check_in/admin)。签到返积分 (CheckIn, type=earn)；消费返积分 floor(paid_amount×1)，核销时发放且幂等；退款按比例回扣积分。

**积分过期（第17轮）**: appointment_user_points.expires_at 列（配置 points.expiry_days，默认 365 天，≤0 永不过期），所有 earn 落库填有效期；PointsExpiryTimer 定时进程每 60s 游标扫描过期 earn 行，写 type=expire 负值扣减行（source=expiry + order_id 溯源原流水，三层幂等）+ 聚合站内通知「您有 X 积分已过期」；可用余额 SUM 口径含 expire 负值行，过期积分不可再抵现/兑换。

**优惠券转赠（第17轮）**: transfer 校验券属于本人/available/券定义未过期/未被转赠过，生成 8 位去混淆字符唯一转赠码（uk_code 唯一索引兜底），7 天有效。claim 防滥用：Redis NX 锁（coupon_transfer_claim:{code} 30s）+ 行锁复验防双花、uk_user_coupon 唯一索引限同一券仅可转赠一次、被转赠券不可再转（新券无转赠记录自然拦截）、不可领取自己转赠的券 422、接收人非原持有人；懒判定过期置 expired 并恢复原券 available。claim 事务内原券置 used + 生成新 UserCoupon 绑定接收人（coupon_id 不变即有效期不变）+ 记录置 claimed。

---

### 6. 通知接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/notification` | 通知列表 (?type=order/system&page=1) |
| PUT | `/api/v1/notification/read/{id}` | 标记已读 |
| PUT | `/api/v1/notification/read-all` | 全部已读 |

---

### 7. 钱包接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/wallet` | 钱包余额 + 流水分页 |
| POST | `/api/v1/wallet/recharge` | 创建充值单 (amount: 元) |
| POST | `/api/v1/wallet/recharge/{id}/pay` | 充值单发起支付 (微信) |
| POST | `/api/v1/wallet/transfer` | 余额转账 (to_user_id hashid/amount/remark 可选/client_token 可选)（第19轮） |
| GET | `/api/v1/wallet/transfers` | 转账记录 (?direction=out/in&page=1)（第19轮） |
| GET | `/api/v1/wallet/transfers/{id}` | 转账详情（仅双方可见，他人 404）（第19轮） |

**流水**: wallet_txn 类型: recharge / consume / refund / gift_card / referral_reward(分销返佣) / referral_level2(二级返佣) / points_exchange(积分兑换入账)，分页返回。

**充值**: `POST /api/v1/wallet/recharge` 传 amount（元）创建充值单，返回充值单 hashid。`POST /api/v1/wallet/recharge/{id}/pay` 发起微信支付，响应含 sign_params（同订单支付模式）；支付回调以 R 前缀的 out_trade_no 区分充值单与订单。

**余额支付**: 订单支付请求体传 `pay_channel: "balance"` 使用钱包余额；微信退款与余额退款均将金额回充至钱包余额。

**余额转账（第19轮）**: `POST /api/v1/wallet/transfer` — 接收人 hashid 解码+存在性 404、转自己 422、金额 0.01-1000/笔 422（DECIMAL 比对禁 float）、余额不足 422、单日累计 5000 元 422。并发/幂等：Redis NX 锁 wallet_transfer:{from} 30s 串行化转出方 → 事务内双方钱包行按 user_id 升序 lockForUpdate（固定顺序防死锁）→ 扣转出方 + 增接收方 + WalletTxn 双流水（transfer_out/transfer_in 含 balance_after 快照）+ 转账记录 completed + 接收方站内通知 type='balance_received'（失败仅记日志）。client_token 可选：成功后 SETNX 24h 防重复提交（失败请求不落 token 可重试）。

---

### 8. 店长工作台接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/store-manager/overview` | 今日概览 (今日订单数/今日营收/进行中/技师数/核销数) |
| GET | `/api/v1/store-manager/orders` | 门店订单列表 (?status=&page=&limit=) |
| GET | `/api/v1/store-manager/technicians` | 技师列表（含今日排班） |
| GET | `/api/v1/store-manager/revenue` | 近 7 天营收聚合 |

**store_id 隔离**: requireStoreId() 强制当前用户绑定门店（appointment_user.store_id），无门店 403；所有查询按 store_id 过滤。

---

### 9. 成长等级接口（需JWT认证，第20轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/growth` | 当前成长概览（balance/等级/下一档差额/等级名称） |
| GET | `/api/v1/growth/records` | 成长值流水分页 (?page=&limit=) |
| GET | `/api/v1/growth/levels` | 档位列表（公开，无需登录） |

**成长值入账**: 签到 +10；提交评价 +20（追评不入账）；消费 floor(paid) 每 1 元 1 点（支付回调内复用状态复验幂等，重复回调不重复入账）。

### 10. 发票接口（需JWT认证，第20轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/v1/invoices` | 申请发票 (order_id hashid/order_type: service=服务/points_exchange=积分兑换/order_type 默认 service；金额与抬头服务端带出，不可篡改) |
| GET | `/api/v1/invoices` | 发票列表 (?status=&page=) |
| GET | `/api/v1/invoices/{id}` | 发票详情（仅本人） |

**防重复**: uk_order_type(order_id, order_type) 唯一键，同一订单同类型重复申请 422（含 MySQL 1062 捕获兜底）。

### 11. 客服工单接口（需JWT认证，第20轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/v1/tickets` | 提交工单 (title/content 必填) |
| GET | `/api/v1/tickets` | 工单列表 (?status=open/closed&page=) |
| GET | `/api/v1/tickets/{id}` | 工单详情（仅本人，他人 404） |
| POST | `/api/v1/tickets/{id}/close` | 关闭工单（仅本人/仅 open；可选 rating 1-5 满意度打分，越界/非整数 422，未提供兼容 NULL） |

### 12. 预约月历接口（需JWT认证，第20轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/calendar/technician/{id}` | 月视图 (?month=YYYY-MM)：排班 time_slots 展开小时槽 + 已约排除 |
| GET | `/api/v1/calendar/technician/{id}/day` | 日视图 (?date=YYYY-MM-DD)：当天可约/已约/不可约槽位明细 |

### 13. 发票抬头接口（需JWT认证，第21轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/v1/invoice-titles` | 保存抬头 (title_type: personal/company；company 必须 tax_no；同用户同抬头重复 422；首条自动为默认) |
| GET | `/api/v1/invoice-titles` | 抬头列表（默认置顶） |
| PUT | `/api/v1/invoice-titles/{id}` | 编辑抬头（仅本人） |
| DELETE | `/api/v1/invoice-titles/{id}` | 删除抬头（仅本人；删默认后自动指定最早一条） |
| POST | `/api/v1/invoice-titles/{id}/default` | 设为默认（事务清零同用户其他行） |

**申请联动**: POST /api/v1/invoices 支持可选 title_id —— 解析抬头自动带入 invoice_title/tax_no/title_type，无 title_id 时保留原手填路径。

### 14. 浏览足迹接口（需JWT认证，第21轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/browse-history` | 最近浏览服务（join 服务名称/封面/价格/原价，viewed_at 倒序，per_page 默认 15 上限 50） |
| DELETE | `/api/v1/browse-history/{item_id}` | 删除单条（仅本人，非法/他人 404） |
| DELETE | `/api/v1/browse-history` | 清空足迹（仅本人） |

**记录时机**: 服务详情接口访问成功后自动记录（未登录跳过；重复浏览只刷新 viewed_at 不重复插入）。

### 15. 满减活动接口（第22轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/full-reduction-activities` | 生效中满减活动列表（status=1 且时间在有效期内，按减免额降序；公开接口） |

**下单叠加规则**: 满减仅标准订单生效（拼团/秒杀跳过），以券/次卡抵扣后的应付金额判断门槛（threshold），叠加顺序 **券/次卡 → 满减 → 等级折扣**；取减免额最大活动；优惠额并入 discount_amount、备注追加「满减：满X减Y」；满减后实付下限 0.01 元。

### 16. 我的预约 ICS 导出（需JWT认证，第22轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/order/ics` | 导出 90 天内有效订单（pending/paid/confirmed/serving）为 iCal（RFC5545） |

**输出**: `Content-Type: text/calendar; charset=utf-8` + `Content-Disposition: attachment; filename="my-appointments.ics"`。VEVENT：UID=订单ID、TZID=Asia/Shanghai、摘要「预约：服务名」（缺失退化「预约」）、描述（技师/门店/地址，缺失跳过）、LOCATION 门店名；文本按 RFC5545 转义（\, \; \\ \n）+ 75 字节行折叠。无订单返回合法空日历；仅导出本人订单。

### 17. 技师考勤接口（需JWT认证，第22轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/v1/technician/attendance/check-in` | 上班打卡（当日重复 422，唯一索引兜底并发；>10:00 标记迟到） |
| POST | `/api/v1/technician/attendance/check-out` | 下班打卡（未上班/已下班 422，行锁并发） |
| GET | `/api/v1/technician/attendance` | 当月考勤列表 + 出勤天数/总工时/平均工时汇总（?month=YYYY-MM，非法 422） |

### 18. 隐私合规接口（需JWT认证，第22轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/privacy/data` | 数据导出（personal/orders/points/wallet_txns/reviews/addresses/invoices 分组 JSON；服务端日志仅记脱敏手机号+条数） |
| POST | `/api/v1/privacy/close-request` | 申请注销（余额非 0 / 未完成订单 / 进行中工单 422；置 close_status=1 + close_requested_at） |
| POST | `/api/v1/privacy/close-cancel` | 取消注销申请（close_status 1→0） |
| POST | `/api/v1/privacy/close-confirm` | 确认注销（满 72h 方可；close_status=2 + close_at + phone/nickname 匿名化为 user{id} + status=0） |

**登录拦截**: close_status=2 的账号登录返回 403「账号已注销」。

### 19. 用户健康档案接口（需JWT认证，第23轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/health-profile` | 查询我的健康档案（无档案返回空对象） |
| PUT | `/api/v1/health-profile` | 创建/更新（upsert，一人一份；allergies/health_notes 上限 500 字、preferred_technician_id 校验存在性；只更新提供的字段，响应 hashid 编码） |
| DELETE | `/api/v1/health-profile` | 删除我的档案（仅本人） |

字段: allergies（过敏史）/health_notes（健康备注）/preferred_technician_id（偏好技师，可空）。

### 20. 钱包支付密码接口（需JWT认证，第23轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/v1/wallet/pay-password/set` | 设置支付密码（6 位数字 `\d{6}`；已设置时需传旧密码 422 拦截） |
| POST | `/api/v1/wallet/pay-password/verify` | 校验支付密码（正确/错误返回布尔，不落库） |
| POST | `/api/v1/wallet/pay-password/check` | 查询是否已设置（set: true/false） |

存储: password_hash() 哈希 + pay_password_set_at，绝不存储明文。

### 21. 订单状态时间线接口（需JWT认证，第23轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/order/{id}/timeline` | 订单状态变更时间线（倒序；仅本人，他人订单 404 不泄露存在性） |

埋点: 提交/支付（微信回调 markOrderPaid 单一消费点）/取消/技师确认/退款申请/退款通过/服务开始/服务完成/超时自动取消/后台操作（operator=admin）共 8 类变更。

### 22. 积分幸运转盘接口（需JWT认证，第23轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/wheel/prizes` | 转盘奖品列表（隐藏 weight/stock 敏感字段） |
| POST | `/api/v1/wheel/spin` | 抽奖一次（Redis NX + 行锁防并发；random_int 权重抽取；积分→earn 流水含过期时间、余额→lockForUpdate 入账、优惠券→pending 人工发放、无奖→lose；client_token 幂等） |
| GET | `/api/v1/wheel/records` | 我的抽奖记录（分页） |

### 23. 游客模式接口（第24轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/guest/home` | 首页聚合（轮播图/公告/服务分类/热门服务，Redis 缓存 svc:guest:home 300s） |
| GET | `/api/v1/guest/services` | 服务列表（?category_id=hashid&sort=newest|sales|price&page/per_page≤50） |
| GET | `/api/v1/guest/services/{id}` | 服务详情（不存在 404） |
| GET | `/api/v1/guest/stores` | 门店列表 |
| GET | `/api/v1/guest/technicians` | 技师列表（仅审核通过；?service_id=hashid 筛选；评分降序） |

无需认证（公开接口）的未登录浏览入口。

### 24. 秒杀接口（需JWT认证，第24轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/seckill` | 秒杀活动列表（status=1 且在时间窗内；含已售量 = appointment_order.seckill_id 订单数、剩余库存） |
| GET | `/api/v1/seckill/{id}` | 活动详情（state=not_started/ongoing/ended） |
| POST | `/api/v1/seckill/{id}/buy` | 秒杀下单（client_token 幂等 + Redis NX 30s 防并发 + 活动校验；不再预扣库存） |

**下单规则（2026-08-26 起）**: 库存统一在 `/api/v1/order store()` 事务内行锁扣减，buy 仅做入口校验/幂等；秒杀价 = seckill_price（以 DB 为准），不叠加优惠券/积分/会员卡；订单取消不回补库存；直接调 `/api/v1/order` 携带 seckill_id 同样扣库存。

### 25. APP 版本检查接口（第24轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/v1/app/version?platform=android|ios` | 最新版本检查（platform 非法 422；无版本返回空对象；公开接口） |

响应: id/platform/version_code/version_name/force_update（1=强制）/changelog/download_url。

---

## 二、管理后台API (admin/ :8787)

请求头: `Authorization: Bearer <admin_token>`；公开认证接口版本随 URL 前缀 `/api/v1`

### 仪表盘

**`GET /admin/dashboard`** — 仪表盘数据

响应: user_count / order_count / technician_count / today_revenue + 图表数据(订单量/金额/新增用户/活跃度)

### 用户管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/user` | 用户列表 (?keyword/status/page/per_page) |
| POST | `/admin/user` | 新增用户 |
| GET | `/admin/user/{id}` | 用户详情 |
| PUT | `/admin/user/{id}` | 编辑用户 |
| DELETE | `/admin/user/{id}` | 删除用户 |
| POST | `/admin/user/batch/destroy` | 批量删除 |
| POST | `/admin/user/batch/status` | 批量启禁用 |

### 会员卡管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/member-cards` | 卡列表 (?keyword/status/page/per_page) |
| GET | `/admin/member-cards/{id}` | 卡详情 |
| POST | `/admin/member-cards` | 新增卡 (services JSON校验) |
| PUT | `/admin/member-cards/{id}` | 更新卡/上下架 |
| DELETE | `/admin/member-cards/{id}` | 删除卡 (有用户持卡时拒绝) |

权限ID: 365-369。

### 门店工作台（第15轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/stores/workbench-overview` | 门店工作台概览 (?store_id=hashid：今日订单数/今日营收/进行中/技师数/今日核销，口径与 service 端一致) |
| GET | `/admin/orders` | 订单列表新增 store_id 筛选 (hashid 解码) |

权限ID: 372。

### 积分兑换商品（第16轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/points-exchange-goods` | 商品列表 (?keyword/status/page/per_page) |
| POST | `/admin/points-exchange-goods` | 新增商品 (type=coupon/gift_card/wallet；coupon 传 hashid、wallet/gift_card 传金额元) |
| PUT | `/admin/points-exchange-goods/{id}` | 更新商品 |
| DELETE | `/admin/points-exchange-goods/{id}` | 删除商品 |
| POST | `/admin/points-exchange-goods/{id}/toggle-status` | 上下架切换 |
| GET | `/admin/points-exchange-goods/{id}/exchanges` | 兑换记录列表（含用户手机号 + result 快照） |

权限ID: 373-378。

### 返佣记录（第16轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/referral-rewards` | 返佣记录 (?keyword=&page=&limit=，仅已发放记录，推荐人/被推荐人昵称或手机号筛选，hashid 编码) |

权限ID: 379。

### 技师等级（第17轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/technician-tiers/logs` | 等级变更日志（join 技师姓名与新旧等级名，hashid 编码，分页） |

权限ID: 380。

**自动评定**: TierRatingService::evaluate 实时统计（appointment_order completed 订单数 + 评价均分，四舍五入 1 位小数）回写 profile.order_count/rating，按 appointment_technician_tier_config（min_orders/min_rating）从高到低匹配，无匹配归最低等级。仅升级不降级（降级影响佣金率与价格系数，由后台人工兜底；allowDowngrade=true 供人工重评）；幂等（等级一致只同步统计）；变更落 appointment_technician_tier_log + 站内通知。触发点：WorkController::complete / ReviewController 评价写入 / ProfileController 查看资料懒判定。

### 评价回复查看（第18轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/reviews/{id}/reply` | 评价回复详情（decodeId → find → 404 → decorate 输出；未回复 reply=''，reply/replied_at 经 toArray 透出；静态路由先于 resource） |

权限ID: 381（slug 'get.admin/reviews/{id}/reply'）。

### 发票管理（第20轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/invoices` | 发票列表（?status=pending/issued/rejected&page=） |
| POST | `/admin/invoices/{id}/issue` | 开票 (invoice_no 必填，status→issued + issued_at；幂等：已开票 422) |
| POST | `/admin/invoices/{id}/reject` | 驳回 (reject_reason 必填，status→rejected；仅 pending 可驳回) |

权限ID: 382 列表 / 383 开票 / 384 驳回。

### 工单管理（第20轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/tickets` | 工单列表（?status=&page=，静态路由先于 resource 避免 shadow） |
| POST | `/admin/tickets/{id}/reply` | 回复工单 (content 必填，写 reply_content/replied_at，工单回到 open) |
| GET | `/admin/tickets/satisfaction` | 满意度汇总（第21轮）：total/rated_count/unrated_count/average 1位小数/1-5星 distribution 缺星补 0；静态路由先于 resource |

权限ID: 385 工单回复 / 387 工单列表查看 / 388 工单满意度统计。

### 评价图片审核（第21轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/review-audit` | 带图评价列表（JSON_LENGTH(images)>0，?status=visible/hidden&page=，join 用户昵称与技师名，ID hashid 编码） |
| POST | `/admin/review-audit/{id}/hide` | 隐藏评价（仅 visible 可隐藏，否则 422；隐藏后用户端技师评价列表自动不可见） |
| POST | `/admin/review-audit/{id}/restore` | 恢复评价（仅 hidden 可恢复，否则 422） |

权限ID: 389 列表 / 390 隐藏 / 391 恢复。

### 二级返佣记录（第20轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/referral-level2` | 二级返佣记录（join 一级推荐人与二级推荐人昵称，分页） |

权限ID: 386。发放规则：订单支付后给一级推荐人的推荐人发 paid×level2_rate（系统配置 referral.level2_rate 默认 0.02），uk_order_referred 幂等防重复。

### 考勤管理（第22轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/attendance` | 考勤记录（?date=YYYY-MM&name=技师名&page=；join real_name，ID hashid 编码） |
| GET | `/admin/attendance/stats` | 按技师分组统计（打卡天数/总工时/平均工时；?date=YYYY-MM，非法 422） |

权限ID: 392 列表 / 393 统计。

### 满减活动管理（第22轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/full-reduction-activities` | 活动列表（分页） |
| POST | `/admin/full-reduction-activities` | 新增（threshold/reduction/title/status/start_at/end_at） |
| PUT | `/admin/full-reduction-activities/{id}` | 编辑 |
| POST | `/admin/full-reduction-activities/{id}/toggle-status` | 上下架 |
| DELETE | `/admin/full-reduction-activities/{id}` | 删除（带 confirmPassword） |

权限ID: 396 列表 / 397 新增 / 398 编辑 / 399 上下架 / 400 删除（一条权限记录对应一个 method.path slug，故 5 路由 5 条）。

### 分账记录（第22轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/profit-sharing` | 分账记录（leftJoin 订单号/技师昵称，?status&order_no&technician_name&page=，hashid 编码） |

权限ID: 394。服务端逻辑：appointment_system_config group=profit_sharing（enabled/receiver_ratio）；未启用 disabled 降级仅日志；启用后支付成功自动请求分账（金额=实付×receiver_ratio 默认 0.7，同单 pending/success 幂等跳过）；无凭据不执行 HTTP，请求结构记日志。

### 积分转盘管理（第23轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/lucky-wheel` | 转盘奖品列表（含 weight/stock，分页） |
| POST | `/admin/lucky-wheel` | 新增奖品（名称/类型 points/balance/coupon/none/权重/库存/图片） |
| GET/PUT | `/admin/lucky-wheel/{id}` | 详情 / 编辑 |
| DELETE | `/admin/lucky-wheel/{id}` | 删除 |
| POST | `/admin/lucky-wheel/{id}/toggle-status` | 上下架 |
| GET | `/admin/lucky-wheel/records` | 抽奖记录（?status&page=，含用户昵称/奖品名） |

权限ID: 401-406。静态路由 `/lucky-wheel/records` 与 `/lucky-wheel/{id}/toggle-status` 注册在 resource 之前避免 {id} shadow。

### 回头客奖励管理（第24轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/return-customer/config` | 配置查看（enabled 开关 / ratio 比例） |
| PUT | `/admin/return-customer/config` | 配置更新（enabled in:0,1；ratio between:0.01,1） |
| GET | `/admin/return-customer/rewards` | 奖励记录列表（?keyword 技师姓名/订单号/用户昵称，type=return_customer 分页） |

权限ID: 412-414。奖励规则：用户对同一技师 30 天内第 2 次消费（订单完成）发放奖金 = 实付 × ratio（默认 0.05），落 appointment_technician_earnings（type=return_customer，status=pending）随佣金结算链统一结算；同订单幂等不重复发放。

### 秒杀活动管理（第24轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/seckill` | 活动列表（分页） |
| POST | `/admin/seckill` | 新增活动（name/service_id/seckill_price/original_price/stock/start_at/end_at） |
| GET | `/admin/seckill/{id}` | 活动详情 |
| PUT | `/admin/seckill/{id}` | 编辑 |
| DELETE | `/admin/seckill/{id}` | 删除 |
| POST | `/admin/seckill/{id}/toggle-status` | 上下架 |
| GET | `/admin/seckill/{id}/orders` | 秒杀订单列表 |

权限ID: 407-411、420。已售量 = appointment_order.seckill_id 订单数；库存行锁扣减、售罄拦截。

### APP 版本管理（第24轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/versions` | 版本列表 |
| POST | `/admin/versions` | 新增版本（platform/version_code/version_name/force_update/changelog/download_url/status） |
| PUT | `/admin/versions/{id}` | 编辑 |
| DELETE | `/admin/versions/{id}` | 删除 |

权限ID: 416-419。检测更新接口 /api/v1/app/version 取 status=1 中最新（updated_at/id 最大）版本。

### 排班导出（第24轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/technician-schedule/export` | 排班 CSV 导出（UTF-8 BOM，Excel 直接打开；start_date/end_date 必填且跨度≤31天；technician_id 可选 hashid） |

权限ID: 415。列：技师ID/技师姓名/日期/时间段明细（time_slots JSON 解析为 "09:00-12:00, 14:00-18:00"）。

### 角色权限

| 方法 | 路径 | 说明 |
|------|------|------|
| GET/POST/PUT/DELETE | `/admin/role` | 角色CRUD |
| GET/POST/PUT/DELETE | `/admin/permission` | 权限CRUD（树形结构）|

### 系统配置

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/config` | 配置列表 |
| POST | `/admin/config` | 新增配置 (group/key/value/type/description) |
| PUT | `/admin/config/{id}` | 编辑配置 |
| DELETE | `/admin/config/{id}` | 删除配置 |

### 操作日志

**`GET /admin/log`** — 日志查询

参数: `?user_id/action/source/start_date/end_date/page`

`souce` 字段: web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS

### 导出

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/admin/export/excel` | Excel导出 (type: users/technicians/orders/finance)。敏感字段自动脱敏 |
| POST | `/admin/export/pdf` | PDF面板导出 (type: dashboard) |

### 文件上传

**`POST /admin/upload`** — 文件上传 (multipart/form-data)

### 个人中心

| 方法 | 路径 | 说明 |
|------|------|------|
| PUT | `/admin/profile` | 修改个人资料 |
| PUT | `/admin/profile/password` | 修改密码 |
| POST | `/admin/profile/logout` | 退出登录 |

### 导入

**`POST /admin/import/users`** — 批量导入用户 (Excel)

### 监控

| 方法 | 路径 | 认证 | 说明 |
|------|------|------|------|
| GET | `/health` | 无 | 健康检查 |
| GET | `/metrics` | 无 | Prometheus指标 |
| GET | `/.well-known/security.txt` | 无 | 安全联系人(RFC 9116) |
| GET | `/api/docs` | 无 | API文档 |

---

## 三、通用说明

### 错误码

| code | 说明 |
|------|------|
| 0 | 成功 |
| 401 | 未登录或Token过期 |
| 403 | 无权限 |
| 404 | 资源不存在 |
| 422 | 参数验证失败 |
| 429 | 请求过于频繁 |

### ID编码

- 所有API响应中的 `id` 和 `*_id` 字段通过hashids编码
- 请求中携带的 `id` 参数也应使用hashids编码格式
- 前端直接使用编码字符串，无需手动解码

### 手机号脱敏

响应中手机号格式: `138****8000`。Excel导出时间样处理。

### 数据加密

- API层: 响应中的敏感字段通过 `erikwang2013/encryption` 加密
- DB层: 手机号/身份证/微信ID等通过 `erikwang2013/encryptable` 自动加解密

### 环境变量配置

| 变量 | 说明 |
|------|------|
| WECHAT_SUBSCRIBE_TEMPLATE_ID | 预约提醒订阅消息模板ID |
| WECHAT_SUBSCRIBE_TEMPLATE_PAID | 支付成功订阅消息模板ID |
| WECHAT_SUBSCRIBE_TEMPLATE_REFUND | 退款订阅消息模板ID |
| WECHAT_SUBSCRIBE_TEMPLATE_VERIFIED | 核销订阅消息模板ID |
| WECHAT_SUBSCRIBE_TEMPLATE_REMINDER | 服务开始前提醒订阅消息模板ID（第18轮） |
| WECHAT_SUBSCRIBE_TEMPLATE_EXPIRY | 会员卡/优惠券到期提醒订阅消息模板ID（第18轮） |

未配置订阅消息模板时自动降级为站内通知。

**订阅消息场景**: SCENE_PAY(支付成功) / SCENE_REFUND(退款到账) / SCENE_VERIFIED(核销成功) / SCENE_RESCHEDULE(改期成功) / SCENE_REMINDER(服务开始前提醒，第18轮) / SCENE_EXPIRY(到期提醒，第18轮)。推送成功才写 push_sent_at，失败下轮重试。

**充值到账通知（第18轮）**: 微信充值回调（R 前缀单号）事务内写站内通知 type='wallet_recharge'「您已成功充值 ¥X.XX」；复用回调幂等（仅首次 pending→paid 触发），与状态变更同事务原子提交，写入失败不阻塞主流程。
