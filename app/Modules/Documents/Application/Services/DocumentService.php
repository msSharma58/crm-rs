<?php

declare(strict_types=1);

namespace App\Modules\Documents\Application\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class DocumentService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Document::query()->with(['uploader', 'documentable'])->latest();

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?Document
    {
        return Document::with(['versions', 'uploader', 'documentable'])->find($id);
    }

    public function upload(array $data, UploadedFile $file): Document
    {
        return DB::transaction(function () use ($data, $file): Document {
            $path = $file->store('documents/'.$data['type'], 'local');

            $document = Document::create([
                'documentable_type' => $data['documentable_type'] ?? null,
                'documentable_id' => $data['documentable_id'] ?? null,
                'uploaded_by' => Auth::id(),
                'type' => $data['type'],
                'title' => $data['title'],
                'current_path' => $path,
                'version' => 1,
            ]);

            DocumentVersion::create([
                'document_id' => $document->id,
                'uploaded_by' => Auth::id(),
                'version' => 1,
                'path' => $path,
                'disk' => 'local',
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'change_note' => $data['change_note'] ?? 'Initial upload',
            ]);

            return $document->load('versions');
        });
    }

    public function addVersion(Document $document, UploadedFile $file, ?string $changeNote = null): DocumentVersion
    {
        return DB::transaction(function () use ($document, $file, $changeNote): DocumentVersion {
            $newVersion = $document->version + 1;
            $path = $file->store('documents/'.$document->type, 'local');

            $version = DocumentVersion::create([
                'document_id' => $document->id,
                'uploaded_by' => Auth::id(),
                'version' => $newVersion,
                'path' => $path,
                'disk' => 'local',
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'change_note' => $changeNote,
            ]);

            $document->update([
                'current_path' => $path,
                'version' => $newVersion,
            ]);

            return $version;
        });
    }

    public function delete(Document $document): bool
    {
        return DB::transaction(function () use ($document): bool {
            foreach ($document->versions as $version) {
                Storage::disk($version->disk)->delete($version->path);
            }

            return (bool) $document->delete();
        });
    }
}
