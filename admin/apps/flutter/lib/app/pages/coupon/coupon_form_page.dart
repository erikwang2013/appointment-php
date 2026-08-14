/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 优惠券编辑表单（新增 / 编辑）
/// 契约：POST /admin/coupons {name, type(fixed|percent), amount, min_amount, total_qty, start_at, end_at, status}
///       PUT  /admin/coupons/{id}（部分字段）
/// 后端校验：name/type/amount/total_qty 必填；amount >= 0；total_qty >= 1
class CouponFormPage extends StatefulWidget {
  final Map<String, dynamic>? couponData;
  const CouponFormPage({super.key, this.couponData});

  @override
  State<CouponFormPage> createState() => _CouponFormPageState();
}

class _CouponFormPageState extends State<CouponFormPage> {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _amountCtrl = TextEditingController();
  final _minAmountCtrl = TextEditingController();
  final _totalQtyCtrl = TextEditingController();
  final _startAtCtrl = TextEditingController();
  final _endAtCtrl = TextEditingController();
  String _type = 'fixed';
  int _status = 1;
  bool _isLoading = false;

  bool get isEdit => widget.couponData != null;

  @override
  void initState() {
    super.initState();
    if (isEdit) {
      final data = widget.couponData!;
      _nameCtrl.text = (data['name'] ?? '').toString();
      _type = data['type']?.toString() ?? 'fixed';
      _amountCtrl.text = (data['amount'] ?? '').toString();
      _minAmountCtrl.text = (data['min_amount'] ?? '').toString();
      _totalQtyCtrl.text = (data['total_qty'] ?? '').toString();
      _startAtCtrl.text = (data['start_at'] ?? '').toString();
      _endAtCtrl.text = (data['end_at'] ?? '').toString();
      _status = (data['status'] ?? 1) as int;
    }
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _amountCtrl.dispose();
    _minAmountCtrl.dispose();
    _totalQtyCtrl.dispose();
    _startAtCtrl.dispose();
    _endAtCtrl.dispose();
    super.dispose();
  }

  Future<void> _pickDate(TextEditingController ctrl) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2035),
    );
    if (picked == null) return;
    final value = '${picked.year.toString().padLeft(4, '0')}-'
        '${picked.month.toString().padLeft(2, '0')}-'
        '${picked.day.toString().padLeft(2, '0')} 00:00:00';
    setState(() => ctrl.text = value);
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() => _isLoading = true);

    final data = <String, dynamic>{
      'name': _nameCtrl.text.trim(),
      'type': _type,
      'amount': double.tryParse(_amountCtrl.text.trim()) ?? 0,
      'min_amount': double.tryParse(_minAmountCtrl.text.trim()) ?? 0,
      'total_qty': int.tryParse(_totalQtyCtrl.text.trim()) ?? 0,
      'status': _status,
    };
    if (_startAtCtrl.text.trim().isNotEmpty) data['start_at'] = _startAtCtrl.text.trim();
    if (_endAtCtrl.text.trim().isNotEmpty) data['end_at'] = _endAtCtrl.text.trim();

    try {
      final api = ApiService();
      if (isEdit) {
        await api.put('/admin/coupons/${widget.couponData!['id']}', data: data);
      } else {
        await api.post('/admin/coupons', data: data);
      }
      Get.snackbar('成功', isEdit ? '优惠券更新成功' : '优惠券创建成功');
      Get.back(result: true);
    } catch (e) {
      Get.snackbar('错误', '操作失败: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isPercent = _type == 'percent';
    return Scaffold(
      appBar: AppBar(title: Text(isEdit ? '编辑优惠券' : '新增优惠券')),
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
                    DropdownMenuItem(value: 'fixed', child: Text('固定金额')),
                    DropdownMenuItem(value: 'percent', child: Text('折扣')),
                  ],
                  onChanged: (v) => setState(() => _type = v ?? 'fixed'),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _amountCtrl,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: InputDecoration(
                    labelText: isPercent ? '折扣数值' : '面额（元）',
                    helperText: isPercent ? '按百分比优惠，如 90 表示 9 折' : '固定立减金额，如 50',
                  ),
                  validator: (v) {
                    if (v == null || v.trim().isEmpty) return '请输入数值';
                    if (double.tryParse(v.trim()) == null) return '请输入合法数字';
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _minAmountCtrl,
                  keyboardType: const TextInputType.numberWithOptions(decimal: true),
                  decoration: const InputDecoration(labelText: '使用门槛（元）', helperText: '订单金额达到该值方可使用，0 表示无门槛'),
                  validator: (v) {
                    if (v != null && v.trim().isNotEmpty && double.tryParse(v.trim()) == null) {
                      return '请输入合法数字';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _totalQtyCtrl,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: '发放总量'),
                  validator: (v) {
                    if (v == null || v.trim().isEmpty) return '请输入发放总量';
                    final n = int.tryParse(v.trim());
                    if (n == null || n < 1) return '须为不小于 1 的整数';
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  readOnly: true,
                  controller: _startAtCtrl,
                  decoration: const InputDecoration(labelText: '生效时间', suffixIcon: Icon(Icons.event)),
                  onTap: () => _pickDate(_startAtCtrl),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  readOnly: true,
                  controller: _endAtCtrl,
                  decoration: InputDecoration(
                    labelText: '失效时间',
                    suffixIcon: _endAtCtrl.text.isEmpty
                        ? const Icon(Icons.event)
                        : IconButton(
                            icon: const Icon(Icons.clear, size: 18),
                            onPressed: () => setState(() => _endAtCtrl.clear()),
                          ),
                  ),
                  onTap: () => _pickDate(_endAtCtrl),
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
