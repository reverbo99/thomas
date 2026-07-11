{{--
  Prefer extract(booking_payment_amounts($booking)) in the parent view.
  @include of this file cannot expose $breakdown* vars to the parent (isolated view scope).
--}}
@php
    extract(booking_payment_amounts($booking ?? $data ?? null));
@endphp
