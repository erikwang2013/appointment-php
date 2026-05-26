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
      final res = await ApiService.to.post('/auth/login', data: {
        'phone': phone,
        'password': password,
      });
      final data = res.data;
      token.value = data['token'];
      user.value = data['user'];
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('token', data['token'] ?? '');
      await prefs.setString('user', data['user']?['name'] ?? '');
      await prefs.setString('role', activeRole.value);
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> loginByWechat(String code) async {
    try {
      isLoading.value = true;
      final res = await ApiService.to.post('/auth/wechat-login', data: {
        'code': code,
      });
      final data = res.data;
      token.value = data['token'];
      user.value = data['user'];
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('token', data['token'] ?? '');
      await prefs.setString('user', data['user']?['name'] ?? '');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> sendCode(String phone) async {
    await ApiService.to.post('/auth/send-code', data: {'phone': phone});
  }

  Future<void> register(String phone, String password, String code, {String? referral}) async {
    try {
      isLoading.value = true;
      final res = await ApiService.to.post('/auth/register', data: {
        'phone': phone,
        'password': password,
        'code': code,
        if (referral != null && referral.isNotEmpty) 'referral': referral,
      });
      final data = res.data;
      token.value = data['token'];
      user.value = data['user'];
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('token', data['token'] ?? '');
      await prefs.setString('user', data['user']?['name'] ?? '');
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> resetPassword(String phone, String code, String newPassword) async {
    await ApiService.to.post('/auth/reset-password', data: {
      'phone': phone,
      'code': code,
      'password': newPassword,
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
      final res = await ApiService.to.get('/user/profile');
      user.value = res.data;
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('user', res.data?['name'] ?? '');
    } catch (_) {}
  }
}
