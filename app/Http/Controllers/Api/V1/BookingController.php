<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Modules\Sales\Application\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = $this->bookingService->paginate(
            $request->only(['status']),
            (int) $request->get('per_page', 15)
        );

        return $this->success(
            BookingResource::collection($bookings)->response()->getData(true)
        );
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $this->authorize('create', Booking::class);

        $booking = $this->bookingService->create($request->validated());

        return $this->success(new BookingResource($booking), 'Booking created', 201);
    }

    public function show(Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        return $this->success(new BookingResource($this->bookingService->find($booking->id)));
    }

    public function reserve(Booking $booking): JsonResponse
    {
        $this->authorize('update', $booking);

        $booking = $this->bookingService->reserveUnit($booking);

        return $this->success(new BookingResource($booking), 'Unit reserved');
    }

    public function confirm(Booking $booking): JsonResponse
    {
        $this->authorize('update', $booking);

        $booking = $this->bookingService->confirmBooking($booking);

        return $this->success(new BookingResource($booking), 'Booking confirmed');
    }

    public function cancel(Booking $booking): JsonResponse
    {
        $this->authorize('delete', $booking);

        $booking = $this->bookingService->cancel($booking);

        return $this->success(new BookingResource($booking), 'Booking cancelled');
    }
}
