class Route {
  final String id;
  final String code;
  final String originHub;
  final String destinationHub;
  final int distance;
  final bool isActive;
  final Map<String, int> pricingMatrix; // size -> price

  Route({
    required this.id,
    required this.code,
    required this.originHub,
    required this.destinationHub,
    required this.distance,
    required this.isActive,
    required this.pricingMatrix,
  });

  factory Route.fromJson(Map<String, dynamic> json) {
    return Route(
      id: json['id'],
      code: json['code'],
      originHub: json['origin_hub'],
      destinationHub: json['destination_hub'],
      distance: json['distance'],
      isActive: json['is_active'],
      pricingMatrix: Map<String, int>.from(json['pricing_matrix'] ?? {}),
    );
  }

  String get displayName => '$originHub ↔ $destinationHub';

  int getPriceForSize(String size) {
    return pricingMatrix[size] ?? 0;
  }
}
