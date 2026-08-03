<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\UnitStatus;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use App\Modules\Payments\Application\Services\PaymentService;
use App\Modules\Sales\Application\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

it('creates a booking, reserves the unit, and records a payment', function (): void {
    $org = Organization::create([
        'name' => 'Homes Co',
        'slug' => 'homes-co',
        'status' => 'active',
        'currency' => 'NPR',
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($org->id);

    $user = User::create([
        'name' => 'Executive',
        'email' => 'exec@homes.test',
        'password' => Hash::make('password'),
        'organization_id' => $org->id,
        'is_active' => true,
    ]);

    $project = Project::create([
        'organization_id' => $org->id,
        'name' => 'Skyline Residences',
        'code' => 'SKY',
        'status' => 'active',
    ]);

    $unit = Unit::create([
        'organization_id' => $org->id,
        'project_id' => $project->id,
        'code' => 'A-101',
        'type' => '2BHK',
        'price' => 15000000,
        'status' => UnitStatus::Available->value,
    ]);

    $customer = Customer::create([
        'organization_id' => $org->id,
        'name' => 'Buyer One',
        'phone' => '+977-9801234567',
        'assigned_to' => $user->id,
    ]);

    /** @var BookingService $bookingService */
    $bookingService = app(BookingService::class);

    app(\App\Core\Tenancy\TenantContext::class)->setOrganizationId($org->id);

    $booking = $bookingService->create([
        'organization_id' => $org->id,
        'customer_id' => $customer->id,
        'unit_id' => $unit->id,
        'project_id' => $project->id,
        'sales_executive_id' => $user->id,
        'booking_amount' => 500000,
        'total_amount' => 15000000,
        'schedules' => [
            ['label' => 'Booking', 'amount' => 500000, 'due_date' => now()->toDateString(), 'sequence' => 1],
            ['label' => 'Installment 1', 'amount' => 14500000, 'due_date' => now()->addMonths(3)->toDateString(), 'sequence' => 2],
        ],
    ]);

    $booking = $bookingService->reserveUnit($booking);

    expect($booking->status)->toBe(BookingStatus::Reserved);
    expect($unit->fresh()->status)->toBe(UnitStatus::Reserved);

    $schedule = $booking->paymentSchedules()->first();
    expect($schedule)->not->toBeNull();

    /** @var PaymentService $paymentService */
    $paymentService = app(PaymentService::class);
    $payment = $paymentService->record([
        'booking_id' => $booking->id,
        'payment_schedule_id' => $schedule->id,
        'customer_id' => $customer->id,
        'amount' => 500000,
        'method' => 'bank',
        'paid_at' => now()->toDateString(),
        'recorded_by' => $user->id,
    ]);

    expect((float) $payment->amount)->toBe(500000.0);
});
