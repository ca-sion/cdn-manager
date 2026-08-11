<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'run_id',
        'code',
        'is_used',
        'used_at',
        'used_by_run_registration_element_id',
        'note',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function run()
    {
        return $this->belongsTo(Run::class);
    }

    public function usedByElement()
    {
        return $this->belongsTo(RunRegistrationElement::class, 'used_by_run_registration_element_id');
    }
}
