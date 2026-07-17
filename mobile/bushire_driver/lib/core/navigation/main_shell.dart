import 'package:flutter/material.dart';

import '../../features/hire_requests/hire_requests_page.dart';
import '../../features/home/dashboard_page.dart';
import '../../features/orders/orders_page.dart';
import '../../features/profile/profile_page.dart';
import '../../widgets/app_gradient_background.dart';
import '../di/app_scope.dart';
import '../strings.dart';
import '../theme/app_colors.dart';

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
      // Solid fallback + extendBody so the gradient (not black) shows behind
      // the floating pill nav.
      backgroundColor: AppColors.gradientEnd,
      extendBody: true,
      body: AppGradientBackground(
        child: IndexedStack(
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
      ),
      bottomNavigationBar: ValueListenableBuilder<int>(
        valueListenable: AppScope.of(context).pendingHireCount,
        builder: (context, pending, _) {
          return _FloatingPillNav(
            selectedIndex: _index,
            pendingRequests: pending,
            onDestinationSelected: _selectTab,
          );
        },
      ),
    );
  }
}

/// TripWay-style floating white pill bar with circular primary active indicator.
class _FloatingPillNav extends StatelessWidget {
  const _FloatingPillNav({
    required this.selectedIndex,
    required this.pendingRequests,
    required this.onDestinationSelected,
  });

  final int selectedIndex;
  final int pendingRequests;
  final ValueChanged<int> onDestinationSelected;

  static const _items = <(IconData, IconData, String)>[
    (Icons.home_outlined, Icons.home, AppStrings.homeTab),
    (Icons.inbox_outlined, Icons.inbox, AppStrings.requestsTab),
    (Icons.route_outlined, Icons.route, AppStrings.ordersTab),
    (Icons.person_outline, Icons.person, AppStrings.profileTab),
  ];

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      minimum: const EdgeInsets.fromLTRB(16, 0, 16, 12),
      child: Padding(
        padding: const EdgeInsets.only(top: 4),
        child: Material(
          color: Colors.transparent,
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
            decoration: BoxDecoration(
              color: AppColors.cardSurface,
              borderRadius: BorderRadius.circular(28),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.10),
                  blurRadius: 24,
                  offset: const Offset(0, 8),
                ),
                BoxShadow(
                  color: AppColors.seed.withValues(alpha: 0.06),
                  blurRadius: 12,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Row(
              children: List.generate(_items.length, (i) {
                final (outlined, filled, label) = _items[i];
                final selected = i == selectedIndex;
                final showBadge = i == MainShell.requestsTab && pendingRequests > 0;

                return Expanded(
                  child: InkWell(
                    borderRadius: BorderRadius.circular(22),
                    onTap: () => onDestinationSelected(i),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          AnimatedContainer(
                            duration: const Duration(milliseconds: 200),
                            curve: Curves.easeOut,
                            width: 42,
                            height: 42,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: selected
                                  ? AppColors.seed
                                  : Colors.transparent,
                            ),
                            child: Center(
                              child: Badge(
                                isLabelVisible: showBadge,
                                label: Text(
                                  pendingRequests > 99
                                      ? '99+'
                                      : '$pendingRequests',
                                ),
                                child: Icon(
                                  selected ? filled : outlined,
                                  size: 22,
                                  color: selected
                                      ? Colors.white
                                      : AppColors.mutedText,
                                ),
                              ),
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            label,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight:
                                  selected ? FontWeight.w700 : FontWeight.w500,
                              color: selected
                                  ? AppColors.seed
                                  : AppColors.mutedText,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                );
              }),
            ),
          ),
        ),
      ),
    );
  }
}
