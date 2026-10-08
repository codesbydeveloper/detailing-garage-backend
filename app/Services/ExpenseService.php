<?php

namespace App\Services;

use App\Models\OfficeExpense;
use App\Models\PersonalExpense;
use App\Models\User;
use App\Support\Money;
use App\Support\Query;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        private ActivityLogService $activity,
        private NumberGenerator $numbers,
    ) {}

    public function paginateOffice(array $filters): LengthAwarePaginator
    {
        $query = OfficeExpense::query()->with('category:id,name');
        $query->search($filters['search'] ?? null);

        if (! empty($filters['category'])) {
            $query->where('expense_category_id', $filters['category']);
        }

        Query::whereDateRange($query, 'date', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        Query::sort($query, $filters['sort'] ?? null, $filters['direction'] ?? null, ['id', 'date', 'price', 'expense', 'created_at'], 'date');

        return $query->paginate(Query::perPage($filters['per_page'] ?? null))->withQueryString();
    }

    public function createOffice(array $data, User $actor): OfficeExpense
    {
        return DB::transaction(function () use ($data, $actor) {
            $expense = OfficeExpense::query()->create([
                'expense_number' => $this->numbers->monthly(OfficeExpense::class, 'expense_number', 'OFF-'),
                'date' => $data['date'],
                'expense_category_id' => $data['expense_category_id'] ?? null,
                'expense' => $data['expense'],
                'price' => Money::of($data['price']),
                'description' => $data['description'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $expense->load('category');
            $this->activity->log('OFFICE_EXPENSE_CREATED', 'expenses', 'Office expense created', $expense, null, $this->activity->snapshot($expense), 'success', $actor);

            return $expense;
        });
    }

    public function updateOffice(OfficeExpense $expense, array $data, User $actor): OfficeExpense
    {
        $expense->fill([
            'date' => $data['date'] ?? $expense->date,
            'expense_category_id' => array_key_exists('expense_category_id', $data) ? $data['expense_category_id'] : $expense->expense_category_id,
            'expense' => $data['expense'] ?? $expense->expense,
            'price' => array_key_exists('price', $data) ? Money::of($data['price']) : $expense->price,
            'description' => array_key_exists('description', $data) ? $data['description'] : $expense->description,
            'updated_by' => $actor->id,
        ]);
        $expense->save();
        [$old, $new] = $this->activity->changed($expense);
        $this->activity->log('OFFICE_EXPENSE_UPDATED', 'expenses', 'Office expense updated', $expense, $old, $new, 'success', $actor);

        return $expense->load('category');
    }

    public function deleteOffice(OfficeExpense $expense, User $actor): void
    {
        $snapshot = $this->activity->snapshot($expense);
        $expense->delete();
        $this->activity->log('OFFICE_EXPENSE_DELETED', 'expenses', 'Office expense deleted', $expense, $snapshot, null, 'success', $actor);
    }

    public function paginatePersonal(array $filters): LengthAwarePaginator
    {
        $query = PersonalExpense::query()->with('staff:id,full_name,staff_code');
        $query->search($filters['search'] ?? null);

        if (! empty($filters['staff_id'])) {
            $query->where('staff_id', $filters['staff_id']);
        }

        Query::whereDateRange($query, 'date', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        Query::sort($query, $filters['sort'] ?? null, $filters['direction'] ?? null, ['id', 'date', 'price', 'expense', 'created_at'], 'date');

        return $query->paginate(Query::perPage($filters['per_page'] ?? null))->withQueryString();
    }

    public function createPersonal(array $data, User $actor): PersonalExpense
    {
        return DB::transaction(function () use ($data, $actor) {
            $expense = PersonalExpense::query()->create([
                'expense_number' => $this->numbers->monthly(PersonalExpense::class, 'expense_number', 'PER-'),
                'date' => $data['date'],
                'expense' => $data['expense'],
                'price' => Money::of($data['price']),
                'staff_id' => $data['staff_id'],
                'description' => $data['description'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $expense->load('staff');
            $this->activity->log('PERSONAL_EXPENSE_CREATED', 'expenses', 'Personal expense created', $expense, null, $this->activity->snapshot($expense), 'success', $actor);

            return $expense;
        });
    }

    public function updatePersonal(PersonalExpense $expense, array $data, User $actor): PersonalExpense
    {
        $expense->fill([
            'date' => $data['date'] ?? $expense->date,
            'expense' => $data['expense'] ?? $expense->expense,
            'price' => array_key_exists('price', $data) ? Money::of($data['price']) : $expense->price,
            'staff_id' => $data['staff_id'] ?? $expense->staff_id,
            'description' => array_key_exists('description', $data) ? $data['description'] : $expense->description,
            'updated_by' => $actor->id,
        ]);
        $expense->save();
        [$old, $new] = $this->activity->changed($expense);
        $this->activity->log('PERSONAL_EXPENSE_UPDATED', 'expenses', 'Personal expense updated', $expense, $old, $new, 'success', $actor);

        return $expense->load('staff');
    }

    public function deletePersonal(PersonalExpense $expense, User $actor): void
    {
        $snapshot = $this->activity->snapshot($expense);
        $expense->delete();
        $this->activity->log('PERSONAL_EXPENSE_DELETED', 'expenses', 'Personal expense deleted', $expense, $snapshot, null, 'success', $actor);
    }
}
