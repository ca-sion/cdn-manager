<?php

namespace App\Models;

use App\Enums\RunRegistrationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RunRegistration extends Model
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'client_id',
        'invoice_id',
        'run_registration_type',
        'type',
        'company_name',
        'company_bloc',
        'school_name',
        'school_postal_code',
        'school_locality',
        'school_country',
        'school_class_level',
        'school_class_holder_first_name',
        'school_class_holder_last_name',
        'school_class_holder_email',
        'school_class_holder_phone',
        'contact_first_name',
        'contact_last_name',
        'contact_email',
        'contact_phone',
        'invoicing_company_name',
        'invoicing_address',
        'invoicing_address_extension',
        'invoicing_postal_code',
        'invoicing_locality',
        'invoicing_email',
        'invoicing_note',
        'payment_iban',
        'payment_note',
    ];

    protected $casts = [
        'run_registration_type' => RunRegistrationType::class,
    ];

    /**
     * Accessor & Mutator for legacy 'type' attribute.
     */
    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->run_registration_type,
            set: fn ($value) => ['run_registration_type' => $value instanceof RunRegistrationType ? $value->value : (is_object($value) && property_exists($value, 'value') ? $value->value : $value)],
        );
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function runRegistrationElements()
    {
        return $this->hasMany(RunRegistrationElement::class);
    }

    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail(): ?string
    {
        $email = $this->contact_email
            ?: ($this->school_class_holder_email
            ?: ($this->invoicing_email
            ?: ($this->client?->email
            ?: ($this->client?->invoicing_email ?: null))));

        return ! empty($email) ? trim($email) : null;
    }

    public static function getCompanyRun(): ?Run
    {
        $defaultId = setting('default_run_company');
        if ($defaultId && ($run = Run::find($defaultId))) {
            return $run;
        }

        return Run::where('name', 'like', '%Entreprise%')
            ->orWhere(function ($q) {
                $q->whereJsonContains('available_for_types', 'company')
                    ->orWhereNull('available_for_types');
            })->first();
    }

    public static function getSchoolRun(): ?Run
    {
        $defaultId = setting('default_run_school');
        if ($defaultId && ($run = Run::find($defaultId))) {
            return $run;
        }

        return Run::where('name', 'like', '%Interclasses%')
            ->orWhere(function ($q) {
                $q->whereJsonContains('available_for_types', 'school')
                    ->orWhereNull('available_for_types');
            })->first();
    }

    /**
     * Calculate estimated total price for any collection or array of element rows (transversal reusable logic).
     */
    public static function calculateElementsEstimatedTotal(iterable $elements, string $type = 'company'): float
    {
        $total = 0.0;

        if ($type === 'company') {
            $companyRun = static::getCompanyRun();
            $defaultCost = (float) ($companyRun?->provision?->product?->price?->amount ?? $companyRun?->cost ?? 0);
        } elseif ($type === 'school') {
            $schoolRun = static::getSchoolRun();
            $defaultCost = (float) ($schoolRun?->provision?->product?->price?->amount ?? $schoolRun?->cost ?? 0);
        } else {
            $defaultCost = null;
        }

        foreach ($elements as $row) {
            $rowArr = is_array($row) ? $row : $row->toArray();

            if (empty($rowArr['first_name']) && empty($rowArr['last_name'])) {
                continue;
            }

            if (! empty($rowArr['has_free_registration_fee'])) {
                continue;
            }

            if ($defaultCost !== null && empty($rowArr['run_id'])) {
                $total += $defaultCost;
            } else {
                $runId = ! empty($rowArr['run_id']) ? $rowArr['run_id'] : null;
                $run = $runId ? (is_object($row) && isset($row->run) ? $row->run : Run::find($runId)) : null;
                if ($run) {
                    $cost = (float) ($run->provision?->product?->price?->amount ?? $run->cost ?? 0);
                    $total += $cost;
                }
            }
        }

        return $total;
    }

    /**
     * Calculate estimated total price for this stored registration batch.
     */
    public function calculateEstimatedTotal(): float
    {
        $type = is_object($this->run_registration_type) ? $this->run_registration_type->value : (string) $this->run_registration_type;

        return static::calculateElementsEstimatedTotal($this->runRegistrationElements, $type);
    }

    /**
     * Accessor for estimated_total attribute.
     */
    public function getEstimatedTotalAttribute(): float
    {
        return $this->calculateEstimatedTotal();
    }

    /**
     * Accessor for participants_count attribute.
     */
    public function getParticipantsCountAttribute(): int
    {
        return $this->runRegistrationElements()->count();
    }

    /**
     * Display name/title for the group/company/school registration.
     */
    public function getDisplayNameAttribute(): string
    {
        $type = is_object($this->run_registration_type) ? $this->run_registration_type->value : $this->run_registration_type;

        if ($type === 'company') {
            return $this->company_name ?: (trim($this->contact_first_name.' '.$this->contact_last_name) ?: 'Entreprise #'.$this->id);
        }

        if ($type === 'school') {
            $name = $this->school_name ?: 'Centre Scolaire';
            if ($this->school_class_level) {
                $name .= ' ('.$this->school_class_level.')';
            }

            return $name;
        }

        if (! empty($this->company_name)) {
            return $this->company_name;
        }

        return trim($this->contact_first_name.' '.$this->contact_last_name) ?: ('Dossier #'.$this->id);
    }
}
