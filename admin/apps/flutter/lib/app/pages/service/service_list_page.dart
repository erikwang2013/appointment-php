/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'service_controller.dart';
import 'service_form_page.dart';

/// 服务管理列表页（分类筛选 + 上架状态 + 名称搜索 + 分页）
class ServiceListPage extends GetView<ServiceController> {
  const ServiceListPage({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<ServiceController>()) {
      Get.put(ServiceController(), permanent: false);
    }
    final ctrl = controller;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        Row(
          children: [
            const Text('服务管理', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const Spacer(),
            ElevatedButton.icon(
              onPressed: () => Get.to(() => const ServiceFormPage())?.then((_) => ctrl.loadServices(reset: true)),
              icon: const Icon(Icons.add),
              label: const Text('新增服务'),
            ),
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
              width: 250,
              child: TextField(
                decoration: const InputDecoration(hintText: '搜索服务名称', prefixIcon: Icon(Icons.search), isDense: true),
                onSubmitted: (v) => ctrl.search(v),
              ),
            ),
            SizedBox(
              width: 200,
              child: DropdownButtonFormField<String>(
                key: ValueKey(ctrl.categoryFilter.value ?? ''),
                initialValue: ctrl.categoryFilter.value ?? '',
                isExpanded: true,
                decoration: const InputDecoration(labelText: '服务分类', isDense: true),
                items: [
                  const DropdownMenuItem(value: '', child: Text('全部分类')),
                  ...ctrl.categories.map((c) => DropdownMenuItem(
                        value: c['id'].toString(),
                        child: Text((c['name'] ?? '').toString()),
                      )),
                ],
                onChanged: (v) => ctrl.filterByCategory(v),
              ),
            ),
            ChoiceChip(label: const Text('全部'), selected: ctrl.statusFilter.value == null, onSelected: (_) => ctrl.filterByStatus(null)),
            const SizedBox(width: 4),
            ChoiceChip(label: const Text('上架'), selected: ctrl.statusFilter.value == 1, onSelected: (_) => ctrl.filterByStatus(1)),
            const SizedBox(width: 4),
            ChoiceChip(label: const Text('下架'), selected: ctrl.statusFilter.value == 0, onSelected: (_) => ctrl.filterByStatus(0)),
          ],
        )),
        const SizedBox(height: 12),
        // Table
        Expanded(
          child: Obx(() {
            if (ctrl.isLoading.value) return const Center(child: CircularProgressIndicator());
            if (ctrl.services.isEmpty) return const Center(child: Text('暂无数据'));

            return SingleChildScrollView(
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('服务名称')),
                  DataColumn(label: Text('分类')),
                  DataColumn(label: Text('价格')),
                  DataColumn(label: Text('原价')),
                  DataColumn(label: Text('时长')),
                  DataColumn(label: Text('销量')),
                  DataColumn(label: Text('状态')),
                  DataColumn(label: Text('操作')),
                ],
                rows: ctrl.services.map((s) {
                  final categoryName = s['category'] is Map
                      ? (s['category'] as Map)['name']?.toString()
                      : null;
                  return DataRow(
                    cells: [
                      DataCell(Text((s['name'] ?? '').toString())),
                      DataCell(Text(categoryName ?? (s['category_id'] ?? '-').toString())),
                      DataCell(Text((s['price'] ?? '-').toString())),
                      DataCell(Text((s['original_price'] ?? '-').toString())),
                      DataCell(Text('${(s['duration'] ?? '-').toString()} 分钟')),
                      DataCell(Text((s['sales_volume'] ?? '0').toString())),
                      DataCell(Chip(
                        label: Text(s['status'] == 1 ? '上架' : '下架'),
                        color: WidgetStatePropertyAll(s['status'] == 1 ? Colors.green.shade50 : Colors.red.shade50),
                      )),
                      DataCell(Row(mainAxisSize: MainAxisSize.min, children: [
                        IconButton(icon: const Icon(Icons.edit, size: 18), onPressed: () => Get.to(() => ServiceFormPage(serviceData: s))?.then((_) => ctrl.loadServices())),
                        IconButton(icon: const Icon(Icons.delete, size: 18, color: Colors.red), onPressed: () => _confirmDelete(context, ctrl, s)),
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

  /// 删除需管理员密码二次确认（后端 confirmPassword）
  void _confirmDelete(BuildContext context, ServiceController ctrl, dynamic service) {
    final pwdCtrl = TextEditingController();
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('确认删除'),
        content: Column(mainAxisSize: MainAxisSize.min, children: [
          Text('确定要删除服务「${service['name']}」吗？'),
          const SizedBox(height: 8),
          TextField(controller: pwdCtrl, obscureText: true, decoration: const InputDecoration(labelText: '请输入您的密码确认')),
        ]),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('取消')),
          ElevatedButton(
            onPressed: () {
              ctrl.deleteService(service['id'].toString(), pwdCtrl.text);
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
