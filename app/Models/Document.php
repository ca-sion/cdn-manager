<?php

namespace App\Models;

use App\Traits\Editionable;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends Model implements HasMedia
{
    use Editionable;
    use HasFactory;
    use InteractsWithMedia;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the client that owns the document.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
