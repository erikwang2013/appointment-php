/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'member_controller.dart';

/// 会员管理列表页（只读：列表 + 详情，后端无新增/编辑路由）
class MemberListPage extends GetView<MemberController> {
  const MemberListPage({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<MemberController>()) {
      Get.put(MemberController(), permanent: false);
    }
    final ctrl = controller;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        const Row(
          children: [
            Text('会员管理', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
          ],
        ),
        const SizedBox(height: 12),
        // Search
        Row(
          children: [
            SizedBox(
              width: 250,
              child: TextField(
                decoration: const InputDecoration(hintText: '搜索会员昵称', prefixIcon: Icon(Icons.search), isDense: true),
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
            if (ctrl.members.isEmpty) return const Center(child: Text('暂无数据'));

            return SingleChildScrollView(
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('昵称')),
                  DataColumn(label: Text('手机号')),
                  DataColumn(label: Text('等级')),
                  DataColumn(label: Text('累计消费')),
                  DataColumn(label: Text('订单数')),
                  DataColumn(label: Text('有效卡数')),
                  DataColumn(label: Text('注册时间')),
                  DataColumn(label: Text('操作')),
                ],
                rows: ctrl.members.map((m) {
                  return DataRow(
                    cells: [
                      DataCell(Text(m['nickname'] ?? '-')),
                      DataCell(Text(m['phone'] ?? '-')),
                      DataCell(Text(m['member_level']?.toString() ?? '-')),
                      DataCell(Text(m['total_spent']?.toString() ?? '-')),
                      DataCell(Text(m['order_count']?.toString() ?? '-')),
                      DataCell(Text(m['member_cards_count']?.toString() ?? '-')),
                      DataCell(Text(m['created_at'] ?? '-')),
                      DataCell(Row(mainAxisSize: MainAxisSize.min, children: [
                        IconButton(
                          icon: const Icon(Icons.visibility, size: 18),
                          tooltip: '详情',
                          onPressed: () => _showDetail(context, ctrl, m),
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

  /// 会员详情：用户信息 + 有效会员卡 + 订单历史
  Future<void> _showDetail(BuildContext context, MemberController ctrl, dynamic member) async {
    final detail = await ctrl.loadDetail(member['id'].toString());
    if (detail == null || !context.mounted) return;

    final user = detail['user'] is Map ? detail['user'] as Map : const {};
    final orders = detail['orders'] is Map ? detail['orders'] as Map : const {};
    final orderList = (orders['list'] as List<dynamic>?) ?? [];
    final cards = (detail['active_cards'] as List<dynamic>?) ?? [];

    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('会员详情 - ${user['nickname'] ?? '-'}'),
        content: SizedBox(
          width: 640,
          child: ListView(
            shrinkWrap: true,
            children: [
              // 基本信息
              Text('昵称：${user['nickname'] ?? '-'}    手机号：${user['phone'] ?? '-'}    等级：${user['member_level'] ?? '-'}'),
              const SizedBox(height: 4),
              Text('累计消费：${user['total_spent'] ?? '-'}    订单数：${(orders['total'] ?? 0)}'),
              const SizedBox(height: 12),
              // 有效会员卡
              Text('有效会员卡（${cards.length}）', style: Theme.of(context).textTheme.titleSmall),
              const SizedBox(height: 4),
              if (cards.isEmpty)
                const Text('无有效会员卡')
              else
                DataTable(
                  columns: const [
                    DataColumn(label: Text('卡种')),
                    DataColumn(label: Text('状态')),
                    DataColumn(label: Text('剩余次数')),
                    DataColumn(label: Text('到期时间')),
                  ],
                  rows: cards.map((c) {
                    final card = c['member_card'] is Map ? c['member_card'] as Map : const {};
                    final remain = ((c['total_times'] ?? 0) as num) - ((c['used_times'] ?? 0) as num);
                    return DataRow(cells: [
                      DataCell(Text(card['name'] ?? '-')),
                      DataCell(Text(MemberController.cardStatusLabel(c['status']))),
                      DataCell(Text('$remain/${c['total_times'] ?? 0}')),
                      DataCell(Text(c['end_at'] ?? '-')),
                    ]);
                  }).toList(),
                ),
              const SizedBox(height: 12),
              // 订单历史
              Text('订单历史（${orders['total'] ?? 0}，仅显示最近 ${orderList.length} 条）', style: Theme.of(context).textTheme.titleSmall),
              const SizedBox(height: 4),
              if (orderList.isEmpty)
                const Text('暂无订单')
              else
                DataTable(
                  columns: const [
                    DataColumn(label: Text('订单号')),
                    DataColumn(label: Text('金额')),
                    DataColumn(label: Text('状态')),
                    DataColumn(label: Text('下单时间')),
                  ],
                  rows: orderList.map((o) => DataRow(cells: [
                        DataCell(Text(o['order_no'] ?? '-')),
                        DataCell(Text(o['paid_amount']?.toString() ?? '-')),
                        DataCell(Text(o['status'] ?? '-')),
                        DataCell(Text(o['created_at'] ?? '-')),
                      ])).toList(),
                ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('关闭')),
        ],
      ),
    );
  }
}
