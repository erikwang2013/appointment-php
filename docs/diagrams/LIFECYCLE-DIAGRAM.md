# 生命周期图

## 1. 订单生命周期（状态机）

```mermaid
stateDiagram-v2
    [*] --> pending: 用户提交订单

    pending --> paid: 支付成功<br/>(微信/余额/免费 三通道)

    pending --> cancelled: 超时取消(15min)<br/>用户主动取消

    paid --> confirmed: 技师确认接单<br/>回调原子消费<br/>优惠券扣减/次卡扣次
    paid --> cancelled: 用户取消<br/>(按退款规则)
    paid --> refunding: 用户申请退款
    paid --> aftersale: 申请售后<br/>(退款/换货)

    confirmed --> serving: 服务开始

    serving --> completed: 服务完成 + 核销<br/>次卡核销扣次

    serving --> refunding: 异常退款<br/>(退80%)

    completed --> reviewed: 用户评价
    completed --> aftersale: 申请售后<br/>(退款/换货)

    refunding --> refunded: 审核通过<br/>原路退回/余额回充<br/>优惠券归还 + 积分回扣
    refunding --> paid: 审核驳回

    aftersale --> refunded: 审核通过-退款<br/>沿用订单退款接口
    aftersale --> paid: 审核拒绝
    aftersale --> [*]: 审核通过-换货<br/>状态流转完成

    reviewed --> [*]
    cancelled --> [*]
    refunded --> [*]

    note right of pending: 锁定技师3分钟
    note right of refunding: 店长→财务 两级审批
```

## 2. 会员卡生命周期

```mermaid
stateDiagram-v2
    [*] --> active: 用户购买会员卡

    active --> used_up: 次卡次数用完

    active --> expired: 到期(月卡/VIP)

    active --> frozen: 违规冻结(后台操作)

    frozen --> active: 解冻

    used_up --> [*]
    expired --> [*]
```

## 3. 技师入驻生命周期

```mermaid
stateDiagram-v2
    [*] --> applied: 提交入驻申请

    applied --> approved: 后台审核通过
    applied --> rejected: 审核驳回

    rejected --> applied: 修改重新提交

    approved --> active: 首次登录技师端

    active --> suspended: 违规暂停
    suspended --> active: 恢复
    active --> banned: 永久封禁

    banned --> [*]
```

## 4. 优惠券生命周期

```mermaid
stateDiagram-v2
    [*] --> draft: 后台创建

    draft --> published: 上架发布

    published --> claimed: 用户领取

    claimed --> used: 下单使用
    claimed --> expired: 超过有效期

    published --> ended: 库存领完/到期下架

    used --> [*]
    expired --> [*]
    ended --> [*]
```

## 5. 技师提现生命周期

```mermaid
stateDiagram-v2
    [*] --> pending: 提交提现申请

    pending --> approved: 店长审核通过
    pending --> rejected: 审核驳回

    rejected --> [*]: 退回

    approved --> processing: 财务确认

    processing --> completed: 微信零钱到账(T+1)

    completed --> [*]
```

## 6. Token 认证生命周期

```mermaid
stateDiagram-v2
    [*] --> issued: 用户登录成功

    issued --> active: 携带Token请求API

    active --> refreshed: 即将过期 刷新Token

    refreshed --> active: 继续使用新Token

    active --> blacklisted: 主动登出<br/>修改密码<br/>并发超限(>3个)

    active --> expired: 7天未使用

    blacklisted --> [*]
    expired --> [*]

    note right of blacklisted: 加入JWT黑名单<br/>立即失效
```

## 7. 拼团活动生命周期

```mermaid
stateDiagram-v2
    [*] --> ongoing: 后台创建并上架

    ongoing --> full: 参与人数 ≥ min_people<br/>(满员锁定，拒绝新参与)

    ongoing --> closed: 到期未满员<br/>(惰性判定：show/join 时关闭)

    full --> closed: 到期

    ongoing --> joined: 用户参与 join<br/>(Redis NX 防超卖，重复参与 422)

    joined --> group_paid: 以拼团价下单并支付<br/>(拼团价=原价×discount_percent)

    joined --> cancelled: 活动关闭未成团<br/>(订单自动取消，释放技师锁)

    group_paid --> [*]: 正常订单生命周期
    cancelled --> [*]
    closed --> [*]

    note right of joined: 拼团订单禁用优惠券/次卡/积分叠加
    note right of closed: 已参与用户提示"未成团"
```
