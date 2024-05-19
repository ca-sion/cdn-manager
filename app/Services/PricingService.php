<?php

namespace App\Services;

use Illuminate\Support\Number;

class PricingService
{
    public static function calculateCostTax($cost = 0, $tax = 100)
    {
        $cost = self::floatify($cost);
        $tax = self::floatify($tax);

        return $cost * $tax / 100;
    }

    public static function calculateCostPrice($cost = 0, $tax = 100, ?bool $includeTaxInPrice = true)
    {
        $cost = self::floatify($cost);
        $tax = self::floatify($tax);
        $taxAmount = self::calculateCostTax($cost, $tax);

        if ($includeTaxInPrice) {
            return $cost;
        } else {
            return $cost + $taxAmount;
        }
    }

    public static function calculateCostNetPrice($cost = 0, $tax = 100, ?bool $includeTaxInPrice = true)
    {
        $cost = self::floatify($cost);
        $tax = self::floatify($tax);
        $taxAmount = self::calculateCostTax($cost, $tax);

        if ($includeTaxInPrice) {
            return $cost - $taxAmount;
        } else {
            return $cost;
        }
    }

    public static function applyQuantity($cost = 0, $quantity = 1)
    {
        $cost = self::floatify($cost);
        $quantity = self::floatify($quantity);

        return $cost * $quantity;
    }

    public static function floatify($value)
    {
        return floatval($value);
    }

    public static function format(int|float $value, string $in = 'CHF', string $locale = 'fr_CH')
    {
        return Number::currency($value, in: $in, locale: $locale);
    }
}
