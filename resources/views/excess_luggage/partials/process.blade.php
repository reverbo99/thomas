{{-- Shared excess luggage show/process UI. Expects: $booking, $ctx, $status, $amountDue, $luggageService --}}
@php
    $status = $status ?? ($luggageService->normalizeStatus($booking) ?? 'none');
    $amountDue = $amountDue ?? $luggageService->amountDue($booking);
    $refundAmount = $refundAmount ?? $luggageService->refundAmount($booking);
    $refundDelta = (float) ($booking->luggage_refund_amount ?? 0);
    $refundDisplay = $refundDelta < 0 ? round(abs($refundDelta), 2) : 0.0;
    $payStatus = $booking->luggage_payment_status ?? null;
    $currency = $currency ?? session('currency', 'TZS');
    $feePerKg = $luggageService->feePerKg();
    $estimatedWeightJs = $booking->estimated_weight !== null ? (float) $booking->estimated_weight : null;
    $grossLuggageFee = (float) ($booking->excess_luggage_fee ?? 0);
    $measurementsSaved = !empty($booking->luggage_weighed_at);
    $defaultFee = $grossLuggageFee > 0
        ? $grossLuggageFee
        : ($estimatedWeightJs !== null ? round($estimatedWeightJs * $feePerKg, 2) : $feePerKg);
    $luggageSplit = split_luggage_fee_amount($grossLuggageFee > 0 ? $grossLuggageFee : (float) $defaultFee);
    $escrow = $booking->excessLuggageEscrow ?? $luggageService->escrowFor($booking);
    $escrowStatus = $escrow->status ?? null;
    $canAssign = $luggageService->canAssignLuggage($booking);
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
        <a href="{{ route($ctx['scan_route']) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('vender/luggage.scan_exit_title') }}</a>
        <a href="{{ route($ctx['index_route']) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('vender/luggage.tracking') }}</a>
        @if((int)($booking->has_excess_luggage ?? 0) === 1 || (float)($booking->excess_luggage_fee ?? 0) > 0)
            @if($luggageService->canPrintReceipt($booking))
                <a href="{{ route($ctx['print_route'], $booking->id) }}" target="_blank" class="inline-flex items-center rounded-lg bg-teal-600 px-3 py-2 text-sm text-white hover:bg-teal-700">{{ __('vender/luggage.print_receipt') }}</a>
            @else
                <span class="inline-flex flex-col items-end">
                    <button type="button" disabled class="inline-flex items-center rounded-lg bg-gray-300 px-3 py-2 text-sm text-gray-600 cursor-not-allowed" title="{{ __('vender/luggage.print_payment_required') }}">
                        {{ __('vender/luggage.print_receipt') }}
                    </button>
                    <span class="mt-1 text-xs text-red-600">{{ __('vender/luggage.print_payment_required') }}</span>
                </span>
            @endif
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
        @php
            $from = optional($booking->route)->from
                ?? optional(optional($booking->bus)->route)->from
                ?? optional($booking->schedule)->from
                ?? $booking->pickup_point
                ?? null;
            $to = optional($booking->route)->to
                ?? optional(optional($booking->bus)->route)->to
                ?? optional($booking->schedule)->to
                ?? $booking->dropping_point
                ?? null;
        @endphp
        <p class="text-sm font-medium text-gray-700">
            {{ __('vender/luggage.origin_destination') }}:
            <span class="font-semibold">{{ $from ?: '—' }} → {{ $to ?: '—' }}</span>
        </p>
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
        @if($escrow)
            <p class="mt-1 text-sm text-indigo-700 dark:text-indigo-300">
                {{ __('vender/luggage.escrow_held') }}: {{ $currency }} {{ convert_money($escrow->held_amount ?? 0) }}
                @if((float)($escrow->surplus_amount ?? 0) > 0)
                    · {{ __('vender/luggage.escrow_surplus') }}: {{ $currency }} {{ convert_money($escrow->surplus_amount) }}
                @endif
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ __('vender/luggage.escrow_status') }}: {{ $luggageService->escrowStatusLabel($escrowStatus) }}
            </p>
        @endif
        <p class="mt-1 text-sm font-semibold text-teal-700">{{ __('vender/luggage.fee_net_to_owner') }}: {{ $currency }} {{ convert_money($luggageSplit['owner']) }}</p>
        @if($amountDue > 0)
            <p class="mt-1 text-sm font-semibold text-red-600">{{ __('vender/luggage.amount_due') }}: {{ $currency }} {{ convert_money($amountDue) }}</p>
        @elseif($refundDisplay > 0)
            <p class="mt-1 text-sm font-semibold text-amber-700">{{ __('vender/luggage.refund_owed') }}: {{ $currency }} {{ convert_money($refundDisplay) }}</p>
            @if($payStatus)
                <p class="mt-1 text-xs text-gray-500">{{ $luggageService->paymentStatusLabel($payStatus) }}</p>
            @endif
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
                <input type="number" step="0.01" min="0" name="excess_luggage_fee" value="{{ old('excess_luggage_fee', $defaultFee) }}"
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
            <p class="text-xs text-gray-500">{{ __('vender/luggage.fee_per_kg_label') }}:
                {{ $currency }} {{ convert_money($feePerKg) }}
            </p>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.actual_weight') }}</label>
                    <input type="number" step="0.1" min="0" name="actual_weight" id="xlugActualWeight"
                           value="{{ old('actual_weight', $booking->actual_weight) }}"
                           class="mt-1 w-full rounded-lg border-gray-300 shadow-sm" data-xlug-recalc>
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
            <div class="rounded-lg border border-teal-100 bg-teal-50/60 p-3 space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-800">{{ __('vender/luggage.auto_reconciliation') }}</p>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.weight_verdict') }}</label>
                    <input type="text" id="xlugVerdictDisplay" readonly
                           value=""
                           class="mt-1 w-full rounded-lg border-gray-200 bg-white shadow-sm text-gray-800">
                    <input type="hidden" name="luggage_weight_verdict" id="xlugVerdictValue" value="{{ old('luggage_weight_verdict', $booking->luggage_weight_verdict) }}">
                    <p class="mt-1 text-xs text-gray-500">{{ __('vender/luggage.weight_verdict_hint') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.refund_payment_amount') }}</label>
                    <input type="number" step="0.01" name="luggage_refund_amount" id="xlugRefundAmount" readonly
                           value="{{ old('luggage_refund_amount', $booking->luggage_refund_amount) }}"
                           class="mt-1 w-full rounded-lg border-gray-200 bg-white shadow-sm text-gray-800">
                    <p class="mt-1 text-xs text-gray-500" id="xlugDeltaHint">{{ __('vender/luggage.refund_payment_hint_v2') }}</p>
                </div>
                <div class="border-t border-teal-100 pt-2 space-y-1 text-sm text-gray-700" id="xlugFeeBreakdown">
                    <p class="text-xs font-semibold uppercase tracking-wide text-teal-800">{{ __('vender/luggage.fee_breakdown') }}</p>
                    <p class="mb-0"><span class="text-gray-500">{{ __('vender/luggage.fee_gross') }}:</span> <span id="xlugGrossDisplay">{{ $currency }} {{ convert_money($grossLuggageFee > 0 ? $grossLuggageFee : $defaultFee) }}</span></p>
                    <p class="mb-0"><span class="text-gray-500">{{ __('vender/luggage.fee_admin_5') }}:</span> <span id="xlugAdminDisplay">{{ $currency }} {{ convert_money($luggageSplit['system']) }}</span></p>
                    <p class="mb-0"><span class="text-gray-500">{{ __('vender/luggage.fee_government_5') }}:</span> <span id="xlugGovDisplay">{{ $currency }} {{ convert_money($luggageSplit['government']) }}</span></p>
                    <p class="mb-0 font-semibold text-teal-800"><span>{{ __('vender/luggage.fee_bus_owner_90') }}:</span> <span id="xlugOwnerDisplay">{{ $currency }} {{ convert_money($luggageSplit['owner']) }}</span></p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit"
                        class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-600 dark:disabled:bg-slate-700 dark:disabled:text-slate-400"
                        @if($measurementsSaved) disabled aria-disabled="true" @endif>
                    {{ __('vender/luggage.save_weigh_in') }}
                </button>
            </div>
        </form>
        <script>
            (function () {
                const feePerKg = {{ json_encode((float) $feePerKg) }};
                const estimatedWeight = {{ json_encode($estimatedWeightJs) }};
                const labels = {
                    underestimated: @json(__('vender/luggage.weight_verdict_underestimated')),
                    overestimated: @json(__('vender/luggage.weight_verdict_overestimated')),
                    correct: @json(__('vender/luggage.weight_verdict_correct')),
                    hintPositive: @json(__('vender/luggage.auto_delta_additional')),
                    hintNegative: @json(__('vender/luggage.auto_delta_refund')),
                    hintZero: @json(__('vender/luggage.auto_delta_none')),
                };
                const feeInput = document.querySelector('input[name="excess_luggage_fee"]');
                const actualInput = document.getElementById('xlugActualWeight');
                const verdictDisplay = document.getElementById('xlugVerdictDisplay');
                const verdictValue = document.getElementById('xlugVerdictValue');
                const refundInput = document.getElementById('xlugRefundAmount');
                const hintEl = document.getElementById('xlugDeltaHint');
                const grossEl = document.getElementById('xlugGrossDisplay');
                const adminEl = document.getElementById('xlugAdminDisplay');
                const govEl = document.getElementById('xlugGovDisplay');
                const ownerEl = document.getElementById('xlugOwnerDisplay');
                const currency = @json($currency);

                function round2(n) { return Math.round(n * 100) / 100; }

                function money(n) {
                    return currency + ' ' + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }

                function splitFee(gross) {
                    gross = Math.max(0, Number(gross) || 0);
                    const system = round2(gross * 0.05);
                    const government = round2(gross * 0.05);
                    const owner = round2(gross - system - government);
                    return { system: system, government: government, owner: owner };
                }

                function updateBreakdown(gross) {
                    const split = splitFee(gross);
                    if (grossEl) grossEl.textContent = money(gross);
                    if (adminEl) adminEl.textContent = money(split.system);
                    if (govEl) govEl.textContent = money(split.government);
                    if (ownerEl) ownerEl.textContent = money(split.owner);
                }

                function compute() {
                    const actualRaw = actualInput && actualInput.value !== '' ? parseFloat(actualInput.value) : NaN;
                    const paid = feeInput && feeInput.value !== '' ? parseFloat(feeInput.value) : 0;
                    let delta = 0;

                    if (!isNaN(actualRaw)) {
                        if (feePerKg > 0) {
                            if (estimatedWeight !== null && estimatedWeight !== undefined) {
                                delta = round2((actualRaw - estimatedWeight) * feePerKg);
                            } else {
                                delta = round2((actualRaw * feePerKg) - (isNaN(paid) ? 0 : paid));
                            }
                        } else if (estimatedWeight && estimatedWeight > 0 && !isNaN(paid) && paid > 0) {
                            delta = round2(paid * ((actualRaw - estimatedWeight) / estimatedWeight));
                        }
                    }

                    if (Math.abs(delta) < 0.005) delta = 0;

                    let verdict = 'correct';
                    if (delta > 0) verdict = 'underestimated';
                    else if (delta < 0) verdict = 'overestimated';

                    if (verdictDisplay) verdictDisplay.value = labels[verdict] || verdict;
                    if (verdictValue) verdictValue.value = verdict;
                    if (refundInput) refundInput.value = delta.toFixed(2);
                    if (hintEl) {
                        if (delta > 0) hintEl.textContent = labels.hintPositive;
                        else if (delta < 0) hintEl.textContent = labels.hintNegative;
                        else hintEl.textContent = labels.hintZero;
                    }

                    // Show split on total luggage fee after reconciliation (paid + positive top-up).
                    const totalGross = round2((isNaN(paid) ? 0 : paid) + Math.max(0, delta));
                    updateBreakdown(totalGross);
                }

                ['input', 'change'].forEach(function (evt) {
                    if (actualInput) actualInput.addEventListener(evt, compute);
                    if (feeInput) feeInput.addEventListener(evt, compute);
                });
                compute();
            })();
        </script>
        <form method="POST" action="{{ route($ctx['weigh_route'], $booking->id) }}" class="mt-3" onsubmit="return confirm(@json(__('vender/luggage.confirm_remove')));">
            @csrf
            <input type="hidden" name="luggage_action" value="remove">
            <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('vender/luggage.remove') }}</button>
        </form>
    </div>

    <div class="space-y-6">
        {{-- Pay extra via gateway / request luggage refund --}}
        @php
            $test_mode = \App\Models\Setting::isTestMode();
        @endphp
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h2 class="mb-2 text-lg font-semibold text-gray-800 dark:text-gray-100">{{ __('vender/luggage.step_pay') }}</h2>
            @if($test_mode && $amountDue > 0)
                <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-100" role="status">
                    <p class="font-semibold">{{ __('vender/luggage.test_mode_notice') }}</p>
                    <p class="mt-1 text-xs">{{ __('vender/luggage.test_mode_hint') }}</p>
                </div>
                <form method="POST" action="{{ route($ctx['pay_route'], $booking->id) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-400">
                        {{ __('vender/luggage.pay_test_mode', ['amount' => $currency . ' ' . convert_money($amountDue)]) }}
                    </button>
                </form>
            @else
            <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">{{ __('vender/luggage.pay_hint') }}</p>
            @if($amountDue > 0)
                <form method="POST" action="{{ route($ctx['pay_route'], $booking->id) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('vender/luggage.phone') }}</label>
                        <input type="text" name="phone" value="{{ old('phone', $booking->customer_phone) }}"
                               class="mt-1 w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100" required>
                    </div>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        {{ __('vender/luggage.pay_clickpesa', ['amount' => $currency . ' ' . convert_money($amountDue)]) }}
                    </button>
                </form>
            @elseif($refundDisplay > 0)
                <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3">
                    <p class="text-sm font-semibold text-amber-900">
                        {{ __('vender/luggage.refund_owed') }}: {{ $currency }} {{ convert_money($refundDisplay) }}
                    </p>
                    <p class="mt-1 text-xs text-amber-800">{{ __('vender/luggage.refund_hint') }}</p>
                </div>
                @if($payStatus === \App\Services\ExcessLuggageService::PAYMENT_REFUND_PENDING)
                    <p class="text-sm font-medium text-amber-800">{{ __('vender/luggage.refund_pending_admin') }}</p>
                    @if(!empty($booking->luggage_payment_ref))
                        <p class="mt-1 text-xs text-gray-500">{{ __('vender/luggage.refund_ref') }}: {{ $booking->luggage_payment_ref }}</p>
                    @endif
                @elseif($payStatus === \App\Services\ExcessLuggageService::PAYMENT_REFUNDED)
                    <p class="text-sm font-medium text-green-700">{{ __('vender/luggage.refund_processed') }}</p>
                @elseif($payStatus === \App\Services\ExcessLuggageService::PAYMENT_REFUND_REJECTED)
                    <p class="mb-3 text-sm font-medium text-red-700">{{ __('vender/luggage.refund_was_rejected') }}</p>
                    <form method="POST" action="{{ route($ctx['refund_route'], $booking->id) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.refund_phone') }}</label>
                            <input type="text" name="phone" value="{{ old('phone', $booking->customer_phone) }}"
                                   class="mt-1 w-full rounded-lg border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.refund_fullname') }}</label>
                            <input type="text" name="fullname" value="{{ old('fullname', $booking->customer_name) }}"
                                   class="mt-1 w-full rounded-lg border-gray-300 shadow-sm">
                        </div>
                        <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
                            onclick="return confirm(@json(__('vender/luggage.confirm_refund_request')))">
                            {{ __('vender/luggage.request_refund', ['amount' => $currency . ' ' . convert_money($refundDisplay)]) }}
                        </button>
                    </form>
                @elseif($luggageService->canRequestRefund($booking))
                    <form method="POST" action="{{ route($ctx['refund_route'], $booking->id) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.refund_phone') }}</label>
                            <input type="text" name="phone" value="{{ old('phone', $booking->customer_phone) }}"
                                   class="mt-1 w-full rounded-lg border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.refund_fullname') }}</label>
                            <input type="text" name="fullname" value="{{ old('fullname', $booking->customer_name) }}"
                                   class="mt-1 w-full rounded-lg border-gray-300 shadow-sm">
                        </div>
                        <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700"
                            onclick="return confirm(@json(__('vender/luggage.confirm_refund_request')))">
                            {{ __('vender/luggage.request_refund', ['amount' => $currency . ' ' . convert_money($refundDisplay)]) }}
                        </button>
                    </form>
                @else
                    <p class="text-sm text-gray-500">{{ __('vender/luggage.refund_hint') }}</p>
                @endif
            @else
                <p class="text-sm text-gray-500">{{ __('vender/luggage.no_amount_due') }}</p>
            @endif
            @endif
        </div>

        {{-- Assign --}}
        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h2 class="mb-2 text-lg font-semibold text-gray-800 dark:text-gray-100">{{ __('vender/luggage.step_assign') }}</h2>
            @if(!$canAssign)
                <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-100">
                    {{ __('vender/luggage.assign_escrow_blocked') }}
                </div>
            @endif
            <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">{{ __('vender/luggage.assign_hint', ['bus' => $booking->bus->bus_number ?? '—']) }}</p>
            <form method="POST" action="{{ route($ctx['assign_route'], $booking->id) }}">
                @csrf
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-50 dark:bg-teal-500 dark:hover:bg-teal-400"
                    @if(!$canAssign || !in_array($status, ['ready', 'weighed'], true)) disabled @endif>
                    {{ __('vender/luggage.assign_to_bus') }}
                </button>
            </form>
        </div>

        {{-- Reclaim (QR exit) --}}
        <div class="rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
            <h2 class="mb-2 text-lg font-semibold text-gray-800">{{ __('vender/luggage.step_reclaim') }}</h2>
            <p class="mb-3 text-sm text-gray-600">{{ __('vender/luggage.reclaim_hint') }}</p>
            <form method="POST" action="{{ route($ctx['reclaim_route'], $booking->id) }}" class="space-y-3" onsubmit="return confirm(@json(__('vender/luggage.confirm_reclaim')));">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('vender/luggage.qr_payload_label') }}</label>
                    <input type="text" name="qr_payload" value="{{ old('qr_payload', session('scanned_luggage_code')) }}" required
                           class="mt-1 w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500"
                           placeholder="{{ __('vender/luggage.qr_payload_placeholder') }}"
                           autocomplete="off"
                           @if(!in_array($status, ['assigned', 'ready'], true)) disabled @endif>
                    <p class="mt-1 text-xs text-gray-500">{{ __('vender/luggage.qr_payload_help') }}</p>
                </div>
                <button type="submit" class="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900 disabled:opacity-50"
                    @if(!in_array($status, ['assigned', 'ready'], true)) disabled @endif>
                    {{ __('vender/luggage.mark_retrieved') }}
                </button>
            </form>
            <p class="mt-3 text-xs text-gray-500">
                <a href="{{ route($ctx['scan_route']) }}" class="text-teal-700 hover:underline">{{ __('vender/luggage.scan_exit_title') }}</a>
            </p>
            @if($booking->luggage_retrieved_at)
                <p class="mt-2 text-xs text-green-700">{{ __('vender/luggage.retrieved_at') }}: {{ $booking->luggage_retrieved_at }}</p>
            @endif
        </div>
    </div>
</div>
