<?php

namespace App\Http\Controllers\Api\Settings;

use App\Enums\RecordStatus;
use App\Http\Controllers\Api\ApiController;
use App\Models\LeadStatus;
use App\Services\ActivityLogService;
use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LeadStatusController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = LeadStatus::query()->orderBy('sort_order');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return $this->success($query->get(), 'Lead statuses loaded');
    }

    public function store(Request $request, ActivityLogService $activity): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:lead_statuses,slug'],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(RecordStatus::values())],
        ]);

        $status = LeadStatus::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'color' => $data['color'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        $activity->log('LEAD_STATUS_CREATED', 'settings', 'Lead status created', $status, null, $activity->snapshot($status), 'success', $request->user());

        return $this->success($status, 'Lead status created', 201);
    }

    public function show(LeadStatus $leadStatus): JsonResponse
    {
        return $this->success($leadStatus, 'Lead status loaded');
    }

    public function update(Request $request, LeadStatus $leadStatus, ActivityLogService $activity): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('lead_statuses', 'slug')->ignore($leadStatus->id)],
            'color' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(RecordStatus::values())],
        ]);
        $leadStatus->fill($data);
        $leadStatus->updated_by = $request->user()->id;
        $leadStatus->save();
        [$old, $new] = $activity->changed($leadStatus);
        $activity->log('LEAD_STATUS_UPDATED', 'settings', 'Lead status updated', $leadStatus, $old, $new, 'success', $request->user());

        return $this->success($leadStatus, 'Lead status updated');
    }

    public function destroy(Request $request, LeadStatus $leadStatus, CatalogService $catalog): JsonResponse
    {
        $result = DB::transaction(fn () => $catalog->remove(
            $leadStatus,
            'leads',
            'settings',
            'LEAD_STATUS_UPDATED',
            'LEAD_STATUS_DELETED',
            'Lead status',
            $request->user(),
            'converted',
        ));

        return $this->success(null, $result === 'deactivated' ? 'Lead status deactivated' : 'Lead status deleted');
    }
}
