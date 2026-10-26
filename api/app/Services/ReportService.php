<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Monthly report data for the admin dashboard.
     *
     * @param  string $month  Format: Y-m (e.g. "2024-10")
     * @return array
     */
    public function monthly(string $month): array
    {
        [$year, $mon] = explode('-', $month);
        $start = Carbon::create((int) $year, (int) $mon, 1)->startOfMonth()->utc();
        $end   = $start->copy()->endOfMonth();

        $bookings = Booking::query()
            ->with(['service', 'staff'])
            ->whereBetween('starts_at', [$start, $end])
            ->get();

        return [
            'month'               => $month,
            'total'               => $bookings->count(),
            'by_status'           => $this->byStatus($bookings),
            'by_service'          => $this->byService($bookings),
            'by_staff'            => $this->byStaff($bookings),
            'estimated_revenue'   => $this->estimatedRevenue($bookings),
        ];
    }

    private function byStatus(Collection $bookings): array
    {
        return $bookings
            ->groupBy(fn ($b) => $b->status->value)
            ->map->count()
            ->all();
    }

    private function byService(Collection $bookings): array
    {
        return $bookings
            ->groupBy('service_id')
            ->map(fn ($group) => [
                'name'     => $group->first()->service->name,
                'count'    => $group->count(),
                'revenue'  => $group
                    ->whereIn('status', [BookingStatus::Confirmed->value, BookingStatus::Completed->value])
                    ->sum(fn ($b) => $b->service->price),
            ])
            ->values()
            ->all();
    }

    private function byStaff(Collection $bookings): array
    {
        return $bookings
            ->groupBy('staff_id')
            ->map(fn ($group) => [
                'name'  => $group->first()->staff->display_name,
                'count' => $group->count(),
            ])
            ->values()
            ->all();
    }

    private function estimatedRevenue(Collection $bookings): int
    {
        // Only count confirmed and completed bookings
        return $bookings
            ->filter(fn ($b) => in_array($b->status->value, [
                BookingStatus::Confirmed->value,
                BookingStatus::Completed->value,
            ]))
            ->sum(fn ($b) => $b->service->price);
    }
}
