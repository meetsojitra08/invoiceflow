<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Services\Tenancy\TenantManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 600];

    public function __construct(
        public Invoice $invoice,
        public string $recipientEmail,
        public ?string $customMessage = null
    ) {}

    public function handle(TenantManager $tenantManager): void
    {
        $tenantManager->execute($this->invoice->company_id, function (): void {
            Mail::to($this->recipientEmail)
                ->send(new InvoiceMail($this->invoice, $this->customMessage));
        });
    }
}
