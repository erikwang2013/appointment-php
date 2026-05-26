import 'package:flutter/material.dart';
class TechWorkPage extends StatelessWidget {
  const TechWorkPage({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('工作台')), body: ListView(children: [Padding(padding: const EdgeInsets.all(16), child: Row(mainAxisAlignment: MainAxisAlignment.spaceAround, children: [Column(children: [const Text('¥298', style: TextStyle(fontSize: 28, color: Color(0xFFe74c3c))), Text('今日收入', style: TextStyle(color: Colors.grey[600]))]), Column(children: [const Text('¥3500', style: TextStyle(fontSize: 28, color: Color(0xFFe74c3c))), Text('可提现', style: TextStyle(color: Colors.grey[600]))]), Column(children: [const Text('8', style: TextStyle(fontSize: 28, color: Color(0xFFe74c3c))), Text('今日订单', style: TextStyle(color: Colors.grey[600]))])]))]));
}
