/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 优惠券管理控制器
/// 契约对应 admin/app/admin/controller/CouponController.php：
///   GET    /admin/coupons?page&limit&keyword&status
///   POST   /admin/coupons {name, type(fixed|percent), amount, min_amount, total_qty, start_at, end_at, status}
///   PUT    /admin/coupons/{id}（部分字段）
///   DELETE /admin/coupons/{id} {password}（需管理员密码二次确认）
/// 列表项：id(hashid), name, type, amount, min_amount, total_qty, remain_qty,
///         start_at, end_at, status, created_at, updated_at
class CouponController extends GetxController {
  final api = ApiService();

  final coupons = <dynamic>[].obs;
  final isLoading = false.obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15.obs;
  final keyword = ''.obs;
  final statusFilter = Rx<int?>(null);

  @override
  void onInit() {
    super.onInit();
    loadCoupons();
  }

  Future<void> loadCoupons({bool reset = false}) async {
    if (reset) page.value = 1;
    isLoading.value = true;
    try {
      final params = <String, dynamic>{
        'page': page.value,
        'limit': limit.value,
      };
      if (keyword.value.isNotEmpty) params['keyword'] = keyword.value;
      if (statusFilter.value != null) params['status'] = statusFilter.value;

      final resp = await api.get('/admin/coupons', params: params);
      coupons.value = resp['data']['list'] as List<dynamic>;
      total.value = resp['data']['total'] as int;
    } catch (e) {
      Get.snackbar('错误', '加载优惠券列表失败: $e');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> search(String kw) async {
    keyword.value = kw.trim();
    await loadCoupons(reset: true);
  }

  Future<void> filterByStatus(int? status) async {
    statusFilter.value = status;
    await loadCoupons(reset: true);
  }

  Future<void> nextPage() async {
    if (page.value * limit.value < total.value) {
      page.value++;
      await loadCoupons();
    }
  }

  Future<void> prevPage() async {
    if (page.value > 1) {
      page.value--;
      await loadCoupons();
    }
  }

  Future<bool> deleteCoupon(String id, String password) async {
    try {
      await api.delete('/admin/coupons/$id', data: {'password': password});
      await loadCoupons();
      Get.snackbar('成功', '优惠券删除成功');
      return true;
    } catch (e) {
      Get.snackbar('错误', '删除失败: $e');
      return false;
    }
  }

  static String typeLabel(String? type) {
    if (type == 'fixed') return '固定金额';
    if (type == 'percent') return '折扣';
    return type ?? '-';
  }
}
