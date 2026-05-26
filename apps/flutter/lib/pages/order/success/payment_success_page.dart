import 'package:flutter/material.dart';
import 'package:get/get.dart';

class PaymentSuccessPage extends StatelessWidget {
  const PaymentSuccessPage({super.key});

  @override
  Widget build(BuildContext context) {
    final args = Get.arguments as Map<String, dynamic>? ?? {};
    return Scaffold(
      body: Center(child: Padding(padding: const EdgeInsets.all(32), child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
        const Icon(Icons.check_circle, color: Color(0xFF07C160), size: 80),
        const SizedBox(height: 24),
        const Text('支付成功', style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold)),
        const SizedBox(height: 12),
        Text('¥${args['amount'] ?? '0.00'}', style: const TextStyle(fontSize: 32, color: Color(0xFFe74c3c))),
        const SizedBox(height: 8),
        Text('订单编号: ${args['orderId'] ?? ''}', style: const TextStyle(color: Colors.grey)),
        const SizedBox(height: 48),
        SizedBox(width: 200, child: ElevatedButton(onPressed: () => Get.offAllNamed('/order/list'), style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFe74c3c)), child: const Text('查看订单', style: TextStyle(color: Colors.white)))),
        const SizedBox(height: 12),
        SizedBox(width: 200, child: OutlinedButton(onPressed: () => Get.offAllNamed('/home'), child: const Text('返回首页'))),
      ]))),
    );
  }
}
