<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'postal_code',
        'locality',
        'country',
        'client_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function runRegistrations(): HasMany
    {
        return $this->hasMany(RunRegistration::class);
    }
}
