class CouponModel {
  final int id;
  final String name;
  final int type;
  final String? typeLabel;
  final double discountAmount;
  final double? minAmount;
  final String? startDate;
  final String? endDate;
  final String? status;
  final String? statusLabel;
  final String? description;
  final String? createdAt;

  CouponModel({
    required this.id,
    required this.name,
    required this.type,
    this.typeLabel,
    required this.discountAmount,
    this.minAmount,
    this.startDate,
    this.endDate,
    this.status,
    this.statusLabel,
    this.description,
    this.createdAt,
  });

  factory CouponModel.fromJson(Map<String, dynamic> json) {
    return CouponModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      type: json['type'] ?? 1,
      typeLabel: json['type_label'],
      discountAmount: (json['discount_amount'] is int)
          ? (json['discount_amount'] as int).toDouble()
          : json['discount_amount']?.toDouble() ?? 0.0,
      minAmount: (json['min_amount'] is int)
          ? (json['min_amount'] as int).toDouble()
          : json['min_amount']?.toDouble(),
      startDate: json['start_date'],
      endDate: json['end_date'],
      status: json['status'],
      statusLabel: json['status_label'],
      description: json['description'],
      createdAt: json['created_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'type': type,
      'type_label': typeLabel,
      'discount_amount': discountAmount,
      'min_amount': minAmount,
      'start_date': startDate,
      'end_date': endDate,
      'status': status,
      'status_label': statusLabel,
      'description': description,
      'created_at': createdAt,
    };
  }
}
