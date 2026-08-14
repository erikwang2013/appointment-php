/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'aftersale_controller.dart';

/// 审核弹窗结果（action: approve / reject，remark 为备注）
class AuditResult {
  final String action;
  final String remark;
  const AuditResult({required this.action, required this.remark});
}

/// 售后详情 + 审核弹窗（通过 / 驳回，可输入备注；驳回备注必填）
class AftersaleAuditDialog extends StatefulWidget {
  final dynamic aftersale;
  const AftersaleAuditDialog({super.key, required this.aftersale});

  @override
  State<AftersaleAuditDialog> createState() => _AftersaleAuditDialogState();
}

class _AftersaleAuditDialogState extends State<AftersaleAuditDialog> {
  final _remark = TextEditingController();
  String? _error;

  @override
  void dispose() {
    _remark.dispose();
    super.dispose();
  }

  void _submit(String action) {
    final remark = _remark.text.trim();
    if (action == 'reject' && remark.isEmpty) {
      setState(() => _error = '驳回必须填写备注');
      return;
    }
    Navigator.pop(context, AuditResult(action: action, remark: remark));
  }

  @override
  Widget build(BuildContext context) {
    final a = widget.aftersale;
    final order = a['order'] as Map<dynamic, dynamic>?;
    final statusText = AftersaleController.statusText(a);
    final typeText = AftersaleController.typeText(a);
    final pending = a['status']?.toString() == 'pending';

    return AlertDialog(
      title: const Text('售后详情'),
      content: SizedBox(
        width: 480,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('售后单号：${a['aftersale_no'] ?? '-'}'),
              const SizedBox(height: 4),
              Text('订单号：${order?['order_no']?.toString() ?? '-'}'),
              const SizedBox(height: 4),
              Text('类型：$typeText（当前状态：$statusText）'),
              const SizedBox(height: 4),
              Text('用户ID：${a['user_id'] ?? '-'}'),
              const SizedBox(height: 4),
              Text('退款金额：${a['refund_amount'] ?? '-'} 元'),
              const SizedBox(height: 4),
              Text('申请原因：${a['reason'] ?? '-'}'),
              const SizedBox(height: 4),
              if ((a['review_remark']?.toString() ?? '').isNotEmpty)
                Text('审核备注：${a['review_remark']}'),
              const SizedBox(height: 12),
              TextField(
                controller: _remark,
                maxLines: 3,
                enabled: pending,
                decoration: InputDecoration(
                  labelText: '审核备注（驳回必填）',
                  hintText: '如：同意售后 / 请补充凭证',
                  isDense: true,
                  errorText: _error,
                ),
              ),
            ],
          ),
        ),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('关闭')),
        if (pending) ...[
          ElevatedButton(
            onPressed: () => _submit('approve'),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.green, foregroundColor: Colors.white),
            child: const Text('通过'),
          ),
          ElevatedButton(
            onPressed: () => _submit('reject'),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
            child: const Text('驳回'),
          ),
        ],
      ],
    );
  }
}
