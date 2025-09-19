<?php

namespace App\Models;

use App\Classes\Price;
use App\Traits\Editionable;
use App\Enums\InvoiceStatusEnum;
use App\Observers\InvoiceObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([InvoiceObserver::class])]
class Invoice extends Model
{
    use Editionable;
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['edition'];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'positions' => 'array',
            'status'    => InvoiceStatusEnum::class,
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
     * The edition that belong to the invoice.
     */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
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
                'position'    => $key + 1,
                'name'        => $item->name,
                'cost'        => $item->cost,
                'quantity'    => $item->quantity,
                'tax_rate'    => $item->tax_rate,
                'include_vat' => $item->include_vat,
                'price'       => $price,
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
            get: fn () => $this->items->pluck('price.amount')->sum(),
        );
    }

    /**
     * Get the invoice's net total.
     */
    protected function totalNet(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->items->pluck('price.net_amount')->sum(),
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

    /**
     * Scope a query to only include current edition.
     */
    public function scopeCurrentEdition(Builder $query): void
    {
        $query->where('edition_id', session()->get('edition_id') ?? setting('edition_id', config('cdn.default_edition_id')));
    }
}
