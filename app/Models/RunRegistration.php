<?php

namespace App\Models;

use App\Enums\RunRegistrationTypesEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RunRegistration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'type',
        'invoicing_company_name',
        'invoicing_address',
        'invoicing_address_extension',
        'invoicing_postal_code',
        'invoicing_locality',
        'invoicing_email',
        'invoicing_note',
        'payment_iban',
        'payment_note',
        'company_name',
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
    ];

    protected $casts = [
        'type' => RunRegistrationTypesEnum::class,
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}