import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../../services/auth_service.dart';

class LoginPage extends StatelessWidget {
  final phoneCtrl = TextEditingController();
  final pwdCtrl = TextEditingController();
  LoginPage({super.key});
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('登录')),
      body: Padding(padding: const EdgeInsets.all(24), child: Column(children: [
        TextField(controller: phoneCtrl, decoration: const InputDecoration(labelText: '手机号'), keyboardType: TextInputType.phone),
        TextField(controller: pwdCtrl, decoration: const InputDecoration(labelText: '密码'), obscureText: true),
        const SizedBox(height: 24),
        SizedBox(width: double.infinity, child: ElevatedButton(onPressed: () => Get.find<AuthService>().login(phoneCtrl.text, pwdCtrl.text), style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFe74c3c)), child: const Text('登录', style: TextStyle(color: Colors.white)))),
        TextButton(onPressed: () => Get.toNamed('/auth/register'), child: const Text('注册账号')),
        TextButton(onPressed: () => Get.toNamed('/auth/forget'), child: const Text('忘记密码')),
        TextButton(onPressed: () => Get.back(), child: const Text('游客模式')),
      ])),
    );
  }
}
