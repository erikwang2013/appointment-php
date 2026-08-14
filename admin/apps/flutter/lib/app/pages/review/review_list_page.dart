// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'review_controller.dart';

/// 评价管理列表页：筛选 + 分页 + 审核（隐藏/恢复）+ 删除
class ReviewListPage extends GetView<ReviewController> {
  const ReviewListPage({super.key});

  @override
  Widget build(BuildContext context) {
    controller;
    return Scaffold(
      appBar: AppBar(title: const Text('评价管理')),
      body: Column(
        children: [
          _buildFilterBar(),
          Expanded(
            child: Obx(() {
              if (controller.loading.value && controller.reviews.isEmpty) {
                return const Center(child: CircularProgressIndicator());
              }
              if (controller.reviews.isEmpty) {
                return const Center(child: Text('暂无评价'));
              }
              return RefreshIndicator(
                onRefresh: () => controller.fetchData(reset: true),
                child: ListView.separated(
                  itemCount: controller.reviews.length + 1,
                  separatorBuilder: (_, __) => const Divider(height: 1),
                  itemBuilder: (context, index) {
                    if (index == controller.reviews.length) {
                      return Padding(
                        padding: const EdgeInsets.all(12),
                        child: Center(
                          child: TextButton(
                            onPressed:
                                controller.reviews.length >=
                                    controller.total.value
                                ? null
                                : controller.loadMore,
                            child: controller.loading.value
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                    ),
                                  )
                                : Text(
                                    controller.reviews.length >=
                                            controller.total.value
                                        ? '没有更多了'
                                        : '加载更多',
                                  ),
                          ),
                        ),
                      );
                    }
                    return _buildItem(controller.reviews[index]);
                  },
                ),
              );
            }),
          ),
        ],
      ),
    );
  }

  /// 顶部筛选栏：评分 + 状态 + 关键词
  Widget _buildFilterBar() {
    return Padding(
      padding: const EdgeInsets.all(12),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: Obx(
                  () => DropdownButtonFormField<int>(
                    initialValue: controller.rating.value,
                    decoration: const InputDecoration(
                      labelText: '评分',
                      isDense: true,
                    ),
                    items: const [
                      DropdownMenuItem(value: 0, child: Text('全部')),
                      DropdownMenuItem(value: 5, child: Text('5 星')),
                      DropdownMenuItem(value: 4, child: Text('4 星')),
                      DropdownMenuItem(value: 3, child: Text('3 星及以下')),
                    ],
                    onChanged: (v) {
                      controller.rating.value = v ?? 0;
                      controller.fetchData(reset: true);
                    },
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Obx(
                  () => DropdownButtonFormField<int>(
                    initialValue: controller.status.value,
                    decoration: const InputDecoration(
                      labelText: '状态',
                      isDense: true,
                    ),
                    items: const [
                      DropdownMenuItem(value: -1, child: Text('全部')),
                      DropdownMenuItem(value: 1, child: Text('可见')),
                      DropdownMenuItem(value: 0, child: Text('隐藏')),
                    ],
                    onChanged: (v) {
                      controller.status.value = v ?? -1;
                      controller.fetchData(reset: true);
                    },
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: Obx(
                  () => TextField(
                    controller: TextEditingController(
                      text: controller.keywordInput.value,
                    ),
                    decoration: const InputDecoration(
                      labelText: '内容关键词',
                      isDense: true,
                      border: OutlineInputBorder(),
                    ),
                    onSubmitted: (_) => controller.applyFilters(),
                    onChanged: (v) => controller.keywordInput.value = v,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              OutlinedButton(
                onPressed: controller.applyFilters,
                child: const Text('搜索'),
              ),
              const SizedBox(width: 8),
              TextButton(
                onPressed: controller.clearFilters,
                child: const Text('重置'),
              ),
            ],
          ),
        ],
      ),
    );
  }

  /// 评价条目
  Widget _buildItem(Map<String, dynamic> item) {
    final technician = item['technician'] as Map<String, dynamic>?;
    final order = item['order'] as Map<String, dynamic>?;
    final status = item['status'] as int?;
    return ListTile(
      title: Row(
        children: [
          Text(
            '${item['rating'] ?? '-'} 星',
            style: const TextStyle(fontWeight: FontWeight.bold),
          ),
          const SizedBox(width: 8),
          Text(
            '技师: ${technician?['real_name'] ?? technician?['name'] ?? '未知'}',
            style: TextStyle(color: Colors.grey[700], fontSize: 13),
          ),
        ],
      ),
      subtitle: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            item['content'] ?? '',
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 4),
          Text(
            '订单号: ${order?['order_no'] ?? '-'}',
            style: TextStyle(color: Colors.grey[500], fontSize: 12),
          ),
        ],
      ),
      trailing: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Chip(
            label: Text(
              controller.statusText(status),
              style: TextStyle(
                fontSize: 11,
                color: status == 1 ? Colors.green : Colors.orange,
              ),
            ),
          ),
          PopupMenuButton<String>(
            onSelected: (v) {
              if (v == 'show' || v == 'hide') {
                controller.audit(item, v);
              } else if (v == 'delete') {
                _confirmDelete(item);
              }
            },
            itemBuilder: (_) => [
              if (status != 1)
                const PopupMenuItem(value: 'show', child: Text('恢复可见'))
              else
                const PopupMenuItem(value: 'hide', child: Text('隐藏')),
              const PopupMenuItem(value: 'delete', child: Text('删除')),
            ],
          ),
        ],
      ),
    );
  }

  void _confirmDelete(Map<String, dynamic> item) {
    Get.dialog(
      AlertDialog(
        title: const Text('确认删除'),
        content: const Text('删除后不可恢复，确定删除该评价吗？'),
        actions: [
          TextButton(onPressed: () => Get.back(), child: const Text('取消')),
          TextButton(
            onPressed: () {
              Get.back();
              controller.remove(item);
            },
            child: const Text('删除'),
          ),
        ],
      ),
    );
  }
}
