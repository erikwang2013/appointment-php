/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'technician_controller.dart';
import 'technician_detail_page.dart';

/// 技师管理列表页（审核状态筛选 + 姓名/手机号搜索 + 分页）
class TechnicianListPage extends GetView<TechnicianController> {
  const TechnicianListPage({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<TechnicianController>()) {
      Get.put(TechnicianController(), permanent: false);
    }
    final ctrl = controller;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        const Row(
          children: [
            Text('技师管理', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
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
                decoration: const InputDecoration(hintText: '搜索姓名', prefixIcon: Icon(Icons.search), isDense: true),
                onSubmitted: (v) => ctrl.searchName(v),
              ),
            ),
            SizedBox(
              width: 220,
              child: TextField(
                decoration: const InputDecoration(hintText: '搜索手机号', prefixIcon: Icon(Icons.phone), isDense: true),
                onSubmitted: (v) => ctrl.searchPhone(v),
              ),
            ),
            ChoiceChip(label: const Text('全部'), selected: ctrl.statusFilter.value == null, onSelected: (_) => ctrl.filterByStatus(null)),
            const SizedBox(width: 4),
            ChoiceChip(label: const Text('待审核'), selected: ctrl.statusFilter.value == 'pending', onSelected: (_) => ctrl.filterByStatus('pending')),
            const SizedBox(width: 4),
            ChoiceChip(label: const Text('已通过'), selected: ctrl.statusFilter.value == 'approved', onSelected: (_) => ctrl.filterByStatus('approved')),
            const SizedBox(width: 4),
            ChoiceChip(label: const Text('已驳回'), selected: ctrl.statusFilter.value == 'rejected', onSelected: (_) => ctrl.filterByStatus('rejected')),
          ],
        )),
        const SizedBox(height: 12),
        // Table
        Expanded(
          child: Obx(() {
            if (ctrl.isLoading.value) return const Center(child: CircularProgressIndicator());
            if (ctrl.technicians.isEmpty) return const Center(child: Text('暂无数据'));

            return SingleChildScrollView(
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('姓名')),
                  DataColumn(label: Text('手机号')),
                  DataColumn(label: Text('性别')),
                  DataColumn(label: Text('评分')),
                  DataColumn(label: Text('接单数')),
                  DataColumn(label: Text('收藏数')),
                  DataColumn(label: Text('审核状态')),
                  DataColumn(label: Text('审核时间')),
                  DataColumn(label: Text('操作')),
                ],
                rows: ctrl.technicians.map((t) {
                  final id = t['id'].toString();
                  final user = t['user'] is Map ? t['user'] as Map : const {};
                  final status = (t['status'] ?? '').toString();
                  return DataRow(
                    cells: [
                      // 后端已脱敏（首字+**）
                      DataCell(Text((t['real_name'] ?? '-').toString())),
                      DataCell(Text((user['phone'] ?? '-').toString())),
                      DataCell(Text(technicianGenderLabel(t['gender']))),
                      DataCell(Text((t['rating'] ?? '-').toString())),
                      DataCell(Text((t['order_count'] ?? '0').toString())),
                      DataCell(Text((t['favorite_count'] ?? '0').toString())),
                      DataCell(Chip(
                        label: Text(technicianStatusLabel(status)),
                        color: WidgetStatePropertyAll(_statusColor(status)),
                      )),
                      DataCell(Text((t['audited_at'] ?? '-').toString())),
                      DataCell(Row(mainAxisSize: MainAxisSize.min, children: [
                        IconButton(
                          icon: const Icon(Icons.visibility, size: 18),
                          tooltip: '详情',
                          onPressed: () => Get.to(() => TechnicianDetailPage(techId: id))
                              ?.then((_) => ctrl.loadTechnicians()),
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

  Color _statusColor(String status) {
    switch (status) {
      case 'pending':
      case '0':
        return Colors.orange.shade50;
      case 'approved':
      case '1':
        return Colors.green.shade50;
      case 'rejected':
      case '2':
        return Colors.red.shade50;
      default:
        return Colors.grey.shade50;
    }
  }
}
