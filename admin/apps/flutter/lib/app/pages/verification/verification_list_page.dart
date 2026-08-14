/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'verification_controller.dart';

class VerificationListPage extends GetView<VerificationController> {
  const VerificationListPage({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<VerificationController>()) {
      Get.put(VerificationController(), permanent: false);
    }
    final ctrl = controller;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        const Row(
          children: [
            Text('核销记录', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
          ],
        ),
        const SizedBox(height: 12),
        // Search + Filter
        Row(
          children: [
            SizedBox(
              width: 250,
              child: TextField(
                decoration: const InputDecoration(hintText: '搜索订单号', prefixIcon: Icon(Icons.search), isDense: true),
                onSubmitted: (v) => ctrl.search(v),
              ),
            ),
            const SizedBox(width: 12),
            ChoiceChip(label: const Text('全部'), selected: ctrl.verifyTypeFilter.value == null, onSelected: (_) => ctrl.filterByType(null)),
            const SizedBox(width: 4),
            ChoiceChip(label: const Text('扫码核销'), selected: ctrl.verifyTypeFilter.value == 'scan', onSelected: (_) => ctrl.filterByType('scan')),
            const SizedBox(width: 4),
            ChoiceChip(label: const Text('自行核销'), selected: ctrl.verifyTypeFilter.value == 'self', onSelected: (_) => ctrl.filterByType('self')),
          ],
        ),
        const SizedBox(height: 12),
        // Table
        Expanded(
          child: Obx(() {
            if (ctrl.isLoading.value) return const Center(child: CircularProgressIndicator());
            if (ctrl.records.isEmpty) return const Center(child: Text('暂无数据'));

            return SingleChildScrollView(
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('订单号')),
                  DataColumn(label: Text('核销方式')),
                  DataColumn(label: Text('核销人ID')),
                  DataColumn(label: Text('核销地点')),
                  DataColumn(label: Text('核销时间')),
                ],
                rows: ctrl.records.map((v) {
                  final order = v['order'] is Map ? v['order'] as Map : const {};
                  final orderNo = (order['order_no'] ?? '').toString();
                  return DataRow(
                    cells: [
                      DataCell(Text(orderNo.isEmpty ? '-' : orderNo)),
                      DataCell(Text(v['verify_type'] == 'self' ? '自行核销' : '扫码核销')),
                      DataCell(Text((v['verified_by'] ?? '').toString())),
                      DataCell(Text((v['location'] ?? '').toString().isEmpty ? '-' : v['location'].toString())),
                      DataCell(Text((v['verified_at'] ?? '-').toString())),
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
}
