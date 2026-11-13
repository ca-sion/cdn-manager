<?php

namespace App\Filament\Actions;

use Closure;
use Throwable;
use App\Models\Client;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Text;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Actions\Support\ClientMailing;
use App\Filament\Actions\Support\BulkMailReport;
use App\Filament\Actions\Support\BulkInvoicePreview;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Envoi / relance en masse d'un des 4 emails clients (voir ClientMailing), avec aperçu et rapport :
 * chaque client est listé avec son destinataire, la date du dernier envoi et les avertissements ;
 * les clients sans e-mail ou non relançables sont désactivés ; un client déjà contacté est décoché par défaut.
 *
 * Usage : ClientMailingBulkAction::for('send_advertiser_form')
 * Depuis une autre table : ->clientUsing(fn ($record) => $record->recipient)
 */
class ClientMailingBulkAction extends BulkAction
{
    protected string $mailing = 'send_advertiser_form';

    protected ?Closure $clientResolver = null;

    public static function for(string $mailing): static
    {
        return static::make($mailing)->mailing($mailing);
    }

    public function mailing(string $mailing): static
    {
        $definition = ClientMailing::definition($mailing);
        $this->mailing = $mailing;

        $this->label($definition['label'])
            ->icon($definition['icon'])
            ->color('warning')
            ->modalHeading($definition['label'])
            ->modalDescription('Vérifiez la liste : seuls les clients cochés recevront l\'email. Rien n\'est envoyé avant de valider, et un email envoyé ne peut pas être annulé.')
            ->modalSubmitActionLabel('Envoyer aux clients cochés');

        return $this;
    }

    public function clientUsing(?Closure $callback): static
    {
        $this->clientResolver = $callback;

        return $this;
    }

    /**
     * Clients distincts de la sélection, avec leurs relations chargées.
     *
     * @return SupportCollection<int, Client>
     */
    protected function clients(Collection $records): SupportCollection
    {
        $ids = $records
            ->map(fn ($record) => $this->clientResolver ? ($this->clientResolver)($record) : $record)
            ->filter(fn ($client) => $client instanceof Client)
            ->pluck('id')
            ->unique()
            ->values();

        return Client::with(['currentEngagement', 'previousEditionProvisionElements'])
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->deselectRecordsAfterCompletion()
            ->schema(function (Collection $records): array {
                $clients = $this->clients($records);
                $withoutClient = $records->count() - $records
                    ->filter(fn ($record) => ($this->clientResolver ? ($this->clientResolver)($record) : $record) instanceof Client)->count();

                $preview = ['options' => [], 'descriptions' => [], 'default' => [], 'disabled' => [], 'counts' => []];
                $counts = ['send' => 0, 'warning' => 0, 'ignored' => 0];

                foreach ($clients as $id => $client) {
                    $assessment = ClientMailing::assess($this->mailing, $client);
                    $preview['options'][$id] = $client->name;
                    $preview['descriptions'][$id] = $assessment['description'];

                    if ($assessment['disabled']) {
                        $preview['disabled'][] = $id;
                        $counts['ignored']++;
                    } elseif ($assessment['warning']) {
                        $counts['warning']++;
                    } else {
                        $counts['send']++;
                    }

                    if ($assessment['checked']) {
                        $preview['default'][] = $id;
                    }
                }

                $view = BulkInvoicePreview::compact($preview, 15, 'ignorés');
                $list = $view['preview'];

                $components = [
                    Text::make($clients->count().' client(s) · '.$counts['send'].' à contacter · '.$counts['warning'].' déjà contacté(s) (décoché(s)) · '.$counts['ignored'].' ignoré(s)'.($withoutClient ? ' · '.$withoutClient.' ligne(s) sans client' : ''))->weight('bold'),
                ];

                if ($list['options'] !== []) {
                    $components[] = CheckboxList::make('client_ids')
                        ->label('Clients à contacter')
                        ->options($list['options'])
                        ->descriptions($list['descriptions'])
                        ->default($list['default'])
                        ->disableOptionWhen(fn (string $value) => in_array((int) $value, $list['disabled'], true))
                        ->bulkToggleable()
                        ->searchable();
                }

                if ($view['summary']) {
                    $components[] = Text::make($view['summary'])->color('gray');
                }

                return $components;
            })
            ->action(function (Collection $records, array $data) {
                $definition = ClientMailing::definition($this->mailing);
                $report = new BulkMailReport($definition['label']);
                $selected = collect($data['client_ids'] ?? [])->map(fn ($id) => (int) $id);

                foreach ($this->clients($records) as $id => $client) {
                    if (! $selected->contains($id)) {
                        $report->skipped($client->name, 'non coché');

                        continue;
                    }

                    // Revalide au moment de l'envoi : l'état du client a pu changer pendant que la fenêtre était ouverte
                    $assessment = ClientMailing::assess($this->mailing, $client);
                    if ($assessment['disabled']) {
                        $report->skipped($client->name, $assessment['disabled']);

                        continue;
                    }

                    try {
                        ClientMailing::send($this->mailing, $client);
                        $report->sent($client->name, $assessment['email']);
                    } catch (Throwable $e) {
                        report($e);
                        $report->failed($client->name, $e->getMessage());
                    }
                }

                $report->send();
            });
    }
}
