import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:bushire_customer/app.dart';
import 'package:bushire_customer/core/strings.dart';
import 'package:bushire_customer/core/theme/app_theme.dart';
import 'package:bushire_customer/data/repositories/auth_repository.dart';
import 'package:bushire_customer/features/auth/login_page.dart';
import 'package:bushire_customer/features/home/dashboard_page.dart';
import 'package:bushire_customer/widgets/primary_button.dart';

void main() {
  testWidgets('Login page shows Bushire branding and sign-in CTA',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      BushireCustomerApp(
        authRepository: AuthRepository(),
        home: LoginPage(
          onLogin: (email, password) async {
            return LoginResult(userName: 'Jane', email: email);
          },
          onRegisterTap: () {},
        ),
      ),
    );

    expect(find.text(AppStrings.brandName), findsOneWidget);
    expect(find.text(AppStrings.loginSubtitle), findsOneWidget);
    expect(find.byType(PrimaryButton), findsOneWidget);
    expect(find.text(AppStrings.registerHint), findsOneWidget);
  });

  testWidgets('Login success opens dashboard shell', (WidgetTester tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light(),
        home: _LoginToDashboardHarness(),
      ),
    );

    final fields = find.byType(TextFormField);
    expect(fields, findsNWidgets(2));

    await tester.enterText(fields.at(0), 'jane@example.com');
    await tester.enterText(fields.at(1), 'password123');
    await tester.tap(find.byType(PrimaryButton));
    await tester.pumpAndSettle();

    expect(find.textContaining('Welcome'), findsOneWidget);
    expect(find.text(AppStrings.browseTitle), findsOneWidget);
    expect(find.text(AppStrings.myTrips), findsOneWidget);
    expect(find.text(AppStrings.profile), findsOneWidget);
  });
}

/// Exercises login → dashboard UI without network / secure storage.
class _LoginToDashboardHarness extends StatefulWidget {
  @override
  State<_LoginToDashboardHarness> createState() =>
      _LoginToDashboardHarnessState();
}

class _LoginToDashboardHarnessState extends State<_LoginToDashboardHarness> {
  String? _userName;
  String? _email;

  @override
  Widget build(BuildContext context) {
    if (_userName == null) {
      return LoginPage(
        onLogin: (email, password) async {
          setState(() {
            _userName = 'Jane Customer';
            _email = email;
          });
          return LoginResult(userName: 'Jane Customer', email: email);
        },
        onRegisterTap: () {},
      );
    }
    return DashboardPage(
      userName: _userName!,
      userEmail: _email,
      onLogout: () async {
        setState(() {
          _userName = null;
          _email = null;
        });
      },
    );
  }
}
