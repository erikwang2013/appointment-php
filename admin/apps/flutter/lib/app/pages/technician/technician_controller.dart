/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 技师管理控制器
/// 契约对应 admin/app/admin/controller/TechnicianController.php：
///   GET  /admin/technicians?page&limit&name&phone&status
///   GET  /admin/technicians/{id}            详情（user/schedules/services/earnings_summary/tier）
///   PUT  /admin/technicians/{id}            {intro?, avatar?} —— 后端仅支持这两个字段
///   POST /admin/technicians/{id}/audit      {action: approve|reject, remark}
/// 注意：
///   - 列表与详情中 real_name 已被后端脱敏（首字 + **）
///   - user_id 为原始数字（后端 encodeIds 仅编码顶层 id）
///   - status 为字符串：pending=待审核 approved=已通过 rejected=已驳回
class TechnicianController extends GetxController {
  final api = ApiService();

  final technicians = <dynamic>[].obs;
  final isLoading = false.obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15.obs;
  final name = ''.obs;
  final phone = ''.obs;
  final statusFilter = Rx<String?>(null);
  final detail = Rx<Map<String, dynamic>?>(null);
  final detailLoading = false.obs;

  @override
  void onInit() {
    super.onInit();
    loadTechnicians();
  }

  Future<void> loadTechnicians({bool reset = false}) async {
    if (reset) page.value = 1;
    isLoading.value = true;
    try {
      final params = <String, dynamic>{
        'page': page.value,
        'limit': limit.value,
      };
      if (name.value.isNotEmpty) params['name'] = name.value;
      if (phone.value.isNotEmpty) params['phone'] = phone.value;
      if (statusFilter.value != null && statusFilter.value!.isNotEmpty) {
        params['status'] = statusFilter.value;
      }

      final resp = await api.get('/admin/technicians', params: params);
      technicians.value = resp['data']['list'] as List<dynamic>;
      total.value = resp['data']['total'] as int;
    } catch (e) {
      Get.snackbar('错误', '加载技师列表失败: $e');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> searchName(String kw) async {
    name.value = kw.trim();
    await loadTechnicians(reset: true);
  }

  Future<void> searchPhone(String kw) async {
    phone.value = kw.trim();
    await loadTechnicians(reset: true);
  }

  Future<void> filterByStatus(String? status) async {
    statusFilter.value = (status == null || status.isEmpty) ? null : status;
    await loadTechnicians(reset: true);
  }

  Future<void> nextPage() async {
    if (page.value * limit.value < total.value) {
      page.value++;
      await loadTechnicians();
    }
  }

  Future<void> prevPage() async {
    if (page.value > 1) {
      page.value--;
      await loadTechnicians();
    }
  }

  Future<void> loadDetail(String id) async {
    detailLoading.value = true;
    try {
      final resp = await api.get('/admin/technicians/$id');
      detail.value = Map<String, dynamic>.from(resp['data'] as Map);
    } catch (e) {
      Get.snackbar('错误', '加载技师详情失败: $e');
    } finally {
      detailLoading.value = false;
    }
  }

  /// 编辑技师档案（后端仅接受 intro / avatar）
  Future<bool> updateProfile(String id, {required String intro, required String avatar}) async {
    try {
      await api.put('/admin/technicians/$id', data: {
        'intro': intro,
        'avatar': avatar,
      });
      await loadDetail(id);
      Get.snackbar('成功', '档案更新成功');
      return true;
    } catch (e) {
      Get.snackbar('错误', '更新失败: $e');
      return false;
    }
  }

  /// 审核操作：approve=通过 reject=驳回
  Future<bool> audit(String id, String action, String remark) async {
    try {
      await api.post('/admin/technicians/$id/audit', data: {
        'action': action,
        'remark': remark,
      });
      await loadDetail(id);
      Get.snackbar('成功', action == 'approve' ? '审核通过' : '已驳回');
      return true;
    } catch (e) {
      Get.snackbar('错误', '审核操作失败: $e');
      return false;
    }
  }
}

/// 技师审核状态文案（兼容字符串与历史数字写法）
String technicianStatusLabel(String? status) {
  switch (status) {
    case 'pending':
      return '待审核';
    case 'approved':
      return '已通过';
    case 'rejected':
      return '已驳回';
    case '1':
      return '已通过';
    case '2':
      return '已驳回';
    default:
      return (status == null || status.isEmpty) ? '-' : status;
  }
}

/// 性别文案：0=未知 1=男 2=女
String technicianGenderLabel(dynamic gender) {
  switch (gender?.toString()) {
    case '1':
      return '男';
    case '2':
      return '女';
    default:
      return '未知';
  }
}
