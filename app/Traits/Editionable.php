<?php

namespace App\Traits;

use App\Models\Edition;

trait Editionable
{
    public function edition()
    {
        return $this->belongsTo(Edition::class);
    }

    public static function bootEditionable()
    {
        static::creating(function ($model) {
            if (! $model->getAttribute('edition_id') && ! $model->relationLoaded('edition')) {
                $model->setAttribute('edition_id', config('cdn.edition_default'));
            }
        });
    }
}
