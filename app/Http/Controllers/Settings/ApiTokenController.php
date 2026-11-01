<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $role = $user->currentRole();

        if (! in_array($role, ['owner', 'accountant'], true)) {
            abort(403, 'Staff members cannot manage API tokens.');
        }

        $tokens = $user->tokens()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'abilities', 'last_used_at', 'created_at'])
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->diffForHumans(),
                'created_at' => $token->created_at?->format('d M Y, H:i'),
            ]);

        return Inertia::render('settings/api-tokens', [
            'tokens' => $tokens,
            'role' => $role,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $role = $user->currentRole();

        if (! in_array($role, ['owner', 'accountant'], true)) {
            abort(403, 'Staff members cannot create API tokens.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

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

        $token = $user->createToken($validated['name'], $abilities)->plainTextToken;

        return back()->with([
            'newToken' => $token,
            'success' => 'API token generated successfully.',
        ]);
    }

    public function destroy(Request $request, int|string $id): RedirectResponse
    {
        $user = $request->user();
        $role = $user->currentRole();

        if (! in_array($role, ['owner', 'accountant'], true)) {
            abort(403, 'Unauthorized.');
        }

        $user->tokens()->where('id', $id)->delete();

        return back()->with('success', 'API token revoked successfully.');
    }
}
