/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 排班管理控制器
/// 契约对应 admin/app/admin/controller/TechnicianScheduleController.php：
///   GET    /admin/schedules?page&limit&date_start&date_end&technician_id
///   POST   /admin/schedules           {technician_id, date, time_slots, status?}
///   DELETE /admin/schedules/{id}
///   PUT    /admin/schedules/{id}/rest
/// 列表项含 bookings（当日预约占用），实现排班维护 + 占位可见闭环。
class ScheduleController extends GetxController {
  final api = ApiService();

  final schedules = <dynamic>[].obs;
  final isLoading = false.obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15.obs;
  final dateStart = ''.obs;
  final dateEnd = ''.obs;
  final technicianId = ''.obs;
  final technicians = <dynamic>[].obs;
  final techniciansLoading = false.obs;

  @override
  void onInit() {
    super.onInit();
    loadSchedules();
    loadTechnicians();
  }

  Future<void> loadSchedules({bool reset = false}) async {
    if (reset) page.value = 1;
    isLoading.value = true;
    try {
      final params = <String, dynamic>{
        'page': page.value,
        'limit': limit.value,
      };
      if (dateStart.value.isNotEmpty) params['date_start'] = dateStart.value;
      if (dateEnd.value.isNotEmpty) params['date_end'] = dateEnd.value;
      if (technicianId.value.isNotEmpty) params['technician_id'] = technicianId.value;

      final resp = await api.get('/admin/schedules', params: params);
      schedules.value = resp['data']['list'] as List<dynamic>;
      total.value = resp['data']['total'] as int;
    } catch (e) {
      Get.snackbar('错误', '加载排班列表失败: $e');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> loadTechnicians() async {
    techniciansLoading.value = true;
    try {
      final resp = await api.get('/admin/technicians', params: {'page': 1, 'limit': 100});
      technicians.value = resp['data']['list'] as List<dynamic>;
    } catch (e) {
      Get.snackbar('错误', '加载技师列表失败: $e');
    } finally {
      techniciansLoading.value = false;
    }
  }

  Future<void> applyFilter({String? start, String? end, String? techId}) async {
    if (start != null) dateStart.value = start;
    if (end != null) dateEnd.value = end;
    if (techId != null) technicianId.value = techId;
    await loadSchedules(reset: true);
  }

  void clearFilter() {
    dateStart.value = '';
    dateEnd.value = '';
    technicianId.value = '';
    loadSchedules(reset: true);
  }

  Future<void> nextPage() async {
    if (page.value * limit.value < total.value) {
      page.value++;
      await loadSchedules();
    }
  }

  Future<void> prevPage() async {
    if (page.value > 1) {
      page.value--;
      await loadSchedules();
    }
  }

  Future<bool> deleteSchedule(String id) async {
    try {
      await api.delete('/admin/schedules/$id');
      await loadSchedules();
      Get.snackbar('成功', '排班删除成功');
      return true;
    } catch (e) {
      Get.snackbar('错误', '删除失败: $e');
      return false;
    }
  }

  Future<bool> setRest(String id) async {
    try {
      await api.put('/admin/schedules/$id/rest');
      await loadSchedules();
      Get.snackbar('成功', '已设为休息');
      return true;
    } catch (e) {
      Get.snackbar('错误', '操作失败: $e');
      return false;
    }
  }

  /// 查看某排班当日预约占用
  void showBookings(BuildContext context, dynamic schedule) {
    final bookings = (schedule['bookings'] as List<dynamic>?) ?? [];
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('${schedule['date']} 预约占用'),
        content: bookings.isEmpty
            ? const Text('当日暂无预约占用')
            : SizedBox(
                width: 480,
                child: DataTable(
                  columns: const [
                    DataColumn(label: Text('订单号')),
                    DataColumn(label: Text('客户')),
                    DataColumn(label: Text('服务时间')),
                    DataColumn(label: Text('状态')),
                  ],
                  rows: bookings.map((b) => DataRow(cells: [
                        DataCell(Text(b['order_no'] ?? '-')),
                        DataCell(Text(b['user_name'] ?? '-')),
                        DataCell(Text(b['service_time'] ?? '-')),
                        DataCell(Text(b['status'] ?? '-')),
                      ])).toList(),
                ),
              ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('关闭')),
        ],
      ),
    );
  }
}
