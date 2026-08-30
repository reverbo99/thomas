<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RouteDistanceService
{
    /**
     * Resolve route distance for booking checkout.
     *
     * Priority:
     * 1. Client-submitted distance when > 99 km (trusted long-haul)
     * 2. Stored routes.distance when > 99 km
     * 3. Live geocode of schedule/route city endpoints
     * 4. Live geocode of pickup → drop
     * 5. Known Tanzania city-pair fallback (e.g. DAR ↔ DODOMA ≈ 451 km)
     * 6. Short submitted/stored distance (> 1 km) for local routes
     *
     * Returns null when distance cannot be resolved. Never invents a silent 1 km
     * fallback (that incorrectly hid BIMA for long trips like DAR → DODOMA).
     */
    public static function resolveForBooking(
        $submitted,
        ?string $pickup,
        ?string $drop,
        ?float $routeDefault = null,
        ?string $cityFrom = null,
        ?string $cityTo = null
    ): ?float {
        $submitted = ($submitted !== null && $submitted !== '') ? (float) $submitted : 0.0;
        $cityFrom = $cityFrom !== null ? trim($cityFrom) : '';
        $cityTo = $cityTo !== null ? trim($cityTo) : '';

        // Trust client distance only when it looks like a real long-haul value.
        // Short submitted values often come from failed/partial stop geocoding and
        // incorrectly hide BIMA on intercity trips.
        if ($submitted > 99) {
            return $submitted;
        }

        // Prefer a real stored long-haul distance. Values ≤ 99 are often wrong
        // placeholders for intercity routes and would incorrectly hide BIMA.
        $storedLongHaul = ($routeDefault !== null && $routeDefault > 99)
            ? (float) $routeDefault
            : null;
        if ($storedLongHaul !== null) {
            return $storedLongHaul;
        }

        if ($cityFrom !== '' && $cityTo !== '') {
            $computed = self::betweenPlaceNames($cityFrom, $cityTo);
            if ($computed !== null && $computed > 1) {
                return $computed;
            }
        }

        if ($pickup && $drop && !self::samePlacePair($pickup, $drop, $cityFrom, $cityTo)) {
            $computed = self::betweenPlaceNames($pickup, $drop);
            if ($computed !== null && $computed > 1) {
                return $computed;
            }
        }

        // Offline fallback for major TZ city pairs when Nominatim/OSRM are unavailable.
        $known = self::knownCityPairKm($cityFrom ?: $pickup, $cityTo ?: $drop);
        if ($known !== null) {
            return $known;
        }

        // Local / short routes: accept submitted or stored distance when > 1.
        if ($submitted > 1) {
            return $submitted;
        }
        if ($routeDefault !== null && $routeDefault > 1) {
            return (float) $routeDefault;
        }

        return null;
    }

    /**
     * Approximate road distances (km) between major Tanzania cities.
     * Used only when live geocode fails so long-haul BIMA eligibility is not lost.
     */
    public static function knownCityPairKm(?string $from, ?string $to): ?float
    {
        $a = self::normalizeCityKey($from);
        $b = self::normalizeCityKey($to);
        if ($a === '' || $b === '' || $a === $b) {
            return null;
        }

        // Undirected pairs — approximate highway distances.
        $pairs = [
            'dar es salaam|dodoma' => 451.0,
            'dar es salaam|arusha' => 645.0,
            'dar es salaam|mwanza' => 1115.0,
            'dar es salaam|mbeya' => 835.0,
            'dar es salaam|morogoro' => 195.0,
            'dar es salaam|tanga' => 355.0,
            'dar es salaam|iringa' => 500.0,
            'dodoma|arusha' => 430.0,
            'dodoma|mwanza' => 680.0,
            'dodoma|mbeya' => 610.0,
            'dodoma|morogoro' => 265.0,
            'arusha|mwanza' => 615.0,
            'arusha|moshi' => 80.0,
            'mbeya|iringa' => 340.0,
        ];

        $key = $a < $b ? "{$a}|{$b}" : "{$b}|{$a}";

        return $pairs[$key] ?? null;
    }

    private static function normalizeCityKey(?string $place): string
    {
        $p = strtolower(trim((string) $place));
        if ($p === '') {
            return '';
        }

        $p = preg_replace('/\s+/', ' ', $p) ?? $p;
        $aliases = [
            'dar es salaam' => ['dar', 'dsm', 'dar-es-salaam', 'daresalaam', 'dar es salam'],
            'dodoma' => ['dodoma city'],
            'arusha' => ['arusha city'],
            'mwanza' => ['mwanza city'],
            'mbeya' => ['mbeya city'],
            'morogoro' => ['morogoro city'],
            'tanga' => ['tanga city'],
            'iringa' => ['iringa city'],
            'moshi' => ['moshi town'],
        ];

        foreach ($aliases as $canonical => $names) {
            if ($p === $canonical || str_contains($p, $canonical)) {
                return $canonical;
            }
            foreach ($names as $alias) {
                if ($p === $alias || str_contains($p, $alias)) {
                    return $canonical;
                }
            }
        }

        return $p;
    }

    private static function samePlacePair(?string $a1, ?string $a2, string $b1, string $b2): bool
    {
        $norm = static fn (?string $v) => strtolower(trim((string) $v));

        return $norm($a1) === $norm($b1) && $norm($a2) === $norm($b2);
    }

    public static function betweenPlaceNames(string $from, string $to): ?float
    {
        $fromCoords = self::geocode($from);
        if (!$fromCoords) {
            return null;
        }

        usleep(1100000);

        $toCoords = self::geocode($to);
        if (!$toCoords) {
            return null;
        }

        return self::routeDistanceKm(
            $fromCoords['lat'],
            $fromCoords['lon'],
            $toCoords['lat'],
            $toCoords['lon']
        );
    }

    private static function geocode(string $place): ?array
    {
        $query = trim($place);
        if ($query === '') {
            return null;
        }

        if (preg_match('/^-?\d+\.\d+\s*,\s*-?\d+\.\d+$/', $query)) {
            $parts = array_map('trim', explode(',', $query));

            return ['lat' => (float) $parts[0], 'lon' => (float) $parts[1]];
        }

        $searchQuery = str_contains(strtolower($query), 'tanzania')
            ? $query
            : $query . ', Tanzania';

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => 'HighlinkBooking/1.0',
            ])->timeout(12)->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'json',
                'q' => $searchQuery,
                'limit' => 1,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            if (empty($data[0]['lat']) || empty($data[0]['lon'])) {
                return null;
            }

            return [
                'lat' => (float) $data[0]['lat'],
                'lon' => (float) $data[0]['lon'],
            ];
        } catch (\Throwable $e) {
            Log::warning('RouteDistanceService geocode failed', [
                'place' => $place,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public static function routeDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        try {
            $url = sprintf(
                'https://router.project-osrm.org/route/v1/driving/%f,%f;%f,%f?overview=false',
                $lon1,
                $lat1,
                $lon2,
                $lat2
            );

            $response = Http::timeout(12)->get($url);
            if ($response->successful()) {
                $data = $response->json();
                if (($data['code'] ?? '') === 'Ok' && !empty($data['routes'][0]['distance'])) {
                    return round($data['routes'][0]['distance'] / 1000, 2);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('RouteDistanceService OSRM failed', ['error' => $e->getMessage()]);
        }

        return self::haversineKm($lat1, $lon1, $lat2, $lon2);
    }

    public static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}
