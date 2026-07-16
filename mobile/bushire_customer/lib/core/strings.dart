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

  static const String welcome = 'Welcome';
  static const String homeTab = 'Home';
  static const String tripsTab = 'My trips';
  static const String profileTab = 'Profile';
  static const String browseTitle = 'Browse coasters';
  static const String browseHint = 'Available special hire vehicles';
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
  static const String noCoasters = 'No coasters found';
  static const String noCoastersHint = 'Try another date or time.';
  static const String emptyCoasters = 'No coasters found for this time.';
  static const String emptyTrips = 'No trips yet. Book a coaster to get started.';
  static const String noTrips = 'No trips yet';
  static const String noTripsHint = 'Book a coaster from the Home tab.';
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
      'Review details, then send your booking request.';
  static const String confirmCta = 'Confirm booking';
  static const String bookingCreated = 'Booking created';
  static const String bookingSuccess = bookingCreated;
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
  static const String returnDate = 'Return';
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
      'Payment request sent. Approve on your phone, then sync.';

  static const String lastSeen = 'Last seen';
  static const String locationUnavailable = 'Location not available yet';
  static const String pollingActive = 'Refreshing location…';
  static const String pollingStopped = 'Polling stopped';

  static const String editProfile = 'Edit profile';
  static const String calculatePrice = 'Calculate price';
}
