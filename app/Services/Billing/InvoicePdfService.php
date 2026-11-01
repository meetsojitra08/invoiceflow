<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Services\Tenancy\TenantManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class InvoicePdfService
{
    public function render(Invoice $invoice): string
    {
        return app(TenantManager::class)->execute($invoice->company_id, function () use ($invoice): string {
            $invoice->loadMissing(['client', 'items', 'company']);

            $pdf = Pdf::loadView('pdf.invoice', [
                'invoice' => $invoice,
            ])->setPaper('a4', 'portrait');

            return $pdf->output();
        });
    }

    public function download(Invoice $invoice): Response
    {
        $content = $this->render($invoice);
        $filename = "{$invoice->number}.pdf";

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function stream(Invoice $invoice): Response
    {
        $content = $this->render($invoice);
        $filename = "{$invoice->number}.pdf";

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }
}
