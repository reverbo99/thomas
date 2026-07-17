@extends('special_hire.app')

@section('title', 'Reset driver password')
@section('page_title', 'Reset driver password')
@section('page_subtitle', $driver->name)

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
        <p class="text-sm text-gray-600 mb-2">
            Set a new login password for <strong>{{ $driver->name }}</strong>
            <span class="text-gray-400">({{ $driver->email }})</span>.
        </p>
        <p class="text-xs text-gray-500 mb-6">The driver will use this password in the driver app. Their previous password will stop working immediately.</p>

        <form method="POST" action="{{ route('special_hire.drivers.reset_password.store', $driver->id) }}" class="space-y-5">
            @csrf

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New password</label>
                <input type="password" name="password" id="password" required autocomplete="new-password"
                    placeholder="At least 6 characters"
                    class="w-full px-4 py-2.5 bg-white text-gray-900 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                @error('password')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm new password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                    placeholder="Repeat password"
                    class="w-full px-4 py-2.5 bg-white text-gray-900 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
            </div>

            <div class="flex flex-wrap items-center gap-3 pt-2">
                <button type="submit" class="btn-primary px-6 py-2.5 text-white rounded-xl font-medium">
                    Save new password
                </button>
                <a href="{{ route('special_hire.coasters') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
