<?php

namespace App\Filament\Actions\Support;

use App\Models\Invoice;
use Filament\Actions\Action;
use Illuminate\Support\Number;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;
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

    public function __construct(protected string $subject = 'Facturation en masse') {}

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

    protected function section(string $title, array $items): string
    {
        return $items === [] ? '' : '<p class="mt-2 font-semibold">'.e($title).'</p><ul class="list-disc ml-5">'.implode('', $items).'</ul>';
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
            $counts['failed'] > 0                              => $notification->danger(),
            $counts['created'] + $counts['completed'] === 0    => $notification->warning(),
            $counts['skipped'] > 0                             => $notification->warning(),
            default                                            => $notification->success(),
        };

        if ($this->created !== []) {
            $notification->actions([
                Action::make('undo')
                    ->label('Annuler ces factures')
                    ->color('danger')
                    ->button()
                    ->dispatch('undo-bulk-invoices', ['invoiceIds' => $this->createdIds()])
                    ->close(),
                Action::make('close')->label('Fermer')->color('gray')->close(),
            ]);
        }

        $notification->send();
    }
}
