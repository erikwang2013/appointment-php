import 'package:flutter/material.dart';
class OrderDetailPage extends StatelessWidget {
  const OrderDetailPage({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('订单详情')), body: ListView(children: const [ListTile(title: Text('订单状态'), subtitle: Text('已支付')), ListTile(title: Text('订单编号'), subtitle: Text('202605261500001234')), ListTile(title: Text('实付金额'), subtitle: Text('¥198.00', style: TextStyle(color: Color(0xFFe74c3c))))]), bottomNavigationBar: Padding(padding: const EdgeInsets.all(16), child: Row(children: [Expanded(child: OutlinedButton(onPressed: null, child: Text('取消订单'))), const SizedBox(width: 12), Expanded(child: ElevatedButton(onPressed: null, style: ElevatedButton.styleFrom(backgroundColor: Color(0xFFe74c3c)), child: Text('去支付', style: TextStyle(color: Colors.white))))])));
}
