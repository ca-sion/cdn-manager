<?php

namespace App\Models;

use App\Classes\Price;
use App\Traits\Editionable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\URL;

class Invoice extends Model
{
    use HasFactory;
    use Editionable;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'positions' => 'array',
        ];
    }

    /**
     * Get the client that owns the invoice.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the invoice pdf url.
     */
    protected function link(): Attribute
    {
        return Attribute::make(
            get: fn () => URL::signedRoute('invoices.show', $this->id),
        );
    }

    /**
     * Get the invoice's formatted positions.
     */
    protected function items(): Attribute
    {
        $positionsCollection = collect(json_decode(json_encode($this->positions ?? [])));

        $items = $positionsCollection->map(function ($item, $key) {
            $price = null;
            $price = Price::of($item->cost)
                ->quantity($item->quantity)
                ->taxRate($item->tax_rate)
                ->includeTaxInPrice($item->include_vat ?? false);

            return (object) [
                'position' => $key + 1,
                'name' => $item->name,
                'cost' => $item->cost,
                'quantity' => $item->quantity,
                'tax_rate' => $item->tax_rate,
                'include_vat' => $item->include_vat,
                'price' => $price,
            ];
        });

        return Attribute::make(
            get: fn () => $items,
        );
    }

    /**
     * Get the invoice's total.
     */
    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->items->pluck('price.price')->sum(),
        );
    }

    /**
     * Get the invoice's total.
     */
    protected function totalTax(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->items->pluck('price.tax_amount')->sum(),
        );
    }
}
