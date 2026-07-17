import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/order_model.dart';
import '../../widgets/app_gradient_background.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_banner.dart';
import '../../widgets/order_card.dart';
import '../../widgets/pill_tab_selector.dart';
import 'order_detail_page.dart';

/// Orders tab with sub-views: All | Schedule | History.
class OrdersPage extends StatefulWidget {
  const OrdersPage({super.key, this.initialSubTab = allTab});

  static const int allTab = 0;
  static const int scheduleTab = 1;
  static const int historyTab = 2;

  final int initialSubTab;

  @override
  State<OrdersPage> createState() => OrdersPageState();
}

class OrdersPageState extends State<OrdersPage>
    with SingleTickerProviderStateMixin {
  late TabController _tabs;
  List<OrderModel> _items = const [];
  bool _loading = true;
  String? _error;
  String? _statusFilter;

  Future<void> reload() => _load();

  void selectSubTab(int index) {
    if (!mounted) return;
    if (_tabs.index != index) {
      _tabs.animateTo(index.clamp(0, 2));
    } else {
      _load();
    }
  }

  @override
  void initState() {
    super.initState();
    _tabs = TabController(
      length: 3,
      vsync: this,
      initialIndex: widget.initialSubTab.clamp(0, 2),
    );
    _tabs.addListener(_onTabChanged);
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void dispose() {
    _tabs.removeListener(_onTabChanged);
    _tabs.dispose();
    super.dispose();
  }

  void _onTabChanged() {
    if (_tabs.indexIsChanging) return;
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final repo = AppScope.of(context).orderRepository;
      final OrderPage page;
      switch (_tabs.index) {
        case OrdersPage.scheduleTab:
          page = await repo.getSchedule();
        case OrdersPage.historyTab:
          page = await repo.getHistory();
        default:
          page = await repo.getOrders(status: _statusFilter);
      }
      if (!mounted) return;
      setState(() {
        _items = page.items;
        _loading = false;
      });
      await AppScope.of(context).syncLocationTracking();
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.statusCode == 404 ? AppStrings.noCoasterAssigned : e.message;
        _items = const [];
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _openDetail(OrderModel order) async {
    final changed = await AppScope.pushScoped<bool>(
      context,
      OrderDetailPage(orderId: order.id),
    );
    if (changed == true && mounted) await _load();
  }

  static const _statusFilters = <String?>[
    null,
    'confirmed',
    'in_progress',
    'pending',
    'completed',
    'cancelled',
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.orders)),
      body: AppGradientBackground(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
              child: PillTabSelector(
                labels: const [
                  AppStrings.filterAll,
                  AppStrings.schedule,
                  AppStrings.history,
                ],
                selectedIndex: _tabs.index,
                onChanged: (i) => _tabs.animateTo(i),
              ),
            ),
            if (_tabs.index == OrdersPage.allTab)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
                child: PillTabSelector(
                  scrollable: true,
                  labels: _statusFilters
                      .map(
                        (f) => f == null
                            ? AppStrings.filterAll
                            : f.replaceAll('_', ' '),
                      )
                      .toList(),
                  selectedIndex: _statusFilters.indexOf(_statusFilter),
                  onChanged: (i) {
                    setState(() => _statusFilter = _statusFilters[i]);
                    _load();
                  },
                ),
              ),
            Expanded(
              child: RefreshIndicator(onRefresh: _load, child: _buildList()),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildList() {
    if (_loading && _items.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 120),
          Center(child: CircularProgressIndicator()),
        ],
      );
    }

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
      children: [
        if (_error != null) ...[
          ErrorBanner(
            message: _error!,
            onDismiss: () => setState(() => _error = null),
          ),
          TextButton(onPressed: _load, child: const Text(AppStrings.retry)),
          const SizedBox(height: 8),
        ],
        if (_items.isEmpty && _error == null)
          Padding(
            padding: const EdgeInsets.only(top: 48),
            child: EmptyState(
              icon: _tabs.index == OrdersPage.historyTab
                  ? Icons.history
                  : _tabs.index == OrdersPage.scheduleTab
                  ? Icons.calendar_month_outlined
                  : Icons.route_outlined,
              title: _tabs.index == OrdersPage.historyTab
                  ? AppStrings.noHistory
                  : _tabs.index == OrdersPage.scheduleTab
                  ? AppStrings.noSchedule
                  : AppStrings.noOrders,
            ),
          )
        else
          ..._items.map(
            (o) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: OrderCard(order: o, onTap: () => _openDetail(o)),
            ),
          ),
      ],
    );
  }
}
