/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:get/get.dart';
import '../../services/api_service.dart';

/// FAQ 管理控制器
/// 契约对应 admin/app/admin/controller/FaqController.php：
///   GET    /admin/faqs?page&limit&keyword&status
///   POST   /admin/faqs {title, content, sort, status}
///   PUT    /admin/faqs/{id}（title/content/sort/status）
///   DELETE /admin/faqs/{id} {password}（需管理员密码二次确认）
/// 后端无分类字段（列表仅 keyword/status 筛选）；sortAll 端点未注册路由，前端不调用。
/// 列表项：id(hashid), title, content, sort, status, created_at, updated_at
class FaqController extends GetxController {
  final api = ApiService();

  final faqs = <dynamic>[].obs;
  final isLoading = false.obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15.obs;
  final keyword = ''.obs;
  final statusFilter = Rx<int?>(null);

  @override
  void onInit() {
    super.onInit();
    loadFaqs();
  }

  Future<void> loadFaqs({bool reset = false}) async {
    if (reset) page.value = 1;
    isLoading.value = true;
    try {
      final params = <String, dynamic>{
        'page': page.value,
        'limit': limit.value,
      };
      if (keyword.value.isNotEmpty) params['keyword'] = keyword.value;
      if (statusFilter.value != null) params['status'] = statusFilter.value;

      final resp = await api.get('/admin/faqs', params: params);
      faqs.value = resp['data']['list'] as List<dynamic>;
      total.value = resp['data']['total'] as int;
    } catch (e) {
      Get.snackbar('错误', '加载 FAQ 列表失败: $e');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> search(String kw) async {
    keyword.value = kw.trim();
    await loadFaqs(reset: true);
  }

  Future<void> filterByStatus(int? status) async {
    statusFilter.value = status;
    await loadFaqs(reset: true);
  }

  Future<void> nextPage() async {
    if (page.value * limit.value < total.value) {
      page.value++;
      await loadFaqs();
    }
  }

  Future<void> prevPage() async {
    if (page.value > 1) {
      page.value--;
      await loadFaqs();
    }
  }

  Future<bool> deleteFaq(String id, String password) async {
    try {
      await api.delete('/admin/faqs/$id', data: {'password': password});
      await loadFaqs();
      Get.snackbar('成功', 'FAQ 删除成功');
      return true;
    } catch (e) {
      Get.snackbar('错误', '删除失败: $e');
      return false;
    }
  }
}
