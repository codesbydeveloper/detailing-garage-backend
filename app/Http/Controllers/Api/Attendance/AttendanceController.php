<?php

namespace App\Http\Controllers\Api\Attendance;

use App\Enums\AttendanceStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\ClockInRequest;
use App\Http\Requests\ClockOutRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Support\Period;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends ApiController
{
    public function index(Request $request, AttendanceService $attendance): JsonResponse
    {
        $filters = $this->listFilters($request, [
            'staff_id' => ['nullable', 'integer'],
        ]);

        return $this->paginated($attendance->paginate($filters), AttendanceResource::class);
    }

    public function mine(Request $request, AttendanceService $attendance): JsonResponse
    {
        $filters = $this->listFilters($request);

        return $this->paginated($attendance->mine($request->user(), $filters), AttendanceResource::class);
    }

    public function today(AttendanceService $attendance): JsonResponse
    {
        return $this->success(AttendanceResource::collection($attendance->today())->resolve(request()), 'Today attendance loaded');
    }

    public function summary(Request $request, AttendanceService $attendance): JsonResponse
    {
        Period::normalize($request);
        $data = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'staff_id' => ['nullable', 'integer'],
        ]);

        return $this->success($attendance->summary(
            (int) ($data['year'] ?? now()->year),
            (int) ($data['month'] ?? now()->month),
            isset($data['staff_id']) ? (int) $data['staff_id'] : null,
        ), 'Attendance summary loaded');
    }

    public function monthly(Request $request, AttendanceService $attendance): JsonResponse
    {
        Period::normalize($request);
        $data = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'staff_id' => ['nullable', 'integer'],
        ]);

        $rows = $attendance->monthly(
            (int) ($data['year'] ?? now()->year),
            (int) ($data['month'] ?? now()->month),
            isset($data['staff_id']) ? (int) $data['staff_id'] : null,
        );

        return $this->success(AttendanceResource::collection($rows)->resolve($request), 'Monthly attendance loaded');
    }

    public function show(Attendance $attendance): JsonResponse
    {
        $attendance->load('staff:id,full_name,staff_code');

        return $this->success(new AttendanceResource($attendance), 'Attendance loaded');
    }

    public function store(Request $request, AttendanceService $attendance): JsonResponse
    {
        $data = $request->validate([
            'staff_id' => ['required', 'exists:staff,id'],
            'date' => ['required', 'date'],
            'clock_in' => ['nullable', 'date'],
            'clock_out' => ['nullable', 'date'],
            'status' => ['required', Rule::in(AttendanceStatus::values())],
            'notes' => ['nullable', 'string'],
        ]);

        return $this->success(new AttendanceResource($attendance->create($data, $request->user())), 'Attendance created', 201);
    }

    public function update(Request $request, Attendance $attendance, AttendanceService $service): JsonResponse
    {
        $data = $request->validate([
            'staff_id' => ['sometimes', 'exists:staff,id'],
            'date' => ['sometimes', 'date'],
            'clock_in' => ['nullable', 'date'],
            'clock_out' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::in(AttendanceStatus::values())],
            'notes' => ['nullable', 'string'],
        ]);

        return $this->success(new AttendanceResource($service->update($attendance, $data, $request->user())), 'Attendance updated');
    }

    public function destroy(Request $request, Attendance $attendance, AttendanceService $service): JsonResponse
    {
        $service->delete($attendance, $request->user());

        return $this->success(null, 'Attendance deleted');
    }

    public function clockIn(ClockInRequest $request, AttendanceService $attendance): JsonResponse
    {
        return $this->success(new AttendanceResource($attendance->clockIn($request->user(), $request->input('notes'))), 'Clocked in', 201);
    }

    public function clockOut(ClockOutRequest $request, AttendanceService $attendance): JsonResponse
    {
        return $this->success(new AttendanceResource($attendance->clockOut($request->user(), $request->input('notes'))), 'Clocked out');
    }
}
