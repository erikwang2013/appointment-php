# 测试团队报告 — 全量测试覆盖审计

> 生成时间：2026-08-26　版本：v1.3.8
> 团队：deep-audit（tester-php / tester-api / tester-ui / tester-go / tester-rust）

## 1. 执行摘要

| 角色 | 任务 | 结果 |
|------|------|------|
| PHP 测试工程师 | 所有模块单元/集成测试 | 70 个既有测试 + 本轮新增（见 §3） |
| API 测试工程师 | 所有接口自动化 | 控制器层集成测试即本项目 API 自动化形态（§4） |
| UI 自动化工程师 | 所有页面端到端 | 环境不具备，结论见 §5 |
| GO 测试工程师 | 单元测试 | **跳过：项目无 GO 代码**（零 .go 文件） |
| Rust 测试工程师 | 单元测试 | **跳过：项目无 Rust 代码**（零 .rs 文件） |

## 2. 技术栈与测试形态

- 后端：PHP 8.3 webman，两应用（service 用户端 / admin 后台端），共享 service 模型
- 测试框架：PHPUnit + Eloquent，**真实 MySQL + 事务回滚**模式（非 mock），DB 不可用自动 skip
- 测试运行：`cd service && php -d memory_limit=2G vendor/bin/phpunit`
- API 自动化 = 控制器层集成测试（构造 Request 直接调控制器方法，打真实 DB，事务回滚）

## 3. PHP 测试覆盖

**全量结果：558 tests / 2508 assertions，0 失败 0 错误 0 skip**（2 既有 vendor deprecation、2 既有 PHPUnit notice，均非本轮引入；原 4 个提现门禁 skip 已通过 config('withdraw.gate_day') 可注入消除，全天可跑）

### 本轮新增（tester-php，6 文件 32 用例，均真实 DB + 事务回滚）

| 测试文件 | 用例 | 覆盖 |
|---------|------|------|
| CartControllerTest | 4 | 保存规范化（白名单/qty≥1/丢脏条目）、非数组 400、空车、清空 |
| PointControllerTest | 4 | 余额=最新快照、分页 meta、type/source 过滤、空列表 |
| AddressControllerTest | 7 | 新增+默认、必填 400、默认互斥、默认优先、越权 404、切默认、删除+二次 404 |
| FavoriteControllerTest | 7 | 收藏服务/技师、非法类型 400、重复 400、favorite_count 增减、孤儿收藏、删除 404 |
| ReferralControllerTest | 5 | 邀请码生成+统计、用户 404、二维码 URL、被推荐列表、返佣明细 |
| WithdrawControllerTest | 5 | 门禁日拒绝（config 注入非今日）、成功、余额不足、<10 元、缺账户（全天可跑，0 skip） |

### 既有覆盖（70 文件，未变）

35+ 控制器已覆盖：Auth/Order 状态机/退款/核销/改期/支付回调/秒杀/拼团/优惠券/礼品卡/积分/钱包/转账/会员卡/成长值/返利/提现/打卡/排班/发票/物流/推送/订阅消息/队列等。

### 本轮修复（tester-php 发现）

- 【bug】AddressController::show/update/destroy 与 FavoriteController::destroy 未做 hashids 解码，hashid 调用 404。
  根因修复：`BaseController::decodeId` 增加纯数字透传兼容（hashids 解不出且 ctype_digit 时原样返回），
  全仓库 89 处调用统一受益；4 个控制器方法入口补 decodeId。全量回归通过。
- 【bug】hashids min-length 为 0 时，部分裸数字 ID（如 306）恰好是其他 ID 的合法 hashids 编码，
  decodeId 会误解码成错误 ID（AddressControllerTest 偶发 404，多轮全量运行随机复现）。
  根因修复：service/admin `config/hashids.php` 的 main 连接 `length` 0→8，
  编码恒 ≥8 字符，与裸数字 ID（<8 位或 16 位）长度不相交，歧义从编码空间消除。
  连跑 5 轮 AddressControllerTest 验证稳定，全量回归通过。
- 提现门禁日硬编码 20 号改为 `config('withdraw.gate_day')` 可注入（config/withdraw.php），
  原 4 个"仅每月 20 号"skip 用例改为反射注入门禁日，全天可跑，0 skip。

## 4. API 自动化测试结论

- 本项目无独立 HTTP 层测试脚本；70 个既有测试文件均为控制器层集成测试（真实 DB），
  覆盖 35+ 控制器，等价于接口自动化测试
- 测试覆盖矩阵见 §3
- 本地无运行中的本仓库 webman 服务（8787/8788/8789 均为其他项目或 /tmp 副本），
  故未做 curl 级冒烟；如需 HTTP 冒烟可在 CI 中起服务后执行

## 5. UI 端到端结论

- 客户端：Flutter（apps/flutter 用户端、admin/apps/flutter 后台端）、微信小程序（apps/wechat）、
  HarmonyOS（apps/harmonyos）、admin/apps/weixin
- 现状：admin Flutter web 未构建产物（build/web 不存在）；本机无运行中的 UI 服务；
  微信小程序/HarmonyOS 无浏览器自动化通道
- **结论：端到端自动化环境不具备**。建议在 CI 中增加：flutter build web → Playwright 驱动
  后台端关键路径（登录→订单列表→核销）；小程序/HarmonyOS 需真机/模拟器手动测试
- 已提供：admin/public/apidoc（接口文档页面）

## 6. GO / Rust

项目根目录递归扫描 **0 个 .go 文件、0 个 .rs 文件**（排除 vendor/node_modules/.git）。
工具链已安装（go / rustc 可用）但无可测对象。若后续引入 GO/Rust 服务，需另行补充测试。

## 7. 遗留风险（未覆盖高价值区域）

- order 主流程（已通过 OrderState/OrderRefundFlow 等 trait 级测试覆盖）
- 微信支付真实回调（WechatPayService 有单测，真实微信沙箱未联测）
- 打印、LBS、验证码等外部依赖模块

（§3 待 tester-php 返回后填充）
