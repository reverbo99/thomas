<?php

namespace App\Http\Controllers;

use App\Console\Commands\PurgeTransactionalDataCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Output\BufferedOutput;

class ArtisanCommandsController extends Controller
{
    public const PURGE_CONFIRM_PHRASE = PurgeTransactionalDataCommand::CONFIRM_PHRASE;
    /**
     * Whitelisted artisan actions (name => [command, default options]).
     * Options are merged with request-safe extras in run().
     *
     * @var array<string, array{0: string, 1: array<string, mixed>}>
     */
    private const ALLOWED = [
        'config_cache' => ['config:cache', []],
        'config_clear' => ['config:clear', []],
        'route_cache' => ['route:cache', []],
        'route_clear' => ['route:clear', []],
        'view_cache' => ['view:cache', []],
        'view_clear' => ['view:clear', []],
        'cache_clear' => ['cache:clear', []],
        'event_cache' => ['event:cache', []],
        'event_clear' => ['event:clear', []],
        'optimize' => ['optimize', []],
        'optimize_clear' => ['optimize:clear', []],
        'migrate_status' => ['migrate:status', ['--no-ansi' => true]],
        'migrate_all' => ['migrate', ['--force' => true]],
        'purge_transactional_data' => ['data:purge-transactional', ['--force' => true]],
    ];

    public function index()
    {
        $migrationFiles = collect(File::files(database_path('migrations')))
            ->map(fn (\SplFileInfo $f) => $f->getFilename())
            ->filter(fn (string $name) => str_ends_with($name, '.php'))
            ->sort()
            ->values()
            ->all();

        $statusOutput = $this->callArtisan('migrate:status', ['--no-ansi' => true]);

        return view('system.commands', [
            'migrationFiles' => $migrationFiles,
            'migrateStatusOutput' => $statusOutput['output'],
            'migrateStatusExit' => $statusOutput['exit_code'],
        ]);
    }

    public function run(Request $request)
    {
        $request->validate([
            'action' => ['required', 'string', 'in:'.implode(',', array_merge(array_keys(self::ALLOWED), ['migrate_file']))],
            'migration' => ['nullable', 'string', 'max:255'],
            'confirm_phrase' => ['nullable', 'string', 'max:255'],
        ]);

        $action = $request->input('action');

        if ($action === 'purge_transactional_data') {
            if ($request->input('confirm_phrase') !== self::PURGE_CONFIRM_PHRASE) {
                return $this->respond(
                    $request,
                    false,
                    1,
                    __('system.commands.purge_confirm_mismatch')
                );
            }
        }

        if ($action === 'migrate_file') {
            $filename = $request->input('migration', '');
            $filename = preg_replace('/^migration--/i', '', $filename);
            if (!is_string($filename) || !preg_match('/^[a-z0-9_]+\.php$/i', $filename)) {
                return $this->respond($request, false, 1, 'Invalid migration filename.');
            }
            $fullPath = database_path('migrations/'.$filename);
            if (!is_file($fullPath)) {
                return $this->respond($request, false, 1, 'Migration file not found: '.$filename);
            }
            $result = $this->callArtisan('migrate', [
                '--force' => true,
                '--path' => 'database/migrations/'.$filename,
            ]);

            return $this->respond($request, $result['exit_code'] === 0, $result['exit_code'], $result['output']);
        }

        [$command, $options] = self::ALLOWED[$action];
        $result = $this->callArtisan($command, $options);

        return $this->respond($request, $result['exit_code'] === 0, $result['exit_code'], $result['output']);
    }

    public function readLog(Request $request)
    {
        $path = storage_path('logs/laravel.log');

        if (! is_file($path)) {
            return redirect()
                ->route('system.commands')
                ->with('log_output', [
                    'error' => true,
                    'output' => 'Log file not found at storage/logs/laravel.log',
                ]);
        }

        return redirect()
            ->route('system.commands')
            ->with('log_output', [
                'error' => false,
                'output' => $this->tailLogFile($path),
            ]);
    }

    private function tailLogFile(string $path, int $maxLines = 500, int $maxBytes = 524288): string
    {
        $size = filesize($path);
        if ($size === false || $size === 0) {
            return '';
        }

        $readBytes = (int) min($size, $maxBytes);
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return '';
        }

        if ($readBytes < $size) {
            fseek($handle, -$readBytes, SEEK_END);
        }

        $chunk = fread($handle, $readBytes);
        fclose($handle);

        if ($chunk === false || $chunk === '') {
            return '';
        }

        $lines = explode("\n", $chunk);
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, -$maxLines);
            array_unshift($lines, '… (truncated — showing last '.$maxLines.' lines) …');
        } elseif ($readBytes < $size) {
            array_unshift($lines, '… (truncated — showing last '.number_format($readBytes).' bytes) …');
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{exit_code: int, output: string}
     */
    private function callArtisan(string $command, array $params): array
    {
        $buffer = new BufferedOutput;
        $exitCode = Artisan::call($command, $params, $buffer);
        $output = trim($buffer->fetch());

        return [
            'exit_code' => $exitCode,
            'output' => $output !== '' ? $output : trim(Artisan::output()),
        ];
    }

    private function respond(Request $request, bool $success, int $exitCode, string $output)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => $success,
                'exit_code' => $exitCode,
                'output' => $output,
            ], $success ? 200 : 422);
        }

        return redirect()
            ->route('system.commands')
            ->with($success ? 'command_ok' : 'command_err', [
                'exit_code' => $exitCode,
                'output' => $output,
            ]);
    }
}
