<?php

namespace App\Services;

use App\Models\ExtraPay;
use App\Models\Job;
use App\Models\OfficeExpense;
use App\Models\PersonalExpense;
use App\Models\SalaryRecord;
use App\Models\VendorPayment;
use App\Support\Money;

class FinancialReportService
{
    public function summarize(?string $from, ?string $to): array
    {
        $jobs = Job::query()->where('job_status', '!=', 'cancelled');

        if ($from) {
            $jobs->whereDate('date', '>=', $from);
        }

        if ($to) {
            $jobs->whereDate('date', '<=', $to);
        }

        $totals = (clone $jobs)
            ->selectRaw('
                COALESCE(SUM(final_amount), 0) as revenue,
                COALESCE(SUM(amount_paid), 0) as amount_received,
                COALESCE(SUM(pending_pay), 0) as pending_payment,
                COALESCE(SUM(product_charge), 0) as product_cost,
                COALESCE(SUM(labour_charge), 0) as labour_cost
            ')
            ->first();

        $office = $this->sum(OfficeExpense::query(), 'price', $from, $to);
        $personal = $this->sum(PersonalExpense::query(), 'price', $from, $to);
        $vendors = $this->sum(VendorPayment::query(), 'amount', $from, $to);
        $extra = $this->sum(ExtraPay::query(), 'amount', $from, $to);
        $salary = $this->salary($from, $to);

        $revenue = Money::of($totals->revenue ?? 0);
        $product = Money::of($totals->product_cost ?? 0);
        $labour = Money::of($totals->labour_cost ?? 0);
        $gross = Money::sub(Money::sub($revenue, $product), $labour);
        $net = Money::sub(Money::sub(Money::sub(Money::sub($gross, $office), $personal), $vendors), $salary);

        return [
            'revenue' => $revenue,
            'amount_received' => Money::of($totals->amount_received ?? 0),
            'pending_payment' => Money::of($totals->pending_payment ?? 0),
            'product_cost' => $product,
            'labour_cost' => $labour,
            'office_expenses' => $office,
            'personal_expenses' => $personal,
            'vendor_payments' => $vendors,
            'staff_salary' => $salary,
            'extra_pay' => $extra,
            'gross_profit' => $gross,
            'net_profit' => $net,
            'pending_customer_payments' => Money::of($totals->pending_payment ?? 0),
        ];
    }

    private function sum($query, string $column, ?string $from, ?string $to): string
    {
        if ($from) {
            $query->whereDate('date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('date', '<=', $to);
        }

        return Money::of($query->sum($column));
    }

    private function salary(?string $from, ?string $to): string
    {
        $query = SalaryRecord::query()->whereIn('status', ['generated', 'paid']);

        if ($from) {
            $start = \Carbon\Carbon::parse($from);
            $query->whereRaw('(year * 100 + month) >= ?', [($start->year * 100) + $start->month]);
        }

        if ($to) {
            $end = \Carbon\Carbon::parse($to);
            $query->whereRaw('(year * 100 + month) <= ?', [($end->year * 100) + $end->month]);
        }

        return Money::of($query->sum('final_payable'));
    }
}
