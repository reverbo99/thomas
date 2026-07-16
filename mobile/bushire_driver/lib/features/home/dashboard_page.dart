import 'package:flutter/material.dart';

import '../../core/di/app_scope.dart';
import '../../core/navigation/main_shell.dart';
import '../../core/strings.dart';
import '../../data/api/api_exception.dart';
import '../../data/models/coaster_model.dart';
import '../../data/models/user_model.dart';
import '../../data/repositories/auth_repository.dart';
import '../../data/repositories/coaster_repository.dart';
import '../../features/history/history_page.dart';
import '../../features/schedule/schedule_page.dart';
import '../../widgets/coaster_summary_card.dart';
import '../../widgets/error_banner.dart';

/// Driver home: profile greeting, assigned coaster, quick-action shortcuts.
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
      await scope?.syncLocationTracking();
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

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    final displayName =
        _user?.name ?? widget.initialUserName ?? AppStrings.brandName;
    final email = _user?.email ?? widget.initialEmail;
    final firstName = displayName.trim().split(RegExp(r'\s+')).first;

    return Scaffold(
      appBar: AppBar(title: const Text(AppStrings.homeTitle)),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
          children: [
            Text(
              '${AppStrings.welcome}, $firstName',
              style: theme.textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            if (email != null && email.isNotEmpty) ...[
              const SizedBox(height: 4),
              Text(
                email,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: colorScheme.onSurfaceVariant,
                ),
              ),
            ],
            const SizedBox(height: 20),
            if (_loading)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 32),
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
              if (_coaster != null)
                CoasterSummaryCard(
                  name: _coaster!.name,
                  plateNumber: _coaster!.plateNumber,
                  status: _coaster!.status,
                  model: _coaster!.model,
                  capacity: _coaster!.capacity,
                )
              else
                const NoCoasterCard(),
              if (_pendingHireRequests > 0) ...[
                const SizedBox(height: 12),
                Text(
                  '${AppStrings.pendingRequests}: $_pendingHireRequests',
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: colorScheme.primary,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
              const SizedBox(height: 28),
              Text(
                AppStrings.quickActions,
                style: theme.textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 12),
              _QuickActionTile(
                icon: Icons.inbox_outlined,
                title: AppStrings.hireRequests,
                subtitle: AppStrings.hireRequestsHint,
                badge: _pendingHireRequests > 0 ? _pendingHireRequests : null,
                onTap: () => MainShell.requestTab(MainShell.requestsTab),
              ),
              const SizedBox(height: 10),
              _QuickActionTile(
                icon: Icons.route_outlined,
                title: AppStrings.orders,
                subtitle: AppStrings.ordersHint,
                onTap: () => MainShell.requestTab(MainShell.ordersTab),
              ),
              const SizedBox(height: 10),
              _QuickActionTile(
                icon: Icons.calendar_month_outlined,
                title: AppStrings.schedule,
                subtitle: AppStrings.scheduleHint,
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const SchedulePage(),
                    ),
                  );
                },
              ),
              const SizedBox(height: 10),
              _QuickActionTile(
                icon: Icons.history,
                title: AppStrings.history,
                subtitle: AppStrings.historyHint,
                onTap: () {
                  Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => const HistoryPage(),
                    ),
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
    final colorScheme = Theme.of(context).colorScheme;

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          child: Row(
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: colorScheme.primaryContainer,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, color: colorScheme.onPrimaryContainer),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w600,
                          ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                            color: colorScheme.onSurfaceVariant,
                          ),
                    ),
                  ],
                ),
              ),
              if (badge != null)
                Padding(
                  padding: const EdgeInsets.only(right: 4),
                  child: Badge(label: Text('$badge')),
                ),
              Icon(Icons.chevron_right, color: colorScheme.onSurfaceVariant),
            ],
          ),
        ),
      ),
    );
  }
}
