// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'report_controller.dart';

/// 数据报表页：订单/营收/技师绩效统计
class ReportPage extends GetView<ReportController> {
  const ReportPage({super.key});

  @override
  Widget build(BuildContext context) {
    Get.put(ReportController());
    return Obx(() {
      if (controller.isLoading.value) {
        return const Center(child: CircularProgressIndicator());
      }
      return SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildHeader(context),
            const SizedBox(height: 24),
            _buildSummaryCards(context),
            const SizedBox(height: 24),
            _buildTrendCard(context),
            const SizedBox(height: 24),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(flex: 3, child: _buildTechnicianCard(context)),
                const SizedBox(width: 24),
                Expanded(flex: 2, child: _buildDistributionCard(context)),
              ],
            ),
          ],
        ),
      );
    });
  }

  Widget _buildHeader(BuildContext context) {
    return Row(
      children: [
        Text('数据报表',
            style: Theme.of(context)
                .textTheme
                .headlineMedium
                ?.copyWith(fontWeight: FontWeight.bold)),
        const Spacer(),
        _rangeButton('近7天', 7),
        const SizedBox(width: 8),
        _rangeButton('近30天', 30),
      ],
    );
  }

  Widget _rangeButton(String label, int days) {
    final selected = controller.rangeDays.value == days;
    return OutlinedButton(
      style: OutlinedButton.styleFrom(
        backgroundColor: selected ? const Color(0xFF1677FF) : null,
        foregroundColor: selected ? Colors.white : null,
        side: BorderSide(color: selected ? const Color(0xFF1677FF) : Colors.grey[400]!),
      ),
      onPressed: () => controller.switchRange(days),
      child: Text(label),
    );
  }

  Widget _buildSummaryCards(BuildContext context) {
    final s = controller.summary;
    final cards = [
      ('总订单', '${s['total_orders'] ?? 0}', Icons.receipt_long, const Color(0xFF1677FF)),
      ('支付金额(元)', controller.fmtMoney(s['payment_amount']), Icons.payments, const Color(0xFF52C41A)),
      ('净营收(元)', controller.fmtMoney(s['net_revenue']), Icons.trending_up, const Color(0xFF13C2C2)),
      ('退款金额(元)', controller.fmtMoney(s['refund_amount']), Icons.currency_yen, const Color(0xFFFA541C)),
    ];
    return LayoutBuilder(
      builder: (context, constraints) {
        final crossAxisCount = constraints.maxWidth > 900 ? 4 : 2;
        return GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: crossAxisCount,
            mainAxisExtent: 110,
            crossAxisSpacing: 16,
            mainAxisSpacing: 16,
          ),
          itemCount: cards.length,
          itemBuilder: (context, index) {
            final c = cards[index];
            return Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(children: [
                      Icon(c.$3, color: c.$4, size: 20),
                      const SizedBox(width: 8),
                      Text(c.$1, style: TextStyle(fontSize: 13, color: Colors.grey[600])),
                    ]),
                    const Spacer(),
                    Text(c.$2,
                        style: const TextStyle(fontSize: 26, fontWeight: FontWeight.bold)),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  /// 趋势条形图（近 N 天逐日，无第三方图表依赖，用宽度比例条形）
  Widget _buildTrendCard(BuildContext context) {
    final list = controller.trendList;
    if (list.isEmpty) {
      return const Card(child: Padding(padding: EdgeInsets.all(24), child: Text('暂无趋势数据')));
    }
    final maxVal = list
        .map((e) => (e['payment_amount'] as num?)?.toDouble() ?? 0)
        .fold<double>(0, (a, b) => a > b ? a : b);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('每日趋势（订单数 / 支付金额）',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
            const SizedBox(height: 16),
            ...list.reversed.map((e) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(
                    children: [
                      SizedBox(
                          width: 90,
                          child: Text(e['date'],
                              style: TextStyle(fontSize: 12, color: Colors.grey[600]))),
                      Expanded(child: Stack(children: [
                        Container(
                            height: 16,
                            decoration: BoxDecoration(
                                color: Colors.grey[100],
                                borderRadius: BorderRadius.circular(4))),
                        FractionallySizedBox(
                          widthFactor: maxVal == 0
                              ? 0
                              : ((e['payment_amount'] as num?)?.toDouble() ?? 0) / maxVal,
                          child: Container(
                              height: 16,
                              decoration: BoxDecoration(
                                color: const Color(0xFF1677FF).withValues(alpha: 0.7),
                                borderRadius: BorderRadius.circular(4),
                              )),
                        ),
                      ])),
                      const SizedBox(width: 8),
                      Text('${e['order_count']}单', style: const TextStyle(fontSize: 12)),
                      const SizedBox(width: 8),
                      SizedBox(
                          width: 90,
                          child: Text('¥${controller.fmtMoney(e['payment_amount'])}',
                              textAlign: TextAlign.right,
                              style: const TextStyle(fontSize: 12))),
                    ],
                  ),
                )),
          ],
        ),
      ),
    );
  }

  Widget _buildTechnicianCard(BuildContext context) {
    final list = controller.technicianList;
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('技师绩效 TOP10',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
            const SizedBox(height: 12),
            if (list.isEmpty)
              Padding(
                  padding: const EdgeInsets.all(16),
                  child: Text('暂无数据', style: TextStyle(color: Colors.grey[500])))
            else
              ...list.asMap().entries.map((entry) {
                final i = entry.key + 1;
                final t = entry.value;
                return Padding(
                  padding: const EdgeInsets.symmetric(vertical: 6),
                  child: Row(
                    children: [
                      SizedBox(
                          width: 24,
                          child: Text('$i',
                              style: TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: i <= 3
                                      ? const Color(0xFFFA8C16)
                                      : Colors.grey[500]))),
                      SizedBox(
                          width: 80,
                          child: Text(t['technician_name'] ?? '-',
                              style: const TextStyle(fontSize: 13))),
                      Expanded(
                          child: Text('${t['order_count']}单',
                              style: const TextStyle(fontSize: 12))),
                      Expanded(
                          child: Text('¥${controller.fmtMoney(t['revenue'])}',
                              style: const TextStyle(fontSize: 12))),
                      SizedBox(
                          width: 50,
                          child: Row(children: [
                            const Icon(Icons.star, size: 12, color: Color(0xFFFA8C16)),
                            Text('${t['rating'] ?? 0}', style: const TextStyle(fontSize: 12)),
                          ])),
                    ],
                  ),
                );
              }),
          ],
        ),
      ),
    );
  }

  Widget _buildDistributionCard(BuildContext context) {
    final payTypes = controller.payTypeList;
    final statuses = controller.statusList;
    final maxCount = payTypes
        .map((e) => (e['count'] as num?)?.toInt() ?? 0)
        .fold<int>(0, (a, b) => a > b ? a : b);
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('渠道分布', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
            const SizedBox(height: 12),
            ...payTypes.map((p) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 6),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(children: [
                        Text('${p['name']}', style: const TextStyle(fontSize: 13)),
                        const Spacer(),
                        Text('${p['count']}笔 ¥${controller.fmtMoney(p['amount'])}',
                            style: const TextStyle(fontSize: 12)),
                      ]),
                      const SizedBox(height: 4),
                      Stack(children: [
                        Container(
                            height: 8,
                            decoration: BoxDecoration(
                                color: Colors.grey[100],
                                borderRadius: BorderRadius.circular(4))),
                        FractionallySizedBox(
                          widthFactor:
                              maxCount == 0 ? 0 : ((p['count'] as num?)?.toInt() ?? 0) / maxCount,
                          child: Container(
                              height: 8,
                              decoration: BoxDecoration(
                                  color: const Color(0xFF52C41A),
                                  borderRadius: BorderRadius.circular(4))),
                        ),
                      ]),
                    ],
                  ),
                )),
            const SizedBox(height: 16),
            const Text('订单状态', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: statuses
                  .map((s) => Chip(
                        label: Text('${s['name']} ${s['count']}',
                            style: const TextStyle(fontSize: 12)),
                        visualDensity: VisualDensity.compact,
                      ))
                  .toList(),
            ),
          ],
        ),
      ),
    );
  }
}
