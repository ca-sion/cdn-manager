<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Provision extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the elements for the provisions.
     */
    public function elements(): HasMany
    {
        return $this->hasMany(Provision::class);
    }

    /**
     * Get the product that owns the provision.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the dicastry that owns the provision.
     */
    public function dicastry(): BelongsTo
    {
        return $this->belongsTo(Dicastry::class);
    }

    /**
     * Get the category that owns the provision.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProvisionCategory::class);
    }
}
