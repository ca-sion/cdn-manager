<?php

namespace App\Filament\Actions;

use Closure;
use App\Models\Client;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;

/**
 * Ouvre la fiche client (PDF). Par défaut l'enregistrement est le client ;
 * ->clientUsing(fn ($record) => $record->client) permet de l'utiliser depuis une autre table.
 */
class ClientPdfAction extends Action
{
    protected ?Closure $clientResolver = null;

    public static function getDefaultName(): ?string
    {
        return 'clientPdf';
    }

    public function clientUsing(?Closure $callback): static
    {
        $this->clientResolver = $callback;

        return $this;
    }

    protected function resolveClient(?Model $record): ?Client
    {
        $client = ($record && $this->clientResolver) ? ($this->clientResolver)($record) : $record;

        return $client instanceof Client ? $client : null;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Fiche client')
            ->icon('heroicon-o-document')
            ->openUrlInNewTab()
            ->visible(fn (?Model $record): bool => $this->resolveClient($record) !== null)
            ->url(fn (?Model $record): ?string => $this->resolveClient($record)?->pdfLink);
    }
}
