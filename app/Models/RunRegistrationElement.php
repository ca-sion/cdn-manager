<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RunRegistrationElement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'run_registration_id',
        'first_name',
        'last_name',
        'birthdate',
        'gender',
        'nationality',
        'email',
        'team',
        'run_id',
        'run_name',
        'bloc',
        'with_video',
        'voucher_code',
        'address',
        'address_extension',
        'postal_code',
        'locality',
        'country',
        'iban',
        'payment_note',
        'has_free_registration_fee',
        'has_bonus_start',
        'bonus_start_amount',
        'bonus_ranking_amount',
        'bonus_arrival_amount',
        'has_accommodation',
        'accommodation_friday',
        'accommodation_saturday',
        'accommodation_precision',
        'has_expense_reimbursement',
        'expense_reimbursement_precision',
    ];

    protected $casts = [
        'birthdate'                 => 'date',
        'with_video'                => 'boolean',
        'has_free_registration_fee' => 'boolean',
        'has_bonus_start'           => 'boolean',
        'has_accommodation'         => 'boolean',
        'accommodation_friday'      => 'boolean',
        'accommodation_saturday'    => 'boolean',
        'has_expense_reimbursement' => 'boolean',
        'bonus_start_amount'        => 'decimal:2',
        'bonus_ranking_amount'      => 'decimal:2',
        'bonus_arrival_amount'      => 'decimal:2',
    ];

    public function runRegistration()
    {
        return $this->belongsTo(RunRegistration::class);
    }

    public function run()
    {
        return $this->belongsTo(Run::class);
    }
}
