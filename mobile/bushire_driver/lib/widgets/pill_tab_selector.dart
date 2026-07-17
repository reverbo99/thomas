import 'package:flutter/material.dart';

/// Horizontal pill-shaped segmented selector. Generic replacement for
/// [TabBar] / [FilterChip] rows — purely presentational, callers own state.
class PillTabSelector extends StatelessWidget {
  const PillTabSelector({
    super.key,
    required this.labels,
    required this.selectedIndex,
    required this.onChanged,
    this.scrollable = false,
  });

  final List<String> labels;
  final int selectedIndex;
  final ValueChanged<int> onChanged;

  /// When true, the pills scroll horizontally instead of filling evenly.
  final bool scrollable;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    final container = Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: colorScheme.surfaceContainerLowest,
        borderRadius: BorderRadius.circular(24),
      ),
      child: scrollable
          ? Row(
              mainAxisSize: MainAxisSize.min,
              children: _pills(theme, colorScheme),
            )
          : Row(
              children: _pills(
                theme,
                colorScheme,
              ).map((w) => Expanded(child: w)).toList(),
            ),
    );

    if (!scrollable) return container;
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: container,
    );
  }

  List<Widget> _pills(ThemeData theme, ColorScheme colorScheme) {
    return List.generate(labels.length, (i) {
      final selected = i == selectedIndex;
      return Padding(
        padding: EdgeInsets.only(right: i == labels.length - 1 ? 0 : 4),
        child: GestureDetector(
          onTap: () => onChanged(i),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 180),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
            decoration: BoxDecoration(
              color: selected ? colorScheme.primary : Colors.transparent,
              borderRadius: BorderRadius.circular(20),
            ),
            alignment: Alignment.center,
            child: Text(
              labels[i],
              textAlign: TextAlign.center,
              overflow: TextOverflow.ellipsis,
              style: theme.textTheme.labelLarge?.copyWith(
                color: selected
                    ? colorScheme.onPrimary
                    : colorScheme.onSurfaceVariant,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ),
      );
    });
  }
}
