@php
    $isRoundTrip = ($row['type'] ?? 'single') === 'round_trip_resaved';
    $book = $row['primary'];
    $legs = $isRoundTrip ? ($row['bookings'] ?? []) : [$book];
    $totalAmount = $isRoundTrip
        ? collect($legs)->sum(fn ($leg) => (float) ($leg->busFee ?: $leg->amount))
        : (float) ($book->busFee ?: $book->amount);
@endphp
<tr data-booking-ids='@json(collect($legs)->pluck("id")->values())'>
    <td class="tickets-table__col-no" data-label="#">{{ $rowNumber }}</td>
    <td data-label="{{ __('customer/myticket.booking_id') }}">
        @if ($isRoundTrip)
            <div class="tickets-table__code-group">
                @foreach ($legs as $leg)
                    <span class="tickets-table__code">{{ $leg->booking_code }}</span>
                @endforeach
                <span class="home-bus-row__class home-bus-row__class--round">{{ __('all.round_trip') }}</span>
            </div>
        @else
            <span class="tickets-table__code">{{ $book->booking_code }}</span>
        @endif
    </td>
    <td data-label="{{ __('customer/busroot.price') }}">
        <span class="tickets-table__price">{{ $currency }} {{ convert_money($totalAmount) }}</span>
    </td>
    <td data-label="{{ __('customer/myticket.bus_name') }}">
        @if ($isRoundTrip)
            {{ $legs[0]->campany->name ?? '—' }}
        @else
            {{ $book->campany->name ?? '—' }}
        @endif
    </td>
    <td data-label="{{ __('customer/myticket.departure_date') }}">
        @if ($isRoundTrip)
            <div class="tickets-table__dates-stack">
                @foreach ($legs as $leg)
                    <span>{{ $leg->travel_date ?? '—' }}</span>
                @endforeach
            </div>
        @else
            {{ $book->travel_date ?? '—' }}
        @endif
    </td>
    <td class="tickets-table__col-date" data-label="{{ __('customer/busroot.created_at') }}">
        {{ $book->created_at ? \Carbon\Carbon::parse($book->created_at)->format('d M Y') : '—' }}
    </td>
    <td data-label="{{ __('customer/myticket.status') }}">
        @include('customer.partials.ticket_status', ['book' => $book])
    </td>
    <td class="tickets-table__col-actions" data-label="{{ __('customer/myticket.action') }}">
        @include('customer.partials.ticket_actions', ['book' => $book])
    </td>
</tr>
