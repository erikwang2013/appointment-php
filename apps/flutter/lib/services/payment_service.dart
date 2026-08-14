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
    // ApiService 已统一剥壳：post 直接返回响应体 data
    final data = await _api.post('/order/pay/$orderId', data: {'pay_type': payType}) as Map<String, dynamic>;

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
      // 服务端 pay() 返回: {prepay_id, sign_params:{appId,timeStamp,nonceStr,package,signType,paySign,partnerId},...}
      final sign = (params['sign_params'] as Map<String, dynamic>?) ?? {};
      final prepayId = params['prepay_id']?.toString() ?? '';
      // fluwx 支付参数: appId, partnerId, prepayId, packageValue, nonceStr, timeStamp, sign
      // M10: 服务端 sign_params 已返回 partnerId(=mch_id)，直接透传给 fluwx；
      // partnerId 不参与 JSAPI 调起签名（签名仅 appId/timeStamp/nonceStr/package/signType）。
      await Fluws().pay(
        appId: sign['appId'] ?? '',
        partnerId: sign['partnerId'] ?? '',
        prepayId: prepayId,
        packageValue: sign['package'] ?? 'Sign=WXPay',
        nonceStr: sign['nonceStr'] ?? '',
        timeStamp: sign['timeStamp']?.toString() ?? '',
        sign: sign['paySign'] ?? '',
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
    // ApiService 已统一剥壳：get 直接返回响应体 data
    return await _api.get('/order/payment-status/$orderId') as Map<String, dynamic>;
  }

  /// 处理支付回调（供微信/支付宝回调页面调用）
  Future<bool> handleCallback(String payType, Map<String, dynamic> result) {
    // 客户端侧验证支付结果后跳转
    return result['success'] == true;
  }
}
