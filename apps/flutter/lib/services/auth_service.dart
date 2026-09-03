import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';

class AuthService extends GetxService {
  var token = Rxn<String>();
  var user = Rxn<Map<String, dynamic>>();
  var activeRole = 'customer'.obs;
  var isLoading = false.obs;

  bool get isLoggedIn => token.value != null && token.value!.isNotEmpty;
  bool get isTechnician => activeRole.value == 'technician';

  static AuthService get to => Get.find<AuthService>();

  @override
  void onInit() {
    super.onInit();
    _loadFromStorage();
  }

  Future<void> _loadFromStorage() async {
    final prefs = await SharedPreferences.getInstance();
    token.value = prefs.getString('token');
    activeRole.value = prefs.getString('role') ?? 'customer';
    final userJson = prefs.getString('user');
    if (userJson != null && userJson.isNotEmpty) {
      user.value = _parseUserJson(userJson);
    }
  }

  Map<String, dynamic> _parseUserJson(String json) {
    // Simple key-value parse from stored string
    return {'name': json};
  }

  Future<void> login(String phone, String password) async {
    try {
      isLoading.value = true;
      // POST /api/v1/auth/login → data: {token, user:{id,phone,nickname,avatar,active_role,referral_code,...}}
      final data = await ApiService.to.post('/auth/login', data: {
        'phone': phone,
        'password': password,
      }) as Map<String, dynamic>;
      await _applyAuth(data);
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> loginByWechat(String code) async {
    try {
      isLoading.value = true;
      // POST /api/v1/wechat/mini-login → data: {token, is_new_user, need_phone, user:{id,nickname,avatar,phone}}
      final data = await ApiService.to.post('/wechat/mini-login', data: {
        'code': code,
      }) as Map<String, dynamic>;
      await _applyAuth(data);
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> sendCode(String phone) async {
    // POST /api/v1/captcha/send → data: {phone, expire_in}
    await ApiService.to.post('/captcha/send', data: {'phone': phone});
  }

  Future<void> register(String phone, String password, String code, {String? referral}) async {
    try {
      isLoading.value = true;
      // POST /api/v1/auth/register：服务端读 referral_code（或 invite_code），并要求 confirm_password 一致
      final data = await ApiService.to.post('/auth/register', data: {
        'phone': phone,
        'password': password,
        'confirm_password': password,
        'code': code,
        if (referral != null && referral.isNotEmpty) 'referral_code': referral,
      }) as Map<String, dynamic>;
      await _applyAuth(data);
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> resetPassword(String phone, String code, String newPassword) async {
    // POST /api/v1/auth/forget-password：服务端要求 confirm_password 与 password 一致
    await ApiService.to.post('/auth/forget-password', data: {
      'phone': phone,
      'code': code,
      'password': newPassword,
      'confirm_password': newPassword,
    });
  }

  Future<void> logout() async {
    token.value = null;
    user.value = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
    Get.offAllNamed('/home');
  }

  Future<void> switchRole(String role) async {
    try {
      isLoading.value = true;
      await ApiService.to.post('/user/switch-role', data: {'role': role});
      activeRole.value = role;
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('role', role);
      Get.offAllNamed(role == 'technician' ? '/tech-work' : '/home');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> fetchUserProfile() async {
    try {
      final data = await ApiService.to.get('/user/profile') as Map<String, dynamic>;
      user.value = data;
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('user', data['nickname']?.toString() ?? '');
    } catch (_) {}
  }

  /// 统一应用登录态：解析剥壳后的 data（{token, user}），落盘 token/user/role
  Future<void> _applyAuth(Map<String, dynamic> data) async {
    final tokenStr = data['token'] as String? ?? '';
    final userData = data['user'];
    final userMap = userData is Map<String, dynamic> ? userData : null;

    token.value = tokenStr.isNotEmpty ? tokenStr : null;
    user.value = userMap;
    if (userMap != null && userMap['active_role'] != null) {
      activeRole.value = userMap['active_role'].toString();
    }

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('token', tokenStr);
    await prefs.setString('user', (userMap?['nickname'] ?? userMap?['phone'] ?? '').toString());
    await prefs.setString('role', activeRole.value);
  }
}
