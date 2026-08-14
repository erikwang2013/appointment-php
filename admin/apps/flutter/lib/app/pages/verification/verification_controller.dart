/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:get/get.dart';
import '../../services/api_service.dart';

class VerificationController extends GetxController {
  final api = ApiService();

  final records = <dynamic>[].obs;
  final isLoading = false.obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15.obs;
  final keyword = ''.obs;
  final verifyTypeFilter = Rx<String?>(null);

  @override
  void onInit() {
    super.onInit();
    loadRecords();
  }

  Future<void> loadRecords({bool reset = false}) async {
    if (reset) page.value = 1;
    isLoading.value = true;
    try {
      final params = <String, dynamic>{
        'page': page.value,
        'limit': limit.value,
      };
      if (keyword.value.isNotEmpty) params['order_no'] = keyword.value;
      if (verifyTypeFilter.value != null) params['verify_type'] = verifyTypeFilter.value;

      final resp = await api.get('/admin/order-verifications', params: params);
      records.value = resp['data']['list'] as List<dynamic>;
      total.value = resp['data']['total'] as int;
    } catch (e) {
      Get.snackbar('错误', '加载核销记录失败: $e');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> search(String kw) async {
    keyword.value = kw.trim();
    await loadRecords(reset: true);
  }

  Future<void> filterByType(String? type) async {
    verifyTypeFilter.value = type;
    await loadRecords(reset: true);
  }

  Future<void> nextPage() async {
    if (page.value * limit.value < total.value) {
      page.value++;
      await loadRecords();
    }
  }

  Future<void> prevPage() async {
    if (page.value > 1) {
      page.value--;
      await loadRecords();
    }
  }
}
