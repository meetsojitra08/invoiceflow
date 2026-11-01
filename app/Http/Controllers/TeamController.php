<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\CompanyInvitation;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request, TenantManager $tenantManager): Response
    {
        $company = $tenantManager->get();
        if (! $company) {
            abort(404, 'Workspace not found.');
        }

        $this->authorize('viewTeam', $company);

        $members = $company->users()
            ->orderByPivot('created_at')
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->pivot?->role,
                'joined_at' => $user->pivot?->created_at?->toFormattedDateString(),
                'is_current_user' => $user->id === $request->user()?->id,
            ]);

        $invitations = $company->invitations()
            ->pending()
            ->latest()
            ->get(['id', 'email', 'role', 'token', 'expires_at', 'created_at'])
            ->map(fn (CompanyInvitation $invitation) => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'role_label' => $invitation->role->label(),
                'expires_at' => $invitation->expires_at->toFormattedDateString(),
                'created_at' => $invitation->created_at->toFormattedDateString(),
                'invite_url' => route('invitations.accept', ['token' => $invitation->token]),
            ]);

        return Inertia::render('team/index', [
            'members' => $members,
            'invitations' => $invitations,
            'can' => [
                'manageTeam' => $request->user()?->can('manageTeam', $company) ?? false,
            ],
            'currentUser' => [
                'id' => $request->user()?->id,
                'role' => $request->user()?->currentRole(),
            ],
        ]);
    }

    public function invite(Request $request, TenantManager $tenantManager): RedirectResponse
    {
        $company = $tenantManager->get();
        if (! $company) {
            abort(404, 'Workspace not found.');
        }

        $this->authorize('invite', $company);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        // Check if user is already a member
        $isMember = $company->users()->where('users.email', $validated['email'])->exists();
        if ($isMember) {
            return back()->withErrors(['email' => 'User is already a member of this workspace.']);
        }

        // Check if there is already a pending invitation for this email
        $existing = $company->invitations()->pending()->where('email', $validated['email'])->first();
        if ($existing) {
            return back()->withErrors(['email' => 'An active invitation already exists for this email address.']);
        }

        $invitation = $company->invitations()->create([
            'email' => $validated['email'],
            'role' => $validated['role'],
            'token' => Str::random(40),
            'expires_at' => now()->addHours(48),
        ]);

        $inviteUrl = route('invitations.accept', ['token' => $invitation->token]);

        return back()->with([
            'success' => 'Invitation link created successfully.',
            'invitation_url' => $inviteUrl,
        ]);
    }

    public function updateRole(Request $request, User $member, TenantManager $tenantManager): RedirectResponse
    {
        $company = $tenantManager->get();
        if (! $company) {
            abort(404, 'Workspace not found.');
        }

        $this->authorize('updateMemberRole', [$company, $member]);

        $validated = $request->validate([
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        $newRole = $validated['role'];
        $currentRole = $member->roleIn($company);

        // Last owner protection
        if ($currentRole === Role::OWNER->value && $newRole !== Role::OWNER->value && $company->ownersCount() <= 1) {
            return back()->withErrors(['role' => 'Cannot demote the last owner of the workspace.']);
        }

        $company->users()->updateExistingPivot($member->id, ['role' => $newRole]);

        return back()->with('success', "{$member->name}'s role updated to {$newRole}.");
    }

    public function removeMember(User $member, TenantManager $tenantManager): RedirectResponse
    {
        $company = $tenantManager->get();
        if (! $company) {
            abort(404, 'Workspace not found.');
        }

        $this->authorize('removeMember', [$company, $member]);

        // Last owner protection
        $targetRole = $member->roleIn($company);
        if ($targetRole === Role::OWNER->value && $company->ownersCount() <= 1) {
            return back()->withErrors(['error' => 'Cannot remove the last owner of the workspace.']);
        }

        $company->users()->detach($member->id);

        if ($member->current_company_id === $company->id) {
            $otherCompany = $member->companies()->first();
            $member->forceFill(['current_company_id' => $otherCompany?->id])->save();
        }

        return back()->with('success', "{$member->name} has been removed from the workspace.");
    }
}
