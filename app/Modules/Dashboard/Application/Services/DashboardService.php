<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Application\Services;

use App\Enums\BookingStatus;
use App\Enums\LeadStatus;
use App\Enums\UnitStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

final class DashboardService
{
    public function kpis(): array
    {
        return [
            'leads' => $this->leadKpis(),
            'sales' => $this->salesKpis(),
            'operations' => $this->operationsKpis(),
            'inventory' => $this->inventoryKpis(),
        ];
    }

    private function leadKpis(): array
    {
        $total = Lead::count();
        $newThisMonth = Lead::where('created_at', '>=', now()->startOfMonth())->count();
        $converted = Lead::whereNotNull('converted_at')->count();
        $byStatus = Lead::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'total' => $total,
            'new_this_month' => $newThisMonth,
            'converted' => $converted,
            'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 2) : 0,
            'by_status' => $byStatus,
            'hot_leads' => Lead::where('priority', 'urgent')
                ->whereNotIn('status', [LeadStatus::Sold->value, LeadStatus::Lost->value, LeadStatus::Cancelled->value])
                ->count(),
        ];
    }

    private function salesKpis(): array
    {
        $bookings = Booking::count();
        $revenue = Payment::where('status', 'completed')->sum('amount');
        $pendingPayments = Payment::where('status', 'pending')->sum('amount');

        return [
            'total_bookings' => $bookings,
            'active_bookings' => Booking::whereIn('status', [
                BookingStatus::Reserved->value,
                BookingStatus::Booked->value,
            ])->count(),
            'revenue_collected' => (float) $revenue,
            'pending_payments' => (float) $pendingPayments,
            'sold_units' => Booking::where('status', BookingStatus::Sold->value)->count(),
        ];
    }

    private function operationsKpis(): array
    {
        return [
            'visits_today' => Visit::whereDate('scheduled_at', today())->count(),
            'visits_this_week' => Visit::whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'pending_follow_ups' => FollowUp::where('status', 'pending')->count(),
            'overdue_follow_ups' => FollowUp::where('status', 'pending')->where('due_at', '<', now())->count(),
            'total_customers' => Customer::count(),
        ];
    }

    private function inventoryKpis(): array
    {
        $units = Unit::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'total_units' => Unit::count(),
            'available' => $units[UnitStatus::Available->value] ?? 0,
            'reserved' => $units[UnitStatus::Reserved->value] ?? 0,
            'sold' => $units[UnitStatus::Sold->value] ?? 0,
            'blocked' => $units[UnitStatus::Blocked->value] ?? 0,
        ];
    }
}
