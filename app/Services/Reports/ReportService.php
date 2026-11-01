<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Invoice;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /**
     * Get aggregated stats for the dashboard, cached per company.
     *
     * @return array<string, mixed>
     */
    public function getDashboardStats(int $companyId, bool $canViewRevenue = true): array
    {
        $cacheKey = "dashboard_stats_{$companyId}";

        $stats = Cache::remember($cacheKey, 3600, function () use ($companyId): array {
            $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
            $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

            // 1. Revenue this month (payments received in cash basis)
            $revenueThisMonth = (int) DB::table('payments')
                ->where('company_id', $companyId)
                ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            // 2. Total outstanding for sent, partially_paid, and overdue invoices
            $totalOutstanding = (int) DB::table('invoices')
                ->where('company_id', $companyId)
                ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
                ->sum(DB::raw('total - amount_paid'));

            // 3. Overdue amount and count
            $overdueStats = DB::table('invoices')
                ->where('company_id', $companyId)
                ->where('status', 'overdue')
                ->selectRaw('COUNT(*)::int as count, COALESCE(SUM(total - amount_paid), 0)::bigint as amount')
                ->first();

            // 4. Invoices sent this month
            $invoicesSentThisMonth = DB::table('invoices')
                ->where('company_id', $companyId)
                ->whereBetween('sent_at', [
                    Carbon::now()->startOfMonth()->toDateTimeString(),
                    Carbon::now()->endOfMonth()->toDateTimeString(),
                ])
                ->count();

            return [
                'revenue_this_month' => $revenueThisMonth,
                'total_outstanding' => $totalOutstanding,
                'overdue_amount' => (int) ($overdueStats->amount ?? 0),
                'overdue_count' => (int) ($overdueStats->count ?? 0),
                'invoices_sent_this_month' => (int) $invoicesSentThisMonth,
            ];
        });

        // 5. Recent invoices (10) and overdue invoices (top 5) - not cached long-term to ensure immediate fresh links
        $recentInvoices = Invoice::query()
            ->withoutGlobalScopes()
            ->with(['client:id,name,email'])
            ->where('company_id', $companyId)
            ->latest('issue_date')
            ->latest('id')
            ->limit(10)
            ->get();

        $overdueInvoices = Invoice::query()
            ->withoutGlobalScopes()
            ->with(['client:id,name,email'])
            ->where('company_id', $companyId)
            ->where('status', 'overdue')
            ->orderBy('due_date', 'asc')
            ->limit(5)
            ->get();

        return [
            'kpis' => [
                'revenue_this_month' => $canViewRevenue ? $stats['revenue_this_month'] : null,
                'total_outstanding' => $canViewRevenue ? $stats['total_outstanding'] : null,
                'overdue_amount' => $canViewRevenue ? $stats['overdue_amount'] : null,
                'overdue_count' => $stats['overdue_count'],
                'invoices_sent_this_month' => $stats['invoices_sent_this_month'],
                'can_view_revenue' => $canViewRevenue,
            ],
            'recent_invoices' => $recentInvoices,
            'overdue_invoices' => $overdueInvoices,
        ];
    }

    /**
     * Get aggregated reporting data for a date range, cached per company.
     *
     * @return array<string, mixed>
     */
    public function getReportsData(int $companyId, CarbonInterface $from, CarbonInterface $to): array
    {
        $cacheKey = "reports_{$companyId}_{$from->toDateString()}_{$to->toDateString()}";

        return Cache::remember($cacheKey, 3600, function () use ($companyId, $from, $to): array {
            $fromDate = $from->toDateString();
            $toDate = $to->toDateString();

            // 1. Revenue by month
            $revenueByMonth = DB::table('payments')
                ->selectRaw("to_char(date_trunc('month', paid_at), 'YYYY-MM') as month, to_char(date_trunc('month', paid_at), 'Mon YYYY') as label, COALESCE(SUM(amount), 0)::bigint as revenue")
                ->where('company_id', $companyId)
                ->whereBetween('paid_at', [$fromDate, $toDate])
                ->groupByRaw("date_trunc('month', paid_at)")
                ->orderByRaw("date_trunc('month', paid_at) ASC")
                ->get()
                ->map(fn ($row) => [
                    'month' => $row->month,
                    'label' => $row->label,
                    'revenue' => (int) $row->revenue,
                ])
                ->all();

            // 2. Invoice status breakdown
            $statusBreakdown = DB::table('invoices')
                ->selectRaw('status, COUNT(*)::int as count, COALESCE(SUM(total), 0)::bigint as total_amount')
                ->where('company_id', $companyId)
                ->whereBetween('issue_date', [$fromDate, $toDate])
                ->where('status', '!=', 'void')
                ->groupBy('status')
                ->get()
                ->map(fn ($row) => [
                    'status' => $row->status,
                    'count' => (int) $row->count,
                    'total_amount' => (int) $row->total_amount,
                ])
                ->all();

            // 3. Top 10 clients by billed amount
            $topClients = DB::table('clients')
                ->join('invoices', 'invoices.client_id', '=', 'clients.id')
                ->selectRaw('clients.id, clients.name, COUNT(invoices.id)::int as invoice_count, COALESCE(SUM(invoices.total), 0)::bigint as total_billed')
                ->where('invoices.company_id', $companyId)
                ->whereBetween('invoices.issue_date', [$fromDate, $toDate])
                ->where('invoices.status', '!=', 'void')
                ->groupBy('clients.id', 'clients.name')
                ->orderByDesc('total_billed')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => $row->name,
                    'invoice_count' => (int) $row->invoice_count,
                    'total_billed' => (int) $row->total_billed,
                ])
                ->all();

            // 4. Receivables aging buckets (0-30, 31-60, 61-90, 90+ days past due)
            $bucketsRaw = DB::table('invoices')
                ->selectRaw("
                    CASE 
                        WHEN CURRENT_DATE - due_date <= 30 THEN '0-30 days'
                        WHEN CURRENT_DATE - due_date <= 60 THEN '31-60 days'
                        WHEN CURRENT_DATE - due_date <= 90 THEN '61-90 days'
                        ELSE '90+ days'
                    END as bucket,
                    COUNT(*)::int as count,
                    COALESCE(SUM(total - amount_paid), 0)::bigint as amount
                ")
                ->where('company_id', $companyId)
                ->whereIn('status', ['sent', 'partially_paid', 'overdue'])
                ->whereRaw('(total - amount_paid) > 0')
                ->groupBy('bucket')
                ->get()
                ->keyBy('bucket');

            $buckets = [
                '0-30 days' => ['bucket' => '0-30 days', 'count' => 0, 'amount' => 0],
                '31-60 days' => ['bucket' => '31-60 days', 'count' => 0, 'amount' => 0],
                '61-90 days' => ['bucket' => '61-90 days', 'count' => 0, 'amount' => 0],
                '90+ days' => ['bucket' => '90+ days', 'count' => 0, 'amount' => 0],
            ];

            foreach ($bucketsRaw as $bucketName => $data) {
                if (isset($buckets[$bucketName])) {
                    $buckets[$bucketName]['count'] = (int) $data->count;
                    $buckets[$bucketName]['amount'] = (int) $data->amount;
                }
            }

            return [
                'revenue_by_month' => $revenueByMonth,
                'status_breakdown' => $statusBreakdown,
                'top_clients' => $topClients,
                'aging_buckets' => array_values($buckets),
            ];
        });
    }

    /**
     * Stream invoices export as CSV.
     */
    public function exportInvoicesCsv(int $companyId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): StreamedResponse
    {
        $filename = 'invoices-export-'.Carbon::now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($companyId, $from, $to): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // Write CSV headers
            fputcsv($handle, [
                'Invoice Number',
                'Client Name',
                'Status',
                'Issue Date',
                'Due Date',
                'Currency',
                'Subtotal',
                'Discount Total',
                'Tax Total',
                'Total',
                'Amount Paid',
                'Balance Due',
                'Sent At',
                'Paid At',
            ]);

            $query = Invoice::query()
                ->withoutGlobalScopes()
                ->with('client:id,name')
                ->where('company_id', $companyId)
                ->orderByDesc('issue_date')
                ->orderByDesc('id');

            if ($from && $to) {
                $query->whereBetween('issue_date', [$from->toDateString(), $to->toDateString()]);
            }

            $query->chunk(200, function ($invoices) use ($handle): void {
                foreach ($invoices as $invoice) {
                    fputcsv($handle, [
                        $invoice->number,
                        $invoice->client?->name ?? 'N/A',
                        $invoice->status->label(),
                        $invoice->issue_date->format('Y-m-d'),
                        $invoice->due_date->format('Y-m-d'),
                        $invoice->currency,
                        number_format($invoice->subtotal / 100, 2, '.', ''),
                        number_format($invoice->discount_total / 100, 2, '.', ''),
                        number_format($invoice->tax_total / 100, 2, '.', ''),
                        number_format($invoice->total / 100, 2, '.', ''),
                        number_format($invoice->amount_paid / 100, 2, '.', ''),
                        number_format($invoice->balance() / 100, 2, '.', ''),
                        $invoice->sent_at?->toDateTimeString() ?? '',
                        $invoice->paid_at?->toDateTimeString() ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Stream payments export as CSV.
     */
    public function exportPaymentsCsv(int $companyId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): StreamedResponse
    {
        $filename = 'payments-export-'.Carbon::now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($companyId, $from, $to): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // Write CSV headers
            fputcsv($handle, [
                'Payment Date',
                'Invoice Number',
                'Client Name',
                'Method',
                'Reference',
                'Currency',
                'Amount',
                'Notes',
            ]);

            $query = DB::table('payments')
                ->join('invoices', 'invoices.id', '=', 'payments.invoice_id')
                ->leftJoin('clients', 'clients.id', '=', 'invoices.client_id')
                ->where('payments.company_id', $companyId)
                ->select([
                    'payments.paid_at',
                    'invoices.number as invoice_number',
                    'clients.name as client_name',
                    'payments.method',
                    'payments.reference',
                    'payments.currency',
                    'payments.amount',
                    'payments.notes',
                ])
                ->orderByDesc('payments.paid_at')
                ->orderByDesc('payments.id');

            if ($from && $to) {
                $query->whereBetween('payments.paid_at', [$from->toDateString(), $to->toDateString()]);
            }

            $query->chunk(200, function ($payments) use ($handle): void {
                foreach ($payments as $payment) {
                    fputcsv($handle, [
                        $payment->paid_at,
                        $payment->invoice_number,
                        $payment->client_name ?? 'N/A',
                        ucwords(str_replace('_', ' ', $payment->method)),
                        $payment->reference ?? '',
                        $payment->currency,
                        number_format($payment->amount / 100, 2, '.', ''),
                        $payment->notes ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }
}
