<?php

namespace App\Filament\Actions;

use Closure;
use App\Models\Client;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Actions\Support\VoucherMailing;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Envoi groupé des vouchers avec aperçu des destinataires, vérification des quotas et rapport détaillé.
 *
 * - Sur ClientResource : les enregistrements sont directement des Client.
 * - Sur VoucherResource : utiliser ->clientUsing(fn ($record) => $record->client).
 */
class SendVouchersBulkAction extends BulkAction
{
    protected ?Closure $clientResolver = null;

    public static function getDefaultName(): ?string
    {
        return 'sendVouchers';
    }

    public function clientUsing(?Closure $callback): static
    {
        $this->clientResolver = $callback;

        return $this;
    }

    /**
     * Clients distincts de la sélection, triés par nom.
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

        return Client::with(['currentProvisionElements'])
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    protected function unassignedCount(Collection $records): int
    {
        return $records
            ->filter(fn ($record) => ! (($this->clientResolver ? ($this->clientResolver)($record) : $record) instanceof Client))
            ->count();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Envoyer les vouchers')
            ->icon('heroicon-o-paper-airplane')
            ->color('primary')
            ->requiresConfirmation(false)
            ->modalHeading('Envoyer les vouchers par email')
            ->modalDescription('Vérifiez la liste : seuls les clients cochés recevront leurs codes par email. Rien n\'est envoyé avant de valider.')
            ->modalSubmitActionLabel('Envoyer aux clients cochés')
            ->deselectRecordsAfterCompletion()
            ->schema(fn (Collection $records): array => VoucherMailing::previewSchema(
                $this->clients($records),
                $this->unassignedCount($records),
            ))
            ->action(fn (Collection $records, array $data) => VoucherMailing::sendBulk(
                $this->clients($records),
                $data,
            ));
    }
}
