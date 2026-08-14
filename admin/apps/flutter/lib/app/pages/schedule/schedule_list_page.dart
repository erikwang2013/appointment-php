/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'schedule_controller.dart';
import 'schedule_form_page.dart';

/// 技师排班管理列表页（排班维护 + 当日预约占位可见）
class ScheduleListPage extends GetView<ScheduleController> {
  const ScheduleListPage({super.key});

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<ScheduleController>()) {
      Get.put(ScheduleController(), permanent: false);
    }
    final ctrl = controller;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        Row(
          children: [
            const Text('排班管理', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const Spacer(),
            ElevatedButton.icon(
              onPressed: () => Get.to(() => const ScheduleFormPage())?.then((_) => ctrl.loadSchedules(reset: true)),
              icon: const Icon(Icons.add),
              label: const Text('新增排班'),
            ),
          ],
        ),
        const SizedBox(height: 12),
        // Filter: date range + technician + clear
        Obx(() => Wrap(
          spacing: 12,
          runSpacing: 8,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: [
            OutlinedButton.icon(
              onPressed: () => _pickDate(context, start: true),
              icon: const Icon(Icons.event, size: 18),
              label: Text(ctrl.dateStart.value.isEmpty ? '开始日期' : ctrl.dateStart.value),
            ),
            OutlinedButton.icon(
              onPressed: () => _pickDate(context, start: false),
              icon: const Icon(Icons.event, size: 18),
              label: Text(ctrl.dateEnd.value.isEmpty ? '结束日期' : ctrl.dateEnd.value),
            ),
            SizedBox(
              width: 200,
              child: DropdownButtonFormField<String>(
                value: ctrl.technicianId.value.isEmpty ? null : ctrl.technicianId.value,
                isExpanded: true,
                decoration: const InputDecoration(labelText: '技师', isDense: true),
                items: [
                  const DropdownMenuItem(value: '', child: Text('全部技师')),
                  ...ctrl.technicians.map((t) => DropdownMenuItem(
                        value: t['id'].toString(),
                        child: Text(t['real_name'] ?? ('技师#' + t['id'].toString())),
                      )),
                ],
                onChanged: (v) => ctrl.applyFilter(techId: v),
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
            if (ctrl.schedules.isEmpty) return const Center(child: Text('暂无数据'));

            return SingleChildScrollView(
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('日期')),
                  DataColumn(label: Text('技师')),
                  DataColumn(label: Text('时间段')),
                  DataColumn(label: Text('状态')),
                  DataColumn(label: Text('当日占用')),
                  DataColumn(label: Text('操作')),
                ],
                rows: ctrl.schedules.map((s) {
                  final id = s['id'].toString();
                  final slots = (s['time_slots'] as List<dynamic>? ?? [])
                      .map((slot) => '${slot['start']}-${slot['end']}')
                      .join('、');
                  final bookings = (s['bookings'] as List<dynamic>? ?? []);
                  return DataRow(
                    cells: [
                      DataCell(Text(s['date'] ?? '-')),
                      DataCell(Text(s['technician_name'] ?? '-')),
                      DataCell(Text(slots.isEmpty ? '-' : slots)),
                      DataCell(Chip(
                        label: Text(s['status'] == 0 ? '休息' : '可预约'),
                        color: WidgetStatePropertyAll(s['status'] == 0 ? Colors.orange.shade50 : Colors.green.shade50),
                      )),
                      DataCell(
                        bookings.isEmpty
                            ? const Text('无')
                            : TextButton(
                                onPressed: () => ctrl.showBookings(context, s),
                                child: Text('${bookings.length} 条预约'),
                              ),
                      ),
                      DataCell(Row(mainAxisSize: MainAxisSize.min, children: [
                        IconButton(
                          icon: const Icon(Icons.event_available, size: 18),
                          tooltip: '设为休息',
                          onPressed: () => _confirmSetRest(context, ctrl, s),
                        ),
                        IconButton(
                          icon: const Icon(Icons.edit, size: 18),
                          onPressed: () => Get.to(() => ScheduleFormPage(scheduleData: s))
                              ?.then((_) => ctrl.loadSchedules()),
                        ),
                        IconButton(
                          icon: const Icon(Icons.delete, size: 18, color: Colors.red),
                          onPressed: () => _confirmDelete(context, ctrl, s),
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

  Future<void> _pickDate(BuildContext context, {required bool start}) async {
    final ctrl = controller;
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2035),
    );
    if (picked == null) return;
    final value = '${picked.year.toString().padLeft(4, '0')}-'
        '${picked.month.toString().padLeft(2, '0')}-'
        '${picked.day.toString().padLeft(2, '0')}';
    await ctrl.applyFilter(start: start ? value : ctrl.dateStart.value, end: start ? ctrl.dateEnd.value : value);
  }

  void _confirmSetRest(BuildContext context, ScheduleController ctrl, dynamic schedule) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('设为休息'),
        content: Text('确定将「${schedule['technician_name']}」${schedule['date']} 设为休息吗？\n设为休息后该日不可预约（排班行保留）。'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('取消')),
          ElevatedButton(
            onPressed: () {
              ctrl.setRest(schedule['id'].toString());
              Navigator.pop(context);
            },
            child: const Text('确认'),
          ),
        ],
      ),
    );
  }

  void _confirmDelete(BuildContext context, ScheduleController ctrl, dynamic schedule) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('确认删除'),
        content: Text('确定删除「${schedule['technician_name']}」${schedule['date']} 的排班吗？\n仅删除排班行，不影响已有订单。'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('取消')),
          ElevatedButton(
            onPressed: () {
              ctrl.deleteSchedule(schedule['id'].toString());
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
