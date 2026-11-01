<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceReminderMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceReminder;
use App\Services\Tenancy\TenantManager;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automated email reminders for overdue invoices at 3, 7, and 14 day milestones';

    /**
     * The milestones in days past due date to trigger reminders.
     *
     * @var array<int, int>
     */
    protected const MILESTONES = [3, 7, 14];

    /**
     * Execute the console command.
     */
    public function handle(TenantManager $tenantManager): int
    {
        $this->info('Checking for overdue invoice reminders to send...');
        $totalSent = 0;

        Company::query()->chunkById(100, function ($companies) use ($tenantManager, &$totalSent): void {
            foreach ($companies as $company) {
                $tenantManager->execute($company->id, function () use ($company, &$totalSent): void {
                    $today = Carbon::today();

                    $invoices = Invoice::query()
                        ->with(['client', 'reminders'])
                        ->whereIn('status', [
                            InvoiceStatus::SENT->value,
                            InvoiceStatus::PARTIALLY_PAID->value,
                            InvoiceStatus::OVERDUE->value,
                        ])
                        ->where('due_date', '<', $today->toDateString())
                        ->get();

                    foreach ($invoices as $invoice) {
                        // Skip if client opted out or has no email
                        if (! $invoice->client || ! $invoice->client->send_reminders || empty($invoice->client->email)) {
                            continue;
                        }

                        // Skip if already paid or has no balance
                        if ($invoice->balance() <= 0) {
                            continue;
                        }

                        $daysOverdue = (int) $invoice->due_date->diffInDays($today, false);

                        foreach (self::MILESTONES as $milestone) {
                            if ($daysOverdue >= $milestone) {
                                $alreadySent = $invoice->reminders->contains('days_overdue', $milestone);

                                if (! $alreadySent) {
                                    InvoiceReminder::create([
                                        'company_id' => $company->id,
                                        'invoice_id' => $invoice->id,
                                        'days_overdue' => $milestone,
                                        'sent_at' => Carbon::now(),
                                    ]);

                                    Mail::to($invoice->client->email)->send(
                                        new InvoiceReminderMail($invoice, $milestone)
                                    );

                                    $totalSent++;
                                }
                            }
                        }
                    }
                });
            }
        });

        $this->info("Completed. Sent {$totalSent} reminder email(s).");

        return Command::SUCCESS;
    }
}
