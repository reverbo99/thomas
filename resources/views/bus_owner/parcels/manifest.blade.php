@extends('admin.app')
@section('title', __('vender/parcels.manifest_title'))
@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex flex-wrap justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">{{ __('vender/parcels.manifest_title') }}</h1>
            <p class="text-sm text-gray-500">{{ __('vender/parcels.manifest_subtitle') }}</p>
        </div>
        <a href="{{ route('bus_owner.parcels.index') }}" class="text-sm text-teal-700">{{ __('vender/parcels.back') }}</a>
    </div>
    <form method="GET" class="mb-4 flex flex-wrap gap-2 items-end">
        <div>
            <label class="text-xs text-gray-600">{{ __('vender/parcels.bus') }}</label>
            <select name="bus_id" class="rounded-lg border-gray-300 text-sm">
                <option value="">{{ __('vender/parcels.all_buses') }}</option>
                @foreach($buses as $bus)
                    <option value="{{ $bus->id }}" @selected(request('bus_id')==$bus->id)>{{ $bus->bus_number }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-gray-600">{{ __('vender/parcels.travel_date') }}</label>
            <input type="date" name="travel_date" value="{{ request('travel_date') }}" class="rounded-lg border-gray-300 text-sm">
        </div>
        <div>
            <label class="text-xs text-gray-600">{{ __('vender/parcels.search') }}</label>
            <input type="text" name="q" value="{{ request('q') }}" class="rounded-lg border-gray-300 text-sm">
        </div>
        <button class="rounded-lg bg-gray-800 px-3 py-2 text-sm text-white">{{ __('vender/parcels.filter') }}</button>
        <a href="{{ request()->fullUrlWithQuery(['print' => 1]) }}" target="_blank" class="rounded-lg bg-teal-600 px-3 py-2 text-sm text-white">{{ __('vender/parcels.print_manifest') }}</a>
    </form>
    <div class="overflow-x-auto rounded-xl border bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left">{{ __('vender/parcels.parcel_no') }}</th>
                    <th class="px-3 py-2 text-left">{{ __('vender/parcels.bus') }}</th>
                    <th class="px-3 py-2 text-left">{{ __('vender/parcels.receiver_name') }}</th>
                    <th class="px-3 py-2 text-left">{{ __('vender/parcels.weight_kg') }}</th>
                    <th class="px-3 py-2 text-left">{{ __('vender/parcels.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($parcels as $p)
                    <tr class="border-t">
                        <td class="px-3 py-2"><a class="text-teal-700" href="{{ route('bus_owner.parcels.show', $p->id) }}">{{ $p->parcel_number }}</a></td>
                        <td class="px-3 py-2">{{ $p->bus->bus_number ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $p->receiver_name }}</td>
                        <td class="px-3 py-2">{{ $p->weight ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $p->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-6 text-center text-gray-500">{{ __('vender/parcels.no_parcels_found') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $parcels->links() }}</div>
</div>
@endsection
