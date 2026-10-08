<?php

namespace App\Http\Controllers\Api\Salary;

use App\Enums\SalaryStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\GenerateSalaryRequest;
use App\Http\Resources\SalaryResource;
use App\Models\SalaryRecord;
use App\Services\PayrollService;
use App\Support\Period;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryController extends ApiController
{
    public function index(Request $request, PayrollService $payroll): JsonResponse
    {
        Period::normalize($request);
        $filters = $this->listFilters($request, [
            'staff_id' => ['nullable', 'integer'],
            'year' => ['nullable', 'integer'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        return $this->paginated($payroll->paginate($filters), SalaryResource::class);
    }

    public function mine(Request $request, PayrollService $payroll): JsonResponse
    {
        Period::normalize($request);
        $filters = $this->listFilters($request, [
            'year' => ['nullable', 'integer'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        return $this->paginated($payroll->paginate($filters, $request->user()), SalaryResource::class);
    }

    public function show(SalaryRecord $salary): JsonResponse
    {
        $this->authorize('view', $salary);
        $salary->load('staff:id,full_name,staff_code');

        return $this->success(new SalaryResource($salary), 'Salary loaded');
    }

    public function generate(GenerateSalaryRequest $request, PayrollService $payroll): JsonResponse
    {
        $records = $payroll->generate(
            (int) $request->integer('year'),
            (int) $request->integer('month'),
            $request->user(),
        );

        return $this->success(SalaryResource::collection(collect($records))->resolve($request), 'Salary generated');
    }

    public function update(Request $request, SalaryRecord $salary, PayrollService $payroll): JsonResponse
    {
        $data = $request->validate([
            'basic_salary' => ['sometimes', 'numeric', 'min:0'],
            'bonus' => ['sometimes', 'numeric', 'min:0'],
            'deduction' => ['sometimes', 'numeric', 'min:0'],
            'advance' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in([SalaryStatus::Draft->value, SalaryStatus::Generated->value])],
        ]);

        return $this->success(new SalaryResource($payroll->update($salary, $data, $request->user())), 'Salary updated');
    }

    public function markPaid(Request $request, SalaryRecord $salary, PayrollService $payroll): JsonResponse
    {
        return $this->success(new SalaryResource($payroll->markPaid($salary, $request->user())), 'Salary marked as paid');
    }
}
