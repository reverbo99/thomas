import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';

import '../data/geo/nominatim_service.dart';
import '../data/geo/place_result.dart';

/// Text field with Nominatim autocomplete + optional "use my location".
class PlaceAutocompleteField extends StatefulWidget {
  const PlaceAutocompleteField({
    super.key,
    required this.controller,
    required this.label,
    required this.onPlaceSelected,
    this.icon = Icons.place_outlined,
    this.enabled = true,
    this.validator,
    this.showMyLocation = true,
    this.focusNode,
  });

  final TextEditingController controller;
  final String label;
  final IconData icon;
  final ValueChanged<PlaceResult?> onPlaceSelected;
  final bool enabled;
  final String? Function(String?)? validator;
  final bool showMyLocation;
  final FocusNode? focusNode;

  @override
  State<PlaceAutocompleteField> createState() => _PlaceAutocompleteFieldState();
}

class _PlaceAutocompleteFieldState extends State<PlaceAutocompleteField> {
  late final FocusNode _focusNode;
  late final DebouncedNominatimSearch _search;
  final NominatimService _nominatim = NominatimService();

  List<PlaceResult> _suggestions = const [];
  bool _searching = false;
  bool _locating = false;
  bool _suppressSearch = false;
  String? _locationError;

  @override
  void initState() {
    super.initState();
    _focusNode = widget.focusNode ?? FocusNode();
    _search = DebouncedNominatimSearch();
    widget.controller.addListener(_onTextChanged);
    _focusNode.addListener(_onFocusChanged);
  }

  @override
  void dispose() {
    widget.controller.removeListener(_onTextChanged);
    _focusNode.removeListener(_onFocusChanged);
    if (widget.focusNode == null) _focusNode.dispose();
    _search.dispose();
    super.dispose();
  }

  void _onFocusChanged() {
    if (!_focusNode.hasFocus) {
      // Delay hide so suggestion taps register.
      Future<void>.delayed(const Duration(milliseconds: 180), () {
        if (mounted && !_focusNode.hasFocus) {
          setState(() => _suggestions = const []);
        }
      });
    }
  }

  Future<void> _onTextChanged() async {
    if (_suppressSearch) return;
    final q = widget.controller.text.trim();
    if (q.length < 3) {
      if (_suggestions.isNotEmpty || _searching) {
        setState(() {
          _suggestions = const [];
          _searching = false;
        });
      }
      widget.onPlaceSelected(null);
      return;
    }

    setState(() => _searching = true);
    final results = await _search.search(q);
    if (!mounted) return;
    setState(() {
      _searching = false;
      _suggestions = results;
    });
  }

  void _select(PlaceResult place) {
    _suppressSearch = true;
    widget.controller.text = place.displayName;
    widget.controller.selection = TextSelection.collapsed(
      offset: place.displayName.length,
    );
    widget.onPlaceSelected(place);
    setState(() {
      _suggestions = const [];
      _locationError = null;
    });
    _focusNode.unfocus();
    Future<void>.delayed(const Duration(milliseconds: 50), () {
      _suppressSearch = false;
    });
  }

  Future<void> _useMyLocation() async {
    setState(() {
      _locating = true;
      _locationError = null;
    });
    try {
      final enabled = await Geolocator.isLocationServiceEnabled();
      if (!enabled) {
        setState(() => _locationError = 'Turn on location services');
        return;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        setState(() => _locationError = 'Location permission denied');
        return;
      }

      final pos = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
        ),
      );

      final place = await _nominatim.reverse(pos.latitude, pos.longitude);
      if (!mounted) return;
      if (place == null) {
        // Still pin the GPS point even if reverse geocode fails.
        _select(
          PlaceResult(
            displayName:
                'My location (${pos.latitude.toStringAsFixed(5)}, '
                '${pos.longitude.toStringAsFixed(5)})',
            latitude: pos.latitude,
            longitude: pos.longitude,
          ),
        );
      } else {
        _select(place);
      }
    } catch (_) {
      if (mounted) {
        setState(() => _locationError = 'Could not get current location');
      }
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final showList = _focusNode.hasFocus &&
        (_suggestions.isNotEmpty || _searching);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        TextFormField(
          controller: widget.controller,
          focusNode: _focusNode,
          enabled: widget.enabled && !_locating,
          textCapitalization: TextCapitalization.sentences,
          decoration: InputDecoration(
            labelText: widget.label,
            prefixIcon: Icon(widget.icon),
            suffixIcon: widget.showMyLocation
                ? IconButton(
                    tooltip: 'Use my location',
                    onPressed: widget.enabled && !_locating
                        ? _useMyLocation
                        : null,
                    icon: _locating
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.my_location),
                  )
                : (_searching
                    ? const Padding(
                        padding: EdgeInsets.all(12),
                        child: SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      )
                    : null),
            border: InputBorder.none,
            enabledBorder: InputBorder.none,
            focusedBorder: InputBorder.none,
            errorBorder: InputBorder.none,
            filled: false,
            contentPadding: const EdgeInsets.symmetric(vertical: 8),
          ),
          validator: widget.validator,
        ),
        if (_locationError != null)
          Padding(
            padding: const EdgeInsets.only(left: 12, bottom: 4),
            child: Text(
              _locationError!,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.error,
              ),
            ),
          ),
        if (showList)
          Padding(
            padding: const EdgeInsets.only(bottom: 6, top: 2),
            child: Material(
              color: theme.colorScheme.surface,
              elevation: 2,
              shadowColor: Colors.black.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(14),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxHeight: 220),
                child: _searching && _suggestions.isEmpty
                    ? const Padding(
                        padding: EdgeInsets.all(16),
                        child: Center(
                          child: SizedBox(
                            width: 22,
                            height: 22,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          ),
                        ),
                      )
                    : ListView.separated(
                        shrinkWrap: true,
                        padding: const EdgeInsets.symmetric(vertical: 4),
                        itemCount: _suggestions.length,
                        separatorBuilder: (_, _) => Divider(
                          height: 1,
                          color: theme.colorScheme.outlineVariant
                              .withValues(alpha: 0.5),
                        ),
                        itemBuilder: (context, index) {
                          final place = _suggestions[index];
                          return ListTile(
                            dense: true,
                            leading: Container(
                              width: 32,
                              height: 32,
                              decoration: BoxDecoration(
                                color: theme.colorScheme.primaryContainer
                                    .withValues(alpha: 0.55),
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Icon(
                                Icons.place_outlined,
                                size: 18,
                                color: theme.colorScheme.primary,
                              ),
                            ),
                            title: Text(
                              place.displayName,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: theme.textTheme.bodyMedium?.copyWith(
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                            onTap: () => _select(place),
                          );
                        },
                      ),
              ),
            ),
          ),
      ],
    );
  }
}
