<?php

namespace App\Filament\Actions\Concerns;

use Closure;
use App\Models\Client;
use Illuminate\Database\Eloquent\Model;

/**
 * Permet à une action de s'appliquer à n'importe quel enregistrement lié à un client.
 * Par défaut : si l'enregistrement est un Client, son id ; sinon `client_id`.
 */
trait ResolvesClientId
{
    protected ?Closure $clientIdResolver = null;

    public function clientIdUsing(?Closure $callback): static
    {
        $this->clientIdResolver = $callback;

        return $this;
    }

    protected function resolveClientId(?Model $record): ?int
    {
        if ($this->clientIdResolver) {
            return ($this->clientIdResolver)($record);
        }

        if (! $record) {
            return null;
        }

        return $record instanceof Client ? $record->id : ($record->client_id ?? null);
    }
}
