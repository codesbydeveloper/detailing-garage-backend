<?php

namespace App\Services;

use App\Models\Job;
use App\Models\Lead;
use App\Models\OfficeExpense;
use App\Models\PersonalExpense;
use App\Models\SalaryRecord;
use App\Models\VendorPayment;
use App\Support\Money;
use App\Support\SqlDate;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(private FinancialReportService $financial) {}

    public function summary(array $filters): array
    {
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;

        $leads = $this->leadCounts($from, $to);
        $byStatus = $this->groupedLeads('lead_statuses.name', 'lead_statuses');
        $byService = $this->simpleLeadGroup('service_interested');
        $leads['by_status'] = $this->namedCounts($byStatus);
        $leads['by_service'] = $this->namedCounts($byService);

        $jobs = $this->jobCounts($from, $to);
        $jobs['today'] = Job::query()->whereDate('date', now()->toDateString())->count();
        $jobs['upcoming_deliveries'] = Job::query()
            ->whereDate('delivery_date', '>=', now()->toDateString())
            ->whereIn('job_status', ['booked', 'in_progress'])
            ->count();
        $jobs['by_service'] = array_map(fn (array $row) => [
            'name' => $row['label'],
            'value' => $row['total'],
            'label' => $row['label'],
            'total' => $row['total'],
        ], $this->jobsByWorkType());

        $financial = $this->financial->summarize($from, $to);
        $charts = $this->namedCharts();
        $finance = array_merge($financial, [
            'total_revenue' => $financial['revenue'],
            'outstanding' => $financial['pending_payment'],
            'pending_payments' => $financial['pending_payment'],
            'salary' => $financial['staff_salary'],
            'gross_revenue' => $financial['revenue'],
            'total_expenses' => Money::add(
                $financial['office_expenses'],
                $financial['personal_expenses'],
                $financial['vendor_payments'],
                $financial['staff_salary'],
            ),
            'total_costs' => Money::add(
                $financial['product_cost'],
                $financial['labour_cost'],
                $financial['office_expenses'],
                $financial['personal_expenses'],
                $financial['vendor_payments'],
                $financial['staff_salary'],
            ),
            'revenue_by_month' => $charts['revenue_by_month'],
            'expense_by_month' => $charts['expense_by_month'],
            'profit_by_month' => $charts['profit_by_month'],
        ]);

        $attendance = $this->attendanceToday();
        $attendance['present_today'] = $attendance['present'];
        $attendance['absent_today'] = $attendance['absent'];
        $attendance['clocked_in'] = $attendance['currently_clocked_in'];
        $attendance['currently_working'] = $attendance['currently_clocked_in'];
        $attendance['on_leave'] = $attendance['leave'];
        $attendance['clocked_out'] = (int) DB::table('attendance')
            ->whereDate('date', now()->toDateString())
            ->whereNotNull('clock_out')
            ->count();
        $attendance['summary'] = [
            'present' => $attendance['present'],
            'absent' => $attendance['absent'],
            'late' => $attendance['late'],
            'leave' => $attendance['leave'],
            'currently_clocked_in' => $attendance['currently_clocked_in'],
        ];

        return [
            'leads' => $leads,
            'jobs' => $jobs,
            'financial' => $financial,
            'finance' => $finance,
            'attendance' => $attendance,
            'charts' => [
                'revenue_by_month' => $charts['revenue_by_month'],
                'expenses_by_month' => $charts['expense_by_month'],
                'profit_by_month' => $charts['profit_by_month'],
                'leads_by_status' => $byStatus,
                'leads_by_service' => $byService,
                'jobs_by_work_type' => $this->jobsByWorkType(),
                'attendance_summary' => $attendance['summary'],
            ],
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function namedCharts(): array
    {
        return [
            'revenue_by_month' => $this->namedSeries($this->monthlyJobMetric('final_amount')),
            'expense_by_month' => $this->namedSeries($this->expensesByMonth()),
            'profit_by_month' => $this->namedSeries($this->profitByMonth()),
        ];
    }

    private function leadCounts(?string $from, ?string $to): array
    {
        $query = Lead::query()
            ->join('lead_statuses', 'lead_statuses.id', '=', 'leads.lead_status_id')
            ->whereNull('leads.deleted_at');

        if ($from) {
            $query->whereDate('leads.created_time', '>=', $from);
        }

        if ($to) {
            $query->whereDate('leads.created_time', '<=', $to);
        }

        $rows = $query
            ->selectRaw('lead_statuses.slug as slug, COUNT(leads.id) as total')
            ->groupBy('lead_statuses.slug')
            ->pluck('total', 'slug');

        return [
            'total' => (int) $rows->sum(),
            'new' => (int) ($rows['new'] ?? 0),
            'contacted' => (int) ($rows['contacted'] ?? 0),
            'qualified' => (int) ($rows['qualified'] ?? 0),
            'converted' => (int) ($rows['converted'] ?? 0),
            'lost' => (int) ($rows['lost'] ?? 0),
        ];
    }

    private function jobCounts(?string $from, ?string $to): array
    {
        $query = Job::query();

        if ($from) {
            $query->whereDate('date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('date', '<=', $to);
        }

        $rows = (clone $query)
            ->selectRaw('job_status, COUNT(*) as total')
            ->groupBy('job_status')
            ->pluck('total', 'job_status');

        return [
            'total' => (int) $rows->sum(),
            'completed' => (int) ($rows['completed'] ?? 0),
            'delivered' => (int) ($rows['delivered'] ?? 0),
            'pending' => (int) ($rows['booked'] ?? 0) + (int) ($rows['in_progress'] ?? 0),
        ];
    }

    private function attendanceToday(): array
    {
        $rows = DB::table('attendance')
            ->whereDate('date', now()->toDateString())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $clockedIn = DB::table('attendance')
            ->whereDate('date', now()->toDateString())
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->count();

        return [
            'present' => (int) ($rows['present'] ?? 0),
            'absent' => (int) ($rows['absent'] ?? 0),
            'late' => (int) ($rows['late'] ?? 0),
            'leave' => (int) ($rows['leave'] ?? 0),
            'currently_clocked_in' => $clockedIn,
        ];
    }

    private function monthlyJobMetric(string $column): array
    {
        $expression = SqlDate::monthKey('date');
        $start = now()->startOfMonth()->subMonths(5);

        $rows = Job::query()
            ->where('job_status', '!=', 'cancelled')
            ->whereDate('date', '>=', $start->toDateString())
            ->selectRaw("{$expression} as month_key, COALESCE(SUM({$column}), 0) as total")
            ->groupByRaw($expression)
            ->pluck('total', 'month_key');

        return $this->fillMonths($rows);
    }

    private function expensesByMonth(): array
    {
        $start = now()->startOfMonth()->subMonths(5)->toDateString();
        $totals = [];

        foreach ([
            OfficeExpense::query()->whereDate('date', '>=', $start),
            PersonalExpense::query()->whereDate('date', '>=', $start),
            VendorPayment::query()->whereDate('date', '>=', $start),
        ] as $index => $query) {
            $column = $index === 2 ? 'amount' : 'price';
            if ($index === 2) {
                $column = 'amount';
            }
            $expression = SqlDate::monthKey('date');
            $rows = $query
                ->selectRaw("{$expression} as month_key, COALESCE(SUM({$column}), 0) as total")
                ->groupByRaw($expression)
                ->pluck('total', 'month_key');

            foreach ($rows as $month => $total) {
                $totals[$month] = Money::add($totals[$month] ?? 0, $total);
            }
        }

        return $this->fillMonths(collect($totals));
    }

    private function profitByMonth(): array
    {
        $revenue = collect($this->monthlyJobMetric('final_amount'))->keyBy('month');
        $product = collect($this->monthlyJobMetric('product_charge'))->keyBy('month');
        $labour = collect($this->monthlyJobMetric('labour_charge'))->keyBy('month');
        $expenses = collect($this->expensesByMonth())->keyBy('month');
        $salary = $this->salaryByMonth();

        $points = [];

        foreach ($revenue as $month => $point) {
            $net = Money::sub($point['total'], $product[$month]['total'] ?? 0);
            $net = Money::sub($net, $labour[$month]['total'] ?? 0);
            $net = Money::sub($net, $expenses[$month]['total'] ?? 0);
            $net = Money::sub($net, $salary[$month] ?? 0);
            $points[] = ['month' => $month, 'total' => $net];
        }

        return $points;
    }

    private function salaryByMonth(): array
    {
        $start = now()->startOfMonth()->subMonths(5);
        $rows = SalaryRecord::query()
            ->whereIn('status', ['generated', 'paid'])
            ->whereRaw('(year * 100 + month) >= ?', [((int) $start->year * 100) + (int) $start->month])
            ->get(['year', 'month', 'final_payable']);

        $totals = [];

        foreach ($rows as $row) {
            $key = sprintf('%04d-%02d', $row->year, $row->month);
            $totals[$key] = Money::add($totals[$key] ?? 0, $row->final_payable);
        }

        return $totals;
    }

    private function groupedLeads(string $labelColumn, string $table): array
    {
        return Lead::query()
            ->join($table, "{$table}.id", '=', 'leads.lead_status_id')
            ->whereNull('leads.deleted_at')
            ->selectRaw("{$labelColumn} as label, COUNT(leads.id) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->all();
    }

    private function simpleLeadGroup(string $column): array
    {
        return Lead::query()
            ->selectRaw("{$column} as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'total' => (int) $row->total])
            ->all();
    }

    private function jobsByWorkType(): array
    {
        return Job::query()
            ->join('work_types', 'work_types.id', '=', 'jobs.work_type_id')
            ->where('jobs.job_status', '!=', 'cancelled')
            ->selectRaw('work_types.name as label, COUNT(jobs.id) as total, COALESCE(SUM(jobs.final_amount), 0) as revenue')
            ->groupBy('work_types.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'total' => (int) $row->total,
                'revenue' => Money::of($row->revenue),
            ])
            ->all();
    }

    private function fillMonths($rows): array
    {
        $points = [];
        $start = now()->startOfMonth()->subMonths(5);

        for ($i = 0; $i < 6; $i++) {
            $key = $start->copy()->addMonths($i)->format('Y-m');
            $points[] = [
                'month' => $key,
                'total' => Money::of($rows[$key] ?? 0),
            ];
        }

        return $points;
    }

    /**
     * @param  list<array{month: string, total: string}>  $points
     * @return list<array<string, mixed>>
     */
    private function namedSeries(array $points): array
    {
        return array_map(fn (array $point) => [
            'month' => $point['month'],
            'name' => $point['month'],
            'value' => $point['total'],
            'total' => $point['total'],
        ], $points);
    }

    /**
     * @param  list<array{label: string, total: int}>  $rows
     * @return list<array<string, mixed>>
     */
    private function namedCounts(array $rows): array
    {
        return array_map(fn (array $row) => [
            'name' => $row['label'],
            'value' => $row['total'],
            'label' => $row['label'],
            'total' => $row['total'],
        ], $rows);
    }
}
