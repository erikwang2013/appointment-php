/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 会员卡定义管理控制器（S10）
/// 契约对应 admin/app/admin/controller/MemberCardController.php：
///   GET  /admin/member-cards?page&limit&keyword&status
///   POST /admin/member-cards
///   PUT  /admin/member-cards/{id}
///   DELETE /admin/member-cards/{id}
/// 列表项：id(hashid), name, type(month/vip/times), price,
///         duration_days, total_times, services(JSON 数组), status(0/1), created_by
class CardDefinitionController extends GetxController {
  final api = ApiService();

  final cards = <dynamic>[].obs;
  final isLoading = false.obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15.obs;
  final keyword = ''.obs;

  static const typeLabels = {'month': '月卡', 'vip': '权益卡', 'times': '次卡'};

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
      final resp = await api.get('/admin/member-cards', params: params);
      cards.value = resp['data']['list'] as List<dynamic>;
      total.value = resp['data']['total'] as int;
    } catch (e) {
      Get.snackbar('错误', '加载会员卡定义失败: $e');
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

  /// 创建或更新（id 为空则创建）
  Future<bool> save(Map<String, dynamic> form, {String? id}) async {
    try {
      final resp = id == null
          ? await api.post('/admin/member-cards', data: form)
          : await api.put('/admin/member-cards/$id', data: form);
      Get.snackbar('成功', resp['message'] ?? '保存成功');
      await loadCards(reset: true);
      return true;
    } catch (e) {
      Get.snackbar('错误', '保存失败: $e');
      return false;
    }
  }

  /// 上架/下架
  Future<void> toggleStatus(dynamic card, int status) async {
    try {
      final id = card['id'].toString();
      final resp = await api.put('/admin/member-cards/$id', data: {
        'status': status,
        ...Map<String, dynamic>.from(card as Map)
          ..removeWhere((k, v) => !['name', 'type', 'price', 'duration_days', 'total_times', 'services'].contains(k)),
      });
      Get.snackbar('成功', resp['message'] ?? (status == 1 ? '已上架' : '已下架'));
      await loadCards();
    } catch (e) {
      Get.snackbar('错误', '操作失败: $e');
    }
  }

  /// 删除（有用户持卡会被后端拒绝）
  Future<void> delete(dynamic card) async {
    try {
      final resp = await api.delete('/admin/member-cards/${card['id']}');
      Get.snackbar('成功', resp['message'] ?? '删除成功');
      await loadCards();
    } catch (e) {
      Get.snackbar('错误', '删除失败: $e');
    }
  }

  static String typeLabel(dynamic type) => typeLabels[type?.toString()] ?? (type?.toString() ?? '-');

  static String statusLabel(dynamic status) => status?.toString() == '1' ? '启用' : '禁用';
}
