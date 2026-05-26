import 'package:flutter/material.dart';
class ForgetPasswordPage extends StatelessWidget {
  ForgetPasswordPage({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('忘记密码')), body: Padding(padding: const EdgeInsets.all(24), child: Column(children: [TextField(decoration: const InputDecoration(labelText: '手机号')), TextField(decoration: const InputDecoration(labelText: '验证码')), TextField(decoration: const InputDecoration(labelText: '新密码'), obscureText: true), const SizedBox(height: 24), SizedBox(width: double.infinity, child: ElevatedButton(onPressed: () {}, style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFe74c3c)), child: const Text('重置密码', style: TextStyle(color: Colors.white))))])));
}
