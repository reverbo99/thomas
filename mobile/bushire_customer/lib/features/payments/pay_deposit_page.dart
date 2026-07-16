import 'package:flutter/material.dart';

import '../trips/pay_page.dart';

/// Deposit payment — ClickPesa USSD via `{ phone }`.
class PayDepositPage extends StatelessWidget {
  const PayDepositPage({
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
      mode: PayMode.deposit,
      amount: amount,
      suggestedPhone: suggestedPhone,
    );
  }
}
