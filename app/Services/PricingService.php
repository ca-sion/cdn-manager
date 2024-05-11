<?php

namespace App\Services;

use phpDocumentor\Reflection\Types\Self_;

class PricingService
{
    public static function calculateCostTax($cost = 0, $tax = 100)
    {
        $cost = Self::floatify($cost);
        $tax = Self::floatify($tax);
        return $cost * $tax / 100;
    }

    public static function calculateCostPrice($cost = 0, $tax = 100, ?bool $includeTaxInPrice = true)
    {
        $cost = Self::floatify($cost);
        $tax = Self::floatify($tax);
        $taxAmount = Self::calculateCostTax($cost, $tax);

        if ($includeTaxInPrice) {
            return $cost;
        } else {
            return $cost + $taxAmount;
        }
    }

    public static function calculateCostNetPrice($cost = 0, $tax = 100, ?bool $includeTaxInPrice = true)
    {
        $cost = Self::floatify($cost);
        $tax = Self::floatify($tax);
        $taxAmount = Self::calculateCostTax($cost, $tax);

        if ($includeTaxInPrice) {
            return $cost - $taxAmount;
        } else {
            return $cost;
        }
    }

    public static function applyQuantity($cost = 0, $quantity = 1)
    {
        $cost = Self::floatify($cost);
        $quantity = Self::floatify($quantity);

        return $cost * $quantity;
    }

    public static function floatify($value)
    {
        return floatval($value);
    }
}
