<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The provisions that belong to the client.
     */
    public function clientProvisions(): HasMany
    {
        return $this->hasMany(ClientProvision::class);
    }

    /**
     * The contacts that belong to the client.
     */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)->withPivot(['type', 'note'])->withTimestamps();
    }

    /**
     * The invoices that belong to the client.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
