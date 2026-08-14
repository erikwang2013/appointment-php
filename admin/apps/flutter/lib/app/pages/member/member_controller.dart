/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 会员管理控制器（只读：列表 + 详情）
/// 契约对应 admin/app/admin/controller/MemberController.php：
///   GET /admin/members?page&limit&nickname&phone&uid&member_level&reg_date_start&reg_date_end
///   GET /admin/members/{id} → data.user + data.orders{list,total,page,limit} + data.active_cards[]
/// 后端未注册新增/编辑路由（resource 仅 index/show），此页只读。
/// 列表项：id(hashid), nickname, phone(脱敏), real_name(脱敏), member_level,
///         total_spent, order_count, member_cards_count, created_at
/// 详情 active_cards 项：id, user_id, card_id, start_at, end_at,
///         total_times, used_times, status, member_card{name,...}
class MemberController extends GetxController {
  final api = ApiService();

  final members = <dynamic>[].obs;
  final isLoading = false.obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15.obs;
  final keyword = ''.obs;

  @override
  void onInit() {
    super.onInit();
    loadMembers();
  }

  Future<void> loadMembers({bool reset = false}) async {
    if (reset) page.value = 1;
    isLoading.value = true;
    try {
      final params = <String, dynamic>{
        'page': page.value,
        'limit': limit.value,
      };
      if (keyword.value.isNotEmpty) params['nickname'] = keyword.value;

      final resp = await api.get('/admin/members', params: params);
      members.value = resp['data']['list'] as List<dynamic>;
      total.value = resp['data']['total'] as int;
    } catch (e) {
      Get.snackbar('错误', '加载会员列表失败: $e');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> search(String kw) async {
    keyword.value = kw.trim();
    await loadMembers(reset: true);
  }

  Future<void> nextPage() async {
    if (page.value * limit.value < total.value) {
      page.value++;
      await loadMembers();
    }
  }

  Future<void> prevPage() async {
    if (page.value > 1) {
      page.value--;
      await loadMembers();
    }
  }

  /// 会员详情（用户信息 + 有效会员卡 + 订单历史）
  Future<Map<String, dynamic>?> loadDetail(String id) async {
    try {
      final resp = await api.get('/admin/members/$id');
      return resp['data'] as Map<String, dynamic>;
    } catch (e) {
      Get.snackbar('错误', '加载会员详情失败: $e');
      return null;
    }
  }

  static String cardStatusLabel(dynamic status) {
    final s = status?.toString();
    if (s == 'active') return '有效';
    if (s == 'expired') return '已过期';
    if (s == 'used_up') return '次数用完';
    return s ?? '-';
  }
}
