<?php

namespace App\Http\Controllers\Api\Leads;

use App\Enums\FollowupStatus;
use App\Http\Controllers\Api\ApiController;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeadFollowupController extends ApiController
{
    public function index(Lead $lead): JsonResponse
    {
        $items = $lead->followups()->with('assignee:id,name')->orderBy('follow_up_date')->get();

        return $this->success($items->map(fn (LeadFollowup $item) => $this->payload($item))->all(), 'Follow-ups loaded');
    }

    public function store(Request $request, Lead $lead, LeadService $leads): JsonResponse
    {
        $this->normalizeFollowup($request);
        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
            'follow_up_date' => ['required', 'date'],
            'follow_up_time' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', Rule::in(FollowupStatus::values())],
            'notes' => ['nullable', 'string'],
        ]);

        return $this->success($this->payload($leads->addFollowup($lead, $data, $request->user())), 'Follow-up created', 201);
    }

    public function update(Request $request, Lead $lead, LeadFollowup $followup, LeadService $leads): JsonResponse
    {
        $this->normalizeFollowup($request);
        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
            'follow_up_date' => ['sometimes', 'date'],
            'follow_up_time' => ['nullable', 'date_format:H:i'],
            'status' => ['sometimes', Rule::in(FollowupStatus::values())],
            'notes' => ['nullable', 'string'],
        ]);

        return $this->success($this->payload($leads->updateFollowup($lead, $followup, $data, $request->user())), 'Follow-up updated');
    }

    public function destroy(Request $request, Lead $lead, LeadFollowup $followup, LeadService $leads): JsonResponse
    {
        $leads->deleteFollowup($lead, $followup, $request->user());

        return $this->success(null, 'Follow-up deleted');
    }

    private function payload(LeadFollowup $item): array
    {
        return [
            'id' => $item->id,
            'lead_id' => $item->lead_id,
            'assigned_to' => $item->assigned_to,
            'assignee' => $item->relationLoaded('assignee') && $item->assignee ? [
                'id' => $item->assignee->id,
                'name' => $item->assignee->name,
            ] : null,
            'follow_up_date' => $item->follow_up_date?->toDateString(),
            'follow_up_time' => $item->follow_up_time,
            'status' => $item->status,
            'notes' => $item->notes,
            'note' => $item->notes,
            'completed_at' => $item->completed_at?->toIso8601String(),
        ];
    }

    private function normalizeFollowup(Request $request): void
    {
        $merge = [];

        if (! $request->filled('assigned_to') && $request->filled('assigned_user_id')) {
            $merge['assigned_to'] = $request->input('assigned_user_id');
        }

        if (! $request->exists('notes') && $request->exists('note')) {
            $merge['notes'] = $request->input('note');
        }

        if ($merge !== []) {
            $request->merge($merge);
        }
    }
}
