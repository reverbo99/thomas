<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #111; margin: 0; padding: 14px; }
        h1 { font-size: 16px; margin: 0 0 4px; color: #1d4ed8; }
        h2 { font-size: 12px; margin: 18px 0 8px; color: #1e3a8a; }
        .meta { font-size: 9px; color: #666; margin-bottom: 12px; }
        .summary { margin-bottom: 12px; font-size: 9px; }
        .summary span { display: inline-block; margin: 0 12px 6px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #d1d5db; padding: 5px 4px; text-align: left; vertical-align: top; }
        th { background: #eff6ff; font-size: 8px; text-transform: uppercase; }
        tfoot td { font-weight: bold; background: #f9fafb; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">
        {{ __('all.highlink_isgc') }} · {{ __('system.pages.printed_on') }}: {{ now()->format('Y-m-d H:i:s') }}
        @if ($period)
            · {{ __('system.pages.period') }}: {{ ucfirst($period) }}
            @if ($period === 'custom' && $startDate && $endDate)
                ({{ $startDate }} — {{ $endDate }})
            @endif
        @else
            · {{ __('system.common.all_time') }}
        @endif
    </p>

    <div class="summary">
        <strong>{{ __('system.pages.combined_total') }}:</strong>
        <span>{{ number_format($combinedTotal ?? 0, 2) }}</span>
        <span>{{ __('system.pages.commission') }}: {{ number_format($commissionTotal ?? 0, 2) }}</span>
        <span>{{ __('system.pages.service_fees') }}: {{ number_format($serviceFeeTotal ?? 0, 2) }}</span>
        <span>{{ __('system.pages.luggage_fees') }}: {{ number_format($luggageTotal ?? 0, 2) }}</span>
        <span>{{ __('system.pages.cancellation_fees') }}: {{ number_format($cancellationTotal ?? 0, 2) }}</span>
        <span>{{ __('system.pages.parcel_commission_fees') }}: {{ number_format($parcelTotal ?? 0, 2) }}</span>
        <span>{{ __('system.pages.special_hire_commission_fees') }}: {{ number_format($specialHireTotal ?? 0, 2) }}</span>
    </div>

    <h2>{{ __('system.pages.commission_section') }}</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>{{ __('system.pages.col_company') }}</th>
                <th>{{ __('system.pages.col_booking_code') }}</th>
                <th class="text-right">{{ __('system.common.amount') }}</th>
                <th>{{ __('system.common.date') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($commissionRows as $row)
                <tr>
                    <td>{{ $row['no'] }}</td>
                    <td>{{ $row['company'] }}</td>
                    <td>{{ $row['booking_code'] }}</td>
                    <td class="text-right">{{ $row['amount'] }}</td>
                    <td>{{ $row['date'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">{{ __('system.pages.no_data_found') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($commissionRows) > 0)
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right">{{ __('system.pages.section_total') }}</td>
                    <td class="text-right">{{ number_format($commissionTotal ?? 0, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <h2>{{ __('system.pages.service_fees') }}</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>{{ __('system.pages.col_company') }}</th>
                <th>{{ __('system.pages.col_booking_code') }}</th>
                <th class="text-right">{{ __('system.common.amount') }}</th>
                <th>{{ __('system.common.date') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($serviceFeeRows as $row)
                <tr>
                    <td>{{ $row['no'] }}</td>
                    <td>{{ $row['company'] }}</td>
                    <td>{{ $row['booking_code'] }}</td>
                    <td class="text-right">{{ $row['amount'] }}</td>
                    <td>{{ $row['date'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">{{ __('system.pages.no_data_found') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($serviceFeeRows) > 0)
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right">{{ __('system.pages.section_total') }}</td>
                    <td class="text-right">{{ number_format($serviceFeeTotal ?? 0, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <h2>{{ __('system.pages.luggage_fees') }}</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>{{ __('system.pages.col_company') }}</th>
                <th>{{ __('system.pages.col_booking_code') }}</th>
                <th class="text-right">{{ __('system.common.amount') }}</th>
                <th>{{ __('system.common.date') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($luggageRows ?? []) as $row)
                <tr>
                    <td>{{ $row['no'] }}</td>
                    <td>{{ $row['company'] }}</td>
                    <td>{{ $row['booking_code'] }}</td>
                    <td class="text-right">{{ $row['amount'] }}</td>
                    <td>{{ $row['date'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">{{ __('system.pages.no_data_found') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($luggageRows ?? []) > 0)
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right">{{ __('system.pages.section_total') }}</td>
                    <td class="text-right">{{ number_format($luggageTotal ?? 0, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <h2>{{ __('system.pages.cancellation_fees') }}</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>{{ __('system.pages.col_company') }}</th>
                <th>{{ __('system.pages.col_booking_code') }}</th>
                <th class="text-right">{{ __('system.common.amount') }}</th>
                <th>{{ __('system.common.date') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($cancellationRows ?? []) as $row)
                <tr>
                    <td>{{ $row['no'] }}</td>
                    <td>{{ $row['company'] }}</td>
                    <td>{{ $row['booking_code'] }}</td>
                    <td class="text-right">{{ $row['amount'] }}</td>
                    <td>{{ $row['date'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">{{ __('system.pages.no_data_found') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($cancellationRows ?? []) > 0)
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right">{{ __('system.pages.section_total') }}</td>
                    <td class="text-right">{{ number_format($cancellationTotal ?? 0, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <h2>{{ __('system.pages.parcel_commission_fees') }}</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>{{ __('system.pages.col_company') }}</th>
                <th>{{ __('system.pages.col_parcel_number') }}</th>
                <th class="text-right">{{ __('system.common.amount') }}</th>
                <th>{{ __('system.common.date') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($parcelRows ?? []) as $row)
                <tr>
                    <td>{{ $row['no'] }}</td>
                    <td>{{ $row['company'] }}</td>
                    <td>{{ $row['booking_code'] }}</td>
                    <td class="text-right">{{ $row['amount'] }}</td>
                    <td>{{ $row['date'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">{{ __('system.pages.no_data_found') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($parcelRows ?? []) > 0)
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right">{{ __('system.pages.section_total') }}</td>
                    <td class="text-right">{{ number_format($parcelTotal ?? 0, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>

    <h2>{{ __('system.pages.special_hire_commission_fees') }}</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>{{ __('system.pages.col_operator') }}</th>
                <th>{{ __('system.pages.col_order_code') }}</th>
                <th class="text-right">{{ __('system.common.amount') }}</th>
                <th>{{ __('system.common.date') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($specialHireRows ?? []) as $row)
                <tr>
                    <td>{{ $row['no'] }}</td>
                    <td>{{ $row['company'] }}</td>
                    <td>{{ $row['booking_code'] }}</td>
                    <td class="text-right">{{ $row['amount'] }}</td>
                    <td>{{ $row['date'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">{{ __('system.pages.no_data_found') }}</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($specialHireRows ?? []) > 0)
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right">{{ __('system.pages.section_total') }}</td>
                    <td class="text-right">{{ number_format($specialHireTotal ?? 0, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
