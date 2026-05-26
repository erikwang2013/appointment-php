import 'package:flutter/material.dart';
class UserPage extends StatelessWidget {
  const UserPage({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('我的')),
    body: ListView(children: [
      const ListTile(leading: CircleAvatar(radius: 30), title: Text('点击登录'), subtitle: Text('138****8000')),
      const Divider(),
      GridView.count(shrinkWrap: true, crossAxisCount: 4, physics: const NeverScrollableScrollPhysics(), children: ['订单','优惠券','会员卡','积分','收藏','消息','推广','反馈'].map((e) => Column(mainAxisAlignment: MainAxisAlignment.center, children: [const Icon(Icons.star, size: 30), Text(e)])).toList()),
      ListTile(title: const Text('退出登录'), onTap: () {})
    ]),
  );
}
