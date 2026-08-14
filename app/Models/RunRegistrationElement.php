<?php

namespace App\Models;

use App\Enums\Gender;
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
        'gender'                    => Gender::class,
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

    protected static function booted(): void
    {
        static::saving(function (RunRegistrationElement $element) {
            if (empty($element->run_id) && $element->run_registration_id) {
                $registration = $element->runRegistration;
                if ($registration) {
                    $type = is_object($registration->run_registration_type)
                        ? $registration->run_registration_type->value
                        : (string) $registration->run_registration_type;

                    $defaultRunId = match ($type) {
                        'school'  => setting('default_run_school'),
                        'company' => setting('default_run_company'),
                        'elite'   => setting('default_run_elite'),
                        default   => null,
                    };

                    if (! $defaultRunId) {
                        $defaultRun = Run::where(function ($q) use ($type) {
                            $q->whereJsonContains('available_for_types', $type)
                                ->orWhereNull('available_for_types');
                        })->first();
                        $defaultRunId = $defaultRun?->id;
                    }

                    if ($defaultRunId) {
                        $element->run_id = $defaultRunId;
                        if (empty($element->run_name)) {
                            $run = Run::find($defaultRunId);
                            $element->run_name = $run?->name;
                        }
                    }
                }
            }
        });
    }
}
