/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'faq_controller.dart';
import 'faq_form_page.dart';

class FaqListPage extends GetView<FaqController> {
  const FaqListPage({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<FaqController>()) {
      Get.put(FaqController(), permanent: false);
    }
    final ctrl = controller;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        Row(
          children: [
            const Text('FAQ 管理', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const Spacer(),
            ElevatedButton.icon(
              onPressed: () => Get.to(() => const FaqFormPage())?.then((_) => ctrl.loadFaqs(reset: true)),
              icon: const Icon(Icons.add),
              label: const Text('新增 FAQ'),
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
                decoration: const InputDecoration(hintText: '搜索问题', prefixIcon: Icon(Icons.search), isDense: true),
                onSubmitted: (v) => ctrl.search(v),
              ),
            ),
            const SizedBox(width: 12),
            ChoiceChip(label: const Text('全部'), selected: ctrl.statusFilter.value == null, onSelected: (_) => ctrl.filterByStatus(null)),
            const SizedBox(width: 4),
            ChoiceChip(label: const Text('显示'), selected: ctrl.statusFilter.value == 1, onSelected: (_) => ctrl.filterByStatus(1)),
            const SizedBox(width: 4),
            ChoiceChip(label: const Text('隐藏'), selected: ctrl.statusFilter.value == 0, onSelected: (_) => ctrl.filterByStatus(0)),
          ],
        ),
        const SizedBox(height: 12),
        // Table
        Expanded(
          child: Obx(() {
            if (ctrl.isLoading.value) return const Center(child: CircularProgressIndicator());
            if (ctrl.faqs.isEmpty) return const Center(child: Text('暂无数据'));

            return SingleChildScrollView(
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('问题')),
                  DataColumn(label: Text('答案')),
                  DataColumn(label: Text('排序')),
                  DataColumn(label: Text('状态')),
                  DataColumn(label: Text('更新时间')),
                  DataColumn(label: Text('操作')),
                ],
                rows: ctrl.faqs.map((f) {
                  return DataRow(
                    cells: [
                      DataCell(SizedBox(
                        width: 220,
                        child: Text(f['title'] ?? '', maxLines: 1, overflow: TextOverflow.ellipsis),
                      )),
                      DataCell(SizedBox(
                        width: 260,
                        child: Text(
                          f['content'] ?? '',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      )),
                      DataCell(Text(f['sort']?.toString() ?? '-')),
                      DataCell(Chip(
                        label: Text(f['status'] == 1 ? '显示' : '隐藏'),
                        color: WidgetStatePropertyAll(f['status'] == 1 ? Colors.green.shade50 : Colors.orange.shade50),
                      )),
                      DataCell(Text(f['updated_at'] ?? '-')),
                      DataCell(Row(mainAxisSize: MainAxisSize.min, children: [
                        IconButton(
                          icon: const Icon(Icons.edit, size: 18),
                          onPressed: () => Get.to(() => FaqFormPage(faqData: f))?.then((_) => ctrl.loadFaqs()),
                        ),
                        IconButton(
                          icon: const Icon(Icons.delete, size: 18, color: Colors.red),
                          onPressed: () => _confirmDelete(context, ctrl, f),
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

  void _confirmDelete(BuildContext context, FaqController ctrl, dynamic faq) {
    final pwdCtrl = TextEditingController();
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('确认删除'),
        content: Column(mainAxisSize: MainAxisSize.min, children: [
          Text('确定要删除 FAQ「${faq['title']}」吗？'),
          const SizedBox(height: 8),
          TextField(controller: pwdCtrl, obscureText: true, decoration: const InputDecoration(labelText: '请输入您的密码确认')),
        ]),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('取消')),
          ElevatedButton(
            onPressed: () {
              ctrl.deleteFaq(faq['id'].toString(), pwdCtrl.text);
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
