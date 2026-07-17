import 'package:flutter/material.dart';

/// Hero header card for the dashboard — greeting + subtitle + circular
/// icon/avatar, filled with the app's primary color. Presentational only.
class HeroHeaderCard extends StatelessWidget {
  const HeroHeaderCard({
    super.key,
    required this.greeting,
    this.subtitle,
    this.icon = Icons.directions_bus_rounded,
    this.trailing,
  });

  final String greeting;
  final String? subtitle;
  final IconData icon;

  /// Optional extra content shown under the greeting/subtitle (e.g. actions).
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 22, 20, 22),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [colorScheme.primary, colorScheme.primary.withValues(alpha: 0.82)],
        ),
        borderRadius: BorderRadius.circular(22),
        boxShadow: [
          BoxShadow(
            color: colorScheme.primary.withValues(alpha: 0.25),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      greeting,
                      style: theme.textTheme.headlineSmall?.copyWith(
                        color: colorScheme.onPrimary,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    if (subtitle != null && subtitle!.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        subtitle!,
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: colorScheme.onPrimary.withValues(alpha: 0.9),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(width: 12),
              Container(
                width: 52,
                height: 52,
                decoration: BoxDecoration(
                  color: colorScheme.onPrimary.withValues(alpha: 0.18),
                  shape: BoxShape.circle,
                ),
                child: Icon(icon, color: colorScheme.onPrimary, size: 28),
              ),
            ],
          ),
          if (trailing != null) ...[
            const SizedBox(height: 16),
            trailing!,
          ],
        ],
      ),
    );
  }
}
