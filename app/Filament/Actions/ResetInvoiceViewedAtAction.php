<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Actions\Concerns\ResolvesInvoice;

class ResetInvoiceViewedAtAction extends Action
{
    use ResolvesInvoice;

    public static function getDefaultName(): ?string
    {
        return 'resetInvoiceViewedAt';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Réinitialiser « Vu à »')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->visible(fn (?Model $record): bool => $record === null || $this->resolveInvoice($record) !== null)
            ->action(fn (Model $record) => $this->resolveInvoice($record)?->update(['viewed_at' => null]));
    }
}
