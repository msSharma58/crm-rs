<?php

declare(strict_types=1);

namespace App\Modules\Payments\Application\Services;

use App\Models\Payment;
use App\Models\PaymentSchedule;
use App\Models\Refund;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class PaymentService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Payment::query()
            ->with(['customer', 'booking', 'paymentSchedule', 'recorder'])
            ->latest();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['booking_id'])) {
            $query->where('booking_id', $filters['booking_id']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Payment
    {
        return Payment::with(['customer', 'booking', 'paymentSchedule', 'refunds'])->find($id);
    }

    public function record(array $data): Payment
    {
        return DB::transaction(function () use ($data): Payment {
            $data['recorded_by'] ??= Auth::id();
            $data['paid_at'] ??= now()->toDateString();
            $data['status'] ??= 'completed';

            $payment = Payment::create($data);

            if (! empty($data['payment_schedule_id'])) {
                $this->updateScheduleStatus((int) $data['payment_schedule_id'], (float) $data['amount']);
            }

            return $payment->load(['customer', 'booking', 'paymentSchedule']);
        });
    }

    public function refund(Payment $payment, array $data): Refund
    {
        return DB::transaction(function () use ($payment, $data): Refund {
            $refund = Refund::create([
                'payment_id' => $payment->id,
                'amount' => $data['amount'],
                'reason' => $data['reason'] ?? null,
                'processed_by' => Auth::id(),
                'processed_at' => now(),
            ]);

            $payment->update(['status' => 'refunded']);

            return $refund;
        });
    }

    private function updateScheduleStatus(int $scheduleId, float $amount): void
    {
        $schedule = PaymentSchedule::find($scheduleId);

        if ($schedule === null) {
            return;
        }

        $paidTotal = $schedule->payments()->where('status', 'completed')->sum('amount') + $amount;

        if ($paidTotal >= (float) $schedule->amount) {
            $schedule->update(['status' => 'paid']);
        } elseif ($paidTotal > 0) {
            $schedule->update(['status' => 'partial']);
        }
    }
}
