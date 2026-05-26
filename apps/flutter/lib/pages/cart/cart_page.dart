import 'package:flutter/material.dart';
class CartPage extends StatelessWidget {
  const CartPage({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(appBar: AppBar(title: const Text('购物车')), body: const Center(child: Text('购物车为空')), bottomNavigationBar: Padding(padding: const EdgeInsets.all(16), child: SizedBox(height: 48, child: ElevatedButton(onPressed: () {}, style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFe74c3c)), child: const Text('去结算', style: TextStyle(color: Colors.white))))));
}
