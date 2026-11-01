<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompanySwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        /** @var User $user */
        $user = $request->user();

        /** @var Company|null $company */
        $company = $user->companies()->where('companies.id', $validated['company_id'])->first();

        if ($company === null) {
            abort(403, 'You do not belong to this company.');
        }

        $user->forceFill(['current_company_id' => $company->id])->save();

        return back()->with('success', "Switched company to {$company->name}.");
    }
}
