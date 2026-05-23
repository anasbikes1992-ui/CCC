class User {
  final String id;
  final String name;
  final String phone;
  final String email;
  final String? profilePhoto;
  final DateTime createdAt;
  
  User({
    required this.id,
    required this.name,
    required this.phone,
    required this.email,
    this.profilePhoto,
    required this.createdAt,
  });
  
  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      phone: json['phone'],
      email: json['email'] ?? '',
      profilePhoto: json['profile_photo'],
      createdAt: DateTime.parse(json['created_at']),
    );
  }
  
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'phone': phone,
      'email': email,
      'profile_photo': profilePhoto,
      'created_at': createdAt.toIso8601String(),
    };
  }
}
