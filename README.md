# 预约服务系统

三端预约服务管理平台：用户端微信小程序 + Flutter APP（同账号身份切换）、PC管理后台。

> **项目状态**: 全部完成 ✅ | 104 控制器 | 58 模型 | 80 测试 | 55+ 数据表 | 242 路由

## 项目结构

```
appointment-php/
├── admin/              # 管理后台 (webman v2 + Flutter Web)
├── service/            # 业务API服务 (webman v2)
├── apps/               # 用户端前端应用
│   ├── wechat/         #   微信小程序（原生）
│   └── flutter/        #   Flutter APP（iOS + Android）
└── docs/               # 项目文档
```

## 快速开始

### 环境要求

- PHP 8.3+
- MySQL 8.0+
- Redis
- Composer

### Web 安装向导（推荐）

```bash
cd admin/
cp .env.example .env
composer install
php start.php start -d
```

浏览器打开 `http://localhost:8787/install`，按指引填写数据库和管理员账号即可完成安装。

### 手动安装

```bash
# 1. 安装依赖
cd service/ && cp .env.example .env && composer install
cd ../admin/ && cp .env.example .env && composer install

# 2. 一键导入数据库（含全部 55 张表 + 演示数据）
mysql -u root -p < docs/install.sql

# 3. 启动服务
cd service/ && php start.php start -d   # 业务API → :8788
cd ../admin/ && php start.php start -d  # 管理后台 → :8787
```

### Docker 部署

```bash
cd admin/ && cp .env.docker .env && docker-compose up -d
cd ../service/ && cp .env.docker .env && docker-compose up -d
```

## 技术栈

| 层级 | 技术 | 说明 |
|------|------|------|
| 后端框架 | webman v2 (PHP 8.3+) | 高性能常驻内存HTTP服务 |
| 数据库 | MySQL 8.0 | 表前缀 `erik_` |
| 缓存 | Redis | 缓存/限流/Session/队列 |
| 搜索 | Elasticsearch | 全文检索（via webman-scout） |
| 管理后台前端 | Flutter Web | PC管理后台风格 |
| 用户端APP | Flutter | iOS + Android |
| 用户端小程序 | 原生微信小程序 | WXML/WXSS/JS |
| ID生成 | erikwang2013/snowflake-php | BIGINT非自增主键 |
| API ID加解密 | erikwang2013/hashids | 对外隐藏真实ID |
| JWT认证 | erikwang2013/jwt-webman | Bearer Token |
| 敏感数据加密 | erikwang2013/encryption + encryptable | API + DB双层加密 |
| 安全防护 | erikwang2013/security-php | 31种攻击检测 |
| 操作验证 | erikwang2013/poster-php | 敏感操作随机验证 |
| 国家旗帜 | erikwang2013/season | 国旗图标 |
| ES同步 | erikwang2013/webman-scout | 模型自动同步 |

## 系统架构

```mermaid
graph TB
    subgraph 终端["用户终端"]
        WX["微信小程序<br/>apps/wechat/"]
        APP["Flutter APP<br/>apps/flutter/"]
    end

    subgraph 业务API["业务API :8788"]
        MW1["Cors → Security → RateLimit → Auth"]
        API["公开 · 用户 · 技师 · 服务 · 订单 · 营销 · 内容 · LBS"]
    end

    subgraph 管理后台["管理后台 :8787"]
        MW2["Cors → Security → RateLimit → AdminAuth → RBAC → OperationLog"]
        ADMIN["仪表盘 · 用户 · 技师 · 门店 · 服务 · 订单 · 优惠券 · 财务 · 内容 · 设置"]
        FW["Flutter Web 前端"]
    end

    subgraph 数据["数据层"]
        MySQL[("MySQL 8.0<br/>55+ 表 erik_")]
        Redis[("Redis<br/>缓存/限流/锁")]
        ES[("Elasticsearch<br/>全文检索")]
    end

    subgraph 外部["第三方服务"]
        WXPAY["微信支付"]
        SMS["短信服务"]
        MAP["地图服务"]
        OSS["对象存储"]
    end

    subgraph 安全["安全组件"]
        SEC["security-php<br/>31种攻击检测"]
        JWT["jwt-webman<br/>Token+黑名单"]
        ENC["encryption<br/>API+DB双层加密"]
    end

    WX & APP -->|"HTTP API"| MW1 --> API
    FW -->|"HTTP API"| MW2 --> ADMIN
    API & ADMIN --> MySQL & Redis & ES
    安全 -.->|防护| 业务API & 管理后台
    API & ADMIN -.->|调用| 外部

    classDef client fill:#e1f5fe,stroke:#0288d1,color:#01579b
    classDef service fill:#fff3e0,stroke:#f57c00,color:#e65100
    classDef admin fill:#e8f5e9,stroke:#388e3c,color:#1b5e20
    classDef dat fill:#fce4ec,stroke:#c62828,color:#880e4f
    classDef ext fill:#f3e5f5,stroke:#7b1fa2,color:#4a148c
    classDef sec fill:#fff8e1,stroke:#f9a825,color:#f57f17

    class WX,APP client
    class MW1,API service
    class MW2,ADMIN,FW admin
    class MySQL,Redis,ES dat
    class WXPAY,SMS,MAP,OSS ext
    class SEC,JWT,ENC sec
```

## 核心流程

### 服务预约流程

```mermaid
flowchart TD
    A["浏览服务"] --> B["选门店/技师/时间"]
    B --> C["确认订单<br/>优惠券/备注/协议"]
    C --> D{"Redis锁技师<br/>SETNX 3min"}
    D -->|"成功"| E["创建订单 pending"]
    D -->|"冲突"| F["提示技师繁忙"]
    E --> G["微信支付"]
    G -->|"成功"| H["订单 paid<br/>通知用户+技师"]
    G -->|"失败"| I["保持pending<br/>15min自动取消"]
    H --> J["技师确认 → serving"]
    J --> K["服务完成 → 核销"]
    K --> L["completed → 评价"]
    L --> M["reviewed ✅"]

    style A fill:#e3f2fd,stroke:#1565c0,color:#01579b
    style M fill:#c8e6c9,stroke:#2e7d32,color:#1b5e20
    style F fill:#ffcdd2,stroke:#c62828,color:#b71c1c
```

### 支付与退款流程

```mermaid
flowchart LR
    subgraph 正向["正向支付"]
        P1["创建支付记录"] --> P2["微信统一下单"] --> P3["调起支付"] --> P4["回调验签"] --> P5["paid"]
    end
    subgraph 退款["退款规则"]
        R1["申请退款"] --> R2{"判定"}
        R2 -->|">6h / ≤15min"| R3["退100%"]
        R2 -->|"≤6h"| R4["退90%"]
        R2 -->|"已开始"| R5["退80%"]
        R2 -->|"确认后"| R6["不退"]
        R3 & R4 & R5 --> R7["两级审批<br/>店长→财务"] --> R8["refunded"]
    end

    style P5 fill:#c8e6c9,stroke:#2e7d32,color:#1b5e20
    style R6 fill:#ffcdd2,stroke:#c62828,color:#b71c1c
    style R8 fill:#c8e6c9,stroke:#2e7d32,color:#1b5e20
```

## 订单生命周期

```mermaid
stateDiagram-v2
    [*] --> pending: 提交订单

    pending --> paid: 支付成功
    pending --> cancelled: 超时/主动取消

    paid --> confirmed: 技师确认
    paid --> cancelled: 取消(按退款规则)
    paid --> refunding: 申请退款

    confirmed --> serving: 服务开始
    serving --> completed: 核销完成
    serving --> refunding: 异常退款(80%)

    completed --> reviewed: 用户评价

    refunding --> refunded: 审核通过
    refunding --> paid: 审核驳回

    reviewed --> [*]
    cancelled --> [*]
    refunded --> [*]

    note right of pending: Redis锁技师3分钟
    note right of refunding: 店长→财务两级审批
```

## 安全架构

### 纵深防御七层体系

```mermaid
graph TB
    subgraph L1["① 边界防护"]
        WAF["WAF/Nginx · TLS 1.3 · 安全头"]
    end
    subgraph L2["② 接入防护"]
        CORS["Cors · CORS_ALLOW_ORIGIN · OPTIONS预检"]
    end
    subgraph L3["③ 攻击检测"]
        SEC["security-php · 31种检测器<br/>XSS/SQL注入/CSRF/路径遍历"]
        BLOCK["自动封禁: 5次/60s → IP黑名单15min"]
    end
    subgraph L4["④ 流量控制"]
        RL["RateLimit · Redis滑动窗口+Lua<br/>默认60次/min · 登录10次 · 验证码1次/60s"]
    end
    subgraph L5["⑤ 身份认证"]
        AUTH["JWT(7天+刷新+黑名单) · bcrypt<br/>登录锁定5次/15min · 并发≤3Token"]
    end
    subgraph L6["⑥ 权限控制"]
        RBAC["RBAC(Redis缓存) · poster-php操作验证"]
    end
    subgraph L7["⑦ 数据安全"]
        DATA["encryption API加密 · encryptable DB加密<br/>hashids ID编码 · 日志不含明文"]
    end

    请求["Request"] --> WAF --> CORS --> SEC
    SEC -->|"通过"| RL --> AUTH --> RBAC --> DATA --> 响应["Response"]
    SEC -->|"攻击"| BLOCK -.-> 拒绝["403/429"]

    classDef layer1 fill:#e3f2fd,stroke:#1565c0,color:#01579b
    classDef layer2 fill:#bbdefb,stroke:#1976d2,color:#01579b
    classDef layer3 fill:#ffcdd2,stroke:#c62828,color:#b71c1c
    classDef layer4 fill:#fff9c4,stroke:#f9a825,color:#f57f17
    classDef layer5 fill:#c8e6c9,stroke:#2e7d32,color:#1b5e20
    classDef layer6 fill:#e1bee7,stroke:#7b1fa2,color:#4a148c
    classDef layer7 fill:#d7ccc8,stroke:#5d4037,color:#3e2723

    class WAF layer1
    class CORS layer2
    class SEC,BLOCK layer3
    class RL layer4
    class AUTH layer5
    class RBAC layer6
    class DATA layer7
```

> 更多详细图示：[流程图](docs/diagrams/FLOWCHART.md)（含技师提现/身份切换）| [功能脑图](docs/diagrams/FUNCTION-DIAGRAM.md) | [全部生命周期](docs/diagrams/LIFECYCLE-DIAGRAM.md) | [完整安全架构](docs/diagrams/SECURITY-ARCHITECTURE.md)

## 文档导航

| 文档 | 说明 |
|------|------|
| [架构说明](docs/ARCHITECTURE.md) | 系统架构、三端关系、技术组件、数据流 |
| [功能说明](docs/FEATURES.md) | 用户端/技师端/管理后台完整功能清单 |
| [架构设计](docs/ARCHITECTURE-DESIGN.md) | 分层设计、中间件链、数据库设计、安全设计 |
| [功能设计](docs/FEATURE-DESIGN.md) | 核心业务流程、业务规则、状态机、退款规则 |
| [API文档](docs/API.md) | 业务API + 管理后台API，含请求/响应示例 + OpenAPI端点 |
| [安装说明](docs/INSTALL.md) | 环境要求、Docker部署、环境变量、第三方配置、常见问题 |
| [使用说明](docs/USAGE.md) | 管理后台配置、用户端/技师端操作、API示例、退款规则 |
| [项目结构](docs/STRUCTURE.md) | 完整目录布局、中间件执行链、数据库表清单 |
| [设计规范](docs/superpowers/specs/2026-05-26-appointment-system-design.md) | 系统设计规范 |
| [实现计划](docs/superpowers/plans/2026-05-26-appointment-system-plan.md) | 分阶段实现计划 |

## 支持项目 / Support

如果这个项目对你有帮助，欢迎支持！感谢你的鼓励 :heart:

If this project helps you, your support is welcome and appreciated!

<table>
  <tr>
    <td align="center" width="50%">
      <img src="docs/weixinpay.png" alt="微信支付 / WeChat Pay" width="130" height="130"><br>
      <b>微信支付</b><br>WeChat Pay
    </td>
    <td align="center" width="50%">
      <img src="docs/alipay.png" alt="支付宝 / Alipay" width="130" height="130"><br>
      <b>支付宝</b><br>Alipay
    </td>
  </tr>
</table>

## 版权

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
