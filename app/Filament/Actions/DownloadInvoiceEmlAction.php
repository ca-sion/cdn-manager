<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Actions\Concerns\ResolvesInvoice;

class DownloadInvoiceEmlAction extends Action
{
    use ResolvesInvoice;

    protected bool $relaunch = false;

    public static function getDefaultName(): ?string
    {
        return 'downloadInvoiceEml';
    }

    public function relaunch(bool $relaunch = true): static
    {
        $this->relaunch = $relaunch;
        $this->label($relaunch ? 'Télécharger la relance (.eml)' : 'Télécharger l\'email (.eml)');

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Télécharger l\'email (.eml)')
            ->icon('heroicon-o-document-arrow-down')
            ->openUrlInNewTab()
            ->visible(fn (?Model $record): bool => $record === null || $this->resolveInvoice($record) !== null)
            ->url(function (?Model $record): ?string {
                $invoice = $record ? $this->resolveInvoice($record) : null;

                return $invoice
                    ? URL::signedRoute($this->relaunch ? 'invoices.emlRelaunch' : 'invoices.eml', ['invoice' => $invoice])
                    : null;
            });
    }
}
