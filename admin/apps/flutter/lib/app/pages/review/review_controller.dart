// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 评价管理控制器：列表 / 评分·状态·关键词筛选 / 分页 / 审核 / 删除
class ReviewController extends GetxController {
  final reviews = <Map<String, dynamic>>[].obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15;
  final loading = false.obs;

  // 筛选条件
  final rating = 0.obs; // 0=全部
  final status = (-1).obs; // -1=全部, 0=隐藏, 1=可见
  final keyword = ''.obs;
  final keywordInput = ''.obs;

  @override
  void onInit() {
    super.onInit();
    fetchData();
  }

  Future<void> fetchData({bool reset = false}) async {
    if (loading.value) return;
    loading.value = true;
    try {
      if (reset) page.value = 1;
      final data = await ApiService.to.get(
        '/admin/reviews',
        params: {
          'page': page.value,
          'limit': limit,
          if (rating.value > 0) 'rating': rating.value,
          if (status.value >= 0) 'status': status.value,
          if (keyword.value.isNotEmpty) 'keyword': keyword.value,
        },
      );
      final list = (data['list'] as List? ?? [])
          .map((e) => Map<String, dynamic>.from(e as Map))
          .toList();
      if (reset) {
        reviews.assignAll(list);
      } else {
        reviews.addAll(list);
      }
      total.value = data['total'] ?? 0;
    } catch (e) {
      Get.snackbar('加载失败', e.toString());
    } finally {
      loading.value = false;
    }
  }

  /// 加载更多（分页）
  Future<void> loadMore() async {
    if (reviews.length >= total.value) return;
    page.value += 1;
    await fetchData();
  }

  /// 触发筛选
  void applyFilters() {
    keyword.value = keywordInput.value.trim();
    fetchData(reset: true);
  }

  void clearFilters() {
    rating.value = 0;
    status.value = -1;
    keyword.value = '';
    keywordInput.value = '';
    fetchData(reset: true);
  }

  /// 审核：show=恢复可见 / hide=隐藏
  Future<void> audit(Map<String, dynamic> item, String action) async {
    try {
      final id = item['id'];
      await ApiService.to.put(
        '/admin/reviews/$id/audit',
        data: {'action': action},
      );
      final idx = reviews.indexWhere((e) => e['id'] == id);
      if (idx >= 0) {
        reviews[idx]['status'] = action == 'show' ? 1 : 0;
        reviews.refresh();
      }
      Get.snackbar('成功', action == 'show' ? '已恢复可见' : '已隐藏');
    } catch (e) {
      Get.snackbar('操作失败', e.toString());
    }
  }

  /// 删除评价
  Future<void> remove(Map<String, dynamic> item) async {
    try {
      await ApiService.to.delete('/admin/reviews/${item['id']}');
      reviews.removeWhere((e) => e['id'] == item['id']);
      total.value -= 1;
      Get.snackbar('成功', '已删除');
    } catch (e) {
      Get.snackbar('操作失败', e.toString());
    }
  }

  /// 评分展示文本
  String statusText(int? s) => s == 1 ? '可见' : '隐藏';
}
