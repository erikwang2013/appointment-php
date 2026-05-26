# 架构说明

## 系统总览

预约服务系统采用三端 + 双服务架构：

```
┌─────────────────────────────────────────────────────┐
│                    用户终端层                         │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ 微信小程序     │  │ Flutter APP   │                │
│  │ apps/wechat/  │  │ apps/flutter/ │                │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │     功能等价      │                         │
│         └────────┬─────────┘                         │
│                  │ 客户/技师 身份切换                   │
├──────────────────┼──────────────────────────────────┤
│              业务API层                                 │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │ service/ API  │  │ admin/ API    │                │
│  │ 端口 8788     │  │ 端口 8787     │                │
│  └──────┬───────┘  └──────┬───────┘                 │
│         │                  │                          │
│         └────────┬─────────┘                          │
│                  │ 共享 MySQL/Redis/ES                 │
├──────────────────┼──────────────────────────────────┤
│                  数据层                                 │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌──────────┐     │
│  │ MySQL  │ │ Redis  │ │  ES    │ │第三方服务 │     │
│  └────────┘ └────────┘ └────────┘ └──────────┘     │
└─────────────────────────────────────────────────────┘
```

## 项目组成

### service/ — 业务API服务

为微信小程序和Flutter APP提供全部业务接口。webman v2，端口8788。

**模块划分：**

| 模块 | 路径 | 认证 | 说明 |
|------|------|------|------|
| 公开API | `api/` | 无 | 登录/注册/验证码/微信回调 |
| 用户模块 | `user/` | JWT | 资料/地址/收藏/反馈/推广 |
| 技师模块 | `technician/` | JWT+技师 | 档案/排班/核销/会员/收益/提现 |
| 服务模块 | `service/` | 混合 | 分类/项目/搜索/门店 |
| 订单模块 | `order/` | JWT | 购物车/下单/支付/退款/核销/评价 |
| 营销模块 | `marketing/` | JWT | 优惠券/会员卡/积分/礼品卡 |
| 内容模块 | `content/` | 混合 | 轮播图/公告/通知 |
| LBS模块 | `lbs/` | 公开 | 城市/附近门店 |

### admin/ — 管理后台

PC管理后台。webman v2 + Flutter Web，端口8787。

**已有模块：** 认证、仪表盘、用户管理、角色权限、系统配置、操作日志、文件上传、安全防护

**扩展模块：** 技师管理、会员管理、门店管理、服务/产品管理、订单管理、优惠券、财务管理、内容管理、系统设置

### apps/ — 用户端前端

| 目录 | 技术 | 平台 |
|------|------|------|
| `apps/wechat/` | 原生微信小程序 | 微信 |
| `apps/flutter/` | Flutter 3.x + GetX + Dio | iOS + Android |

## 核心组件

### Snowflake ID

所有主键由 `erikwang2013/snowflake-php` 生成，BIGINT非自增，保证分布式全局唯一。

### Hashids

API请求/响应中的ID通过 `erikwang2013/hashids` 编码，对外暴露hash字符串。

### JWT认证

`erikwang2013/jwt-webman` Bearer Token，7天有效期，支持刷新和黑名单。

### 数据加密

- **API层**：`erikwang2013/encryption` 敏感数据加解密
- **DB层**：`erikwang2013/encryptable` trait 自动加解密字段

### 安全防护

- `erikwang2013/security-php`：31种攻击检测
- `erikwang2013/poster-php`：敏感操作随机验证
- 登录锁定：5次失败锁15分钟
- 并发限制：最多3个有效Token

### Elasticsearch

`erikwang2013/webman-scout` 模型自动同步ES，支持全文搜索。

## 中间件执行链

### service/ 中间件

```
公开API:  Cors → Security(31种检测) → RateLimit → Controller
用户API:  Cors → Security → RateLimit → Auth(JWT) → Controller
技师API:  Cors → Security → RateLimit → Auth → TechnicianAuth → Controller
支付回调: Cors → Security → Controller
```

### admin/ 中间件

```
公开API:  Cors → Security → RateLimit → Controller
管理API:  Cors → Security → RateLimit → AdminAuth(JWT) → AdminPermission(RBAC) → OperationLog → Controller
健康检查: Cors → Security → RateLimit → Controller
```

## 数据流

### 请求流

```
客户端 → Cors → Security → RateLimit → Auth(JWT) → [TechnicianAuth] → Controller
    → Model(encryptable加解密) → BaseController(hashids编码) → JSON响应
```

### 预约流程

```
浏览服务 → 选门店/技师/时间 → 提交订单 → Redis锁技师3分钟
    → 微信支付 → 通知技师 → 服务开始 → 服务完成 → 评价 → 订单完成
```

## 8操作来源端

`source` 字段记录操作来源：web / iPadOS / macOS / Windows / Linux / ios / android / harmonyOS
