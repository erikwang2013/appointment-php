/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 服务管理控制器
/// 契约对应 admin/app/admin/controller/ServiceController.php：
///   GET    /admin/services?page&limit&keyword&category_id&status
///   POST   /admin/services            {category_id, name, description, cover_image, price, original_price, duration, sort, status}
///   PUT    /admin/services/{id}       同上（部分字段可选）
///   DELETE /admin/services/{id}       {password} 管理员密码二次确认
/// 列表项：id=hashid；category_id=原始数字（后端未编码，仅编码顶层 id）；
/// 嵌套 category：{id: 原始数字, name}，列表分类列用 category.name 展示。
class ServiceController extends GetxController {
  final api = ApiService();

  final services = <dynamic>[].obs;
  final categories = <dynamic>[].obs;
  final categoriesLoading = false.obs;
  final isLoading = false.obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15.obs;
  final keyword = ''.obs;
  final categoryFilter = Rx<String?>(null);
  final statusFilter = Rx<int?>(null);

  @override
  void onInit() {
    super.onInit();
    loadServices();
    loadCategories();
  }

  Future<void> loadServices({bool reset = false}) async {
    if (reset) page.value = 1;
    isLoading.value = true;
    try {
      final params = <String, dynamic>{
        'page': page.value,
        'limit': limit.value,
      };
      if (keyword.value.isNotEmpty) params['keyword'] = keyword.value;
      if (categoryFilter.value != null && categoryFilter.value!.isNotEmpty) {
        params['category_id'] = categoryFilter.value;
      }
      if (statusFilter.value != null) params['status'] = statusFilter.value;

      final resp = await api.get('/admin/services', params: params);
      services.value = resp['data']['list'] as List<dynamic>;
      total.value = resp['data']['total'] as int;
    } catch (e) {
      Get.snackbar('错误', '加载服务列表失败: $e');
    } finally {
      isLoading.value = false;
    }
  }

  /// 加载服务分类（树形接口，扁平化为带缩进的选项列表）
  Future<void> loadCategories() async {
    categoriesLoading.value = true;
    try {
      final resp = await api.get('/admin/service-categories', params: {'page': 1, 'limit': 100});
      final tree = resp['data']['list'] as List<dynamic>? ?? [];
      categories.value = _flattenTree(tree, 0);
    } catch (e) {
      Get.snackbar('错误', '加载服务分类失败: $e');
    } finally {
      categoriesLoading.value = false;
    }
  }

  List<dynamic> _flattenTree(List<dynamic> nodes, int depth) {
    final result = <dynamic>[];
    for (final node in nodes) {
      final map = Map<String, dynamic>.from(node as Map);
      final children = map['children'] as List<dynamic>? ?? [];
      map['name'] = '${List.filled(depth, '  ').join()}${map['name'] ?? ''}';
      result.add(map);
      result.addAll(_flattenTree(children, depth + 1));
    }
    return result;
  }

  Future<void> search(String kw) async {
    keyword.value = kw.trim();
    await loadServices(reset: true);
  }

  Future<void> filterByCategory(String? categoryId) async {
    categoryFilter.value = (categoryId == null || categoryId.isEmpty) ? null : categoryId;
    await loadServices(reset: true);
  }

  Future<void> filterByStatus(int? status) async {
    statusFilter.value = status;
    await loadServices(reset: true);
  }

  Future<void> nextPage() async {
    if (page.value * limit.value < total.value) {
      page.value++;
      await loadServices();
    }
  }

  Future<void> prevPage() async {
    if (page.value > 1) {
      page.value--;
      await loadServices();
    }
  }

  Future<bool> deleteService(String id, String password) async {
    try {
      await api.delete('/admin/services/$id', data: {'password': password});
      await loadServices();
      Get.snackbar('成功', '服务删除成功');
      return true;
    } catch (e) {
      Get.snackbar('错误', '删除失败: $e');
      return false;
    }
  }
}
