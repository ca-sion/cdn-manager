<?php

namespace App\Models;

use App\Traits\Editionable;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use App\Enums\ProvisionElementStatusEnum;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProvisionElement extends Model implements HasMedia
{
    use Editionable;
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

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
            'status' => ProvisionElementStatusEnum::class,
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
}
