<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Exchange email & password for an API personal access token.
     */
    public function token(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $role = $user->currentRole();

        if ($role === 'staff') {
            return response()->json([
                'message' => 'Staff members cannot generate API tokens.',
            ], 403);
        }

        $company = $user->currentCompany;

        $abilities = $role === 'owner' ? ['*'] : [
            'read',
            'invoices:create',
            'invoices:update',
            'invoices:send',
            'invoices:void',
            'payments:create',
            'clients:manage',
            'products:manage',
        ];

        $deviceName = $request->input('device_name') ?: 'api-token';
        $token = $user->createToken($deviceName, $abilities)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'company' => $company ? [
                'id' => $company->id,
                'name' => $company->name,
            ] : null,
            'role' => $role,
        ]);
    }

    /**
     * Revoke the current access token.
     */
    public function revoke(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Token revoked successfully.',
        ]);
    }
}
