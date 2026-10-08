<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\Staff;
use App\Services\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends ApiController
{
    public function index(Request $request, StaffService $staff): JsonResponse
    {
        $filters = $this->listFilters($request, [
            'staff_category_id' => ['nullable', 'integer'],
        ]);

        return $this->paginated($staff->paginate($filters), StaffResource::class);
    }

    public function directory(Request $request, StaffService $staff): JsonResponse
    {
        if (! $request->filled('per_page') && ! $request->filled('page_size')) {
            $request->merge(['per_page' => 100]);
        }

        $filters = $this->listFilters($request, [
            'staff_category_id' => ['nullable', 'integer'],
        ]);
        $paginator = $staff->paginate($filters);

        return response()->json([
            'success' => true,
            'message' => 'Staff directory loaded',
            'data' => $paginator->getCollection()->map(fn (Staff $member) => [
                'id' => $member->id,
                'full_name' => $member->full_name,
                'staff_category_id' => $member->staff_category_id,
                'role' => $member->role,
                'status' => $member->status,
                'user_id' => $member->user_id,
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreStaffRequest $request, StaffService $staff): JsonResponse
    {
        return $this->success(new StaffResource($staff->create($request->validated(), $request->user())), 'Staff created', 201);
    }

    public function show(Staff $staff): JsonResponse
    {
        $staff->load(['category', 'user']);

        return $this->success(new StaffResource($staff), 'Staff loaded');
    }

    public function update(UpdateStaffRequest $request, Staff $staff, StaffService $service): JsonResponse
    {
        return $this->success(new StaffResource($service->update($staff, $request->validated(), $request->user())), 'Staff updated');
    }

    public function destroy(Request $request, Staff $staff, StaffService $service): JsonResponse
    {
        $service->delete($staff, $request->user());

        return $this->success(null, 'Staff deleted');
    }
}
