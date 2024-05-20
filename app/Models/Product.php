<?php

namespace App\Models;

use App\Classes\Price;
use App\Services\PricingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the invoice's total.
     */
    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn () => Price::of($this->cost)->taxRate($this->tax_rate)->includeTaxInPrice($this->include_vat ?? false),
        );
    }
}
