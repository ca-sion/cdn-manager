<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\AppHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Edition extends Model
{
    use HasFactory;

    /**
     * Valeurs par défaut fixes des métadonnées utilisées dans les notifications.
     * Les valeurs liées à l'année (dates, numéros d'édition, PDF VIP) sont calculées dynamiquement.
     *
     * @var array<string, string>
     */
    public const META_DEFAULTS = [
        'expected_participants'      => '6000',
        'advertisers_email'          => 'pub@coursedenoel.ch',
        'advertisers_paper_form_url' => 'https://coursedenoel.ch/assets/documents/fo-annonceurs.pdf',
        'advertisers_signature'      => 'Michael Ravedoni, Administration',
        'interclass_participants'    => "1'200",
        'interclass_contact_address' => "Christian Masserey\nChemin des Amandiers 108\n1950 Sion\n079 453 60 03\nchristian.masserey@bluewin.ch",
        'interclass_signature'       => "Christian Masserey\nResponsable du Concours interclasses",
        'vip_email'                  => 'info@coursedenoel.ch',
        'vip_start_time'             => '16h30',
        'vip_signature'              => "Benoît Torrent\nCo-Président du Co\n\nEsther Debons\nCo-Présidente du Co",
        'vouchers_deadline'          => '22 novembre',
        'vouchers_company_form_url'  => 'https://manager.coursedenoel.ch/registrations/company',
        'vouchers_datasport_url'     => 'https://coursedenoel.ch/courses/challenge-entreprises#content-inscriptions',
        'vouchers_info_url'          => 'https://coursedenoel.ch/courses/challenge-entreprises',
        'vouchers_tshirt_deadline'   => '11 novembre',
        'vouchers_tshirt_url'        => 'https://coursedenoel.ch/courses/challenge-entreprises#content-t-shirt',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public static function current(): ?self
    {
        $editionId = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');

        if ($editionId) {
            $edition = static::find($editionId);
            if ($edition) {
                return $edition;
            }
        }

        return static::query()->orderByDesc('year')->first();
    }

    public static function defaultMeta(string $key, ?string $fallback = null, ?int $year = null): string
    {
        $year ??= (int) static::currentYear();
        $eventDay = Carbon::create($year, 12, 1)
            ->nthOfMonth(2, Carbon::SATURDAY)
            ->locale('fr');

        return match ($key) {
            'event_date'                   => $eventDay->translatedFormat('j F Y'),
            'event_short_date'             => $eventDay->translatedFormat('j F'),
            'cdn_edition_number'           => ($year - 1969).'ᵉ',
            'trail_edition_number'         => ($year - 2018).'ᵉ',
            'interclass_edition_number'    => ($year - 2000).'e',
            'interclass_deadline'          => "9 novembre {$year}",
            'interclass_relaunch_deadline' => "23 novembre {$year}",
            'vip_deadline'                 => "4 décembre {$year}",
            'vip_invitation_pdf_url'       => "https://coursedenoel.ch/assets/documents/{$year}-invitation-vip-simple.pdf",
            default                        => self::META_DEFAULTS[$key] ?? $fallback ?? '',
        };
    }

    public function meta(string $key, ?string $default = null): string
    {
        $value = Arr::get($this->metadata ?? [], $key);

        if (filled($value)) {
            return (string) $value;
        }

        $year = is_numeric($this->year) ? (int) $this->year : null;

        return $default ?? self::defaultMeta($key, year: $year);
    }

    public static function currentMeta(string $key, ?string $default = null): string
    {
        return static::current()?->meta($key, $default) ?? ($default ?? self::defaultMeta($key));
    }

    public static function currentYear(): string
    {
        return (string) (static::current()?->year ?? now()->format('Y'));
    }
}
