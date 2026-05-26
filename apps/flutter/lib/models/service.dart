class ServiceModel {
  final int id;
  final String name;
  final String? description;
  final double price;
  final double? originalPrice;
  final String? coverImage;
  final List<String>? images;
  final int? categoryId;
  final String? categoryName;
  final int? duration;
  final int? salesCount;
  final double? rating;
  final bool? isHot;
  final List<ServiceSpec>? specs;
  final String? createdAt;

  ServiceModel({
    required this.id,
    required this.name,
    this.description,
    required this.price,
    this.originalPrice,
    this.coverImage,
    this.images,
    this.categoryId,
    this.categoryName,
    this.duration,
    this.salesCount,
    this.rating,
    this.isHot,
    this.specs,
    this.createdAt,
  });

  factory ServiceModel.fromJson(Map<String, dynamic> json) {
    return ServiceModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      description: json['description'],
      price: (json['price'] is int)
          ? (json['price'] as int).toDouble()
          : json['price']?.toDouble() ?? 0.0,
      originalPrice: (json['original_price'] is int)
          ? (json['original_price'] as int).toDouble()
          : json['original_price']?.toDouble(),
      coverImage: json['cover_image'],
      images: json['images'] != null ? List<String>.from(json['images']) : null,
      categoryId: json['category_id'],
      categoryName: json['category_name'],
      duration: json['duration'],
      salesCount: json['sales_count'],
      rating: json['rating']?.toDouble(),
      isHot: json['is_hot'],
      specs: json['specs'] != null
          ? (json['specs'] as List).map((e) => ServiceSpec.fromJson(e)).toList()
          : null,
      createdAt: json['created_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'description': description,
      'price': price,
      'original_price': originalPrice,
      'cover_image': coverImage,
      'images': images,
      'category_id': categoryId,
      'category_name': categoryName,
      'duration': duration,
      'sales_count': salesCount,
      'rating': rating,
      'is_hot': isHot,
      'specs': specs?.map((e) => e.toJson()).toList(),
      'created_at': createdAt,
    };
  }
}

class ServiceSpec {
  final String name;
  final String? value;
  final double? priceAdjust;

  ServiceSpec({required this.name, this.value, this.priceAdjust});

  factory ServiceSpec.fromJson(Map<String, dynamic> json) {
    return ServiceSpec(
      name: json['name'] ?? '',
      value: json['value'],
      priceAdjust: (json['price_adjust'] is int)
          ? (json['price_adjust'] as int).toDouble()
          : json['price_adjust']?.toDouble(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'name': name,
      'value': value,
      'price_adjust': priceAdjust,
    };
  }
}
