// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'store_manager_controller.dart';

/// 门店工作台视图页（admin 端）
/// 按门店筛选展示概览卡片（今日订单/营收/进行中/技师数/核销数）与门店订单列表。
class StoreManagerPage extends GetView<StoreManagerController> {
  const StoreManagerPage({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<StoreManagerController>()) {
      Get.put(StoreManagerController(), permanent: false);
    }
    final ctrl = controller;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Row(
          children: [
            Text('门店工作台', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            Spacer(),
            Text('按门店查看今日经营概览与订单', style: TextStyle(color: Colors.grey, fontSize: 12)),
          ],
        ),
        const SizedBox(height: 12),
        // 门店选择 + 状态筛选
        Obx(() => Wrap(
          spacing: 12,
          runSpacing: 8,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            SizedBox(
              width: 220,
              child: DropdownButtonFormField<String>(
                key: ValueKey(ctrl.selectedStoreId.value),
                initialValue: ctrl.selectedStoreId.value.isEmpty ? null : ctrl.selectedStoreId.value,
                isExpanded: true,
                decoration: const InputDecoration(labelText: '选择门店', isDense: true),
                items: ctrl.stores.map((s) => DropdownMenuItem(
                  value: s['id']?.toString(),
                  child: Text(s['name']?.toString() ?? '-', overflow: TextOverflow.ellipsis),
                )).toList(),
                onChanged: (v) => ctrl.selectStore(v),
              ),
            ),
            SizedBox(
              width: 150,
              child: DropdownButtonFormField<String>(
                key: ValueKey(ctrl.status.value),
                initialValue: ctrl.status.value.isEmpty ? null : ctrl.status.value,
                isExpanded: true,
                decoration: const InputDecoration(labelText: '订单状态', isDense: true),
                items: [
                  const DropdownMenuItem(value: '', child: Text('全部状态')),
                  ...StoreManagerController.statusOptions.entries
                      .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value))),
                ],
                onChanged: (v) => ctrl.applyStatus(v),
              ),
            ),
          ],
        )),
        const SizedBox(height: 12),
        // 概览卡片
        Obx(() {
          if (ctrl.loadingOverview.value) {
            return const SizedBox(
              height: 96,
              child: Center(child: CircularProgressIndicator()),
            );
          }
          final o = ctrl.overview.value;
          if (o == null) return const SizedBox(height: 40);
          return Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              _statCard('今日订单数', '${o['today_orders'] ?? 0}', Icons.receipt_long, Colors.blue),
              _statCard('今日营收(元)', _money(o['today_revenue']), Icons.payments, Colors.green),
              _statCard('进行中订单', '${o['ongoing_orders'] ?? 0}', Icons.hourglass_top, Colors.orange),
              _statCard('技师数', '${o['technician_count'] ?? 0}', Icons.handyman, Colors.purple),
              _statCard('今日核销', '${o['verification_count'] ?? 0}', Icons.qr_code, Colors.teal),
            ],
          );
        }),
        const SizedBox(height: 12),
        // 订单表格
        Expanded(
          child: Obx(() {
            if (ctrl.isLoading.value) return const Center(child: CircularProgressIndicator());
            if (ctrl.orders.isEmpty) return const Center(child: Text('暂无数据'));
            return SingleChildScrollView(
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('订单号')),
                  DataColumn(label: Text('用户')),
                  DataColumn(label: Text('实付金额')),
                  DataColumn(label: Text('状态')),
                  DataColumn(label: Text('下单时间')),
                ],
                rows: ctrl.orders.map((o) {
                  return DataRow(cells: [
                    DataCell(Text(o['order_no']?.toString() ?? '-')),
                    DataCell(Text(
                        (o['user'] as Map<dynamic, dynamic>?)?['nickname']?.toString() ?? '-')),
                    DataCell(Text(_money(o['paid_amount']))),
                    DataCell(Text(
                      StoreManagerController.statusText(o),
                      style: TextStyle(
                        color: StoreManagerController.statusColor(o),
                        fontWeight: FontWeight.w600,
                      ),
                    )),
                    DataCell(Text(_shortDate(o['created_at']))),
                  ]);
                }).toList(),
              ),
            );
          }),
        ),
        // 分页
        const SizedBox(height: 8),
        Obx(() => Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            IconButton(onPressed: ctrl.prevPage, icon: const Icon(Icons.chevron_left)),
            Text('第 ${ctrl.page.value} 页 / 共 ${(ctrl.total.value / ctrl.limit.value).ceil()} 页 (${ctrl.total.value} 条)'),
            IconButton(onPressed: ctrl.nextPage, icon: const Icon(Icons.chevron_right)),
          ],
        )),
      ],
    );
  }

  Widget _statCard(String label, String value, IconData icon, Color color) {
    return Container(
      width: 160,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          Icon(icon, color: color, size: 28),
          const SizedBox(width: 10),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(value, style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: color)),
              Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
            ],
          ),
        ],
      ),
    );
  }

  String _money(dynamic v) {
    if (v == null) return '0.00';
    return double.tryParse(v.toString())?.toStringAsFixed(2) ?? v.toString();
  }

  String _shortDate(dynamic v) {
    final s = v?.toString() ?? '';
    return s.length >= 16 ? s.substring(0, 16) : s;
  }
}
