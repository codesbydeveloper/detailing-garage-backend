<?php

namespace App\Services;

use App\Domain\PayrollMath;
use App\Enums\SalaryStatus;
use App\Models\ExtraPay;
use App\Models\SalaryRecord;
use App\Models\Staff;
use App\Models\User;
use App\Support\Money;
use App\Support\Query;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function __construct(private ActivityLogService $activity) {}

    public function paginate(array $filters, ?User $restrictTo = null): LengthAwarePaginator
    {
        $query = SalaryRecord::query()->with('staff:id,full_name,staff_code,staff_category_id');

        if ($restrictTo) {
            $query->where('staff_id', $restrictTo->staff_id ?: 0);
        } elseif (! empty($filters['staff_id'])) {
            $query->where('staff_id', $filters['staff_id']);
        }

        if (! empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        if (! empty($filters['month'])) {
            $query->where('month', $filters['month']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        Query::sort($query, $filters['sort'] ?? null, $filters['direction'] ?? null, ['id', 'year', 'month', 'final_payable', 'status', 'staff_id'], 'id');

        return $query->paginate(Query::perPage($filters['per_page'] ?? null))->withQueryString();
    }

    /**
     * @return list<SalaryRecord>
     */
    public function generate(int $year, int $month, User $actor): array
    {
        return DB::transaction(function () use ($year, $month, $actor) {
            $records = [];

            Staff::query()
                ->whereIn('status', ['active', 'on_leave'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->each(function (Staff $staff) use ($year, $month, $actor, &$records) {
                    $records[] = $this->upsert($staff, $year, $month, $actor);
                });

            $this->activity->log(
                'SALARY_GENERATED',
                'salary',
                sprintf('Monthly salary generated for %04d-%02d', $year, $month),
                null,
                null,
                ['year' => $year, 'month' => $month, 'count' => count($records)],
                'success',
                $actor,
            );

            return $records;
        });
    }

    public function update(SalaryRecord $record, array $data, User $actor): SalaryRecord
    {
        return DB::transaction(function () use ($record, $data, $actor) {
            $locked = SalaryRecord::query()->lockForUpdate()->findOrFail($record->id);

            if ($locked->status === SalaryStatus::Paid->value) {
                throw ValidationException::withMessages([
                    'status' => ['Paid salary records cannot be edited.'],
                ]);
            }

            $extra = $this->extraPayTotal($locked->staff_id, $locked->year, $locked->month);
            $basic = Money::of($data['basic_salary'] ?? $locked->basic_salary);
            $bonus = Money::of($data['bonus'] ?? $locked->bonus);
            $deduction = Money::of($data['deduction'] ?? $locked->deduction);
            $advance = Money::of($data['advance'] ?? $locked->advance);

            $locked->fill([
                'basic_salary' => $basic,
                'extra_pay' => $extra,
                'bonus' => $bonus,
                'deduction' => $deduction,
                'advance' => $advance,
                'final_payable' => PayrollMath::payable($basic, $extra, $bonus, $deduction, $advance),
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $locked->notes,
                'status' => $data['status'] ?? $locked->status,
                'updated_by' => $actor->id,
            ]);
            $locked->save();

            [$old, $new] = $this->activity->changed($locked);
            $this->activity->log('SALARY_UPDATED', 'salary', 'Salary record updated', $locked, $old, $new, 'success', $actor);

            return $locked->load('staff:id,full_name,staff_code');
        });
    }

    public function markPaid(SalaryRecord $record, User $actor): SalaryRecord
    {
        return DB::transaction(function () use ($record, $actor) {
            $locked = SalaryRecord::query()->lockForUpdate()->findOrFail($record->id);

            if ($locked->status === SalaryStatus::Paid->value) {
                throw ValidationException::withMessages([
                    'status' => ['Salary is already marked as paid.'],
                ]);
            }

            $before = ['status' => $locked->status, 'paid_at' => $locked->paid_at];
            $locked->forceFill([
                'status' => SalaryStatus::Paid->value,
                'paid_at' => now(),
                'updated_by' => $actor->id,
            ])->save();

            $this->activity->log('SALARY_PAID', 'salary', 'Salary marked as paid', $locked, $before, [
                'status' => $locked->status,
                'paid_at' => $locked->paid_at?->toIso8601String(),
                'paid_salary' => $locked->final_payable,
            ], 'success', $actor);

            return $locked->load('staff:id,full_name,staff_code');
        });
    }

    public function extraPayTotal(int $staffId, int $year, int $month): string
    {
        return Money::of(
            ExtraPay::query()
                ->where('staff_id', $staffId)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->sum('amount')
        );
    }

    public function refreshUnpaidSalary(int $staffId, int $year, int $month, User $actor): void
    {
        $record = SalaryRecord::query()
            ->where('staff_id', $staffId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', '!=', SalaryStatus::Paid->value)
            ->first();

        if (! $record) {
            return;
        }

        $extra = $this->extraPayTotal($staffId, $year, $month);
        $record->extra_pay = $extra;
        $record->final_payable = PayrollMath::payable($record->basic_salary, $extra, $record->bonus, $record->deduction, $record->advance);
        $record->updated_by = $actor->id;
        $record->save();
    }

    private function upsert(Staff $staff, int $year, int $month, User $actor): SalaryRecord
    {
        $existing = SalaryRecord::query()
            ->where('staff_id', $staff->id)
            ->where('year', $year)
            ->where('month', $month)
            ->lockForUpdate()
            ->first();

        if ($existing && $existing->status === SalaryStatus::Paid->value) {
            return $existing->load('staff:id,full_name,staff_code');
        }

        $extra = $this->extraPayTotal($staff->id, $year, $month);
        $basic = Money::of($staff->basic_salary);
        $bonus = Money::of($existing->bonus ?? 0);
        $deduction = Money::of($existing->deduction ?? 0);
        $advance = Money::of($existing->advance ?? 0);
        $status = $existing?->status === SalaryStatus::Draft->value
            ? SalaryStatus::Draft->value
            : SalaryStatus::Generated->value;

        $payload = [
            'basic_salary' => $basic,
            'extra_pay' => $extra,
            'bonus' => $bonus,
            'deduction' => $deduction,
            'advance' => $advance,
            'final_payable' => PayrollMath::payable($basic, $extra, $bonus, $deduction, $advance),
            'status' => $status,
            'updated_by' => $actor->id,
        ];

        if (! $existing) {
            $record = SalaryRecord::query()->create([
                ...$payload,
                'staff_id' => $staff->id,
                'year' => $year,
                'month' => $month,
                'created_by' => $actor->id,
            ]);

            return $record->load('staff:id,full_name,staff_code');
        }

        $existing->update($payload);

        return $existing->refresh()->load('staff:id,full_name,staff_code');
    }
}
