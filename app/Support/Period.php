<?php

namespace App\Support;

use Illuminate\Http\Request;

final class Period
{
    public static function normalize(Request $request): void
    {
        $month = $request->input('month');

        if (is_string($month) && preg_match('/^(\d{4})-(\d{1,2})$/', trim($month), $matches)) {
            $request->merge([
                'year' => (int) $matches[1],
                'month' => (int) $matches[2],
            ]);
        }
    }
}
