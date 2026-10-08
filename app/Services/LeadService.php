<?php

namespace App\Services;

use App\Enums\FollowupStatus;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadNote;
use App\Models\LeadStatus;
use App\Models\User;
use App\Models\WorkType;
use App\Support\Query;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadService
{
    public function __construct(
        private ActivityLogService $activity,
        private JobService $jobs,
    ) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Lead::query()->with(['leadStatus:id,name,slug,color', 'creator:id,name']);

        $query->search($filters['search'] ?? null);
        $this->applyStatus($query, $filters['status'] ?? null);

        if (! empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        if (array_key_exists('is_organic', $filters) && $filters['is_organic'] !== null && $filters['is_organic'] !== '') {
            $query->where('is_organic', filter_var($filters['is_organic'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['service'])) {
            $query->where('service_interested', $filters['service']);
        }

        if (! empty($filters['car_condition'])) {
            $query->where('car_condition', $filters['car_condition']);
        }

        if (! empty($filters['preferred_finish'])) {
            $query->where('preferred_finish', $filters['preferred_finish']);
        }

        if (! empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (! empty($filters['adset_id'])) {
            $query->where('adset_id', $filters['adset_id']);
        }

        Query::whereDateRange($query, 'created_time', $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        Query::whereDateRange($query, 'planned_service_date', $filters['planned_date_from'] ?? null, $filters['planned_date_to'] ?? null);
        Query::sort(
            $query,
            $filters['sort'] ?? null,
            $filters['direction'] ?? null,
            ['id', 'created_time', 'full_name', 'phone_number', 'platform', 'planned_service_date', 'created_at'],
            'created_time',
        );

        return $query->paginate(Query::perPage($filters['per_page'] ?? null))->withQueryString();
    }

    public function create(array $data, User $actor): Lead
    {
        $lead = Lead::query()->create([
            ...$this->attributes($data),
            'created_time' => $data['created_time'] ?? now(),
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $lead->load('leadStatus');
        $this->activity->log('CREATE', 'leads', 'Lead created', $lead, null, $this->activity->snapshot($lead), 'success', $actor);

        return $lead;
    }

    public function update(Lead $lead, array $data, User $actor): Lead
    {
        $originalStatus = $lead->lead_status_id;
        $payload = collect($data)->only([
            'created_time', 'ad_id', 'ad_name', 'adset_id', 'adset_name', 'campaign_id', 'campaign_name',
            'form_id', 'form_name', 'is_organic', 'platform', 'service_interested', 'car_condition',
            'preferred_finish', 'planned_service_date', 'car_model', 'car_colour', 'full_name',
            'phone_number', 'email', 'lead_status_id', 'notes',
        ])->all();

        if (array_key_exists('is_organic', $payload)) {
            $payload['is_organic'] = (bool) $payload['is_organic'];
        }

        $lead->fill($payload);
        $lead->updated_by = $actor->id;
        $lead->save();
        $lead->load('leadStatus');

        [$old, $new] = $this->activity->changed($lead);
        $statusChanged = (int) $originalStatus !== (int) $lead->lead_status_id;

        if ($old && ! ($statusChanged && count($old) === 1 && array_key_exists('lead_status_id', $old))) {
            $this->activity->log('UPDATE', 'leads', 'Lead updated', $lead, $old, $new, 'success', $actor);
        }

        if ($statusChanged) {
            $this->activity->log('STATUS_CHANGED', 'leads', 'Lead status changed', $lead, [
                'lead_status_id' => $originalStatus,
            ], [
                'lead_status_id' => $lead->lead_status_id,
                'status' => $lead->leadStatus?->name,
            ], 'success', $actor);
        }

        return $lead;
    }

    public function delete(Lead $lead, User $actor): void
    {
        $snapshot = $this->activity->snapshot($lead);
        $lead->delete();
        $this->activity->log('DELETE', 'leads', 'Lead deleted', $lead, $snapshot, null, 'success', $actor);
    }

    public function addNote(Lead $lead, string $note, User $actor): LeadNote
    {
        $record = $lead->leadNotes()->create([
            'user_id' => $actor->id,
            'note' => $note,
        ]);

        $this->activity->log('NOTE_CREATED', 'leads', 'Lead note created', $record, null, $this->activity->snapshot($record), 'success', $actor);

        return $record->load('user:id,name');
    }

    public function updateNote(Lead $lead, LeadNote $note, string $text, User $actor): LeadNote
    {
        $this->assertNote($lead, $note);
        $note->note = $text;
        $note->save();
        [$old, $new] = $this->activity->changed($note);
        $this->activity->log('NOTE_UPDATED', 'leads', 'Lead note updated', $note, $old, $new, 'success', $actor);

        return $note->load('user:id,name');
    }

    public function deleteNote(Lead $lead, LeadNote $note, User $actor): void
    {
        $this->assertNote($lead, $note);
        $snapshot = $this->activity->snapshot($note);
        $note->delete();
        $this->activity->log('NOTE_DELETED', 'leads', 'Lead note deleted', $note, $snapshot, null, 'success', $actor);
    }

    public function addFollowup(Lead $lead, array $data, User $actor): LeadFollowup
    {
        $followup = $lead->followups()->create($this->followupAttributes($data, $actor, true));
        $this->activity->log('FOLLOWUP_CREATED', 'leads', 'Lead follow-up created', $followup, null, $this->activity->snapshot($followup), 'success', $actor);

        return $followup->load('assignee:id,name');
    }

    public function updateFollowup(Lead $lead, LeadFollowup $followup, array $data, User $actor): LeadFollowup
    {
        $this->assertFollowup($lead, $followup);
        $followup->fill($this->followupAttributes([
            'assigned_to' => array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $followup->assigned_to,
            'follow_up_date' => $data['follow_up_date'] ?? $followup->follow_up_date?->toDateString(),
            'follow_up_time' => array_key_exists('follow_up_time', $data) ? $data['follow_up_time'] : $followup->follow_up_time,
            'status' => $data['status'] ?? $followup->status,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $followup->notes,
        ], $actor, false));
        $followup->save();
        [$old, $new] = $this->activity->changed($followup);
        $this->activity->log('FOLLOWUP_UPDATED', 'leads', 'Lead follow-up updated', $followup, $old, $new, 'success', $actor);

        return $followup->load('assignee:id,name');
    }

    public function deleteFollowup(Lead $lead, LeadFollowup $followup, User $actor): void
    {
        $this->assertFollowup($lead, $followup);
        $snapshot = $this->activity->snapshot($followup);
        $followup->delete();
        $this->activity->log('FOLLOWUP_DELETED', 'leads', 'Lead follow-up deleted', $followup, $snapshot, null, 'success', $actor);
    }

    public function convertToJob(Lead $lead, array $data, User $actor): \App\Models\Job
    {
        return DB::transaction(function () use ($lead, $data, $actor) {
            $lead->load('leadStatus');
            $converted = LeadStatus::query()->where('slug', 'converted')->firstOrFail();

            if ((int) $lead->lead_status_id === (int) $converted->id || $lead->jobs()->exists()) {
                throw ValidationException::withMessages([
                    'lead' => ['This lead has already been converted.'],
                ]);
            }

            $previousStatusId = $lead->lead_status_id;
            $previousStatusName = $lead->leadStatus?->name;

            $lead->update([
                'lead_status_id' => $converted->id,
                'updated_by' => $actor->id,
            ]);

            $workTypeId = $data['work_type_id'] ?? WorkType::query()->where('name', $lead->service_interested)->value('id');

            if (! $workTypeId) {
                throw ValidationException::withMessages([
                    'work_type_id' => ['A work type is required to convert this lead.'],
                ]);
            }

            $carName = trim(implode(' ', array_filter([$lead->car_model, $lead->car_colour])));
            $source = trim(implode(' / ', array_filter([
                $lead->platform,
                $lead->campaign_name,
                $lead->is_organic ? 'Organic' : null,
            ])));

            $remark = trim((string) ($data['remark'] ?? ''));
            if ($source !== '') {
                $remark = trim($remark.' Lead source: '.$source);
            }

            $job = $this->jobs->create([
                'date' => $data['date'] ?? ($lead->planned_service_date?->toDateString() ?? now()->toDateString()),
                'delivery_date' => $data['delivery_date'] ?? null,
                'car_name' => $data['car_name'] ?? ($carName !== '' ? $carName : 'Unknown vehicle'),
                'car_number' => $data['car_number'],
                'customer_name' => $lead->full_name,
                'customer_mobile' => $lead->phone_number,
                'customer_email' => $lead->email,
                'work_type_id' => $workTypeId,
                'income' => $data['income'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'product_charge' => $data['product_charge'] ?? 0,
                'labour_charge' => $data['labour_charge'] ?? 0,
                'amount_paid' => $data['amount_paid'] ?? 0,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'job_status' => $data['job_status'] ?? 'booked',
                'remark' => $remark,
                'lead_id' => $lead->id,
            ], $actor);

            $this->activity->log('STATUS_CHANGED', 'leads', 'Lead status changed', $lead, [
                'status' => $previousStatusName,
                'lead_status_id' => $previousStatusId,
            ], [
                'status' => 'Converted',
                'lead_status_id' => $converted->id,
            ], 'success', $actor);

            $this->activity->log('CONVERTED_TO_JOB', 'leads', 'Lead converted to job '.$job->job_number, $lead, [
                'status' => $previousStatusName,
            ], [
                'status' => 'Converted',
                'job_id' => $job->id,
                'job_number' => $job->job_number,
            ], 'success', $actor);

            \App\Events\LeadConvertedToJob::dispatch($lead, $job);

            return $job->load(['workType', 'lead.leadStatus', 'payments']);
        });
    }

    private function attributes(array $data): array
    {
        return [
            'created_time' => $data['created_time'] ?? null,
            'ad_id' => $data['ad_id'] ?? null,
            'ad_name' => $data['ad_name'] ?? null,
            'adset_id' => $data['adset_id'] ?? null,
            'adset_name' => $data['adset_name'] ?? null,
            'campaign_id' => $data['campaign_id'] ?? null,
            'campaign_name' => $data['campaign_name'] ?? null,
            'form_id' => $data['form_id'] ?? null,
            'form_name' => $data['form_name'] ?? null,
            'is_organic' => (bool) ($data['is_organic'] ?? false),
            'platform' => $data['platform'],
            'service_interested' => $data['service_interested'],
            'car_condition' => $data['car_condition'] ?? null,
            'preferred_finish' => $data['preferred_finish'] ?? null,
            'planned_service_date' => $data['planned_service_date'] ?? null,
            'car_model' => $data['car_model'] ?? null,
            'car_colour' => $data['car_colour'] ?? null,
            'full_name' => $data['full_name'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'] ?? null,
            'lead_status_id' => $data['lead_status_id'],
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function followupAttributes(array $data, User $actor, bool $creating): array
    {
        $status = $data['status'] ?? FollowupStatus::Pending->value;
        $attributes = [
            'assigned_to' => $data['assigned_to'] ?? null,
            'follow_up_date' => $data['follow_up_date'],
            'follow_up_time' => $data['follow_up_time'] ?? null,
            'status' => $status,
            'notes' => $data['notes'] ?? null,
            'completed_at' => $status === FollowupStatus::Completed->value ? ($data['completed_at'] ?? now()) : null,
        ];

        if ($creating) {
            $attributes['created_by'] = $actor->id;
        }

        return $attributes;
    }

    private function applyStatus($query, mixed $status): void
    {
        if ($status === null || $status === '') {
            return;
        }

        if (is_numeric($status)) {
            $query->where('lead_status_id', $status);

            return;
        }

        $query->whereHas('leadStatus', function ($statusQuery) use ($status) {
            $statusQuery->where('slug', $status)->orWhere('name', $status);
        });
    }

    private function assertNote(Lead $lead, LeadNote $note): void
    {
        if ((int) $note->lead_id !== (int) $lead->id) {
            abort(404, 'Resource not found');
        }
    }

    private function assertFollowup(Lead $lead, LeadFollowup $followup): void
    {
        if ((int) $followup->lead_id !== (int) $lead->id) {
            abort(404, 'Resource not found');
        }
    }
}
