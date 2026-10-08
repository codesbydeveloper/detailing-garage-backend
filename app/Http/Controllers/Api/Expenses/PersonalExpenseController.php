<?php

namespace App\Http\Controllers\Api\Expenses;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StorePersonalExpenseRequest;
use App\Http\Requests\UpdatePersonalExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\PersonalExpense;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PersonalExpenseController extends ApiController
{
    public function index(Request $request, ExpenseService $expenses): JsonResponse
    {
        $filters = $this->listFilters($request, [
            'staff_id' => ['nullable', 'integer'],
        ]);

        return $this->paginated($expenses->paginatePersonal($filters), ExpenseResource::class);
    }

    public function store(StorePersonalExpenseRequest $request, ExpenseService $expenses): JsonResponse
    {
        return $this->success(new ExpenseResource($expenses->createPersonal($request->validated(), $request->user())), 'Personal expense created', 201);
    }

    public function show(PersonalExpense $personalExpense): JsonResponse
    {
        $personalExpense->load('staff');

        return $this->success(new ExpenseResource($personalExpense), 'Personal expense loaded');
    }

    public function update(UpdatePersonalExpenseRequest $request, PersonalExpense $personalExpense, ExpenseService $expenses): JsonResponse
    {
        return $this->success(new ExpenseResource($expenses->updatePersonal($personalExpense, $request->validated(), $request->user())), 'Personal expense updated');
    }

    public function destroy(Request $request, PersonalExpense $personalExpense, ExpenseService $expenses): JsonResponse
    {
        $expenses->deletePersonal($personalExpense, $request->user());

        return $this->success(null, 'Personal expense deleted');
    }
}
