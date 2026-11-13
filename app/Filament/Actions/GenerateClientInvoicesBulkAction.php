<?php

namespace App\Filament\Actions;

use Throwable;
use RuntimeException;
use App\Models\Client;
use Illuminate\Support\Number;
use App\Services\InvoiceService;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Text;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Actions\Concerns\ResolvesClientId;
use App\Filament\Actions\Support\BulkInvoiceReport;

/**
 * Génère, en masse, les factures de prestations des clients sélectionnés.
 *
 * 1. un aperçu liste chaque client avec ce qui va se passer (nouvelle facture / brouillon complété / rien à facturer)
 * 2. seuls les clients cochés sont traités (ceux sans rien à facturer sont désactivés)
 * 3. un rapport persistant détaille le résultat et permet d'annuler les factures créées
 */
class GenerateClientInvoicesBulkAction extends BulkAction
{
    use ResolvesClientId;

    public static function getDefaultName(): ?string
    {
        return 'generateInvoices';
    }

    /**
     * @param  Collection<int, Model>  $records
     * @return array<int, int>
     */
    protected function clientIds(Collection $records): array
    {
        return $records->map(fn ($record) => $this->resolveClientId($record))->filter()->unique()->values()->all();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Générer les factures de prestations')
            ->icon('heroicon-o-document-currency-dollar')
            ->modalHeading('Générer les factures de prestations')
            ->modalDescription('Vérifiez la liste : seuls les clients cochés seront facturés. Rien n\'est créé avant de valider.')
            ->modalSubmitActionLabel('Créer les factures cochées')
            ->deselectRecordsAfterCompletion()
            ->schema(function (Collection $records): array {
                $clientIds = $this->clientIds($records);
                $names = Client::whereIn('id', $clientIds)->pluck('name', 'id');

                $options = [];
                $descriptions = [];
                $default = [];
                $disabled = [];
                $counts = ['new' => 0, 'draft' => 0, 'nothing' => 0];

                foreach ($clientIds as $clientId) {
                    $preview = InvoiceService::previewInvoiceByClient($clientId);
                    $counts[$preview['action']]++;
                    $name = $names[$clientId] ?? 'Client #'.$clientId;

                    $options[$clientId] = $name;
                    $amount = Number::currency($preview['amount'], 'CHF', 'fr_CH');

                    switch ($preview['action']) {
                        case 'new':
                            $descriptions[$clientId] = "Nouvelle facture · {$preview['count']} prestation(s) · {$amount}";
                            $default[] = $clientId;
                            break;
                        case 'draft':
                            $descriptions[$clientId] = "Complète le brouillon {$preview['draft']->number} · {$preview['count']} prestation(s) · {$amount}";
                            $default[] = $clientId;
                            break;
                        default:
                            $descriptions[$clientId] = 'Rien à facturer ('.$preview['reason'].')';
                            $disabled[] = $clientId;
                    }
                }

                return [
                    Text::make(count($clientIds).' client(s) sélectionné(s) : '.$counts['new'].' nouvelle(s) facture(s) · '.$counts['draft'].' brouillon(s) à compléter · '.$counts['nothing'].' sans rien à facturer')->weight('bold'),
                    CheckboxList::make('client_ids')
                        ->label('Clients à facturer')
                        ->options($options)
                        ->descriptions($descriptions)
                        ->default($default)
                        ->disableOptionWhen(fn (string $value) => in_array((int) $value, $disabled, true))
                        ->bulkToggleable()
                        ->searchable()
                        ->required(),
                ];
            })
            ->action(function (Collection $records, array $data) {
                $report = new BulkInvoiceReport('Génération en masse des factures de prestations');
                $selected = collect($data['client_ids'] ?? [])->map(fn ($id) => (int) $id);
                $names = Client::whereIn('id', $this->clientIds($records))->pluck('name', 'id');

                foreach ($this->clientIds($records) as $clientId) {
                    $name = $names[$clientId] ?? 'Client #'.$clientId;

                    if (! $selected->contains($clientId)) {
                        $report->skipped($name, 'non coché');

                        continue;
                    }

                    try {
                        $invoice = InvoiceService::generateInvoiceByClient($clientId);
                        $invoice->wasRecentlyCreated ? $report->created($name, $invoice) : $report->completed($name, $invoice);
                    } catch (RuntimeException $e) {
                        $report->skipped($name, $e->getMessage());
                    } catch (Throwable $e) {
                        report($e);
                        $report->failed($name, $e->getMessage());
                    }
                }

                $report->send();
            });
    }
}
