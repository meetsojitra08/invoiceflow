<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $currentCompany = null;
        $companies = [];
        $role = null;

        if ($user !== null) {
            $userCompanies = $user->companies;
            $currentCompanyModel = $userCompanies->firstWhere('id', $user->current_company_id) ?? $userCompanies->first();

            if ($currentCompanyModel !== null) {
                $currentCompany = [
                    'id' => $currentCompanyModel->id,
                    'name' => $currentCompanyModel->name,
                    'currency' => $currentCompanyModel->currency,
                    'tax_number' => $currentCompanyModel->tax_number,
                    'invoice_prefix' => $currentCompanyModel->invoice_prefix,
                ];
                $role = $currentCompanyModel->pivot?->role;
            }

            $companies = $userCompanies->map(fn ($company) => [
                'id' => $company->id,
                'name' => $company->name,
                'role' => $company->pivot?->role,
            ])->values()->all();
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ] : null,
                'company' => $currentCompany,
                'companies' => $companies,
                'role' => $role,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
