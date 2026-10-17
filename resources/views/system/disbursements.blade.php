@extends('system.app')

@section('title', __('system.disbursements.title'))

@section('content')
@php
    $badgeFor = function (?string $status) {
        return match ($status) {
            'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'failed', 'reversed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'needs_details' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
            'skipped' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'completed_with_errors' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
            'open' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
        };
    };
@endphp

<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- Header --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700">
                    <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 tracking-tight">{{ __('system.disbursements.title') }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ __('system.disbursements.subtitle') }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('system.disbursements.collect') }}">
                    @csrf
                    <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">
                    <button type="submit" class="inline-flex items-center px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm shadow-sm">
                        {{ __('system.disbursements.collect_now') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('system.disbursements.run') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm shadow-sm">
                        {{ __('system.disbursements.run_now') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('system.disbursements.sync') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition text-sm shadow-sm">
                        {{ __('system.disbursements.sync_now') }}
                    </button>
                </form>
                <a href="{{ route('system.disbursements.csv', ['date' => $selectedDate->toDateString()]) }}"
                   class="inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition text-sm shadow-sm">
                    {{ __('system.disbursements.export_csv') }}
                </a>
            </div>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="mb-5 rounded-xl bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 px-4 py-3 text-sm font-medium text-green-800 dark:text-green-100" role="alert">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-5 rounded-xl bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 px-4 py-3 text-sm font-medium text-red-800 dark:text-red-100" role="alert">
                {{ session('error') }}
            </div>
        @endif
        @if (!empty($setupError))
            <div class="mb-5 rounded-xl bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 px-4 py-3 text-sm text-red-800 dark:text-red-100" role="alert">
                <p class="font-semibold">{{ __('system.disbursements.no_batches') }}</p>
                <p class="mt-1 font-mono text-xs break-all">{{ $setupError }}</p>
            </div>
        @endif

        {{-- Gateway / mode --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('system.disbursements.auto_settings') }}</p>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                        {{ $enabled ? __('system.disbursements.auto_enabled') : __('system.disbursements.status_open') }}
                    </span>
                </div>
                <form method="POST" action="{{ route('system.disbursements.settings') }}" class="mt-4 space-y-3">
                    @csrf
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input type="checkbox" name="disbursement_enabled" value="1" {{ $enabled ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                        {{ __('system.disbursements.auto_enabled') }}
                    </label>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('system.disbursements.auto_time') }}</label>
                        <input type="time" name="disbursement_run_time" value="{{ $runTime }}"
                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                    </div>
                    <label class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-200">
                        <input type="checkbox" name="disbursement_live" value="1" {{ $live ? 'checked' : '' }} class="mt-0.5 rounded border-gray-300 text-red-600">
                        <span>
                            {{ __('system.disbursements.live_mode') }}
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('system.disbursements.live_mode_hint') }}</span>
                        </span>
                    </label>
                    <button type="submit" class="w-full px-3 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 text-sm">
                        {{ __('system.disbursements.save') }}
                    </button>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">ClickPesa</p>
                <p class="mt-3 text-sm font-semibold {{ $gatewayConfigured ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                    {{ $gatewayConfigured ? __('system.disbursements.gateway_configured') : __('system.disbursements.gateway_missing') }}
                </p>
                @if (!$gatewayConfigured)
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('system.disbursements.gateway_missing_hint') }}</p>
                @endif
                <p class="mt-3 text-sm font-semibold {{ $dryRun ? 'text-amber-700 dark:text-amber-300' : 'text-green-700 dark:text-green-300' }}">
                    {{ $dryRun ? __('system.disbursements.dry_run') : __('system.disbursements.live_mode') }}
                </p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('system.disbursements.dry_run_hint') }}</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('system.disbursements.payout_rate') }}</p>
                <p class="mt-3 text-sm text-gray-700 dark:text-gray-200">{{ __('system.disbursements.rate_limit_hint') }}</p>
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('system.disbursements.last_run') }}:
                    <span class="font-semibold">{{ $lastRunAt ? $lastRunAt->format('d M Y H:i') : '—' }}</span>
                </p>
                @if ($batch && in_array($batch->status, ['open', 'processing'], true))
                    <p class="mt-3 text-xs text-blue-600 dark:text-blue-300">{{ __('system.disbursements.auto_refresh_hint') }}</p>
                @endif
            </div>
        </div>

        {{-- Date tabs --}}
        <div class="mb-6">
            <div class="flex gap-2 overflow-x-auto pb-2">
                @forelse ($dates as $day)
                    @php $isActive = $batch && $day->batch_date->isSameDay($batch->batch_date); @endphp
                    <a href="{{ route('system.disbursements', ['date' => $day->batch_date->toDateString()]) }}"
                       class="min-w-[9rem] rounded-xl border px-3 py-2 text-left transition shadow-sm
                              {{ $isActive ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900 dark:border-indigo-400' : 'border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 hover:border-indigo-300' }}">
                        <span class="block text-sm font-bold text-gray-800 dark:text-gray-100">
                            {{ $day->batch_date->isToday() ? __('system.disbursements.tab_today') : ($day->batch_date->isYesterday() ? __('system.disbursements.tab_yesterday') : $day->batch_date->format('d M')) }}
                        </span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $day->batch_date->format('Y') }} · {{ $day->total_items }} items</span>
                        <span class="block text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $day->currency }} {{ number_format((float) $day->total_amount, 0) }}</span>
                    </a>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('system.disbursements.no_batches') }}</div>
                @endforelse
            </div>
        </div>

        @if (! $batch)
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-10 text-center">
                <p class="text-lg font-semibold text-gray-700 dark:text-gray-200">{{ __('system.disbursements.no_batches') }}</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('system.disbursements.no_batches_desc') }}</p>
            </div>
        @else
            {{-- Batch summary --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 mb-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('system.disbursements.batch_reference') }}</p>
                        <p class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ $batch->reference }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $batch->batch_date?->format('d M Y') }} ·
                            <span class="font-semibold">{{ $batch->currency }}</span>
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $badgeFor($batch->status) }}">
                        {{ $batch->statusLabel() }}
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mt-5">
                    @foreach ([
                        ['label' => __('system.disbursements.summary_items'), 'value' => $batch->total_items, 'color' => 'text-gray-800 dark:text-gray-100'],
                        ['label' => __('system.disbursements.summary_total'), 'value' => $batch->currency . ' ' . number_format((float) $batch->total_amount, 0), 'color' => 'text-indigo-700 dark:text-indigo-300'],
                        ['label' => __('system.disbursements.summary_paid'), 'value' => $batch->paid_items . ' / ' . $batch->currency . ' ' . number_format((float) $batch->paid_amount, 0), 'color' => 'text-green-700 dark:text-green-300'],
                        ['label' => __('system.disbursements.summary_pending'), 'value' => max(0, $batch->total_items - $batch->paid_items - $batch->failed_items - $batch->needs_details_items), 'color' => 'text-yellow-700 dark:text-yellow-300'],
                        ['label' => __('system.disbursements.summary_failed'), 'value' => $batch->failed_items, 'color' => 'text-red-700 dark:text-red-300'],
                        ['label' => __('system.disbursements.summary_needs_details'), 'value' => $batch->needs_details_items, 'color' => 'text-amber-700 dark:text-amber-300'],
                    ] as $card)
                        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                            <p class="mt-1 text-base font-bold {{ $card['color'] }}">{{ $card['value'] }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('system.disbursements.collected_at') }}: {{ $batch->collected_at?->format('d M Y H:i') ?? '—' }}</span>
                    <span>{{ __('system.disbursements.started_at') }}: {{ $batch->started_at?->format('d M Y H:i') ?? '—' }}</span>
                    <span>{{ __('system.disbursements.completed_at') }}: {{ $batch->completed_at?->format('d M Y H:i') ?? '—' }}</span>
                </div>
            </div>

            {{-- Items --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3">
                    <h2 class="text-base font-bold text-gray-800 dark:text-gray-100">{{ __('system.disbursements.summary_items') }}</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('system.disbursements.items_count', ['count' => $batch->total_items]) }}</span>
                </div>

                @if (! $items || $items->count() === 0)
                    <div class="p-10 text-center">
                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('system.disbursements.no_items') }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('system.disbursements.no_items_desc') }}</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr class="text-left text-[11px] uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-4 py-3">{{ __('system.disbursements.table_reference') }}</th>
                                    <th class="px-4 py-3">{{ __('system.disbursements.table_source') }}</th>
                                    <th class="px-4 py-3">{{ __('system.disbursements.table_beneficiary') }}</th>
                                    <th class="px-4 py-3">{{ __('system.disbursements.table_channel') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('system.disbursements.table_amount') }}</th>
                                    <th class="px-4 py-3">{{ __('system.disbursements.table_status') }}</th>
                                    <th class="px-4 py-3">{{ __('system.disbursements.table_gateway') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('system.disbursements.table_actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ($items as $item)
                                    <tr class="{{ $item->status === 'needs_details' ? 'bg-amber-50 dark:bg-amber-900/20' : '' }}">
                                        <td class="px-4 py-3 align-top">
                                            <p class="font-mono text-xs font-semibold text-gray-800 dark:text-gray-100">{{ $item->reference }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->booking_code ?: $item->source_reference }}</p>
                                        </td>
                                        <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">{{ $item->sourceLabel() }}</td>
                                        <td class="px-4 py-3 align-top">
                                            <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $item->beneficiary_name ?: __('system.disbursements.no_beneficiary') }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $item->channelLabel() }} · {{ $item->payout_number ?: '—' }}
                                                @if ($item->bank_bic)
                                                    · {{ $item->bank_bic }}
                                                @endif
                                            </p>
                                        </td>
                                        <td class="px-4 py-3 align-top text-gray-700 dark:text-gray-200">{{ $item->channelLabel() }}</td>
                                        <td class="px-4 py-3 align-top text-right font-semibold text-gray-800 dark:text-gray-100">
                                            {{ $item->currency }} {{ number_format((float) $item->amount, 2) }}
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $badgeFor($item->status) }}">
                                                {{ $item->statusLabel() }}
                                            </span>
                                            @if ($item->last_error)
                                                <p class="mt-1 max-w-[16rem] text-[11px] text-red-600 dark:text-red-300 break-words">{{ $item->last_error }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $item->gateway_status ?: '—' }}</p>
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('system.disbursements.table_attempts') }}: {{ $item->attempts }}</p>
                                        </td>
                                        <td class="px-4 py-3 align-top text-right">
                                            <div class="flex flex-wrap justify-end gap-1">
                                                @if ($item->isRetryable() && $item->status !== 'paid')
                                                    <form method="POST" action="{{ route('system.disbursements.items.retry', $item) }}">
                                                        @csrf
                                                        <button type="submit" class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-800 hover:bg-yellow-200">
                                                            {{ __('system.disbursements.retry') }}
                                                        </button>
                                                    </form>
                                                @endif
                                                <button type="button" onclick="openItemModal({{ $item->id }})"
                                                        class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-100">
                                                    {{ __('system.disbursements.edit') }}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $items->links() }}
                    </div>

                    {{-- One modal per item --}}
                    @foreach ($items as $item)
                        <div id="item-modal-{{ $item->id }}" class="fixed inset-0 z-50 hidden">
                            <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeItemModal({{ $item->id }})"></div>
                            <div class="relative mx-auto my-10 w-full max-w-2xl px-4">
                                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700">
                                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ __('system.disbursements.payout_details') }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->sourceLabel() }} · {{ $item->reference }}</p>
                                        </div>
                                        <button type="button" onclick="closeItemModal({{ $item->id }})" class="text-gray-400 hover:text-gray-600">✕</button>
                                    </div>

                                    <div class="p-5 space-y-5 max-h-[75vh] overflow-y-auto">
                                        <form method="POST" action="{{ route('system.disbursements.items.update', $item) }}" class="space-y-3">
                                            @csrf
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('system.disbursements.beneficiary_name') }}</label>
                                                    <input type="text" name="beneficiary_name" value="{{ $item->beneficiary_name }}"
                                                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('system.disbursements.payout_channel') }}</label>
                                                    <select name="payout_channel" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                                        <option value="mobile_money" {{ $item->payout_channel === 'mobile_money' ? 'selected' : '' }}>{{ __('system.disbursements.channel_mobile') }}</option>
                                                        <option value="bank" {{ $item->payout_channel === 'bank' ? 'selected' : '' }}>{{ __('system.disbursements.channel_bank') }}</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('system.disbursements.payout_number') }}</label>
                                                    <input type="text" name="payout_number" value="{{ $item->payout_number }}" required
                                                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('system.disbursements.bank_name') }}</label>
                                                    <input type="text" name="bank_name" value="{{ $item->bank_name }}"
                                                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('system.disbursements.bank_bic') }}</label>
                                                    <input type="text" name="bank_bic" value="{{ $item->bank_bic }}"
                                                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                                </div>
                                            </div>
                                            <button type="submit" class="px-3 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
                                                {{ __('system.disbursements.save') }}
                                            </button>
                                        </form>

                                        <hr class="border-gray-200 dark:border-gray-700">

                                        <form method="POST" action="{{ route('system.disbursements.items.mark_paid', $item) }}" class="flex flex-wrap items-end gap-2" data-confirm="{{ __('system.disbursements.mark_paid') }}">
                                            @csrf
                                            <div class="flex-1 min-w-[12rem]">
                                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('system.disbursements.manual_reference') }}</label>
                                                <input type="text" name="manual_reference"
                                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                            </div>
                                            <button type="submit" class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                                {{ __('system.disbursements.mark_paid') }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('system.disbursements.items.skip', $item) }}" class="flex flex-wrap items-end gap-2">
                                            @csrf
                                            <div class="flex-1 min-w-[12rem]">
                                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">{{ __('system.disbursements.skip_reason') }}</label>
                                                <input type="text" name="reason"
                                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                                            </div>
                                            <button type="submit" class="px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-50 text-sm">
                                                {{ __('system.disbursements.skip') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        @endif
    </div>
</div>

<script>
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    function openItemModal(id) {
        const modal = document.getElementById('item-modal-' + id);
        if (modal) {
            modal.classList.remove('hidden');
            modal.setAttribute('data-modal-open', '1');
        }
    }

    function closeItemModal(id) {
        const modal = document.getElementById('item-modal-' + id);
        if (modal) {
            modal.classList.add('hidden');
            modal.removeAttribute('data-modal-open');
        }
    }

    @if ($batch && in_array($batch->status, ['open', 'processing'], true))
    setTimeout(function () {
        if (!document.querySelector('[data-modal-open="1"]')) {
            window.location.reload();
        }
    }, 60000);
    @endif
</script>
@endsection
