<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Modules\Documents\Application\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $documents = $this->documentService->paginate(
            $request->only(['type']),
            (int) $request->get('per_page', 15)
        );

        return $this->success(
            DocumentResource::collection($documents)->response()->getData(true)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'title' => ['required', 'string'],
            'documentable_type' => ['nullable', 'string'],
            'documentable_id' => ['nullable', 'integer'],
            'change_note' => ['nullable', 'string'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $document = $this->documentService->upload($data, $request->file('file'));

        return $this->success(new DocumentResource($document), 'Document uploaded', 201);
    }

    public function show(Document $document): JsonResponse
    {
        return $this->success(new DocumentResource($this->documentService->find($document->id)));
    }

    public function destroy(Document $document): JsonResponse
    {
        $this->documentService->delete($document);

        return $this->success(null, 'Document deleted');
    }
}
