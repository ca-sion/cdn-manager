<?php

namespace App\Filament\Actions;

use Throwable;
use App\Models\Invoice;
use Filament\Actions\BulkAction;
use Illuminate\Support\HtmlString;
use Filament\Schemas\Components\Text;
use Filament\Notifications\Notification;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Actions\Support\InvoiceMailing;
use App\Filament\Actions\Concerns\ResolvesInvoice;

/**
 * Envoi / relance de factures en masse, avec aperçu : chaque facture est listée avec son statut,
 * son montant et l'e-mail destinataire ; celles qui ne peuvent pas être envoyées sont désactivées
 * avec la raison. Un compte rendu détaille les envois.
 */
abstract class InvoiceMailingBulkAction extends BulkAction
{
    use ResolvesInvoice;

    /** Raison du refus (null = envoi possible). */
    abstract protected function reason(?Invoice $invoice): ?string;

    /** Envoie l'e-mail et met à jour le statut. */
    abstract protected function dispatchMail(Invoice $invoice): void;

    abstract protected function verb(): string;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation(false)
            ->modalHeading(fn () => $this->verb().' en masse')
            ->modalDescription('Vérifiez la liste : seules les factures cochées sont envoyées par e-mail au client. Rien n\'est envoyé avant de valider.')
            ->deselectRecordsAfterCompletion()
            ->schema(function (Collection $records): array {
                $options = [];
                $descriptions = [];
                $default = [];
                $disabled = [];

                foreach ($records as $record) {
                    $invoice = $this->resolveInvoice($record);
                    $key = $record->getKey();
                    $reason = $this->reason($invoice);
                    $options[$key] = $invoice
                        ? ($invoice->client?->name ?? 'Client inconnu').' — facture '.$invoice->number
                        : ($record->name ?? 'Enregistrement #'.$key);

                    if ($reason) {
                        $descriptions[$key] = 'Ignorée : '.$reason;
                        $disabled[] = $key;
                    } else {
                        $descriptions[$key] = InvoiceMailing::describe($invoice);
                        $default[] = $key;
                    }
                }

                return [
                    Text::make(count($default).' facture(s) peuvent être envoyées · '.count($disabled).' ignorée(s)')->weight('bold'),
                    CheckboxList::make('record_ids')
                        ->label('Factures à envoyer')
                        ->options($options)
                        ->descriptions($descriptions)
                        ->default($default)
                        ->disableOptionWhen(fn (string $value) => in_array($value, array_map('strval', $disabled), true))
                        ->bulkToggleable()
                        ->required(),
                ];
            })
            ->action(function (Collection $records, array $data) {
                $selected = collect($data['record_ids'] ?? [])->map(fn ($id) => (string) $id);
                $sent = [];
                $skipped = [];
                $failed = [];

                foreach ($records as $record) {
                    $invoice = $this->resolveInvoice($record);
                    $label = $invoice ? ($invoice->client?->name.' — '.$invoice->number) : ($record->name ?? '#'.$record->getKey());

                    if (! $selected->contains((string) $record->getKey())) {
                        $skipped[] = $label.' (non cochée)';

                        continue;
                    }

                    // Revalide au moment de l'envoi : le statut a pu changer pendant que la fenêtre était ouverte
                    if ($reason = $this->reason($invoice)) {
                        $skipped[] = $label.' ('.$reason.')';

                        continue;
                    }

                    try {
                        $this->dispatchMail($invoice);
                        $sent[] = $label;
                    } catch (Throwable $e) {
                        report($e);
                        $failed[] = $label.' ('.$e->getMessage().')';
                    }
                }

                $lines = fn (array $items) => '<ul class="list-disc ml-5">'.implode('', array_map(fn ($i) => '<li>'.e($i).'</li>', $items)).'</ul>';
                $body = ($sent ? '<p class="font-semibold">Envoyées</p>'.$lines($sent) : '')
                    .($skipped ? '<p class="font-semibold mt-2">Ignorées</p>'.$lines($skipped) : '')
                    .($failed ? '<p class="font-semibold mt-2">Erreurs</p>'.$lines($failed) : '');

                Notification::make()
                    ->title(count($sent).' facture(s) envoyée(s)'.($skipped ? ' · '.count($skipped).' ignorée(s)' : '').($failed ? ' · '.count($failed).' erreur(s)' : ''))
                    ->body(new HtmlString($body))
                    ->persistent()
                    ->{$failed ? 'danger' : ($skipped || ! $sent ? 'warning' : 'success')}()
                    ->send();
            });
    }
}
