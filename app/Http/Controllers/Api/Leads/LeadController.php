<?php

namespace App\Http\Controllers\Api\Leads;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\ConvertLeadRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Resources\JobResource;
use App\Http\Resources\LeadResource;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends ApiController
{
    public function index(Request $request, LeadService $leads): JsonResponse
    {
        $filters = $this->listFilters($request, [
            'platform' => ['nullable', 'string', 'max:50'],
            'is_organic' => ['nullable'],
            'service' => ['nullable', 'string', 'max:255'],
            'car_condition' => ['nullable', 'string', 'max:100'],
            'preferred_finish' => ['nullable', 'string', 'max:100'],
            'campaign_id' => ['nullable', 'string', 'max:100'],
            'adset_id' => ['nullable', 'string', 'max:100'],
            'planned_date_from' => ['nullable', 'date'],
            'planned_date_to' => ['nullable', 'date'],
        ]);

        return $this->paginated($leads->paginate($filters), LeadResource::class);
    }

    public function store(StoreLeadRequest $request, LeadService $leads): JsonResponse
    {
        $lead = $leads->create($request->validated(), $request->user());

        return $this->success(new LeadResource($lead), 'Lead created', 201);
    }

    public function show(Lead $lead): JsonResponse
    {
        $lead->load(['leadStatus', 'leadNotes.user:id,name', 'followups.assignee:id,name']);

        return $this->success(new LeadResource($lead), 'Lead loaded');
    }

    public function update(UpdateLeadRequest $request, Lead $lead, LeadService $leads): JsonResponse
    {
        $lead = $leads->update($lead, $request->validated(), $request->user());

        return $this->success(new LeadResource($lead), 'Lead updated');
    }

    public function destroy(Request $request, Lead $lead, LeadService $leads): JsonResponse
    {
        $leads->delete($lead, $request->user());

        return $this->success(null, 'Lead deleted');
    }

    public function convert(ConvertLeadRequest $request, Lead $lead, LeadService $leads): JsonResponse
    {
        $job = $leads->convertToJob($lead, $request->validated(), $request->user());

        return $this->success([
            'lead' => (new LeadResource($lead->fresh('leadStatus')))->resolve($request),
            'job' => (new JobResource($job))->resolve($request),
        ], 'Lead converted to job', 201);
    }

    public function convertDirect(ConvertLeadRequest $request, Lead $lead, LeadService $leads): JsonResponse
    {
        $job = $leads->convertToJob($lead, $request->validated(), $request->user());

        return $this->success(new JobResource($job), 'Lead converted to job', 201);
    }
}
