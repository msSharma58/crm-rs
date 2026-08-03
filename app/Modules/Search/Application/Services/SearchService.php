<?php

declare(strict_types=1);

namespace App\Modules\Search\Application\Services;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;

final class SearchService
{
    public function search(string $query, ?string $type = null, int $limit = 10): array
    {
        $results = [];

        if ($type === null || $type === 'leads') {
            $results['leads'] = Lead::query()
                ->where(function ($q) use ($query): void {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get(['id', 'name', 'phone', 'email', 'status']);
        }

        if ($type === null || $type === 'customers') {
            $results['customers'] = Customer::query()
                ->where(function ($q) use ($query): void {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('phone', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get(['id', 'name', 'phone', 'email']);
        }

        if ($type === null || $type === 'projects') {
            $results['projects'] = Project::query()
                ->where('name', 'like', "%{$query}%")
                ->orWhere('code', 'like', "%{$query}%")
                ->limit($limit)
                ->get(['id', 'name', 'code', 'location', 'status']);
        }

        if ($type === null || $type === 'units') {
            $results['units'] = Unit::query()
                ->where('code', 'like', "%{$query}%")
                ->with('project:id,name')
                ->limit($limit)
                ->get(['id', 'code', 'type', 'status', 'price', 'project_id']);
        }

        if ($type === null || $type === 'bookings') {
            $results['bookings'] = Booking::query()
                ->where('code', 'like', "%{$query}%")
                ->with('customer:id,name')
                ->limit($limit)
                ->get(['id', 'code', 'status', 'customer_id', 'total_amount']);
        }

        if ($type === null || $type === 'users') {
            $results['users'] = User::query()
                ->where(function ($q) use ($query): void {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                })
                ->limit($limit)
                ->get(['id', 'name', 'email', 'phone']);
        }

        return $results;
    }
}
