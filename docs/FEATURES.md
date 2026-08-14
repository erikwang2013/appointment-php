# 功能说明

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
| 提现 | 每月20号，T+1到账微信零钱；管理端审核，金额≥500 两级审批（店长→财务） |
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
| 消息通知 | 站内通知（erik_notification）；消息中心页：分页/下拉刷新/已读高亮/标记已读/全部已读 |
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
| 订阅授权 | utils/subscribe.js 集中管理模板 ID（键名与服务端 erik_system_config.wechat_app.template_ids 对齐） |
| 触发场景 | 预约成功/支付成功后手势回调内 wx.requestSubscribeMessage，未配置模板 ID 或用户拒绝均静默 |
| 服务端链路 | WechatTemplateMessageService 发送 + NotificationReminderService 预约前 2h~1h 提醒 + AutoCancelTimer 进程扫描 |

### 19. 售后退换货（第14轮）

| 功能 | 说明 |
|------|------|
| 申请售后 | POST /api/aftersales：type=refund/exchange，校验本人订单/paid+completed/同单去重 |
| 我的售后 | GET /api/aftersales 分页列表 + GET /api/aftersales/{id} 详情 |
| 审核流转 | 管理端 approve/reject（rejected 必填 remark）；approved 仅状态流转，退款沿用订单退款接口 |

### 20. 拼团/秒杀（第15轮）

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
| 订单标记 | erik_order 新增 promotion_id/participant_id 列 + 索引 |
| 未成团处理 | 到期未满员→活动关闭+批量取消该活动 pending 订单（幂等）；pay() 懒判定已关闭则自动取消订单并释放技师锁 |

### 22. 分销返佣（第16轮）

| 功能 | 说明 |
|------|------|
| 发放规则 | 被推荐人首单 completed 后发放：金额=paid_amount×reward_rate（erik_system_config referral.reward_rate 默认 0.05，非法回落常量），>0 才发 |
| 挂接点 | ReferralRewardService::handleOrderCompleted 挂接 WorkController::complete 事务内（serving→completed 唯一入口，核销 verify 只到 serving 不触发），失败整体回滚可重试 |
| 幂等 | erik_user_referral 行锁 lockForUpdate + rewarded_at 判空 + 锁内首单复查（并发/重复调用只发一次） |
| 入账 | 钱包行锁累加 + WalletTxn type='referral_reward'（balance_after + 订单号 remark）；推荐记录写 reward_type/reward_amount/rewarded_at/first_order_at |
| 明细 | GET /api/user/referral/earnings 分页（被推荐人昵称/头像/订单号/金额/时间） |

### 23. 积分兑换商城（第16轮）

| 功能 | 说明 |
|------|------|
| 兑换商品 | erik_points_exchange_goods：type=coupon/gift_card/wallet，points_cost/value（DECIMAL(25,2) 防雪崩 ID 精度丢失）/stock/status |
| 商品列表 | GET /api/marketing/points-exchange：上架商品 + 实时剩余库存 + 已兑数 |
| 兑换 | POST /api/marketing/points-exchange/{id}：Redis NX 锁 + 商品行锁防超兑；积分 SUM 校验（不足 422）+ UserPoints type='consume' source='exchange' 扣减；coupon 发券 / wallet 余额入账（WalletTxn points_exchange）/ gift_card 卡密返回 |
| 幂等 | uk_user_goods 唯一索引同用户同商品限一次 + 锁内复验 + 1062 兜底；兑换记录快照 erik_user_points_exchange |

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

- erik_user.member_level 会员等级列（迁移 000008）
- MemberCardController 完整 CRUD（权限 365-369）：GET/POST/PUT/DELETE /admin/member-cards
- Flutter 会员卡定义管理页

### 15. 售后管理（第14轮）

- erik_order_aftersale 表（迁移 000009）：type=refund/exchange，status=pending/approved/rejected/completed
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
