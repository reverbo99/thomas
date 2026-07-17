import 'package:flutter/material.dart';

import '../core/strings.dart';
import '../core/theme/app_colors.dart';

/// TripWay-style top greeting row: "Hello, Name" + subtitle, bell + avatar.
///
/// Purely presentational — no AppBar chrome.
class HeroHeaderCard extends StatelessWidget {
  const HeroHeaderCard({
    super.key,
    required this.greeting,
    this.subtitle,
    this.icon = Icons.person,
    this.notificationCount = 0,
    this.onNotificationTap,
    this.onAvatarTap,
  });

  final String greeting;
  final String? subtitle;
  final IconData icon;
  final int notificationCount;
  final VoidCallback? onNotificationTap;
  final VoidCallback? onAvatarTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Row(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                greeting,
                style: theme.textTheme.headlineSmall?.copyWith(
                  color: AppColors.heading,
                  fontWeight: FontWeight.w800,
                  letterSpacing: -0.3,
                ),
              ),
              if (subtitle != null && subtitle!.isNotEmpty) ...[
                const SizedBox(height: 4),
                Text(
                  subtitle!,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    color: AppColors.mutedText,
                  ),
                ),
              ],
            ],
          ),
        ),
        const SizedBox(width: 12),
        _CircleIconButton(
          tooltip: AppStrings.notifications,
          onTap: onNotificationTap,
          child: Badge(
            isLabelVisible: notificationCount > 0,
            label: Text(
              notificationCount > 99 ? '99+' : '$notificationCount',
            ),
            child: Icon(
              Icons.notifications_outlined,
              color: AppColors.heading.withValues(alpha: 0.85),
              size: 22,
            ),
          ),
        ),
        const SizedBox(width: 10),
        _CircleIconButton(
          tooltip: AppStrings.profile,
          onTap: onAvatarTap,
          child: Icon(icon, color: AppColors.seed, size: 22),
        ),
      ],
    );
  }
}

class _CircleIconButton extends StatelessWidget {
  const _CircleIconButton({
    required this.child,
    this.onTap,
    this.tooltip,
  });

  final Widget child;
  final VoidCallback? onTap;
  final String? tooltip;

  @override
  Widget build(BuildContext context) {
    final button = Material(
      color: AppColors.cardSurface,
      shape: const CircleBorder(),
      elevation: 0,
      shadowColor: Colors.black.withValues(alpha: 0.08),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: Ink(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: AppColors.cardSurface,
            border: Border.all(
              color: AppColors.seed.withValues(alpha: 0.08),
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.06),
                blurRadius: 10,
                offset: const Offset(0, 3),
              ),
            ],
          ),
          child: Center(child: child),
        ),
      ),
    );

    if (tooltip == null) return button;
    return Tooltip(message: tooltip!, child: button);
  }
}
