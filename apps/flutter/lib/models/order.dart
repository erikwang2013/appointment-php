class OrderModel {
  final String id;
  final String orderNo;
  final String? userId;
  final String? technicianId;
  final String? technicianName;
  final String? technicianAvatar;
  final double totalAmount;
  final double? discountAmount;
  final double? paidAmount;
  final String status;
  final String? statusLabel;
  final String? appointmentDate;
  final String? appointmentTime;
  final String? storeName;
  final String? storeAddress;
  final String? remark;
  final List<OrderItem>? items;
  // M4: 服务端仅认 user_coupon_id（用户领券记录 id，来自 GET /api/marketing/coupons 列表顶层 id）
  final String? userCouponId;
  final double? couponDiscount;
  final String? createdAt;
  final String? paidAt;
  final String? completedAt;
  final String? cancelledAt;

  OrderModel({
    required this.id,
    required this.orderNo,
    this.userId,
    this.technicianId,
    this.technicianName,
    this.technicianAvatar,
    required this.totalAmount,
    this.discountAmount,
    this.paidAmount,
    required this.status,
    this.statusLabel,
    this.appointmentDate,
    this.appointmentTime,
    this.storeName,
    this.storeAddress,
    this.remark,
    this.items,
    this.userCouponId,
    this.couponDiscount,
    this.createdAt,
    this.paidAt,
    this.completedAt,
    this.cancelledAt,
  });

  factory OrderModel.fromJson(Map<String, dynamic> json) {
    final technician = json['technician'];
    final store = json['store'];
    final serviceTime = json['service_time']?.toString();
    return OrderModel(
      id: json['id']?.toString() ?? '',
      orderNo: json['order_no'] ?? '',
      userId: json['user_id']?.toString(),
      technicianId: json['technician_id']?.toString(),
      technicianName: technician is Map ? technician['nickname']?.toString() : null,
      technicianAvatar: technician is Map ? technician['avatar']?.toString() : null,
      totalAmount: (json['total_amount'] is int)
          ? (json['total_amount'] as int).toDouble()
          : json['total_amount']?.toDouble() ?? 0.0,
      discountAmount: (json['discount_amount'] is int)
          ? (json['discount_amount'] as int).toDouble()
          : json['discount_amount']?.toDouble(),
      paidAmount: (json['paid_amount'] is int)
          ? (json['paid_amount'] as int).toDouble()
          : json['paid_amount']?.toDouble(),
      status: json['status'] ?? 'pending',
      statusLabel: json['status_label'],
      appointmentDate:
          serviceTime != null && serviceTime.length >= 10 ? serviceTime.substring(0, 10) : null,
      appointmentTime:
          serviceTime != null && serviceTime.length >= 16 ? serviceTime.substring(11, 16) : null,
      storeName: store is Map ? store['name']?.toString() : null,
      storeAddress: store is Map ? store['address']?.toString() : null,
      remark: json['remark'],
      items: json['items'] != null
          ? (json['items'] as List).map((e) => OrderItem.fromJson(e)).toList()
          : null,
      // M4: 详情接口同时返回 coupon_id（券定义）与 user_coupon_id（领券记录），优先取后者
      userCouponId: json['user_coupon_id']?.toString() ?? json['coupon_id']?.toString(),
      couponDiscount: (json['coupon_discount'] is int)
          ? (json['coupon_discount'] as int).toDouble()
          : json['coupon_discount']?.toDouble(),
      createdAt: json['created_at'],
      paidAt: json['paid_at'],
      completedAt: json['completed_at'],
      cancelledAt: json['cancel_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'order_no': orderNo,
      'user_id': userId,
      'technician_id': technicianId,
      'technician_name': technicianName,
      'technician_avatar': technicianAvatar,
      'total_amount': totalAmount,
      'discount_amount': discountAmount,
      'paid_amount': paidAmount,
      'status': status,
      'status_label': statusLabel,
      'appointment_date': appointmentDate,
      'appointment_time': appointmentTime,
      'store_name': storeName,
      'store_address': storeAddress,
      'remark': remark,
      'items': items?.map((e) => e.toJson()).toList(),
      // M4: 下单传 user_coupon_id（用户领券记录 id）；coupon_id 直通已被服务端禁用
      'user_coupon_id': userCouponId,
      'coupon_discount': couponDiscount,
      'created_at': createdAt,
      'paid_at': paidAt,
      'completed_at': completedAt,
      'cancelled_at': cancelledAt,
    };
  }
}

class OrderItem {
  final String? id;
  final String serviceId;
  final String serviceName;
  final String? serviceImage;
  final double price;
  final int quantity;
  final String? specs;

  OrderItem({
    this.id,
    required this.serviceId,
    required this.serviceName,
    this.serviceImage,
    required this.price,
    required this.quantity,
    this.specs,
  });

  factory OrderItem.fromJson(Map<String, dynamic> json) {
    return OrderItem(
      id: json['id']?.toString(),
      serviceId: json['service_id']?.toString() ?? '',
      serviceName: json['name'] ?? '',
      serviceImage: json['cover_image'],
      price: (json['price'] is int)
          ? (json['price'] as int).toDouble()
          : json['price']?.toDouble() ?? 0.0,
      quantity: json['quantity'] ?? 1,
      specs: json['spec_info'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'service_id': serviceId,
      'name': serviceName,
      'cover_image': serviceImage,
      'price': price,
      'quantity': quantity,
      'spec_info': specs,
    };
  }
}
