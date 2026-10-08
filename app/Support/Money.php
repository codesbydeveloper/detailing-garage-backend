<?php

namespace App\Support;

final class Money
{
    public static function of(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        if (is_int($value)) {
            return bcadd((string) $value, '0', 2);
        }

        if (is_float($value)) {
            return bcadd(number_format($value, 2, '.', ''), '0', 2);
        }

        $normalized = str_replace([',', ' '], '', trim((string) $value));

        if ($normalized === '' || ! is_numeric($normalized)) {
            return '0.00';
        }

        return bcadd($normalized, '0', 2);
    }

    public static function add(mixed ...$values): string
    {
        $total = '0.00';

        foreach ($values as $value) {
            $total = bcadd($total, self::of($value), 2);
        }

        return $total;
    }

    public static function sub(mixed $left, mixed $right): string
    {
        return bcsub(self::of($left), self::of($right), 2);
    }

    public static function comp(mixed $left, mixed $right): int
    {
        return bccomp(self::of($left), self::of($right), 2);
    }

    public static function gt(mixed $left, mixed $right): bool
    {
        return self::comp($left, $right) === 1;
    }

    public static function gte(mixed $left, mixed $right): bool
    {
        return self::comp($left, $right) >= 0;
    }

    public static function lt(mixed $left, mixed $right): bool
    {
        return self::comp($left, $right) === -1;
    }

    public static function lte(mixed $left, mixed $right): bool
    {
        return self::comp($left, $right) <= 0;
    }

    public static function eq(mixed $left, mixed $right): bool
    {
        return self::comp($left, $right) === 0;
    }

    public static function isZero(mixed $value): bool
    {
        return self::eq($value, '0');
    }

    public static function isPositive(mixed $value): bool
    {
        return self::gt($value, '0');
    }
}
