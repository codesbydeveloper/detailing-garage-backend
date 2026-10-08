<?php

namespace App\Http\Requests;

use App\Enums\CarCondition;
use App\Enums\LeadPlatform;
use App\Enums\PreferredFinish;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->mergeLeadAliases();
    }

    public function rules(): array
    {
        return [
            'created_time' => ['nullable', 'date'],
            'ad_id' => ['nullable', 'string', 'max:100'],
            'ad_name' => ['nullable', 'string', 'max:255'],
            'adset_id' => ['nullable', 'string', 'max:100'],
            'adset_name' => ['nullable', 'string', 'max:255'],
            'campaign_id' => ['nullable', 'string', 'max:100'],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'form_id' => ['nullable', 'string', 'max:100'],
            'form_name' => ['nullable', 'string', 'max:255'],
            'is_organic' => ['nullable', 'boolean'],
            'platform' => ['required', Rule::in(LeadPlatform::values())],
            'service_interested' => ['required', 'string', 'max:255'],
            'car_condition' => ['nullable', Rule::in(CarCondition::values())],
            'preferred_finish' => ['nullable', Rule::in(PreferredFinish::values())],
            'planned_service_date' => ['nullable', 'date'],
            'car_model' => ['nullable', 'string', 'max:255'],
            'car_colour' => ['nullable', 'string', 'max:100'],
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'lead_status_id' => ['required', 'exists:lead_statuses,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
