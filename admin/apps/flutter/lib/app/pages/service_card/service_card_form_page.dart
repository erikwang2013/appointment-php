/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 卡项设计编辑表单（新增 / 编辑）
/// 契约：POST /admin/service-cards {name, type(package|combo), services, product_ids,
///        total_price, handwork_total, commission_amount, sales_commission, status, description}
///       PUT  /admin/service-cards/{id}（部分字段）
/// 后端校验：name/type/total_price 必填；type ∈ package|combo；total_price >= 0
/// 说明：services/product_ids 为复杂嵌套数组，本表单不提供编辑（后端 has() 判断，
///       编辑时不传即保留原值；新增时后端默认空数组）。
class ServiceCardFormPage extends StatefulWidget {
  final Map<String, dynamic>? cardData;
  const ServiceCardFormPage({super.key, this.cardData});

  @override
  State<ServiceCardFormPage> createState() => _ServiceCardFormPageState();
}

class _ServiceCardFormPageState extends State<ServiceCardFormPage> {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _totalPriceCtrl = TextEditingController();
  final _handworkTotalCtrl = TextEditingController();
  final _commissionAmountCtrl = TextEditingController();
  final _salesCommissionCtrl = TextEditingController();
  final _descriptionCtrl = TextEditingController();
  String _type = 'package';
  int _status = 1;
  bool _isLoading = false;

  bool get isEdit => widget.cardData != null;

  @override
  void initState() {
    super.initState();
    if (isEdit) {
      final data = widget.cardData!;
      _nameCtrl.text = (data['name'] ?? '').toString();
      _type = data['type']?.toString() ?? 'package';
      _totalPriceCtrl.text = (data['total_price'] ?? '').toString();
      _handworkTotalCtrl.text = (data['handwork_total'] ?? '').toString();
      _commissionAmountCtrl.text = (data['commission_amount'] ?? '').toString();
      _salesCommissionCtrl.text = (data['sales_commission'] ?? '').toString();
      _descriptionCtrl.text = (data['description'] ?? '').toString();
      _status = (data['status'] ?? 1) as int;
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _totalPriceCtrl.dispose();
    _handworkTotalCtrl.dispose();
    _commissionAmountCtrl.dispose();
    _salesCommissionCtrl.dispose();
    _descriptionCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() => _isLoading = true);

    final data = <String, dynamic>{
      'name': _nameCtrl.text.trim(),
      'type': _type,
      'total_price': double.tryParse(_totalPriceCtrl.text.trim()) ?? 0,
      'handwork_total': double.tryParse(_handworkTotalCtrl.text.trim()) ?? 0,
      'commission_amount': double.tryParse(_commissionAmountCtrl.text.trim()) ?? 0,
      'sales_commission': double.tryParse(_salesCommissionCtrl.text.trim()) ?? 0,
      'status': _status,
      'description': _descriptionCtrl.text.trim(),
    };

    try {
      final api = ApiService();
      if (isEdit) {
        await api.put('/admin/service-cards/${widget.cardData!['id']}', data: data);
      } else {
        await api.post('/admin/service-cards', data: data);
      }
      Get.snackbar('成功', isEdit ? '卡项更新成功' : '卡项创建成功');
      Get.back(result: true);
    } catch (e) {
      Get.snackbar('错误', '操作失败: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(isEdit ? '编辑卡项' : '新增卡项')),
      body: Center(
        child: SizedBox(
          width: 520,
          child: Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(24),
              children: [
                TextFormField(
                  controller: _nameCtrl,
                  decoration: const InputDecoration(labelText: '名称'),
                  validator: (v) => (v == null || v.trim().isEmpty) ? '请输入名称' : null,
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  value: _type,
                  decoration: const InputDecoration(labelText: '类型'),
                  items: const [
                    DropdownMenuItem(value: 'package', child: Text('套餐')),
                    DropdownMenuItem(value: 'combo', child: Text('组合卡')),
                  ],
                  onChanged: (v) => setState(() => _type = v ?? 'package'),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _totalPriceCtrl,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(labelText: '总售价（元）'),
                  validator: (v) {
                    if (v == null || v.trim().isEmpty) return '请输入总售价';
                    if (double.tryParse(v.trim()) == null) return '请输入合法数字';
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _handworkTotalCtrl,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(labelText: '手工费总额（元）'),
                  validator: (v) {
                    if (v != null && v.trim().isNotEmpty && double.tryParse(v.trim()) == null) {
                      return '请输入合法数字';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _commissionAmountCtrl,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(labelText: '佣金金额（元）'),
                  validator: (v) {
                    if (v != null && v.trim().isNotEmpty && double.tryParse(v.trim()) == null) {
                      return '请输入合法数字';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _salesCommissionCtrl,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(labelText: '销售提成（元）'),
                  validator: (v) {
                    if (v != null && v.trim().isNotEmpty && double.tryParse(v.trim()) == null) {
                      return '请输入合法数字';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _descriptionCtrl,
                  maxLines: 3,
                  decoration: const InputDecoration(labelText: '描述', alignLabelWithHint: true),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<int>(
                  value: _status,
                  decoration: const InputDecoration(labelText: '状态'),
                  items: const [
                    DropdownMenuItem(value: 1, child: Text('启用')),
                    DropdownMenuItem(value: 0, child: Text('停用')),
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
}
