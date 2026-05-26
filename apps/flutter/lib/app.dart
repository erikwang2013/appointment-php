import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';

import 'services/api_service.dart';
import 'services/auth_service.dart';
import 'pages/home/view.dart';
import 'pages/auth/login/view.dart';
import 'pages/auth/register/view.dart';
import 'pages/auth/forget/view.dart';
import 'pages/service/list/view.dart';
import 'pages/service/detail/view.dart';
import 'pages/cart/view.dart';
import 'pages/order/confirm/view.dart';
import 'pages/order/list/view.dart';
import 'pages/order/detail/view.dart';
import 'pages/user/view.dart';
import 'pages/tech_work/view.dart';
import 'pages/marketing/coupons/view.dart';
import 'pages/message/view.dart';

class AppointmentApp extends StatelessWidget {
  const AppointmentApp({super.key});

  @override
  Widget build(BuildContext context) {
    return GetMaterialApp(
      title: '预约服务',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorSchemeSeed: const Color(0xFFE74C3C),
        brightness: Brightness.light,
        appBarTheme: const AppBarTheme(
          centerTitle: true,
          elevation: 0,
        ),
      ),
      initialBinding: AppBinding(),
      initialRoute: '/home',
      getPages: [
        GetPage(name: '/home', page: () => const HomePage()),
        GetPage(name: '/login', page: () => const LoginPage()),
        GetPage(name: '/register', page: () => const RegisterPage()),
        GetPage(name: '/forget-password', page: () => const ForgetPasswordPage()),
        GetPage(name: '/services', page: () => const ServiceListPage()),
        GetPage(name: '/service-detail', page: () => const ServiceDetailPage()),
        GetPage(name: '/cart', page: () => const CartPage()),
        GetPage(name: '/order-confirm', page: () => const OrderConfirmPage()),
        GetPage(name: '/orders', page: () => const OrderListPage()),
        GetPage(name: '/order-detail', page: () => const OrderDetailPage()),
        GetPage(name: '/user', page: () => const UserPage()),
        GetPage(name: '/tech-work', page: () => const TechWorkPage()),
        GetPage(name: '/coupons', page: () => const CouponsPage()),
        GetPage(name: '/messages', page: () => const MessagesPage()),
      ],
      builder: EasyLoading.init(),
    );
  }
}

class AppBinding extends Bindings {
  @override
  void dependencies() {
    Get.put(AuthService());
    Get.put(ApiService());
  }
}
