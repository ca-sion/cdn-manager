<?php

namespace App\Models;

use App\Traits\Editionable;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientProvision extends Model implements HasMedia
{
    use HasFactory;
    use SoftDeletes;
    use Editionable;
    use InteractsWithMedia;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The provision that belong to the provision.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The provision that belong to the provision.
     */
    public function provision(): BelongsTo
    {
        return $this->belongsTo(Provision::class);
    }
}
