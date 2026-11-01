<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Company;
use RuntimeException;

class TenantManager
{
    private ?int $companyId = null;

    private ?Company $company = null;

    public function set(Company|int|null $company): void
    {
        if ($company instanceof Company) {
            $this->companyId = $company->id;
            $this->company = $company;
        } elseif (is_int($company)) {
            $this->companyId = $company;
            $this->company = null;
        } else {
            $this->companyId = null;
            $this->company = null;
        }
    }

    public function id(): ?int
    {
        return $this->companyId;
    }

    public function idOrFail(): int
    {
        if ($this->companyId === null) {
            throw new RuntimeException('No tenant context set.');
        }

        return $this->companyId;
    }

    public function get(): ?Company
    {
        if ($this->company !== null) {
            return $this->company;
        }

        if ($this->companyId !== null) {
            $this->company = Company::find($this->companyId);
        }

        return $this->company;
    }

    public function clear(): void
    {
        $this->companyId = null;
        $this->company = null;
    }

    /**
     * @template T
     *
     * @param  callable(int): T  $callback
     * @return T
     */
    public function execute(Company|int $company, callable $callback): mixed
    {
        $previousCompanyId = $this->companyId;
        $previousCompany = $this->company;

        $this->set($company);

        try {
            return $callback($this->idOrFail());
        } finally {
            $this->companyId = $previousCompanyId;
            $this->company = $previousCompany;
        }
    }
}
