import 'package:flutter/material.dart';
class ServiceDetailPage extends StatelessWidget {
  const ServiceDetailPage({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('服务详情')), body: ListView(children: [Container(height: 240, color: Colors.grey[200]), const Padding(padding: EdgeInsets.all(16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [Text('全身推拿', style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)), SizedBox(height: 8), Text('¥198.00', style: TextStyle(fontSize: 20, color: Color(0xFFe74c3c), fontWeight: FontWeight.bold)), Text('时长: 60分钟 | 已售: 1280')]))]), bottomNavigationBar: Padding(padding: const EdgeInsets.all(16), child: SizedBox(height: 48, child: ElevatedButton(onPressed: () {}, style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFe74c3c)), child: const Text('立即预约', style: TextStyle(color: Colors.white, fontSize: 18))))));
}
