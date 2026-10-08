<?php

namespace App\Http\Controllers\Api\Expenses;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreOfficeExpenseRequest;
use App\Http\Requests\UpdateOfficeExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\OfficeExpense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeExpenseController extends ApiController
{
    public function index(Request $request, ExpenseService $expenses): JsonResponse
    {
        $filters = $this->listFilters($request, [
            'category' => ['nullable', 'integer'],
        ]);

        return $this->paginated($expenses->paginateOffice($filters), ExpenseResource::class);
    }

    public function store(StoreOfficeExpenseRequest $request, ExpenseService $expenses): JsonResponse
    {
        return $this->success(new ExpenseResource($expenses->createOffice($request->validated(), $request->user())), 'Office expense created', 201);
    }

    public function show(OfficeExpense $officeExpense): JsonResponse
    {
        $officeExpense->load('category');

        return $this->success(new ExpenseResource($officeExpense), 'Office expense loaded');
    }

    public function update(UpdateOfficeExpenseRequest $request, OfficeExpense $officeExpense, ExpenseService $expenses): JsonResponse
    {
        return $this->success(new ExpenseResource($expenses->updateOffice($officeExpense, $request->validated(), $request->user())), 'Office expense updated');
    }

    public function destroy(Request $request, OfficeExpense $officeExpense, ExpenseService $expenses): JsonResponse
    {
        $expenses->deleteOffice($officeExpense, $request->user());

        return $this->success(null, 'Office expense deleted');
    }
}
