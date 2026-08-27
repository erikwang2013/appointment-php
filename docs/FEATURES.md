# 功能说明
> **多语言**：[English](en/FEATURES.md) · [한국어](ko/FEATURES.md) · [Русский](ru/FEATURES.md) · [Deutsch](de/FEATURES.md) · [Français](fr/FEATURES.md) · [Español](es/FEATURES.md) · [Português](pt/FEATURES.md) · [हिन्दी](hi/FEATURES.md) · [العربية](ar/FEATURES.md) · [বাংলা](bn/FEATURES.md) · [Bahasa Indonesia](id/FEATURES.md) · [日本語](ja/FEATURES.md)

> **项目状态**: 全部完成 ✅ | 109 控制器 | 103 模型 | 344 测试（service 240 / admin 104） | WebSocket | 支付回调 | 叫号 | 考核 | 社区

## 一、用户端（微信小程序 + Flutter APP）

用户端小程序与APP功能完全相同。统一账号支持客户/技师身份切换。

### 1. 认证

| 功能 | 说明 |
|------|------|
| 手机号注册 | 手机号+验证码+密码+确认密码，支持推荐码 |
| 密码登录 | 已注册手机号+密码 |
| 验证码登录 | 已注册手机号+验证码 |
| 微信登录 | 微信授权登录，首次需绑定手机号 |
| 游客模式 | 可浏览不可下单，下单需注册 |
| 忘记密码 | 验证码修改密码 |
| 用户协议/隐私协议 | 管理后台可编辑，注册时展示 |

### 2. 首页

| 功能 | 说明 |
|------|------|
| LBS定位 | 定位所在区域，展示该区域服务，支持切换城市 |
| 轮播图 | 自动轮播，管理后台设置跳转（网页/详情/无操作） |
| 公告 | 滚动播放，点击查看列表，管理后台添加 |
| 服务类别 | 图片/名称/价格/销量，点击进详情 |
| 新用户优惠券 | 注册自动获取 |

### 3. 服务项目

| 功能 | 说明 |
|------|------|
| 基础信息 | 图片/名称/价格/销量/规格/服务时长/项目详情 |
| 用户评价 | 评价内容展示，可查看更多 |
| 预约服务 | 进入确认订单页 |
| 门店选择 | 到店服务门店地址（导航）/营业时间/联系电话 |
| 技师选择 | 技师名字/头像/评分 |
| 服务时间 | 选择预约时间段 |
| 低峰9折 | 10-12点/17-18点/21:00之后 |
| 提前预约95折 | 提前30分钟，不可与优惠券叠加 |
| 优惠券 | 展示可用金额，使用/不使用 |
| 备注 | 服务需求备注（限字数） |
| 服务协议 | 提交前阅读确认 |

### 4. 产品搜索与购物车

| 功能 | 说明 |
|------|------|
| 商品搜索 | 名称搜索 |
| 类目筛选 | 按分类搜索 |
| 商品详情 | 可购买量/收藏/分享/加入购物车/立即购买 |
| 购物车 | 选择/删除/修改数量 |

### 5. 订单

| 功能 | 说明 |
|------|------|
| 全部订单 | 按状态Tab查看 |
| 待支付 | 查看/支付 |
| 待发货/自取 | 催发货/取消订单/查看 |
| 待收货 | 物流信息/确认收货 |
| 待评价 | 订单详情/文字+图片评价 |
| 已完成 | 订单信息查看 |
| 退款规则 | 下单15min内或>6h退100% / <6h退90% / 开始后退80% / 确认后不退 |

### 6. 技师（客户视角）

| 功能 | 说明 |
|------|------|
| 技师列表 | 距离近到远/头像/名字/订单数/评分/收藏/距离/可约时间/立即预约 |
| 技师详情 | 图片/名字/距离/订单/评价/收藏/可服务项目列表 |
| 技师入驻 | 填写信息申请成为技师，下载技师端APP |

### 7. 技师工作台（技师身份切换后）

| 功能 | 说明 |
|------|------|
| 今日概况 | 今日订单/收入总览 |
| 排班设置 | 按日设置可预约时间段 |
| 我的订单 | 已约未核销/已完成 |
| 扫码核销 | 扫用户二维码核销次数 |
| 会员管理 | 服务过的会员列表/耗课数据/次卡/档案编辑 |
| 收益管理 | 今日收入/结算中/钱包余额 |
| 在途资金 | 已核销未结算，3天自动确认 |
| 提现 | 每月20号，T+1到账微信零钱；管理端审核，金额≥500 两级审批（店长→财务）；申请时余额在途预留、审批转账前复核、并发审批防双打款（2026-08-26 加固） |
| 考勤 | 签到/签退/卫生照片上传 |
| 回头客奖励 | 30天内二次消费记录奖金 |
| 专业培训 | 视频课程/图文课程 |
| 今日任务 | WorkController today：实时获取今日待办 |
| 完成记录 | WorkController records：历史完成记录 |
| 开始/完成服务 | WorkController start/complete：行锁+状态机守卫+幂等，完成后自动写站内通知 |
| 小程序技师工作台 | tech-work 三 Tab：扫码核销/今日任务/完成记录 |

### 8. 个人中心

| 功能 | 说明 |
|------|------|
| 个人信息 | 头像/昵称/手机号 |
| 身份切换 | 客户 ↔ 技师 |
| 消息通知 | 站内通知（appointment_notification）；消息中心页：分页/下拉刷新/已读高亮/标记已读/全部已读 |
| 我的会员卡 | 月卡/VIP年卡/次卡（到期/次数/已用/剩余） |
| 我的积分 | 获取记录/可用积分/使用记录（1:100兑换礼品卡）；签到/消费返积分，退款按比例回扣，明细分页+type/source过滤 |
| 我的礼品卡 | 现金卡/实物礼品；cash 类型兑换直接充值到钱包 |
| 优惠券 | 已领取可用/已使用/已过期 |
| 我的收藏 | 收藏的服务项目 |
| 关注公众号 | 二维码弹窗，长按保存 |
| 用户推广 | 推广说明/二维码海报/推荐用户列表/积分奖励 |
| 意见反馈 | 文字+图片提交，24h回复 |
| 关于我们 | LOGO/介绍/客服电话/官网/邮箱 |

### 9. 设置

| 功能 | 说明 |
|------|------|
| 修改密码 | 当前密码+新密码+确认新密码 |
| 换绑手机 | 当前手机验证码+新手机验证码 |
| 用户协议 | 文本展示，后台可编辑 |
| 隐私协议 | 文本展示，后台可编辑 |
| 检测更新 | 版本号+更新 |
| 账号注销 | 注销说明+确认操作 |
| 退出登录 | 清除登录状态 |

### 10. 储值钱包（第6轮）

| 功能 | 说明 |
|------|------|
| 钱包余额 | GET /api/wallet 余额+流水（user_wallet/wallet_recharge/wallet_txn 表） |
| 充值 | POST /api/wallet/recharge 创建充值单；POST /api/wallet/recharge/{id}/pay 微信支付充值，回调使用 R 前缀单号 |
| 余额支付 | 订单支付渠道 pay_channel=balance |
| 退款回充 | 微信/余额退款自动回充余额（refundToBalance / creditRefundToWallet） |

### 11. 订阅消息（第6+8轮）

| 功能 | 说明 |
|------|------|
| 订阅场景 | 订单事件 3 场景：支付成功 / 退款到账 / 核销成功 |
| 幂等 | push_sent_at 标记防重复推送 |
| 降级 | 未配置订阅模板自动降级为站内通知 |

### 12. 次卡核销闭环（第8轮）

| 功能 | 说明 |
|------|------|
| 我的次卡 | GET /api/marketing/cards/my 实时计算 used_up/expired |
| 核销扣次 | POST /api/marketing/cards/use：Redis NX 幂等 + lockForUpdate 行锁，直建 completed 订单 + OrderItem + OrderPayment(pay_type='card') |

### 13. 优惠券抵扣（第9轮）

| 功能 | 说明 |
|------|------|
| 下单选券 | 下单可选传 user_coupon_id，PriceCalculator.applyCoupon 只读校验+算额 |
| 优惠类型 | fixed 固定金额 / percent 百分比，min_amount 满减门槛 |
| 消费与归还 | 支付成功 consume 置 used；退款 restoreCouponAndCard 幂等归还 |

### 14. 礼品卡（第9轮）

| 功能 | 说明 |
|------|------|
| 兑换 | redeem：cash 类型充值到钱包（行锁防双入账，WalletTxn type='gift_card'），gift 类型仅标记 |
| 我的礼品卡 | GET /api/marketing/gift-cards/my |

### 15. 积分体系（第9+10轮）

| 功能 | 说明 |
|------|------|
| 签到返积分 | CheckIn 每日签到 |
| 消费返积分 | 核销时 floor(paid×1)，order_id 幂等，balance 快照 |
| 退款回扣 | clawbackOrderPoints 按比例回扣（3 处接入） |
| 积分抵现 | 支付时传 use_points，100 积分=1 元（config app.points_rate），SUM 聚合校验余额，消费流水 source=points_offset 幂等 |
| 积分回补（第15轮） | 取消/退款归还 points_offset 积分：refundOffsetPoints 5 挂接点（doCancel 3 路径/doRefund 微信事务/creditRefundToWallet/completeOneRefundCompensation），source=points_refund 幂等 |
| 积分明细 | GET /api/marketing/points 分页 + type/source 过滤，type 统一为 earn |

### 16. 小程序下单链路（第10轮）

| 功能 | 说明 |
|------|------|
| 服务详情页 | service/detail |
| 确认订单页 | order/confirm：选券/门槛置灰/客户端预估金额 → POST /order → 微信/余额支付 |
| 页面规模 | 小程序现共 20 个页面 |

### 17. 用户侧三入口（第10轮）

| 功能 | 说明 |
|------|------|
| 收藏 | favorite 收藏页（user 页入口） |
| 推广 | referral：邀请码/链接复制/被推荐用户列表 |
| 反馈 | feedback 反馈表单 |

### 18. 订阅消息授权（第14轮）

| 功能 | 说明 |
|------|------|
| 订阅授权 | utils/subscribe.js 集中管理模板 ID（键名与服务端 appointment_system_config.wechat_app.template_ids 对齐） |
| 触发场景 | 预约成功/支付成功后手势回调内 wx.requestSubscribeMessage，未配置模板 ID 或用户拒绝均静默 |
| 服务端链路 | WechatTemplateMessageService 发送 + NotificationReminderService 预约前 2h~1h 提醒 + AutoCancelTimer 进程扫描 |

### 19. 售后退换货（第14轮）

| 功能 | 说明 |
|------|------|
| 申请售后 | POST /api/aftersales：type=refund/exchange，校验本人订单/paid+completed/同单去重 |
| 我的售后 | GET /api/aftersales 分页列表 + GET /api/aftersales/{id} 详情 |
| 审核流转 | 管理端 approve/reject（rejected 必填 remark）；approved 仅状态流转，退款沿用订单退款接口 |

### 20. 拼团/秒杀（第15轮）

> 2026-08 起 FLASH_SALE 通道下线：PromotionController::index 过滤 flash_sale、show/join 对其返回 400，秒杀统一走「43. 秒杀（第24轮）」通道；`Promotion::TYPE_FLASH_SALE` 常量保留兼容历史数据。本节及「27. 秒杀下单」为历史记录。

| 功能 | 说明 |
|------|------|
| 活动列表/详情 | GET /api/promotions + /api/promotions/{id}，type 过滤 group_buy/flash_sale |
| 参与 | POST /api/promotions/join/{id}：Redis NX 锁防超卖（flash_sale 以 max_people 为库存上限）、重复参与 422、group_buy 满员锁定、到期未满员惰性关闭（show/join 时 status 置 0） |
| 参与列表 | GET /api/promotions/{id}/participants |
| 状态修复 | PromotionParticipant 状态改整型常量 0/1/2/3（修复严格模式下 join 1366 损坏） |

### 21. 拼团成团下单（第16轮）

| 功能 | 说明 |
|------|------|
| 拼团价 | join 响应返回 discount_percent/original_price/group_price |
| 拼团下单 | POST /api/order 传 promotion_id：校验仅 group_buy/活动有效/调用者是参与者/未满员/服务匹配；拼团价=原价×discount_percent/100，禁用优惠券/次卡/积分叠加（422） |
| 订单标记 | appointment_order 新增 promotion_id/participant_id 列 + 索引 |
| 未成团处理 | 到期未满员→活动关闭+批量取消该活动 pending 订单（幂等）；pay() 懒判定已关闭则自动取消订单并释放技师锁 |

### 22. 分销返佣（第16轮）

| 功能 | 说明 |
|------|------|
| 发放规则 | 被推荐人首单 completed 后发放：金额=paid_amount×reward_rate（appointment_system_config referral.reward_rate 默认 0.05，非法回落常量），>0 才发 |
| 挂接点 | ReferralRewardService::handleOrderCompleted 挂接 WorkController::complete 事务内（serving→completed 唯一入口，核销 verify 只到 serving 不触发），失败整体回滚可重试 |
| 幂等 | appointment_user_referral 行锁 lockForUpdate + rewarded_at 判空 + 锁内首单复查（并发/重复调用只发一次） |
| 入账 | 钱包行锁累加 + WalletTxn type='referral_reward'（balance_after + 订单号 remark）；推荐记录写 reward_type/reward_amount/rewarded_at/first_order_at |
| 明细 | GET /api/user/referral/earnings 分页（被推荐人昵称/头像/订单号/金额/时间） |

### 23. 积分兑换商城（第16轮）

| 功能 | 说明 |
|------|------|
| 兑换商品 | appointment_points_exchange_goods：type=coupon/gift_card/wallet，points_cost/value（DECIMAL(25,2) 防雪崩 ID 精度丢失）/stock/status |
| 商品列表 | GET /api/marketing/points-exchange：上架商品 + 实时剩余库存 + 已兑数 |
| 兑换 | POST /api/marketing/points-exchange/{id}：Redis NX 锁 + 商品行锁防超兑；积分 SUM 校验（不足 422）+ UserPoints type='consume' source='exchange' 扣减；coupon 发券 / wallet 余额入账（WalletTxn points_exchange）/ gift_card 卡密返回 |
| 幂等 | uk_user_goods 唯一索引同用户同商品限一次 + 锁内复验 + 1062 兜底；兑换记录快照 appointment_user_points_exchange |

### 24. 预约改期（第17轮）

| 功能 | 说明 |
|------|------|
| 接口 | POST /api/order/reschedule/{id}：new_service_time（必填）+ reason（可选），同技师换时间 |
| 规则 | 仅本人订单（非本人 404）；仅 appointment 类型且状态 pending/paid/confirmed（其余 422）；距原服务开始 ≥ 6 小时（与全额退款窗口一致） |
| 并发防护 | B1 order_lock（与 pay/cancel/refund 同互斥族）→ 新时段技师锁 Redis SETNX EX 180（并发改期防超卖）→ 事务内行锁重读 + B2 排班冲突 DB 校验（排除本单） |
| 收尾 | 更新 service_time + 落 appointment_order_reschedule（含 reason）+ 释放原时段锁/新时段锁本单持有；失败事务回滚同时释放新时段锁 |
| 通知 | SCENE_RESCHEDULE 订阅消息（未配置模板降级站内通知「预约改期成功」）+ pushOrderUpdate |

### 25. 优惠券转赠（第17轮）

| 功能 | 说明 |
|------|------|
| 接口 | POST /api/marketing/coupons/transfer（user_coupon_id）生成 8 位去混淆字符唯一转赠码（uk_code 兜底，7 天有效）；POST /api/marketing/coupons/claim（code）领取；GET /api/marketing/coupons/transfers 发出(pending/claimed/expired)+收到(claimed) 分页 |
| 校验 | 券属于本人/available/券定义未过期/未被转赠过（422）；不可领取自己转赠的券、接收人非原持有人 |
| 防滥用 | Redis NX 锁 coupon_transfer_claim:{code}（30s）+ 事务内行锁复验防双花；uk_user_coupon 唯一索引限同一券转赠一次；被转赠券不可再转（新券无转赠记录自然拦截）；懒判定过期置 expired + 恢复原券 available |
| 领取 | 事务内原券置 used + 生成新 UserCoupon 绑定接收人（coupon_id 不变即有效期不变）+ 转赠记录置 claimed |

### 26. 积分过期（第17轮）

| 功能 | 说明 |
|------|------|
| 有效期 | appointment_user_points.expires_at 列；所有 earn（签到/消费返/回补）落库填 expires_at = now + points.expiry_days（默认 365，≤0 永不过期）；consume/use 行为空 |
| 过期执行 | PointsExpiryTimer 定时进程每 60s 游标扫描（100/批）expires_at < now 的 earn 行 → 写 type=expire 负值扣减行（source=expiry + order_id 溯源原流水）→ 按用户聚合站内通知「您有 X 积分已过期」 |
| 幂等 | ① expire 行 order_id 指向原 earn 流水，事务内对原行 lockForUpdate + exists 复验（并发进程在行锁上串行）② id 游标分页 ③ 通知仅在实际扣减轮次产生 |
| 口径 | 可用余额 SUM 聚合含 expire 负值行；过期积分不可再抵现/兑换 |

### 27. 秒杀下单（第18轮，已下线）

> 已被第24轮 `/api/seckill` 通道取代（store() 促销分支仅剩拼团），见「43. 秒杀」。

| 功能 | 说明 |
|------|------|
| 接口 | POST /api/order 传 promotion_id（flash_sale 类型）：秒杀价 = round(total × (100 − discount_percent)/100, 2)，与 PromotionController 秒杀价口径一致 |
| 校验 | 类型白名单 [group_buy, flash_sale]（其余 422）；活动进行中；调用者是参与者；订单服务与活动匹配；售罄 participants_count ≥ max_people 422「已抢光」；禁用优惠券/次卡/积分叠加 422 |
| 过期 | pay() 懒判定 isFlashSaleClosed（同 isGroupBuyClosed 模式）：秒杀过期 → 活动置 0 + 批量取消该活动 pending 订单 + 本单自动取消 + 释放技师锁 422 |

### 28. 服务提醒 + 到期提醒（第18轮）

| 功能 | 说明 |
|------|------|
| 服务开始前提醒 | ServiceReminderTimer 60s 扫描 service_time ∈ [now+1h, now+1h+60s)、status confirmed/serving、appointment 类型订单 → 站内通知（type='service_reminder'，含服务/技师/门店/时间）+ SCENE_REMINDER 订阅消息 |
| 到期提醒 | ExpiryReminderTimer 6h 扫描 end_at ∈ (now, now+3d+6h]：active 会员卡（type='card_expiry'）+ available 优惠券（type='coupon_expiry'，whereHas 关联券定义 end_at）+ SCENE_EXPIRY 订阅消息 |
| 幂等 | 均 id 游标 100/批 + 事务内行锁复验 + 通知查重（order_id 列记来源 id/订单 id 作防重键）；订阅消息推送成功才写 push_sent_at，失败下轮重试 |
| 降级 | 模板未配置（WECHAT_SUBSCRIBE_TEMPLATE_REMINDER / _EXPIRY）自动降级仅站内通知 |

### 29. 技师回复评价（第18轮）

| 功能 | 说明 |
|------|------|
| 接口 | POST /api/technician/review/reply/{order_id}（技师身份中间件）：评价不存在/非本人统一 404；已有回复 422（幂等拒绝不覆盖）；空回复 422 |
| 回复后 | 站内通知用户（type='review_reply'，非阻塞 try/catch + Log） |
| 数据 | appointment_order_review 幂等补 replied_at 列（reply 列建表已有）；管理端评价 list/show 经 decorate()->toArray() 透出 reply/replied_at |

### 30. 充值到账通知（第18轮）

| 功能 | 说明 |
|------|------|
| 接口 | 微信充值回调（R 前缀单号）handleRechargeNotify 事务内：WalletTxn 之后写站内通知 type='wallet_recharge'，「您已成功充值 ¥X.XX」（金额元，number_format 2 位） |
| 幂等 | 复用现有回调幂等（充值单行 lockForUpdate + status 复验，仅首次 pending→paid 走到通知）；通知与状态变更同事务原子提交，无 crash 间隙；验签失败/单不存在/金额不符不写通知 |
| 容错 | 通知写入 try/catch，失败仅记 warning 日志不阻塞主流程 |

### 31. 余额转账（第19轮）

| 功能 | 说明 |
|------|------|
| 接口 | POST /api/wallet/transfer：接收人 hashid 解码+存在性 404、转自己 422、金额 0.01-1000/笔 422（DECIMAL 比对禁 float）、余额不足 422、单日累计 5000 元 422 |
| 并发/幂等 | Redis NX 锁 wallet_transfer:{from} 30s 串行化转出方；事务内按双方 user_id 升序 lockForUpdate 钱包行（固定顺序防死锁）；client_token 成功后 SETNX 24h 防重复提交（失败请求不落 token 可重试） |
| 入账 | 扣转出方 + 增接收方 + WalletTxn 双流水（transfer_out/transfer_in 含 balance_after 快照）+ 转账记录 completed + 接收方站内通知 type='balance_received'（失败仅记日志） |
| 记录 | GET /api/wallet/transfers（direction=out/in 分页）+ GET /transfers/{id}（仅双方可见 404） |

### 32. 积分转赠（第19轮）

| 功能 | 说明 |
|------|------|
| 接口 | POST /api/user/points/transfer：接收人存在 404、转自己 422、点数 1-10000 422、余额 SUM 聚合不足 422、单日累计 10000 限额 422 |
| 并发/幂等 | Redis NX 锁 points_transfer:{user} 30s；事务内双方最后一条流水 lockForUpdate（user_id 升序防互转死锁）+ 锁内复验余额/限额/接收人 |
| 流水规范 | 发送方 type=consume source=points_transfer 负值（balance=上条快照-本次，与 points_offset/exchange 同口径）；接收方 type=earn source=points_transfer 正值含 expires_at（PointsExpiryTimer 可正常过期）；事务内写转赠记录，commit 后站内通知接收方 type='points_received' |
| 记录 | GET /api/user/points/transfers（direction=sent/received 分页，对方昵称） |

### 33. 评价追评 + 提交路由补全（第19轮）

| 功能 | 说明 |
|------|------|
| 追评 | POST /api/order/review/{order_id}/append：评价不存在/非本人统一 404、非 completed 422、重复追评 422（append_content/append_at 任一非空即拒）、空内容 422；成功写 append_content/append_images(JSON)/append_at + 技师站内通知 type='review_append' |
| 提交评价 | 补注册 POST /api/order/review/{order_id}（ReviewController::store 原无路由不可达）；顺带修复潜伏 TypeError：findByOrderId 收到 int 违反 string 签名（对照 append 的 (string) 转换），补注册即暴露调用即 500 |
| 数据 | appointment_order_review 增 append_content TEXT/append_images JSON/append_at DATETIME 三列（幂等迁移）；响应透出 append 字段 |

### 34. 用户端物流跟踪（第19轮）

| 功能 | 说明 |
|------|------|
| 接口 | GET /api/order/logistics/{id}：仅本人 product 订单可查（非本人/非商品/未发货统一 404） |
| 数据 | 读取 order.remark JSON（shipping_company/tracking_no/shipped_at，由 admin MallOrderController::ship() 发货时写入）；parseShippingInfo/parseReceiver 双解析兜底旧格式 |
| 脱敏 | 收货人手机号 maskPhone（138****5678），防泄露 |

### 35. 消息偏好设置（第19轮）

| 功能 | 说明 |
|------|------|
| 数据 | appointment_user_notify_setting 表（user_id+type 复合唯一键 uk_user_type，缺省行=默认开）；5 类：service_reminder 服务提醒 / card_expiry 到期提醒（卡+券统一伞形）/ points_expiry 积分过期 / marketing 营销（预留）/ system 系统（不可关，PUT 强制为 1） |
| 接口 | GET /api/user/notify-settings 返回 5 类全量开关；PUT 批量 upsert 不产生重复行 |
| 门控 | NotificationReminderService::notifySettingEnabled 挂接 3 定时器进程（ServiceReminderTimer/ExpiryReminderTimer 卡+券/PointsExpiryTimer，定时器直插 appointment_notification 表不走服务写入路径故各自加同款门控）+ 订阅事件（sendSubscribeForOrderEvent/Notification 场景映射 PAY/REFUND/VERIFIED/RESCHEDULE→system 恒发，REMINDER→service_reminder，EXPIRY→card_expiry）；类型关闭时站内通知与订阅消息一并跳过 |

---

## 二、管理后台（PC Web）

Flutter Web 单页应用，共 21 个页面：dashboard/用户/角色/配置/日志/核销/排班/服务/技师/订单/优惠券/会员/次卡/公告/FAQ/提现/评价/报表/个人中心/门店工作台。

### 1. 首页仪表盘

- 实时统计：用户数/订单总数/技师数/服务订单数
- 折线图：订单量趋势/金额趋势/新增用户/活跃度
- 快速导航：待处理模块按钮
- 站内消息：新订单通知/退款通知

### 2. 技师管理

- 技师列表：UID/手机号/姓名/归属地/注册时间搜索
- 列表展示：编号/UID/手机号/昵称/推荐人/状态/学员数/业绩/账号状态/注册时间/最后登录/归属地
- 操作：导出/修改上级/查看下级/修改密码手机号/排班管理/技术服务项设置/课程进度查看
- 新增：姓名/性别/手机号/身份证/身份证照片
- 审核入驻申请

### 3. 用户管理

- 会员列表：名称/手机号/头像/等级/消费金额
- 搜索：UID/手机号/昵称/注册时间
- 操作：详情/修改上级/查看下级/修改密码手机号/设置会员等级

### 4. 门店管理

- 门店列表：启禁用/删除
- 新增门店：名称/地址/坐标/电话/营业时间/图片

### 5. 服务管理

- 服务列表：名称/分类搜索；编号/名称/类型/折扣/最低价/销量/封面/序号/状态/时间
- 操作：新增/修改/删除/卡项设计
- 产品列表：类型/名称/折扣/最低价/销量/库存/封面/序号/状态/时间

### 6. 商城管理

- 商城订单：明细/发货/物流/打印
- 售后订单：查看/审核/打印
- 评价管理：查看/审核（show/hide）/删除（ReviewController index/show/audit/destroy）
- 支付流水
- 销售统计

### 7. 订单管理

- 待使用订单：多条件搜索
- 操作：详情/平台取消/确认完成

### 8. 优惠券活动

- 列表：序号/图片/类型/名称/上下架/总数/剩余/管理员/时间/结束日期
- 操作：新增/修改/删除

### 9. 财务管理

- 订单分账：搜索/详情
- 技师提现：WithdrawalController 审核；金额≥500 两级审批（店长 store_approved_at → 财务 finance_approved_at）；状态机 pending→approved→completed（rejected/failed）
- 佣金设置：修改佣金率/结算周期/奖罚/余额
- 收支流水
- 提现账号管理
- 提现限制配置

### 10. 内容管理

- 轮播图CRUD
- 关于我们设置
- 朋友圈动态审核
- 常见问题CRUD
- 意见反馈处理
- 平台公告CRUD

### 11. 设置

- 平台协议编辑（用户协议/隐私协议/服务协议）
- 技师统一佣金设置
- 系统消息模板（含小程序订阅消息模板配置，未配置自动降级站内通知）
- 子账号权限管理（店长可发优惠券+排班）

### 12. 扩展功能

- 卡项设计：项目+产品组合/手工费/提成设置
- 系统监控：CPU/内存/磁盘/Redis/MySQL/队列实时看板
- IP黑名单：security-php攻击记录可视化+手动封禁
- 数据库备份：Web界面备份/下载/恢复
- 客户画像：360视图/消费偏好/分层营销
- 批量推送：模板消息/分段群发
- 退款审核流：两级审批(店长→财务)
- 技师等级：junior/senior/expert自动评定
- 定时任务：自动取消/结算/过期处理
- 短信配置：阿里云/腾讯云多通道管理
- 存储配置：本地/OSS/COS/CDN
- 报表增强：自定义字段/定时邮件报表
- 排班导出：Excel导出预约记录/出勤列表
- 技师性别限制：特定项目性别控制
- 技师培训：课程管理/学习进度追踪
- 店长账号：store_id数据隔离+专属权限

### 13. 数据报表（第7轮）

- ReportController 3 端点：订单统计 / 技师业绩 / 门店分布
- Redis 缓存 svc:admin_report:{type}:{start}:{end}，TTL 300

### 14. 会员卡管理（第10轮）

- appointment_user.member_level 会员等级列（迁移 000008）
- MemberCardController 完整 CRUD（权限 365-369）：GET/POST/PUT/DELETE /admin/member-cards
- Flutter 会员卡定义管理页

### 15. 售后管理（第14轮）

- appointment_order_aftersale 表（迁移 000009）：type=refund/exchange，status=pending/approved/rejected/completed
- AftersaleController：GET /admin/aftersales（分页+status/uid/order_no 筛选）+ POST /admin/aftersales/{id}/review（approve/reject+remark）
- Flutter 售后管理页（列表+审核对话框，权限 370/371），布局已注册

### 16. 店长工作台（第15轮）

- service /api/store-manager：overview（今日订单/营收/进行中/技师数/核销数）+ orders（分页+状态筛选）+ technicians（含今日排班）+ revenue（近 7 天聚合），requireStoreId() 强制 store_id 隔离（无门店 403）
- admin StoreController::workbenchOverview（GET /admin/stores/workbench-overview?store_id=，口径与 service 一致）+ AppointmentOrderController 订单列表 store_id 筛选（hashid 解码）
- Flutter 门店工作台页：门店下拉 + 状态筛选 + 5 张概览卡片 + 订单 DataTable + 分页（权限 372）

### 17. 积分兑换商品（第16轮）

- PointsExchangeGoodsController：GET/POST/PUT/DELETE /admin/points-exchange-goods + POST {id}/toggle-status（上下架）+ GET {id}/exchanges（兑换记录，含手机号+result JSON 解析）
- 迁移 000012（两表）+ 000013（权限 373-378）已应用

### 18. 返佣记录（第16轮）

- ReferralRewardController：GET /admin/referral-rewards（仅 rewarded_at 非空记录，分页 + keyword 筛选推荐人/被推荐人昵称或手机号，hashid 编码，权限 379）

### 19. 技师等级自动评定（第17轮）

- TierRatingService::evaluate(technicianId, allowDowngrade=false)：实时统计 appointment_order completed 订单数 + appointment_order_review 均分（四舍五入 1 位小数）回写 profile.order_count/rating，按 appointment_technician_tier_config（min_orders/min_rating）从高到低匹配，无匹配归最低等级
- 升降级规则：仅升级不降级（等级绑定佣金率与价格系数，自动降级影响技师收入易引发纠纷，下滑由 admin 手动兜底）；allowDowngrade=true（后台人工重评场景）才执行降级，降级同样落日志 + 通知
- 幂等：应得等级与 profile.tier_id 一致时只同步统计、不写日志不发通知
- 日志：变更写 appointment_technician_tier_log（id/technician_id/old_tier_id/new_tier_id/reason/created_at）+ 站内通知（type='tier'）
- 触发点：WorkController::complete / ReviewController 评价写入 / ProfileController 查看资料懒判定
- 管理端：TechnicianTierController 保持手动配置能力；GET /admin/technician-tiers/logs 分页查看变更日志（join 技师姓名与新旧等级名，ID hashid 编码，权限 380）

### 20. 评价回复查看（第18轮）

- ReviewController 新增 reply()：GET /admin/reviews/{id}/reply 回复详情（decodeId → find → 404 → decorate 输出，未回复时 reply=''，reply/replied_at 经 toArray 透出）
- 路由为静态路由（位于 audit 前，先于 resource 定义）；权限种子 id 381（slug 'get.admin/reviews/{id}/reply'，type 3，超管角色幂等关联）
- 权限点：381

### 21. 预约月历（第20轮）

- CalendarController 月/日视图：GET /api/calendar/technician/{id}（月视图）+ /day（日视图）
- 数据源：technician_schedule.time_slots JSON 按星期展开小时槽，appointment_order 该日已约时段排除（status ∈ pending/paid/confirmed/serving），剩余可约槽位输出
- 用途：门店排班可视化选时，前端按天横向滚动 + 时间格点选

### 22. 用户成长等级（第20轮）

- appointment_user_growth（流水）+ appointment_growth_level（档位种子 5 级：青铜0/白银100/黄金500/铂金2000/钻石5000）
- 成长值入账点：签到 +10（CheckInController）；提交评价 +20（ReviewController::store，追评不入账）；消费 floor(paid) 每 1 元 1 点（WechatPayService::markOrderPaid，复用既有支付状态复验天然幂等，重复回调不重复入账）
- 接口：GET /api/growth（当前等级概览：balance/level/下一档差额）；GET /api/growth/records（流水分页）；GET /api/growth/levels（公开档位列表，无需登录）
- 失败策略：任一入账点 try/catch 记日志，不影响主流程

### 23. 电子发票（第20轮）

- appointment_invoice：uk_order_type(order_id,order_type) 防同一订单重复申请（重复申请 422，含 MySQL 1062 捕获兜底）；idx_user_created/idx_status
- 用户端：POST /api/invoices（申请，金额/标题服务端从订单带出，不可篡改）；GET /api/invoices（列表）；GET /api/invoices/{id}（详情）
- 管理端：InvoiceController issue（开票：写 invoice_no + status=issued + issued_at）/ reject（驳回：status=rejected + reject_reason），权限 382 列表/383 开票/384 驳回
- 状态机：pending → issued / rejected

### 24. 客服工单（第20轮）

- appointment_ticket：用户提交工单（title/content），后台回复追加（reply_content/replied_at），用户可关闭（closed_at）
- 用户端：POST /api/tickets（提交）；GET /api/tickets（列表）；GET /api/tickets/{id}（详情，仅本人）；POST /api/tickets/{id}/close（关闭）
- 管理端：TicketController index（列表）/ reply（回复），静态路由先于 resource 定义避免 {id} shadow；权限 385 工单回复/387 工单列表查看
- 状态机：open → replied（回复后回 open 可再回）/ closed

### 25. 多级分销-二级返佣（第20轮）

- ReferralRewardService::payLevel2Reward(paidAmount, orderId)：订单支付成功后，查一级推荐人的推荐人（二级推荐关系），发 paid×level2_rate（系统配置 referral.level2_rate，默认 0.02）
- 幂等：事务内行锁 + uk_order_referred(order_id, level2_user_id) 唯一键，重复支付回调/并发不重复发放；try/catch 失败仅记日志不影响支付主流程
- 入账：WalletTxn type='referral_level2'（TYPE_REFERRAL_LEVEL2 常量）+ 钱包余额累加
- 管理端：ReferralLevel2Controller index 分页记录（权限 386），join 两级用户昵称

### 26. 成长等级权益落地（第21轮）

- GrowthLevel.benefits JSON 空壳落地：迁移种子 5 档（青铜 {"discount_rate":1.0,"points_multiplier":1.0}、白银 0.98/1.1、黄金 0.95/1.2、铂金 0.92/1.3、钻石 0.9/1.5）
- 等级折扣：OrderController::store applyGrowthDiscount() —— 仅标准订单（promotion_id 为空，拼团/秒杀禁用叠加）；顺序：券/次卡优惠后应付金额 × discount_rate；折扣额并入 discount_amount，订单备注追加「等级折扣：白银9.8折，优惠¥2.00」可追溯；最低价保护：折后实付 ≥0.01 元（分制 ≥100），不足则折扣截断为 0
- 积分倍率：WechatPayService::markOrderPaid 成长值由 floor(paid) 改 floor(paid × points_multiplier)，倍率按支付时点等级取档（入账前累计，本单不抬级）；R20 的 try/catch 挂接点完整保留
- 查询复用：GrowthLevel::levelForGrowth() 按累计成长值取档，供下单/支付复用；GET /api/growth 已返回 benefits 与 next_gap（R20 实现，无需改）

### 27. 发票抬头管理（第21轮）

- appointment_invoice_title（uk_user_title(user_id, title_type, invoice_title) 防重复 + idx_user_default）
- 接口：POST /api/invoice-titles（保存，company 必须 tax_no，重复 422）；GET（列表，默认置顶）；PUT /{id}（编辑，仅本人）；DELETE /{id}（删除，仅本人）；POST /{id}/default（设默认，事务清零同用户其他行）
- 默认规则：首条保存自动为默认；删除默认后自动指定最早一条
- 申请联动：InvoiceController::store 可选 title_id 解析抬头带入 invoice_title/tax_no/title_type，无 title_id 时保留原手填路径；uk_order_type 防重逻辑未动

### 28. 工单满意度（第21轮）

- appointment_ticket 加 rating TINYINT NULL + rated_at DATETIME NULL（迁移 000303）
- 关闭打分：TicketController::close() 支持可选 rating 1-5（filter_var 整数校验，越界/非整数 422；提供则写 rating+rated_at，未提供保持 NULL 兼容旧客户端；只关 open 工单规则保留）
- 后台统计：GET /admin/tickets/satisfaction（静态路由先于 resource 避免 {id} shadow）返回 total/rated_count/unrated_count/average（1 位小数）/distribution（1-5 星各数量，缺星补 0）；权限 388

### 29. 评价图片审核（第21轮）

- admin ReviewAuditController（新建，不动现有 ReviewController）：GET /admin/review-audit 带图评价列表（JSON_LENGTH(images)>0 过滤 + leftJoin 用户昵称与技师名 + status 筛选 + hashid 编码）；POST /{id}/hide 隐藏；POST /{id}/restore 恢复
- 状态机：hide 仅 visible 可隐藏、restore 仅 hidden 可恢复（双向 422）；OrderReview 状态为整数体系（STATUS_HIDDEN=0/STATUS_VISIBLE=1）
- 生效链路：用户端技师评价列表已按 status 过滤 → 隐藏后自动不可见
- 权限：389 列表 / 390 隐藏 / 391 恢复

### 30. 用户浏览足迹（第21轮）

- appointment_browse_history（uk_user_item(user_id, item_id) 唯一，重复浏览只刷 viewed_at 不重复插入；idx_user_viewed 排序）
- 记录挂接：ServiceController::detail() 成功后记录（try/catch + Log::warning 不影响主流程；公开路由无 JWT，user_id 判空跳过匿名）
- 接口：GET /api/browse-history（join appointment_service 名称/封面/价格/原价，viewed_at 倒序，per_page 默认 15 上限 50，item_id hashid）；DELETE /{item_id}（仅本人，非法/他人 404）；DELETE /（清空仅本人）

### 31. 满减营销（第22轮）

- appointment_full_reduction_activity（threshold/reduction/title/status/start_at/end_at + idx_status_status_time）
- 下单叠加：仅标准订单（拼团/秒杀跳过），以券/次卡抵扣后应付金额判门槛，顺序 **券/次卡 → 满减 → 等级折扣**；取减免额最大活动；优惠额并入 discount_amount + 备注「满减：满X减Y」；满减后实付下限 0.01 元（分制）
- 用户端 GET /api/full-reduction-activities（公开，生效中按减免额降序）
- admin FullReductionController：CRUD + toggle-status 上下架（destroy 带 confirmPassword）
- 权限：396 列表 / 397 新增 / 398 编辑 / 399 上下架 / 400 删除（一条权限记录仅对应一个 method.path slug，5 路由拆 5 条）

### 32. 我的预约 ICS 导出（第22轮）

- IcsController GET /api/order/ics：90 天内 pending/paid/confirmed/serving 订单导出 iCal（RFC5545），仅本人
- VEVENT：UID=订单ID、DTSTAMP(UTC)、TZID=Asia/Shanghai、默认时长 1h、摘要「预约：服务名」（缺失退化「预约」）、描述技师/门店/地址（缺失跳过）、LOCATION；文本转义（\, \; \\ \n）+ 75 字节行折叠
- 无订单返回合法空日历（`BEGIN:VCALENDAR` 骨架）

### 33. 技师考勤（第22轮）

- appointment_technician_attendance（date/check_in_at/check_out_at/status + uk_technician_date 唯一索引防并发重复打卡）
- 技师端（TechnicianAuth）：check-in 当日重复 422；check-out 未上班/已下班 422 + 行锁；>10:00 标记迟到；GET 当月列表 + 出勤天数/总工时/平均工时（?month=YYYY-MM 非法 422）
- admin：GET /admin/attendance（date+技师名筛选、join real_name、hashid）+ /stats（按技师分组统计）
- 权限：392 列表 / 393 统计

### 34. APP 推送服务（第22轮）

- AppPushService（config group=push：enabled 默认 0 / provider jpush/getui/placeholder）：未启用静默降级仅日志；启用构造平台/标题/内容/payload 结构记 Log + 写 appointment_push_log（status=sent）；厂商 SDK 对接留 TODO（无凭据不实际发送）
- 接入 5 处事件：支付成功（WechatPayService::markOrderPaid）、自动退款（autoRefundCancelledOrder）、手动退款（doRefund/refundToBalance）、退款补偿（completeOneRefundCompensation）、服务开始提醒（ServiceReminderTimer）；全部 try/catch 不阻断主流程
- appointment_push_log（user_id/title/content/payload JSON/status/provider + idx_user）

### 35. 微信官方分账（第22轮）

- WechatProfitSharingService（config group=profit_sharing：enabled/receiver_ratio，凭据复用 wechat_pay）：未启用 disabled 降级仅日志不落库；启用→金额校验（>0 且 ≤paid，实付×0.7 默认）+ 幂等（同单 pending/success 跳过）→ 落 pending 记录 → 构造「请求单次分账」结构（无凭据不执行 HTTP，请求内容记日志，记录保持 pending）；HTTP 隔离私有 doRequest 可测试
- WechatPayService::markOrderPaid 提交后挂接 requestSharing（try/catch 失败仅日志）
- appointment_profit_sharing（uk_sharing_no 唯一 + idx_order）；admin GET /admin/profit-sharing 列表（join 订单号/技师昵称，状态/订单号/技师名筛选）
- 权限：394

### 36. 隐私合规（第22轮）

- GET /api/privacy/data：数据导出（personal/orders/points/wallet_txns/reviews/addresses/invoices 分组；日志只记脱敏手机号+条数）
- 注销闭环：close-request（余额非 0 / 未完成订单 / 进行中工单 422 → close_status=1）→ close-cancel（1→0）→ close-confirm（满 72h → close_status=2 + close_at + phone/nickname 匿名化 user{id} + status=0）
- appointment_user 加 close_status/close_requested_at/close_at（幂等 ALTER 迁移）；AuthController login/loginByCode 对 close_status=2 返回 403「账号已注销」

### 37. 用户健康档案（第23轮）

- GET/PUT/DELETE /api/health-profile：一人一份（uk_user 唯一索引），upsert 只更新提供的字段
- allergies/health_notes 上限 500 字，preferred_technician_id 校验存在性，响应 hashid 编码
- 迁移 000504_user_health_profile；HealthProfileTest 6 tests

### 38. 钱包支付密码（第23轮）

- POST /api/wallet/pay-password/{set,verify,check}：6 位数字校验，password_hash 存储 + pay_password_set_at
- 已设置时修改需旧密码 422；verify 仅校验不落库；check 返回是否已设置
- 迁移 000502（INFORMATION_SCHEMA 幂等 ALTER 两列）；WalletPayPasswordTest 7 tests

### 39. 技师批量排班（第23轮）

- POST /api/technician/schedule/batch：日期段 ≤7 天 + weekdays 过滤，已有排班的天跳过
- 单条设置同样启用时间段重叠检测（422「与已有排班时间冲突：HH:MM-HH:MM」）
- ScheduleConflictTest 5 tests

### 40. 订单状态时间线（第23轮）

- GET /api/order/{id}/timeline：仅本人可查（他人 404），倒序返回；admin 订单详情并入 timeline 数组
- OrderStatusLog::record() 静态埋点 8 类变更：提交/支付/取消/确认/退款申请/退款通过/服务开始/服务完成/超时自动取消/后台操作（operator=admin）
- 支付回调 markOrderPaid 为单一消费点；record() 内部 try/catch + Log::warning 绝不阻塞主流程
- 迁移 000501_order_status_log；OrderTimelineTest 4 tests

### 41. 积分幸运转盘（第23轮）

- GET /api/wheel/prizes（隐藏 weight/stock）；POST /api/wheel/spin：Redis NX + 行锁防并发，random_int 权重抽取，client_token 幂等
- 奖品落账：积分→earn 流水（含过期时间，可被 PointsExpiryTimer 正常过期）、余额→lockForUpdate、优惠券→pending 人工发放、无奖→lose
- GET /api/wheel/records 我的记录分页；admin /admin/lucky-wheel CRUD + 上下架 + 记录（权限 401-406）
- 迁移 000503（appointment_lucky_wheel + appointment_wheel_record + w60/w40 演示种子）+ 000505（权限种子）；LuckyWheelTest admin 3 + service 6 tests

### 42. 游客模式（第24轮）

- GET /api/guest/{home,services,services/{id},stores,technicians}：无需认证（仅 ApiVersion 中间件）的未登录浏览入口
- home 聚合轮播图/公告/服务分类/热门服务，Redis 缓存 svc:guest:home 300s；services 支持分类筛选 + newest/sales/price 排序（page/per_page≤50）；technicians 仅审核通过、可 service_id 筛选、评分降序
- GuestControllerTest 覆盖

### 43. 秒杀（第24轮）

- appointment_seckill_activity（name/service_id/seckill_price/original_price/stock/start_at/end_at/status）；已售量 = appointment_order.seckill_id 订单数
- GET /api/seckill（status=1 + 时间窗）、/{id}（state=not_started/ongoing/ended）、POST /{id}/buy：client_token（8-64 字符，SETNX 24h）幂等 + Redis NX 30s 防并发 + 活动校验（2026-08-26 起不再预扣库存）
- 下单注入 seckill_id 复用 OrderController::store；库存统一在 store() 事务内行锁扣减（直接调 /api/order 带 seckill_id 同样扣库存），秒杀价 = seckill_price（以 DB 为准），不叠加优惠券/积分/会员卡；订单取消不回补库存；旧促销 FLASH_SALE 通道已删除（store() 促销分支仅剩拼团，PromotionController index 过滤 flash_sale、show/join 400），秒杀只走本通道
- admin /admin/seckill CRUD + 上下架 + 订单列表（权限 407-411、420）；迁移 000606 权限种子；SeckillTest service + admin

### 44. APP 版本管理与检测更新（第24轮）

- appointment_app_version（platform/version_code/version_name/force_update/changelog/download_url/status）
- GET /api/app/version?platform=android|ios 公开检测更新（platform 非法 422；status=1 中取最新；无则空对象）
- admin /admin/versions CRUD（权限 416-419）；迁移 000609 权限种子；VersionTest service + admin

### 45. 回头客奖励（第24轮）

- ReturnCustomerRewardService：用户对同一技师 30 天内第 2 次消费（订单完成）给技师发放奖金 = 实付 paid_amount × ratio（system_config group=return_customer，ratio 默认 0.05、enabled 开关，非法值回落默认）
- 落 appointment_technician_earnings（type=return_customer，status=pending）复用佣金结算链，技师端 earnings 汇总自动包含；同 order_id+type 幂等；WorkController::complete 行锁事务内调用
- admin /admin/return-customer/config（GET/PUT）+ /rewards（?keyword 技师名/订单号/用户昵称）（权限 412-414）；迁移 000607 权限种子；ReturnCustomerRewardServiceTest

### 46. 排班导出（第24轮）

- GET /admin/technician-schedule/export：CSV（UTF-8 BOM，Excel 直接打开），文件名 schedules_{YmdHis}.csv
- start_date/end_date 必填（YYYY-MM-DD，非法 422）且跨度≤31天；technician_id 可选（hashid，非法 422）
- 列：技师ID/技师姓名/日期/时间段明细（time_slots JSON 解析为 "09:00-12:00, 14:00-18:00"）
- 权限：415；迁移 000608 权限种子；ScheduleExportTest 覆盖
