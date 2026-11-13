<?php

namespace App\Filament\Actions;

use Closure;
use Livewire\Component;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

/**
 * Copie dans le presse-papier les emails des enregistrements sélectionnés.
 * Par défaut la propriété `email` est lue ; utiliser ->emailUsing() pour une autre source.
 */
class CopyEmailBulkAction extends BulkAction
{
    protected ?Closure $emailResolver = null;

    public static function getDefaultName(): ?string
    {
        return 'copyEmail';
    }

    public function emailUsing(?Closure $callback): static
    {
        $this->emailResolver = $callback;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Copier emails')
            ->icon('heroicon-m-clipboard-document-list')
            ->action(function (Component $livewire, Collection $records) {
                $clipboard = $records
                    ->map(fn ($record) => $this->emailResolver ? ($this->emailResolver)($record) : $record->email)
                    ->map(fn ($email) => $email."\n")
                    ->implode('');

                $livewire->dispatch('copy-to-clipboard', $clipboard);
            })
            ->extraAttributes([
                'x-on:copy-to-clipboard.window' => 'navigator.clipboard.writeText($event.detail)',
            ]);
    }
}
