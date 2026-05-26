import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'api_service.dart';
import 'auth_service.dart';

/// 支付服务 — 微信支付 + 支付宝
class PaymentService extends GetxService {
  final ApiService _api = Get.find<ApiService>();

  /// 发起支付：根据类型调起微信/支付宝
  Future<Map<String, dynamic>> pay({
    required String orderId,
    required String payType, // 'wechat' | 'alipay'
  }) async {
    // 1. 调用后端创建支付记录，获取支付参数
    final res = await _api.post('/order/pay/$orderId', data: {'pay_type': payType});
    final data = res.data['data'] as Map<String, dynamic>;

    // 2. 根据支付类型调起SDK
    switch (payType) {
      case 'wechat':
        return _wechatPay(data);
      case 'alipay':
        return _alipay(data);
      default:
        throw Exception('不支持的支付方式: $payType');
    }
  }

  /// 微信支付
  Future<Map<String, dynamic>> _wechatPay(Map<String, dynamic> params) async {
    try {
      // fluwx 支付参数: appId, partnerId, prepayId, packageValue, nonceStr, timeStamp, sign
      await Fluws().pay(
        appId: params['appid'] ?? '',
        partnerId: params['partnerid'] ?? '',
        prepayId: params['prepayid'] ?? '',
        packageValue: params['package'] ?? 'Sign=WXPay',
        nonceStr: params['noncestr'] ?? '',
        timeStamp: params['timestamp']?.toString() ?? '',
        sign: params['sign'] ?? '',
      );
      return {'success': true, 'pay_type': 'wechat'};
    } on PlatformException catch (e) {
      return {'success': false, 'pay_type': 'wechat', 'error': e.message};
    }
  }

  /// 支付宝支付
  Future<Map<String, dynamic>> _alipay(Map<String, dynamic> params) async {
    try {
      // tobias: 传入后端返回的签名订单字符串
      final orderStr = params['order_str'] ?? '';
      if (orderStr.isEmpty) throw Exception('缺少支付宝订单串');
      
      final result = await Tobias.pay(orderStr);
      // result: {resultStatus, memo, result}
      return {
        'success': result['resultStatus'] == '9000',
        'pay_type': 'alipay',
        'result': result,
      };
    } on PlatformException catch (e) {
      return {'success': false, 'pay_type': 'alipay', 'error': e.message};
    }
  }

  /// 查询支付状态
  Future<Map<String, dynamic>> queryStatus(String orderId) async {
    final res = await _api.get('/order/payment-status/$orderId');
    return res.data['data'] as Map<String, dynamic>;
  }

  /// 处理支付回调（供微信/支付宝回调页面调用）
  Future<bool> handleCallback(String payType, Map<String, dynamic> result) {
    // 客户端侧验证支付结果后跳转
    return result['success'] == true;
  }
}
