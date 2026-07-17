import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:bushire_driver/app.dart';
import 'package:bushire_driver/core/di/app_scope.dart';
import 'package:bushire_driver/core/strings.dart';
import 'package:bushire_driver/core/theme/app_theme.dart';
import 'package:bushire_driver/data/api/api_client.dart';
import 'package:bushire_driver/data/local/token_store.dart';
import 'package:bushire_driver/data/models/coaster_model.dart';
import 'package:bushire_driver/data/models/driver_profile.dart';
import 'package:bushire_driver/data/models/user_model.dart';
import 'package:bushire_driver/data/repositories/auth_repository.dart';
import 'package:bushire_driver/data/repositories/coaster_repository.dart';
import 'package:bushire_driver/features/auth/login_page.dart';
import 'package:bushire_driver/features/home/dashboard_page.dart';
import 'package:bushire_driver/widgets/coaster_summary_card.dart';
import 'package:bushire_driver/widgets/primary_button.dart';

void main() {
  testWidgets('Login page shows Bushire Driver branding and sign-in CTA',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      BushireDriverApp(
        authRepository: AuthRepository(tokenStore: TokenStore.memory()),
        home: LoginPage(
          onLogin: (email, password) async {
            return LoginResult(userName: 'John', email: email);
          },
        ),
      ),
    );

    expect(find.text(AppStrings.brandName), findsOneWidget);
    expect(find.text(AppStrings.loginSubtitle), findsOneWidget);
    expect(find.byType(PrimaryButton), findsOneWidget);
    expect(find.text(AppStrings.noSelfRegister), findsOneWidget);
    // Drivers cannot self-register — no register CTA.
    expect(find.textContaining('Register'), findsNothing);
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

    await tester.enterText(fields.at(0), 'driver@example.com');
    await tester.enterText(fields.at(1), 'password123');
    await tester.tap(find.byType(PrimaryButton));
    await tester.pumpAndSettle();

    expect(find.textContaining('Hello'), findsOneWidget);
    expect(find.text(AppStrings.assignedCoaster), findsOneWidget);
    expect(find.text('Luxury Coaster A'), findsOneWidget);
    expect(find.text(AppStrings.hireRequests), findsOneWidget);
    expect(find.text(AppStrings.orders), findsOneWidget);
  });

  testWidgets('Coaster summary and empty cards render',
      (WidgetTester tester) async {
    await tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light(),
        home: const Scaffold(
          body: SingleChildScrollView(
            child: Column(
              children: [
                CoasterSummaryCard(
                  name: 'Luxury Coaster A',
                  plateNumber: 'T 123 ABC',
                  status: 'available',
                ),
                NoCoasterCard(),
              ],
            ),
          ),
        ),
      ),
    );

    expect(find.text('Luxury Coaster A'), findsOneWidget);
    expect(find.text('${AppStrings.plate}: T 123 ABC'), findsOneWidget);
    expect(find.text(AppStrings.noCoasterAssigned), findsOneWidget);
  });
}

/// Login → dashboard without network / secure storage.
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
            _userName = 'John Driver';
            _email = email;
          });
          return LoginResult(userName: 'John Driver', email: email);
        },
      );
    }
    final auth = _FakeAuthRepository();
    return AppScope(
      services: AppServices(authRepository: auth),
      child: DashboardPage(
        initialUserName: _userName,
        initialEmail: _email,
        authRepository: auth,
        coasterRepository: _FakeCoasterRepository(),
        onLogout: () async {
          setState(() {
            _userName = null;
            _email = null;
          });
        },
      ),
    );
  }
}

class _FakeAuthRepository extends AuthRepository {
  _FakeAuthRepository() : super(tokenStore: TokenStore.memory());

  @override
  Future<DriverProfile> getProfile() async {
    return const DriverProfile(
      user: UserModel(
        id: 10,
        name: 'John Driver',
        email: 'driver@example.com',
        role: 'driver',
      ),
      coaster: CoasterModel(
        id: 1,
        name: 'Luxury Coaster A',
        plateNumber: 'T 123 ABC',
        status: 'available',
        capacity: 30,
      ),
      pendingHireRequests: 0,
    );
  }
}

class _FakeCoasterRepository extends CoasterRepository {
  _FakeCoasterRepository()
      : super(apiClient: ApiClient(baseUrl: 'http://test.invalid'));

  @override
  Future<CoasterModel?> getCoasterOrNull() async => null;
}
