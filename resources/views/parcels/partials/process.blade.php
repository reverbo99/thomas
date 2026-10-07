@php
    $test_mode = \App\Models\Setting::isTestMode();
    $status = $status ?? $flow->normalizeStatus($parcel);
    $showPrefix = request()->routeIs('bus_owner.*') ? 'bus_owner.parcels' : 'vender.parcels';
    $currency = $currency ?? session('currency', 'TZS');
    $canPrint = $flow->canPrintReceipt($parcel);
    $isCollection = ($parcel->parcel_instructions ?? '') === 'collection';
    $routeFrom = $parcel->bus->route->from ?? $parcel->bus->schedule->from ?? null;
    $routeTo = $parcel->bus->route->to ?? $parcel->bus->schedule->to ?? null;
    $addressLabel = $isCollection
        ? __('vender/parcels.receiver_collection_address')
        : __('vender/parcels.receiver_delivery_address');
    $isBusOwnerView = request()->routeIs('bus_owner.*');
    $companyBuses = collect();
    if ($isBusOwnerView && auth()->user()?->campany) {
        $companyBuses = \App\Models\bus::query()
            ->where('campany_id', auth()->user()->campany->id)
            ->with('route')
            ->orderBy('bus_number')
            ->get(['id', 'bus_number', 'route_id']);
    }

    $payMode = $parcel->payment_mode ?? \App\Services\ParcelFlowService::MODE_CASH;
    $modeLabels = [
        \App\Services\ParcelFlowService::MODE_CASH => __('vender/parcels.mode_cash'),
        \App\Services\ParcelFlowService::MODE_INSTALMENT => __('vender/parcels.mode_instalment'),
        \App\Services\ParcelFlowService::MODE_COD => __('vender/parcels.mode_cod'),
    ];
    $modeLabel = $modeLabels[$payMode] ?? $modeLabels[\App\Services\ParcelFlowService::MODE_CASH];
    $paidAmount = (float) ($parcel->paid_amount ?? ($flow->isFullyPaid($parcel) ? $parcel->amount_paid : 0));
    $balanceDue = round((float) ($parcel->balance_due ?? 0), 2);
@endphp

@if(session('success'))
    <div class="mb-4 rounded-lg border-l-4 border-green-500 bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-100">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-100">{{ session('error') }}</div>
@endif

<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">{{ $parcel->parcel_number }}</h1>
        <p class="text-sm text-gray-500">{{ $parcel->bus->bus_number ?? '—' }} · {{ $parcel->bus->campany->name ?? '' }}</p>
        @if($routeFrom || $routeTo)
            <p class="mt-1 text-sm font-medium text-gray-700">
                <i class="fas fa-route mr-1 text-teal-600"></i>
                {{ __('vender/parcels.origin_destination') }}:
                <span class="font-semibold">{{ $routeFrom ?: '—' }} → {{ $routeTo ?: '—' }}</span>
            </p>
        @endif
        <span class="mt-2 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">{{ $flow->statusLabel($status) }}</span>
        <span class="ml-2 text-sm text-gray-600">{{ $currency }} {{ convert_money($parcel->amount_paid) }} · {{ $flow->paymentStatusLabel($parcel->payment_status) }}</span>
    </div>
    <div class="flex flex-wrap gap-2 items-center">
        <a href="{{ route($showPrefix.'.index') }}" class="rounded-lg border px-3 py-2 text-sm">{{ __('vender/parcels.back') }}</a>
        @if($canPrint)
            <a href="{{ route($showPrefix.'.print', $parcel->id) }}" target="_blank" class="rounded-lg bg-teal-600 px-3 py-2 text-sm text-white">{{ __('vender/parcels.print_receipt') }}</a>
        @else
            <span class="inline-flex flex-col items-end">
                <button type="button" disabled class="rounded-lg bg-gray-300 px-3 py-2 text-sm text-gray-600 cursor-not-allowed" title="{{ __('vender/parcels.print_payment_required') }}">
                    {{ __('vender/parcels.print_receipt') }}
                </button>
                <span class="mt-1 text-xs text-red-600">{{ __('vender/parcels.print_payment_required') }}</span>
            </span>
        @endif
    </div>
</div>

<div class="mb-6 grid gap-3 rounded-xl border bg-white p-4 shadow-sm sm:grid-cols-3 dark:border-slate-700 dark:bg-slate-800">
    <div>
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('vender/parcels.payment_mode') }}</p>
        <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $modeLabel }}</p>
    </div>
    <div>
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('vender/parcels.paid_amount') }}</p>
        <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $currency }} {{ convert_money($paidAmount) }}</p>
    </div>
    <div>
        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('vender/parcels.balance_due') }}</p>
        <p class="mt-1 text-sm font-semibold {{ $balanceDue > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ $currency }} {{ convert_money($balanceDue) }}</p>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-xl border bg-white p-5 shadow-sm space-y-2 text-sm">
        @if($routeFrom || $routeTo)
            <p><strong>{{ __('vender/parcels.origin_destination') }}:</strong> {{ $routeFrom ?: '—' }} → {{ $routeTo ?: '—' }}</p>
        @endif
        <p><strong>{{ __('vender/parcels.sender_name') }}:</strong> {{ $parcel->sender_name }} ({{ $parcel->sender_contact }})</p>
        <p><strong>{{ __('vender/parcels.receiver_name') }}:</strong> {{ $parcel->receiver_name }} ({{ $parcel->receiver_contact_1 }})</p>
        <p><strong>{{ $addressLabel }}:</strong> {{ $parcel->receiver_delivery_address }}</p>
        <p><strong>{{ __('vender/parcels.parcel_instructions') }}:</strong>
            {{ $isCollection ? __('vender/parcels.instructions_collection') : __('vender/parcels.instructions_delivery') }}
        </p>
        <p><strong>{{ __('vender/parcels.weight_kg') }}:</strong> {{ $parcel->weight ?? '—' }}</p>
        @if($parcel->length || $parcel->height || $parcel->width)
            <p><strong>{{ __('vender/parcels.dimensions_weight') }}:</strong>
                {{ $parcel->length ?: '—' }} × {{ $parcel->width ?: '—' }} × {{ $parcel->height ?: '—' }} cm
            </p>
        @endif
        @if(filled($parcel->description))
            <p><strong>{{ __('vender/parcels.description') }}:</strong> {{ $parcel->description }}</p>
        @endif
        @php
            $parcelSplit = parcel_share_split($parcel);
        @endphp
        @if($isBusOwnerView)
            <p><strong>{{ __('vender/parcels.your_share') }}:</strong> {{ $currency }} {{ convert_money($parcelSplit['owner']) }}</p>
            @if($parcelSplit['vendor'] > 0)
                <p><strong>{{ __('vender/parcels.vendor_share') }}:</strong> {{ $currency }} {{ convert_money($parcelSplit['vendor']) }}</p>
            @endif
        @else
            <p><strong>{{ __('vender/parcels.your_share') }}:</strong> {{ $currency }} {{ convert_money($parcelSplit['vendor']) }}</p>
            <p><strong>{{ __('vender/parcels.bus_owner_share') }}:</strong> {{ $currency }} {{ convert_money($parcelSplit['owner']) }}</p>
        @endif
        <p><strong>{{ __('vender/parcels.admin_share') }}:</strong> {{ $currency }} {{ convert_money($parcelSplit['admin']) }}</p>
        <p><strong>{{ __('vender/parcels.government_levy_share') }}:</strong> {{ $currency }} {{ convert_money($parcelSplit['government']) }}</p>
        <p class="text-xs text-gray-500">{{ __('vender/parcels.share_formula') }}</p>
        <div class="pt-2 text-xs text-gray-600 space-y-1">
            <p class="font-semibold text-gray-800">{{ __('vender/parcels.timeline') }}</p>
            @php $paidAt = $parcel->settled_at ?? $parcel->deposit_paid_at; @endphp
            <p>{{ __('vender/parcels.paid_at') }}: {{ $paidAt ? $paidAt->format('d M Y H:i') : '—' }}
                @if(!$flow->isFullyPaid($parcel) && $parcel->deposit_paid_at)
                    ({{ __('vender/parcels.status_partial') }})
                @endif
            </p>
            <p>{{ __('vender/parcels.loaded_at') }}: {{ $parcel->loaded_at ? $parcel->loaded_at->format('d M Y H:i') : '—' }}</p>
            <p>{{ __('vender/parcels.received_at') }}: {{ $parcel->received_at ? $parcel->received_at->format('d M Y H:i') : '—' }}</p>
            <p>{{ __('vender/parcels.collected_at') }}: {{ $parcel->collected_at ? $parcel->collected_at->format('d M Y H:i') : '—' }}</p>
        </div>
        @if(!$isCollection)
            <p><strong>{{ __('vender/parcels.receiving_agent_name') }}:</strong> {{ $parcel->receiving_agent_name ?? '—' }} {{ $parcel->receiving_agent_phone }}</p>
        @endif
        @if(filled($parcel->conductor_name) || filled($parcel->conductor_phone))
            <p><strong>{{ __('vender/parcels.conductor_name') }}:</strong> {{ $parcel->conductor_name ?? '—' }} {{ $parcel->conductor_phone }}</p>
        @endif
        @if(filled($parcel->collector_name) || filled($parcel->collector_phone))
            <p><strong>{{ __('vender/parcels.collector_name') }}:</strong> {{ $parcel->collector_name ?? '—' }} {{ $parcel->collector_phone }}</p>
        @endif
    </div>

    <div class="space-y-4">
        @if(!in_array($parcel->payment_status ?? '', ['paid', 'cod'], true))
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            @if($test_mode ?? false)
                <h2 class="mb-2 font-semibold text-gray-800 dark:text-gray-100">{{ __('vender/parcels.pay_test_mode') }}</h2>
                <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-100" role="status">
                    <p class="font-semibold">{{ __('vender/parcels.test_mode_notice') }}</p>
                    <p class="mt-1 text-xs">{{ __('vender/parcels.test_mode_hint') }}</p>
                </div>
                <form method="POST" action="{{ route($showPrefix.'.pay', $parcel->id) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-400">{{ __('vender/parcels.pay_test_mode') }}</button>
                </form>
            @else
                <h2 class="mb-2 font-semibold text-gray-800 dark:text-gray-100">{{ __('vender/parcels.pay_clickpesa') }}</h2>
                <form method="POST" action="{{ route($showPrefix.'.pay', $parcel->id) }}" class="space-y-3">
                    @csrf
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200" for="retry_phone">{{ __('vender/parcels.clickpesa_phone') }}</label>
                    <input type="tel" id="retry_phone" name="phone" value="{{ old('phone', $parcel->sender_contact) }}" class="w-full rounded-lg border-gray-300 bg-white text-gray-900 dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100" required inputmode="tel" autocomplete="tel" placeholder="{{ __('vender/parcels.clickpesa_phone_placeholder') }}">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('vender/parcels.clickpesa_hint') }}</p>
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">{{ __('vender/parcels.pay_and_register') }}</button>
                </form>
            @endif
        </div>
        @endif

        @php
            $vendorAssignmentBlocked = !$isBusOwnerView && $flow->inCustody($status);
            $assignmentClosed = in_array($status, ['completed', 'cancelled'], true);
        @endphp
        @if(!$isCollection && !$vendorAssignmentBlocked && !$assignmentClosed)
        @php
            $assignmentLocked = filled($parcel->receiving_agent_name)
                || filled($parcel->receiving_agent_phone)
                || filled($parcel->assigned_at ?? null);
        @endphp
        <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h2 class="font-semibold mb-2 text-gray-800 dark:text-gray-100">{{ __('vender/parcels.assign_receiving') }}</h2>
            <form id="parcel-assign-form" method="POST" action="{{ route($showPrefix.'.assign', $parcel->id) }}" class="space-y-2">
                @csrf
                <input type="text" name="receiving_agent_name" value="{{ old('receiving_agent_name', $parcel->receiving_agent_name) }}" placeholder="{{ __('vender/parcels.receiving_agent_name') }}" class="w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-100 disabled:cursor-not-allowed dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100" @disabled($assignmentLocked)>
                <input type="text" name="receiving_agent_phone" value="{{ old('receiving_agent_phone', $parcel->receiving_agent_phone) }}" placeholder="{{ __('vender/parcels.receiving_agent_phone') }}" class="w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-100 disabled:cursor-not-allowed dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100" @disabled($assignmentLocked)>
                <input type="text" name="delivery_rider_name" value="{{ old('delivery_rider_name', $parcel->delivery_rider_name) }}" placeholder="{{ __('vender/parcels.delivery_rider_name') }}" class="w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-100 disabled:cursor-not-allowed dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100" @disabled($assignmentLocked)>
                <input type="text" name="delivery_rider_phone" value="{{ old('delivery_rider_phone', $parcel->delivery_rider_phone) }}" placeholder="{{ __('vender/parcels.delivery_rider_phone') }}" class="w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-100 disabled:cursor-not-allowed dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100" @disabled($assignmentLocked)>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" id="parcel-assign-save" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white disabled:bg-gray-300 disabled:text-gray-600 disabled:cursor-not-allowed" @disabled($assignmentLocked)>{{ __('vender/parcels.save_assignment') }}</button>
                    @if($assignmentLocked)
                        <button type="button" id="parcel-assign-edit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100">{{ __('vender/parcels.edit_assignment') }}</button>
                    @endif
                </div>
            </form>
            @if($assignmentLocked)
            <script>
                (function () {
                    var form = document.getElementById('parcel-assign-form');
                    var editBtn = document.getElementById('parcel-assign-edit');
                    var saveBtn = document.getElementById('parcel-assign-save');
                    if (!form || !editBtn || !saveBtn) return;
                    editBtn.addEventListener('click', function () {
                        form.querySelectorAll('input[name]').forEach(function (el) { el.disabled = false; });
                        saveBtn.disabled = false;
                        editBtn.classList.add('hidden');
                    });
                })();
            </script>
            @endif
        </div>
        @endif

        @if($vendorAssignmentBlocked)
        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-300">
            {{ __('vender/parcels.assignment_locked_after_handover') }}
        </div>
        @endif

        @if($isBusOwnerView && $companyBuses->isNotEmpty() && !in_array($status, ['completed', 'cancelled'], true))
        <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h2 class="mb-2 font-semibold text-gray-800 dark:text-gray-100">{{ __('vender/parcels.assign_bus') }}</h2>
            @php
                $currentBus = $parcel->bus;
                $currentBusNumber = $currentBus->bus_number ?? null;
                $currentBusFrom = $currentBus->route->from ?? $currentBus->schedule->from ?? null;
                $currentBusTo = $currentBus->route->to ?? $currentBus->schedule->to ?? null;
            @endphp
            <p class="mb-3 text-sm text-gray-800 dark:text-gray-100">
                <span class="font-bold">{{ __('vender/parcels.current_bus') }}:</span>
                {{ $currentBusNumber ?: '-' }} — {{ $currentBusFrom ?: '-' }} -> {{ $currentBusTo ?: '-' }}
            </p>
            <form method="POST" action="{{ route($showPrefix.'.assign', $parcel->id) }}" class="flex flex-wrap items-center gap-2"
                @if(in_array($status, ['loaded', 'received'], true)) onsubmit="return confirm(@json(__('vender/parcels.bus_move_confirm')))" @endif>
                @csrf
                <select name="bus_id" class="rounded-lg border-gray-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100">
                    @foreach($companyBuses as $companyBus)
                        @php
                            $isCurrentBus = (int) $companyBus->id === (int) $parcel->bus_id;
                            $busRouteLabel = $companyBus->route
                                ? ' — ' . ($companyBus->route->from ?: '—') . ' -> ' . ($companyBus->route->to ?: '—')
                                : '';
                            $busMarker = $isCurrentBus ? ' ' . __('vender/parcels.bus_current_marker') : '';
                        @endphp
                        <option value="{{ $companyBus->id }}" @selected($isCurrentBus)>{{ $companyBus->bus_number }}{{ $busRouteLabel }}{{ $busMarker }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-700 dark:bg-teal-500 dark:hover:bg-teal-400">{{ __('vender/parcels.save_bus') }}</button>
            </form>
        </div>
        @endif

        @if($isBusOwnerView && !in_array($status, ['completed', 'cancelled'], true))
        <div class="rounded-xl border bg-white p-5 shadow-sm space-y-4 dark:border-slate-700 dark:bg-slate-800">
            <h2 class="font-semibold text-gray-800 dark:text-gray-100">{{ __('vender/parcels.movement_actions') }}</h2>

            @if(in_array($status, ['registered', 'in_store'], true))
                <form method="POST" action="{{ route($showPrefix.'.store_state', $parcel->id) }}">
                    @csrf
                    <button class="rounded-lg bg-amber-600 px-3 py-2 text-sm text-white" @if(!$flow->paymentAllowsMovement($parcel)) disabled @endif>{{ __('vender/parcels.mark_in_store') }}</button>
                </form>

                <form method="POST" action="{{ route($showPrefix.'.load', $parcel->id) }}" class="space-y-2 rounded-lg border border-gray-200 p-3 dark:border-slate-600">
                    @csrf
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('vender/parcels.mark_loaded') }}</p>
                    <input type="text" name="conductor_name" required value="{{ old('conductor_name') }}" placeholder="{{ __('vender/parcels.conductor_name') }}" class="w-full rounded-lg border-gray-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100">
                    <input type="tel" name="conductor_phone" required value="{{ old('conductor_phone') }}" placeholder="{{ __('vender/parcels.conductor_phone') }}" class="w-full rounded-lg border-gray-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100">
                    <button class="rounded-lg bg-blue-600 px-3 py-2 text-sm text-white" @if(!$flow->paymentAllowsMovement($parcel)) disabled @endif>{{ __('vender/parcels.mark_loaded') }}</button>
                    @if(!$flow->paymentAllowsMovement($parcel))
                        <p class="text-xs text-red-600">{{ __('vender/parcels.cannot_load_unpaid') }}</p>
                    @endif
                </form>
            @endif

            @if($status === 'loaded')
                <form method="POST" action="{{ route($showPrefix.'.receive', $parcel->id) }}">
                    @csrf
                    <button class="rounded-lg bg-green-600 px-3 py-2 text-sm text-white">{{ __('vender/parcels.mark_received_destination') }}</button>
                </form>
            @endif
        </div>
        @endif

        @if($isBusOwnerView && $balanceDue > 0 && $payMode !== 'cash' && in_array($status, ['received', 'loaded'], true))
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-5 shadow-sm dark:border-amber-700 dark:bg-amber-900/30">
            <h2 class="font-semibold mb-2 text-amber-900 dark:text-amber-100">{{ __('vender/parcels.collect_balance') }}</h2>
            <p class="text-xs text-amber-800 dark:text-amber-200 mb-3">{{ __('vender/parcels.collect_balance_hint') }}</p>
            <form method="POST" action="{{ route($showPrefix.'.collect_balance', $parcel->id) }}">
                @csrf
                <button class="rounded-lg bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700">{{ __('vender/parcels.collect_balance') }} ({{ $currency }} {{ convert_money($balanceDue) }})</button>
            </form>
        </div>
        @endif

        @if($isBusOwnerView && $status === 'received')
        <div class="rounded-xl border bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h2 class="font-semibold mb-2 text-gray-800 dark:text-gray-100">{{ __('vender/parcels.collect_verify') }}</h2>
            <p class="text-xs text-gray-500 mb-2 dark:text-gray-400">{{ __('vender/parcels.collect_hint') }}</p>
            <form id="parcel-collect-form" method="POST" action="{{ route($showPrefix.'.collect', $parcel->id) }}" class="space-y-2">
                @csrf
                <input type="text" name="tracking_number" required placeholder="{{ __('vender/parcels.parcel_number') }}" class="w-full rounded-lg border-gray-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100">
                <input type="text" name="collector_name" required value="{{ old('collector_name', $parcel->receiver_name) }}" placeholder="{{ __('vender/parcels.collector_name') }}" class="w-full rounded-lg border-gray-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100">
                <input type="tel" name="collector_phone" value="{{ old('collector_phone', $parcel->receiver_contact_1) }}" placeholder="{{ __('vender/parcels.collector_phone') }}" class="w-full rounded-lg border-gray-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300" for="parcel-signature-pad">{{ __('vender/parcels.collector_signature') }}</label>
                    <canvas id="parcel-signature-pad" width="420" height="140" class="w-full touch-none rounded-lg border border-gray-300 bg-white"></canvas>
                    <input type="hidden" name="collector_signature" id="parcel-signature-input">
                    <button type="button" id="parcel-signature-clear" class="mt-1 text-xs text-gray-500 underline dark:text-gray-400">{{ __('vender/parcels.signature_clear') }}</button>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('vender/parcels.signature_hint') }}</p>
                </div>
                <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm text-white" @if($balanceDue > 0) disabled @endif>{{ __('vender/parcels.mark_collected') }}</button>
                @if($balanceDue > 0)
                    <p class="text-xs text-red-600 dark:text-red-400">{{ __('vender/parcels.mark_collected_needs_balance') }}</p>
                @endif
            </form>
        </div>
        @endif

        @if(!$isBusOwnerView && !in_array($status, ['completed', 'cancelled'], true))
        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-300">
            {{ __('vender/parcels.handover_hint') }}
        </div>
        @endif

        @if($status !== 'cancelled' && $flow->actorMayCancel($status, $isBusOwnerView))
        <form method="POST" action="{{ route($showPrefix.'.update_status', $parcel->id) }}" onsubmit="return confirm(@json(__('vender/parcels.cancel_confirm')))">
            @csrf
            <input type="hidden" name="status" value="cancelled">
            <button class="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700 dark:border-red-800 dark:text-red-300">{{ __('vender/parcels.cancel_parcel') }}</button>
        </form>
        @endif
    </div>
</div>

@if($isBusOwnerView && $status === 'received')
<script>
(function () {
    var canvas = document.getElementById('parcel-signature-pad');
    var input = document.getElementById('parcel-signature-input');
    var clearBtn = document.getElementById('parcel-signature-clear');
    var form = document.getElementById('parcel-collect-form');
    if (!canvas) return;

    var ctx = canvas.getContext('2d');
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#111827';

    var drawing = false;
    var drew = false;

    function point(e) {
        var rect = canvas.getBoundingClientRect();
        var p = e.touches ? e.touches[0] : e;
        return {
            x: (p.clientX - rect.left) * (canvas.width / rect.width),
            y: (p.clientY - rect.top) * (canvas.height / rect.height)
        };
    }
    function start(e) {
        drawing = true;
        drew = true;
        var p = point(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
        if (e.cancelable) e.preventDefault();
    }
    function move(e) {
        if (!drawing) return;
        var p = point(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        if (e.cancelable) e.preventDefault();
    }
    function end() { drawing = false; }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', end);

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            drew = false;
            if (input) input.value = '';
        });
    }
    if (form && input) {
        form.addEventListener('submit', function () {
            input.value = drew ? canvas.toDataURL('image/png') : '';
        });
    }
})();
</script>
@endif
