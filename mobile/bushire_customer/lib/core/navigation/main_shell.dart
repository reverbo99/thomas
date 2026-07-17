import 'package:flutter/material.dart';

import '../../features/home/browse_page.dart';
import '../../features/home/dashboard_page.dart';
import '../../features/profile/profile_page.dart';
import '../../features/trips/trips_page.dart';
import '../../widgets/app_gradient_background.dart';
import '../di/app_scope.dart';
import '../strings.dart';

/// Bottom navigation: Home | Book | My Trips | Profile.
class MainShell extends StatefulWidget {
  const MainShell({
    super.key,
    required this.onLogout,
    this.initialTab = homeTab,
  });

  static const int homeTab = 0;
  static const int bookTab = 1;
  static const int tripsTab = 2;
  static const int profileTab = 3;

  static final ValueNotifier<int?> tabRequests = ValueNotifier<int?>(null);

  static void requestTab(int index) {
    tabRequests.value = index;
  }

  final Future<void> Function() onLogout;
  final int initialTab;

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  late int _index;
  final _tripsKey = GlobalKey<TripsPageState>();

  @override
  void initState() {
    super.initState();
    _index = widget.initialTab;
    MainShell.tabRequests.addListener(_onTabRequest);
  }

  @override
  void dispose() {
    MainShell.tabRequests.removeListener(_onTabRequest);
    super.dispose();
  }

  void _onTabRequest() {
    final next = MainShell.tabRequests.value;
    if (next == null || !mounted) return;
    setState(() => _index = next);
    MainShell.tabRequests.value = null;
    if (next == MainShell.tripsTab) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _tripsKey.currentState?.load();
      });
    }
  }

  void _selectTab(int i) {
    setState(() => _index = i);
    if (i == MainShell.tripsTab) {
      _tripsKey.currentState?.load();
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = AppScope.of(context).authRepository.currentUser;

    return Scaffold(
      body: AppGradientBackground(
        child: IndexedStack(
          index: _index,
          children: [
            DashboardPage(
              userName: user?.name ?? 'Customer',
              userEmail: user?.email,
              onLogout: widget.onLogout,
              onBrowseCoasters: () => _selectTab(MainShell.bookTab),
              onMyTrips: () => _selectTab(MainShell.tripsTab),
              onProfile: () => _selectTab(MainShell.profileTab),
            ),
            const BrowsePage(),
            TripsPage(key: _tripsKey),
            ProfilePage(onLogout: widget.onLogout),
          ],
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: _selectTab,
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: AppStrings.homeTab,
          ),
          NavigationDestination(
            icon: Icon(Icons.directions_bus_outlined),
            selectedIcon: Icon(Icons.directions_bus),
            label: AppStrings.bookTab,
          ),
          NavigationDestination(
            icon: Icon(Icons.confirmation_number_outlined),
            selectedIcon: Icon(Icons.confirmation_number),
            label: AppStrings.tripsTab,
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: AppStrings.profileTab,
          ),
        ],
      ),
    );
  }
}
