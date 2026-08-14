/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'technician_controller.dart';

/// 技师详情页（档案 + 收益汇总 + 等级 + 排班 + 服务 + 档案编辑 + 审核操作）
class TechnicianDetailPage extends StatefulWidget {
  final String techId;
  const TechnicianDetailPage({super.key, required this.techId});

  @override
  State<TechnicianDetailPage> createState() => _TechnicianDetailPageState();
}

class _TechnicianDetailPageState extends State<TechnicianDetailPage> {
  final _introCtrl = TextEditingController();
  final _avatarCtrl = TextEditingController();
  final _remarkCtrl = TextEditingController();
  bool _editLoading = false;
  bool _auditLoading = false;

  TechnicianController get _ctrl {
    if (!Get.isRegistered<TechnicianController>()) {
      Get.put(TechnicianController(), permanent: false);
    }
    return Get.find<TechnicianController>();
  }

  @override
  void initState() {
    super.initState();
    _ctrl.loadDetail(widget.techId).then((_) => _syncForm());
  }

  @override
  void dispose() {
    _introCtrl.dispose();
    _avatarCtrl.dispose();
    _remarkCtrl.dispose();
    super.dispose();
  }

  void _syncForm() {
    if (!mounted) return;
    final d = _ctrl.detail.value;
    if (d == null) return;
    _introCtrl.text = (d['intro'] ?? '').toString();
    _avatarCtrl.text = (d['avatar'] ?? '').toString();
  }

  Future<void> _saveProfile() async {
    setState(() => _editLoading = true);
    final ok = await _ctrl.updateProfile(
      widget.techId,
      intro: _introCtrl.text.trim(),
      avatar: _avatarCtrl.text.trim(),
    );
    if (!mounted) return;
    setState(() => _editLoading = false);
    if (ok) _syncForm();
  }

  Future<void> _audit(String action) async {
    setState(() => _auditLoading = true);
    await _ctrl.audit(widget.techId, action, _remarkCtrl.text.trim());
    if (!mounted) return;
    setState(() => _auditLoading = false);
    _remarkCtrl.clear();
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = _ctrl;

    return Scaffold(
      appBar: AppBar(title: const Text('技师详情')),
      body: Obx(() {
        if (ctrl.detailLoading.value && ctrl.detail.value == null) {
          return const Center(child: CircularProgressIndicator());
        }
        final d = ctrl.detail.value;
        if (d == null) {
          return const Center(child: Text('加载失败，请返回重试'));
        }
        final user = d['user'] is Map ? d['user'] as Map : const {};
        final status = (d['status'] ?? '').toString();
        final earnings = d['earnings_summary'] is Map ? d['earnings_summary'] as Map : const {};
        final tier = d['current_tier'] is Map ? d['current_tier'] as Map : null;
        final nextTier = d['next_tier'] is Map ? d['next_tier'] as Map : null;
        final tierProgress = d['tier_progress'] is Map ? d['tier_progress'] as Map : null;
        final schedules = (d['schedules'] as List<dynamic>?) ?? [];
        final services = (d['services'] as List<dynamic>?) ?? [];

        return ListView(
          padding: const EdgeInsets.all(24),
          children: [
            _Card(
              title: '基本信息',
              children: [
                _InfoRow('技师ID', d['id'].toString()),
                _InfoRow('用户ID', (d['user_id'] ?? '-').toString()),
                _InfoRow('姓名', (d['real_name'] ?? '-').toString()),
                _InfoRow('性别', technicianGenderLabel(d['gender'])),
                _InfoRow('手机号', (user['phone'] ?? '-').toString()),
                _InfoRow('昵称', (user['nickname'] ?? '-').toString()),
                _InfoRow('评分', (d['rating'] ?? '-').toString()),
                _InfoRow('接单数', (d['order_count'] ?? '0').toString()),
                _InfoRow('收藏数', (d['favorite_count'] ?? '0').toString()),
                _InfoRow('审核状态', technicianStatusLabel(status)),
                _InfoRow('审核备注', (d['audit_remark'] ?? '-').toString()),
                _InfoRow('审核时间', (d['audited_at'] ?? '-').toString()),
                _InfoRow('创建时间', (d['created_at'] ?? '-').toString()),
              ],
            ),
            const SizedBox(height: 16),
            _Card(
              title: '收益汇总',
              children: [
                _InfoRow('累计佣金', (earnings['total_commission'] ?? '0').toString()),
                _InfoRow('累计奖金', (earnings['total_bonus'] ?? '0').toString()),
                _InfoRow('累计扣罚', (earnings['total_penalty'] ?? '0').toString()),
                _InfoRow('待结算', (earnings['pending_settlement'] ?? '0').toString()),
              ],
            ),
            const SizedBox(height: 16),
            _Card(
              title: '等级评估',
              children: [
                _InfoRow('当前等级', tier != null ? '${tier['name'] ?? '-'}（佣金率 ${tier['commission_rate'] ?? '-'}%）' : '暂无'),
                _InfoRow('下一等级', nextTier != null ? (nextTier['name'] ?? '-').toString() : '已最高'),
                _InfoRow('升级进度', tierProgress != null ? '${tierProgress['overall_progress'] ?? '-'}%（还需 ${tierProgress['orders_needed'] ?? '-'} 单）' : '-'),
              ],
            ),
            const SizedBox(height: 16),
            _Card(
              title: '排班（${schedules.length}）',
              children: schedules.isEmpty
                  ? const [_InfoRow('', '暂无排班')]
                  : schedules.take(10).map((s) {
                      final slots = (s['time_slots'] as List<dynamic>? ?? [])
                          .map((slot) => '${slot['start']}-${slot['end']}')
                          .join('、');
                      return _InfoRow(
                        (s['date'] ?? '-').toString(),
                        '${slots.isEmpty ? '-' : slots}（${s['status'] == 0 ? '休息' : '可预约'}）',
                      );
                    }).toList(),
            ),
            const SizedBox(height: 16),
            _Card(
              title: '已分配服务（${services.length}）',
              children: services.isEmpty
                  ? const [_InfoRow('', '暂无服务')]
                  : services.take(10).map((ts) {
                      final svc = ts['service'] is Map ? ts['service'] as Map : null;
                      final name = svc != null ? (svc['name'] ?? '-').toString() : (ts['service_id'] ?? '-').toString();
                      final price = svc != null ? (svc['price'] ?? '-').toString() : '-';
                      return _InfoRow(name, '¥$price');
                    }).toList(),
            ),
            const SizedBox(height: 16),
            _Card(
              title: '编辑档案（后端仅支持简介/头像）',
              children: [
                TextField(
                  controller: _introCtrl,
                  maxLines: 3,
                  decoration: const InputDecoration(labelText: '个人简介'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _avatarCtrl,
                  decoration: const InputDecoration(labelText: '头像 URL'),
                ),
                const SizedBox(height: 12),
                ElevatedButton(
                  onPressed: _editLoading ? null : _saveProfile,
                  child: Text(_editLoading ? '保存中...' : '保存档案'),
                ),
              ],
            ),
            const SizedBox(height: 16),
            _Card(
              title: '审核操作',
              children: [
                TextField(
                  controller: _remarkCtrl,
                  decoration: const InputDecoration(labelText: '审核备注（驳回时建议填写原因）'),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: ElevatedButton(
                        onPressed: (_auditLoading || status == 'approved' || status == '1')
                            ? null
                            : () => _audit('approve'),
                        child: const Text('审核通过'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton(
                        onPressed: (_auditLoading || status == 'rejected' || status == '2')
                            ? null
                            : () => _confirmReject(),
                        style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
                        child: const Text('驳回'),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ],
        );
      }),
    );
  }

  void _confirmReject() {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('确认驳回'),
        content: const Text('确定要驳回该技师申请吗？'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('取消')),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              _audit('reject');
            },
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            child: const Text('驳回'),
          ),
        ],
      ),
    );
  }
}

class _Card extends StatelessWidget {
  final String title;
  final List<Widget> children;
  const _Card({required this.title, required this.children});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            ...children,
          ],
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  const _InfoRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    if (label.isEmpty) return Text(value);
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(label, style: TextStyle(color: Colors.grey.shade600)),
          ),
          Expanded(child: Text(value)),
        ],
      ),
    );
  }
}
