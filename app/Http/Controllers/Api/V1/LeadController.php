<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Requests\UpdateLeadStatusRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use App\Modules\Leads\Application\Services\LeadService;
use App\Modules\Leads\Domain\DTOs\CreateLeadData;
use App\Modules\Leads\Domain\DTOs\UpdateLeadData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Lead::class);

        $leads = $this->leadService->paginate($request->only([
            'status', 'assigned_to', 'search', 'priority', 'source',
        ]), (int) $request->get('per_page', 15));

        return $this->success(
            LeadResource::collection($leads)->response()->getData(true)
        );
    }

    public function store(StoreLeadRequest $request): JsonResponse
    {
        $this->authorize('create', Lead::class);

        $lead = $this->leadService->create(CreateLeadData::fromArray($request->validated()));

        return $this->success(new LeadResource($lead), 'Lead created', 201);
    }

    public function show(Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        return $this->success(new LeadResource($this->leadService->find($lead->id)));
    }

    public function update(UpdateLeadRequest $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $lead = $this->leadService->update($lead, UpdateLeadData::fromArray($request->validated()));

        return $this->success(new LeadResource($lead), 'Lead updated');
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $this->authorize('delete', $lead);

        $this->leadService->delete($lead);

        return $this->success(null, 'Lead deleted');
    }

    public function board(): JsonResponse
    {
        $this->authorize('viewAny', Lead::class);

        $board = $this->leadService->boardByStatus();

        return $this->success(
            $board->map(fn ($leads, $status) => [
                'status' => $status,
                'leads' => LeadResource::collection($leads),
            ])->values()
        );
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $status = LeadStatus::from($request->validated('status'));
        $lead = $this->leadService->changeStatus($lead, $status);

        return $this->success(new LeadResource($lead), 'Status updated');
    }

    public function assign(Request $request, Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $request->validate(['assigned_to' => ['nullable', 'integer', 'exists:users,id']]);

        $lead = $this->leadService->assign($lead, $request->input('assigned_to'));

        return $this->success(new LeadResource($lead), 'Lead assigned');
    }

    public function convert(Lead $lead): JsonResponse
    {
        $this->authorize('update', $lead);

        $customer = $this->leadService->convertToCustomer($lead);

        return $this->success(new CustomerResource($customer), 'Lead converted to customer');
    }

    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', Lead::class);

        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt']]);

        $result = $this->leadService->importCsv($request->file('file')->getRealPath());

        return $this->success($result, 'Import completed');
    }
}
