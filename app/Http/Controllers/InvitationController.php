<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CompanyInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(string $token): Response
    {
        /** @var CompanyInvitation $invitation */
        $invitation = CompanyInvitation::withoutGlobalScopes()
            ->where('token', $token)
            ->with('company')
            ->firstOrFail();

        $status = 'valid';
        if ($invitation->isAccepted()) {
            $status = 'accepted';
        } elseif ($invitation->isExpired()) {
            $status = 'expired';
        }

        return Inertia::render('invitations/accept', [
            'invitation' => [
                'token' => $invitation->token,
                'email' => $invitation->email,
                'role' => $invitation->role->label(),
                'company_name' => $invitation->company->name,
                'status' => $status,
                'expires_at' => $invitation->expires_at->toFormattedDateString(),
            ],
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        /** @var CompanyInvitation $invitation */
        $invitation = CompanyInvitation::withoutGlobalScopes()
            ->where('token', $token)
            ->with('company')
            ->firstOrFail();

        if (! $invitation->isValid()) {
            return back()->withErrors(['error' => 'This invitation is no longer valid or has expired.']);
        }

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login', ['invitation_token' => $token]);
        }

        // Email match check
        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return back()->withErrors([
                'error' => "This invitation was sent to {$invitation->email}. You are logged in as {$user->email}.",
            ]);
        }

        // Check if already in workspace
        $isMember = $invitation->company->users()->where('users.id', $user->id)->exists();
        if (! $isMember) {
            $invitation->company->users()->attach($user->id, ['role' => $invitation->role->value]);
        }

        $user->forceFill(['current_company_id' => $invitation->company_id])->save();
        $invitation->update(['accepted_at' => now()]);

        return redirect()->route('dashboard')->with('success', "Welcome to {$invitation->company->name}!");
    }
}
