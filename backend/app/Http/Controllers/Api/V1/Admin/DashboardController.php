<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\NotificationLog;
use App\Models\Parcel;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $parcelsByStatus = Parcel::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $revenueToday = Parcel::whereDate('created_at', today())
            ->sum('total_price_lkr');

        $revenueMtd = Parcel::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_price_lkr');

        $bookingsToday = Parcel::whereDate('created_at', today())->count();
        $bookingsMtd   = Parcel::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $activeTrips = Trip::whereIn('status', ['SCHEDULED', 'LOADING', 'IN_TRANSIT'])->count();

        $totalCustomers = User::where('role', 'customer')->count();

        // Last 7 days bookings
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date        = now()->subDays($i)->toDateString();
            $last7Days[] = [
                'date'   => $date,
                'count'  => Parcel::whereDate('created_at', $date)->count(),
                'revenue' => (float) Parcel::whereDate('created_at', $date)->sum('total_price_lkr'),
            ];
        }

        // Recent parcels
        $recentParcels = Parcel::with(['customer', 'route.originHub', 'route.destinationHub'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'parcel_number' => $p->parcel_number,
                'status'        => $p->status->value,
                'customer_name' => $p->customer?->full_name,
                'route'         => $p->route?->display_name,
                'total_price'   => $p->total_price_lkr,
                'created_at'    => $p->created_at?->toIso8601String(),
            ]);

        // Notification stats
        $notifStats = NotificationLog::selectRaw('channel, status, COUNT(*) as count')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->groupBy('channel', 'status')
            ->get();

        return ApiResponse::success([
            'kpis' => [
                'bookings_today'   => $bookingsToday,
                'bookings_mtd'     => $bookingsMtd,
                'revenue_today'    => (float) $revenueToday,
                'revenue_mtd'      => (float) $revenueMtd,
                'active_trips'     => $activeTrips,
                'total_customers'  => $totalCustomers,
            ],
            'parcels_by_status' => $parcelsByStatus,
            'last_7_days'       => $last7Days,
            'recent_parcels'    => $recentParcels,
            'notification_stats' => $notifStats,
        ]);
    }
}
