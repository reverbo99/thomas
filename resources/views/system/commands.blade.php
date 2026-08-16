@extends('system.app')

@section('title', __('system.sidebar.command'))

@section('content')
<div class="max-w-6xl mx-auto space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('system.commands.title') }}</h1>
            <p class="text-slate-600 mt-1 text-sm">{!! __('system.commands.subtitle', ['cmd' => '<code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded text-indigo-800">php artisan</code>']) !!}</p>
        </div>
        <a href="{{ route('system.index') }}" class="inline-flex items-center justify-center text-sm font-medium text-indigo-700 hover:text-indigo-900 border border-indigo-200 rounded-lg px-4 py-2 bg-white shadow-sm hover:bg-indigo-50 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            &larr; {{ __('system.commands.dashboard_link') }}
        </a>
    </div>

    @php
        $ok = session('command_ok');
        $err = session('command_err');
        $log = session('log_output');
        $configCommands = [
            ['config_cache', 'system.commands.cmd_config_cache', 'config:cache'],
            ['config_clear', 'system.commands.cmd_config_clear', 'config:clear'],
            ['route_cache', 'system.commands.cmd_route_cache', 'route:cache'],
            ['route_clear', 'system.commands.cmd_route_clear', 'route:clear'],
            ['view_cache', 'system.commands.cmd_view_cache', 'view:cache'],
            ['view_clear', 'system.commands.cmd_view_clear', 'view:clear'],
            ['cache_clear', 'system.commands.cmd_cache_clear', 'cache:clear'],
            ['event_cache', 'system.commands.cmd_event_cache', 'event:cache'],
            ['event_clear', 'system.commands.cmd_event_clear', 'event:clear'],
        ];
        $optimizeCommands = [
            ['optimize', 'system.commands.cmd_optimize', 'optimize'],
            ['optimize_clear', 'system.commands.cmd_optimize_clear', 'optimize:clear'],
            ['migrate_status', 'system.commands.cmd_migrate_status', 'migrate:status'],
        ];
    @endphp

    @if($ok)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/90 p-4 shadow-sm" role="status">
            <p class="text-sm font-semibold text-emerald-900">{{ __('system.commands.completed', ['code' => $ok['exit_code']]) }}</p>
            <pre class="mt-2 text-xs text-emerald-950 bg-white/80 border border-emerald-100 rounded-lg p-3 overflow-x-auto whitespace-pre-wrap font-mono">{{ $ok['output'] ?: '—' }}</pre>
        </div>
    @endif
    @if($err)
        <div class="rounded-xl border border-red-200 bg-red-50/90 p-4 shadow-sm" role="alert">
            <p class="text-sm font-semibold text-red-900">{{ __('system.commands.failed', ['code' => $err['exit_code']]) }}</p>
            <pre class="mt-2 text-xs text-red-950 bg-white/80 border border-red-100 rounded-lg p-3 overflow-x-auto whitespace-pre-wrap font-mono">{{ $err['output'] ?: '—' }}</pre>
        </div>
    @endif
    @if($log)
        <div class="rounded-xl border {{ $log['error'] ? 'border-amber-200 bg-amber-50/90' : 'border-slate-200 bg-slate-50/90' }} p-4 shadow-sm" role="status">
            <p class="text-sm font-semibold {{ $log['error'] ? 'text-amber-900' : 'text-slate-900' }}">{{ __('system.commands.laravel_log') }}</p>
            <pre class="mt-2 text-xs {{ $log['error'] ? 'text-amber-950 bg-white/80 border-amber-100' : 'text-slate-950 bg-white/80 border-slate-100' }} border rounded-lg p-3 overflow-x-auto whitespace-pre-wrap font-mono max-h-[32rem] overflow-y-auto">{{ $log['output'] ?: __('system.commands.log_empty') }}</pre>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/80">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('system.commands.config_cache_section') }}</h2>
                <p class="text-xs text-slate-600 mt-0.5">{{ __('system.commands.config_cache_desc') }}</p>
            </div>
            <div class="p-5 grid sm:grid-cols-2 gap-3">
                @foreach ($configCommands as [$key, $labelKey, $cmd])
                    <form action="{{ route('system.commands.run') }}" method="post" class="contents">
                        @csrf
                        <input type="hidden" name="action" value="{{ $key }}">
                        <button type="submit" class="w-full text-left rounded-lg border border-slate-200 px-3 py-3 hover:border-indigo-300 hover:bg-indigo-50/60 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                            <span class="block text-sm font-medium text-slate-900">{{ __($labelKey) }}</span>
                            <span class="block text-xs text-slate-500 mt-0.5 font-mono">{{ $cmd }}</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/80">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('system.commands.optimize_section') }}</h2>
                <p class="text-xs text-slate-600 mt-0.5">{{ __('system.commands.optimize_desc') }}</p>
            </div>
            <div class="p-5 grid sm:grid-cols-2 gap-3">
                @foreach ($optimizeCommands as [$key, $labelKey, $cmd])
                    <form action="{{ route('system.commands.run') }}" method="post" class="contents">
                        @csrf
                        <input type="hidden" name="action" value="{{ $key }}">
                        <button type="submit" class="w-full text-left rounded-lg border border-slate-200 px-3 py-3 hover:border-indigo-300 hover:bg-indigo-50/60 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1">
                            <span class="block text-sm font-medium text-slate-900">{{ __($labelKey) }}</span>
                            <span class="block text-xs text-slate-500 mt-0.5 font-mono">{{ $cmd }}</span>
                        </button>
                    </form>
                @endforeach
            </div>
            <div class="px-5 pb-5">
                <form action="{{ route('system.commands.run') }}" method="post" class="flex flex-wrap items-center gap-3">
                    @csrf
                    <input type="hidden" name="action" value="migrate_all">
                    <button type="submit" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        {{ __('system.commands.run_pending_migrations') }}
                    </button>
                    <span class="text-xs text-slate-500">{!! __('system.commands.migrate_force_hint', ['cmd' => '<code class="bg-slate-100 px-1 rounded">migrate --force</code>']) !!}</span>
                </form>
            </div>
        </section>
    </div>

    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/80">
            <h2 class="text-lg font-semibold text-slate-900">{{ __('system.commands.app_log_section') }}</h2>
            <p class="text-xs text-slate-600 mt-0.5">{!! __('system.commands.app_log_desc', ['path' => '<code class="bg-slate-100 px-1 rounded">storage/logs/laravel.log</code>']) !!}</p>
        </div>
        <div class="p-5">
            <form action="{{ route('system.commands.log') }}" method="post">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2.5 rounded-lg bg-slate-800 text-white text-sm font-medium shadow-sm hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    {{ __('system.commands.read_laravel_log') }}
                </button>
            </form>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ __('system.commands.migrations_section') }}</h2>
                <p class="text-xs text-slate-600 mt-0.5">{!! __('system.commands.migrations_desc', ['path' => '<code class="bg-slate-100 px-1 rounded">--path</code>']) !!}</p>
            </div>
        </div>
        <div class="p-5 space-y-4">
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ __('system.commands.migrate_status') }}</h3>
                @if($migrateStatusExit !== 0)
                    <p class="text-sm text-amber-800 mb-2">{{ __('system.commands.migrate_status_error', ['code' => $migrateStatusExit]) }}</p>
                @endif
                <pre class="text-xs bg-slate-900 text-slate-100 rounded-lg p-4 overflow-x-auto max-h-56 overflow-y-auto font-mono whitespace-pre-wrap">{{ $migrateStatusOutput ?: '—' }}</pre>
            </div>
            <div>
                <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">{{ __('system.commands.migration_files') }}</h3>
                <div class="border border-slate-200 rounded-lg overflow-hidden max-h-96 overflow-y-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-100 text-slate-700 sticky top-0">
                            <tr>
                                <th class="text-left font-semibold px-4 py-2">{{ __('system.commands.file') }}</th>
                                <th class="text-right font-semibold px-4 py-2 w-40">{{ __('system.commands.action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($migrationFiles as $file)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-4 py-2 font-mono text-xs text-slate-800 break-all">{{ $file }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <form action="{{ route('system.commands.run') }}" method="post" class="inline">
                                            @csrf
                                            <input type="hidden" name="action" value="migrate_file">
                                            <input type="hidden" name="migration" value="{{ $file }}">
                                            <button type="submit" class="text-xs font-medium text-indigo-700 hover:text-indigo-900 px-2 py-1 rounded-md hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                {{ __('system.commands.migrate') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-6 text-center text-slate-500">{{ __('system.commands.no_migration_files') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-red-300 dark:border-red-800 bg-red-50/80 dark:bg-red-950/30 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-red-200 dark:border-red-900/60 bg-red-100/70 dark:bg-red-950/50">
            <h2 class="text-lg font-semibold text-red-900 dark:text-red-100">{{ __('system.commands.purge_section') }}</h2>
            <p class="text-xs text-red-800 dark:text-red-300 mt-0.5">{{ __('system.commands.purge_desc') }}</p>
        </div>
        <div class="p-5 space-y-4">
            <div class="grid gap-4 md:grid-cols-3 text-sm">
                <div class="rounded-lg border border-red-200 dark:border-red-900/50 bg-white/80 dark:bg-slate-900/50 p-4">
                    <h3 class="font-semibold text-red-900 dark:text-red-100 mb-2">{{ __('system.commands.purge_will_delete') }}</h3>
                    <ul class="list-disc list-inside space-y-1 text-red-800 dark:text-red-200 text-xs">
                        @foreach (explode('|', __('system.commands.purge_delete_items')) as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="rounded-lg border border-emerald-200 dark:border-emerald-900/50 bg-white/80 dark:bg-slate-900/50 p-4">
                    <h3 class="font-semibold text-emerald-900 dark:text-emerald-100 mb-2">{{ __('system.commands.purge_will_preserve') }}</h3>
                    <ul class="list-disc list-inside space-y-1 text-emerald-800 dark:text-emerald-200 text-xs">
                        @foreach (explode('|', __('system.commands.purge_preserve_items')) as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="rounded-lg border border-amber-200 dark:border-amber-900/50 bg-white/80 dark:bg-slate-900/50 p-4">
                    <h3 class="font-semibold text-amber-900 dark:text-amber-100 mb-2">{{ __('system.commands.purge_will_reset') }}</h3>
                    <ul class="list-disc list-inside space-y-1 text-amber-800 dark:text-amber-200 text-xs">
                        @foreach (explode('|', __('system.commands.purge_reset_items')) as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <form
                id="purge-transactional-form"
                action="{{ route('system.commands.run') }}"
                method="post"
                class="space-y-3"
                onsubmit="return confirm(@json(__('system.commands.purge_js_confirm')))"
            >
                @csrf
                <input type="hidden" name="action" value="purge_transactional_data">
                <label for="purge-confirm-phrase" class="block text-sm font-medium text-red-900 dark:text-red-100">
                    {{ __('system.commands.purge_confirm_label', ['phrase' => \App\Http\Controllers\ArtisanCommandsController::PURGE_CONFIRM_PHRASE]) }}
                </label>
                <input
                    type="text"
                    id="purge-confirm-phrase"
                    name="confirm_phrase"
                    autocomplete="off"
                    placeholder="{{ __('system.commands.purge_confirm_placeholder') }}"
                    class="w-full rounded-lg border border-red-300 dark:border-red-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
                >
                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="submit"
                        id="purge-submit-btn"
                        disabled
                        class="inline-flex items-center px-4 py-2.5 rounded-lg bg-red-600 text-white text-sm font-medium shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed dark:focus:ring-offset-slate-900"
                    >
                        {{ __('system.commands.purge_submit') }}
                    </button>
                    <span class="text-xs text-red-700 dark:text-red-300 font-mono">{{ __('system.commands.purge_cmd') }}</span>
                </div>
            </form>
        </div>
    </section>
</div>

<script>
(function () {
    var phrase = @json(\App\Http\Controllers\ArtisanCommandsController::PURGE_CONFIRM_PHRASE);
    var input = document.getElementById('purge-confirm-phrase');
    var button = document.getElementById('purge-submit-btn');
    if (!input || !button) return;
    function sync() {
        button.disabled = input.value !== phrase;
    }
    input.addEventListener('input', sync);
    sync();
})();
</script>
@endsection
