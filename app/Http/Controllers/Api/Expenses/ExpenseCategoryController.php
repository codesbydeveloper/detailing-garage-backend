<?php

namespace App\Http\Controllers\Api\Expenses;

use App\Enums\RecordStatus;
use App\Http\Controllers\Api\ApiController;
use App\Models\ExpenseCategory;
use App\Services\ActivityLogService;
use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = ExpenseCategory::query()->orderBy('name');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return $this->success($query->get(), 'Expense categories loaded');
    }

    public function store(Request $request, ActivityLogService $activity): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:expense_categories,name'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(RecordStatus::values())],
        ]);

        $category = ExpenseCategory::query()->create([
            ...$data,
            'status' => $data['status'] ?? 'active',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        $activity->log('EXPENSE_CATEGORY_CREATED', 'settings', 'Expense category created', $category, null, $activity->snapshot($category), 'success', $request->user());

        return $this->success($category, 'Expense category created', 201);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory, ActivityLogService $activity): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('expense_categories', 'name')->ignore($expenseCategory->id)],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(RecordStatus::values())],
        ]);

        $expenseCategory->fill($data);
        $expenseCategory->updated_by = $request->user()->id;
        $expenseCategory->save();
        [$old, $new] = $activity->changed($expenseCategory);
        $activity->log('EXPENSE_CATEGORY_UPDATED', 'settings', 'Expense category updated', $expenseCategory, $old, $new, 'success', $request->user());

        return $this->success($expenseCategory, 'Expense category updated');
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory, CatalogService $catalog): JsonResponse
    {
        $result = DB::transaction(fn () => $catalog->remove(
            $expenseCategory,
            'officeExpenses',
            'settings',
            'EXPENSE_CATEGORY_UPDATED',
            'EXPENSE_CATEGORY_DELETED',
            'Expense category',
            $request->user(),
        ));

        return $this->success(null, $result === 'deactivated' ? 'Expense category deactivated' : 'Expense category deleted');
    }
}
