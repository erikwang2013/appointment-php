/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'withdrawal_controller.dart';
import 'withdrawal_audit_dialog.dart';

/// 技师提现管理列表页（状态筛选 / 分页 / 金额展示 / 审核操作）
class WithdrawalListPage extends GetView<WithdrawalController> {
  const WithdrawalListPage({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<WithdrawalController>()) {
      Get.put(WithdrawalController(), permanent: false);
    }
    final ctrl = controller;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        const Row(
          children: [
            Text('提现管理', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            Spacer(),
            Text('单笔 ≥500 元需两级审批（店长→财务）', style: TextStyle(color: Colors.grey, fontSize: 12)),
          ],
        ),
        const SizedBox(height: 12),
        // Filter: status + technician name + finance no + reset
        Obx(() => Wrap(
          spacing: 12,
          runSpacing: 8,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            SizedBox(
              width: 150,
              child: DropdownButtonFormField<String>(
                value: ctrl.status.value.isEmpty ? null : ctrl.status.value,
                isExpanded: true,
                decoration: const InputDecoration(labelText: '状态', isDense: true),
                items: [
                  const DropdownMenuItem(value: '', child: Text('全部状态')),
                  ...WithdrawalController.statusOptions.entries
                      .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value))),
                ],
                onChanged: (v) => ctrl.applyFilter(st: v ?? ''),
              ),
            ),
            SizedBox(
              width: 180,
              child: TextField(
                decoration: const InputDecoration(labelText: '技师姓名', isDense: true),
                onSubmitted: (v) => ctrl.applyFilter(name: v.trim()),
              ),
            ),
            SizedBox(
              width: 200,
              child: TextField(
                decoration: const InputDecoration(labelText: '提现单号', isDense: true),
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
            if (ctrl.withdrawals.isEmpty) return const Center(child: Text('暂无数据'));

            return SingleChildScrollView(
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('提现单号')),
                  DataColumn(label: Text('技师')),
                  DataColumn(label: Text('申请金额')),
                  DataColumn(label: Text('手续费')),
                  DataColumn(label: Text('到账金额')),
                  DataColumn(label: Text('状态')),
                  DataColumn(label: Text('申请时间')),
                  DataColumn(label: Text('操作')),
                ],
                rows: ctrl.withdrawals.map((w) {
                  final status = w['status']?.toString() ?? '';
                  return DataRow(
                    cells: [
                      DataCell(Text(w['withdrawal_no'] ?? '-')),
                      DataCell(Text(
                          (w['technician'] as Map<dynamic, dynamic>?)?['real_name']?.toString() ??
                              '-')),
                      DataCell(Text(_money(w['amount']))),
                      DataCell(Text(_money(w['commission_fee']))),
                      DataCell(Text(_money(w['actual_amount']))),
                      DataCell(Text(
                        WithdrawalController.statusText(w),
                        style: TextStyle(
                          color: WithdrawalController.statusColor(w),
                          fontWeight: FontWeight.w600,
                        ),
                      )),
                      DataCell(Text(_shortDate(w['created_at']))),
                      DataCell(Row(mainAxisSize: MainAxisSize.min, children: [
                        if (status == 'pending') ...[
                          IconButton(
                            icon: const Icon(Icons.check_circle, size: 18, color: Colors.green),
                            tooltip: '审核通过',
                            onPressed: () => _showAudit(context, w, approve: true),
                          ),
                          IconButton(
                            icon: const Icon(Icons.cancel, size: 18, color: Colors.red),
                            tooltip: '驳回',
                            onPressed: () => _showAudit(context, w, approve: false),
                          ),
                        ],
                        if (status == 'approved')
                          IconButton(
                            icon: const Icon(Icons.done_all, size: 18, color: Colors.blue),
                            tooltip: '标记已完成',
                            onPressed: () => _confirmComplete(context, ctrl, w),
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

  Future<void> _showAudit(BuildContext context, dynamic w, {required bool approve}) async {
    final result = await showDialog<AuditResult>(
      context: context,
      builder: (_) => WithdrawalAuditDialog(withdrawal: w, approve: approve),
    );
    if (result == null) return;
    final ctrl = controller;
    if (result.approve) {
      await ctrl.approve(w['id'].toString(), remark: result.remark);
    } else {
      await ctrl.reject(w['id'].toString(), remark: result.remark);
    }
  }

  void _confirmComplete(BuildContext context, WithdrawalController ctrl, dynamic w) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('标记已完成'),
        content: Text('确定将提现单「${w['withdrawal_no']}」（${_money(w['amount'])}）标记为已完成吗？\n确认款项已到账后操作。'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('取消')),
          ElevatedButton(
            onPressed: () {
              ctrl.complete(w['id'].toString());
              Navigator.pop(context);
            },
            child: const Text('确认'),
          ),
        ],
      ),
    );
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
