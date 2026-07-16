import 'package:flutter_test/flutter_test.dart';

import 'package:bushire_driver/main.dart';

void main() {
  testWidgets('Bushire Driver home shows title', (WidgetTester tester) async {
    await tester.pumpWidget(const BushireDriverApp());

    expect(find.text('Bushire Driver'), findsOneWidget);
    expect(find.text('Special hire driver app'), findsOneWidget);
  });
}
