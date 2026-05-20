<?php

namespace App\Models;

use App\Classes\Price;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the product's price.
     */
    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn () => Price::of($this->cost)->taxRate($this->tax_rate)->includeTaxInPrice($this->include_vat ?? false),
        );
    }
}
