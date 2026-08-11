<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
        'cost'                   => 'decimal:2',
        'distance'               => 'decimal:2',
    ];

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
