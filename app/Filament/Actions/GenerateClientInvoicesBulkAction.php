<?php

namespace App\Filament\Actions;

use Throwable;
use RuntimeException;
use App\Models\Client;
use App\Services\InvoiceService;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Text;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Actions\Concerns\ResolvesClientId;
use App\Filament\Actions\Support\BulkInvoiceReport;
use App\Filament\Actions\Support\BulkInvoicePreview;

/**
 * Génère, en masse, les factures des clients sélectionnés, pour les DEUX familles de factures :
 * - les factures de prestations
 * - les factures d'inscriptions de courses
 *
 * 1. un aperçu liste chaque client avec ce qui va se passer, famille par famille
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

        $this->label('Générer les factures')
            ->icon('heroicon-o-document-currency-dollar')
            ->modalHeading('Générer les factures')
            ->modalDescription('Deux types de factures sont proposés : prestations et inscriptions de courses. Seuls les clients cochés seront facturés. Rien n\'est créé avant de valider.')
            ->modalSubmitActionLabel('Créer les factures cochées')
            ->deselectRecordsAfterCompletion()
            ->schema(function (Collection $records): array {
                $clientIds = $this->clientIds($records);
                $provisions = BulkInvoicePreview::provisions($clientIds);
                $registrations = BulkInvoicePreview::registrations($clientIds);

                // La liste des inscriptions ne concerne que les clients qui en ont
                $withRegistrations = array_keys(array_filter($registrations['descriptions'], fn ($d) => ! str_contains($d, 'aucune inscription')));
                $registrationsFiltered = $registrations;
                $registrationsFiltered['options'] = array_intersect_key($registrations['options'], array_flip($withRegistrations));
                $registrationsFiltered['descriptions'] = array_intersect_key($registrations['descriptions'], $registrationsFiltered['options']);
                $registrationsFiltered['disabled'] = array_values(array_intersect($registrations['disabled'], $withRegistrations));

                // Longue sélection : on ne liste que ce qui est actionnable, le reste est résumé
                $provisionsView = BulkInvoicePreview::compact($provisions);
                $registrationsView = BulkInvoicePreview::compact($registrationsFiltered);
                $provisionsList = $provisionsView['preview'];
                $registrationsList = $registrationsView['preview'];

                $components = [
                    Text::make(count($clientIds).' client(s) sélectionné(s)')->weight('bold'),
                    Text::make('Prestations : '.$provisions['counts']['new'].' nouvelle(s) facture(s) · '.$provisions['counts']['draft'].' brouillon(s) à compléter · '.$provisions['counts']['nothing'].' sans rien à facturer'),
                    Text::make('Inscriptions de courses : '.$registrations['counts']['todo'].' client(s) avec des inscriptions à facturer'),
                ];

                if ($provisionsList['options'] !== []) {
                    $components[] = CheckboxList::make('provision_client_ids')
                        ->label('Factures de PRESTATIONS')
                        ->options($provisionsList['options'])
                        ->descriptions($provisionsList['descriptions'])
                        ->default($provisionsList['default'])
                        ->disableOptionWhen(fn (string $value) => in_array((int) $value, $provisionsList['disabled'], true))
                        ->bulkToggleable()
                        ->searchable();
                }

                if ($provisionsView['summary']) {
                    $components[] = Text::make('Prestations — '.$provisionsView['summary'])->color('gray');
                }

                if ($registrationsList['options'] !== []) {
                    $components[] = CheckboxList::make('registration_client_ids')
                        ->label('Factures d\'INSCRIPTIONS de courses (une facture par client, toutes ses inscriptions non facturées)')
                        ->options($registrationsList['options'])
                        ->descriptions($registrationsList['descriptions'])
                        ->default($registrationsList['default'])
                        ->disableOptionWhen(fn (string $value) => in_array((int) $value, $registrationsList['disabled'], true))
                        ->bulkToggleable()
                        ->searchable();
                }

                if ($registrationsView['summary']) {
                    $components[] = Text::make('Inscriptions — '.$registrationsView['summary'])->color('gray');
                }

                return $components;
            })
            ->action(function (Collection $records, array $data) {
                InvoiceService::flushCache();
                $report = new BulkInvoiceReport('Génération en masse des factures (prestations et inscriptions)');
                $clientIds = $this->clientIds($records);
                $report->rememberEngagements($clientIds);
                $names = Client::whereIn('id', $clientIds)->pluck('name', 'id');
                $provisionIds = collect($data['provision_client_ids'] ?? [])->map(fn ($id) => (int) $id);
                $registrationIds = collect($data['registration_client_ids'] ?? [])->map(fn ($id) => (int) $id);

                if ($provisionIds->isEmpty() && $registrationIds->isEmpty()) {
                    $report->skipped('Aucun client coché', 'rien n\'a été facturé');
                }

                // 1. Prestations
                foreach ($provisionIds->intersect($clientIds) as $clientId) {
                    $name = ($names[$clientId] ?? 'Client #'.$clientId).' (prestations)';

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

                // 2. Inscriptions de courses
                GenerateRegistrationInvoicesBulkAction::process($registrationIds->intersect($clientIds), $report, ' (inscriptions)');

                $report->send();
            });
    }
}
