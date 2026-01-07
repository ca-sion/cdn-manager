<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'available_for_types' => 'array',
        'start_blocs' => 'array',
        'registrations_deadline' => 'date',
        'accepts_voucher' => 'boolean',
        'registrations_limit' => 'integer',
        'registrations_number' => 'integer',
        'cost' => 'decimal:2',
        'distance' => 'decimal:2',
    ];

    public function provision()
    {
        return $this->belongsTo(Provision::class);
    }
}