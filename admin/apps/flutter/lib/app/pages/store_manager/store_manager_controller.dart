// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 门店工作台控制器（admin 端店长工作台视图）
/// 契约对应：
///   GET /admin/stores?limit=100                       门店列表（store_id 为 hashid）
///   GET /admin/stores/workbench-overview?store_id=    门店概览（今日订单/营收/进行中/技师数/核销数）
///   GET /admin/orders?store_id=&status=&page=&limit=  门店订单列表
class StoreManagerController extends GetxController {
  final api = ApiService();

  final stores = <dynamic>[].obs;
  final selectedStoreId = ''.obs;
  final overview = Rxn<Map<String, dynamic>>();
  final orders = <dynamic>[].obs;

  final isLoading = false.obs;
  final loadingOverview = false.obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15.obs;
  final status = ''.obs; // 空 = 全部

  static const statusOptions = <String, String>{
    'pending': '待支付',
    'paid': '已支付',
    'confirmed': '待服务',
    'serving': '服务中',
    'completed': '已完成',
    'cancelled': '已取消',
    'refunding': '退款中',
    'refunded': '已退款',
  };

  @override
  void onInit() {
    super.onInit();
    loadStores();
  }

  Future<void> loadStores() async {
    try {
      final resp = await api.get('/admin/stores', params: {'limit': 100});
      final list = resp['data']['list'] as List<dynamic>? ?? [];
      stores.value = list;
      // 默认选中第一家门店并加载工作台数据
      if (list.isNotEmpty && selectedStoreId.value.isEmpty) {
        await selectStore((list.first['id'] as String?) ?? '');
      }
    } catch (e) {
      Get.snackbar('错误', '加载门店列表失败: $e');
    }
  }

  Future<void> selectStore(String? storeId) async {
    selectedStoreId.value = storeId ?? '';
    page.value = 1;
    status.value = '';
    await Future.wait([loadOverview(), loadOrders()]);
  }

  Future<void> loadOverview() async {
    if (selectedStoreId.value.isEmpty) return;
    loadingOverview.value = true;
    try {
      final resp = await api.get('/admin/stores/workbench-overview',
          params: {'store_id': selectedStoreId.value});
      overview.value = resp['data'] as Map<String, dynamic>;
    } catch (e) {
      overview.value = null;
      Get.snackbar('错误', '加载门店概览失败: $e');
    } finally {
      loadingOverview.value = false;
    }
  }

  Future<void> loadOrders({bool reset = false}) async {
    if (selectedStoreId.value.isEmpty) return;
    if (reset) page.value = 1;
    isLoading.value = true;
    try {
      final params = <String, dynamic>{
        'store_id': selectedStoreId.value,
        'page': page.value,
        'limit': limit.value,
      };
      if (status.value.isNotEmpty) params['status'] = status.value;
      final resp = await api.get('/admin/orders', params: params);
      orders.value = resp['data']['list'] as List<dynamic>;
      total.value = resp['data']['total'] as int;
    } catch (e) {
      Get.snackbar('错误', '加载门店订单失败: $e');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> applyStatus(String? st) async {
    status.value = st ?? '';
    await loadOrders(reset: true);
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

  /// 状态展示文案
  static String statusText(dynamic o) =>
      statusOptions[o?['status']] ?? (o?['status']?.toString() ?? '-');

  /// 状态标签颜色
  static Color statusColor(dynamic o) {
    switch (o?['status']) {
      case 'completed':
        return Colors.green;
      case 'pending':
      case 'refunding':
        return Colors.orange;
      case 'cancelled':
      case 'refunded':
        return Colors.red;
      case 'serving':
        return Colors.blue;
      default:
        return Colors.grey;
    }
  }
}
