import 'package:dio/dio.dart';
import 'package:get/get.dart';
import 'auth_service.dart';

class ApiService extends GetxService {
  late Dio _dio;
  static const baseUrl = 'http://localhost:8788/api';

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

  Future<Response> get(String path, {Map<String, dynamic>? params}) {
    return _dio.get(path, queryParameters: params);
  }

  Future<Response> post(String path, {dynamic data}) {
    return _dio.post(path, data: data);
  }

  Future<Response> put(String path, {dynamic data}) {
    return _dio.put(path, data: data);
  }

  Future<Response> delete(String path) {
    return _dio.delete(path);
  }

  Future<Response> upload(String path, String filePath, {String field = 'file'}) {
    final formData = FormData.fromMap({
      field: MultipartFile.fromFileSync(filePath),
    });
    return _dio.post(path, data: formData);
  }
}
