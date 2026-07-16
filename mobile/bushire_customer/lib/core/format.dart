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

  static String lastSeen(String? iso) {
    if (iso == null || iso.isEmpty) return 'Unknown';
    try {
      final dt = DateTime.parse(iso).toLocal();
      final h = dt.hour.toString().padLeft(2, '0');
      final m = dt.minute.toString().padLeft(2, '0');
      return '${dt.year}-${dt.month.toString().padLeft(2, '0')}-${dt.day.toString().padLeft(2, '0')} $h:$m';
    } catch (_) {
      return iso;
    }
  }
}
