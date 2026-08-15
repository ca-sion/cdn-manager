<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Run extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'distance',
        'cost',
        'available_for_types',
        'start_blocs',
        'registrations_deadline',
        'registrations_limit',
        'registrations_number',
        'min_age',
        'max_age',
        'datasport_code',
        'code',
        'accepts_voucher',
        'provision_id',
    ];

    protected $casts = [
        'available_for_types'    => 'array',
        'start_blocs'            => 'array',
        'registrations_deadline' => 'datetime',
        'accepts_voucher'        => 'boolean',
        'registrations_limit'    => 'integer',
        'registrations_number'   => 'integer',
        'min_age'                => 'integer',
        'max_age'                => 'integer',
        'cost'                   => 'decimal:2',
        'distance'               => 'decimal:2',
    ];

    public function matchesAge(?int $age): bool
    {
        if ($age === null) {
            return true;
        }

        if ($this->min_age !== null && $age < $this->min_age) {
            return false;
        }

        if ($this->max_age !== null && $age > $this->max_age) {
            return false;
        }

        return true;
    }

    protected function ageRangeLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->min_age !== null && $this->max_age !== null) {
                    return "{$this->min_age} à {$this->max_age} ans";
                }
                if ($this->min_age !== null) {
                    return "dès {$this->min_age} ans";
                }
                if ($this->max_age !== null) {
                    return "jusqu'à {$this->max_age} ans";
                }

                return null;
            }
        );
    }

    protected function fillRate(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->registrations_limit || $this->registrations_limit <= 0) {
                    return 0;
                }

                return min(100, round(($this->registrations_number / $this->registrations_limit) * 100, 1));
            }
        );
    }

    public function provision()
    {
        return $this->belongsTo(Provision::class);
    }

    public function runRegistrationElements()
    {
        return $this->hasMany(RunRegistrationElement::class);
    }
}
