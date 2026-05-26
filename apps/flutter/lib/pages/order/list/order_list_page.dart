import 'package:flutter/material.dart';
class OrderListPage extends StatelessWidget {
  const OrderListPage({super.key});
  @override
  Widget build(BuildContext context) => DefaultTabController(length: 5, child: Scaffold(appBar: AppBar(title: const Text('我的订单'), bottom: const TabBar(isScrollable: true, tabs: [Tab(text: '全部'), Tab(text: '待支付'), Tab(text: '已支付'), Tab(text: '已完成'), Tab(text: '已取消')])), body: const TabBarView(children: [Center(child: Text('暂无订单'))]))));
}
