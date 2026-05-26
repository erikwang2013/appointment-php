import 'package:flutter/material.dart';
class OrderConfirmPage extends StatelessWidget {
  const OrderConfirmPage({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('确认订单')), body: ListView(children: const [ListTile(title: Text('服务项目'), subtitle: Text('全身推拿 - ¥198.00')), ListTile(title: Text('门店'), subtitle: Text('请选择门店'), trailing: Icon(Icons.chevron_right)), ListTile(title: Text('技师'), subtitle: Text('请选择技师'), trailing: Icon(Icons.chevron_right)), ListTile(title: Text('服务时间'), subtitle: Text('请选择时间'), trailing: Icon(Icons.chevron_right)), TextField(decoration: InputDecoration(labelText: '备注'))]), bottomNavigationBar: Padding(padding: const EdgeInsets.all(16), child: SizedBox(height: 48, child: ElevatedButton(onPressed: () {}, style: ElevatedButton.styleFrom(backgroundColor: Color(0xFFe74c3c)), child: const Text('提交订单', style: TextStyle(color: Colors.white))))));
}
