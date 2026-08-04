{{-- Shared excess luggage show/process UI. Expects: $booking, $ctx, $status, $amountDue, $luggageService --}}
@php
    $status = $status ?? ($luggageService->normalizeStatus($booking) ?? 'none');
    $amountDue = $amountDue ?? $luggageService->amountDue($booking);
    $currency = $currency ?? session('currency', 'TZS');
@endphp

@if (session('success'))
    <div class="mb-4 rounded-lg border-l-4 border-green-500 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-disc pl-4">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">{{ __('vender/luggage.process_title') }}</h1>
        <p class="text-sm text-gray-500">{{ __('vender/luggage.process_subtitle') }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route($ctx['lookup_route']) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('vender/luggage.lookup_ticket') }}</a>
        <a href="{{ route($ctx['index_route']) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('vender/luggage.tracking') }}</a>
        @if((int)($booking->has_excess_luggage ?? 0) === 1 || (float)($booking->excess_luggage_fee ?? 0) > 0)
            <a href="{{ route($ctx['print_route'], $booking->id) }}" target="_blank" class="inline-flex items-center rounded-lg bg-teal-600 px-3 py-2 text-sm text-white hover:bg-teal-700">{{ __('vender/luggage.print_receipt') }}</a>
        @endif
    </div>
</div>

<div class="mb-6 grid gap-4 md:grid-cols-3">
    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('vender/luggage.ticket') }}</p>
        <p class="mt-1 font-semibold text-gray-900">{{ $booking->booking_code }}</p>
        <p class="text-sm text-gray-600">{{ $booking->customer_name }}</p>
        <p class="text-sm text-gray-500">{{ $booking->customer_phone }}</p>
    </div>
    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('vender/luggage.bus') }}</p>
        <p class="mt-1 font-semibold text-gray-900">{{ $booking->bus->bus_number ?? '—' }}</p>
        <p class="text-sm text-gray-600">{{ $booking->bus->campany->name ?? '' }}</p>
        <p class="text-sm text-gray-500">{{ $booking->travel_date }} · {{ $booking->seat }}</p>
    </div>
    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('vender/luggage.status') }}</p>
        <p class="mt-1">
            <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                {{ $luggageService->statusLabel($status) }}
            </span>
        </p>
        <p class="mt-2 text-sm text-gray-600">{{ __('vender/luggage.fee') }}: {{ $currency }} {{ convert_money($booking->excess_luggage_fee ?? 0) }}</p>
        @if($amountDue > 0)
            <p class="mt-1 text-sm font-semibold text-red-600">{{ __('vender/luggage.amount_due') }}: {{ $currency }} {{ convert_money($amountDue) }}</p>
        @endif
    </div>
</div>

{{-- Step indicators --}}
<ol class="mb-8 grid gap-2 text-xs md:grid-cols-5">
    @foreach ([
        'declared' => __('vender/luggage.step_report'),
        'weighed' => __('vender/luggage.step_measure'),
        'ready' => __('vender/luggage.step_pay'),
        'assigned' => __('vender/luggage.step_assign'),
        'retrieved' => __('vender/luggage.step_reclaim'),
    ] as $step => $label)
        @php
            $order = ['declared'=>1,'weighed'=>2,'awaiting_payment'=>2,'ready'=>3,'assigned'=>4,'retrieved'=>5];
            $cur = $order[$status] ?? 0;
            $thisStep = $order[$step] ?? 0;
            $done = $cur >= $thisStep;
        @endphp
        <li class="rounded-lg border px-3 py-2 {{ $done ? 'border-teal-500 bg-teal-50 text-teal-800' : 'border-gray-200 bg-white text-gray-500' }}">
            <span class="font-semibold">{{ $loop->iteration }}.</span> {{ $label }}
        </li>
    @endforeach
</ol>

<div class="grid gap-6 lg:grid-cols-2">
    {{-- Weigh-in --}}
    <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
        <h2 class="mb-3 text-lg font-semibold text-gray-800">{{ __('vender/luggage.weigh_in_section') }}</h2>
        <form method="POST" action="{{ route($ctx['weigh_route'], $booking->id) }}" class="space-y-3">
            @csrf
            <input type="hidden" name="luggage_action" value="set">
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.excess_luggage_fee') }}</label>
                <input type="number" step="0.01" min="0" name="excess_luggage_fee" value="{{ old('excess_luggage_fee', $booking->excess_luggage_fee ?: 2500) }}"
                       class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.excess_luggage_description') }}</label>
                <input type="text" name="excess_luggage_description" value="{{ old('excess_luggage_description', $booking->excess_luggage_description) }}"
                       placeholder="{{ __('vender/luggage.excess_luggage_description_placeholder') }}"
                       class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500">
            </div>
            <p class="text-xs text-gray-500">{{ __('vender/luggage.estimated_weight') }}:
                {{ $booking->estimated_weight !== null ? $booking->estimated_weight . ' kg' : __('vender/luggage.not_declared') }}
            </p>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.actual_weight') }}</label>
                    <input type="number" step="0.1" min="0" name="actual_weight" value="{{ old('actual_weight', $booking->actual_weight) }}"
                           class="mt-1 w-full rounded-lg border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.actual_length') }}</label>
                    <input type="number" step="0.1" min="0" name="actual_length" value="{{ old('actual_length', $booking->actual_length) }}"
                           class="mt-1 w-full rounded-lg border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.actual_height') }}</label>
                    <input type="number" step="0.1" min="0" name="actual_height" value="{{ old('actual_height', $booking->actual_height) }}"
                           class="mt-1 w-full rounded-lg border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.actual_width') }}</label>
                    <input type="number" step="0.1" min="0" name="actual_width" value="{{ old('actual_width', $booking->actual_width) }}"
                           class="mt-1 w-full rounded-lg border-gray-300 shadow-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.refund_payment_amount') }}</label>
                <input type="number" step="0.01" name="luggage_refund_amount" value="{{ old('luggage_refund_amount', $booking->luggage_refund_amount) }}"
                       class="mt-1 w-full rounded-lg border-gray-300 shadow-sm">
                <p class="mt-1 text-xs text-gray-500">{{ __('vender/luggage.refund_payment_hint_v2') }}</p>
            </div>
            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700">{{ __('vender/luggage.save_weigh_in') }}</button>
            </div>
        </form>
        <form method="POST" action="{{ route($ctx['weigh_route'], $booking->id) }}" class="mt-3" onsubmit="return confirm(@json(__('vender/luggage.confirm_remove')));">
            @csrf
            <input type="hidden" name="luggage_action" value="remove">
            <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('vender/luggage.remove') }}</button>
        </form>
    </div>

    <div class="space-y-6">
        {{-- Pay extra via gateway --}}
        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
            <h2 class="mb-2 text-lg font-semibold text-gray-800">{{ __('vender/luggage.step_pay') }}</h2>
            <p class="mb-3 text-sm text-gray-600">{{ __('vender/luggage.pay_hint') }}</p>
            @if($amountDue > 0)
                <form method="POST" action="{{ route($ctx['pay_route'], $booking->id) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.phone') }}</label>
                        <input type="text" name="phone" value="{{ old('phone', $booking->customer_phone) }}"
                               class="mt-1 w-full rounded-lg border-gray-300 shadow-sm" required>
                    </div>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        {{ __('vender/luggage.pay_clickpesa', ['amount' => $currency . ' ' . convert_money($amountDue)]) }}
                    </button>
                </form>
            @else
                <p class="text-sm text-gray-500">{{ __('vender/luggage.no_amount_due') }}</p>
            @endif
        </div>

        {{-- Assign --}}
        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
            <h2 class="mb-2 text-lg font-semibold text-gray-800">{{ __('vender/luggage.step_assign') }}</h2>
            <p class="mb-3 text-sm text-gray-600">{{ __('vender/luggage.assign_hint', ['bus' => $booking->bus->bus_number ?? '—']) }}</p>
            <form method="POST" action="{{ route($ctx['assign_route'], $booking->id) }}">
                @csrf
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50"
                    @if($amountDue > 0 || !in_array($status, ['ready', 'weighed'], true)) disabled @endif>
                    {{ __('vender/luggage.assign_to_bus') }}
                </button>
            </form>
        </div>

        {{-- Reclaim --}}
        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
            <h2 class="mb-2 text-lg font-semibold text-gray-800">{{ __('vender/luggage.step_reclaim') }}</h2>
            <p class="mb-3 text-sm text-gray-600">{{ __('vender/luggage.reclaim_hint') }}</p>
            <form method="POST" action="{{ route($ctx['reclaim_route'], $booking->id) }}" onsubmit="return confirm(@json(__('vender/luggage.confirm_reclaim')));">
                @csrf
                <button type="submit" class="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900 disabled:opacity-50"
                    @if(!in_array($status, ['assigned', 'ready'], true)) disabled @endif>
                    {{ __('vender/luggage.mark_retrieved') }}
                </button>
            </form>
            @if($booking->luggage_retrieved_at)
                <p class="mt-2 text-xs text-green-700">{{ __('vender/luggage.retrieved_at') }}: {{ $booking->luggage_retrieved_at }}</p>
            @endif
        </div>
    </div>
</div>
