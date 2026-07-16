import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

/// Tap-to-call helper for customer phone numbers.
Future<void> launchPhoneCall(String? phone) async {
  if (phone == null || phone.trim().isEmpty) return;
  final normalized = phone.replaceAll(RegExp(r'[^\d+]'), '');
  final uri = Uri(scheme: 'tel', path: normalized);
  if (await canLaunchUrl(uri)) {
    await launchUrl(uri);
  }
}

/// Tappable phone row for order detail.
class PhoneTile extends StatelessWidget {
  const PhoneTile({
    super.key,
    required this.phone,
    this.label,
  });

  final String phone;
  final String? label;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: Icon(Icons.phone_outlined, color: colorScheme.primary),
      title: Text(phone),
      subtitle: label != null ? Text(label!) : null,
      trailing: Icon(Icons.call, color: colorScheme.primary, size: 20),
      onTap: () => launchPhoneCall(phone),
    );
  }
}
