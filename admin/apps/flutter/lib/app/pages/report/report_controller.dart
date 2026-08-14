// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 数据报表控制器：订单统计 / 技师绩效 / 渠道分布
class ReportController extends GetxController {
  final isLoading = true.obs;

  /// 当前时间范围（天），可选 7 / 30
  final rangeDays = 7.obs;

  /// 订单统计汇总（summary 字段）
  final summary = <String, dynamic>{}.obs;

  /// 按天趋势数组 [{date, order_count, payment_amount, refund_amount, net_revenue}]
  final trendList = <Map<String, dynamic>>[].obs;

  /// 技师 TOP10 [{technician_id, technician_name, order_count, revenue, rating}]
  final technicianList = <Map<String, dynamic>>[].obs;

  /// 支付渠道分布 [{name, type, count, amount}]
  final payTypeList = <Map<String, dynamic>>[].obs;

  /// 订单状态分布 [{name, status, count}]
  final statusList = <Map<String, dynamic>>[].obs;

  @override
  void onInit() {
    super.onInit();
    loadData();
  }

  String get startDate {
    final now = DateTime.now();
    return DateTime(now.year, now.month, now.day - (rangeDays.value - 1))
        .toIso8601String()
        .split('T')
        .first;
  }

  String get endDate => DateTime.now().toIso8601String().split('T').first;

  Future<void> switchRange(int days) async {
    if (days == rangeDays.value) return;
    rangeDays.value = days;
    await loadData();
  }

  Future<void> loadData() async {
    try {
      isLoading.value = true;
      final params = {'start_date': startDate, 'end_date': endDate};
      final results = await Future.wait([
        ApiService().get('/admin/reports/orders', params: params),
        ApiService().get('/admin/reports/technicians', params: {...params, 'sort_by': 'revenue'}),
        ApiService().get('/admin/reports/distribution', params: params),
      ]);
      final ordersData = results[0]['data'] as Map<String, dynamic>? ?? const {};
      final techData = results[1]['data'] as Map<String, dynamic>? ?? const {};
      final distData = results[2]['data'] as Map<String, dynamic>? ?? const {};

      summary.value = Map<String, dynamic>.from(ordersData['summary'] ?? {});
      trendList.value = List<Map<String, dynamic>>.from(ordersData['list'] ?? []);
      technicianList.value = List<Map<String, dynamic>>.from(techData['list'] ?? []);
      payTypeList.value = List<Map<String, dynamic>>.from(distData['pay_type'] ?? []);
      statusList.value = List<Map<String, dynamic>>.from(distData['status'] ?? []);
    } catch (e) {
      Get.snackbar('加载失败', '报表数据获取失败：$e');
      summary.value = const {};
      trendList.value = const [];
      technicianList.value = const [];
      payTypeList.value = const [];
      statusList.value = const [];
    } finally {
      isLoading.value = false;
    }
  }

  /// 格式化金额：带千分位，2 位小数
  String fmtMoney(num? v) {
    final n = (v ?? 0).toDouble();
    return n.toStringAsFixed(2).replaceAllMapped(
        RegExp(r'(\d)(?=(\d{3})+\.)'), (m) => '${m[1]},');
  }

  /// 渠道名（英文 type）转中文
  String payTypeName(String type) {
    switch (type) {
      case 'wechat':
        return '微信支付';
      case 'alipay':
        return '支付宝';
      case 'balance':
        return '余额支付';
      default:
        return type;
    }
  }
}
