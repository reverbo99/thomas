import 'package:flutter/material.dart';

import '../../core/strings.dart';
import '../../widgets/app_gradient_background.dart';
import '../../widgets/hero_header_card.dart';

/// Home tab: welcome + quick actions into Book / Trips / Profile.
class DashboardPage extends StatelessWidget {
  const DashboardPage({
    super.key,
    required this.userName,
    this.userEmail,
    required this.onLogout,
    this.onBrowseCoasters,
    this.onMyTrips,
    this.onProfile,
  });

  final String userName;
  final String? userEmail;
  final Future<void> Function() onLogout;
  final VoidCallback? onBrowseCoasters;
  final VoidCallback? onMyTrips;
  final VoidCallback? onProfile;

  Future<void> _handleLogout(BuildContext context) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text(AppStrings.logout),
        content: const Text('Sign out of Bushire Customer?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text(AppStrings.cancel),
          ),
          FilledButton(
            onPressed: () => Navigator.of(ctx).pop(true),
            child: const Text(AppStrings.logout),
          ),
        ],
      ),
    );
    if (confirmed == true) await onLogout();
  }

  @override
  Widget build(BuildContext context) {
    final firstName = userName.trim().split(RegExp(r'\s+')).first;

    return Scaffold(
      appBar: AppBar(
        title: const Text(AppStrings.homeTab),
        actions: [
          IconButton(
            tooltip: AppStrings.logout,
            onPressed: () => _handleLogout(context),
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: AppGradientBackground(
        child: SafeArea(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
            children: [
              HeroHeaderCard(
                greeting: '${AppStrings.welcome}, $firstName',
                subtitle: userEmail != null && userEmail!.isNotEmpty
                    ? userEmail
                    : null,
                icon: Icons.directions_bus_rounded,
              ),
              const SizedBox(height: 24),
              _QuickActionCard(
                icon: Icons.directions_bus_outlined,
                title: AppStrings.bookTab,
                subtitle: AppStrings.browseHint,
                onTap: onBrowseCoasters,
              ),
              const SizedBox(height: 10),
              _QuickActionCard(
                icon: Icons.confirmation_number_outlined,
                title: AppStrings.myTrips,
                subtitle: 'View bookings and track rides',
                onTap: onMyTrips,
              ),
              const SizedBox(height: 10),
              _QuickActionCard(
                icon: Icons.person_outline,
                title: AppStrings.profile,
                subtitle: 'Update your account details',
                onTap: onProfile,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _QuickActionCard extends StatelessWidget {
  const _QuickActionCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final VoidCallback? onTap;

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
                  shape: BoxShape.circle,
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
              Icon(Icons.chevron_right, color: colorScheme.onSurfaceVariant),
            ],
          ),
        ),
      ),
    );
  }
}
