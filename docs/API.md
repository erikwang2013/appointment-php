# API 说明文档

## 概述

- **业务API** (service/): `http://localhost:8788` — 为小程序/APP提供业务接口
- **管理后台API** (admin/): `http://localhost:8787` — 为管理后台Flutter Web提供接口
- **认证方式**: Bearer Token (JWT), 请求头 `Authorization: Bearer <token>`
- **版本控制**: admin API通过请求头 `API-Version: v1` 控制版本
- **ID编码**: 所有请求/响应中的ID字段使用hashids编码，对外隐藏真实数据库ID
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

## 一、业务API (service/ :8788)

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

---

### 4. 订单接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/api/order` | 创建订单 (order_type/items/store_id/technician_id/service_time/coupon_id/remark) |
| GET | `/api/order/list` | 订单列表 (?status=&page=1) |
| GET | `/api/order/detail/{id}` | 订单详情 |
| POST | `/api/order/cancel/{id}` | 取消订单 (reason) |
| POST | `/api/order/pay/{id}` | 发起支付 |
| POST | `/api/order/refund/{id}` | 申请退款 |
| POST | `/api/order/verify/{id}` | 核销 (code: 二维码值) |

**订单状态**: pending(待支付) → paid(已支付) → confirmed(已确认) → serving(服务中) → completed(已完成)

**创建订单时**: Redis SETNX 锁定技师3分钟，退出页面或超时释放。

**退款规则**: 下单15min内或距开始>6h退100% / ≤6h退90% / 已开始退80% / 确认开始后不退。

---

### 5. 营销接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/marketing/coupons` | 优惠券列表 (?status=available/used/expired) |
| POST | `/api/marketing/coupons/receive` | 领取优惠券 (coupon_id) |
| GET | `/api/marketing/cards` | 会员卡列表 |
| POST | `/api/marketing/cards/buy` | 购买会员卡 (card_id) |
| GET | `/api/marketing/points` | 积分流水 |
| GET | `/api/marketing/gift-cards` | 礼品卡列表 |

---

### 6. 通知接口（需JWT认证）

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/notification` | 通知列表 (?type=order/system&page=1) |
| PUT | `/api/notification/read/{id}` | 标记已读 |
| PUT | `/api/notification/read-all` | 全部已读 |

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
