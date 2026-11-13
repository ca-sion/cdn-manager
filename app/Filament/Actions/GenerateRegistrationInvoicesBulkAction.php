<?php

namespace App\Filament\Actions;

use Throwable;
use App\Models\Client;
use Illuminate\Support\Number;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Text;
use App\Services\RunRegistrationService;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Actions\Support\BulkInvoiceReport;

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
                $service = app(RunRegistrationService::class);
                $withoutClient = $records->whereNull('client_id')->count();
                $selectedByClient = $records->whereNotNull('client_id')->groupBy('client_id');
                $names = Client::whereIn('id', $selectedByClient->keys())->pluck('name', 'id');

                $options = [];
                $descriptions = [];
                $default = [];
                $disabled = [];
                $todo = 0;

                foreach ($selectedByClient as $clientId => $selection) {
                    $plan = $service->plan((int) $clientId);
                    $options[$clientId] = $names[$clientId] ?? 'Client #'.$clientId;

                    if ($plan['elements']->isEmpty()) {
                        $descriptions[$clientId] = $plan['registrations']->isEmpty() && $plan['elite_excluded'] > 0
                            ? 'Rien à facturer (inscriptions Élite uniquement)'
                            : 'Rien à facturer (toutes les inscriptions sont déjà facturées)';
                        $disabled[] = $clientId;

                        continue;
                    }

                    $todo++;
                    $default[] = $clientId;
                    $selectedIds = $selection->pluck('id');
                    $inSelection = $plan['elements']->whereIn('run_registration_id', $selectedIds)->count();
                    $amount = Number::currency($plan['amount'], 'CHF', 'fr_CH');

                    $text = ($plan['draft'] ? "Recalcule le brouillon {$plan['draft']->number}" : 'Nouvelle facture')
                        ." · {$plan['elements']->count()} inscription(s) ({$inSelection} dans votre sélection) · {$amount}";
                    if ($plan['quota_ids'] !== []) {
                        $text .= ' · '.count($plan['quota_ids']).' marquée(s) « incluse(s) » par le quota';
                    }
                    if ($plan['warnings'] !== []) {
                        $text .= ' ⚠ '.implode(' ; ', $plan['warnings']);
                    }
                    $descriptions[$clientId] = $text;
                }

                $components = [
                    Text::make($selectedByClient->count().' client(s) · '.$todo.' avec des inscriptions à facturer'.($withoutClient ? ' · '.$withoutClient.' inscription(s) sans client ignorée(s)' : ''))->weight('bold'),
                ];

                if ($withoutClient > 0) {
                    $components[] = Text::make('Les inscriptions sans client ne peuvent pas être facturées : associez-leur un client d\'abord (action « Associer un client »).')->color('warning');
                }

                $components[] = CheckboxList::make('client_ids')
                    ->label('Clients à facturer')
                    ->options($options)
                    ->descriptions($descriptions)
                    ->default($default)
                    ->disableOptionWhen(fn (string $value) => in_array((int) $value, $disabled, true))
                    ->bulkToggleable()
                    ->searchable()
                    ->required();

                return $components;
            })
            ->action(function (Collection $records, array $data) {
                $service = app(RunRegistrationService::class);
                $report = new BulkInvoiceReport('Génération en masse des factures de courses');
                $selected = collect($data['client_ids'] ?? [])->map(fn ($id) => (int) $id);
                $clientIds = $records->whereNotNull('client_id')->pluck('client_id')->unique();
                $names = Client::whereIn('id', $clientIds)->pluck('name', 'id');

                if (($without = $records->whereNull('client_id')->count()) > 0) {
                    $report->skipped($without.' inscription(s) sans client', 'associez-leur un client avant de facturer');
                }

                foreach ($clientIds as $clientId) {
                    $name = $names[$clientId] ?? 'Client #'.$clientId;

                    if (! $selected->contains((int) $clientId)) {
                        $report->skipped($name, 'non coché');

                        continue;
                    }

                    try {
                        $draftBefore = $service->plan((int) $clientId)['draft'];
                        $invoice = $service->createInvoiceForClient((int) $clientId);
                        $draftBefore ? $report->completed($name, $invoice) : $report->created($name, $invoice);
                    } catch (Throwable $e) {
                        $report->failed($name, $e->getMessage());
                    }
                }

                $report->send();
            });
    }
}
