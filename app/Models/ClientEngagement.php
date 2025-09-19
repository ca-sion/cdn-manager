<?php

namespace App\Models;

use App\Traits\Editionable;
use App\Enums\EngagementStageEnum;
use App\Enums\EngagementStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientEngagement extends Model
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
            'stage'  => EngagementStageEnum::class,
            'status' => EngagementStatusEnum::class,
        ];
    }

    /**
     * The client that belong to the engagement.
     */
    public function client(): BelongsTo
    {
        return $this->BelongsTo(Client::class);
    }

    /**
     * The responsible contact that belong to the engagement.
     */
    public function responsibleContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'responsible_contact_id');
    }

    /**
     * The edition that belong to the engagement.
     */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    /**
     * Scope a query to only include current edition.
     */
    public function scopeCurrentEdition(Builder $query): void
    {
        $query->where('edition_id', session()->get('edition_id') ?? setting('edition_id', config('cdn.default_edition_id')));
    }
}
