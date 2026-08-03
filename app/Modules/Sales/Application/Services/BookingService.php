<?php

declare(strict_types=1);

namespace App\Modules\Sales\Application\Services;

use App\Enums\BookingStatus;
use App\Enums\UnitStatus;
use App\Models\Booking;
use App\Models\PaymentSchedule;
use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class BookingService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Booking::query()
            ->with(['customer', 'unit', 'project', 'salesExecutive'])
            ->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Booking
    {
        return Booking::with(['customer', 'unit', 'project', 'paymentSchedules', 'payments'])->find($id);
    }

    public function create(array $data): Booking
    {
        return DB::transaction(function () use ($data): Booking {
            $schedules = $data['schedules'] ?? [];
            unset($data['schedules']);

            $data['code'] ??= 'BK-'.Str::upper(Str::random(8));
            $data['status'] ??= BookingStatus::Draft->value;

            $booking = Booking::create($data);

            if ($schedules !== []) {
                $this->createPaymentSchedule($booking, $schedules);
            }

            return $booking->load(['customer', 'unit', 'project', 'paymentSchedules']);
        });
    }

    public function reserveUnit(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking): Booking {
            $unit = Unit::findOrFail($booking->unit_id);

            if ($unit->status !== UnitStatus::Available) {
                throw new \RuntimeException('Unit is not available for reservation.');
            }

            $unit->update(['status' => UnitStatus::Reserved]);
            $booking->update(['status' => BookingStatus::Reserved]);

            return $booking->fresh(['unit', 'customer', 'project']);
        });
    }

    public function confirmBooking(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking): Booking {
            $booking->update([
                'status' => BookingStatus::Booked,
                'booked_at' => now()->toDateString(),
            ]);

            return $booking->fresh();
        });
    }

    public function cancel(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking): Booking {
            $booking->unit?->update(['status' => UnitStatus::Available]);
            $booking->update(['status' => BookingStatus::Cancelled]);

            return $booking->fresh();
        });
    }

    public function createPaymentSchedule(Booking $booking, array $schedules): array
    {
        $created = [];

        foreach ($schedules as $index => $schedule) {
            $created[] = PaymentSchedule::create([
                'organization_id' => $booking->organization_id,
                'booking_id' => $booking->id,
                'label' => $schedule['label'],
                'amount' => $schedule['amount'],
                'due_date' => $schedule['due_date'],
                'sequence' => $schedule['sequence'] ?? ($index + 1),
                'status' => $schedule['status'] ?? 'pending',
            ]);
        }

        return $created;
    }

    public function update(Booking $booking, array $data): Booking
    {
        $booking->update($data);

        return $booking->fresh();
    }
}
