class OrderModel {
  final int id;
  final String orderNo;
  final int? userId;
  final int? technicianId;
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
  final int? couponId;
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
    this.couponId,
    this.couponDiscount,
    this.createdAt,
    this.paidAt,
    this.completedAt,
    this.cancelledAt,
  });

  factory OrderModel.fromJson(Map<String, dynamic> json) {
    return OrderModel(
      id: json['id'] ?? 0,
      orderNo: json['order_no'] ?? '',
      userId: json['user_id'],
      technicianId: json['technician_id'],
      technicianName: json['technician_name'],
      technicianAvatar: json['technician_avatar'],
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
      appointmentDate: json['appointment_date'],
      appointmentTime: json['appointment_time'],
      storeName: json['store_name'],
      storeAddress: json['store_address'],
      remark: json['remark'],
      items: json['items'] != null
          ? (json['items'] as List).map((e) => OrderItem.fromJson(e)).toList()
          : null,
      couponId: json['coupon_id'],
      couponDiscount: (json['coupon_discount'] is int)
          ? (json['coupon_discount'] as int).toDouble()
          : json['coupon_discount']?.toDouble(),
      createdAt: json['created_at'],
      paidAt: json['paid_at'],
      completedAt: json['completed_at'],
      cancelledAt: json['cancelled_at'],
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
      'coupon_id': couponId,
      'coupon_discount': couponDiscount,
      'created_at': createdAt,
      'paid_at': paidAt,
      'completed_at': completedAt,
      'cancelled_at': cancelledAt,
    };
  }
}

class OrderItem {
  final int? id;
  final int serviceId;
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
      id: json['id'],
      serviceId: json['service_id'] ?? 0,
      serviceName: json['service_name'] ?? '',
      serviceImage: json['service_image'],
      price: (json['price'] is int)
          ? (json['price'] as int).toDouble()
          : json['price']?.toDouble() ?? 0.0,
      quantity: json['quantity'] ?? 1,
      specs: json['specs'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'service_id': serviceId,
      'service_name': serviceName,
      'service_image': serviceImage,
      'price': price,
      'quantity': quantity,
      'specs': specs,
    };
  }
}
