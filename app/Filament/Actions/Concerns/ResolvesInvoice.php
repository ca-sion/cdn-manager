<?php

namespace App\Filament\Actions\Concerns;

use Closure;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;

/**
 * Permet à une action de facture d'être utilisée depuis n'importe quelle table :
 * par défaut l'enregistrement est la facture, sinon on fournit un résolveur
 * (ex. `fn (RunRegistration $r) => $r->invoice`).
 */
trait ResolvesInvoice
{
    protected ?Closure $invoiceResolver = null;

    public function invoiceUsing(?Closure $callback): static
    {
        $this->invoiceResolver = $callback;

        return $this;
    }

    protected function resolveInvoice(Model $record): ?Invoice
    {
        $invoice = $this->invoiceResolver ? ($this->invoiceResolver)($record) : $record;

        return $invoice instanceof Invoice ? $invoice : null;
    }
}
