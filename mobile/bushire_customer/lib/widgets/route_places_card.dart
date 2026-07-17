import 'package:flutter/material.dart';

import '../data/geo/place_result.dart';
import 'place_autocomplete_field.dart';

/// Card with pickup + drop-off autocomplete fields and a circular swap control.
///
/// Matches [RouteSearchCard] visual language; uses Nominatim typeahead.
class RoutePlacesCard extends StatelessWidget {
  const RoutePlacesCard({
    super.key,
    required this.pickupController,
    required this.dropoffController,
    required this.onPickupSelected,
    required this.onDropoffSelected,
    this.pickupLabel = 'Pickup',
    this.dropoffLabel = 'Drop-off',
    this.enabled = true,
    this.pickupValidator,
    this.dropoffValidator,
    this.onSwap,
  });

  final TextEditingController pickupController;
  final TextEditingController dropoffController;
  final ValueChanged<PlaceResult?> onPickupSelected;
  final ValueChanged<PlaceResult?> onDropoffSelected;
  final String pickupLabel;
  final String dropoffLabel;
  final bool enabled;
  final String? Function(String?)? pickupValidator;
  final String? Function(String?)? dropoffValidator;
  final VoidCallback? onSwap;

  void _handleSwap() {
    final fromText = pickupController.text;
    final toText = dropoffController.text;
    pickupController.text = toText;
    dropoffController.text = fromText;
    onSwap?.call();
  }

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
        child: Stack(
          alignment: Alignment.centerRight,
          children: [
            Padding(
              padding: const EdgeInsets.only(right: 40),
              child: Column(
                children: [
                  PlaceAutocompleteField(
                    controller: pickupController,
                    label: pickupLabel,
                    icon: Icons.trip_origin,
                    enabled: enabled,
                    validator: pickupValidator,
                    onPlaceSelected: onPickupSelected,
                    showMyLocation: true,
                  ),
                  Divider(
                    height: 1,
                    color: colorScheme.outlineVariant.withValues(alpha: 0.6),
                  ),
                  PlaceAutocompleteField(
                    controller: dropoffController,
                    label: dropoffLabel,
                    icon: Icons.place_outlined,
                    enabled: enabled,
                    validator: dropoffValidator,
                    onPlaceSelected: onDropoffSelected,
                    showMyLocation: true,
                  ),
                ],
              ),
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
