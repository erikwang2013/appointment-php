import 'package:flutter/material.dart';
class RegisterPage extends StatelessWidget {
  RegisterPage({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('注册')), body: Padding(padding: const EdgeInsets.all(24), child: Column(children: [TextField(decoration: const InputDecoration(labelText: '手机号')), TextField(decoration: const InputDecoration(labelText: '验证码')), TextField(decoration: const InputDecoration(labelText: '密码'), obscureText: true), TextField(decoration: const InputDecoration(labelText: '推荐码')), const SizedBox(height: 24), SizedBox(width: double.infinity, child: ElevatedButton(onPressed: () {}, style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFe74c3c)), child: const Text('注册', style: TextStyle(color: Colors.white))))])));
}
