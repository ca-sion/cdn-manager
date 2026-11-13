<?php

namespace App\Models;

use Illuminate\Support\Carbon;
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
        'gender',
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

    /**
     * Échéance d'inscription comme instant réel.
     *
     * Le champ est saisi et stocké tel quel : « 18:00 » veut dire 18:00 heure suisse. Comparée à l'heure UTC,
     * l'échéance se fermerait 1 à 2 heures trop tard : on la lit donc explicitement en Europe/Zurich
     * (l'heure d'été et d'hiver sont gérées).
     */
    public function registrationsDeadlineAt(): ?Carbon
    {
        return $this->registrations_deadline
            ? Carbon::parse($this->registrations_deadline->format('Y-m-d H:i:s'), 'Europe/Zurich')
            : null;
    }

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

    public function matchesGender(?string $gender): bool
    {
        if ($this->gender === null || $this->gender === '' || $this->gender === 'all') {
            return true;
        }

        if (empty($gender)) {
            return true;
        }

        $runGender = is_object($this->gender) ? $this->gender->value : (string) $this->gender;

        return strtoupper($runGender) === strtoupper((string) $gender);
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
