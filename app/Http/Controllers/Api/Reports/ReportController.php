<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Api\ApiController;
use App\Services\DashboardService;
use App\Services\FinancialReportService;
use App\Services\LeadReportService;
use App\Services\StaffReportService;
use App\Support\Period;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends ApiController
{
    public function financial(Request $request, FinancialReportService $reports): JsonResponse
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $summary = $reports->summarize($filters['date_from'] ?? null, $filters['date_to'] ?? null);
        $charts = app(DashboardService::class)->namedCharts();
        $summary['pending_payments'] = $summary['pending_payment'];
        $summary['salary'] = $summary['staff_salary'];
        $summary['revenue_by_month'] = $charts['revenue_by_month'];
        $summary['expense_by_month'] = $charts['expense_by_month'];
        $summary['profit_by_month'] = $charts['profit_by_month'];

        return $this->success($summary, 'Financial report loaded');
    }

    public function index(Request $request, FinancialReportService $financial, LeadReportService $leads, StaffReportService $staff): JsonResponse
    {
        Period::normalize($request);
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        return $this->success([
            'financial' => $financial->summarize($filters['date_from'] ?? null, $filters['date_to'] ?? null),
            'leads' => $leads->summarize($filters['date_from'] ?? null, $filters['date_to'] ?? null),
            'staff' => $staff->summarize(
                (int) ($filters['year'] ?? now()->year),
                (int) ($filters['month'] ?? now()->month),
            ),
        ], 'Reports loaded');
    }

    public function leads(Request $request, LeadReportService $reports): JsonResponse
    {
        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        return $this->success($reports->summarize($filters['date_from'] ?? null, $filters['date_to'] ?? null), 'Lead report loaded');
    }

    public function staff(Request $request, StaffReportService $reports): JsonResponse
    {
        Period::normalize($request);
        $filters = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        return $this->success($reports->summarize(
            (int) ($filters['year'] ?? now()->year),
            (int) ($filters['month'] ?? now()->month),
        ), 'Staff report loaded');
    }
}
