<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Company;

class InvoiceNumberGenerator
{
    /**
     * Generate the next sequential invoice number for the company.
     * Must be called within a database transaction.
     */
    public function generate(int $companyId): string
    {
        /** @var Company $company */
        $company = Company::where('id', $companyId)->lockForUpdate()->firstOrFail();

        $prefix = $company->invoice_prefix ?: 'INV';
        $number = sprintf('%s-%04d', $prefix, $company->next_invoice_number);

        $company->increment('next_invoice_number');

        return $number;
    }
}
