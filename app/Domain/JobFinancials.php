<?php

namespace App\Domain;

use App\Support\Money;
use Illuminate\Validation\ValidationException;

final class JobFinancials
{
    /**
     * @return array{
     *     income: string,
     *     discount: string,
     *     product_charge: string,
     *     labour_charge: string,
     *     amount_paid: string,
     *     final_amount: string,
     *     profit: string,
     *     pending_pay: string,
     *     payment_status: string
     * }
     */
    public static function calculate(
        mixed $income,
        mixed $discount,
        mixed $productCharge,
        mixed $labourCharge,
        mixed $amountPaid,
    ): array {
        $income = Money::of($income);
        $discount = Money::of($discount);
        $product = Money::of($productCharge);
        $labour = Money::of($labourCharge);
        $paid = Money::of($amountPaid);

        if (Money::gt($discount, $income)) {
            throw ValidationException::withMessages([
                'discount' => ['Discount cannot exceed income.'],
            ]);
        }

        $final = Money::sub($income, $discount);

        if (Money::gt($paid, $final)) {
            throw ValidationException::withMessages([
                'amount' => ['Payment exceeds the balance due.'],
            ]);
        }

        $profit = Money::sub(Money::sub($final, $product), $labour);
        $pending = Money::sub($final, $paid);

        return [
            'income' => $income,
            'discount' => $discount,
            'product_charge' => $product,
            'labour_charge' => $labour,
            'amount_paid' => $paid,
            'final_amount' => $final,
            'profit' => $profit,
            'pending_pay' => $pending,
            'payment_status' => self::paymentStatus($paid, $pending),
        ];
    }

    public static function paymentStatus(string $paid, string $pending): string
    {
        if (Money::lte($pending, '0')) {
            return 'paid';
        }

        if (Money::isPositive($paid)) {
            return 'partial';
        }

        return 'pending';
    }
}
