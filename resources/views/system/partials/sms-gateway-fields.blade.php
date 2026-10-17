@php
    $gw = $gateway ?? null;
    $idSuffix = $idSuffix ?? 'new';
    $secretSaved = $gw && filled($gw->getRawOriginal('client_secret'));
@endphp

<div>
    <label for="{{ $idSuffix }}_gateway_name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.gateway_name') }}</label>
    <input type="text" id="{{ $idSuffix }}_gateway_name" name="gateway_name" maxlength="100"
           value="{{ old('gateway_name', $gw->name ?? '') }}" required
           class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md"
           placeholder="Main bulk SMS">
    <p class="mt-1 text-xs text-gray-500">{{ __('system.settings.gateway_name_desc') }}</p>
    @if ($showErrors ?? true)
        @error('gateway_name')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror @endif
</div>

<div>
    <label for="{{ $idSuffix }}_gateway_client_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.gateway_client_id') }}</label>
    <input type="text" id="{{ $idSuffix }}_gateway_client_id" name="gateway_client_id" maxlength="191"
           value="{{ old('gateway_client_id', $gw->client_id ?? '') }}" required autocomplete="off"
           class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md">
    @if ($showErrors ?? true)
        @error('gateway_client_id')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror @endif
</div>

<div>
    <label for="{{ $idSuffix }}_gateway_client_secret" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.gateway_client_secret') }}</label>
    <input type="password" id="{{ $idSuffix }}_gateway_client_secret" name="gateway_client_secret" maxlength="500"
           autocomplete="new-password" @if (!$gw) required @endif
           class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md"
           placeholder="{{ $secretSaved ? __('system.settings.secret_saved_placeholder') : __('system.settings.secret_empty_placeholder') }}">
    <p class="mt-1 text-xs text-gray-500">{{ __('system.settings.gateway_client_secret_desc') }}</p>
    @if ($showErrors ?? true)
        @error('gateway_client_secret')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror @endif
</div>

<div>
    <label for="{{ $idSuffix }}_gateway_status_callback_url" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.gateway_status_callback_url') }}</label>
    <input type="url" id="{{ $idSuffix }}_gateway_status_callback_url" name="gateway_status_callback_url" maxlength="500"
           value="{{ old('gateway_status_callback_url', $gw->status_callback_url ?? '') }}"
           class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md"
           placeholder="https://example.com/webhooks/fasthub/sms-status/1">
    <p class="mt-1 text-xs text-gray-500">{{ __('system.settings.gateway_status_callback_url_desc') }}</p>
    @if ($showErrors ?? true)
        @error('gateway_status_callback_url')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror @endif
</div>

<div class="md:col-span-2">
    <label for="{{ $idSuffix }}_gateway_api_endpoint" class="block text-sm font-medium text-gray-700 mb-1">{{ __('system.settings.gateway_api_endpoint') }}</label>
    <input type="url" id="{{ $idSuffix }}_gateway_api_endpoint" name="gateway_api_endpoint" maxlength="500"
           value="{{ old('gateway_api_endpoint', $gw->api_endpoint ?? '') }}"
           class="focus:ring-blue-500 focus:border-blue-500 block w-full px-3 py-2 border border-gray-300 rounded-md"
           placeholder="{{ config('services.fasthub.endpoint') ?: 'https://...' }}">
    <p class="mt-1 text-xs text-gray-500">{{ __('system.settings.gateway_api_endpoint_desc') }}</p>
    @if ($showErrors ?? true)
        @error('gateway_api_endpoint')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror @endif
</div>
