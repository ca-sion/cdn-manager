<?php

namespace App\Filament\Actions\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

/**
 * Rapport d'un envoi d'emails : envoyés / ignorés / erreurs, dans une notification persistante et dans les logs.
 * Un email envoyé ne peut pas être annulé : il n'y a donc pas de bouton d'annulation.
 */
class BulkMailReport
{
    protected const DISPLAY_LIMIT = 15;

    /** @var array<int, string> */
    protected array $sent = [];

    /** @var array<int, string> */
    protected array $skipped = [];

    /** @var array<int, string> */
    protected array $failed = [];

    public function __construct(protected string $subject) {}

    public function sent(string $client, string $email): static
    {
        $this->sent[] = $client.' → '.$email;

        return $this;
    }

    public function skipped(string $client, string $reason): static
    {
        $this->skipped[] = $client.' — '.$reason;

        return $this;
    }

    public function failed(string $client, string $reason): static
    {
        $this->failed[] = $client.' — '.$reason;

        return $this;
    }

    /** @return array{sent: int, skipped: int, failed: int} */
    public function summary(): array
    {
        return ['sent' => count($this->sent), 'skipped' => count($this->skipped), 'failed' => count($this->failed)];
    }

    protected function section(string $title, array $items): string
    {
        if ($items === []) {
            return '';
        }

        $more = count($items) > self::DISPLAY_LIMIT
            ? '<li style="font-style:italic">… et '.(count($items) - self::DISPLAY_LIMIT).' autre(s) (détail complet dans les logs)</li>'
            : '';

        return '<p style="margin-top:.5rem;font-weight:600">'.e($title).' ('.count($items).')</p><ul style="list-style:disc;margin-left:1.25rem">'
            .implode('', array_map(fn ($i) => '<li>'.e($i).'</li>', array_slice($items, 0, self::DISPLAY_LIMIT))).$more.'</ul>';
    }

    public function send(): void
    {
        $counts = $this->summary();

        Log::info($this->subject, ['sent' => $this->sent, 'skipped' => $this->skipped, 'failed' => $this->failed]);

        $title = collect([
            $counts['sent'].' email(s) envoyé(s)',
            $counts['skipped'] ? $counts['skipped'].' ignoré(s)' : null,
            $counts['failed'] ? $counts['failed'].' erreur(s)' : null,
        ])->filter()->implode(' · ');

        $notification = Notification::make()
            ->title($title)
            ->body(new HtmlString(
                $this->section('Envoyés', $this->sent)
                .$this->section('Ignorés', $this->skipped)
                .$this->section('Erreurs', $this->failed)
                .'<p style="margin-top:.5rem;font-style:italic">Un email envoyé ne peut pas être annulé.</p>'
            ))
            ->persistent();

        match (true) {
            $counts['failed'] > 0                           => $notification->danger(),
            $counts['sent'] === 0 || $counts['skipped'] > 0 => $notification->warning(),
            default                                         => $notification->success(),
        };

        $notification->send();
    }
}
