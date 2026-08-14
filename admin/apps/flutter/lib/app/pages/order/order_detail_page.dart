/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'order_controller.dart';

/// 订单详情页（订单项 + 支付信息 + 退款信息 + 用户/技师/门店信息，只读）
class OrderDetailPage extends StatefulWidget {
  final String orderId;
  const OrderDetailPage({super.key, required this.orderId});

  @override
  State<OrderDetailPage> createState() => _OrderDetailPageState();
}

class _OrderDetailPageState extends State<OrderDetailPage> {
  OrderController get _ctrl {
    if (!Get.isRegistered<OrderController>()) {
      Get.put(OrderController(), permanent: false);
    }
    return Get.find<OrderController>();
  }

  @override
  void initState() {
    super.initState();
    _ctrl.loadDetail(widget.orderId);
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = _ctrl;

    return Scaffold(
      appBar: AppBar(title: const Text('订单详情')),
      body: Obx(() {
        if (ctrl.detailLoading.value && ctrl.detail.value == null) {
          return const Center(child: CircularProgressIndicator());
        }
        final o = ctrl.detail.value;
        if (o == null) {
          return const Center(child: Text('加载失败，请返回重试'));
        }
        final user = o['user'] is Map ? o['user'] as Map : const {};
        final technician = o['technician'] is Map ? o['technician'] as Map : const {};
        final store = o['store'] is Map ? o['store'] as Map : const {};
        final items = (o['items'] as List<dynamic>?) ?? [];
        final payment = o['payment'] is Map ? o['payment'] as Map : const {};
        final verification = o['verification'] is Map ? o['verification'] as Map : null;
        final status = (o['status'] ?? '').toString();

        return ListView(
          padding: const EdgeInsets.all(24),
          children: [
            _Card(
              title: '订单信息',
              children: [
                _InfoRow('订单号', (o['order_no'] ?? '-').toString()),
                _InfoRow('订单类型', orderTypeLabel((o['order_type'] ?? '').toString())),
                _InfoRow('状态', orderStatusLabel(status)),
                _InfoRow('服务时间', (o['service_time'] ?? '-').toString()),
                _InfoRow('备注', (o['remark'] ?? '-').toString()),
                _InfoRow('取消原因', (o['cancel_reason'] ?? '-').toString()),
                _InfoRow('取消时间', (o['cancel_at'] ?? '-').toString()),
                _InfoRow('创建时间', (o['created_at'] ?? '-').toString()),
                _InfoRow('服务开始', (o['service_start_at'] ?? '-').toString()),
                _InfoRow('服务结束', (o['service_end_at'] ?? '-').toString()),
              ],
            ),
            const SizedBox(height: 16),
            _Card(
              title: '金额信息',
              children: [
                _InfoRow('订单总额', (o['total_amount'] ?? '0').toString()),
                _InfoRow('优惠金额', (o['discount_amount'] ?? '0').toString()),
                _InfoRow('实付金额', (o['paid_amount'] ?? '0').toString()),
              ],
            ),
            const SizedBox(height: 16),
            _Card(
              title: '用户信息',
              children: [
                _InfoRow('用户ID', (o['user_id'] ?? '-').toString()),
                _InfoRow('昵称', (user['nickname'] ?? '-').toString()),
                _InfoRow('真实姓名', (user['real_name'] ?? '-').toString()),
                _InfoRow('手机号', (user['phone'] ?? '-').toString()),
              ],
            ),
            const SizedBox(height: 16),
            _Card(
              title: '技师信息',
              children: [
                _InfoRow('技师ID', (o['technician_id'] ?? '-').toString()),
                _InfoRow('昵称', (technician['nickname'] ?? '-').toString()),
                _InfoRow('真实姓名', (technician['real_name'] ?? '-').toString()),
                _InfoRow('手机号', (technician['phone'] ?? '-').toString()),
              ],
            ),
            const SizedBox(height: 16),
            _Card(
              title: '门店信息',
              children: [
                _InfoRow('门店ID', (o['store_id'] ?? '-').toString()),
                _InfoRow('门店名称', (store['name'] ?? '-').toString()),
              ],
            ),
            const SizedBox(height: 16),
            _Card(
              title: '订单明细（${items.length}）',
              children: items.isEmpty
                  ? const [_InfoRow('', '暂无明细')]
                  : [
                      SingleChildScrollView(
                        scrollDirection: Axis.horizontal,
                        child: DataTable(
                          columns: const [
                            DataColumn(label: Text('项目名称')),
                            DataColumn(label: Text('类型')),
                            DataColumn(label: Text('单价')),
                            DataColumn(label: Text('数量')),
                            DataColumn(label: Text('规格')),
                            DataColumn(label: Text('小计')),
                          ],
                          rows: items.map((item) {
                            final price = double.tryParse((item['price'] ?? '0').toString()) ?? 0;
                            final quantity = int.tryParse((item['quantity'] ?? '1').toString()) ?? 1;
                            return DataRow(cells: [
                              DataCell(Text((item['name'] ?? '-').toString())),
                              DataCell(Text(itemTargetTypeLabel((item['target_type'] ?? '').toString()))),
                              DataCell(Text((item['price'] ?? '-').toString())),
                              DataCell(Text((item['quantity'] ?? '-').toString())),
                              DataCell(Text(specText(item['spec_info']))),
                              DataCell(Text((price * quantity).toString())),
                            ]);
                          }).toList(),
                        ),
                      ),
                    ],
            ),
            const SizedBox(height: 16),
            _Card(
              title: '支付信息',
              children: payment.isEmpty
                  ? const [_InfoRow('', '暂无支付记录')]
                  : [
                      _InfoRow('支付单号', (payment['payment_no'] ?? '-').toString()),
                      _InfoRow('支付方式', payTypeLabel((payment['pay_type'] ?? '').toString())),
                      _InfoRow('第三方交易号', (payment['transaction_id'] ?? '-').toString()),
                      _InfoRow('支付金额', (payment['amount'] ?? '-').toString()),
                      _InfoRow('支付状态', payStatusLabel((payment['status'] ?? '').toString())),
                      _InfoRow('支付时间', (payment['paid_at'] ?? '-').toString()),
                    ],
            ),
            const SizedBox(height: 16),
            _Card(
              title: '退款信息',
              children: [
                _InfoRow('退款状态', refundStatusLabel((o['refund_status'] ?? '').toString())),
                _InfoRow('退款金额', (o['refund_amount'] ?? '-').toString()),
                _InfoRow('退款时间', (o['refunded_at'] ?? '-').toString()),
              ],
            ),
            const SizedBox(height: 16),
            _Card(
              title: '核销信息',
              children: verification == null
                  ? const [_InfoRow('', '暂无核销记录')]
                  : [
                      _InfoRow('核销方式', verifyTypeLabel((verification['verify_type'] ?? '').toString())),
                      _InfoRow('核销人ID', (verification['verified_by'] ?? '-').toString()),
                      _InfoRow('核销地点', (verification['location'] ?? '-').toString()),
                      _InfoRow('核销时间', (verification['verified_at'] ?? '-').toString()),
                    ],
            ),
          ],
        );
      }),
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
