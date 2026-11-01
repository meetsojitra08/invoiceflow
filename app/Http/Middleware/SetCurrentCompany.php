<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use App\Services\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentCompany
{
    public function __construct(
        protected TenantManager $tenantManager,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null) {
            $companyId = $user->current_company_id;

            if ($companyId !== null && $user->companies()->where('companies.id', $companyId)->exists()) {
                $this->tenantManager->set($companyId);
            } else {
                /** @var Company|null $firstCompany */
                $firstCompany = $user->companies()->first();

                if ($firstCompany !== null) {
                    $user->forceFill(['current_company_id' => $firstCompany->id])->save();
                    $this->tenantManager->set($firstCompany->id);
                } else {
                    $this->tenantManager->clear();
                }
            }
        } else {
            $this->tenantManager->clear();
        }

        return $next($request);
    }
}
