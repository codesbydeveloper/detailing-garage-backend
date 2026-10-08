<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class SqlDate
{
    public static function monthKey(string $column): string
    {
        $column = preg_replace('/[^A-Za-z0-9_.]/', '', $column) ?? $column;

        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}
