import 'package:flutter/material.dart';

import 'app.dart';
import 'data/repositories/auth_repository.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(BushireDriverApp(authRepository: AuthRepository()));
}
