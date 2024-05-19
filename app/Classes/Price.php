<?php

namespace App\Classes;

use Exception;
use Illuminate\Support\Number;
use App\Services\PricingService;

/**
 * Class InvoiceItem
 */
class Price
{
    public int|float|string $cost;

    public int|float|string $quantity;

    public int|float|string $tax_rate;

    public int|float|string $tax;

    public int|float|string $discount_rate;

    public int|float|string $discount;

    public bool $include_tax_in_price;

    public float $price;

    public float $net_price;

    public float $tax_amount;

    /**
     * InvoiceItem constructor.
     */
    public function __construct()
    {
        $this->cost = 0.0;
        $this->quantity = 1.0;
        $this->tax_rate = 0.0;
        $this->tax = 0.0;
        $this->discount_rate = 0.0;
        $this->discount = 0.0;
        $this->include_tax_in_price = false;
    }

    public static function of($cost)
    {
        return (new self())->cost($cost);
    }

    public function cost($cost)
    {
        $this->cost = (float) $cost;

        $this->calculate();

        return $this;
    }

    public function taxRate($taxRate)
    {
        $this->tax_rate = (float) $taxRate;

        $this->calculate();

        return $this;
    }

    public function includeTaxInPrice(bool $includeTaxInPrice)
    {
        $this->include_tax_in_price = $includeTaxInPrice;

        $this->calculate();

        return $this;
    }

    public function quantity($quantity)
    {
        $this->quantity = (float) $quantity;

        $this->calculate();

        return $this;
    }

    public function calculate(): void
    {
        $this->price = $this->calculatePrice();
        $this->net_price = $this->calculateNetPrice();
        $this->tax_amount = $this->calculateTax();
    }

    public function calculatePrice()
    {
        $price = PricingService::calculateCostPrice($this->cost, $this->tax_rate, $this->include_tax_in_price);

        if ($this->quantity > 0) {
            return $price * $this->quantity;
        }

        return $price;
    }

    public function calculateNetPrice()
    {
        $netPrice = PricingService::calculateCostNetPrice($this->cost, $this->tax_rate, $this->include_tax_in_price);

        if ($this->quantity > 0) {
            return $netPrice * $this->quantity;
        }

        return $netPrice;
    }

    public function calculateTax()
    {
        $tax = PricingService::calculateCostTax($this->cost, $this->tax_rate);

        if ($this->quantity > 0) {
            return $tax * $this->quantity;
        }

        return $tax;
    }

    public static function format(int|float $value, string $in = 'CHF', string $locale = 'fr_CH')
    {
        return Number::currency($value, in: $in, locale: $locale);
    }

    public static function formatfoPdf(int|float $value, string $in = 'CHF', string $locale = 'fr_CH')
    {
        return str(Number::currency($value, in: $in, locale: $locale))->replace(' ', " ");
    }
}
