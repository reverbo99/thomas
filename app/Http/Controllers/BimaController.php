<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Access;
use App\Models\Bima;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BimaController extends Controller
{
    const price = 100;
    const foreign = 200;

    private function requireAccess(string $link): void
    {
        $user = Auth::user();
        abort_unless($user && $user->isActive() && $user->hasAccess($link), 403);
    }

    public function index()
    {
        $this->requireAccess(Access::LINKS['INSURANCE']);
        $bimas = $this->loadBimaRecords();

        return view('system.bima', compact('bimas'));
    }

    public function reportPdf(Request $request)
    {
        $this->requireAccess(Access::LINKS['INSURANCE']);

        $payload = $this->buildBimaExportPayload($request);
        if ($payload['rows']->isEmpty()) {
            return redirect()->back()->with('error', __('system.pages.no_data_found'));
        }

        $pdf = Pdf::loadView('print.bima', $payload['pdfData']);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('insurance_' . now()->format('Ymd_His') . '.pdf');
    }

    public function reportCsv(Request $request)
    {
        $this->requireAccess(Access::LINKS['INSURANCE']);

        $payload = $this->buildBimaExportPayload($request);
        if ($payload['rows']->isEmpty()) {
            return redirect()->back()->with('error', __('system.pages.no_data_found'));
        }

        return $this->streamBimaCsv(
            'insurance_' . now()->format('Ymd_His') . '.csv',
            $payload['headers'],
            $payload['rows']
        );
    }

    public function getData()
    {
        $this->requireAccess(Access::LINKS['INSURANCE']);
        $bimas = Bima::with('booking')->get();

        return response()->json([
            'data' => $bimas
        ]);
    }

    private function loadBimaRecords()
    {
        return Bima::with(['booking.campany', 'booking.bus', 'booking.route'])
            ->orderByDesc('start_date')
            ->get()
            ->map(function ($item) {
                $item->valid_days = $this->resolveValidDays($item->end_date);

                return $item;
            });
    }

    private function buildBimaExportPayload(Request $request): array
    {
        $query = Bima::with(['booking.campany', 'booking.bus', 'booking.route'])
            ->orderByDesc('start_date');
        $this->applyBimaDateFilter($query, $request);
        $dateFilter = $this->resolveBimaDateFilter($request);

        $bimas = $query->get();
        $rows = $bimas->values()->map(function ($bima, $index) {
            return $this->mapBimaRow($bima, $index + 1);
        });

        $totalAmount = (float) $bimas->sum('amount');
        $totalVat = (float) $bimas->sum('bima_vat');

        return [
            'headers' => array_keys($rows->first() ?? $this->emptyBimaRow()),
            'rows' => $rows,
            'pdfData' => [
                'title' => __('system.pages.bima_title'),
                'period' => $dateFilter['period'],
                'startDate' => $dateFilter['startDate'],
                'endDate' => $dateFilter['endDate'],
                'rows' => $rows->values()->all(),
                'totals' => [
                    'count' => $bimas->count(),
                    'totalAmount' => $totalAmount,
                    'totalVat' => $totalVat,
                    'grandTotal' => $totalAmount + $totalVat,
                ],
            ],
        ];
    }

    private function resolveBimaDateFilter(Request $request): array
    {
        $period = $request->query('period');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($startDate && $endDate) {
            $period = 'custom';
        } elseif (! $period || $period === 'all') {
            $period = null;
        }

        return [
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    private function applyBimaDateFilter($query, Request $request, string $dateColumn = 'start_date'): void
    {
        $period = $request->query('period');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($startDate && $endDate) {
            $query->whereBetween($dateColumn, [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);

            return;
        }

        if (! $period || $period === 'all') {
            return;
        }

        switch ($period) {
            case 'day':
            case 'today':
                $query->whereDate($dateColumn, today());
                break;
            case 'week':
                $query->whereBetween($dateColumn, [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth($dateColumn, now()->month)->whereYear($dateColumn, now()->year);
                break;
            case 'year':
                $query->whereYear($dateColumn, now()->year);
                break;
        }
    }

    private function mapBimaRow(Bima $bima, int $no): array
    {
        $booking = $bima->booking;
        $routeFrom = $booking?->route?->from ?? 'N/A';
        $routeTo = $booking?->route?->to ?? 'N/A';

        return [
            'no' => $no,
            'booking_code' => $booking->booking_code ?? 'N/A',
            'booking_date' => optional($booking?->created_at)->format('Y-m-d H:i') ?? 'N/A',
            'customer_name' => $booking->customer_name ?? 'N/A',
            'customer_phone' => $booking->customer_phone ?? 'N/A',
            'company' => $booking?->campany?->name ?? 'N/A',
            'bus_number' => $booking?->bus?->bus_number ?? 'N/A',
            'route' => $routeFrom . ' → ' . $routeTo,
            'start_date' => Carbon::parse($bima->start_date)->format('Y-m-d'),
            'end_date' => Carbon::parse($bima->end_date)->format('Y-m-d'),
            'valid_days' => $this->resolveValidDays($bima->end_date),
            'amount' => number_format((float) $bima->amount, 2),
            'vat' => number_format((float) $bima->bima_vat, 2),
        ];
    }

    private function resolveValidDays($endDate): string
    {
        $start = Carbon::parse(now());
        $end = Carbon::parse($endDate);

        if ($start->greaterThan($end)) {
            return 'expired';
        }

        return (string) $start->diffInDays($end);
    }

    private function emptyBimaRow(): array
    {
        return [
            'no' => '',
            'booking_code' => '',
            'booking_date' => '',
            'customer_name' => '',
            'customer_phone' => '',
            'company' => '',
            'bus_number' => '',
            'route' => '',
            'start_date' => '',
            'end_date' => '',
            'valid_days' => '',
            'amount' => '',
            'vat' => '',
        ];
    }

    private function streamBimaCsv(string $filename, array $headers, $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, array_values($row instanceof \Illuminate\Support\Collection ? $row->all() : (array) $row));
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
