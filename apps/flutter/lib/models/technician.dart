class TechnicianModel {
  final int id;
  final String name;
  final String? avatar;
  final String? phone;
  final String? intro;
  final double? rating;
  final int? orderCount;
  final int? fansCount;
  final List<String>? tags;
  final List<int>? serviceIds;
  final bool? isAvailable;
  final String? storeName;
  final String? createdAt;

  TechnicianModel({
    required this.id,
    required this.name,
    this.avatar,
    this.phone,
    this.intro,
    this.rating,
    this.orderCount,
    this.fansCount,
    this.tags,
    this.serviceIds,
    this.isAvailable,
    this.storeName,
    this.createdAt,
  });

  factory TechnicianModel.fromJson(Map<String, dynamic> json) {
    return TechnicianModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      avatar: json['avatar'],
      phone: json['phone'],
      intro: json['intro'],
      rating: json['rating']?.toDouble(),
      orderCount: json['order_count'],
      fansCount: json['fans_count'],
      tags: json['tags'] != null ? List<String>.from(json['tags']) : null,
      serviceIds: json['service_ids'] != null ? List<int>.from(json['service_ids']) : null,
      isAvailable: json['is_available'],
      storeName: json['store_name'],
      createdAt: json['created_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'avatar': avatar,
      'phone': phone,
      'intro': intro,
      'rating': rating,
      'order_count': orderCount,
      'fans_count': fansCount,
      'tags': tags,
      'service_ids': serviceIds,
      'is_available': isAvailable,
      'store_name': storeName,
      'created_at': createdAt,
    };
  }
}
