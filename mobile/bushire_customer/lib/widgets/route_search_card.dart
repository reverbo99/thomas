import 'package:flutter/material.dart';

/// White rounded card with two stacked From/To (pickup/dropoff) fields and a
/// circular swap icon button overlapping the divider between them.
///
/// Purely presentational — pass the same [TextEditingController]s /
/// validators you'd otherwise put directly on [TextFormField]s.
class RouteSearchCard extends StatelessWidget {
  const RouteSearchCard({
    super.key,
    required this.fromController,
    required this.toController,
    this.fromLabel = 'From',
    this.toLabel = 'To',
    this.fromIcon = Icons.trip_origin,
    this.toIcon = Icons.place_outlined,
    this.onSwap,
    this.enabled = true,
    this.fromValidator,
    this.toValidator,
  });

  final TextEditingController fromController;
  final TextEditingController toController;
  final String fromLabel;
  final String toLabel;
  final IconData fromIcon;
  final IconData toIcon;
  final VoidCallback? onSwap;
  final bool enabled;
  final String? Function(String?)? fromValidator;
  final String? Function(String?)? toValidator;

  void _handleSwap() {
    final fromText = fromController.text;
    final toText = toController.text;
    fromController.text = toText;
    toController.text = fromText;
    onSwap?.call();
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
        child: Stack(
          alignment: Alignment.centerRight,
          children: [
            Column(
              children: [
                TextFormField(
                  controller: fromController,
                  enabled: enabled,
                  textCapitalization: TextCapitalization.sentences,
                  decoration: InputDecoration(
                    labelText: fromLabel,
                    prefixIcon: Icon(fromIcon),
                    border: InputBorder.none,
                    enabledBorder: InputBorder.none,
                    focusedBorder: InputBorder.none,
                    errorBorder: InputBorder.none,
                    filled: false,
                    contentPadding: const EdgeInsets.symmetric(vertical: 8),
                  ),
                  validator: fromValidator,
                ),
                const Divider(height: 1),
                TextFormField(
                  controller: toController,
                  enabled: enabled,
                  textCapitalization: TextCapitalization.sentences,
                  decoration: InputDecoration(
                    labelText: toLabel,
                    prefixIcon: Icon(toIcon),
                    border: InputBorder.none,
                    enabledBorder: InputBorder.none,
                    focusedBorder: InputBorder.none,
                    errorBorder: InputBorder.none,
                    filled: false,
                    contentPadding: const EdgeInsets.symmetric(vertical: 8),
                  ),
                  validator: toValidator,
                ),
              ],
            ),
            Positioned(
              right: 0,
              child: Material(
                color: colorScheme.primary,
                shape: const CircleBorder(),
                elevation: 2,
                child: InkWell(
                  customBorder: const CircleBorder(),
                  onTap: enabled ? _handleSwap : null,
                  child: Padding(
                    padding: const EdgeInsets.all(8),
                    child: Icon(
                      Icons.swap_vert_rounded,
                      color: colorScheme.onPrimary,
                      size: 20,
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
