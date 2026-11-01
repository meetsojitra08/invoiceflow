<?php

declare(strict_types=1);

namespace App\Support\Concerns;

use App\Models\Company;
use App\Services\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $query): void {
            $query->where($query->getModel()->getTable().'.company_id', app(TenantManager::class)->idOrFail());
        });

        static::creating(function (Model $model): void {
            $model->company_id ??= app(TenantManager::class)->idOrFail();
        });
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
