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
            ->orderBy('bus_number')
            ->get(['id', 'bus_number']);
    }
    $statusChoices = ['registered', 'received', 'in_transit', 'arrived', 'completed', 'cancelled'];
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
        <span class="ml-2 text-sm text-gray-600">{{ $currency }} {{ convert_money($parcel->amount_paid) }} · {{ $parcel->payment_status ?? '—' }}</span>
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
            <p>{{ __('vender/parcels.paid_at') }}: {{ $parcel->settled_at ? $parcel->settled_at->format('d M Y H:i') : '—' }}</p>
            <p>{{ __('vender/parcels.received_at') }}: {{ $parcel->received_at ? $parcel->received_at->format('d M Y H:i') : '—' }}</p>
            <p>{{ __('vender/parcels.departed_at') }}: {{ $parcel->departed_at ? $parcel->departed_at->format('d M Y H:i') : '—' }}</p>
            <p>{{ __('vender/parcels.arrived_at') }}: {{ $parcel->arrived_at ? $parcel->arrived_at->format('d M Y H:i') : '—' }}</p>
            <p>{{ __('vender/parcels.collected_at') }}: {{ $parcel->collected_at ? $parcel->collected_at->format('d M Y H:i') : '—' }}</p>
        </div>
        @if(!$isCollection)
            <p><strong>{{ __('vender/parcels.receiving_agent_name') }}:</strong> {{ $parcel->receiving_agent_name ?? '—' }} {{ $parcel->receiving_agent_phone }}</p>
        @endif
    </div>

    <div class="space-y-4">
        @if(($parcel->payment_status ?? '') !== 'paid')
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

        @if(!$isCollection)
        @php
            $assignmentLocked = filled($parcel->receiving_agent_name)
                || filled($parcel->receiving_agent_phone)
                || filled($parcel->assigned_at ?? null);
        @endphp
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <h2 class="font-semibold mb-2">{{ __('vender/parcels.assign_receiving') }}</h2>
            <form id="parcel-assign-form" method="POST" action="{{ route($showPrefix.'.assign', $parcel->id) }}" class="space-y-2">
                @csrf
                <input type="text" name="receiving_agent_name" value="{{ old('receiving_agent_name', $parcel->receiving_agent_name) }}" placeholder="{{ __('vender/parcels.receiving_agent_name') }}" class="w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-100 disabled:cursor-not-allowed" @disabled($assignmentLocked)>
                <input type="text" name="receiving_agent_phone" value="{{ old('receiving_agent_phone', $parcel->receiving_agent_phone) }}" placeholder="{{ __('vender/parcels.receiving_agent_phone') }}" class="w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-100 disabled:cursor-not-allowed" @disabled($assignmentLocked)>
                <input type="text" name="delivery_rider_name" value="{{ old('delivery_rider_name', $parcel->delivery_rider_name) }}" placeholder="{{ __('vender/parcels.delivery_rider_name') }}" class="w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-100 disabled:cursor-not-allowed" @disabled($assignmentLocked)>
                <input type="text" name="delivery_rider_phone" value="{{ old('delivery_rider_phone', $parcel->delivery_rider_phone) }}" placeholder="{{ __('vender/parcels.delivery_rider_phone') }}" class="w-full rounded-lg border-gray-300 text-sm disabled:bg-gray-100 disabled:cursor-not-allowed" @disabled($assignmentLocked)>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="submit" id="parcel-assign-save" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white disabled:bg-gray-300 disabled:text-gray-600 disabled:cursor-not-allowed" @disabled($assignmentLocked)>{{ __('vender/parcels.save_assignment') }}</button>
                    @if($assignmentLocked)
                        <button type="button" id="parcel-assign-edit" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('vender/parcels.edit_assignment') }}</button>
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

        @if($isBusOwnerView && $companyBuses->isNotEmpty() && !in_array($status, ['completed', 'cancelled'], true))
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <h2 class="font-semibold mb-2">{{ __('vender/parcels.assign_bus') }}</h2>
            <form method="POST" action="{{ route($showPrefix.'.assign', $parcel->id) }}" class="flex flex-wrap items-center gap-2">
                @csrf
                <select name="bus_id" class="rounded-lg border-gray-300 text-sm">
                    @foreach($companyBuses as $companyBus)
                        <option value="{{ $companyBus->id }}" @selected((int) $companyBus->id === (int) $parcel->bus_id)>{{ $companyBus->bus_number }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">{{ __('vender/parcels.save_bus') }}</button>
            </form>
        </div>
        @endif

        @if($isBusOwnerView && $status !== 'completed')
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <h2 class="font-semibold mb-2">{{ __('vender/parcels.update_status') }}</h2>
            <form method="POST" action="{{ route($showPrefix.'.update_status', $parcel->id) }}" class="flex flex-wrap items-center gap-2">
                @csrf
                <select name="status" class="rounded-lg border-gray-300 text-sm">
                    @foreach($statusChoices as $choice)
                        <option value="{{ $choice }}" @selected($status === $choice)>{{ $flow->statusLabel($choice) }}</option>
                    @endforeach
                </select>
                <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm text-white">{{ __('vender/parcels.update') }}</button>
            </form>
        </div>
        @endif

        <div class="rounded-xl border bg-white p-5 shadow-sm flex flex-wrap gap-2">
            <form method="POST" action="{{ route($showPrefix.'.receive', $parcel->id) }}">@csrf
                <button class="rounded-lg bg-green-600 px-3 py-2 text-sm text-white" @if($status !== 'registered' || ($parcel->payment_status ?? '') !== 'paid') disabled @endif>{{ __('vender/parcels.mark_received') }}</button>
            </form>
            <form method="POST" action="{{ route($showPrefix.'.depart', $parcel->id) }}">@csrf
                <button class="rounded-lg bg-blue-600 px-3 py-2 text-sm text-white" @if($status !== 'received') disabled @endif>{{ __('vender/parcels.mark_departed') }}</button>
            </form>
            <form method="POST" action="{{ route($showPrefix.'.arrive', $parcel->id) }}">@csrf
                <button class="rounded-lg bg-purple-600 px-3 py-2 text-sm text-white" @if($status !== 'in_transit') disabled @endif>{{ __('vender/parcels.mark_arrived') }}</button>
            </form>
        </div>

        @if(!in_array($status, ['completed', 'cancelled'], true))
        <form method="POST" action="{{ route($showPrefix.'.update_status', $parcel->id) }}" onsubmit="return confirm(@json(__('vender/parcels.cancel_confirm')))">
            @csrf
            <input type="hidden" name="status" value="cancelled">
            <button class="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-700">{{ __('vender/parcels.cancel_parcel') }}</button>
        </form>
        @endif

        @if(in_array($status, ['in_transit', 'arrived'], true))
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <h2 class="font-semibold mb-2">{{ __('vender/parcels.collect_verify') }}</h2>
            <p class="text-xs text-gray-500 mb-2">{{ __('vender/parcels.collect_hint') }}</p>
            <form method="POST" action="{{ route($showPrefix.'.collect', $parcel->id) }}" class="flex gap-2">
                @csrf
                <input type="text" name="tracking_number" required placeholder="{{ __('vender/parcels.parcel_number') }}" class="flex-1 rounded-lg border-gray-300 text-sm">
                <button class="rounded-lg bg-gray-900 px-4 py-2 text-sm text-white">{{ __('vender/parcels.mark_collected') }}</button>
            </form>
        </div>
        @endif
    </div>
</div>
