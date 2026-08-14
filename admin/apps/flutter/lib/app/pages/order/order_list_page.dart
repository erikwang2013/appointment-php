/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'order_controller.dart';
import 'order_detail_page.dart';

/// 订单管理列表页（状态筛选 + 订单号/用户搜索 + 日期范围 + 分页）
class OrderListPage extends StatefulWidget {
  const OrderListPage({super.key});

  @override
  State<OrderListPage> createState() => _OrderListPageState();
}

class _OrderListPageState extends State<OrderListPage> {
  final _keywordCtrl = TextEditingController();
  final _uidCtrl = TextEditingController();

  OrderController get _ctrl {
    if (!Get.isRegistered<OrderController>()) {
      Get.put(OrderController(), permanent: false);
    }
    return Get.find<OrderController>();
  }

  @override
  void dispose() {
    _keywordCtrl.dispose();
    _uidCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = _ctrl;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        const Row(
          children: [
            Text('订单管理', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
          ],
        ),
        const SizedBox(height: 12),
        // Search + Filter
        Obx(() => Wrap(
          spacing: 12,
          runSpacing: 8,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            SizedBox(
              width: 220,
              child: TextField(
                controller: _keywordCtrl,
                decoration: const InputDecoration(hintText: '搜索订单号', prefixIcon: Icon(Icons.search), isDense: true),
                onSubmitted: (v) => ctrl.search(v),
              ),
            ),
            SizedBox(
              width: 160,
              child: TextField(
                controller: _uidCtrl,
                decoration: const InputDecoration(hintText: '用户ID', prefixIcon: Icon(Icons.person), isDense: true),
                onSubmitted: (v) => ctrl.searchUid(v),
              ),
            ),
            SizedBox(
              width: 160,
              child: DropdownButtonFormField<String>(
                key: ValueKey(ctrl.statusFilter.value ?? ''),
                initialValue: ctrl.statusFilter.value ?? '',
                isExpanded: true,
                decoration: const InputDecoration(labelText: '订单状态', isDense: true),
                items: const [
                  DropdownMenuItem(value: '', child: Text('全部状态')),
                  DropdownMenuItem(value: 'pending', child: Text('待支付')),
                  DropdownMenuItem(value: 'paid', child: Text('已支付')),
                  DropdownMenuItem(value: 'confirmed', child: Text('已确认')),
                  DropdownMenuItem(value: 'serving', child: Text('服务中')),
                  DropdownMenuItem(value: 'completed', child: Text('已完成')),
                  DropdownMenuItem(value: 'cancelled', child: Text('已取消')),
                  DropdownMenuItem(value: 'refunding', child: Text('退款中')),
                  DropdownMenuItem(value: 'refunded', child: Text('已退款')),
                ],
                onChanged: (v) => ctrl.filterByStatus(v),
              ),
            ),
            OutlinedButton.icon(
              onPressed: () => _pickDate(context, start: true),
              icon: const Icon(Icons.event, size: 18),
              label: Text(ctrl.dateStart.value.isEmpty ? '开始日期' : ctrl.dateStart.value),
            ),
            OutlinedButton.icon(
              onPressed: () => _pickDate(context, start: false),
              icon: const Icon(Icons.event, size: 18),
              label: Text(ctrl.dateEnd.value.isEmpty ? '结束日期' : ctrl.dateEnd.value),
            ),
            TextButton.icon(
              onPressed: () {
                _keywordCtrl.clear();
                _uidCtrl.clear();
                ctrl.clearFilter();
              },
              icon: const Icon(Icons.refresh, size: 18),
              label: const Text('重置'),
            ),
          ],
        )),
        const SizedBox(height: 12),
        // Table
        Expanded(
          child: Obx(() {
            if (ctrl.isLoading.value) return const Center(child: CircularProgressIndicator());
            if (ctrl.orders.isEmpty) return const Center(child: Text('暂无数据'));

            return SingleChildScrollView(
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('订单号')),
                  DataColumn(label: Text('类型')),
                  DataColumn(label: Text('实付金额')),
                  DataColumn(label: Text('状态')),
                  DataColumn(label: Text('服务时间')),
                  DataColumn(label: Text('用户')),
                  DataColumn(label: Text('创建时间')),
                  DataColumn(label: Text('操作')),
                ],
                rows: ctrl.orders.map((o) {
                  final id = o['id'].toString();
                  final user = o['user'] is Map ? o['user'] as Map : const {};
                  final userName = (user['nickname'] ?? user['real_name'] ?? user['phone'] ?? '').toString();
                  final status = (o['status'] ?? '').toString();
                  return DataRow(
                    cells: [
                      DataCell(Text((o['order_no'] ?? '-').toString())),
                      DataCell(Text(orderTypeLabel((o['order_type'] ?? '').toString()))),
                      DataCell(Text((o['paid_amount'] ?? '-').toString())),
                      DataCell(Chip(
                        label: Text(orderStatusLabel(status)),
                        color: WidgetStatePropertyAll(_statusColor(status)),
                      )),
                      DataCell(Text((o['service_time'] ?? '-').toString())),
                      DataCell(Text(userName.isEmpty ? '-' : userName)),
                      DataCell(Text((o['created_at'] ?? '-').toString())),
                      DataCell(Row(mainAxisSize: MainAxisSize.min, children: [
                        IconButton(
                          icon: const Icon(Icons.visibility, size: 18),
                          tooltip: '详情',
                          onPressed: () => Get.to(() => OrderDetailPage(orderId: id)),
                        ),
                      ])),
                    ],
                  );
                }).toList(),
              ),
            );
          }),
        ),
        // Pagination
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

  Future<void> _pickDate(BuildContext context, {required bool start}) async {
    final ctrl = _ctrl;
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2035),
    );
    if (picked == null) return;
    final value = '${picked.year.toString().padLeft(4, '0')}-'
        '${picked.month.toString().padLeft(2, '0')}-'
        '${picked.day.toString().padLeft(2, '0')}';
    await ctrl.applyDate(start: start ? value : ctrl.dateStart.value, end: start ? ctrl.dateEnd.value : value);
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'pending':
        return Colors.orange.shade50;
      case 'paid':
        return Colors.blue.shade50;
      case 'confirmed':
        return Colors.indigo.shade50;
      case 'serving':
        return Colors.cyan.shade50;
      case 'completed':
        return Colors.green.shade50;
      case 'cancelled':
        return Colors.grey.shade50;
      case 'refunding':
        return Colors.purple.shade50;
      case 'refunded':
        return Colors.red.shade50;
      default:
        return Colors.grey.shade50;
    }
  }
}
