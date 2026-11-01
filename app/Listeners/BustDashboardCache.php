<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Events\InvoiceSent;
use App\Events\PaymentRecorded;
use Illuminate\Support\Facades\Cache;

class BustDashboardCache
{
    /**
     * Handle the event and clear tenant-scoped dashboard caches.
     */
    public function handle(InvoiceSent|InvoicePaid|PaymentRecorded $event): void
    {
        $companyId = match (true) {
            $event instanceof InvoiceSent, $event instanceof InvoicePaid => $event->invoice->company_id,
            $event instanceof PaymentRecorded => $event->payment->company_id,
            default => null,
        };

        if ($companyId) {
            Cache::forget("dashboard_stats_{$companyId}");
            Cache::forget("dashboard_revenue_{$companyId}");
        }
    }
}
