/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import '../../services/api_service.dart';

/// 公告编辑表单（新增 / 编辑）
/// 契约：POST /admin/announcements {title, content, sort, status}
///       PUT  /admin/announcements/{id}（title/content/sort/status）
/// 后端校验：title/content 必填；发布状态 status=1 时由后端更新 published_at。
class AnnouncementFormPage extends StatefulWidget {
  final Map<String, dynamic>? announcementData;
  const AnnouncementFormPage({super.key, this.announcementData});

  @override
  State<AnnouncementFormPage> createState() => _AnnouncementFormPageState();
}

class _AnnouncementFormPageState extends State<AnnouncementFormPage> {
  final _formKey = GlobalKey<FormState>();
  final _titleCtrl = TextEditingController();
  final _contentCtrl = TextEditingController();
  final _sortCtrl = TextEditingController();
  int _status = 0;
  bool _isLoading = false;

  bool get isEdit => widget.announcementData != null;

  @override
  void initState() {
    super.initState();
    if (isEdit) {
      final data = widget.announcementData!;
      _titleCtrl.text = (data['title'] ?? '').toString();
      _contentCtrl.text = (data['content'] ?? '').toString();
      _sortCtrl.text = (data['sort'] ?? '').toString();
      _status = (data['status'] ?? 0) as int;
    }
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _contentCtrl.dispose();
    _sortCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() => _isLoading = true);

    final data = <String, dynamic>{
      'title': _titleCtrl.text.trim(),
      'content': _contentCtrl.text.trim(),
      'sort': int.tryParse(_sortCtrl.text.trim()) ?? 0,
      'status': _status,
    };

    try {
      final api = ApiService();
      if (isEdit) {
        await api.put('/admin/announcements/${widget.announcementData!['id']}', data: data);
      } else {
        await api.post('/admin/announcements', data: data);
      }
      Get.snackbar('成功', isEdit ? '公告更新成功' : '公告创建成功');
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
      appBar: AppBar(title: Text(isEdit ? '编辑公告' : '新增公告')),
      body: Center(
        child: SizedBox(
          width: 520,
          child: Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(24),
              children: [
                TextFormField(
                  controller: _titleCtrl,
                  decoration: const InputDecoration(labelText: '标题'),
                  validator: (v) => (v == null || v.trim().isEmpty) ? '请输入标题' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _contentCtrl,
                  maxLines: 8,
                  decoration: const InputDecoration(labelText: '内容', alignLabelWithHint: true),
                  validator: (v) => (v == null || v.trim().isEmpty) ? '请输入内容' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _sortCtrl,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: '排序（数字越小越靠前）'),
                  validator: (v) {
                    if (v != null && v.trim().isNotEmpty && int.tryParse(v.trim()) == null) {
                      return '请输入整数';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<int>(
                  value: _status,
                  decoration: const InputDecoration(labelText: '状态'),
                  items: const [
                    DropdownMenuItem(value: 1, child: Text('已发布')),
                    DropdownMenuItem(value: 0, child: Text('草稿')),
                  ],
                  onChanged: (v) => setState(() => _status = v ?? 0),
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
