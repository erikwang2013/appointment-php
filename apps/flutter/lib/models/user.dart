class UserModel {
  final int id;
  final String phone;
  final String? nickname;
  final String? avatar;
  final String? gender;
  final String? birthday;
  final String? role;
  final double? balance;
  final int? points;
  final String? referralCode;
  final String? createdAt;

  UserModel({
    required this.id,
    required this.phone,
    this.nickname,
    this.avatar,
    this.gender,
    this.birthday,
    this.role,
    this.balance,
    this.points,
    this.referralCode,
    this.createdAt,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'] ?? 0,
      phone: json['phone'] ?? '',
      nickname: json['nickname'],
      avatar: json['avatar'],
      gender: json['gender'],
      birthday: json['birthday'],
      role: json['role'],
      balance: (json['balance'] is int)
          ? (json['balance'] as int).toDouble()
          : json['balance']?.toDouble(),
      points: json['points'],
      referralCode: json['referral_code'],
      createdAt: json['created_at'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'phone': phone,
      'nickname': nickname,
      'avatar': avatar,
      'gender': gender,
      'birthday': birthday,
      'role': role,
      'balance': balance,
      'points': points,
      'referral_code': referralCode,
      'created_at': createdAt,
    };
  }
}
