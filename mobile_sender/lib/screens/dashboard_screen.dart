import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../config/app_theme.dart';
import '../providers/auth_provider.dart';
import '../providers/parcel_provider.dart';
import '../models/parcel.dart';
import 'book_parcel_screen.dart';
import 'parcel_details_screen.dart';
import 'profile_screen.dart';
import '../widgets/parcel_card.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  int _selectedIndex = 0;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadParcels();
  }

  Future<void> _loadParcels() async {
    await Provider.of<ParcelProvider>(context, listen: false).fetchParcels();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _onNavTap(int index) {
    if (index == 1) {
      Navigator.push(
        context,
        MaterialPageRoute(builder: (context) => const BookParcelScreen()),
      ).then((_) => _loadParcels());
    } else {
      setState(() => _selectedIndex = index);
    }
  }

  @override
  Widget build(BuildContext context) {
    final screens = [
      _buildHomeScreen(),
      const SizedBox.shrink(),
      const ProfileScreen(),
    ];

    return Scaffold(
      body: IndexedStack(
        index: _selectedIndex,
        children: screens,
      ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _selectedIndex,
        onTap: _onNavTap,
        selectedItemColor: AppTheme.primary,
        unselectedItemColor: AppTheme.textSecondary,
        items: const [
          BottomNavigationBarItem(icon: Icon(Icons.home), label: 'Home'),
          BottomNavigationBarItem(icon: Icon(Icons.add_box_rounded), label: 'Book'),
          BottomNavigationBarItem(icon: Icon(Icons.person), label: 'Profile'),
        ],
      ),
    );
  }

  Widget _buildHomeScreen() {
    return NestedScrollView(
      headerSliverBuilder: (context, innerBoxIsScrolled) => [
        SliverAppBar(
          floating: true,
          title: Consumer<AuthProvider>(
            builder: (context, auth, _) => Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Hello, ${auth.user?.name ?? "User"}!', style: const TextStyle(fontSize: 20)),
                Text('Track & manage your parcels', style: Theme.of(context).textTheme.bodySmall),
              ],
            ),
          ),
          actions: [
            IconButton(
              icon: const Icon(Icons.notifications_outlined),
              onPressed: () {},
            ),
          ],
        ),
      ],
      body: RefreshIndicator(
        onRefresh: _loadParcels,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(AppTheme.spaceMd),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildQuickActions(),
              const SizedBox(height: AppTheme.spaceLg),
              _buildParcelsTabs(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildQuickActions() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Quick Actions', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
        const SizedBox(height: AppTheme.spaceMd),
        Row(
          children: [
            Expanded(child: _buildActionCard('Book Parcel', Icons.add_box_rounded, AppTheme.primary, () {
              Navigator.push(context, MaterialPageRoute(builder: (context) => const BookParcelScreen()));
            })),
            const SizedBox(width: AppTheme.spaceMd),
            Expanded(child: _buildActionCard('Track', Icons.qr_code_scanner, AppTheme.secondary, () {})),
          ],
        ),
      ],
    );
  }

  Widget _buildActionCard(String label, IconData icon, Color color, VoidCallback onTap) {
    return Card(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppTheme.radiusMd),
        child: Padding(
          padding: const EdgeInsets.all(AppTheme.spaceMd),
          child: Column(
            children: [
              Container(
                padding: const EdgeInsets.all(AppTheme.spaceMd),
                decoration: BoxDecoration(color: color.withValues(alpha: 0.1), shape: BoxShape.circle),
                child: Icon(icon, color: color, size: 32),
              ),
              const SizedBox(height: AppTheme.spaceSm),
              Text(label, style: const TextStyle(fontWeight: FontWeight.w600)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildParcelsTabs() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('My Parcels', style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600)),
        const SizedBox(height: AppTheme.spaceMd),
        TabBar(
          controller: _tabController,
          labelColor: AppTheme.primary,
          unselectedLabelColor: AppTheme.textSecondary,
          indicatorColor: AppTheme.primary,
          tabs: const [Tab(text: 'Active'), Tab(text: 'Delivered')],
        ),
        const SizedBox(height: AppTheme.spaceMd),
        Consumer<ParcelProvider>(
          builder: (context, provider, _) {
            if (provider.isLoading) {
              return const Center(child: CircularProgressIndicator());
            }
            
            return SizedBox(
              height: 400,
              child: TabBarView(
                controller: _tabController,
                children: [
                  _buildParcelsList(provider.activeParcels),
                  _buildParcelsList(provider.deliveredParcels),
                ],
              ),
            );
          },
        ),
      ],
    );
  }

  Widget _buildParcelsList(List<Parcel> parcels) {
    if (parcels.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.inbox_outlined, size: 64, color: AppTheme.textLight),
            const SizedBox(height: AppTheme.spaceMd),
            Text('No parcels found', style: TextStyle(color: AppTheme.textLight)),
          ],
        ),
      );
    }

    return ListView.builder(
      itemCount: parcels.length,
      itemBuilder: (context, index) {
        return ParcelCard(
          parcel: parcels[index],
          onTap: () {
            Navigator.push(
              context,
              MaterialPageRoute(
                builder: (context) => ParcelDetailsScreen(parcelId: parcels[index].id),
              ),
            );
          },
        );
      },
    );
  }
}
