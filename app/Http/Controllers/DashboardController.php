<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Reports\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportService $reportService): Response
    {
        $user = $request->user();
        $companyId = (int) $user->current_company_id;
        $role = $user->currentRole();

        $canViewRevenue = in_array($role, ['owner', 'accountant'], true);

        $data = $reportService->getDashboardStats($companyId, $canViewRevenue);

        return Inertia::render('dashboard', [
            'kpis' => $data['kpis'],
            'recentInvoices' => $data['recent_invoices'],
            'overdueInvoices' => $data['overdue_invoices'],
            'userRole' => $role,
        ]);
    }
}
