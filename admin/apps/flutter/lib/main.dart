// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:responsive_framework/responsive_framework.dart';
import 'app/theme/app_theme.dart';
import 'app/layouts/admin_layout.dart';
import 'app/pages/login/login_page.dart';
import 'app/pages/dashboard/dashboard_page.dart';
import 'app/pages/user/user_list_page.dart';
import 'app/pages/role/role_list_page.dart';
import 'app/pages/config/config_page.dart';
import 'app/pages/log/log_page.dart';
import 'app/pages/profile/profile_page.dart';
import 'app/pages/verification/verification_list_page.dart';
import 'app/pages/schedule/schedule_list_page.dart';
import 'app/pages/service/service_list_page.dart';
import 'app/pages/technician/technician_list_page.dart';
import 'app/pages/order/order_list_page.dart';
import 'app/pages/coupon/coupon_list_page.dart';
import 'app/pages/member/member_list_page.dart';
import 'app/pages/service_card/service_card_list_page.dart';
import 'app/pages/announcement/announcement_list_page.dart';
import 'app/pages/faq/faq_list_page.dart';

void main() {
  runApp(const AdminApp());
}

class AdminApp extends StatelessWidget {
  const AdminApp({super.key});

  @override
  Widget build(BuildContext context) {
    return GetMaterialApp(
      title: '开放管理后台',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      darkTheme: AppTheme.dark,
      builder: (context, child) => ResponsiveBreakpoints.builder(
        child: child!,
        breakpoints: [
          const Breakpoint(start: 0, end: 767, name: PHONE),
          const Breakpoint(start: 768, end: 1199, name: TABLET),
          const Breakpoint(start: 1200, end: 4500, name: DESKTOP),
        ],
      ),
      getPages: [
        GetPage(name: '/login', page: () => const LoginPage()),
        GetPage(name: '/dashboard', page: () => const AdminLayout(child: DashboardPage())),
        GetPage(name: '/users', page: () => const AdminLayout(child: UserListPage(), initialIndex: 1)),
        GetPage(name: '/roles', page: () => const AdminLayout(child: RoleListPage(), initialIndex: 2)),
        GetPage(name: '/config', page: () => const AdminLayout(child: ConfigPage(), initialIndex: 3)),
        GetPage(name: '/logs', page: () => const AdminLayout(child: LogPage(), initialIndex: 4)),
        GetPage(name: '/verifications', page: () => const AdminLayout(child: VerificationListPage(), initialIndex: 5)),
        GetPage(name: '/schedules', page: () => const AdminLayout(child: ScheduleListPage(), initialIndex: 6)),
        GetPage(name: '/services', page: () => const AdminLayout(child: ServiceListPage(), initialIndex: 7)),
        GetPage(name: '/technicians', page: () => const AdminLayout(child: TechnicianListPage(), initialIndex: 8)),
        GetPage(name: '/orders', page: () => const AdminLayout(child: OrderListPage(), initialIndex: 9)),
        GetPage(name: '/coupons', page: () => const AdminLayout(child: CouponListPage(), initialIndex: 10)),
        GetPage(name: '/members', page: () => const AdminLayout(child: MemberListPage(), initialIndex: 11)),
        GetPage(name: '/service-cards', page: () => const AdminLayout(child: ServiceCardListPage(), initialIndex: 12)),
        GetPage(name: '/announcements', page: () => const AdminLayout(child: AnnouncementListPage(), initialIndex: 13)),
        GetPage(name: '/faqs', page: () => const AdminLayout(child: FaqListPage(), initialIndex: 14)),
        GetPage(name: '/profile', page: () => const ProfilePage()),
      ],
      initialRoute: '/login',
    );
  }
}
