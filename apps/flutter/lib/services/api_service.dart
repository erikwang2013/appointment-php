import 'package:dio/dio.dart';
import 'package:get/get.dart';
import 'auth_service.dart';

class ApiService extends GetxService {
  late Dio _dio;

  /// 支持 --dart-define=API_BASE_URL=xxx 覆盖，默认本地开发地址
  static const baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://localhost:8788/api',
  );

  static ApiService get to => Get.find<ApiService>();

  @override
  void onInit() {
    super.onInit();
    _dio = Dio(BaseOptions(
      baseUrl: baseUrl,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 15),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'API-Version': 'v1',
      },
    ));

    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) {
        final token = Get.find<AuthService>().token.value;
        if (token != null && token.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
      onError: (error, handler) {
        if (error.response?.statusCode == 401) {
          Get.find<AuthService>().logout();
        }
        handler.next(error);
      },
    ));
  }

  /// 统一剥壳：返回响应体 data 字段（服务端 {code, message, data}），
  /// code != 0 时抛出 ApiException（含服务端 message）
  Future<dynamic> get(String path, {Map<String, dynamic>? params}) async {
    final resp = await _dio.get(path, queryParameters: params);
    return _unwrap(resp);
  }

  Future<dynamic> post(String path, {dynamic data}) async {
    final resp = await _dio.post(path, data: data);
    return _unwrap(resp);
  }

  Future<dynamic> put(String path, {dynamic data}) async {
    final resp = await _dio.put(path, data: data);
    return _unwrap(resp);
  }

  Future<dynamic> delete(String path) async {
    final resp = await _dio.delete(path);
    return _unwrap(resp);
  }

  Future<Response> upload(String path, String filePath, {String field = 'file'}) {
    final formData = FormData.fromMap({
      field: MultipartFile.fromFileSync(filePath),
    });
    return _dio.post(path, data: formData);
  }

  dynamic _unwrap(Response resp) {
    final body = resp.data;
    if (body is! Map<String, dynamic>) {
      throw ApiException(-1, '响应格式错误');
    }
    if (body['code'] != 0) {
      throw ApiException(body['code'] as int, body['message'] as String? ?? '请求失败');
    }
    return body['data'];
  }
}

class ApiException implements Exception {
  final int code;
  final String message;
  ApiException(this.code, this.message);

  @override
  String toString() => 'ApiException($code): $message';
}
