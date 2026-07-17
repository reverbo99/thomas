import 'package:flutter/material.dart';

import '../core/strings.dart';
import '../core/theme/app_colors.dart';

/// Polished start + return date/time cards for the booking form.
///
/// Uses branded [DatePickerTheme] / [TimePickerTheme] — not bare Material
/// outlined buttons. Presentational; parent owns state.
class DateTimeRangeCards extends StatelessWidget {
  const DateTimeRangeCards({
    super.key,
    required this.hireDate,
    required this.hireTime,
    required this.onHireDateChanged,
    required this.onHireTimeChanged,
    this.returnDate,
    this.returnTime,
    this.onReturnDateChanged,
    this.onReturnTimeChanged,
    this.onClearReturn,
    this.enabled = true,
  });

  final DateTime? hireDate;
  final TimeOfDay? hireTime;
  final ValueChanged<DateTime> onHireDateChanged;
  final ValueChanged<TimeOfDay> onHireTimeChanged;

  final DateTime? returnDate;
  final TimeOfDay? returnTime;
  final ValueChanged<DateTime>? onReturnDateChanged;
  final ValueChanged<TimeOfDay>? onReturnTimeChanged;
  final VoidCallback? onClearReturn;

  final bool enabled;

  static const _weekdays = [
    'Mon',
    'Tue',
    'Wed',
    'Thu',
    'Fri',
    'Sat',
    'Sun',
  ];
  static const _months = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
  ];

  static Future<DateTime?> pickDate(
    BuildContext context, {
    required DateTime initial,
    required DateTime first,
    required DateTime last,
  }) {
    final theme = Theme.of(context);
    return showDatePicker(
      context: context,
      initialDate: initial.isBefore(first) ? first : initial,
      firstDate: first,
      lastDate: last,
      builder: (ctx, child) {
        return Theme(
          data: theme.copyWith(
            colorScheme: theme.colorScheme,
            datePickerTheme: DatePickerThemeData(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(22),
              ),
              headerBackgroundColor: theme.colorScheme.primary,
              headerForegroundColor: theme.colorScheme.onPrimary,
              todayForegroundColor: WidgetStatePropertyAll(
                theme.colorScheme.primary,
              ),
              todayBackgroundColor: WidgetStatePropertyAll(
                theme.colorScheme.primaryContainer.withValues(alpha: 0.35),
              ),
              dayBackgroundColor: WidgetStateProperty.resolveWith((states) {
                if (states.contains(WidgetState.selected)) {
                  return theme.colorScheme.primary;
                }
                return null;
              }),
              dayForegroundColor: WidgetStateProperty.resolveWith((states) {
                if (states.contains(WidgetState.selected)) {
                  return theme.colorScheme.onPrimary;
                }
                return null;
              }),
            ),
          ),
          child: child!,
        );
      },
    );
  }

  static Future<TimeOfDay?> pickTime(
    BuildContext context, {
    required TimeOfDay initial,
  }) {
    final theme = Theme.of(context);
    return showTimePicker(
      context: context,
      initialTime: initial,
      builder: (ctx, child) {
        return Theme(
          data: theme.copyWith(
            timePickerTheme: TimePickerThemeData(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(22),
              ),
              dialHandColor: theme.colorScheme.primary,
              hourMinuteColor: theme.colorScheme.primaryContainer,
              hourMinuteTextColor: theme.colorScheme.onPrimaryContainer,
              dayPeriodColor: theme.colorScheme.primaryContainer,
              entryModeIconColor: theme.colorScheme.primary,
            ),
          ),
          child: child!,
        );
      },
    );
  }

  Future<void> _pickHireDate(BuildContext context) async {
    final now = DateTime.now();
    final first = DateTime(now.year, now.month, now.day);
    final picked = await pickDate(
      context,
      initial: hireDate ?? first,
      first: first,
      last: now.add(const Duration(days: 365)),
    );
    if (picked != null) onHireDateChanged(picked);
  }

  Future<void> _pickHireTime(BuildContext context) async {
    final picked = await pickTime(
      context,
      initial: hireTime ?? TimeOfDay.now(),
    );
    if (picked != null) onHireTimeChanged(picked);
  }

  Future<void> _pickReturnDate(BuildContext context) async {
    final now = DateTime.now();
    final first = hireDate ?? DateTime(now.year, now.month, now.day);
    final picked = await pickDate(
      context,
      initial: returnDate ?? first,
      first: first,
      last: now.add(const Duration(days: 365)),
    );
    if (picked != null) onReturnDateChanged?.call(picked);
  }

  Future<void> _pickReturnTime(BuildContext context) async {
    final picked = await pickTime(
      context,
      initial: returnTime ?? hireTime ?? TimeOfDay.now(),
    );
    if (picked != null) onReturnTimeChanged?.call(picked);
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _ScheduleCard(
          title: AppStrings.departureSchedule,
          subtitle: AppStrings.departureScheduleHint,
          accent: Theme.of(context).colorScheme.primary,
          icon: Icons.departure_board_rounded,
          dateLabel: hireDate == null
              ? AppStrings.startDateLabel
              : _friendlyDate(hireDate!),
          timeLabel: hireTime == null
              ? AppStrings.startTimeLabel
              : _friendlyTime(context, hireTime!),
          onDateTap: enabled ? () => _pickHireDate(context) : null,
          onTimeTap: enabled ? () => _pickHireTime(context) : null,
        ),
        const SizedBox(height: 12),
        _ScheduleCard(
          title: AppStrings.returnSchedule,
          subtitle: AppStrings.returnScheduleHint,
          accent: AppColors.confirmed,
          icon: Icons.event_available_rounded,
          dateLabel: returnDate == null
              ? AppStrings.returnDateOptional
              : _friendlyDate(returnDate!),
          timeLabel: returnTime == null
              ? AppStrings.returnTimeOptional
              : _friendlyTime(context, returnTime!),
          onDateTap: enabled ? () => _pickReturnDate(context) : null,
          onTimeTap: enabled ? () => _pickReturnTime(context) : null,
          trailing: (returnDate != null || returnTime != null) &&
                  onClearReturn != null &&
                  enabled
              ? TextButton(
                  onPressed: onClearReturn,
                  child: const Text(AppStrings.clearReturn),
                )
              : null,
        ),
      ],
    );
  }

  static String _friendlyDate(DateTime d) {
    final weekday = _weekdays[d.weekday - 1];
    final month = _months[d.month - 1];
    return '$weekday, ${d.day} $month ${d.year}';
  }

  static String _friendlyTime(BuildContext context, TimeOfDay t) {
    return MaterialLocalizations.of(context).formatTimeOfDay(
      t,
      alwaysUse24HourFormat: true,
    );
  }
}

class _ScheduleCard extends StatelessWidget {
  const _ScheduleCard({
    required this.title,
    required this.subtitle,
    required this.accent,
    required this.icon,
    required this.dateLabel,
    required this.timeLabel,
    this.onDateTap,
    this.onTimeTap,
    this.trailing,
  });

  final String title;
  final String subtitle;
  final Color accent;
  final IconData icon;
  final String dateLabel;
  final String timeLabel;
  final VoidCallback? onDateTap;
  final VoidCallback? onTimeTap;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: accent.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(icon, color: accent, size: 22),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: theme.textTheme.titleSmall?.copyWith(
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        subtitle,
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: colorScheme.onSurfaceVariant,
                        ),
                      ),
                    ],
                  ),
                ),
                ?trailing,
              ],
            ),
            const SizedBox(height: 14),
            Row(
              children: [
                Expanded(
                  child: _PickerTile(
                    icon: Icons.calendar_today_rounded,
                    label: dateLabel,
                    onTap: onDateTap,
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _PickerTile(
                    icon: Icons.schedule_rounded,
                    label: timeLabel,
                    onTap: onTimeTap,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _PickerTile extends StatelessWidget {
  const _PickerTile({
    required this.icon,
    required this.label,
    this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    final enabled = onTap != null;

    return Material(
      color: colorScheme.surfaceContainerHighest.withValues(alpha: 0.45),
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
          child: Row(
            children: [
              Icon(
                icon,
                size: 18,
                color: enabled
                    ? colorScheme.primary
                    : colorScheme.onSurfaceVariant,
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.labelLarge?.copyWith(
                    fontWeight: FontWeight.w600,
                    color: enabled
                        ? colorScheme.onSurface
                        : colorScheme.onSurfaceVariant,
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
