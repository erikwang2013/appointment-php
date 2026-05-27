# 安装说明

## 环境要求

| 组件 | 最低版本 | 说明 |
|------|----------|------|
| PHP | 8.3+ | 扩展: bcmath/curl/gd/mbstring/pdo_mysql/redis |
| MySQL | 8.0+ | 表前缀 `erik_` |
| Redis | 6.0+ | 缓存/限流/Session |
| Composer | 2.x | PHP依赖管理 |

## 快速安装

### 1. 安装依赖

```bash
cd service/ && cp .env.example .env && composer install
cd ../admin/ && cp .env.example .env && composer install
```

### 2. 导入数据库

```bash
cd admin/
for f in database/migrations/*.sql; do mysql -u root -p < "$f"; done
```

### 3. 启动服务

```bash
cd service/ && php start.php start -d   # 业务API → :8788
cd admin/ && php start.php start -d     # 管理后台 → :8787
```

### 4. 验证

```bash
curl http://localhost:8788/api/docs   # 业务API文档
curl http://localhost:8787/api/docs   # 管理后台API文档
```

---

## Docker 部署

```bash
cd admin/ && cp .env.docker .env && docker-compose up -d
```

编排: nginx(80/443) + app(8787) + mysql(3306) + redis(6379) + elasticsearch(9200)

---

## 环境变量 (service/.env)

```bash
APP_ENV=dev                  # dev / production
DB_HOST=127.0.0.1            # 数据库主机
DB_PORT=3306                 # 数据库端口
DB_DATABASE=appointment      # 数据库名
DB_USERNAME=root             # 用户名
DB_PASSWORD=                 # 密码
REDIS_HOST=127.0.0.1         # Redis主机
REDIS_PORT=6379              # Redis端口
JWT_SECRET_KEY=              # JWT密钥(生产务必修改)
ENCRYPTION_KEY=              # API加密密钥
ENCRYPTABLE_KEY=             # 数据库加密密钥
HASHIDS_SALT=                # Hashids盐值
```

---

## 第三方服务配置

在管理后台「系统配置」中填写:

| 配置组 | 用途 |
|--------|------|
| `wechat_pay` | 微信支付商户号/API密钥 |
| `sms` | 短信服务商(Aliyun/Tencent) |
| `map_service` | 地图服务(Amap/Tencent) |
| `wechat_app` | 微信小程序AppID/Secret |
| `storage` | 对象存储(OSS/COS)配置 |
| `push` | APNs/FCM推送证书 |

---

## 运行测试

```bash
cd admin/ && phpunit --bootstrap tests/bootstrap.php    # 60 tests
cd service/ && phpunit --configuration phpunit.xml       # 21 tests
```

## 导入演示数据

```bash
cd service/ && php seed.php
```

---

## 常见问题

**Q: 启动报错 `Class support\Model not found`**  
A: 运行 `composer dump-autoload`

**Q: 数据库连接失败**  
A: 检查 .env 中 DB_* 配置

**Q: Redis 连接失败**  
A: 确认 Redis 已启动 `redis-server`

**Q: 端口被占用**  
A: 修改 config/server.php 中 listen 端口
