<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_time' => $this->created_time?->toIso8601String(),
            'ad_id' => $this->ad_id,
            'ad_name' => $this->ad_name,
            'adset_id' => $this->adset_id,
            'adset_name' => $this->adset_name,
            'campaign_id' => $this->campaign_id,
            'campaign_name' => $this->campaign_name,
            'form_id' => $this->form_id,
            'form_name' => $this->form_name,
            'is_organic' => (bool) $this->is_organic,
            'platform' => $this->platform,
            'service_interested' => $this->service_interested,
            'car_condition' => $this->car_condition,
            'preferred_finish' => $this->preferred_finish,
            'planned_service_date' => $this->planned_service_date?->toDateString(),
            'car_model' => $this->car_model,
            'car_colour' => $this->car_colour,
            'full_name' => $this->full_name,
            'phone_number' => $this->phone_number,
            'email' => $this->email,
            'lead_status_id' => $this->lead_status_id,
            'status' => $this->whenLoaded('leadStatus', fn () => [
                'id' => $this->leadStatus->id,
                'name' => $this->leadStatus->name,
                'slug' => $this->leadStatus->slug,
                'color' => $this->leadStatus->color,
            ]),
            'notes' => $this->whenLoaded('leadNotes', fn () => $this->leadNotes->map(fn ($note) => [
                'id' => $note->id,
                'content' => $note->note,
                'note' => $note->note,
                'user_id' => $note->user_id,
                'user' => $note->relationLoaded('user') && $note->user ? [
                    'id' => $note->user->id,
                    'name' => $note->user->name,
                ] : null,
                'created_at' => $note->created_at?->toIso8601String(),
            ])->values()),
            'followups' => $this->whenLoaded('followups', fn () => $this->followups->map(fn ($item) => [
                'id' => $item->id,
                'follow_up_date' => $item->follow_up_date?->toDateString(),
                'follow_up_time' => $item->follow_up_time,
                'notes' => $item->notes,
                'note' => $item->notes,
                'status' => $item->status,
                'assigned_to' => $item->assigned_to,
                'assignee' => $item->relationLoaded('assignee') && $item->assignee ? [
                    'id' => $item->assignee->id,
                    'name' => $item->assignee->name,
                ] : null,
            ])->values()),
            'timeline' => $this->when(
                $this->relationLoaded('leadNotes') || $this->relationLoaded('followups'),
                fn () => $this->timeline(),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function timeline(): array
    {
        $events = collect();

        if ($this->relationLoaded('leadNotes')) {
            foreach ($this->leadNotes as $note) {
                $events->push([
                    'type' => 'note',
                    'id' => $note->id,
                    'content' => $note->note,
                    'note' => $note->note,
                    'user_id' => $note->user_id,
                    'user' => $note->relationLoaded('user') && $note->user ? [
                        'id' => $note->user->id,
                        'name' => $note->user->name,
                    ] : null,
                    'created_at' => $note->created_at?->toIso8601String(),
                ]);
            }
        }

        if ($this->relationLoaded('followups')) {
            foreach ($this->followups as $item) {
                $events->push([
                    'type' => 'followup',
                    'id' => $item->id,
                    'follow_up_date' => $item->follow_up_date?->toDateString(),
                    'follow_up_time' => $item->follow_up_time,
                    'notes' => $item->notes,
                    'status' => $item->status,
                    'assigned_to' => $item->assigned_to,
                    'created_at' => $item->created_at?->toIso8601String(),
                ]);
            }
        }

        return $events->sortBy('created_at')->values()->all();
    }
}
