/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'aftersale_controller.dart';
import 'aftersale_audit_dialog.dart';

/// 售后（退换货）管理列表页（状态筛选 / uid/订单号搜索 / 分页 / 审核操作）
class AftersaleListPage extends GetView<AftersaleController> {
  const AftersaleListPage({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<AftersaleController>()) {
      Get.put(AftersaleController(), permanent: false);
    }
    final ctrl = controller;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        const Row(
          children: [
            Text('售后管理', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            Spacer(),
            Text('审核通过仅状态流转，退款沿用订单退款流程另行操作', style: TextStyle(color: Colors.grey, fontSize: 12)),
          ],
        ),
        const SizedBox(height: 12),
        // Filter: status + uid + order no + reset
        Obx(() => Wrap(
          spacing: 12,
          runSpacing: 8,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            SizedBox(
              width: 150,
              child: DropdownButtonFormField<String>(
                key: ValueKey(ctrl.status.value),
                initialValue: ctrl.status.value.isEmpty ? null : ctrl.status.value,
                isExpanded: true,
                decoration: const InputDecoration(labelText: '状态', isDense: true),
                items: [
                  const DropdownMenuItem(value: '', child: Text('全部状态')),
                  ...AftersaleController.statusOptions.entries
                      .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value))),
                ],
                onChanged: (v) => ctrl.applyFilter(st: v ?? ''),
              ),
            ),
            SizedBox(
              width: 160,
              child: TextField(
                decoration: const InputDecoration(labelText: '用户ID', isDense: true),
                onSubmitted: (v) => ctrl.applyFilter(uid: v.trim()),
              ),
            ),
            SizedBox(
              width: 200,
              child: TextField(
                decoration: const InputDecoration(labelText: '订单号', isDense: true),
                onSubmitted: (v) => ctrl.applyFilter(no: v.trim()),
              ),
            ),
            TextButton.icon(
              onPressed: ctrl.clearFilter,
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
            if (ctrl.aftersales.isEmpty) return const Center(child: Text('暂无数据'));

            return SingleChildScrollView(
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('售后单号')),
                  DataColumn(label: Text('订单号')),
                  DataColumn(label: Text('类型')),
                  DataColumn(label: Text('用户ID')),
                  DataColumn(label: Text('退款金额')),
                  DataColumn(label: Text('状态')),
                  DataColumn(label: Text('申请时间')),
                  DataColumn(label: Text('操作')),
                ],
                rows: ctrl.aftersales.map((a) {
                  final order = a['order'] as Map<dynamic, dynamic>?;
                  return DataRow(
                    cells: [
                      DataCell(Text(a['aftersale_no'] ?? '-')),
                      DataCell(Text(order?['order_no']?.toString() ?? '-')),
                      DataCell(Text(AftersaleController.typeText(a))),
                      DataCell(Text(a['user_id']?.toString() ?? '-')),
                      DataCell(Text(_money(a['refund_amount']))),
                      DataCell(Text(
                        AftersaleController.statusText(a),
                        style: TextStyle(
                          color: AftersaleController.statusColor(a),
                          fontWeight: FontWeight.w600,
                        ),
                      )),
                      DataCell(Text(_shortDate(a['created_at']))),
                      DataCell(Row(mainAxisSize: MainAxisSize.min, children: [
                        IconButton(
                          icon: const Icon(Icons.visibility, size: 18),
                          tooltip: '详情/审核',
                          onPressed: () => _showAudit(context, a),
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

  Future<void> _showAudit(BuildContext context, dynamic a) async {
    final result = await showDialog<AuditResult>(
      context: context,
      builder: (_) => AftersaleAuditDialog(aftersale: a),
    );
    if (result == null) return;
    await controller.review(a['id'].toString(), action: result.action, remark: result.remark);
  }

  String _money(dynamic v) {
    if (v == null) return '-';
    final n = double.tryParse(v.toString());
    return n == null ? '-' : '¥${n.toStringAsFixed(2)}';
  }

  String _shortDate(dynamic v) {
    if (v == null) return '-';
    final s = v.toString();
    return s.length >= 19 ? s.substring(0, 16) : s;
  }
}
