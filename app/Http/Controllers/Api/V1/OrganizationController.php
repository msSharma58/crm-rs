<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $organizations = Organization::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%'))
            ->paginate((int) $request->get('per_page', 15));

        return $this->success(
            OrganizationResource::collection($organizations)->response()->getData(true)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'slug' => ['nullable', 'string', 'unique:organizations,slug'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string'],
            'currency' => ['nullable', 'string'],
            'locale' => ['nullable', 'string'],
            'branding' => ['nullable', 'array'],
        ]);

        $data['slug'] ??= Str::slug($data['name']);

        $organization = Organization::create($data);

        return $this->success(new OrganizationResource($organization), 'Organization created', 201);
    }

    public function show(Organization $organization): JsonResponse
    {
        return $this->success(new OrganizationResource($organization));
    }

    public function update(Request $request, Organization $organization): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string'],
            'currency' => ['nullable', 'string'],
            'locale' => ['nullable', 'string'],
            'branding' => ['nullable', 'array'],
            'status' => ['nullable', 'string'],
        ]);

        $organization->update($data);

        return $this->success(new OrganizationResource($organization->fresh()), 'Organization updated');
    }
}
