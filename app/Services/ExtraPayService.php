<?php

namespace App\Services;

use App\Models\ExtraPay;
use App\Models\User;
use App\Support\Money;
use App\Support\Query;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExtraPayService
{
    public function __construct(
        private ActivityLogService $activity,
        private PayrollService $payroll,
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = ExtraPay::query()->with('staff:id,full_name,staff_code');

        if (! empty($filters['staff_id'])) {
            $query->where('staff_id', $filters['staff_id']);
        }

        if (! empty($filters['reason'])) {
            $query->where('reason', $filters['reason']);
        }

        Query::whereDateRange($query, 'date', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        Query::sort($query, $filters['sort'] ?? null, $filters['direction'] ?? null, ['id', 'date', 'amount', 'reason', 'staff_id'], 'date');

        return $query->paginate(Query::perPage($filters['per_page'] ?? null))->withQueryString();
    }

    public function create(array $data, User $actor): ExtraPay
    {
        return DB::transaction(function () use ($data, $actor) {
            $record = ExtraPay::query()->create([
                'staff_id' => $data['staff_id'],
                'date' => $data['date'],
                'amount' => Money::of($data['amount']),
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->refresh($record, $actor);
            $record->load('staff');
            $this->activity->log('EXTRA_PAY_CREATED', 'salary', 'Extra pay created', $record, null, $this->activity->snapshot($record), 'success', $actor);

            return $record;
        });
    }

    public function update(ExtraPay $record, array $data, User $actor): ExtraPay
    {
        return DB::transaction(function () use ($record, $data, $actor) {
            $original = [$record->staff_id, $record->date?->year, $record->date?->month];
            $record->fill([
                'staff_id' => $data['staff_id'] ?? $record->staff_id,
                'date' => $data['date'] ?? $record->date,
                'amount' => array_key_exists('amount', $data) ? Money::of($data['amount']) : $record->amount,
                'reason' => $data['reason'] ?? $record->reason,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $record->notes,
            ]);
            $record->save();
            $this->refresh($record, $actor);
            if ($original[0]) {
                $this->payroll->refreshUnpaidSalary((int) $original[0], (int) $original[1], (int) $original[2], $actor);
            }
            [$old, $new] = $this->activity->changed($record);
            $this->activity->log('EXTRA_PAY_UPDATED', 'salary', 'Extra pay updated', $record, $old, $new, 'success', $actor);

            return $record->load('staff');
        });
    }

    public function delete(ExtraPay $record, User $actor): void
    {
        DB::transaction(function () use ($record, $actor) {
            $snapshot = $this->activity->snapshot($record);
            $staffId = $record->staff_id;
            $year = (int) $record->date->year;
            $month = (int) $record->date->month;
            $record->delete();
            $this->payroll->refreshUnpaidSalary($staffId, $year, $month, $actor);
            $this->activity->log('EXTRA_PAY_DELETED', 'salary', 'Extra pay deleted', $record, $snapshot, null, 'success', $actor);
        });
    }

    private function refresh(ExtraPay $record, User $actor): void
    {
        $this->payroll->refreshUnpaidSalary(
            (int) $record->staff_id,
            (int) $record->date->year,
            (int) $record->date->month,
            $actor,
        );
    }
}
