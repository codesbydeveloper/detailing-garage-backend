<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class Query
{
    public static function perPage(mixed $value): int
    {
        $perPage = (int) ($value ?: 20);

        return min(100, max(1, $perPage));
    }

    public static function like(?string $term): ?string
    {
        $term = trim((string) $term);

        if ($term === '') {
            return null;
        }

        return '%'.addcslashes($term, '%_\\').'%';
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function sort(Builder $query, ?string $column, ?string $direction, array $allowed, string $default): Builder
    {
        $column = in_array($column, $allowed, true) ? $column : $default;
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($column, $direction);
    }

    public static function whereDateRange(Builder $query, string $column, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate($column, '>=', $from);
        }

        if ($to) {
            $query->whereDate($column, '<=', $to);
        }

        return $query;
    }
}
