<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\Tenancy\TenantManager;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:mark-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark sent and partially paid invoices past their due date as overdue';

    /**
     * Execute the console command.
     */
    public function handle(TenantManager $tenantManager): int
    {
        $this->info('Checking for overdue invoices across all companies...');
        $totalMarked = 0;

        Company::query()->chunkById(100, function ($companies) use ($tenantManager, &$totalMarked): void {
            foreach ($companies as $company) {
                $tenantManager->execute($company->id, function () use (&$totalMarked): void {
                    $today = Carbon::today()->toDateString();

                    $invoices = Invoice::query()
                        ->whereIn('status', [InvoiceStatus::SENT->value, InvoiceStatus::PARTIALLY_PAID->value])
                        ->where('due_date', '<', $today)
                        ->get();

                    foreach ($invoices as $invoice) {
                        $invoice->update([
                            'status' => InvoiceStatus::OVERDUE,
                        ]);
                        $totalMarked++;
                    }
                });
            }
        });

        $this->info("Completed. Marked {$totalMarked} invoice(s) as overdue.");

        return Command::SUCCESS;
    }
}
