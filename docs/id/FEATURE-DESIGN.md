# Desain Fitur
> **Languages**: [中文](../FEATURE-DESIGN.md) · [English](../en/FEATURE-DESIGN.md) · [한국어](../ko/FEATURE-DESIGN.md) · [Русский](../ru/FEATURE-DESIGN.md) · [Deutsch](../de/FEATURE-DESIGN.md) · [Français](../fr/FEATURE-DESIGN.md) · [Español](../es/FEATURE-DESIGN.md) · [Português](../pt/FEATURE-DESIGN.md) · [हिन्दी](../hi/FEATURE-DESIGN.md) · [العربية](../ar/FEATURE-DESIGN.md) · [বাংলা](../bn/FEATURE-DESIGN.md) · [日本語](../ja/FEATURE-DESIGN.md)

> Terjemahan bahasa Indonesia · Asli: [中文](../../docs/FEATURE-DESIGN.md)

## Alur Pembelian

### Alur Janji Temu Layanan (pemesanan langsung)

```
服务详情 → 确认订单(门店/技师/时间/优惠券/备注) → 阅读服务协议
    → 提交订单 → Redis锁技师3分钟 → 微信支付 → 支付成功
    → 通知用户+技师 → 服务时间到 → 技师确认开始
    → 服务完成 → 二维码核销 → 用户评价 → 订单完成
```

### Alur Pembelian Produk (mode keranjang)

```
产品列表 → 加入购物车 → 购物车确认(改数量/删除)
    → 提交订单 → 支付 → 发货 → 收货 → 完成
```

## State Machine Pesanan

```
pending(待支付) → paid(已支付) → confirmed(已确认)
    → serving(服务中) → completed(已完成) → reviewed(已评价)

pending → cancelled(已取消)
paid → cancelled
paid → refunding(退款中) → refunded(已退款)
```

## Mekanisme Kunci Teknisi

Pengguna masuk halaman konfirmasi pesanan → Redis SETNX kunci 3 menit. Keluar/timeout lepaskan.

```
SETNX lock:tech:123:2026-05-26-14:00 user_456 EX 180
 → 成功: 继续下单
 → 失败: 技师已被锁定
```

## Aturan Refund

| Kondisi | Proporsi refund |
|------|----------|
| Dalam 15 menit pemesanan atau jarak mulai >6 jam | 100% |
| Jarak mulai ≤6 jam | 90% |
| Sudah mulai tetapi belum konfirmasi layanan | 80% |
| Setelah layanan dikonfirmasi mulai | 0% (tidak direfund) |

## Aturan Diskon

| Tipe | Kondisi | Diskon | Tumpuk |
|------|------|------|------|
| Diskon jam sepi | 10-12 / 17-18 / setelah 21:00 | 90% | bisa ditumpuk kupon |
| Janji lebih awal | 30 menit lebih awal | 95% | tidak bisa ditumpuk kupon |

## Penarikan Dana Teknisi

- Setiap tanggal 20 bisa menarik dana, T+1 masuk WeChat Pay
- Sudah diverifikasi belum diselesaikan: konfirmasi otomatis 3 hari
- Jumlah minimum/jumlah disimpan/batas kelipatan ratus dikonfigurasi backend

### Alur Penarikan

```
申请提现 → poster-php验证 → 后台审核(通过/驳回)
    → 完成提现 → 微信零钱到账 → 生成财务流水
```

### Tipe Pendapatan

| Tipe | Keterangan |
|------|------|
| commission | komisi layanan |
| bonus | bonus (pelanggan berulang/kehadiran) |
| penalty | denda (24 jam tidak tulis arsip) |
| subsidy | subsidi |
| attendance | hadiah kehadiran penuh |

### Bonus Pelanggan Berulang

Konsumsi kedua dalam 30 hari ke teknisi sama → catat bonus

### Arsip Member

Dalam 24 jam setelah setiap pesanan selesai wajib tulis arsip, jika tidak tidak ada komisi

## Desain Poin

- Didapat dari konsumsi, didapat dari referral (dapat dikonfigurasi backend)
- 1:100 tukar kartu hadiah (dapat dikonfigurasi backend)
- Tabel transaksi poin mencatat setiap perubahan + saldo

## Desain Kartu Member

| Tipe | Penagihan | Keterangan |
|------|------|------|
| month | per hari | kartu bulanan biasa |
| vip | per hari | kartu tahunan VIP |
| times | per kunjungan | kartu kunjungan, dapat bebas mengombinasikan item layanan |

Kartu kunjungan: saat membeli pilih kombinasi layanan (A×3+B×5), setiap kunjungan mengonsumsi 1 kunjungan item terkait. Habis→used_up, kedaluwarsa→expired.

## Peralihan Identitas

```
客户 → 切换技师 → 检查技师档案是否approved
    → 是: active_role=technician, 页面切换
    → 否: 引导入驻申请

技师 → 切换客户 → active_role=customer, 页面切换
```

## Hadiah Pengguna Baru

```
注册 → 生成推荐码 → 有推荐人→创建推广记录
    → 自动发新用户优惠券(Phase 5)
    → 推荐人获积分(被推荐人首单后)
```

## Desain Pembayaran (reservasi WeChat Pay)

```
POST /api/order/pay/{id}
    → 创建支付记录 → 调用微信统一下单(预留WechatPayService)
    → 返回支付参数 → 前端调起支付
    → 微信回调 /api/wechat/notify → 验签 → 更新状态paid
    → 通知用户+技师
```
