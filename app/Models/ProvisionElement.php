<?php

namespace App\Models;

use App\Classes\Price;
use App\Traits\Editionable;
use App\Enums\MediaStatusEnum;
use Spatie\MediaLibrary\HasMedia;
use Spatie\EloquentSortable\Sortable;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ProvisionElementStatusEnum;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProvisionElement extends Model implements HasMedia, Sortable
{
    use Editionable;
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;
    use SortableTrait;

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
    protected $with = ['provision'];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'status'        => ProvisionElementStatusEnum::class,
            'media_status'  => MediaStatusEnum::class,
            'due_date'      => 'date',
            'contact_date'  => 'date',
            'tracking_date' => 'date',
        ];
    }

    /**
     * The provision that belong to the provision.
     */
    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The client that belong to the provision.
     */
    public function client(): HasOne
    {
        return $this->hasOne(Client::class, 'id', 'recipient_id');
    }

    /**
     * The client that belong to the provision.
     */
    public function clientAdministrationEmail(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->client?->contactEmail,
        );
    }

    /**
     * The provision that belong to the provision.
     */
    public function provision(): BelongsTo
    {
        return $this->belongsTo(Provision::class);
    }

    /**
     * The contact that belong to the provision.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * The dicastry that belong to the provision.
     */
    public function dicastry(): BelongsTo
    {
        return $this->belongsTo(Dicastry::class);
    }

    /**
     * Get the provision element's price.
     */
    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn () => Price::of($this->cost)->taxRate($this->tax_rate)->includeTaxInPrice($this->include_vat ?? false),
        );
    }

    /**
     * Get the provision element's name.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->provision?->name,
        );
    }
}
