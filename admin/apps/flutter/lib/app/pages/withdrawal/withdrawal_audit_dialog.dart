/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'withdrawal_controller.dart';

/// 审核弹窗结果（approve: 通过 / reject: 驳回，remark 为备注）
class AuditResult {
  final bool approve;
  final String remark;
  const AuditResult({required this.approve, required this.remark});
}

/// 提现审核弹窗（通过 / 驳回，可输入备注；驳回备注必填）
class WithdrawalAuditDialog extends StatefulWidget {
  final dynamic withdrawal;
  final bool approve;
  const WithdrawalAuditDialog({super.key, required this.withdrawal, required this.approve});

  @override
  State<WithdrawalAuditDialog> createState() => _WithdrawalAuditDialogState();
}

class _WithdrawalAuditDialogState extends State<WithdrawalAuditDialog> {
  final _remark = TextEditingController();
  String? _error;

  @override
  void dispose() {
    _remark.dispose();
    super.dispose();
  }

  void _submit(bool approve) {
    final remark = _remark.text.trim();
    if (!approve && remark.isEmpty) {
      setState(() => _error = '驳回必须填写备注');
      return;
    }
    Navigator.pop(context, AuditResult(approve: approve, remark: remark));
  }

  @override
  Widget build(BuildContext context) {
    final w = widget.withdrawal;
    final statusText = WithdrawalController.statusText(w);
    return AlertDialog(
      title: Text(widget.approve ? '审核通过' : '驳回提现'),
      content: SizedBox(
        width: 420,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('提现单号：${w['withdrawal_no'] ?? '-'}'),
            const SizedBox(height: 4),
            Text('技师：${(w['technician'] as Map<dynamic, dynamic>?)?['real_name']?.toString() ?? '-'}'),
            const SizedBox(height: 4),
            Text('金额：${w['amount'] ?? '-'} 元（当前状态：$statusText）'),
            const SizedBox(height: 12),
            TextField(
              controller: _remark,
              maxLines: 3,
              decoration: InputDecoration(
                labelText: widget.approve ? '审核备注（可选）' : '驳回备注（必填）',
                hintText: widget.approve ? '如：同意提现' : '如：账户信息有误，请核实',
                isDense: true,
                errorText: _error,
              ),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('取消')),
        if (widget.approve)
          ElevatedButton(
            onPressed: () => _submit(true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.green, foregroundColor: Colors.white),
            child: const Text('通过'),
          ),
        ElevatedButton(
          onPressed: () => _submit(false),
          style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
          child: const Text('驳回'),
        ),
      ],
    );
  }
}
