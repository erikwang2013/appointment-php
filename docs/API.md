# API 说明文档

## 概述

- **业务API** (service/): `http://localhost:8787` — 为小程序/APP提供业务接口
- **管理后台API** (admin/): `http://localhost:8787` — 为管理后台Flutter Web提供接口
- **认证方式**: Bearer Token (JWT), 请求头 `Authorization: Bearer <token>`
- **版本控制**: 通过请求头 `API-Version: v1` 控制API版本，不在URL中体现。默认v1
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

**`POST /api/captcha/send`** — 发送短信验证码

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

**`POST /api/auth/register`** — 手机号注册

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

**`POST /api/auth/login`** — 密码登录

请求:
```json
{
  "phone": "13800138000",
  "password": "abc123"
}
```
响应: 同注册响应，包含token和user信息。

---

**`POST /api/auth/login-by-code`** — 验证码登录

请求:
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```
响应: 同登录。未注册用户自动创建账号。

---

**`POST /api/auth/forget-password`** — 忘记密码

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

**`POST /api/auth/refresh`** — 刷新Token

请求头: `Authorization: Bearer <旧token>`
响应: `{"code":0,"data":{"token":"eyJhbGciOi..."}}`

---

#### 1.3 微信

**`POST /api/wechat/mini-login`** — 小程序登录

请求: `{"code":"微信登录code"}`
说明: 首次登录需后续调用 `/api/wechat/phone` 绑定手机号。

---

**`POST /api/wechat/phone`** — 绑定手机号

请求: `{"code":"微信手机号组件code"}`

---

**`POST /api/wechat/oa-login`** — 公众号登录

请求: `{"code":"公众号授权code"}`

---

#### 1.4 公共服务

**`GET /api/common/config`** — 公共配置

响应: 包含协议文本(用户协议/隐私协议/服务协议)、关于我们信息、版本号。

---

**`GET /api/common/area`** — 城市区域列表

---

#### 1.5 服务查询

**`GET /api/service/categories`** — 分类列表

参数: `?parent_id=0`

---

**`GET /api/service/items`** — 服务项目列表

参数: `?category_id=&page=1&per_page=10&sort=sales`

---

**`GET /api/service/detail/{id}`** — 服务详情

响应包含: 图片/名称/价格/规格/时长/销量/评价列表。

---

**`GET /api/service/products`** — 产品列表

**`GET /api/service/stores`** — 门店列表

参数: `?lat=&lng=&city=`

---

#### 1.6 技师查询

**`GET /api/technician/list`** — 技师列表

参数: `?lat=&lng=&service_id=&page=1`
按距离由近到远排序，返回: 头像/名字/评分/订单数/收藏数/距离/最早可约时间/是否可服务。

---

**`GET /api/technician/detail/{id}`** — 技师详情

响应包含: 图片/名字/介绍/评分/距离/可服务项目列表/评价。

---

**`GET /api/technician/schedule/{id}`** — 技师排班

参数: `?date=2026-05-26`
返回该日期可预约时间段及可用状态。

---

#### 1.7 内容

**`GET /api/content/banners`** — 轮播图

参数: `?position=home`

**`GET /api/content/articles`** — 公告/文章列表

参数: `?type=announcement&page=1`

**`GET /api/content/article/{id}`** — 文章详情

---

#### 1.8 LBS

**`GET /api/lbs/nearby-stores`** — 附近门店

参数: `?lat=&lng=&radius=5000`

**`GET /api/lbs/geocode`** — 逆地理编码

参数: `?lat=&lng=`

---

### 2. 用户接口（需JWT认证）

所有接口请求头携带 `Authorization: Bearer <token>`

#### 2.1 个人资料

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/user/profile` | 获取个人信息 |
| PUT | `/api/user/profile` | 更新昵称/头像/性别 |
| POST | `/api/user/change-password` | 修改密码 (old_password/new_password/confirm_password) |
| POST | `/api/user/change-phone` | 换绑手机 (old_code/new_phone/new_code) |
| POST | `/api/user/cancel-account` | 注销账号 (需验证密码) |
| POST | `/api/user/logout` | 退出登录 (token加入黑名单) |
| POST | `/api/user/switch-role` | 切换身份 (role: customer/technician) |

切换为technician需已有approved状态的技师档案。

#### 2.2 地址管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/user/addresses` | 地址列表 |
| POST | `/api/user/addresses` | 新增地址 (contact_name/contact_phone/province/city/district/detail/lat/lng/is_default) |
| GET | `/api/user/addresses/{id}` | 地址详情 |
| PUT | `/api/user/addresses/{id}` | 更新地址 |
| DELETE | `/api/user/addresses/{id}` | 删除地址 |

设为默认时自动取消其他默认地址。

#### 2.3 收藏

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/user/favorites` | 收藏列表 (?type=service/technician) |
| POST | `/api/user/favorites` | 添加收藏 (target_type/target_id) |
| DELETE | `/api/user/favorites/{id}` | 取消收藏 |

#### 2.4 意见反馈

`POST /api/user/feedback` — 提交反馈 (content + images数组)

#### 2.5 推广推荐

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/user/referral` | 推广信息 (推荐码/推荐人数/首单人数/获得积分) |
| GET | `/api/user/referral/qrcode` | 推广二维码 (推荐码+邀请链接) |
| GET | `/api/user/referral/referred-users` | 已推荐用户列表 |
| GET | `/api/user/referral/earnings` | 分销返佣明细 (分页: 被推荐人昵称/头像/订单号/金额/发放时间) |

**分销返佣**: 被推荐人首单 completed 后发放，金额 = paid_amount × reward_rate（erik_system_config referral.reward_rate，默认 0.05，非法值回落常量）。行锁 + rewarded_at 判空 + 首单复查三重幂等；入账 WalletTxn type=referral_reward。

#### 2.6 积分转赠（第19轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/user/points/transfer` | 积分转赠 (to_user_id hashid/points) |
| GET | `/api/user/points/transfers` | 转赠记录 (?direction=sent/received&page=1) |

**积分转赠**: 接收人 hashid 解码+存在性 404、转自己 422、点数 1-10000 422、余额 SUM 聚合不足 422、单日累计 10000 限额 422。并发防护：Redis NX 锁 points_transfer:{user} 30s → 事务内双方最后一条流水 lockForUpdate（user_id 升序防互转死锁）→ 锁内复验余额/限额/接收人。流水规范：发送方 type=consume/source=points_transfer 负值（balance=上条快照-本次），接收方 type=earn/source=points_transfer 正值含 expires_at（PointsExpiryTimer 可正常过期）；commit 后站内通知接收方 type='points_received'（失败仅 warn）。

#### 2.7 消息偏好设置（第19轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/user/notify-settings` | 查询通知开关（5 类全量） |
| PUT | `/api/user/notify-settings` | 批量更新开关 (types: {service_reminder: 0/1, ...}) |

**通知开关**: erik_user_notify_setting 表（user_id+type 复合唯一键，缺省行=默认开）。5 类：service_reminder 服务提醒 / card_expiry 到期提醒（卡+券统一伞形）/ points_expiry 积分过期 / marketing 营销（预留）/ system 系统（不可关，PUT 强制为 1）。门控：notifySettingEnabled 挂接 ServiceReminderTimer/ExpiryReminderTimer/PointsExpiryTimer 3 个定时器进程 + 订阅事件场景映射（PAY/REFUND/VERIFIED/RESCHEDULE→system 恒发，REMINDER→service_reminder，EXPIRY→card_expiry）；类型关闭时站内通知与订阅消息一并跳过。

---

### 3. 技师接口（需JWT + 技师身份）

#### 3.1 技师档案

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/technician/profile` | 获取技师档案 |
| PUT | `/api/technician/profile` | 更新档案 (avatar/intro/real_name/gender/id_card/id_card_front/id_card_back) |

首次完整填写视为入驻申请，status=pending等待审核。

#### 3.2 排班

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/technician/schedule` | 排班查询 (?start_date=&end_date=) |
| PUT | `/api/technician/schedule` | 设置排班 (date/time_slots/status) |

#### 3.3 技师订单

`GET /api/technician/orders` — 订单列表 (?status=&page=1)

#### 3.4 收益

`GET /api/technician/earnings` — 收益概况 (today_income/pending_settlement/balance + 流水列表)

#### 3.5 提现

`POST /api/technician/withdraw` — 申请提现 (amount)
规则: 每月20号可提，T+1到账，最低金额/整百限制由后台配置。

#### 3.6 评价回复（第18轮）

`POST /api/technician/review/reply/{order_id}` — 技师回复评价 (reply)。评价不存在/非本人统一 404（不泄露存在性）；已有回复 422（幂等拒绝不覆盖）；空回复 422。回复成功站内通知用户（type='review_reply'）。

#### 3.6 工作台

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/technician/work/today` | 今日任务列表 |
| GET | `/api/technician/work/records` | 完成记录分页 |
| POST | `/api/technician/work/{id}/start` | 开始服务 |
| POST | `/api/technician/work/{id}/complete` | 完成服务 |

**今日任务**: status ∈ [confirmed, serving]，service_time 为今日或空，返回 service_name/price/nickname/avatar。

**完成记录**: status ∈ [serving, completed]，按 service_end_at 倒序，分页响应含 meta。

**开始/完成服务**: 行锁+状态机校验，幂等操作。开始服务写入 service_start_at；完成服务写入 service_end_at 并发送站内通知。错误码: 非本人 403、状态错误 422、无效 hashid 422。

---

### 4. 订单接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/order` | 创建订单 (order_type/items/store_id/technician_id/service_time/coupon_id/user_coupon_id/promotion_id/remark) |
| GET | `/api/order/list` | 订单列表 (?status=&page=1) |
| GET | `/api/order/detail/{id}` | 订单详情 |
| POST | `/api/order/cancel/{id}` | 取消订单 (reason) |
| POST | `/api/order/pay/{id}` | 发起支付 (pay_channel: wechat/balance, use_points: 可选积分抵现) |
| POST | `/api/order/refund/{id}` | 申请退款 |
| POST | `/api/order/verify/{id}` | 核销 (code: 二维码值) |
| POST | `/api/order/reschedule/{id}` | 预约改期 (new_service_time 必填/reason 可选) |
| GET | `/api/order/logistics/{id}` | 物流跟踪（第19轮，product 订单） |
| POST | `/api/order/review/{order_id}` | 提交评价 (rating 1-5/content/images)（第19轮补注册） |
| POST | `/api/order/review/{order_id}/append` | 评价追评 (content/images 逗号分隔)（第19轮） |

**订单状态**: pending(待支付) → paid(已支付) → confirmed(已确认) → serving(服务中) → completed(已完成)

**创建订单时**: Redis SETNX 锁定技师3分钟，退出页面或超时释放。

**退款规则**: 下单15min内或距开始>6h退100% / ≤6h退90% / 已开始退80% / 确认开始后不退。

**优惠券抵扣**: 创建订单可选传 user_coupon_id（hashid）。错误码: 他人券 404、门槛不足/已过期/已下架/已使用 422、非法 hashid 422。抵扣两段式：下单时 PriceCalculator.applyCoupon 只读校验并计算抵扣金额写入 discount_amount；支付成功后 consume 将优惠券置为 used；退款时 restoreCouponAndCard 幂等归还。

**余额支付与退款**: 支付请求体传 `pay_channel: "balance"` 使用钱包余额；微信退款与余额退款均将金额回充至钱包余额。

**积分抵现**: 支付请求体可选传 `use_points`（整数）。SUM 聚合校验积分余额（erik_user_points 的 balance 列为单次增量快照，不可直接当余额），抵扣额 = floor(use_points / config('app.points_rate', 100)) 元，实付金额 = 原应付 - 抵扣额（下限 0.01，超出应付按应付满减不浪费积分）。成功时写 type=consume/source=points_offset 消费流水（幂等，重试不重复扣）。余额不足 422。

**积分回补**: 取消/退款时归还 points_offset 消耗的积分（type=earn/source=points_refund）：取消全额、退款按比例，5 挂接点幂等（refundOffsetPoints）。

**拼团下单（第16轮）**: 创建订单可选传 `promotion_id`（hashid）。校验：仅 group_buy 类型、活动有效期内、调用者是参与者、未满员（已成团锁定 422）、订单服务与活动匹配；拼团价 = 原价 × discount_percent/100，禁用优惠券/次卡/积分叠加（传任一即 422）。订单落库 promotion_id/participant_id；支付完全复用 `POST /api/order/pay/{id}`，pay 时懒判定活动已关闭（到期未成团）→ 订单自动取消并释放技师锁。

**秒杀下单（第18轮）**: 创建订单传 `promotion_id`（flash_sale 类型）：秒杀价 = round(total × (100 − discount_percent)/100, 2)，与 PromotionController 秒杀价口径一致；校验：类型白名单 [group_buy, flash_sale]、活动进行中、调用者是参与者、服务匹配、售罄（participants_count ≥ max_people）422「已抢光」；禁用优惠券/次卡/积分叠加 422。pay() 懒判定 isFlashSaleClosed：秒杀过期 → 活动置 0 + 批量取消该活动 pending 订单 + 本单自动取消 + 释放技师锁。

**预约改期（第17轮）**: `POST /api/order/reschedule/{id}` 传 new_service_time（必填）+ reason（可选），同技师换时间。规则：仅本人订单（非本人 404）、仅 appointment 类型且状态 pending/paid/confirmed 可改（其余 422）、距原服务开始 ≥ 6 小时（与全额退款窗口一致）方可改期。并发防护：B1 order_lock（与 pay/cancel/refund 同一互斥族）→ 新时段技师锁 Redis SETNX EX 180（并发改期防超卖）→ 事务内行锁重读 + B2 排班冲突 DB 校验（排除本单）→ 更新 service_time + 落 erik_order_reschedule 记录 → 释放原时段锁、新时段锁由本单持有 → SCENE_RESCHEDULE 订阅消息（未配置降级站内通知）。失败路径事务回滚同时释放新时段锁。

**物流跟踪（第19轮）**: `GET /api/order/logistics/{id}` — 仅本人 product 订单可查（非本人/非商品/未发货统一 404）。读取 order.remark JSON（shipping_company/tracking_no/shipped_at，admin MallOrderController::ship() 发货时写入），parseShippingInfo/parseReceiver 双解析兜底旧格式；收货人手机号脱敏 138****5678。

**评价（第19轮）**: `POST /api/order/review/{order_id}` 提交评价（rating 必填 1-5、content/images 可选）：非本人 404、非 completed 422、重复评价 400。`POST /api/order/review/{order_id}/append` 追评（content 必填、images 逗号分隔）：评价不存在/非本人统一 404、非 completed 422、重复追评 422、空内容 422；成功写 append_content/append_images(JSON)/append_at 并站内通知技师 type='review_append'，响应透出 append 字段。

### 4.1 售后接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/aftersales` | 申请售后 (order_id hashid/type: refund|exchange/reason)，校验本人订单 404、状态 paid+completed 才可申请 422、同单进行中售后去重 422 |
| GET | `/api/aftersales` | 我的售后列表 (?status=&page=1&limit=) |
| GET | `/api/aftersales/{id}` | 售后详情（归属校验 404） |

**售后状态**: pending(待审核) → approved(通过) / rejected(拒绝)。approved 仅状态流转，退款动作沿用 `POST /api/order/refund/{id}`。

---

### 4.2 拼团/秒杀接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/promotions` | 活动列表 (?type=group_buy/flash_sale) |
| GET | `/api/promotions/{id}` | 活动详情（含参与人数/是否成团） |
| GET | `/api/promotions/{id}/participants` | 参与列表 |
| POST | `/api/promotions/join/{id}` | 参与活动（第15轮完善：响应含 discount_percent/original_price/group_price） |

**参与规则**: flash_sale 以 max_people 为库存上限，Redis NX 锁防超卖；重复参与 422；group_buy 满员（≥min_people）锁定、已成团后新参与 422；到期未满员惰性关闭（show/join 时 status 置 0）。join 后按拼团价下单见「拼团下单（第16轮）」。

---

### 5. 营销接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/marketing/coupons` | 优惠券列表 (?status=available/used/expired) |
| POST | `/api/marketing/coupons/receive` | 领取优惠券 (coupon_id) |
| GET | `/api/marketing/cards` | 会员卡列表 |
| POST | `/api/marketing/cards/buy` | 购买会员卡 (card_id) |
| GET | `/api/marketing/cards/my` | 我的次卡列表 |
| POST | `/api/marketing/cards/use` | 核销次卡 (user_card_id/service_id/remark?) |
| GET | `/api/marketing/gift-cards` | 礼品卡列表 |
| GET | `/api/marketing/gift-cards/my` | 我的礼品卡 (redeem记录) |
| POST | `/api/marketing/gift-cards/redeem` | 兑换礼品卡 (cash类型兑换后充值钱包余额) |
| GET | `/api/marketing/points` | 积分流水 (?type=earn/use/expire&source=order/referral/gift_card/check_in/admin) |
| GET | `/api/marketing/points-exchange` | 积分兑换商品列表（上架 + 实时剩余库存 + 已兑数） |
| POST | `/api/marketing/points-exchange/{id}` | 兑换 (type=coupon 发券 / wallet 入账 / gift_card 卡密返回) |
| POST | `/api/marketing/coupons/transfer` | 生成转赠码 (user_coupon_id: 8位唯一码/7天有效) |
| POST | `/api/marketing/coupons/claim` | 领取转赠券 (code) |
| GET | `/api/marketing/coupons/transfers` | 转赠记录 (发出 pending/claimed/expired + 收到 claimed) |

**次卡**: cards/my 返回 card_id/name/type/services/total_times/used_times/remaining_times/start_at/end_at/status（实时计算）。核销成功返回 {order_id, usage_id, remaining_times}；错误码: 无效 hashid 422、次数不足 422、已过期 400、非本人 404、Redis 防重 400。

**礼品卡**: gift-cards/my 返回 redeem 记录 (type/amount/gift_name/status/used_at)。

**积分规则**: 明细分页，type 过滤 (earn/use/expire)，source 过滤 (order/referral/gift_card/check_in/admin)。签到返积分 (CheckIn, type=earn)；消费返积分 floor(paid_amount×1)，核销时发放且幂等；退款按比例回扣积分。

**积分过期（第17轮）**: erik_user_points.expires_at 列（配置 points.expiry_days，默认 365 天，≤0 永不过期），所有 earn 落库填有效期；PointsExpiryTimer 定时进程每 60s 游标扫描过期 earn 行，写 type=expire 负值扣减行（source=expiry + order_id 溯源原流水，三层幂等）+ 聚合站内通知「您有 X 积分已过期」；可用余额 SUM 口径含 expire 负值行，过期积分不可再抵现/兑换。

**优惠券转赠（第17轮）**: transfer 校验券属于本人/available/券定义未过期/未被转赠过，生成 8 位去混淆字符唯一转赠码（uk_code 唯一索引兜底），7 天有效。claim 防滥用：Redis NX 锁（coupon_transfer_claim:{code} 30s）+ 行锁复验防双花、uk_user_coupon 唯一索引限同一券仅可转赠一次、被转赠券不可再转（新券无转赠记录自然拦截）、不可领取自己转赠的券 422、接收人非原持有人；懒判定过期置 expired 并恢复原券 available。claim 事务内原券置 used + 生成新 UserCoupon 绑定接收人（coupon_id 不变即有效期不变）+ 记录置 claimed。

---

### 6. 通知接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/notification` | 通知列表 (?type=order/system&page=1) |
| PUT | `/api/notification/read/{id}` | 标记已读 |
| PUT | `/api/notification/read-all` | 全部已读 |

---

### 7. 钱包接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/wallet` | 钱包余额 + 流水分页 |
| POST | `/api/wallet/recharge` | 创建充值单 (amount: 元) |
| POST | `/api/wallet/recharge/{id}/pay` | 充值单发起支付 (微信) |
| POST | `/api/wallet/transfer` | 余额转账 (to_user_id hashid/amount/remark 可选/client_token 可选)（第19轮） |
| GET | `/api/wallet/transfers` | 转账记录 (?direction=out/in&page=1)（第19轮） |
| GET | `/api/wallet/transfers/{id}` | 转账详情（仅双方可见，他人 404）（第19轮） |

**流水**: wallet_txn 类型: recharge / consume / refund / gift_card / referral_reward(分销返佣) / referral_level2(二级返佣) / points_exchange(积分兑换入账)，分页返回。

**充值**: `POST /api/wallet/recharge` 传 amount（元）创建充值单，返回充值单 hashid。`POST /api/wallet/recharge/{id}/pay` 发起微信支付，响应含 sign_params（同订单支付模式）；支付回调以 R 前缀的 out_trade_no 区分充值单与订单。

**余额支付**: 订单支付请求体传 `pay_channel: "balance"` 使用钱包余额；微信退款与余额退款均将金额回充至钱包余额。

**余额转账（第19轮）**: `POST /api/wallet/transfer` — 接收人 hashid 解码+存在性 404、转自己 422、金额 0.01-1000/笔 422（DECIMAL 比对禁 float）、余额不足 422、单日累计 5000 元 422。并发/幂等：Redis NX 锁 wallet_transfer:{from} 30s 串行化转出方 → 事务内双方钱包行按 user_id 升序 lockForUpdate（固定顺序防死锁）→ 扣转出方 + 增接收方 + WalletTxn 双流水（transfer_out/transfer_in 含 balance_after 快照）+ 转账记录 completed + 接收方站内通知 type='balance_received'（失败仅记日志）。client_token 可选：成功后 SETNX 24h 防重复提交（失败请求不落 token 可重试）。

---

### 8. 店长工作台接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/store-manager/overview` | 今日概览 (今日订单数/今日营收/进行中/技师数/核销数) |
| GET | `/api/store-manager/orders` | 门店订单列表 (?status=&page=&limit=) |
| GET | `/api/store-manager/technicians` | 技师列表（含今日排班） |
| GET | `/api/store-manager/revenue` | 近 7 天营收聚合 |

**store_id 隔离**: requireStoreId() 强制当前用户绑定门店（erik_user.store_id），无门店 403；所有查询按 store_id 过滤。

---

### 9. 成长等级接口（需JWT认证，第20轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/growth` | 当前成长概览（balance/等级/下一档差额/等级名称） |
| GET | `/api/growth/records` | 成长值流水分页 (?page=&limit=) |
| GET | `/api/growth/levels` | 档位列表（公开，无需登录） |

**成长值入账**: 签到 +10；提交评价 +20（追评不入账）；消费 floor(paid) 每 1 元 1 点（支付回调内复用状态复验幂等，重复回调不重复入账）。

### 10. 发票接口（需JWT认证，第20轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/invoices` | 申请发票 (order_id hashid/order_type: service=服务/points_exchange=积分兑换/order_type 默认 service；金额与抬头服务端带出，不可篡改) |
| GET | `/api/invoices` | 发票列表 (?status=&page=) |
| GET | `/api/invoices/{id}` | 发票详情（仅本人） |

**防重复**: uk_order_type(order_id, order_type) 唯一键，同一订单同类型重复申请 422（含 MySQL 1062 捕获兜底）。

### 11. 客服工单接口（需JWT认证，第20轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/tickets` | 提交工单 (title/content 必填) |
| GET | `/api/tickets` | 工单列表 (?status=open/closed&page=) |
| GET | `/api/tickets/{id}` | 工单详情（仅本人，他人 404） |
| POST | `/api/tickets/{id}/close` | 关闭工单（仅本人/仅 open；可选 rating 1-5 满意度打分，越界/非整数 422，未提供兼容 NULL） |

### 12. 预约月历接口（需JWT认证，第20轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/calendar/technician/{id}` | 月视图 (?month=YYYY-MM)：排班 time_slots 展开小时槽 + 已约排除 |
| GET | `/api/calendar/technician/{id}/day` | 日视图 (?date=YYYY-MM-DD)：当天可约/已约/不可约槽位明细 |

### 13. 发票抬头接口（需JWT认证，第21轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/invoice-titles` | 保存抬头 (title_type: personal/company；company 必须 tax_no；同用户同抬头重复 422；首条自动为默认) |
| GET | `/api/invoice-titles` | 抬头列表（默认置顶） |
| PUT | `/api/invoice-titles/{id}` | 编辑抬头（仅本人） |
| DELETE | `/api/invoice-titles/{id}` | 删除抬头（仅本人；删默认后自动指定最早一条） |
| POST | `/api/invoice-titles/{id}/default` | 设为默认（事务清零同用户其他行） |

**申请联动**: POST /api/invoices 支持可选 title_id —— 解析抬头自动带入 invoice_title/tax_no/title_type，无 title_id 时保留原手填路径。

### 14. 浏览足迹接口（需JWT认证，第21轮）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/browse-history` | 最近浏览服务（join 服务名称/封面/价格/原价，viewed_at 倒序，per_page 默认 15 上限 50） |
| DELETE | `/api/browse-history/{item_id}` | 删除单条（仅本人，非法/他人 404） |
| DELETE | `/api/browse-history` | 清空足迹（仅本人） |

**记录时机**: 服务详情接口访问成功后自动记录（未登录跳过；重复浏览只刷新 viewed_at 不重复插入）。

---

## 二、管理后台API (admin/ :8787)

请求头: `Authorization: Bearer <admin_token>`, `API-Version: v1`

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

**自动评定**: TierRatingService::evaluate 实时统计（erik_order completed 订单数 + 评价均分，四舍五入 1 位小数）回写 profile.order_count/rating，按 erik_technician_tier_config（min_orders/min_rating）从高到低匹配，无匹配归最低等级。仅升级不降级（降级影响佣金率与价格系数，由后台人工兜底；allowDowngrade=true 供人工重评）；幂等（等级一致只同步统计）；变更落 erik_technician_tier_log + 站内通知。触发点：WorkController::complete / ReviewController 评价写入 / ProfileController 查看资料懒判定。

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
