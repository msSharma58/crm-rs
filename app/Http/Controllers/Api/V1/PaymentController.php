<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Modules\Payments\Application\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $payments = $this->paymentService->paginate(
            $request->only(['status', 'booking_id']),
            (int) $request->get('per_page', 15)
        );

        return $this->success(
            PaymentResource::collection($payments)->response()->getData(true)
        );
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->record($request->validated());

        return $this->success(new PaymentResource($payment), 'Payment recorded', 201);
    }

    public function show(Payment $payment): JsonResponse
    {
        return $this->success(new PaymentResource($this->paymentService->find($payment->id)));
    }

    public function refund(Request $request, Payment $payment): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string'],
        ]);

        $refund = $this->paymentService->refund($payment, $data);

        return $this->success($refund, 'Refund processed');
    }
}
