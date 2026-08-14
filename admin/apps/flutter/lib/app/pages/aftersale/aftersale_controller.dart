/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 售后（退换货）管理控制器
/// 契约对应 admin/app/admin/controller/AftersaleController.php：
///   GET  /admin/aftersales?page&limit&status&uid&order_no
///   POST /admin/aftersales/{id}/review  {action: approve|reject, remark}
/// 状态机：pending(待审核) → approved(已通过)/rejected(已驳回)；退款走既有订单退款接口由商家另行操作。
class AftersaleController extends GetxController {
  final api = ApiService();

  final aftersales = <dynamic>[].obs;
  final isLoading = false.obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15.obs;
  final status = ''.obs; // 空 = 全部
  final uid = ''.obs;
  final orderNo = ''.obs;

  static const statusOptions = <String, String>{
    'pending': '待审核',
    'approved': '已通过',
    'rejected': '已驳回',
    'completed': '已完成',
  };

  static const typeOptions = <String, String>{
    'refund': '仅退款',
    'exchange': '换货',
  };

  @override
  void onInit() {
    super.onInit();
    loadAftersales();
  }

  Future<void> loadAftersales({bool reset = false}) async {
    if (reset) page.value = 1;
    isLoading.value = true;
    try {
      final params = <String, dynamic>{
        'page': page.value,
        'limit': limit.value,
      };
      if (status.value.isNotEmpty) params['status'] = status.value;
      if (uid.value.isNotEmpty) params['uid'] = uid.value;
      if (orderNo.value.isNotEmpty) params['order_no'] = orderNo.value;

      final resp = await api.get('/admin/aftersales', params: params);
      aftersales.value = resp['data']['list'] as List<dynamic>;
      total.value = resp['data']['total'] as int;
    } catch (e) {
      Get.snackbar('错误', '加载售后列表失败: $e');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> applyFilter({String? st, String? uid, String? no}) async {
    if (st != null) status.value = st;
    if (uid != null) this.uid.value = uid;
    if (no != null) orderNo.value = no;
    await loadAftersales(reset: true);
  }

  void clearFilter() {
    status.value = '';
    uid.value = '';
    orderNo.value = '';
    loadAftersales(reset: true);
  }

  Future<void> nextPage() async {
    if (page.value * limit.value < total.value) {
      page.value++;
      await loadAftersales();
    }
  }

  Future<void> prevPage() async {
    if (page.value > 1) {
      page.value--;
      await loadAftersales();
    }
  }

  /// 审核（approve 备注可选；reject 备注必填）
  Future<bool> review(String id, {required String action, required String remark}) async {
    try {
      await api.post('/admin/aftersales/$id/review', data: {'action': action, 'remark': remark});
      await loadAftersales();
      Get.snackbar('成功', action == 'approve' ? '审核通过' : '已驳回');
      return true;
    } catch (e) {
      Get.snackbar('错误', '审核失败: $e');
      return false;
    }
  }

  static String statusText(dynamic a) =>
      statusOptions[a?['status']] ?? (a?['status']?.toString() ?? '-');

  static String typeText(dynamic a) =>
      typeOptions[a?['type']] ?? (a?['type']?.toString() ?? '-');

  static Color statusColor(dynamic a) {
    switch (a?['status']) {
      case 'pending':
        return Colors.orange;
      case 'approved':
        return Colors.green;
      case 'rejected':
        return Colors.red;
      case 'completed':
        return Colors.blue;
      default:
        return Colors.grey;
    }
  }
}
