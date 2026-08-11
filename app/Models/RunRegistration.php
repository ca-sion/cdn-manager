<?php

namespace App\Models;

use App\Enums\RunRegistrationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
    public function routeNotificationForMail(): string
    {
        return $this->contact_email ?? null;
    }
}
