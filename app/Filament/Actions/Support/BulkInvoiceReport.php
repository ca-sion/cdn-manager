<?php

namespace App\Filament\Actions\Support;

use App\Models\Invoice;
use Filament\Actions\Action;
use Illuminate\Support\Number;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;
use App\Services\EngagementSnapshot;
use Filament\Notifications\Notification;
use App\Filament\Resources\InvoiceResource;

/**
 * Rapport d'une action en masse : ce qui a été créé / complété / ignoré / en erreur.
 * Il s'affiche dans une notification persistante (liens vers les factures, bouton d'annulation)
 * et est écrit dans les logs.
 */
class BulkInvoiceReport
{
    /** @var array<int, array{client: string, invoice: Invoice}> */
    protected array $created = [];

    /** @var array<int, array{client: string, invoice: Invoice}> */
    protected array $completed = [];

    /** @var array<int, array{client: string, reason: string}> */
    protected array $skipped = [];

    /** @var array<int, array{client: string, reason: string}> */
    protected array $failed = [];

    /** @var array<int, array{existed: bool, stage: ?string, status: ?string}> suivi des clients AVANT l'action */
    protected array $engagementsBefore = [];

    public function __construct(protected string $subject = 'Facturation en masse') {}

    /**
     * À appeler AVANT de générer : mémorise le suivi (étape/statut) des clients pour pouvoir le restaurer à l'annulation.
     *
     * @param  iterable<int|string>  $clientIds
     */
    public function rememberEngagements(iterable $clientIds): static
    {
        $this->engagementsBefore = EngagementSnapshot::capture($clientIds);

        return $this;
    }

    /**
     * Suivi avant / après pour les clients qui ont reçu au moins une nouvelle facture.
     *
     * @return array<int, array{before: array, after: array}>
     */
    protected function engagementPairs(): array
    {
        $clientIds = collect($this->created)->pluck('invoice.client_id')->unique()->values()->all();
        $after = EngagementSnapshot::capture($clientIds);

        return collect($clientIds)
            ->filter(fn ($id) => isset($this->engagementsBefore[$id]))
            ->mapWithKeys(fn ($id) => [$id => ['before' => $this->engagementsBefore[$id], 'after' => $after[$id]]])
            ->all();
    }

    public function created(string $client, Invoice $invoice): static
    {
        $this->created[] = compact('client', 'invoice');

        return $this;
    }

    public function completed(string $client, Invoice $invoice): static
    {
        $this->completed[] = compact('client', 'invoice');

        return $this;
    }

    public function skipped(string $client, string $reason): static
    {
        $this->skipped[] = compact('client', 'reason');

        return $this;
    }

    public function failed(string $client, string $reason): static
    {
        $this->failed[] = compact('client', 'reason');

        return $this;
    }

    /**
     * Ids of the invoices that can be cancelled afterwards (only the newly created ones).
     *
     * @return array<int, int>
     */
    public function createdIds(): array
    {
        return collect($this->created)->pluck('invoice.id')->all();
    }

    public function summary(): array
    {
        return [
            'created'   => count($this->created),
            'completed' => count($this->completed),
            'skipped'   => count($this->skipped),
            'failed'    => count($this->failed),
        ];
    }

    protected function invoiceLine(array $row): string
    {
        $invoice = $row['invoice'];
        $url = InvoiceResource::getUrl('edit', ['record' => $invoice->id]);

        return '<li>'.e($row['client']).' — <a href="'.e($url).'" target="_blank" class="underline">facture '.e($invoice->number).'</a> · '.e(Number::currency((float) $invoice->total, 'CHF', 'fr_CH')).'</li>';
    }

    /** Nombre maximum de lignes affichées par section (le détail complet reste dans les logs). */
    protected const DISPLAY_LIMIT = 15;

    protected function section(string $title, array $items): string
    {
        if ($items === []) {
            return '';
        }

        $more = count($items) > self::DISPLAY_LIMIT
            ? '<li class="italic">… et '.(count($items) - self::DISPLAY_LIMIT).' autre(s) (détail complet dans les logs et dans la liste des factures)</li>'
            : '';

        return '<p class="mt-2 font-semibold">'.e($title).' ('.count($items).')</p><ul class="list-disc ml-5">'
            .implode('', array_slice($items, 0, self::DISPLAY_LIMIT)).$more.'</ul>';
    }

    public function send(): void
    {
        $counts = $this->summary();

        Log::info($this->subject, [
            'created'   => collect($this->created)->map(fn ($r) => ['client' => $r['client'], 'invoice' => $r['invoice']->number])->all(),
            'completed' => collect($this->completed)->map(fn ($r) => ['client' => $r['client'], 'invoice' => $r['invoice']->number])->all(),
            'skipped'   => $this->skipped,
            'failed'    => $this->failed,
        ]);

        $title = collect([
            $counts['created'] ? $counts['created'].' facture(s) créée(s)' : null,
            $counts['completed'] ? $counts['completed'].' brouillon(s) complété(s)' : null,
            $counts['skipped'] ? $counts['skipped'].' ignoré(s)' : null,
            $counts['failed'] ? $counts['failed'].' erreur(s)' : null,
        ])->filter()->implode(' · ') ?: 'Aucune facture créée';

        $body = $this->section('Créées', array_map(fn ($r) => $this->invoiceLine($r), $this->created))
            .$this->section('Brouillons complétés (non annulables automatiquement)', array_map(fn ($r) => $this->invoiceLine($r), $this->completed))
            .$this->section('Ignorés', array_map(fn ($r) => '<li>'.e($r['client']).' — '.e($r['reason']).'</li>', $this->skipped))
            .$this->section('Erreurs', array_map(fn ($r) => '<li>'.e($r['client']).' — '.e($r['reason']).'</li>', $this->failed));

        $notification = Notification::make()
            ->title($title)
            ->body(new HtmlString($body ?: 'Rien n\'a été modifié.'))
            ->persistent();

        $notification = match (true) {
            $counts['failed'] > 0                           => $notification->danger(),
            $counts['created'] + $counts['completed'] === 0 => $notification->warning(),
            $counts['skipped'] > 0                          => $notification->warning(),
            default                                         => $notification->success(),
        };

        if ($this->created !== []) {
            $notification->actions([
                Action::make('undo')
                    ->label('Annuler ces factures')
                    ->color('danger')
                    ->button()
                    ->dispatch('undo-bulk-invoices', ['invoiceIds' => $this->createdIds(), 'engagements' => $this->engagementPairs()])
                    ->close(),
                Action::make('close')->label('Fermer')->color('gray')->close(),
            ]);
        }

        $notification->send();
    }
}
