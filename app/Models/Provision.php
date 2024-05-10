<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
     * The client provisions that belong to the provision.
     */
    public function clientProvisions(): BelongsTo
    {
        return $this->belongsTo(ClientProvision::class);
    }

    /**
     * Get the client that owns the invoice.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
