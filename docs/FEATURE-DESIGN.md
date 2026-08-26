# 功能设计
> **多语言**：[English](en/FEATURE-DESIGN.md) · [한국어](ko/FEATURE-DESIGN.md) · [Русский](ru/FEATURE-DESIGN.md) · [Deutsch](de/FEATURE-DESIGN.md) · [Français](fr/FEATURE-DESIGN.md) · [Español](es/FEATURE-DESIGN.md) · [Português](pt/FEATURE-DESIGN.md) · [हिन्दी](hi/FEATURE-DESIGN.md) · [العربية](ar/FEATURE-DESIGN.md) · [বাংলা](bn/FEATURE-DESIGN.md) · [Bahasa Indonesia](id/FEATURE-DESIGN.md) · [日本語](ja/FEATURE-DESIGN.md)

## 购买流程

### 服务预约流程（直接下单）

```
服务详情 → 确认订单(门店/技师/时间/优惠券/备注) → 阅读服务协议
    → 提交订单 → Redis锁技师3分钟 → 微信支付 → 支付成功
    → 通知用户+技师 → 服务时间到 → 技师确认开始
    → 服务完成 → 二维码核销 → 用户评价 → 订单完成
```

### 产品购买流程（购物车模式）

```
产品列表 → 加入购物车 → 购物车确认(改数量/删除)
    → 提交订单 → 支付 → 发货 → 收货 → 完成
```

## 订单状态机

```
pending(待支付) → paid(已支付) → confirmed(已确认)
    → serving(服务中) → completed(已完成) → reviewed(已评价)

pending → cancelled(已取消)
paid → cancelled
paid → refunding(退款中) → refunded(已退款)
```

## 技师锁定机制

用户进入确认订单页 → Redis SETNX 锁定3分钟。退出/超时释放。

```
SETNX lock:tech:123:2026-05-26-14:00 user_456 EX 180
 → 成功: 继续下单
 → 失败: 技师已被锁定
```

## 退款规则

| 条件 | 退款比例 |
|------|----------|
| 下单15分钟内 或 距开始>6小时 | 100% |
| 距开始≤6小时 | 90% |
| 已开始但未确认服务 | 80% |
| 服务确认开始后 | 0%（不予退款）|

## 折扣规则

| 类型 | 条件 | 折扣 | 叠加 |
|------|------|------|------|
| 低峰折扣 | 10-12点/17-18点/21:00后 | 9折 | 可与优惠券叠加 |
| 提前预约 | 提前30分钟以上 | 95折 | 不可与优惠券叠加 |

## 技师提现

- 每月20号可提现，T+1到账微信零钱
- 已核销未结算：3天自动确认
- 最低金额/保留金额/整百限制后台配置

### 提现流程

```
申请提现 → poster-php验证 → 后台审核(通过/驳回)
    → 完成提现 → 微信零钱到账 → 生成财务流水
```

### 收益类型

| 类型 | 说明 |
|------|------|
| commission | 服务提成 |
| bonus | 奖金(回头客/考勤) |
| penalty | 罚款(24h未写档案) |
| subsidy | 补贴 |
| attendance | 全勤奖励 |

### 回头客奖励

同技师30天内二次消费 → 记录奖金

### 会员档案

每单完成后24h内必须写档案，否则无提成

## 积分设计

- 消费获取、推荐获取（后台可配）
- 1:100兑换礼品卡（后台可配）
- 积分流水表记录每次变动+余额

## 会员卡设计

| 类型 | 计费 | 说明 |
|------|------|------|
| month | 按天 | 普通月卡 |
| vip | 按天 | VIP年卡 |
| times | 按次 | 次卡，可自由组合服务项目 |

次卡：购买时选服务组合(A×3+B×5)，每次消耗对应项目1次。用完→used_up，到期→expired。

## 身份切换

```
客户 → 切换技师 → 检查技师档案是否approved
    → 是: active_role=technician, 页面切换
    → 否: 引导入驻申请

技师 → 切换客户 → active_role=customer, 页面切换
```

## 新用户奖励

```
注册 → 生成推荐码 → 有推荐人→创建推广记录
    → 自动发新用户优惠券(Phase 5)
    → 推荐人获积分(被推荐人首单后)
```

## 支付设计（微信支付预留）

```
POST /api/order/pay/{id}
    → 创建支付记录 → 调用微信统一下单(预留WechatPayService)
    → 返回支付参数 → 前端调起支付
    → 微信回调 /api/wechat/notify → 验签 → 更新状态paid
    → 通知用户+技师
```
