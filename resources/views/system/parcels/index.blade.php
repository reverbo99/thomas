@extends('system.app')
@section('title', __('vender/parcels.admin_tracking'))
@section('content')
@php
    $fmtPct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    $commissionLabel = $fmtPct($commissionPercent);
    $levyLabel = $fmtPct($levyPercent);

    $fees = (float) ($breakdown['fees'] ?? 0);
    $shareOf = fn ($v) => $fees > 0 ? round(((float) $v / $fees) * 100, 1) : 0;

    $segments = [
        ['key' => 'admin', 'tone' => 'admin', 'label' => __('vender/parcels.admin_share')],
        ['key' => 'vendor', 'tone' => 'vendor', 'label' => __('vender/parcels.vendor_share')],
        ['key' => 'government', 'tone' => 'gov', 'label' => __('vender/parcels.government_levy_share')],
        ['key' => 'owner', 'tone' => 'owner', 'label' => __('vender/parcels.bus_owner_share')],
    ];

    $statusTones = [
        'awaiting_payment' => 'owner',
        'registered' => 'admin',
        'in_store' => 'cyan',
        'loaded' => 'vendor',
        'received' => 'info',
        'completed' => 'gov',
        'cancelled' => 'danger',
    ];
    $statusList = array_keys($statusTones);
    $activeStatus = $filters['status'] ?? '';
    $query = $filters['q'] ?? '';
    $countsTotal = (int) collect($statusCounts)->sum();
    $chipStatuses = collect($statusList)
        ->merge(collect($statusCounts)->keys()->filter()->diff($statusList))
        ->values();
@endphp

<style>
    .pcl {
        --pcl-surface: #ffffff;
        --pcl-surface-2: #f9fafb;
        --pcl-border: #e5e7eb;
        --pcl-text: #111827;
        --pcl-muted: #6b7280;
        --pcl-brand: #0d9488;
        --pcl-hero-from: #0f766e;
        --pcl-hero-to: #1d4ed8;
        --pcl-admin: #2563eb;
        --pcl-vendor: #7c3aed;
        --pcl-gov: #059669;
        --pcl-owner: #d97706;
        --pcl-info: #4f46e5;
        --pcl-cyan: #0891b2;
        --pcl-danger: #dc2626;
        --pcl-neutral: #6b7280;
        color: var(--pcl-text);
    }
    .dark .pcl {
        --pcl-surface: #111827;
        --pcl-surface-2: #1f2937;
        --pcl-border: #374151;
        --pcl-text: #f3f4f6;
        --pcl-muted: #9ca3af;
        --pcl-brand: #14b8a6;
        --pcl-hero-from: #115e59;
        --pcl-hero-to: #1e3a8a;
        --pcl-admin: #60a5fa;
        --pcl-vendor: #a78bfa;
        --pcl-gov: #34d399;
        --pcl-owner: #fbbf24;
        --pcl-info: #818cf8;
        --pcl-cyan: #22d3ee;
        --pcl-danger: #f87171;
        --pcl-neutral: #9ca3af;
    }
    @media (prefers-color-scheme: dark) {
        html:not(.light) .pcl {
            --pcl-surface: #111827;
            --pcl-surface-2: #1f2937;
            --pcl-border: #374151;
            --pcl-text: #f3f4f6;
            --pcl-muted: #9ca3af;
            --pcl-brand: #14b8a6;
            --pcl-hero-from: #115e59;
            --pcl-hero-to: #1e3a8a;
            --pcl-admin: #60a5fa;
            --pcl-vendor: #a78bfa;
            --pcl-gov: #34d399;
            --pcl-owner: #fbbf24;
            --pcl-info: #818cf8;
            --pcl-cyan: #22d3ee;
            --pcl-danger: #f87171;
            --pcl-neutral: #9ca3af;
        }
        html:not(.light) .pcl-page { background: #0b1220; }
    }
    .dark .pcl-page { background: #0b1220; }

    .pcl-tone-admin { --tone: var(--pcl-admin); }
    .pcl-tone-vendor { --tone: var(--pcl-vendor); }
    .pcl-tone-gov { --tone: var(--pcl-gov); }
    .pcl-tone-owner { --tone: var(--pcl-owner); }
    .pcl-tone-info { --tone: var(--pcl-info); }
    .pcl-tone-cyan { --tone: var(--pcl-cyan); }
    .pcl-tone-danger { --tone: var(--pcl-danger); }
    .pcl-tone-neutral { --tone: var(--pcl-neutral); }
    .pcl-tone-brand { --tone: var(--pcl-brand); }

    .pcl-muted { color: var(--pcl-muted); }
    .pcl-tone-text { color: var(--tone); }
    .pcl-card {
        background: var(--pcl-surface);
        border: 1px solid var(--pcl-border);
        border-radius: 1rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .05);
    }
    .pcl-kpi { border-top: 3px solid var(--tone); }
    .pcl-icon {
        color: var(--tone);
        background: color-mix(in srgb, var(--tone) 14%, transparent);
    }
    .pcl-hero {
        color: #ffffff;
        background: linear-gradient(135deg, var(--pcl-hero-from), var(--pcl-hero-to));
        border-radius: 1rem;
    }
    .pcl-hero-sub { color: rgba(255, 255, 255, .8); }
    .pcl-badge {
        color: var(--tone);
        background: color-mix(in srgb, var(--tone) 14%, transparent);
        border: 1px solid color-mix(in srgb, var(--tone) 30%, transparent);
    }
    .pcl-dot { background: var(--tone); }
    .pcl-bar { background: var(--pcl-surface-2); }
    .pcl-seg { background: var(--tone); }
    .pcl-chip {
        background: var(--pcl-surface);
        border: 1px solid var(--pcl-border);
        color: var(--pcl-text);
        transition: border-color .15s, background .15s;
    }
    .pcl-chip:hover { border-color: var(--tone, var(--pcl-brand)); }
    .pcl-chip-active {
        color: var(--tone, var(--pcl-brand));
        border-color: var(--tone, var(--pcl-brand));
        background: color-mix(in srgb, var(--tone, var(--pcl-brand)) 12%, var(--pcl-surface));
    }
    .pcl-chip-count {
        background: var(--pcl-surface-2);
        color: var(--pcl-muted);
    }
    .pcl-input {
        background: var(--pcl-surface);
        border: 1px solid var(--pcl-border);
        color: var(--pcl-text);
        border-radius: .5rem;
    }
    .pcl-input::placeholder { color: var(--pcl-muted); }
    .pcl-input:focus {
        outline: none;
        border-color: var(--pcl-brand);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--pcl-brand) 25%, transparent);
    }
    .pcl-btn-brand { background: var(--pcl-brand); color: #ffffff; }
    .pcl-btn-brand:hover { filter: brightness(1.08); }
    .pcl-btn-ghost {
        background: var(--pcl-surface-2);
        border: 1px solid var(--pcl-border);
        color: var(--pcl-text);
    }
    .pcl-btn-ghost:hover { border-color: var(--pcl-brand); }
    .pcl-thead { background: var(--pcl-surface-2); color: var(--pcl-muted); }
    .pcl-row { border-top: 1px solid var(--pcl-border); }
    .pcl-row:hover { background: var(--pcl-surface-2); }
    .pcl-divider { border-color: var(--pcl-border); }
    .pcl-formula {
        background: var(--pcl-surface-2);
        border: 1px dashed var(--pcl-border);
    }
</style>

<div class="pcl pcl-page rounded-2xl">
<div class="container mx-auto max-w-7xl px-4 py-6">

    {{-- Header --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('vender/parcels.admin_tracking') }}</h1>
            <p class="pcl-muted mt-1 text-sm">{{ __('vender/parcels.admin_tracking_subtitle') }}</p>
        </div>
        <a href="{{ route('system.parcels.manifest') }}"
           class="pcl-btn-brand inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            {{ __('vender/parcels.print_manifest') }}
        </a>
    </div>

    {{-- Money breakdown --}}
    <section class="mb-6">
        <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-lg font-semibold">{{ __('vender/parcels.money_breakdown') }}</h2>
            <p class="pcl-muted text-xs">{{ __('vender/parcels.breakdown_scope_hint') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="pcl-hero p-5 shadow-sm sm:col-span-2 xl:col-span-1">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wide">{{ __('vender/parcels.total_fees_paid') }}</p>
                    <svg class="h-6 w-6 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <p class="mt-3 text-2xl font-bold tabular-nums">{{ $currency }} {{ convert_money($fees) }}</p>
                <p class="pcl-hero-sub mt-1 text-xs">{{ trans_choice('vender/parcels.paid_parcels_count', $breakdown['paid_count'] ?? 0, ['count' => $breakdown['paid_count'] ?? 0]) }}</p>
            </div>

            @php
                $kpis = [
                    ['key' => 'admin', 'tone' => 'admin', 'label' => __('vender/parcels.admin_share'), 'hint' => __('vender/parcels.admin_commission_hint', ['percent' => $commissionLabel]),
                     'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                    ['key' => 'vendor', 'tone' => 'vendor', 'label' => __('vender/parcels.vendor_share'), 'hint' => __('vender/parcels.vendor_share_hint'),
                     'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                    ['key' => 'government', 'tone' => 'gov', 'label' => __('vender/parcels.government_levy_share'), 'hint' => __('vender/parcels.levy_hint', ['percent' => $levyLabel]),
                     'icon' => 'M3 21h18M5 21V10m4 11V10m6 11V10m4 11V10M12 3l9 5H3l9-5z'],
                    ['key' => 'owner', 'tone' => 'owner', 'label' => __('vender/parcels.bus_owner_share'), 'hint' => __('vender/parcels.owner_share_hint'),
                     'icon' => 'M8 7h8m-8 4h8m-6 8h4M6 3h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V5a2 2 0 012-2zM7 19v2m10-2v2'],
                ];
            @endphp
            @foreach($kpis as $kpi)
                <div class="pcl-card pcl-kpi pcl-tone-{{ $kpi['tone'] }} p-5">
                    <div class="flex items-center justify-between gap-2">
                        <p class="pcl-muted text-xs font-semibold uppercase tracking-wide">{{ $kpi['label'] }}</p>
                        <span class="pcl-icon inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $kpi['icon'] }}"/>
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-xl font-bold tabular-nums">{{ $currency }} {{ convert_money($breakdown[$kpi['key']] ?? 0) }}</p>
                    <div class="mt-1 flex items-center justify-between gap-2 text-xs">
                        <span class="pcl-muted">{{ $kpi['hint'] }}</span>
                        <span class="pcl-tone-text font-semibold tabular-nums">{{ $shareOf($breakdown[$kpi['key']] ?? 0) }}%</span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Proportion bar --}}
        <div class="pcl-card mt-4 p-5">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm font-semibold">{{ __('vender/parcels.share_of_fees') }}</p>
                <p class="pcl-muted text-xs tabular-nums">{{ $currency }} {{ convert_money($fees) }}</p>
            </div>
            <div class="pcl-bar flex h-3 w-full overflow-hidden rounded-full" role="img" aria-label="{{ __('vender/parcels.share_of_fees') }}">
                @foreach($segments as $seg)
                    @php $w = $shareOf($breakdown[$seg['key']] ?? 0); @endphp
                    @if($w > 0)
                        <div class="pcl-seg pcl-tone-{{ $seg['tone'] }} h-full" style="width: {{ $w }}%" title="{{ $seg['label'] }}: {{ $w }}%"></div>
                    @endif
                @endforeach
            </div>
            <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs">
                @foreach($segments as $seg)
                    <span class="pcl-tone-{{ $seg['tone'] }} inline-flex items-center gap-2">
                        <span class="pcl-dot inline-block h-2.5 w-2.5 rounded-full"></span>
                        <span>{{ $seg['label'] }}</span>
                        <span class="pcl-muted tabular-nums">{{ $shareOf($breakdown[$seg['key']] ?? 0) }}%</span>
                    </span>
                @endforeach
            </div>
            <p class="pcl-formula pcl-muted mt-4 rounded-lg px-3 py-2 text-xs">
                {{ __('vender/parcels.formula_line', ['commission' => $commissionLabel, 'levy' => $levyLabel]) }}
            </p>
        </div>
    </section>

    {{-- Status chips --}}
    <div class="mb-4">
        <p class="pcl-muted mb-2 text-xs font-semibold uppercase tracking-wide">{{ __('vender/parcels.filter_by_status') }}</p>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('system.parcels', array_filter(['q' => $query])) }}"
               class="pcl-chip pcl-tone-brand {{ $activeStatus === '' ? 'pcl-chip-active' : '' }} inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium">
                {{ __('vender/parcels.all') }}
                @if($activeStatus === '')
                    <span class="pcl-chip-count rounded-full px-2 text-xs tabular-nums">{{ $countsTotal }}</span>
                @endif
            </a>
            @foreach($chipStatuses as $st)
                @php
                    $isActive = $activeStatus === $st;
                    $count = $statusCounts[$st] ?? null;
                @endphp
                <a href="{{ route('system.parcels', array_filter(['status' => $st, 'q' => $query])) }}"
                   class="pcl-chip pcl-tone-{{ $statusTones[$st] ?? 'neutral' }} {{ $isActive ? 'pcl-chip-active' : '' }} inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-medium">
                    <span class="pcl-dot inline-block h-2 w-2 rounded-full"></span>
                    {{ $flow->statusLabel($st) }}
                    @if($activeStatus === '' || $isActive)
                        <span class="pcl-chip-count rounded-full px-2 text-xs tabular-nums">{{ (int) $count }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    {{-- Filter form --}}
    <form method="GET" action="{{ route('system.parcels') }}" class="pcl-card mb-4 flex flex-col gap-3 p-4 md:flex-row md:items-center">
        <div class="relative flex-1">
            <svg class="pcl-muted pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
            </svg>
            <input type="text" name="q" value="{{ $query }}" aria-label="{{ __('vender/parcels.search') }}"
                   class="pcl-input w-full py-2 pl-9 pr-3 text-sm" placeholder="{{ __('vender/parcels.search_placeholder') }}">
        </div>
        <select name="status" aria-label="{{ __('vender/parcels.status') }}" class="pcl-input px-3 py-2 text-sm md:w-56">
            <option value="">{{ __('vender/parcels.all_statuses') }}</option>
            @foreach($statusList as $st)
                <option value="{{ $st }}" @selected($activeStatus === $st)>{{ $flow->statusLabel($st) }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <button type="submit" class="pcl-btn-brand flex-1 rounded-lg px-4 py-2 text-sm font-semibold transition md:flex-none">
                {{ __('vender/parcels.filter') }}
            </button>
            @if($activeStatus !== '' || $query !== '')
                <a href="{{ route('system.parcels') }}" class="pcl-btn-ghost flex-1 rounded-lg px-4 py-2 text-center text-sm font-medium transition md:flex-none">
                    {{ __('vender/parcels.reset') }}
                </a>
            @endif
        </div>
    </form>

    {{-- Table --}}
    <div class="pcl-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="pcl-thead">
                    <tr class="text-xs font-semibold uppercase tracking-wide">
                        <th class="whitespace-nowrap px-4 py-3 text-left">{{ __('vender/parcels.tracking') }}</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left">{{ __('vender/parcels.company') }}</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left">{{ __('vender/parcels.route') }}</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left">{{ __('vender/parcels.bus') }}</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right">{{ __('vender/parcels.fee') }}</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right">{{ __('vender/parcels.admin_share') }}</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right">{{ __('vender/parcels.government_levy_share') }}</th>
                        <th class="whitespace-nowrap px-4 py-3 text-right">{{ __('vender/parcels.bus_owner_share') }}</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left">{{ __('vender/parcels.payment_details') }}</th>
                        <th class="whitespace-nowrap px-4 py-3 text-left">{{ __('vender/parcels.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($parcels as $p)
                        @php
                            $from = $p->bus->route->from ?? null;
                            $to = $p->bus->route->to ?? ($p->receiver_delivery_address ?? null);
                            $status = $flow->normalizeStatus($p);
                            $pay = $p->payment_status;
                            $showSplit = $pay === 'paid' && $status !== 'cancelled';
                            $split = $showSplit ? parcel_share_split($p) : null;
                            [$payTone, $payLabel] = match ($pay) {
                                'paid' => ['gov', __('vender/parcels.pay_paid')],
                                'pending' => ['owner', __('vender/parcels.pending')],
                                'unpaid' => ['neutral', __('vender/parcels.pay_unpaid')],
                                default => ['neutral', '—'],
                            };
                        @endphp
                        <tr class="pcl-row transition">
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="font-semibold tracking-wide">{{ $p->parcel_number }}</div>
                                <div class="pcl-muted text-xs">{{ optional($p->created_at)->format('d M Y, H:i') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="whitespace-nowrap">{{ $p->bus->campany->name ?? '—' }}</div>
                                @if($p->vender_id && $p->vender)
                                    <div class="pcl-muted whitespace-nowrap text-xs">{{ __('vender/parcels.sold_by_vendor', ['name' => $p->vender->name]) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                                    <span>{{ $from ?: '—' }}</span>
                                    <svg class="pcl-muted h-3.5 w-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                    <span>{{ $to ?: '—' }}</span>
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $p->bus->bus_number ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums">
                                <span class="pcl-muted text-xs font-normal">{{ $currency }}</span> {{ convert_money($p->amount_paid) }}
                            </td>
                            @foreach([['admin', 'admin'], ['government', 'gov'], ['owner', 'owner']] as [$splitKey, $tone])
                                <td class="pcl-tone-{{ $tone }} whitespace-nowrap px-4 py-3 text-right tabular-nums">
                                    @if($split)
                                        <span class="pcl-tone-text font-medium">{{ convert_money($split[$splitKey]) }}</span>
                                    @else
                                        <span class="pcl-muted">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="pcl-badge pcl-tone-{{ $payTone }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold">{{ $payLabel }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="pcl-badge pcl-tone-{{ $statusTones[$status] ?? 'neutral' }} inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold">
                                    <span class="pcl-dot inline-block h-1.5 w-1.5 rounded-full"></span>
                                    {{ $flow->statusLabel($status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-16 text-center">
                                <div class="pcl-tone-neutral mx-auto flex max-w-sm flex-col items-center">
                                    <span class="pcl-icon mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </span>
                                    <p class="font-semibold">{{ __('vender/parcels.no_parcels_found') }}</p>
                                    <p class="pcl-muted mt-1 text-sm">{{ __('vender/parcels.no_parcels_hint') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($parcels->total() > 0)
            <div class="pcl-divider flex flex-col gap-3 border-t px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="pcl-muted text-xs tabular-nums">
                    {{ __('vender/parcels.showing_range', ['from' => $parcels->firstItem(), 'to' => $parcels->lastItem(), 'total' => $parcels->total()]) }}
                </p>
                <div>{{ $parcels->links() }}</div>
            </div>
        @endif
    </div>

</div>
</div>
@endsection
