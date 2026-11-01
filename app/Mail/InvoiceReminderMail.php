<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Invoice;
use App\Services\Billing\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public int $daysOverdue
    ) {}

    public function envelope(): Envelope
    {
        $this->invoice->loadMissing('company');
        $companyName = $this->invoice->company->name ?? config('app.name');

        return new Envelope(
            subject: "Reminder: Invoice {$this->invoice->number} is {$this->daysOverdue} days overdue - {$companyName}",
        );
    }

    public function content(): Content
    {
        $this->invoice->loadMissing(['client', 'items', 'company']);

        return new Content(
            markdown: 'emails.invoices.reminder',
            with: [
                'invoice' => $this->invoice,
                'daysOverdue' => $this->daysOverdue,
                'publicUrl' => route('invoices.public', ['token' => $this->invoice->public_token]),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdfService = app(InvoicePdfService::class);
        $pdfContent = $pdfService->render($this->invoice);

        return [
            Attachment::fromData(fn () => $pdfContent, "{$this->invoice->number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
