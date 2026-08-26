# 使用说明
> **多语言**：[English](en/USAGE.md) · [한국어](ko/USAGE.md) · [Русский](ru/USAGE.md) · [Deutsch](de/USAGE.md) · [Français](fr/USAGE.md) · [Español](es/USAGE.md) · [Português](pt/USAGE.md) · [हिन्दी](hi/USAGE.md) · [العربية](ar/USAGE.md) · [বাংলা](bn/USAGE.md) · [Bahasa Indonesia](id/USAGE.md) · [日本語](ja/USAGE.md)

## 管理后台登录

默认管理员：`admin` / `admin123` | 地址：`http://localhost:8787`

> 首次登录后立即修改密码

---

## 系统配置流程

### 1. 基础设置
系统配置 → 填写平台名称/LOGO → 关于我们 → 客服电话/官网/邮箱 → 平台协议 → 编辑用户协议/隐私协议

### 2. 门店与服务
门店管理 → 新增门店(名称/地址/坐标/电话/时间) → 服务分类 → 创建分类 → 服务项目 → 新增服务(名称/价格/时长/规格) → 产品管理 → 新增商品/卡券

### 3. 技师入驻
技师APP端申请 → 管理后台「技师管理」审核 → 通过后技师设排班 → 可接收预约

### 4. 运营配置
轮播图 → 上传+设置跳转 | 公告 → 发布滚动公告 | 优惠券 → 创建新用户券/满减券 | 会员卡 → 月卡/VIP/次卡 | 佣金 → 设置技师提成比例

---

## 用户端流程

### 注册登录
微信搜索/扫码 → 手机号+验证码注册(推荐码可选) → 或微信一键登录 → 新用户自动获优惠券

### 预约服务
首页浏览分类 → 点击服务进详情 → 查看价格/评价 → 立即预约 → 选门店/技师/时间/优惠券 → 确认订单 → 微信支付 → 支付成功

### 订单管理
待支付: 完成支付 | 已支付: 等待服务 | 已完成: 评价(星级+文字+图片) | 退款: 自动计算退款比例

### 个人中心
订单/优惠券/会员卡/积分/收藏 | 推广中心: 获取推广二维码得积分 | 意见反馈: 文字+图片

---

## 技师端操作

### 切换身份
APP「我的」→ 切换技师 → 工作台

### 日常工作
- **排班设置**: 按日设可预约时间段
- **查看预约**: 今日已约订单列表
- **扫码核销**: 扫用户二维码核销次数
- **会员档案**: 每单24h内填写顾客档案(超时无提成)
- **考勤签到**: 签到/签退/卫生照片

### 收益
查看今日收入/在途资金/余额 → 每月20号提现 → T+1到账微信零钱

### 成长
学习培训课程 → 参加考核 → 通过升级技师等级(影响佣金率)

---

## API 接口

接口文档已独立维护，见 [API.md](API.md)（业务 API + 管理后台 API，含请求/响应示例与 OpenAPI 端点）。

---

## WebSocket

```
ws://localhost:8282
```

认证: `{"type":"auth","token":"<JWT>"}`

事件: `order_update` / `technician_online` / `system_notice`

---

## 推送配置

iOS(APNs): 配置 apns_key_id/team_id/bundle_id/.p8文件  
Android(FCM): 配置 fcm_server_key

APP注册设备: `POST /api/user/device/register {"platform":"ios","device_token":"..."}`

---

## 定时任务

| 任务 | 频率 | 说明 |
|------|------|------|
| 订单自动取消 | 30秒 | 待支付超30分钟 |
| 收益自动结算 | 3天 | 完成订单结算提成 |
| 优惠券过期 | 每天 | 标记expired |
| 会员卡过期 | 每天 | 标记expired |

---

## 退款规则

| 条件 | 比例 |
|------|------|
| 下单15分钟内或距开始>6h | 100% |
| 距开始≤6h | 90% |
| 已开始未确认 | 80% |
| 确认开始后 | 0% |

---

## 监控

```bash
GET /health          # 健康检查
GET /metrics         # Prometheus指标
GET /.well-known/security.txt  # 安全联系人
```

## 测试

```bash
admin/ && phpunit --bootstrap tests/bootstrap.php     # 60 tests
service/ && phpunit --configuration phpunit.xml        # 21 tests
```
