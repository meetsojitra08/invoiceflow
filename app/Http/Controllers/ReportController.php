<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Reports\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reportService): Response
    {
        $user = $request->user();
        $role = $user->currentRole();

        if (! in_array($role, ['owner', 'accountant'], true)) {
            abort(403, 'Only owners and accountants can view reports.');
        }

        $companyId = (int) $user->current_company_id;

        $fromInput = $request->input('from');
        $toInput = $request->input('to');

        $from = $fromInput ? Carbon::parse($fromInput)->startOfDay() : Carbon::now()->subMonths(11)->startOfMonth();
        $to = $toInput ? Carbon::parse($toInput)->endOfDay() : Carbon::now()->endOfMonth();

        // Enforce max 5 years limit
        if ($from->diffInYears($to) > 5) {
            $from = $to->copy()->subYears(5)->startOfDay();
        }

        $reportsData = $reportService->getReportsData($companyId, $from, $to);

        return Inertia::render('reports/index', [
            'reportsData' => $reportsData,
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }

    public function exportInvoices(Request $request, ReportService $reportService): StreamedResponse
    {
        $user = $request->user();
        $role = $user->currentRole();

        if (! in_array($role, ['owner', 'accountant'], true)) {
            abort(403, 'Unauthorized.');
        }

        $companyId = (int) $user->current_company_id;
        $from = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
        $to = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : null;

        return $reportService->exportInvoicesCsv($companyId, $from, $to);
    }

    public function exportPayments(Request $request, ReportService $reportService): StreamedResponse
    {
        $user = $request->user();
        $role = $user->currentRole();

        if (! in_array($role, ['owner', 'accountant'], true)) {
            abort(403, 'Unauthorized.');
        }

        $companyId = (int) $user->current_company_id;
        $from = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : null;
        $to = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : null;

        return $reportService->exportPaymentsCsv($companyId, $from, $to);
    }
}
