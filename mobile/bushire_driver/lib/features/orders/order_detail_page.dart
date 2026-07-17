import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/format.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/order_model.dart';
import '../../widgets/app_gradient_background.dart';
import '../../widgets/error_banner.dart';
import '../../widgets/phone_tile.dart';
import '../../widgets/primary_button.dart';
import '../../widgets/status_chip.dart';

/// Order detail with Start / Complete trip actions.
class OrderDetailPage extends StatefulWidget {
  const OrderDetailPage({super.key, required this.orderId});

  final int orderId;

  @override
  State<OrderDetailPage> createState() => _OrderDetailPageState();
}

class _OrderDetailPageState extends State<OrderDetailPage> {
  OrderModel? _order;
  bool _loading = true;
  bool _acting = false;
  String? _error;
  bool _changed = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final order = await AppScope.of(
        context,
      ).orderRepository.getOrder(widget.orderId);
      if (!mounted) return;
      setState(() {
        _order = order;
        _loading = false;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
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

  Future<void> _startTrip() async {
    final order = _order;
    if (order == null) return;
    setState(() => _acting = true);
    try {
      final updated = await AppScope.of(
        context,
      ).orderRepository.startTrip(order.id);
      _changed = true;
      if (!mounted) return;
      setState(() => _order = updated);
      await AppScope.of(context).locationTracker.start();
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text(AppStrings.tripStarted)));
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _acting = false);
    }
  }

  Future<void> _completeTrip() async {
    final order = _order;
    if (order == null) return;
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text(AppStrings.completeTrip),
        content: const Text(AppStrings.completeTripConfirm),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text(AppStrings.cancel),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text(AppStrings.completeTrip),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;

    setState(() => _acting = true);
    try {
      final updated = await AppScope.of(
        context,
      ).orderRepository.completeTrip(order.id);
      _changed = true;
      if (!mounted) return;
      setState(() => _order = updated);
      await AppScope.of(context).syncLocationTracking();
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text(AppStrings.tripCompleted)));
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _acting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        Navigator.of(context).pop(_changed);
      },
      child: Scaffold(
        appBar: AppBar(
          title: Text(_order?.orderCode ?? AppStrings.orderDetail),
          leading: IconButton(
            icon: const Icon(Icons.arrow_back),
            onPressed: () => Navigator.of(context).pop(_changed),
          ),
        ),
        body: AppGradientBackground(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : _order == null
              ? _errorBody()
              : _detailBody(_order!),
        ),
      ),
    );
  }

  Widget _errorBody() {
    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        if (_error != null) ErrorBanner(message: _error!),
        TextButton(onPressed: _load, child: const Text(AppStrings.retry)),
      ],
    );
  }

  Widget _detailBody(OrderModel order) {
    final theme = Theme.of(context);
    final muted = theme.colorScheme.onSurfaceVariant;

    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
      children: [
        if (_error != null) ...[
          ErrorBanner(
            message: _error!,
            onDismiss: () => setState(() => _error = null),
          ),
          const SizedBox(height: 12),
        ],
        Row(
          children: [
            Expanded(
              child: Text(
                order.title,
                style: theme.textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            StatusChip.order(order.orderStatus),
          ],
        ),
        if (order.orderCode != null) ...[
          const SizedBox(height: 4),
          Text(
            order.orderCode!,
            style: theme.textTheme.bodyMedium?.copyWith(color: muted),
          ),
        ],
        const SizedBox(height: 20),
        _section(AppStrings.route, [
          _row(AppStrings.pickup, order.pickupLocation),
          _row(AppStrings.dropoff, order.dropoffLocation),
          _row(AppStrings.when, order.whenLabel),
          if (order.passengersCount != null)
            _row(AppStrings.passengers, '${order.passengersCount}'),
          if (order.distanceKm != null)
            _row('Distance', AppFormat.km(order.distanceKm)),
        ]),
        const SizedBox(height: 16),
        _section(AppStrings.customer, [
          _row(AppStrings.name, order.customerName),
          if (order.customerPhone != null && order.customerPhone!.isNotEmpty)
            PhoneTile(phone: order.customerPhone!),
          _row(AppStrings.email, order.customerEmail),
        ]),
        if (order.purpose != null || order.notes != null) ...[
          const SizedBox(height: 16),
          _section(AppStrings.notes, [
            if (order.purpose != null) _row(AppStrings.purpose, order.purpose),
            if (order.notes != null) _row(AppStrings.notes, order.notes),
          ]),
        ],
        const SizedBox(height: 16),
        _section(AppStrings.payment, [
          _row(AppStrings.amount, AppFormat.tzs(order.totalAmount)),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 6),
            child: Row(
              children: [
                SizedBox(
                  width: 100,
                  child: Text(
                    AppStrings.paymentStatus,
                    style: theme.textTheme.bodySmall?.copyWith(color: muted),
                  ),
                ),
                StatusChip.payment(order.paymentStatus),
              ],
            ),
          ),
        ]),
        const SizedBox(height: 28),
        if (order.canStartTrip)
          PrimaryButton(
            label: AppStrings.startTrip,
            icon: Icons.play_arrow,
            isLoading: _acting,
            onPressed: _acting ? null : _startTrip,
          ),
        if (order.canCompleteTrip)
          PrimaryButton(
            label: AppStrings.completeTrip,
            icon: Icons.flag,
            isLoading: _acting,
            onPressed: _acting ? null : _completeTrip,
          ),
      ],
    );
  }

  Widget _section(String title, List<Widget> children) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: Theme.of(
            context,
          ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 8),
        Card(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            child: Column(children: children),
          ),
        ),
      ],
    );
  }

  Widget _row(String label, String? value) {
    if (value == null || value.trim().isEmpty) {
      return const SizedBox.shrink();
    }
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ),
          Expanded(child: Text(value, style: theme.textTheme.bodyMedium)),
        ],
      ),
    );
  }
}
