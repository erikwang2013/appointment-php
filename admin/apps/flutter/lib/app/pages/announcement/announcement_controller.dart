/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 公告管理控制器
/// 契约对应 admin/app/admin/controller/AnnouncementController.php：
///   GET    /admin/announcements?page&limit&keyword&status
///   POST   /admin/announcements {title, content, sort, status}
///   PUT    /admin/announcements/{id}（title/content/sort/status）
///   DELETE /admin/announcements/{id} {password}（需管理员密码二次确认）
/// 发布/取消发布通过 status（1=已发布 / 0=草稿）实现；
/// 后端 publish 端点未注册路由，前端不调用。
/// 列表项：id(hashid), title, content, sort, status, published_at, created_at, updated_at
class AnnouncementController extends GetxController {
  final api = ApiService();

  final announcements = <dynamic>[].obs;
  final isLoading = false.obs;
  final total = 0.obs;
  final page = 1.obs;
  final limit = 15.obs;
  final keyword = ''.obs;
  final statusFilter = Rx<int?>(null);

  @override
  void onInit() {
    super.onInit();
    loadAnnouncements();
  }

  Future<void> loadAnnouncements({bool reset = false}) async {
    if (reset) page.value = 1;
    isLoading.value = true;
    try {
      final params = <String, dynamic>{
        'page': page.value,
        'limit': limit.value,
      };
      if (keyword.value.isNotEmpty) params['keyword'] = keyword.value;
      if (statusFilter.value != null) params['status'] = statusFilter.value;

      final resp = await api.get('/admin/announcements', params: params);
      announcements.value = resp['data']['list'] as List<dynamic>;
      total.value = resp['data']['total'] as int;
    } catch (e) {
      Get.snackbar('错误', '加载公告列表失败: $e');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> search(String kw) async {
    keyword.value = kw.trim();
    await loadAnnouncements(reset: true);
  }

  Future<void> filterByStatus(int? status) async {
    statusFilter.value = status;
    await loadAnnouncements(reset: true);
  }

  Future<void> nextPage() async {
    if (page.value * limit.value < total.value) {
      page.value++;
      await loadAnnouncements();
    }
  }

  Future<void> prevPage() async {
    if (page.value > 1) {
      page.value--;
      await loadAnnouncements();
    }
  }

  Future<bool> deleteAnnouncement(String id, String password) async {
    try {
      await api.delete('/admin/announcements/$id', data: {'password': password});
      await loadAnnouncements();
      Get.snackbar('成功', '公告删除成功');
      return true;
    } catch (e) {
      Get.snackbar('错误', '删除失败: $e');
      return false;
    }
  }
}
