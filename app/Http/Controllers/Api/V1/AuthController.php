<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            return $this->error('Account is inactive.', 403);
        }

        if ($user->organization_id !== null) {
            app(PermissionRegistrar::class)->setPermissionsTeamId((int) $user->organization_id);
        }

        $user->update(['last_login_at' => now()]);

        $deviceName = $request->validated('device_name') ?? 'api';
        $token = $user->createToken($deviceName)->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => new UserResource($user->load('organization', 'roles')),
        ], 'Login successful');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->organization_id !== null) {
            app(PermissionRegistrar::class)->setPermissionsTeamId((int) $user->organization_id);
        }

        return $this->success(
            new UserResource($user->load('organization', 'roles', 'permissions'))
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out successfully');
    }
}
