import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/order_model.dart';
import '../../widgets/app_gradient_background.dart';
import '../../widgets/empty_state.dart';
import '../../widgets/error_banner.dart';
import '../../widgets/hire_request_card.dart';
import '../orders/order_detail_page.dart';

/// Pending hire requests with Accept / Decline.
class HireRequestsPage extends StatefulWidget {
  const HireRequestsPage({super.key, this.onRequestsChanged});

  /// Called with the current pending count after load / accept / decline.
  final ValueChanged<int>? onRequestsChanged;

  @override
  State<HireRequestsPage> createState() => HireRequestsPageState();
}

class HireRequestsPageState extends State<HireRequestsPage> {
  List<OrderModel> _items = const [];
  bool _loading = true;
  String? _error;
  int? _busyId;
  bool _accepting = true;
  bool _noCoaster = false;

  Future<void> load() => _load();
  Future<void> reload() => _load();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
      _noCoaster = false;
    });
    try {
      final page = await AppScope.of(context).orderRepository.getHireRequests();
      if (!mounted) return;
      setState(() {
        _items = page.items;
        _loading = false;
      });
      widget.onRequestsChanged?.call(page.length);
      AppScope.of(context).setPendingHireCount(page.length);
    } on ApiException catch (e) {
      if (!mounted) return;
      if (e.statusCode == 404) {
        setState(() {
          _items = const [];
          _noCoaster = true;
          _loading = false;
        });
        widget.onRequestsChanged?.call(0);
        AppScope.of(context).setPendingHireCount(0);
        return;
      }
      setState(() {
        _error = e.message;
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

  Future<void> _accept(OrderModel order) async {
    setState(() {
      _busyId = order.id;
      _accepting = true;
    });
    try {
      await AppScope.of(context).orderRepository.acceptHireRequest(order.id);
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text(AppStrings.hireAccepted)));
      await _load();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busyId = null);
    }
  }

  Future<void> _decline(OrderModel order) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text(AppStrings.declineHire),
        content: Text('${AppStrings.declineHireConfirm}\n\n${order.title}'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text(AppStrings.cancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text(AppStrings.decline),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    setState(() {
      _busyId = order.id;
      _accepting = false;
    });
    try {
      await AppScope.of(context).orderRepository.declineHireRequest(order.id);
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text(AppStrings.hireDeclined)));
      await _load();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busyId = null);
    }
  }

  Future<void> _openDetail(OrderModel order) async {
    final changed = await AppScope.pushScoped<bool>(
      context,
      OrderDetailPage(orderId: order.id),
    );
    if (changed == true && mounted) await _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.hireRequests)),
      body: AppGradientBackground(
        child: RefreshIndicator(onRefresh: _load, child: _buildBody()),
      ),
    );
  }

  Widget _buildBody() {
    if (_loading && _items.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 120),
          Center(child: CircularProgressIndicator()),
        ],
      );
    }

    if (_noCoaster) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: const [
          SizedBox(height: 48),
          EmptyState(
            icon: Icons.directions_bus_outlined,
            title: AppStrings.noCoasterAssigned,
            subtitle: AppStrings.noCoasterAssignedHint,
          ),
        ],
      );
    }

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
      children: [
        if (_error != null) ...[
          ErrorBanner(
            message: _error!,
            onDismiss: () => setState(() => _error = null),
          ),
          TextButton(onPressed: _load, child: const Text(AppStrings.retry)),
          const SizedBox(height: 8),
        ],
        if (_items.isEmpty)
          const Padding(
            padding: EdgeInsets.only(top: 48),
            child: EmptyState(
              icon: Icons.inbox_outlined,
              title: AppStrings.noHireRequests,
              subtitle: AppStrings.noHireRequestsHint,
            ),
          )
        else
          ..._items.map((order) {
            final busy = _busyId == order.id;
            final canRespond = order.canRespondToHire;
            return Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: HireRequestCard(
                request: order,
                onTap: () => _openDetail(order),
                showActions: canRespond,
                isAccepting: busy && _accepting,
                isDeclining: busy && !_accepting,
                onAccept: canRespond && !busy ? () => _accept(order) : null,
                onDecline: canRespond && !busy ? () => _decline(order) : null,
              ),
            );
          }),
      ],
    );
  }
}
