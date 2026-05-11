<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TripStatus;
use App\Models\Lorry;
use App\Models\Route;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Generates upcoming trips for every active route.
 *
 * Default: 14 days forward, two trips per day (06:00 and 14:00 Asia/Colombo).
 * Idempotent: re-running won't create duplicates (unique trip_code).
 */
class GenerateTripsCommand extends Command
{
    protected $signature = 'trips:generate
                            {--days=14 : Number of days forward to generate}
                            {--from= : Start date (YYYY-MM-DD), defaults to today}';

    protected $description = 'Generate scheduled trips for active routes (idempotent).';

    /** Departure times in 24h format, Asia/Colombo. */
    private array $departureTimes = ['06:00', '14:00'];

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $from = $this->option('from')
            ? Carbon::parse($this->option('from'), 'Asia/Colombo')->startOfDay()
            : Carbon::now('Asia/Colombo')->startOfDay();

        $routes = Route::where('is_active', true)->get();
        $defaultLorry = Lorry::where('is_active', true)->first();

        $created = 0;
        $skipped = 0;

        foreach ($routes as $route) {
            for ($d = 0; $d < $days; $d++) {
                $date = $from->copy()->addDays($d);
                foreach ($this->departureTimes as $time) {
                    [$h, $m] = explode(':', $time);
                    $departure = $date->copy()->setTime((int) $h, (int) $m, 0)->setTimezone('UTC');
                    $arrival = $departure->copy()->addMinutes((int) $route->estimated_duration_minutes);

                    $code = sprintf(
                        'TRP-%s-%s-%s',
                        $date->format('Ymd'),
                        $route->code,
                        $h
                    );

                    if (Trip::where('trip_code', $code)->exists()) {
                        $skipped++;
                        continue;
                    }

                    Trip::create([
                        'trip_code' => $code,
                        'route_id' => $route->id,
                        'lorry_id' => $defaultLorry?->id,
                        'driver_id' => null,
                        'scheduled_departure' => $departure,
                        'scheduled_arrival' => $arrival,
                        'status' => TripStatus::SCHEDULED,
                        'capacity_units_max' => $defaultLorry?->max_capacity_units ?? 300,
                        'capacity_units_used' => 0,
                        'bookings_close_at' => $departure->copy()->subHours(2),
                    ]);
                    $created++;
                }
            }
        }

        $this->info("trips:generate complete — created={$created}, skipped={$skipped}");

        return self::SUCCESS;
    }
}
