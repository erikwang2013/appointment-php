import 'package:flutter/material.dart';
class ServiceListPage extends StatelessWidget {
  const ServiceListPage({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('服务项目')), body: ListView.builder(itemCount: 10, itemBuilder: (_, i) => ListTile(leading: const Icon(Icons.spa), title: Text('服务项目 ${i+1}'), subtitle: const Text('¥198.00'), trailing: const Text('已售1280'))));
}
