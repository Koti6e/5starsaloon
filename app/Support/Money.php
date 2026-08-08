<?php

namespace App\Support;

class Money
{
    public static function inr(int|float|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return 'Contact for Price';
        }

        $number = (float) $amount;
        $parts = explode('.', number_format($number, 2, '.', ''));
        $whole = $parts[0];
        $decimal = $parts[1] ?? '00';

        $lastThree = substr($whole, -3);
        $leading = substr($whole, 0, -3);

        if ($leading !== '') {
            $lastThree = ','.$lastThree;
            $leading = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $leading);
        }

        $formatted = $leading.$lastThree;

        if ($decimal !== '00') {
            $formatted .= '.'.$decimal;
        }

        return '₹'.$formatted;
    }
}
