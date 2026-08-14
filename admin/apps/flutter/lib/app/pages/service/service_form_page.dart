/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../services/api_service.dart';
import 'service_controller.dart';

/// 服务编辑表单（新增 / 编辑）
/// 契约：POST /admin/services {category_id, name, description, cover_image, price, original_price, duration, sort, status}
///       PUT  /admin/services/{id} 同上（部分字段可选）
class ServiceFormPage extends StatefulWidget {
  final Map<String, dynamic>? serviceData;
  const ServiceFormPage({super.key, this.serviceData});

  @override
  State<ServiceFormPage> createState() => _ServiceFormPageState();
}

class _ServiceFormPageState extends State<ServiceFormPage> {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _priceCtrl = TextEditingController();
  final _originalPriceCtrl = TextEditingController();
  final _durationCtrl = TextEditingController();
  final _coverImageCtrl = TextEditingController();
  final _descriptionCtrl = TextEditingController();
  final _sortCtrl = TextEditingController();
  String? _categoryId;
  int _status = 1;
  bool _isLoading = false;

  bool get isEdit => widget.serviceData != null;

  @override
  void initState() {
    super.initState();
    if (isEdit) {
      final data = widget.serviceData!;
      _categoryId = data['category_id']?.toString();
      _nameCtrl.text = (data['name'] ?? '').toString();
      _priceCtrl.text = (data['price'] ?? '').toString();
      _originalPriceCtrl.text = (data['original_price'] ?? '').toString();
      _durationCtrl.text = (data['duration'] ?? '').toString();
      _coverImageCtrl.text = (data['cover_image'] ?? '').toString();
      _descriptionCtrl.text = (data['description'] ?? '').toString();
      _sortCtrl.text = (data['sort'] ?? '').toString();
      _status = (data['status'] ?? 1) as int;
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _priceCtrl.dispose();
    _originalPriceCtrl.dispose();
    _durationCtrl.dispose();
    _coverImageCtrl.dispose();
    _descriptionCtrl.dispose();
    _sortCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() => _isLoading = true);

    final data = <String, dynamic>{
      'category_id': _categoryId,
      'name': _nameCtrl.text.trim(),
      'price': _priceCtrl.text.trim(),
      'duration': _durationCtrl.text.trim(),
      'cover_image': _coverImageCtrl.text.trim(),
      'description': _descriptionCtrl.text.trim(),
      'sort': _sortCtrl.text.trim().isEmpty ? 0 : int.parse(_sortCtrl.text.trim()),
      'status': _status,
    };
    if (_originalPriceCtrl.text.trim().isNotEmpty) {
      data['original_price'] = _originalPriceCtrl.text.trim();
    }

    try {
      final api = ApiService();
      if (isEdit) {
        await api.put('/admin/services/${widget.serviceData!['id']}', data: data);
      } else {
        await api.post('/admin/services', data: data);
      }
      Get.snackbar('成功', isEdit ? '服务更新成功' : '服务创建成功');
      Get.back(result: true);
    } catch (e) {
      Get.snackbar('错误', '操作失败: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<ServiceController>()) {
      Get.put(ServiceController(), permanent: false);
    }
    final ctrl = Get.find<ServiceController>();

    return Scaffold(
      appBar: AppBar(title: Text(isEdit ? '编辑服务' : '新增服务')),
      body: Center(
        child: SizedBox(
          width: 520,
          child: Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(24),
              children: [
                // 服务分类（编辑模式：列表项 category_id 为原始数字，后端未编码，
                // value 不在 items 会断言，按 schedule 页模式兜底插入占位 item）
                Obx(() {
                  if (ctrl.categoriesLoading.value && ctrl.categories.isEmpty) {
                    return const LinearProgressIndicator();
                  }
                  String? categoryName;
                  final items = ctrl.categories.map((c) {
                    final name = (c['name'] ?? '').toString();
                    if (c['id'].toString() == _categoryId) categoryName = name;
                    return DropdownMenuItem<String>(
                      value: c['id'].toString(),
                      child: Text(name),
                    );
                  }).toList();
                  if (isEdit && categoryName == null && _categoryId != null) {
                    final data = widget.serviceData!;
                    final nested = data['category'] is Map
                        ? (data['category'] as Map)['name']?.toString()
                        : null;
                    categoryName = nested ?? '分类#$_categoryId';
                    items.add(DropdownMenuItem(value: _categoryId, child: Text(categoryName!)));
                  }
                  return DropdownButtonFormField<String>(
                    initialValue: _categoryId,
                    isExpanded: true,
                    decoration: const InputDecoration(labelText: '服务分类'),
                    validator: (v) => (v == null || v.isEmpty) ? '请选择服务分类' : null,
                    items: items,
                    onChanged: (v) => setState(() => _categoryId = v),
                    hint: Text(categoryName ?? '请选择服务分类'),
                  );
                }),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _nameCtrl,
                  decoration: const InputDecoration(labelText: '服务名称'),
                  validator: (v) => (v == null || v.trim().isEmpty) ? '请输入服务名称' : null,
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _priceCtrl,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(labelText: '价格（元）'),
                        validator: _priceValidator,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextFormField(
                        controller: _originalPriceCtrl,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(labelText: '原价（元，选填）'),
                        validator: _optionalNumberValidator,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _durationCtrl,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(labelText: '时长（分钟）'),
                        validator: (v) {
                          final text = v?.trim() ?? '';
                          if (text.isEmpty) return '请输入时长';
                          final n = int.tryParse(text);
                          if (n == null || n < 1) return '时长必须为正整数';
                          return null;
                        },
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextFormField(
                        controller: _sortCtrl,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(labelText: '排序（选填，默认0）'),
                        validator: (v) {
                          final text = v?.trim() ?? '';
                          if (text.isEmpty) return null;
                          if (int.tryParse(text) == null) return '排序必须为整数';
                          return null;
                        },
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _coverImageCtrl,
                  decoration: const InputDecoration(labelText: '图片 URL（封面）'),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _descriptionCtrl,
                  maxLines: 3,
                  decoration: const InputDecoration(labelText: '服务描述'),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<int>(
                  initialValue: _status,
                  decoration: const InputDecoration(labelText: '上架状态'),
                  items: const [
                    DropdownMenuItem(value: 1, child: Text('上架')),
                    DropdownMenuItem(value: 0, child: Text('下架')),
                  ],
                  onChanged: (v) => setState(() => _status = v ?? 1),
                ),
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: _isLoading ? null : _submit,
                  child: Text(_isLoading ? '提交中...' : '提交'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  String? _priceValidator(String? v) {
    final text = v?.trim() ?? '';
    if (text.isEmpty) return '请输入价格';
    final n = double.tryParse(text);
    if (n == null || n < 0) return '价格必须 ≥ 0';
    return null;
  }

  String? _optionalNumberValidator(String? v) {
    final text = v?.trim() ?? '';
    if (text.isEmpty) return null;
    if (double.tryParse(text) == null) return '必须为数字';
    return null;
  }
}
