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
import 'app/pages/withdrawal/withdrawal_list_page.dart';
import 'app/pages/review/review_list_page.dart';
import 'app/pages/report/report_page.dart';

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
        GetPage(name: '/users', page: () => const AdminLayout(initialIndex: 1, child: UserListPage())),
        GetPage(name: '/roles', page: () => const AdminLayout(initialIndex: 2, child: RoleListPage())),
        GetPage(name: '/config', page: () => const AdminLayout(initialIndex: 3, child: ConfigPage())),
        GetPage(name: '/logs', page: () => const AdminLayout(initialIndex: 4, child: LogPage())),
        GetPage(name: '/verifications', page: () => const AdminLayout(initialIndex: 5, child: VerificationListPage())),
        GetPage(name: '/schedules', page: () => const AdminLayout(initialIndex: 6, child: ScheduleListPage())),
        GetPage(name: '/services', page: () => const AdminLayout(initialIndex: 7, child: ServiceListPage())),
        GetPage(name: '/technicians', page: () => const AdminLayout(initialIndex: 8, child: TechnicianListPage())),
        GetPage(name: '/orders', page: () => const AdminLayout(initialIndex: 9, child: OrderListPage())),
        GetPage(name: '/coupons', page: () => const AdminLayout(initialIndex: 10, child: CouponListPage())),
        GetPage(name: '/members', page: () => const AdminLayout(initialIndex: 11, child: MemberListPage())),
        GetPage(name: '/service-cards', page: () => const AdminLayout(initialIndex: 12, child: ServiceCardListPage())),
        GetPage(name: '/announcements', page: () => const AdminLayout(initialIndex: 13, child: AnnouncementListPage())),
        GetPage(name: '/faqs', page: () => const AdminLayout(initialIndex: 14, child: FaqListPage())),
        GetPage(name: '/withdrawals', page: () => const AdminLayout(initialIndex: 15, child: WithdrawalListPage())),
        GetPage(name: '/reviews', page: () => const AdminLayout(initialIndex: 16, child: ReviewListPage())),
        GetPage(name: '/reports', page: () => const AdminLayout(initialIndex: 17, child: ReportPage())),
        GetPage(name: '/profile', page: () => const ProfilePage()),
      ],
      initialRoute: '/login',
    );
  }
}
