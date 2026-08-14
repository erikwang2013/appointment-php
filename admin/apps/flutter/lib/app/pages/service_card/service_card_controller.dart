/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 卡项设计控制器
/// 契约对应 admin/app/admin/controller/ServiceCardController.php：
///   GET    /admin/service-cards?page&limit&keyword
///   POST   /admin/service-cards {name, type(package|combo), services[], product_ids[],
///                                total_price, handwork_total, commission_amount, sales_commission, status, description}
///   PUT    /admin/service-cards/{id}（部分字段）
///   DELETE /admin/service-cards/{id}
/// 列表项：id, name, type, services[{service_id,times,handwork_fee,service_detail}],
///         product_ids[{product_id,qty,product_detail}], total_price, handwork_total,
///         commission_amount, sales_commission, status, description, created_at, updated_at
class ServiceCardController extends GetxController {
  final api = ApiService();

  final cards = <dynamic>[].obs;
  final isLoading = false.obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15.obs;
  final keyword = ''.obs;

  @override
  void onInit() {
    super.onInit();
    loadCards();
  }

  Future<void> loadCards({bool reset = false}) async {
    if (reset) page.value = 1;
    isLoading.value = true;
    try {
      final params = <String, dynamic>{
        'page': page.value,
        'limit': limit.value,
      };
      if (keyword.value.isNotEmpty) params['keyword'] = keyword.value;

      final resp = await api.get('/admin/service-cards', params: params);
      cards.value = resp['data']['list'] as List<dynamic>;
      total.value = resp['data']['total'] as int;
    } catch (e) {
      Get.snackbar('错误', '加载卡项列表失败: $e');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> search(String kw) async {
    keyword.value = kw.trim();
    await loadCards(reset: true);
  }

  Future<void> nextPage() async {
    if (page.value * limit.value < total.value) {
      page.value++;
      await loadCards();
    }
  }

  Future<void> prevPage() async {
    if (page.value > 1) {
      page.value--;
      await loadCards();
    }
  }

  Future<bool> deleteCard(String id, String password) async {
    try {
      await api.delete('/admin/service-cards/$id', data: {'password': password});
      await loadCards();
      Get.snackbar('成功', '卡项删除成功');
      return true;
    } catch (e) {
      Get.snackbar('错误', '删除失败: $e');
      return false;
    }
  }

  static String typeLabel(String? type) {
    if (type == 'package') return '套餐';
    if (type == 'combo') return '组合卡';
    return type ?? '-';
  }

  static int listLength(dynamic value) {
    final list = value as List<dynamic>?;
    return list?.length ?? 0;
  }
}
