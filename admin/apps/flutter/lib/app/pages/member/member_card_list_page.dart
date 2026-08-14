/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'member_card_controller.dart';

/// 会员卡定义管理页（列表 + 新建/编辑对话框，精简版）
class MemberCardListPage extends GetView<CardDefinitionController> {
  const MemberCardListPage({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<CardDefinitionController>()) {
      Get.put(CardDefinitionController(), permanent: false);
    }
    final ctrl = controller;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            const Text('会员卡定义', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const Spacer(),
            ElevatedButton.icon(
              onPressed: () => _showEditDialog(context, ctrl),
              icon: const Icon(Icons.add, size: 18),
              label: const Text('新建会员卡'),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            SizedBox(
              width: 250,
              child: TextField(
                decoration: const InputDecoration(hintText: '搜索卡名/类型', prefixIcon: Icon(Icons.search), isDense: true),
                onSubmitted: (v) => ctrl.search(v),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Expanded(
          child: Obx(() {
            if (ctrl.isLoading.value) return const Center(child: CircularProgressIndicator());
            if (ctrl.cards.isEmpty) return const Center(child: Text('暂无数据'));
            return SingleChildScrollView(
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('卡名')),
                  DataColumn(label: Text('类型')),
                  DataColumn(label: Text('价格')),
                  DataColumn(label: Text('有效天数')),
                  DataColumn(label: Text('总次数')),
                  DataColumn(label: Text('状态')),
                  DataColumn(label: Text('操作')),
                ],
                rows: ctrl.cards.map((c) {
                  return DataRow(cells: [
                    DataCell(Text(c['name'] ?? '-')),
                    DataCell(Text(CardDefinitionController.typeLabel(c['type']))),
                    DataCell(Text(c['price']?.toString() ?? '-')),
                    DataCell(Text(c['duration_days']?.toString() ?? '-')),
                    DataCell(Text(c['total_times']?.toString() ?? '-')),
                    DataCell(Text(CardDefinitionController.statusLabel(c['status']))),
                    DataCell(Row(mainAxisSize: MainAxisSize.min, children: [
                      IconButton(
                        icon: const Icon(Icons.edit, size: 18),
                        tooltip: '编辑',
                        onPressed: () => _showEditDialog(context, ctrl, card: c),
                      ),
                      IconButton(
                        icon: Icon(c['status']?.toString() == '1' ? Icons.visibility_off : Icons.visibility, size: 18),
                        tooltip: c['status']?.toString() == '1' ? '下架' : '上架',
                        onPressed: () => ctrl.toggleStatus(c, c['status']?.toString() == '1' ? 0 : 1),
                      ),
                      IconButton(
                        icon: const Icon(Icons.delete, size: 18),
                        tooltip: '删除',
                        onPressed: () => _confirmDelete(context, ctrl, c),
                      ),
                    ])),
                  ]);
                }).toList(),
              ),
            );
          }),
        ),
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

  /// 新建/编辑对话框（精简：名称/类型/价格/有效天数/总次数/包含服务 JSON/状态）
  Future<void> _showEditDialog(BuildContext context, CardDefinitionController ctrl, {dynamic card}) async {
    final isEdit = card != null;
    final nameCtrl = TextEditingController(text: card?['name']?.toString() ?? '');
    final priceCtrl = TextEditingController(text: card?['price']?.toString() ?? '');
    final daysCtrl = TextEditingController(text: card?['duration_days']?.toString() ?? '');
    final timesCtrl = TextEditingController(text: card?['total_times']?.toString() ?? '');
    final servicesCtrl = TextEditingController(text: card?['services'] == null ? '' : card['services'].toString());
    var type = card?['type']?.toString() ?? 'month';
    var status = card?['status']?.toString() == '1' ? 1 : 0;

    await showDialog(
      context: context,
      builder: (_) => StatefulBuilder(
        builder: (context, setState) => AlertDialog(
          title: Text(isEdit ? '编辑会员卡' : '新建会员卡'),
          content: SizedBox(
            width: 420,
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  TextField(controller: nameCtrl, decoration: const InputDecoration(labelText: '卡名 *', isDense: true)),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<String>(
                    initialValue: type,
                    decoration: const InputDecoration(labelText: '类型 *', isDense: true),
                    items: CardDefinitionController.typeLabels.entries
                        .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value)))
                        .toList(),
                    onChanged: (v) => setState(() => type = v!),
                  ),
                  const SizedBox(height: 8),
                  TextField(controller: priceCtrl, decoration: const InputDecoration(labelText: '价格 *', isDense: true)),
                  const SizedBox(height: 8),
                  TextField(controller: daysCtrl, decoration: const InputDecoration(labelText: '有效天数', isDense: true)),
                  const SizedBox(height: 8),
                  TextField(controller: timesCtrl, decoration: const InputDecoration(labelText: '总次数（次卡）', isDense: true)),
                  const SizedBox(height: 8),
                  TextField(
                    controller: servicesCtrl,
                    decoration: const InputDecoration(labelText: '包含服务（JSON 数组，可留空）', isDense: true),
                    maxLines: 2,
                  ),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<int>(
                    initialValue: status,
                    decoration: const InputDecoration(labelText: '状态', isDense: true),
                    items: const [
                      DropdownMenuItem(value: 1, child: Text('启用')),
                      DropdownMenuItem(value: 0, child: Text('禁用')),
                    ],
                    onChanged: (v) => setState(() => status = v!),
                  ),
                ],
              ),
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(context), child: const Text('取消')),
            ElevatedButton(
              onPressed: () async {
                if (nameCtrl.text.trim().isEmpty || priceCtrl.text.trim().isEmpty) {
                  Get.snackbar('提示', '卡名和价格必填');
                  return;
                }
                final ok = await ctrl.save({
                  'name': nameCtrl.text.trim(),
                  'type': type,
                  'price': priceCtrl.text.trim(),
                  'duration_days': int.tryParse(daysCtrl.text.trim()) ?? 0,
                  'total_times': int.tryParse(timesCtrl.text.trim()) ?? 0,
                  'services': servicesCtrl.text.trim().isEmpty ? [] : servicesCtrl.text.trim(),
                  'status': status,
                }, id: isEdit ? card['id'].toString() : null);
                if (ok && context.mounted) Navigator.pop(context);
              },
              child: const Text('保存'),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _confirmDelete(BuildContext context, CardDefinitionController ctrl, dynamic card) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('确认删除'),
        content: Text('确定删除会员卡「${card['name']}」？有用户持卡时将被拒绝。'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('取消')),
          ElevatedButton(onPressed: () => Navigator.pop(context, true), child: const Text('删除')),
        ],
      ),
    );
    if (ok == true) ctrl.delete(card);
  }
}
