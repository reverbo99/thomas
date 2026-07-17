/// User-facing copy in one place (Swahili can follow later).
abstract final class AppStrings {
  static const String brandName = 'Bushire Customer';
  static const String brandTagline = 'Book and manage special hire trips';

  static const String loginTitle = 'Welcome back';
  static const String loginSubtitle = 'Use your customer account to continue';
  static const String emailLabel = 'Email';
  static const String passwordLabel = 'Password';
  static const String loginCta = 'Sign in';
  static const String registerHint = 'Need an account? Register';
  static const String registerComingSoon = 'Registration coming soon';
  static const String registerTitle = 'Create account';
  static const String registerSubtitle = 'Register to book special hire coasters';
  static const String registerCta = 'Register';
  static const String nameLabel = 'Full name';
  static const String phoneLabel = 'Phone';
  static const String confirmPasswordLabel = 'Confirm password';
  static const String haveAccount = 'Already have an account? Sign in';
  static const String alreadyHaveAccount = haveAccount;

  static const String emailRequired = 'Enter your email';
  static const String emailInvalid = 'Enter a valid email';
  static const String passwordRequired = 'Enter your password';
  static const String passwordTooShort = 'Password must be at least 6 characters';
  static const String nameRequired = 'Enter your name';
  static const String phoneRequired = 'Enter your phone number';
  static const String passwordMismatch = 'Passwords do not match';
  static const String fieldRequired = 'This field is required';

  /// Shown when DNS/host lookup or other low-level network failures occur.
  static const String networkUnavailable =
      "Can't reach the server. Check your internet connection and try again.";

  /// DNS / Failed host lookup — includes configured [ApiConfig.apiBaseUrl] at runtime.
  static String apiHostUnreachable(String apiBaseUrl) =>
      "Can't resolve API host ($apiBaseUrl). "
      'Use a reachable host or rebuild with '
      '--dart-define=API_BASE_URL=<url>';

  static const String requestTimedOut =
      'Request timed out. Check your connection and try again.';

  static const String welcome = 'Welcome';
  static const String homeTab = 'Home';
  static const String bookTab = 'Book';
  static const String tripsTab = 'My trips';
  static const String profileTab = 'Profile';
  /// Book tab app bar — customer starts a coaster hire from this list.
  static const String browseTitle = 'Book a coaster';
  static const String browseHint =
      'Choose a coaster to start your special hire booking';
  static const String anyDate = 'Any date';
  static const String anyTime = 'Any time';
  static const String filterBothRequired =
      'Set both date and time to check availability for that slot.';
  static const String myTrips = 'My trips';
  static const String tripsTitle = 'My trips';
  static const String profile = 'Profile';
  static const String logout = 'Log out';
  static const String save = 'Save';
  static const String retry = 'Retry';
  static const String cancel = 'Cancel';
  static const String confirm = 'Confirm';
  static const String comingSoon = 'Coming soon';
  static const String bookNow = 'Book now';
  static const String bookCta = 'Book now';
  static const String unavailable = 'Unavailable';
  static const String available = 'Available';
  static const String busy = 'Busy';

  static const String mapPlaceholder =
      'Map SDK not bundled — showing list / coordinates.';
  static const String mapPlaceholderHint =
      'Markers will appear here when Google Maps is added.';
  static const String filterDate = 'Date';
  static const String filterTime = 'Time';
  static const String applyFilters = 'Apply';
  static const String clearFilters = 'Clear';
  static const String noCoasters = 'No coasters available';
  static const String noCoastersHint =
      'No vehicles are available right now. Please refresh and try again.';
  /// Date/time only marks busy vs available — it does not remove coasters.
  static const String emptyCoastersHint =
      'No vehicles are available for this date and time. Try again later.';
  static const String locationPending = 'Location not shared yet';
  static const String emptyTrips = 'No trips yet. Book a coaster to get started.';
  static const String noTrips = 'No trips yet';
  static const String noTripsHint = 'Book a coaster from the Book tab.';
  static const String allStatuses = 'All';

  static const String plate = 'Plate';
  static const String features = 'Features';
  static const String model = 'Model';
  static const String color = 'Color';
  static const String capacity = 'Capacity';
  static const String seats = 'seats';
  static const String pricing = 'Pricing';
  static const String pricePerKm = 'Price per km';
  static const String minKm = 'Minimum km';
  static const String weekendSurcharge = 'Weekend surcharge';
  static const String nightSurcharge = 'Night surcharge';
  static const String driver = 'Driver';
  static const String noDriver = 'No driver assigned yet';
  static const String unavailableBookHint =
      'This coaster is busy for the selected time.';

  static const String bookingFormTitle = 'Book trip';
  static const String bookingForm = bookingFormTitle;
  static const String pricePreviewTitle = 'Price preview';
  static const String pricePreview = pricePreviewTitle;
  static const String continueToConfirm = 'Continue';
  static const String confirmTitle = 'Confirm booking';
  static const String confirmBooking = confirmTitle;
  static const String confirmSubtitle =
      'Pay with ClickPesa first. Your hire is saved only after payment succeeds, then enter passenger names.';
  static const String confirmCta = 'Confirm booking';
  static const String payWithClickPesa = 'Pay with ClickPesa';
  static const String confirmAndPay = 'Confirm & pay';
  static const String waitingForPayment =
      'Waiting for payment… Approve the prompt on your phone.';
  static const String paymentPollingHint =
      'We check payment status automatically. You can also tap Check payment.';
  static const String checkPayment = 'Check payment';
  static const String paymentStillPending =
      'Payment not confirmed yet. Keep waiting or try Check payment again.';
  static const String paymentPollTimedOut =
      'Still waiting for payment confirmation. Tap Check payment or re-send the prompt.';
  static const String enterPassengerNames = 'Enter passenger names';
  static const String enterPassengerNamesHint =
      'Add a name for each seat, then finish your booking.';
  static const String finishBooking = 'Finish booking';
  static const String paymentReceived = 'Payment received';
  static const String bookingConfirmed = 'Booking confirmed';
  static const String bookingCreated = 'Booking created';
  static const String bookingSuccess = bookingConfirmed;
  static const String viewTrips = 'View my trips';
  static const String summary = 'Summary';
  static const String coasterDetail = 'Coaster details';
  static const String pickupLocation = 'Pickup';
  static const String pickupLabel = pickupLocation;
  static const String dropoffLocation = 'Drop-off';
  static const String dropoffLabel = dropoffLocation;
  static const String hireDate = 'Hire date';
  static const String hireDateLabel = hireDate;
  static const String hireTimeLabel = 'Hire time';
  static const String startDateLabel = 'Start date';
  static const String startTimeLabel = 'Start time';
  static const String returnDate = 'Return';
  static const String returnDateLabel = 'Return date';
  static const String returnTimeLabel = 'Return time';
  static const String departureSchedule = 'Departure';
  static const String departureScheduleHint = 'When the hire starts';
  static const String returnSchedule = 'Return';
  static const String returnScheduleHint = 'Optional — multi-day hire end';
  static const String returnDateOptional = returnDateLabel;
  static const String returnTimeOptional = returnTimeLabel;
  static const String clearReturn = 'Clear';
  static const String mapPreviewHint =
      'Search pickup and drop-off to preview the route on the map';
  static const String calculatingRoute = 'Routing…';
  static const String routeDistancePending = 'Select both points';
  static const String routeSection = 'Route';
  static const String scheduleSection = 'Schedule';
  static const String detailsSection = 'Trip details';
  static const String bookingStepHint = 'Step 1 of 3 — route & schedule';
  static const String selectPlaceHint = 'Pick a suggestion from the list';
  static const String routeSectionTitle = 'Pickup & drop-off';
  static const String routeSectionHint =
      'Type an address to search OpenStreetMap, or tap the crosshair for your live location.';
  static const String passengersCount = 'Passengers';
  static const String passengersCountLabel = passengersCount;
  static const String passengers = passengersCount;
  static const String purpose = 'Purpose';
  static const String purposeLabel = 'Purpose (optional)';
  static const String notes = 'Notes';
  static const String notesLabel = 'Notes (optional)';
  static const String distanceKm = 'Distance';
  static const String distanceLabel = 'Distance (km)';
  static const String total = 'Total';
  static const String actualKm = 'Actual distance';
  static const String billableKm = 'Billable distance';
  static const String kmAmount = 'Distance amount';
  static const String surcharges = 'Surcharges';
  static const String noSurcharges = 'No surcharges';
  static const String orderStatus = 'Order';
  static const String paymentStatus = 'Payment';

  static const String tripDetailTitle = 'Trip details';
  static const String tripDetail = tripDetailTitle;
  static const String orderCode = 'Order';
  static const String nextStep = 'Next step';
  static const String trackTrip = 'Track trip';
  static const String cancelTrip = 'Cancel booking';
  static const String cancelTripConfirm = 'Cancel this booking?';
  static const String managePassengers = 'Passengers';
  static const String passengersTitle = 'Passengers';
  static const String passengersSubtitle =
      'Enter exactly the number of passenger names required.';
  static const String passengerName = 'Passenger name';
  static const String passengerPhoneOptional = 'Phone (optional)';
  static const String savePassengers = 'Save passengers';
  static const String passengersSaved = 'Passengers saved';

  static const String payDeposit = 'Pay deposit';
  static const String payDepositTitle = 'Pay deposit';
  static const String payBalance = 'Pay balance';
  static const String payBalanceTitle = 'Pay balance';
  static const String payCta = 'Send payment prompt';
  static const String syncPayment = 'Sync payment';
  static const String mpesaPhone = 'M-Pesa / USSD phone';
  static const String amountDue = 'Amount due';
  static const String paymentMethodHint =
      'A ClickPesa USSD prompt will be sent to this number.';
  static const String paymentSentHint =
      'Payment request sent. Approve on your phone — we sync in the background.';
  static const String resendPaymentPrompt = 'Re-send payment prompt';

  static const String lastSeen = 'Last seen';
  static const String locationUnavailable = 'Location not available yet';
  static const String pollingActive = 'Refreshing location…';
  static const String pollingStopped = 'Polling stopped';

  static const String editProfile = 'Edit profile';
  static const String calculatePrice = 'Calculate price';

  /// Passenger cap hint shown under the passengers field on the booking form.
  static String passengersCapacityHint(int capacity) =>
      'Max $capacity passenger${capacity == 1 ? '' : 's'} for this coaster';
  static String passengersOverCapacity(int capacity) =>
      'Cannot exceed coaster capacity of $capacity';
  static const String passengersSeatsLaterHint =
      'After successful ClickPesa payment your hire is saved, then you enter a name for each seat.';

  /// Friendly labels for `BookingModel.hireNextStep`.
  static const String nextStepPayDeposit = 'Pay deposit';
  static const String nextStepWaitOwner = 'Waiting for owner to accept';
  static const String nextStepPayBalance = 'Pay balance';
  static const String nextStepEnterPassengers = 'Enter passenger / seat names';
  static const String nextStepDone = 'All set';

  static String nextStepLabel(String? step) {
    switch (step) {
      case 'pay_deposit':
        return nextStepPayDeposit;
      case 'wait_owner':
        return nextStepWaitOwner;
      case 'pay_balance':
        return nextStepPayBalance;
      case 'enter_passengers':
        return nextStepEnterPassengers;
      case 'done':
        return nextStepDone;
      default:
        return step == null || step.isEmpty
            ? ''
            : step.replaceAll('_', ' ');
    }
  }

  static const String seatsSavedTitle = 'Seats';
  static const String seatUnnamed = 'Unnamed';
  static String seatNumberLabel(int n) => 'Seat $n';
  static const String passengersCountMissing =
      'Passenger count missing — reopen from trip details after refresh.';
}
