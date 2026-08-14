/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'service_card_controller.dart';
import 'service_card_form_page.dart';

/// 卡项设计列表页（服务套餐/组合卡管理）
class ServiceCardListPage extends GetView<ServiceCardController> {
  const ServiceCardListPage({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<ServiceCardController>()) {
      Get.put(ServiceCardController(), permanent: false);
    }
    final ctrl = controller;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        Row(
          children: [
            const Text('卡项设计', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const Spacer(),
            ElevatedButton.icon(
              onPressed: () => Get.to(() => const ServiceCardFormPage())?.then((_) => ctrl.loadCards(reset: true)),
              icon: const Icon(Icons.add),
              label: const Text('新增卡项'),
            ),
          ],
        ),
        const SizedBox(height: 12),
        // Search
        Row(
          children: [
            SizedBox(
              width: 250,
              child: TextField(
                decoration: const InputDecoration(hintText: '搜索卡项名称/类型', prefixIcon: Icon(Icons.search), isDense: true),
                onSubmitted: (v) => ctrl.search(v),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        // Table
        Expanded(
          child: Obx(() {
            if (ctrl.isLoading.value) return const Center(child: CircularProgressIndicator());
            if (ctrl.cards.isEmpty) return const Center(child: Text('暂无数据'));

            return SingleChildScrollView(
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('名称')),
                  DataColumn(label: Text('类型')),
                  DataColumn(label: Text('总售价')),
                  DataColumn(label: Text('手工费')),
                  DataColumn(label: Text('佣金')),
                  DataColumn(label: Text('销售提成')),
                  DataColumn(label: Text('内含服务/产品')),
                  DataColumn(label: Text('状态')),
                  DataColumn(label: Text('操作')),
                ],
                rows: ctrl.cards.map((c) {
                  final serviceCount = ServiceCardController.listLength(c['services']);
                  final productCount = ServiceCardController.listLength(c['product_ids']);
                  return DataRow(
                    cells: [
                      DataCell(Text(c['name'] ?? '')),
                      DataCell(Text(ServiceCardController.typeLabel(c['type']?.toString()))),
                      DataCell(Text(c['total_price']?.toString() ?? '-')),
                      DataCell(Text(c['handwork_total']?.toString() ?? '-')),
                      DataCell(Text(c['commission_amount']?.toString() ?? '-')),
                      DataCell(Text(c['sales_commission']?.toString() ?? '-')),
                      DataCell(Text('$serviceCount 项 / $productCount 个')),
                      DataCell(Chip(
                        label: Text(c['status'] == 1 ? '启用' : '停用'),
                        color: WidgetStatePropertyAll(c['status'] == 1 ? Colors.green.shade50 : Colors.red.shade50),
                      )),
                      DataCell(Row(mainAxisSize: MainAxisSize.min, children: [
                        IconButton(
                          icon: const Icon(Icons.edit, size: 18),
                          onPressed: () => Get.to(() => ServiceCardFormPage(cardData: c))?.then((_) => ctrl.loadCards()),
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

  void _confirmDelete(BuildContext context, ServiceCardController ctrl, dynamic card) {
    final pwdCtrl = TextEditingController();
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('确认删除'),
        content: Column(mainAxisSize: MainAxisSize.min, children: [
          Text('确定要删除卡项「${card['name']}」吗？'),
          const SizedBox(height: 8),
          TextField(controller: pwdCtrl, obscureText: true, decoration: const InputDecoration(labelText: '请输入您的密码确认')),
        ]),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('取消')),
          ElevatedButton(
            onPressed: () {
              ctrl.deleteCard(card['id'].toString(), pwdCtrl.text);
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
