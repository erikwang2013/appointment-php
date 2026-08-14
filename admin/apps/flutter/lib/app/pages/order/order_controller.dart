/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 订单管理控制器
/// 契约对应 admin/app/admin/controller/AppointmentOrderController.php：
///   GET /admin/appointment-orders?page&limit&order_no&uid&status&date_start&date_end
///   GET /admin/appointment-orders/{id}   详情（user/technician/store/items/payment/review/verification）
/// 订单状态为字符串：pending/paid/confirmed/serving/completed/cancelled/refunding/refunded。
/// 注意：admin 端 show() 未附加 refund_status/refund_amount/refunded_at
/// （service 端 OrderController 才有），详情页对退款字段做空值兜底。
class OrderController extends GetxController {
  final api = ApiService();

  final orders = <dynamic>[].obs;
  final isLoading = false.obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15.obs;
  final keyword = ''.obs;
  final uid = ''.obs;
  final statusFilter = Rx<String?>(null);
  final dateStart = ''.obs;
  final dateEnd = ''.obs;
  final detail = Rx<Map<String, dynamic>?>(null);
  final detailLoading = false.obs;

  @override
  void onInit() {
    super.onInit();
    loadOrders();
  }

  Future<void> loadOrders({bool reset = false}) async {
    if (reset) page.value = 1;
    isLoading.value = true;
    try {
      final params = <String, dynamic>{
        'page': page.value,
        'limit': limit.value,
      };
      if (keyword.value.isNotEmpty) params['order_no'] = keyword.value;
      if (uid.value.isNotEmpty) params['uid'] = uid.value;
      if (statusFilter.value != null && statusFilter.value!.isNotEmpty) {
        params['status'] = statusFilter.value;
      }
      if (dateStart.value.isNotEmpty) params['date_start'] = dateStart.value;
      if (dateEnd.value.isNotEmpty) params['date_end'] = dateEnd.value;

      final resp = await api.get('/admin/appointment-orders', params: params);
      orders.value = resp['data']['list'] as List<dynamic>;
      total.value = resp['data']['total'] as int;
    } catch (e) {
      Get.snackbar('错误', '加载订单列表失败: $e');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> search(String kw) async {
    keyword.value = kw.trim();
    await loadOrders(reset: true);
  }

  Future<void> searchUid(String kw) async {
    uid.value = kw.trim();
    await loadOrders(reset: true);
  }

  Future<void> filterByStatus(String? status) async {
    statusFilter.value = (status == null || status.isEmpty) ? null : status;
    await loadOrders(reset: true);
  }

  Future<void> applyDate({String? start, String? end}) async {
    if (start != null) dateStart.value = start;
    if (end != null) dateEnd.value = end;
    await loadOrders(reset: true);
  }

  void clearFilter() {
    keyword.value = '';
    uid.value = '';
    statusFilter.value = null;
    dateStart.value = '';
    dateEnd.value = '';
    loadOrders(reset: true);
  }

  Future<void> nextPage() async {
    if (page.value * limit.value < total.value) {
      page.value++;
      await loadOrders();
    }
  }

  Future<void> prevPage() async {
    if (page.value > 1) {
      page.value--;
      await loadOrders();
    }
  }

  Future<void> loadDetail(String id) async {
    detailLoading.value = true;
    try {
      final resp = await api.get('/admin/appointment-orders/$id');
      detail.value = Map<String, dynamic>.from(resp['data'] as Map);
    } catch (e) {
      Get.snackbar('错误', '加载订单详情失败: $e');
    } finally {
      detailLoading.value = false;
    }
  }
}

/// 订单状态文案
String orderStatusLabel(String? status) {
  switch (status) {
    case 'pending':
      return '待支付';
    case 'paid':
      return '已支付';
    case 'confirmed':
      return '已确认';
    case 'serving':
      return '服务中';
    case 'completed':
      return '已完成';
    case 'cancelled':
      return '已取消';
    case 'refunding':
      return '退款中';
    case 'refunded':
      return '已退款';
    default:
      return (status == null || status.isEmpty) ? '-' : status;
  }
}

/// 订单类型文案：appointment=预约服务 product=产品购买
String orderTypeLabel(String? type) {
  switch (type) {
    case 'appointment':
      return '预约服务';
    case 'product':
      return '产品购买';
    default:
      return (type == null || type.isEmpty) ? '-' : type;
  }
}

/// 支付方式文案：wechat=微信支付
String payTypeLabel(String? type) {
  switch (type) {
    case 'wechat':
      return '微信支付';
    default:
      return (type == null || type.isEmpty) ? '-' : type;
  }
}

/// 支付状态文案：pending/success/failed/closed
String payStatusLabel(String? status) {
  switch (status) {
    case 'pending':
      return '待支付';
    case 'success':
      return '成功';
    case 'failed':
      return '失败';
    case 'closed':
      return '已关闭';
    default:
      return (status == null || status.isEmpty) ? '-' : status;
  }
}

/// 退款状态文案：pending/success/failed
String refundStatusLabel(String? status) {
  switch (status) {
    case 'pending':
      return '处理中';
    case 'success':
      return '退款成功';
    case 'failed':
      return '退款失败';
    default:
      return (status == null || status.isEmpty) ? '-' : status;
  }
}

/// 订单明细项目类型：service=服务 product=产品
String itemTargetTypeLabel(String? type) {
  switch (type) {
    case 'service':
      return '服务';
    case 'product':
      return '产品';
    default:
      return (type == null || type.isEmpty) ? '-' : type;
  }
}

/// 核销方式：scan=扫码 self=自行核销
String verifyTypeLabel(String? type) {
  switch (type) {
    case 'scan':
      return '扫码核销';
    case 'self':
      return '自行核销';
    default:
      return (type == null || type.isEmpty) ? '-' : type;
  }
}

/// 订单明细规格展示（兼容数组/对象/字符串）
String specText(dynamic spec) {
  if (spec == null) return '-';
  if (spec is List) {
    final parts = (spec as List).map((e) => e.toString()).where((e) => e.isNotEmpty).toList();
    return parts.isEmpty ? '-' : parts.join('、');
  }
  if (spec is Map) {
    final parts = (spec as Map).values.map((e) => e.toString()).where((e) => e.isNotEmpty).toList();
    return parts.isEmpty ? '-' : parts.join('、');
  }
  final text = spec.toString().trim();
  return text.isEmpty ? '-' : text;
}
