<?php

namespace App\Filament\Actions;

use Throwable;
use App\Models\Client;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Text;
use App\Services\RunRegistrationService;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Actions\Support\BulkInvoiceReport;
use App\Filament\Actions\Support\BulkInvoicePreview;

/**
 * Génère, en masse, les factures de courses des clients liés aux inscriptions sélectionnées.
 *
 * Attention : la facture est établie PAR CLIENT. Elle couvre toutes les inscriptions non facturées du client,
 * pas seulement celles qui sont cochées : l'aperçu l'indique pour chaque client.
 * Les inscriptions Élite ne sont jamais facturées automatiquement.
 */
class GenerateRegistrationInvoicesBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'generateInvoices';
    }

    /**
     * Facture les clients cochés et alimente le rapport.
     *
     * @param  iterable<int|string>  $clientIds
     */
    public static function process(iterable $clientIds, BulkInvoiceReport $report, string $suffix = ''): void
    {
        $service = app(RunRegistrationService::class);
        $names = Client::whereIn('id', collect($clientIds)->all())->pluck('name', 'id');

        foreach ($clientIds as $clientId) {
            $name = ($names[$clientId] ?? 'Client #'.$clientId).$suffix;

            try {
                $draftBefore = $service->plan((int) $clientId)['draft'];
                $invoice = $service->createInvoiceForClient((int) $clientId);
                $draftBefore ? $report->completed($name, $invoice) : $report->created($name, $invoice);
            } catch (Throwable $e) {
                report($e);
                $report->failed($name, $e->getMessage());
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Facturer les inscriptions (par client)')
            ->icon('heroicon-o-document-currency-dollar')
            ->modalHeading('Facturer les inscriptions de courses')
            ->modalDescription('La facture est établie par client et couvre toutes ses inscriptions non facturées, même si une seule est cochée. Rien n\'est créé avant de valider.')
            ->modalSubmitActionLabel('Créer les factures cochées')
            ->deselectRecordsAfterCompletion()
            ->schema(function (Collection $records): array {
                $withoutClient = $records->whereNull('client_id')->count();
                $clientIds = $records->whereNotNull('client_id')->pluck('client_id')->unique()->map(fn ($id) => (int) $id)->values()->all();
                $full = BulkInvoicePreview::registrations($clientIds, $records);
                $view = BulkInvoicePreview::compact($full);
                $preview = $view['preview'];

                $components = [
                    Text::make(count($clientIds).' client(s) · '.$full['counts']['todo'].' avec des inscriptions à facturer'.($withoutClient ? ' · '.$withoutClient.' inscription(s) sans client ignorée(s)' : ''))->weight('bold'),
                ];

                if ($withoutClient > 0) {
                    $components[] = Text::make('Les inscriptions sans client ne peuvent pas être facturées : associez-leur un client d\'abord (action « Associer un client »).')->color('warning');
                }

                $components[] = CheckboxList::make('client_ids')
                    ->label('Clients à facturer')
                    ->options($preview['options'])
                    ->descriptions($preview['descriptions'])
                    ->default($preview['default'])
                    ->disableOptionWhen(fn (string $value) => in_array((int) $value, $preview['disabled'], true))
                    ->bulkToggleable()
                    ->searchable()
                    ->required();

                if ($view['summary']) {
                    $components[] = Text::make($view['summary'])->color('gray');
                }

                return $components;
            })
            ->action(function (Collection $records, array $data) {
                $report = new BulkInvoiceReport('Génération en masse des factures d\'inscriptions');
                $selected = collect($data['client_ids'] ?? [])->map(fn ($id) => (int) $id);
                $clientIds = $records->whereNotNull('client_id')->pluck('client_id')->unique()->map(fn ($id) => (int) $id);
                $names = Client::whereIn('id', $clientIds)->pluck('name', 'id');

                if (($without = $records->whereNull('client_id')->count()) > 0) {
                    $report->skipped($without.' inscription(s) sans client', 'associez-leur un client avant de facturer');
                }

                foreach ($clientIds->reject(fn ($id) => $selected->contains($id)) as $id) {
                    $report->skipped($names[$id] ?? 'Client #'.$id, 'non coché');
                }

                self::process($selected->intersect($clientIds), $report);
                $report->send();
            });
    }
}
