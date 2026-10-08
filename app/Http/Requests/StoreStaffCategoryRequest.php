<?php

namespace App\Http\Requests;

use App\Enums\RecordStatus;
use Illuminate\Validation\Rule;

class StoreStaffCategoryRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:staff_categories,name'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(RecordStatus::values())],
        ];
    }
}
