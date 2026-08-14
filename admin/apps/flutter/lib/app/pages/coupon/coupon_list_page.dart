/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'coupon_controller.dart';
import 'coupon_form_page.dart';

class CouponListPage extends GetView<CouponController> {
  const CouponListPage({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<CouponController>()) {
      Get.put(CouponController(), permanent: false);
    }
    final ctrl = controller;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        Row(
          children: [
            const Text('优惠券管理', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const Spacer(),
            ElevatedButton.icon(
              onPressed: () => Get.to(() => const CouponFormPage())?.then((_) => ctrl.loadCoupons(reset: true)),
              icon: const Icon(Icons.add),
              label: const Text('新增优惠券'),
            ),
          ],
        ),
        const SizedBox(height: 12),
        // Search + Filter
        Row(
          children: [
            SizedBox(
              width: 250,
              child: TextField(
                decoration: const InputDecoration(hintText: '搜索优惠券名称', prefixIcon: Icon(Icons.search), isDense: true),
                onSubmitted: (v) => ctrl.search(v),
              ),
            ),
            const SizedBox(width: 12),
            ChoiceChip(label: const Text('全部'), selected: ctrl.statusFilter.value == null, onSelected: (_) => ctrl.filterByStatus(null)),
            const SizedBox(width: 4),
            ChoiceChip(label: const Text('启用'), selected: ctrl.statusFilter.value == 1, onSelected: (_) => ctrl.filterByStatus(1)),
            const SizedBox(width: 4),
            ChoiceChip(label: const Text('停用'), selected: ctrl.statusFilter.value == 0, onSelected: (_) => ctrl.filterByStatus(0)),
          ],
        ),
        const SizedBox(height: 12),
        // Table
        Expanded(
          child: Obx(() {
            if (ctrl.isLoading.value) return const Center(child: CircularProgressIndicator());
            if (ctrl.coupons.isEmpty) return const Center(child: Text('暂无数据'));

            return SingleChildScrollView(
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('名称')),
                  DataColumn(label: Text('类型')),
                  DataColumn(label: Text('优惠数值')),
                  DataColumn(label: Text('使用门槛')),
                  DataColumn(label: Text('总量/剩余')),
                  DataColumn(label: Text('有效期')),
                  DataColumn(label: Text('状态')),
                  DataColumn(label: Text('操作')),
                ],
                rows: ctrl.coupons.map((c) {
                  final startAt = c['start_at']?.toString() ?? '-';
                  final endAt = c['end_at']?.toString();
                  final endText = (endAt == null || endAt.isEmpty) ? '长期' : endAt;
                  return DataRow(
                    cells: [
                      DataCell(Text(c['name'] ?? '')),
                      DataCell(Text(CouponController.typeLabel(c['type']?.toString()))),
                      DataCell(Text(c['amount']?.toString() ?? '-')),
                      DataCell(Text(c['min_amount']?.toString() ?? '-')),
                      DataCell(Text('${c['total_qty'] ?? 0}/${c['remain_qty'] ?? 0}')),
                      DataCell(Text('$startAt ~ $endText')),
                      DataCell(Chip(
                        label: Text(c['status'] == 1 ? '启用' : '停用'),
                        color: WidgetStatePropertyAll(c['status'] == 1 ? Colors.green.shade50 : Colors.red.shade50),
                      )),
                      DataCell(Row(mainAxisSize: MainAxisSize.min, children: [
                        IconButton(
                          icon: const Icon(Icons.edit, size: 18),
                          onPressed: () => Get.to(() => CouponFormPage(couponData: c))?.then((_) => ctrl.loadCoupons()),
                        ),
                        IconButton(
                          icon: const Icon(Icons.delete, size: 18, color: Colors.red),
                          onPressed: () => _confirmDelete(context, ctrl, c),
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

  void _confirmDelete(BuildContext context, CouponController ctrl, dynamic coupon) {
    final pwdCtrl = TextEditingController();
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('确认删除'),
        content: Column(mainAxisSize: MainAxisSize.min, children: [
          Text('确定要删除优惠券「${coupon['name']}」吗？'),
          const SizedBox(height: 8),
          TextField(controller: pwdCtrl, obscureText: true, decoration: const InputDecoration(labelText: '请输入您的密码确认')),
        ]),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('取消')),
          ElevatedButton(
            onPressed: () {
              ctrl.deleteCoupon(coupon['id'].toString(), pwdCtrl.text);
              Navigator.pop(context);
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            child: const Text('删除'),
          ),
        ],
      ),
    );
  }
}
