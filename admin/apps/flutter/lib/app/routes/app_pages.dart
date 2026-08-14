// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import 'package:get/get.dart';
import '../pages/dashboard/dashboard_page.dart';
import '../pages/verification/verification_list_page.dart';

class AppPages {
  static const String initial = '/dashboard';

  static final List<GetPage> routes = [
    GetPage(name: '/dashboard', page: () => const DashboardPage()),
    GetPage(name: '/verifications', page: () => const VerificationListPage()),
  ];
}
