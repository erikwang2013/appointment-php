/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 技师提现管理控制器
/// 契约对应 admin/app/admin/controller/WithdrawalController.php：
///   GET  /admin/withdrawals?page&limit&status&technician_name&finance_no&date_start&date_end
///   GET  /admin/withdrawals/{id}
///   POST /admin/withdrawals/{id}/approve   {remark}
///   POST /admin/withdrawals/{id}/reject    {remark}
///   POST /admin/withdrawals/{id}/complete
/// 状态机：pending(待审核) → approved(已通过) → completed(已完成)；rejected(已驳回)/failed(转账失败)。
/// 金额 ≥500 为两级审批（店长 → 财务），<500 店长审批后自动完成并转账。
class WithdrawalController extends GetxController {
  final api = ApiService();

  final withdrawals = <dynamic>[].obs;
  final isLoading = false.obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15.obs;
  final status = ''.obs; // 空 = 全部
  final technicianName = ''.obs;
  final financeNo = ''.obs;
  final dateStart = ''.obs;
  final dateEnd = ''.obs;

  static const statusOptions = <String, String>{
    'pending': '待审核',
    'approved': '已通过',
    'rejected': '已驳回',
    'completed': '已完成',
    'failed': '转账失败',
  };

  @override
  void onInit() {
    super.onInit();
    loadWithdrawals();
  }

  Future<void> loadWithdrawals({bool reset = false}) async {
    if (reset) page.value = 1;
    isLoading.value = true;
    try {
      final params = <String, dynamic>{
        'page': page.value,
        'limit': limit.value,
      };
      if (status.value.isNotEmpty) params['status'] = status.value;
      if (technicianName.value.isNotEmpty) params['technician_name'] = technicianName.value;
      if (financeNo.value.isNotEmpty) params['finance_no'] = financeNo.value;
      if (dateStart.value.isNotEmpty) params['date_start'] = dateStart.value;
      if (dateEnd.value.isNotEmpty) params['date_end'] = dateEnd.value;

      final resp = await api.get('/admin/withdrawals', params: params);
      withdrawals.value = resp['data']['list'] as List<dynamic>;
      total.value = resp['data']['total'] as int;
    } catch (e) {
      Get.snackbar('错误', '加载提现列表失败: $e');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> applyFilter({String? st, String? name, String? no}) async {
    if (st != null) status.value = st;
    if (name != null) technicianName.value = name;
    if (no != null) financeNo.value = no;
    await loadWithdrawals(reset: true);
  }

  void clearFilter() {
    status.value = '';
    technicianName.value = '';
    financeNo.value = '';
    dateStart.value = '';
    dateEnd.value = '';
    loadWithdrawals(reset: true);
  }

  Future<void> nextPage() async {
    if (page.value * limit.value < total.value) {
      page.value++;
      await loadWithdrawals();
    }
  }

  Future<void> prevPage() async {
    if (page.value > 1) {
      page.value--;
      await loadWithdrawals();
    }
  }

  /// 审核通过（备注可选，默认沿用原备注）
  Future<bool> approve(String id, {String remark = ''}) async {
    try {
      await api.post('/admin/withdrawals/$id/approve', data: {'remark': remark});
      await loadWithdrawals();
      Get.snackbar('成功', '审核通过');
      return true;
    } catch (e) {
      Get.snackbar('错误', '审核失败: $e');
      return false;
    }
  }

  /// 驳回（备注必填）
  Future<bool> reject(String id, {required String remark}) async {
    try {
      await api.post('/admin/withdrawals/$id/reject', data: {'remark': remark});
      await loadWithdrawals();
      Get.snackbar('成功', '已驳回');
      return true;
    } catch (e) {
      Get.snackbar('错误', '驳回失败: $e');
      return false;
    }
  }

  /// 标记已完成（款项已到账）
  Future<bool> complete(String id) async {
    try {
      await api.post('/admin/withdrawals/$id/complete');
      await loadWithdrawals();
      Get.snackbar('成功', '已标记完成');
      return true;
    } catch (e) {
      Get.snackbar('错误', '操作失败: $e');
      return false;
    }
  }

  /// 状态展示文案
  static String statusText(dynamic w) =>
      statusOptions[w?['status']] ?? (w?['status']?.toString() ?? '-');

  /// 状态标签颜色
  static Color statusColor(dynamic w) {
    switch (w?['status']) {
      case 'pending':
        return Colors.orange;
      case 'approved':
        return Colors.green;
      case 'rejected':
      case 'failed':
        return Colors.red;
      case 'completed':
        return Colors.blue;
      default:
        return Colors.grey;
    }
  }
}
