import 'package:flutter/material.dart';

import '../core/strings.dart';
import '../core/theme/app_colors.dart';
import 'status_chip.dart';

/// Assigned coaster summary — single unified card with the coaster photo
/// bleeding into the right edge, icon-prefixed detail rows, and a
/// full-width primary action (matches the driver dashboard reference).
class CoasterSummaryCard extends StatelessWidget {
  const CoasterSummaryCard({
    super.key,
    required this.name,
    this.imageUrl,
    this.plateNumber,
    this.status,
    this.model,
    this.capacity,
    this.pendingHireRequests = 0,
    this.ctaLabel,
    this.onCta,
  });

  final String name;
  final String? imageUrl;
  final String? plateNumber;
  final String? status;
  final String? model;
  final int? capacity;
  final int pendingHireRequests;
  final String? ctaLabel;
  final VoidCallback? onCta;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return _ElevatedPanel(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(18, 18, 8, 16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const _AssignedPill(),
                        const SizedBox(height: 12),
                        Text(
                          name,
                          style: theme.textTheme.headlineSmall?.copyWith(
                            color: AppColors.heading,
                            fontWeight: FontWeight.w800,
                            letterSpacing: -0.3,
                          ),
                        ),
                        const SizedBox(height: 12),
                        if (plateNumber != null && plateNumber!.isNotEmpty)
                          _InfoRow(
                            icon: Icons.credit_card_outlined,
                            text: '${AppStrings.plate}: $plateNumber',
                          ),
                        if (model != null && model!.isNotEmpty)
                          _InfoRow(
                            icon: Icons.directions_bus_outlined,
                            text: '${AppStrings.model}: $model',
                          ),
                        if (capacity != null)
                          _InfoRow(
                            icon: Icons.event_seat_outlined,
                            text:
                                '${AppStrings.capacity}: $capacity ${AppStrings.seats}',
                          ),
                        if (status != null && status!.isNotEmpty) ...[
                          const SizedBox(height: 10),
                          StatusChip.coaster(status),
                        ],
                      ],
                    ),
                  ),
                ),
                _CoasterPhoto(imageUrl: imageUrl),
              ],
            ),
          ),
          if (pendingHireRequests > 0)
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 0, 18, 14),
              child: _PendingBanner(
                count: pendingHireRequests,
                onTap: onCta,
              ),
            ),
          if (ctaLabel != null && onCta != null)
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 0, 18, 18),
              child: FilledButton(
                onPressed: onCta,
                style: FilledButton.styleFrom(
                  minimumSize: const Size.fromHeight(52),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      ctaLabel!,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(width: 6),
                    const Icon(Icons.chevron_right_rounded, size: 22),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}

/// The coaster photo that bleeds into the card's right edge. Falls back to a
/// tinted bus glyph when there is no image (or it fails to load).
class _CoasterPhoto extends StatelessWidget {
  const _CoasterPhoto({this.imageUrl});

  final String? imageUrl;

  bool get _hasImage => imageUrl != null && imageUrl!.trim().isNotEmpty;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 132,
      child: _hasImage
          ? Stack(
              fit: StackFit.expand,
              children: [
                Image.network(
                  imageUrl!,
                  fit: BoxFit.cover,
                  alignment: Alignment.centerRight,
                  errorBuilder: (context, error, stackTrace) =>
                      const _PhotoFallback(),
                  loadingBuilder: (context, child, progress) {
                    if (progress == null) return child;
                    return const _PhotoFallback();
                  },
                ),
                // Soft white fade on the left so the photo blends into the card.
                DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.centerLeft,
                      end: Alignment.centerRight,
                      colors: [
                        AppColors.cardSurface,
                        AppColors.cardSurface.withValues(alpha: 0.0),
                      ],
                      stops: const [0.0, 0.32],
                    ),
                  ),
                ),
              ],
            )
          : const _PhotoFallback(),
    );
  }
}

class _PhotoFallback extends StatelessWidget {
  const _PhotoFallback();

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            AppColors.seed.withValues(alpha: 0.10),
            AppColors.seed.withValues(alpha: 0.20),
          ],
        ),
      ),
      child: Center(
        child: Icon(
          Icons.directions_bus_filled_rounded,
          color: AppColors.seed.withValues(alpha: 0.55),
          size: 44,
        ),
      ),
    );
  }
}

class _AssignedPill extends StatelessWidget {
  const _AssignedPill();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: AppColors.seed.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(
            Icons.directions_bus_filled,
            size: 15,
            color: AppColors.seed,
          ),
          const SizedBox(width: 6),
          Text(
            AppStrings.assignedCoaster,
            style: const TextStyle(
              color: AppColors.seed,
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Icon(icon, size: 16, color: AppColors.mutedText),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: theme.textTheme.bodyMedium?.copyWith(
                color: AppColors.mutedText,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _PendingBanner extends StatelessWidget {
  const _PendingBanner({required this.count, this.onTap});

  final int count;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Material(
      color: AppColors.warning.withValues(alpha: 0.14),
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          child: Row(
            children: [
              const Icon(
                Icons.inbox_outlined,
                size: 20,
                color: Color(0xFF6D4C00),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  '${AppStrings.pendingRequests}: $count',
                  style: theme.textTheme.titleSmall?.copyWith(
                    color: const Color(0xFF6D4C00),
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Empty state when the driver has no assigned coaster.
class NoCoasterCard extends StatelessWidget {
  const NoCoasterCard({
    super.key,
    this.pendingHireRequests = 0,
    this.ctaLabel,
    this.onCta,
  });

  final int pendingHireRequests;
  final String? ctaLabel;
  final VoidCallback? onCta;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return _ElevatedPanel(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                Container(
                  width: 52,
                  height: 52,
                  decoration: BoxDecoration(
                    color: AppColors.seed.withValues(alpha: 0.08),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    Icons.directions_bus_outlined,
                    color: AppColors.mutedText,
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        AppStrings.noCoasterAssigned,
                        style: theme.textTheme.titleSmall?.copyWith(
                          color: AppColors.heading,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        AppStrings.noCoasterHint,
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: AppColors.mutedText,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            if (pendingHireRequests > 0) ...[
              const SizedBox(height: 14),
              _PendingBanner(count: pendingHireRequests, onTap: onCta),
            ],
            if (ctaLabel != null && onCta != null) ...[
              const SizedBox(height: 16),
              FilledButton(
                onPressed: onCta,
                child: Text(ctaLabel!),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _ElevatedPanel extends StatelessWidget {
  const _ElevatedPanel({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        color: AppColors.cardSurface,
        borderRadius: BorderRadius.circular(22),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
          BoxShadow(
            color: AppColors.seed.withValues(alpha: 0.06),
            blurRadius: 12,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: child,
    );
  }
}
