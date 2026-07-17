import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/navigation/main_shell.dart';
import '../../core/strings.dart';
import '../../core/theme/app_colors.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/coaster_model.dart';
import '../../data/models/user_model.dart';
import '../../data/repositories/auth_repository.dart';
import '../../data/repositories/coaster_repository.dart';
import '../../features/history/history_page.dart';
import '../../features/schedule/schedule_page.dart';
import '../../widgets/app_gradient_background.dart';
import '../../widgets/coaster_summary_card.dart';
import '../../widgets/dashboard_stats_row.dart';
import '../../widgets/error_banner.dart';
import '../../widgets/hero_header_card.dart';

/// Driver home: TripWay-style greeting, hero band, overlapping coaster card,
/// and quick-action shortcuts.
class DashboardPage extends StatefulWidget {
  const DashboardPage({
    super.key,
    required this.onLogout,
    this.initialUserName,
    this.initialEmail,
    this.authRepository,
    this.coasterRepository,
    this.onPendingCountChanged,
  });

  final Future<void> Function() onLogout;

  /// Shown immediately while profile/coaster load (from login/session cache).
  final String? initialUserName;
  final String? initialEmail;

  /// Optional overrides (tests). Defaults to [AppScope] repositories.
  final AuthRepository? authRepository;
  final CoasterRepository? coasterRepository;

  /// Notifies shell when pending hire count changes (nav badge).
  final ValueChanged<int>? onPendingCountChanged;

  @override
  State<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends State<DashboardPage> {
  UserModel? _user;
  CoasterModel? _coaster;
  int _pendingHireRequests = 0;
  bool _loading = true;
  String? _error;

  AuthRepository get _auth =>
      widget.authRepository ?? AppScope.of(context).authRepository;

  CoasterRepository get _coasters =>
      widget.coasterRepository ?? AppScope.of(context).coasterRepository;

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
      final profile = await _auth.getProfile();
      var coaster = profile.coaster;
      coaster ??= await _coasters.getCoasterOrNull();

      if (!mounted) return;
      setState(() {
        _user = profile.user;
        _coaster = coaster;
        _pendingHireRequests = profile.pendingHireRequests;
        _loading = false;
      });
      widget.onPendingCountChanged?.call(profile.pendingHireRequests);
      final scope = AppScope.maybeOf(context);
      scope?.setPendingHireCount(profile.pendingHireRequests);
      await scope?.syncLocationTracking(hasCoaster: coaster != null);
    } on ApiException catch (e) {
      if (e.isUnauthorized) {
        await widget.onLogout();
        return;
      }
      if (!mounted) return;
      setState(() {
        _error = e.message;
        _loading = false;
        _user ??= _auth.currentUser;
        _coaster ??= _auth.currentCoaster;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  void _openRequests() => MainShell.requestTab(MainShell.requestsTab);

  void _openOrders() => MainShell.requestTab(MainShell.ordersTab);

  void _openPrimaryCta() {
    if (_pendingHireRequests > 0) {
      _openRequests();
    } else {
      _openOrders();
    }
  }

  String get _ctaLabel => _pendingHireRequests > 0
      ? AppStrings.viewHireRequests
      : AppStrings.viewOrders;

  /// Prefer assigned plate; otherwise email (or brand fallback).
  String? get _subtitle {
    final plate = _coaster?.plateNumber?.trim();
    if (plate != null && plate.isNotEmpty) {
      return '${AppStrings.plate}: $plate';
    }
    final email = _user?.email ?? widget.initialEmail;
    if (email != null && email.isNotEmpty) return email;
    return AppStrings.brandName;
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final displayName =
        _user?.name ?? widget.initialUserName ?? AppStrings.brandName;
    final firstName = displayName.trim().split(RegExp(r'\s+')).first;

    return Scaffold(
      backgroundColor: Colors.transparent,
      body: AppGradientBackground(
        child: SafeArea(
          bottom: false,
          child: RefreshIndicator(
            onRefresh: _load,
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 28),
              children: [
                HeroHeaderCard(
                  greeting: '${AppStrings.hello}, $firstName',
                  subtitle: _subtitle,
                  notificationCount: _pendingHireRequests,
                  onNotificationTap: _openRequests,
                  onAvatarTap: () =>
                      MainShell.requestTab(MainShell.profileTab),
                ),
                const SizedBox(height: 20),
                if (_loading)
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 48),
                    child: Center(child: CircularProgressIndicator()),
                  )
                else ...[
                  if (_error != null) ...[
                    ErrorBanner(
                      message: _error!,
                      onDismiss: () => setState(() => _error = null),
                    ),
                    Align(
                      alignment: Alignment.centerLeft,
                      child: TextButton(
                        onPressed: _load,
                        child: const Text(AppStrings.retry),
                      ),
                    ),
                    const SizedBox(height: 12),
                  ],
                  // Unified assigned-coaster card with embedded photo.
                  if (_coaster != null)
                    CoasterSummaryCard(
                      name: _coaster!.name,
                      imageUrl: _coaster!.imageUrl,
                      plateNumber: _coaster!.plateNumber,
                      status: _coaster!.status,
                      model: _coaster!.model,
                      capacity: _coaster!.capacity,
                      pendingHireRequests: _pendingHireRequests,
                      ctaLabel: _ctaLabel,
                      onCta: _openPrimaryCta,
                    )
                  else
                    NoCoasterCard(
                      pendingHireRequests: _pendingHireRequests,
                      ctaLabel: _ctaLabel,
                      onCta: _openPrimaryCta,
                    ),
                  const SizedBox(height: 20),
                  DashboardStatsRow(
                    pendingRequests: _pendingHireRequests,
                    coasterStatus: _coaster?.status,
                    capacity: _coaster?.capacity,
                  ),
                  const SizedBox(height: 28),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          AppStrings.quickActions,
                          style: theme.textTheme.titleMedium?.copyWith(
                            color: AppColors.heading,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                      TextButton(
                        onPressed: _openOrders,
                        style: TextButton.styleFrom(
                          foregroundColor: AppColors.seed,
                          padding: const EdgeInsets.symmetric(horizontal: 8),
                          minimumSize: Size.zero,
                          tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        ),
                        child: const Text(AppStrings.seeAll),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  _QuickActionTile(
                    icon: Icons.inbox_outlined,
                    title: AppStrings.hireRequests,
                    subtitle: AppStrings.hireRequestsHint,
                    badge: _pendingHireRequests > 0 ? _pendingHireRequests : null,
                    onTap: _openRequests,
                  ),
                  const SizedBox(height: 10),
                  _QuickActionTile(
                    icon: Icons.route_outlined,
                    title: AppStrings.orders,
                    subtitle: AppStrings.ordersHint,
                    onTap: _openOrders,
                  ),
                  const SizedBox(height: 10),
                  _QuickActionTile(
                    icon: Icons.calendar_month_outlined,
                    title: AppStrings.schedule,
                    subtitle: AppStrings.scheduleHint,
                    onTap: () {
                      AppScope.pushScoped(
                        context,
                        const SchedulePage(),
                      );
                    },
                  ),
                  const SizedBox(height: 10),
                  _QuickActionTile(
                    icon: Icons.history,
                    title: AppStrings.history,
                    subtitle: AppStrings.historyHint,
                    onTap: () {
                      AppScope.pushScoped(
                        context,
                        const HistoryPage(),
                      );
                    },
                  ),
                  const SizedBox(height: 10),
                  _QuickActionTile(
                    icon: Icons.person_outline,
                    title: AppStrings.profile,
                    subtitle: AppStrings.profileHint,
                    onTap: () => MainShell.requestTab(MainShell.profileTab),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _QuickActionTile extends StatelessWidget {
  const _QuickActionTile({
    required this.icon,
    required this.title,
    required this.subtitle,
    this.onTap,
    this.badge,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback? onTap;
  final int? badge;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Ink(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
          decoration: BoxDecoration(
            color: AppColors.cardSurface,
            borderRadius: BorderRadius.circular(20),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.06),
                blurRadius: 14,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: AppColors.seed.withValues(alpha: 0.12),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: AppColors.seed),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: theme.textTheme.titleSmall?.copyWith(
                        color: AppColors.heading,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: AppColors.mutedText,
                      ),
                    ),
                  ],
                ),
              ),
              if (badge != null)
                Padding(
                  padding: const EdgeInsets.only(right: 6),
                  child: Badge(label: Text('$badge')),
                ),
              Icon(
                Icons.chevron_right_rounded,
                color: AppColors.mutedText.withValues(alpha: 0.8),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
