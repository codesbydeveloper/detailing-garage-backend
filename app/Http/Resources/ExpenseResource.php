<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'expense_number' => $this->expense_number,
            'date' => $this->date?->toDateString(),
            'expense_category_id' => $this->expense_category_id ?? null,
            'category_id' => $this->expense_category_id ?? null,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null),
            'staff_id' => $this->staff_id ?? null,
            'staff' => $this->whenLoaded('staff', fn () => $this->staff ? [
                'id' => $this->staff->id,
                'full_name' => $this->staff->full_name,
                'staff_code' => $this->staff->staff_code,
            ] : null),
            'expense' => $this->expense,
            'price' => $this->price,
            'description' => $this->description,
            'remark' => $this->description,
        ];
    }
}
