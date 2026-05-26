import 'package:flutter/material.dart';

class PaymentSelector extends StatelessWidget {
  final String selected;
  final ValueChanged<String> onChanged;

  const PaymentSelector({super.key, required this.selected, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      const Text('选择支付方式', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
      const SizedBox(height: 12),
      _buildOption('wechat', '微信支付', Icons.wechat, const Color(0xFF09BB07)),
      const SizedBox(height: 8),
      _buildOption('alipay', '支付宝', Icons.account_balance_wallet, const Color(0xFF1677FF)),
    ]);
  }

  Widget _buildOption(String value, String label, IconData icon, Color color) {
    return InkWell(
      onTap: () => onChanged(value),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          border: Border.all(color: selected == value ? color : Colors.grey[300]!, width: 2),
          borderRadius: BorderRadius.circular(12),
          color: selected == value ? color.withOpacity(0.05) : Colors.white,
        ),
        child: Row(children: [
          Icon(icon, color: color, size: 28),
          const SizedBox(width: 12),
          Text(label, style: TextStyle(fontSize: 16, fontWeight: selected == value ? FontWeight.bold : FontWeight.normal)),
          const Spacer(),
          if (selected == value) Icon(Icons.check_circle, color: color),
        ]),
      ),
    );
  }
}
