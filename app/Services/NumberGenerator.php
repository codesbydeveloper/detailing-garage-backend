<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NumberGenerator
{
    public function monthly(string $modelClass, string $column, string $prefix): string
    {
        /** @var class-string<Model> $modelClass */
        $fullPrefix = $prefix.now()->format('Ym').'-';
        $query = $modelClass::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        $last = $query
            ->where($column, 'like', $fullPrefix.'%')
            ->orderByDesc($column)
            ->lockForUpdate()
            ->value($column);

        $sequence = 1;

        if (is_string($last) && str_starts_with($last, $fullPrefix)) {
            $sequence = ((int) substr($last, strlen($fullPrefix))) + 1;
        }

        return $fullPrefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function sequence(string $modelClass, string $column, string $prefix, int $pad = 4): string
    {
        /** @var class-string<Model> $modelClass */
        $query = $modelClass::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        $last = $query->orderByDesc('id')->lockForUpdate()->value($column);
        $sequence = 1;

        if (is_string($last) && preg_match('/(\d+)$/', $last, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, $pad, '0', STR_PAD_LEFT);
    }
}
