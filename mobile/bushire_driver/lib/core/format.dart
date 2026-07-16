/// Display helpers (TZS amounts are server-side).
abstract final class AppFormat {
  static String tzs(num? amount) {
    if (amount == null) return 'TZS —';
    final rounded = amount.round();
    final raw = rounded.toString();
    final buf = StringBuffer();
    for (var i = 0; i < raw.length; i++) {
      final fromEnd = raw.length - i;
      buf.write(raw[i]);
      if (fromEnd > 1 && fromEnd % 3 == 1) buf.write(',');
    }
    return 'TZS $buf';
  }

  static String km(num? value) {
    if (value == null) return '—';
    if (value == value.roundToDouble()) {
      return '${value.toInt()} km';
    }
    return '${value.toStringAsFixed(1)} km';
  }

  static String dateTime(String? date, String? time) {
    if (date == null || date.isEmpty) return '—';
    if (time == null || time.isEmpty) return date;
    return '$date · $time';
  }
}
