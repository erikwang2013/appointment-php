import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../../services/payment_service.dart';
import '../../../widgets/payment_selector.dart';

class PaymentPage extends StatefulWidget {
  final String orderId;
  final String amount;
  const PaymentPage({super.key, required this.orderId, required this.amount});

  @override
  State<PaymentPage> createState() => _PaymentPageState();
}

class _PaymentPageState extends State<PaymentPage> {
  String _payType = 'wechat';
  bool _loading = false;

  Future<void> _pay() async {
    setState(() => _loading = true);
    try {
      final result = await Get.find<PaymentService>().pay(
        orderId: widget.orderId,
        payType: _payType,
      );
      if (result['success'] == true) {
        if (mounted) Get.offAllNamed('/order/success', arguments: {'orderId': widget.orderId, 'amount': widget.amount});
      } else {
        Get.snackbar('支付失败', result['error'] ?? '请重试', snackPosition: SnackPosition.bottom);
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('收银台')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(children: [
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(color: const Color(0xFFe74c3c).withValues(alpha: 0.05), borderRadius: BorderRadius.circular(16)),
            child: Column(children: [
              const Text('应付金额', style: TextStyle(fontSize: 14, color: Colors.grey)),
              const SizedBox(height: 8),
              Text('¥${widget.amount}', style: const TextStyle(fontSize: 40, fontWeight: FontWeight.bold, color: Color(0xFFe74c3c))),
              const SizedBox(height: 4),
              Text('订单号: ${widget.orderId}', style: const TextStyle(color: Colors.grey, fontSize: 12)),
            ]),
          ),
          const SizedBox(height: 32),
          PaymentSelector(selected: _payType, onChanged: (v) => setState(() => _payType = v)),
          const Spacer(),
          SizedBox(
            width: double.infinity, height: 52,
            child: ElevatedButton(
              onPressed: _loading ? null : _pay,
              style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFe74c3c)),
              child: _loading ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2)) : const Text('确认支付', style: TextStyle(color: Colors.white, fontSize: 18)),
            ),
          ),
        ]),
      ),
    );
  }
}
