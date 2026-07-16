import 'package:flutter/material.dart';

import '../../features/hire_requests/hire_requests_page.dart';
import '../../features/home/dashboard_page.dart';
import '../../features/orders/orders_page.dart';
import '../../features/profile/profile_page.dart';
import '../di/app_scope.dart';
import '../strings.dart';

/// Bottom navigation: Home | Requests | Orders | Profile.
class MainShell extends StatefulWidget {
  const MainShell({
    super.key,
    required this.onLogout,
    this.initialTab = homeTab,
    this.initialUserName,
    this.initialEmail,
    this.initialPendingRequests = 0,
  });

  static const int homeTab = 0;
  static const int requestsTab = 1;
  static const int ordersTab = 2;
  static const int profileTab = 3;

  static final ValueNotifier<int?> tabRequests = ValueNotifier<int?>(null);

  static void requestTab(int index) {
    tabRequests.value = index;
  }

  final Future<void> Function() onLogout;
  final int initialTab;
  final String? initialUserName;
  final String? initialEmail;
  final int initialPendingRequests;

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  late int _index;

  final _hireRequestsKey = GlobalKey<HireRequestsPageState>();
  final _ordersKey = GlobalKey<OrdersPageState>();

  @override
  void initState() {
    super.initState();
    _index = widget.initialTab;
    MainShell.tabRequests.addListener(_onTabRequest);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final services = AppScope.of(context);
      if (widget.initialPendingRequests > 0) {
        services.setPendingHireCount(widget.initialPendingRequests);
      }
      services.refreshSessionSideEffects();
    });
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
    _refreshTab(next);
  }

  void _refreshTab(int index) {
    if (index == MainShell.requestsTab) {
      _hireRequestsKey.currentState?.reload();
    } else if (index == MainShell.ordersTab) {
      _ordersKey.currentState?.reload();
    }
  }

  void _selectTab(int i) {
    setState(() => _index = i);
    _refreshTab(i);
  }

  void _onPendingCountChanged(int count) {
    AppScope.of(context).setPendingHireCount(count);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: IndexedStack(
        index: _index,
        children: [
          DashboardPage(
            onLogout: widget.onLogout,
            initialUserName: widget.initialUserName,
            initialEmail: widget.initialEmail,
            onPendingCountChanged: _onPendingCountChanged,
          ),
          HireRequestsPage(
            key: _hireRequestsKey,
            onRequestsChanged: (count) {
              AppScope.of(context).setPendingHireCount(count);
            },
          ),
          OrdersPage(key: _ordersKey),
          ProfilePage(onLogout: widget.onLogout),
        ],
      ),
      bottomNavigationBar: ValueListenableBuilder<int>(
        valueListenable: AppScope.of(context).pendingHireCount,
        builder: (context, pending, _) {
          return NavigationBar(
            selectedIndex: _index,
            onDestinationSelected: _selectTab,
            destinations: [
              const NavigationDestination(
                icon: Icon(Icons.home_outlined),
                selectedIcon: Icon(Icons.home),
                label: AppStrings.homeTab,
              ),
              NavigationDestination(
                icon: _badgeIcon(Icons.inbox_outlined, pending),
                selectedIcon: _badgeIcon(Icons.inbox, pending),
                label: AppStrings.requestsTab,
              ),
              const NavigationDestination(
                icon: Icon(Icons.route_outlined),
                selectedIcon: Icon(Icons.route),
                label: AppStrings.ordersTab,
              ),
              const NavigationDestination(
                icon: Icon(Icons.person_outline),
                selectedIcon: Icon(Icons.person),
                label: AppStrings.profileTab,
              ),
            ],
          );
        },
      ),
    );
  }

  Widget _badgeIcon(IconData icon, int count) {
    if (count <= 0) return Icon(icon);
    return Badge(
      label: Text(count > 99 ? '99+' : '$count'),
      child: Icon(icon),
    );
  }
}
