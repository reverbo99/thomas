@extends('system.app')

@section('title', 'Passengers — ' . $hireOrder->order_code)

@section('content')
<div class="container mx-auto px-4 py-6 max-w-4xl">
    @if(session('success'))
        <div class="mb-4 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800 text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6">
        <a href="{{ route('system.special_hire.show', $hireOrder->user_id) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">← {{ optional($hireOrder->user)->name ?? 'Operator' }}</a>
        <h1 class="text-2xl font-semibold text-gray-800 mt-2">{{ __('system.pages.manage_passengers') }}</h1>
        <p class="text-gray-600 mt-1">
            {{ __('system.pages.order') }} <span class="font-mono">{{ $hireOrder->order_code }}</span>
            · {{ $hireOrder->coaster->name ?? '—' }} ({{ $hireOrder->coaster->plate_number ?? '—' }})
            · {{ $hireOrder->hire_date?->format('Y-m-d') }}
        </p>
        <p class="text-sm text-gray-500 mt-1">{{ __('system.pages.customer') }}: {{ $hireOrder->customer_name }} · {{ $hireOrder->customer_phone }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <p class="text-sm text-gray-600">
            {{ __('system.pages.passengers_count_label') }}: <strong>{{ $hireOrder->passengers_count }}</strong>
            — {{ __('system.pages.passengers_capture_hint') }}
        </p>
    </div>

    <form action="{{ route('system.special_hire.order.passengers.update', $hireOrder->id) }}" method="post">
        @csrf
        <div id="passenger-rows" class="space-y-3 mb-4">
            @php $rowCount = max($hireOrder->passengers_count, count($passengers), 1); @endphp
            @for ($i = 0; $i < $rowCount; $i++)
                @php $p = $passengers[$i] ?? null; @endphp
                <div class="passenger-row bg-white rounded-xl shadow-sm border border-gray-100 p-4 grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                    <div class="sm:col-span-1 text-sm text-gray-400 font-medium">#{{ $i + 1 }}</div>
                    <div class="sm:col-span-4">
                        <input type="text" name="passengers[{{ $i }}][name]" value="{{ old("passengers.$i.name", $p['name'] ?? '') }}"
                               placeholder="{{ __('system.pages.passenger_name') }}"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="sm:col-span-3">
                        <input type="text" name="passengers[{{ $i }}][phone]" value="{{ old("passengers.$i.phone", $p['phone'] ?? '') }}"
                               placeholder="{{ __('system.pages.passenger_phone') }}"
                               class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="sm:col-span-2">
                        <select name="passengers[{{ $i }}][gender]" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">{{ __('system.pages.gender') }}</option>
                            <option value="male" {{ old("passengers.$i.gender", $p['gender'] ?? '') === 'male' ? 'selected' : '' }}>{{ __('system.pages.male') }}</option>
                            <option value="female" {{ old("passengers.$i.gender", $p['gender'] ?? '') === 'female' ? 'selected' : '' }}>{{ __('system.pages.female') }}</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2 flex items-center justify-end gap-2">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="passengers[{{ $i }}][is_infant]" value="1"
                                   {{ old("passengers.$i.is_infant", $p['is_infant'] ?? false) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            {{ __('system.pages.infant') }}
                        </label>
                    </div>
                </div>
            @endfor
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <button type="button" id="add-passenger-row" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-sm">
                + {{ __('system.pages.add_passenger_row') }}
            </button>
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow-sm transition-colors">
                {{ __('system.common.save') }}
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    var container = document.getElementById('passenger-rows');
    var addBtn = document.getElementById('add-passenger-row');
    if (!container || !addBtn) return;

    addBtn.addEventListener('click', function () {
        var index = container.querySelectorAll('.passenger-row').length;
        var row = document.createElement('div');
        row.className = 'passenger-row bg-white rounded-xl shadow-sm border border-gray-100 p-4 grid grid-cols-1 sm:grid-cols-12 gap-3 items-center';
        row.innerHTML =
            '<div class="sm:col-span-1 text-sm text-gray-400 font-medium">#' + (index + 1) + '</div>' +
            '<div class="sm:col-span-4"><input type="text" name="passengers[' + index + '][name]" placeholder="{{ __('system.pages.passenger_name') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>' +
            '<div class="sm:col-span-3"><input type="text" name="passengers[' + index + '][phone]" placeholder="{{ __('system.pages.passenger_phone') }}" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></div>' +
            '<div class="sm:col-span-2"><select name="passengers[' + index + '][gender]" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">' +
                '<option value="">{{ __('system.pages.gender') }}</option>' +
                '<option value="male">{{ __('system.pages.male') }}</option>' +
                '<option value="female">{{ __('system.pages.female') }}</option>' +
            '</select></div>' +
            '<div class="sm:col-span-2 flex items-center justify-end gap-2"><label class="inline-flex items-center gap-2 text-sm text-gray-700">' +
                '<input type="checkbox" name="passengers[' + index + '][is_infant]" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"> {{ __('system.pages.infant') }}' +
            '</label></div>';
        container.appendChild(row);
    });
})();
</script>
@endsection
