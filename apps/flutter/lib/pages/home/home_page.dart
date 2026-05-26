import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../services/api_service.dart';
import '../../widgets/service_card.dart';

class HomePage extends StatelessWidget {
  const HomePage({super.key});
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('预约服务'), actions: [IconButton(icon: const Icon(Icons.search), onPressed: () {})]),
      body: ListView(children: [
        _buildBanners(),
        _buildCategories(),
      ]),
    );
  }
  Widget _buildBanners() => SizedBox(height: 180, child: const Center(child: Text('轮播图')));
  Widget _buildCategories() => Padding(padding: const EdgeInsets.all(16), child: Wrap(spacing: 16, children: List.generate(6, (i) => Container(width: 100, height: 120, decoration: BoxDecoration(color: Colors.grey[200], borderRadius: BorderRadius.circular(12)), child: const Center(child: Text('分类'))))));
}
