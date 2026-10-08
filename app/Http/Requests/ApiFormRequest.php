<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return list<string>
     */
    protected function money(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'numeric', 'min:0'];
    }

    protected function mergeLeadAliases(): void
    {
        $merge = [];

        if (! $this->filled('service_interested') && $this->filled('service_interest')) {
            $merge['service_interested'] = $this->input('service_interest');
        }

        if (! $this->filled('preferred_finish') && $this->filled('finish_preference')) {
            $merge['preferred_finish'] = $this->input('finish_preference');
        }

        if (! $this->filled('planned_service_date') && $this->filled('service_timeline') && strtotime((string) $this->input('service_timeline'))) {
            $merge['planned_service_date'] = $this->input('service_timeline');
        }

        if (! $this->filled('lead_status_id') && $this->filled('status_id')) {
            $merge['lead_status_id'] = $this->input('status_id');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    protected function mergeExpenseAliases(): void
    {
        $merge = [];

        if (! $this->exists('expense_category_id') && $this->exists('category_id')) {
            $merge['expense_category_id'] = $this->input('category_id');
        }

        if (! $this->exists('description') && $this->exists('remark')) {
            $merge['description'] = $this->input('remark');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
