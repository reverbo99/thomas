@extends('special_hire.app')

@section('title', 'Create driver')
@section('page_title', 'Create driver')
@section('page_subtitle', 'Link a new driver account to one of your buses')

@section('content')
@php
    $inputClass = 'w-full px-4 py-2.5 bg-white text-gray-900 border border-gray-300 rounded-xl shadow-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500';
@endphp
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-teal-50 to-cyan-50">
            <h3 class="text-lg font-bold text-gray-800">Driver account</h3>
            <p class="text-sm text-gray-600 mt-1">Choose a coaster without a driver, then set login details for the driver app.</p>
        </div>

        <div class="p-6">
            @if($coasters->isEmpty())
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-amber-900">
                    <p class="font-medium">No coasters yet</p>
                    <p class="text-sm mt-1">Add a coaster first, then you can create a driver for it.</p>
                    <a href="{{ route('special_hire.coasters.create') }}" class="inline-flex mt-3 text-sm font-semibold text-teal-800 underline hover:text-teal-900">
                        Add coaster
                    </a>
                </div>
            @elseif($assignableCoasters->isEmpty())
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-amber-900">
                    <p class="font-medium">No coasters available for a new driver</p>
                    <p class="text-sm mt-1">
                        Every coaster already has a driver login linked.
                        Manage drivers from the Coasters page (reset password), or add another coaster.
                    </p>
                    <div class="flex flex-wrap gap-3 mt-3">
                        <a href="{{ route('special_hire.coasters') }}" class="inline-flex text-sm font-semibold text-teal-800 underline hover:text-teal-900">
                            View coasters
                        </a>
                        <a href="{{ route('special_hire.coasters.create') }}" class="inline-flex text-sm font-semibold text-teal-800 underline hover:text-teal-900">
                            Add coaster
                        </a>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('special_hire.drivers.store') }}" class="space-y-5" autocomplete="off">
                    @csrf

                    <div>
                        <label for="coaster_id" class="block text-sm font-medium text-gray-700 mb-2">Bus (coaster) *</label>
                        <select name="coaster_id" id="coaster_id" required
                            class="{{ $inputClass }}">
                            <option value="">— Select —</option>
                            @foreach($assignableCoasters as $c)
                                <option value="{{ $c->id }}" {{ (string) old('coaster_id') === (string) $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}@if($c->plate_number) ({{ $c->plate_number }})@endif
                                </option>
                            @endforeach
                        </select>
                        @if($coasters->count() > $assignableCoasters->count())
                            <p class="text-xs text-gray-500 mt-1.5">
                                {{ $coasters->count() - $assignableCoasters->count() }} coaster(s) already have a driver and are hidden from this list.
                            </p>
                        @endif
                        @error('coaster_id')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Driver name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                            autocomplete="name"
                            placeholder="e.g. John Doe"
                            class="{{ $inputClass }}">
                        @error('name')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email (login) *</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                            autocomplete="email"
                            placeholder="driver@example.com"
                            class="{{ $inputClass }}">
                        @error('email')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
                        <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required
                            autocomplete="tel"
                            inputmode="tel"
                            placeholder="+255712345678"
                            class="{{ $inputClass }}">
                        @error('phone')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                        <input type="password" name="password" id="password" required minlength="6"
                            autocomplete="new-password"
                            placeholder="At least 6 characters"
                            class="{{ $inputClass }}">
                        @error('password')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="submit" class="btn-primary flex-1 py-3 rounded-xl text-white font-semibold text-sm">
                            Create &amp; assign
                        </button>
                        <a href="{{ route('special_hire.coasters') }}" class="px-4 py-3 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">
                            Cancel
                        </a>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
