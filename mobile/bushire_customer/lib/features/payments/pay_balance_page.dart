import 'package:flutter/material.dart';

import '../trips/pay_page.dart';

/// Balance payment — ClickPesa USSD via `{ phone }`.
class PayBalancePage extends StatelessWidget {
  const PayBalancePage({
    super.key,
    required this.bookingId,
    this.amount,
    this.suggestedPhone,
  });

  final int bookingId;
  final num? amount;
  final String? suggestedPhone;

  @override
  Widget build(BuildContext context) {
    return PayPage(
      bookingId: bookingId,
      mode: PayMode.balance,
      amount: amount,
      suggestedPhone: suggestedPhone,
    );
  }
}
