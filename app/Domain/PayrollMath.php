<?php

namespace App\Domain;

use App\Support\Money;

final class PayrollMath
{
    public static function payable(
        mixed $basic,
        mixed $extraPay,
        mixed $bonus,
        mixed $deduction,
        mixed $advance,
    ): string {
        $earnings = Money::add($basic, $extraPay, $bonus);

        return Money::sub(Money::sub($earnings, $deduction), $advance);
    }
}
