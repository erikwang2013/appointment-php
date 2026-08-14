/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../services/api_service.dart';
import 'schedule_controller.dart';

/// 排班编辑表单（新增 / 编辑）
/// 契约：POST /admin/schedules {technician_id, date, time_slots:[{start,end}], status}
class ScheduleFormPage extends StatefulWidget {
  final Map<String, dynamic>? scheduleData;
  const ScheduleFormPage({super.key, this.scheduleData});

  @override
  State<ScheduleFormPage> createState() => _ScheduleFormPageState();
}

class _ScheduleFormPageState extends State<ScheduleFormPage> {
  final _formKey = GlobalKey<FormState>();
  final _dateCtrl = TextEditingController();
  String? _technicianId;
  String? _date;
  int _status = 1;
  bool _isLoading = false;
  final List<_SlotRow> _slots = [];

  bool get isEdit => widget.scheduleData != null;

  @override
  void initState() {
    super.initState();
    if (isEdit) {
      final data = widget.scheduleData!;
      _technicianId = data['technician_id']?.toString();
      _date = data['date']?.toString();
      _dateCtrl.text = _date ?? '';
      _status = (data['status'] ?? 1) as int;
      final slots = (data['time_slots'] as List<dynamic>? ?? []);
      for (final slot in slots) {
        _slots.add(_SlotRow(
          startCtrl: TextEditingController(text: slot['start']?.toString() ?? ''),
          endCtrl: TextEditingController(text: slot['end']?.toString() ?? ''),
        ));
      }
    }
    if (_slots.isEmpty) {
      _slots.add(_SlotRow());
    }
  }

  @override
  void dispose() {
    _dateCtrl.dispose();
    for (final slot in _slots) {
      slot.startCtrl.dispose();
      slot.endCtrl.dispose();
    }
    super.dispose();
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime(2020),
      lastDate: DateTime(2035),
    );
    if (picked == null) return;
    final value = '${picked.year.toString().padLeft(4, '0')}-'
        '${picked.month.toString().padLeft(2, '0')}-'
        '${picked.day.toString().padLeft(2, '0')}';
    setState(() {
      _date = value;
      _dateCtrl.text = value;
    });
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() => _isLoading = true);

    final timeSlots = _slots
        .where((s) => s.startCtrl.text.trim().isNotEmpty || s.endCtrl.text.trim().isNotEmpty)
        .map((s) => {'start': s.startCtrl.text.trim(), 'end': s.endCtrl.text.trim()})
        .toList();

    final data = <String, dynamic>{
      'technician_id': _technicianId,
      'date': _date,
      'time_slots': timeSlots,
      'status': _status,
    };

    try {
      final api = ApiService();
      await api.post('/admin/schedules', data: data);
      Get.snackbar('成功', isEdit ? '排班更新成功' : '排班创建成功');
      Get.back(result: true);
    } catch (e) {
      Get.snackbar('错误', '操作失败: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!Get.isRegistered<ScheduleController>()) {
      Get.put(ScheduleController(), permanent: false);
    }
    final ctrl = Get.find<ScheduleController>();

    return Scaffold(
      appBar: AppBar(title: Text(isEdit ? '编辑排班' : '新增排班')),
      body: Center(
        child: SizedBox(
          width: 520,
          child: Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(24),
              children: [
                // 技师（编辑时不改技师，仅展示）
                Obx(() {
                  if (ctrl.techniciansLoading.value && ctrl.technicians.isEmpty) {
                    return const LinearProgressIndicator();
                  }
                  String? techName;
                  final items = ctrl.technicians.map((t) {
                    final name = t['real_name'] ?? ('技师#' + t['id'].toString());
                    if (t['id'].toString() == _technicianId) techName = name;
                    return DropdownMenuItem<String>(
                      value: t['id'].toString(),
                      child: Text(name),
                    );
                  }).toList();
                  // 编辑模式下技师列表未加载完时，value 必须仍在 items 中
                  if (isEdit && techName == null && _technicianId != null) {
                    techName = '技师#' + _technicianId!;
                    items.add(DropdownMenuItem(value: _technicianId, child: Text(techName!)));
                  }
                  return DropdownButtonFormField<String>(
                    value: _technicianId,
                    isExpanded: true,
                    decoration: const InputDecoration(labelText: '技师'),
                    validator: (v) => (v == null || v.isEmpty) ? '请选择技师' : null,
                    items: items,
                    onChanged: isEdit
                        ? null
                        : (v) => setState(() => _technicianId = v),
                    hint: techName == null ? const Text('请选择技师') : Text(techName),
                  );
                }),
                const SizedBox(height: 16),
                // 日期
                TextFormField(
                  readOnly: true,
                  controller: _dateCtrl,
                  decoration: const InputDecoration(labelText: '排班日期', suffixIcon: Icon(Icons.event)),
                  onTap: _pickDate,
                  validator: (v) => (_date == null) ? '请选择日期' : null,
                ),
                const SizedBox(height: 16),
                // 时间段
                Text('时间段（可多条，格式 HH:mm）', style: Theme.of(context).textTheme.labelLarge),
                const SizedBox(height: 8),
                ..._slots.asMap().entries.map((entry) => Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: Row(
                        children: [
                          Expanded(
                            child: TextFormField(
                              controller: entry.value.startCtrl,
                              keyboardType: TextInputType.datetime,
                              decoration: const InputDecoration(labelText: '开始', isDense: true),
                              validator: _timeValidator,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: TextFormField(
                              controller: entry.value.endCtrl,
                              keyboardType: TextInputType.datetime,
                              decoration: const InputDecoration(labelText: '结束', isDense: true),
                              validator: _timeValidator,
                            ),
                          ),
                          IconButton(
                            icon: const Icon(Icons.remove_circle_outline, color: Colors.red),
                            onPressed: _slots.length > 1
                                ? () => setState(() => _slots.removeAt(entry.key))
                                : null,
                          ),
                        ],
                      ),
                    )),
                Align(
                  alignment: Alignment.centerLeft,
                  child: TextButton.icon(
                    onPressed: () => setState(() => _slots.add(_SlotRow())),
                    icon: const Icon(Icons.add, size: 18),
                    label: const Text('添加时间段'),
                  ),
                ),
                const SizedBox(height: 16),
                // 状态
                DropdownButtonFormField<int>(
                  value: _status,
                  decoration: const InputDecoration(labelText: '状态'),
                  items: const [
                    DropdownMenuItem(value: 1, child: Text('可预约')),
                    DropdownMenuItem(value: 0, child: Text('休息')),
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

  String? _timeValidator(String? v) {
    if (v == null || v.trim().isEmpty) return '必填';
    if (!RegExp(r'^\d{1,2}:\d{2}$').hasMatch(v.trim())) return '格式 HH:mm';
    return null;
  }
}

class _SlotRow {
  final TextEditingController startCtrl;
  final TextEditingController endCtrl;

  _SlotRow({TextEditingController? startCtrl, TextEditingController? endCtrl})
      : startCtrl = startCtrl ?? TextEditingController(),
        endCtrl = endCtrl ?? TextEditingController();
}
