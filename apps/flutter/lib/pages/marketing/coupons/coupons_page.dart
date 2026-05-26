import 'package:flutter/material.dart';
class CouponsPage extends StatelessWidget {
  const CouponsPage({super.key});
  @override
  Widget build(BuildContext context) => DefaultTabController(length: 3, child: Scaffold(appBar: AppBar(title: const Text('优惠券'), bottom: const TabBar(tabs: [Tab(text: '可用'), Tab(text: '已用'), Tab(text: '过期')])), body: const TabBarView(children: [Center(child: Text('暂无可用优惠券')), Center(child: Text('暂无已用优惠券')), Center(child: Text('暂无过期优惠券'))])));
}
