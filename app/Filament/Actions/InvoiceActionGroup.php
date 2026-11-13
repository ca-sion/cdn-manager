<?php

namespace App\Filament\Actions;

use Closure;
use Filament\Actions\ActionGroup;

/**
 * Menu « Facture » réutilisable depuis n'importe quelle table liée à un client ou une facture.
 *
 * @param  Closure  $invoice  fn (Model $record): ?Invoice
 * @param  Closure|null  $clientId  fn (?Model $record): ?int — active les actions de génération
 */
class InvoiceActionGroup
{
    public static function make(Closure $invoice, ?Closure $clientId = null, bool $withRegistrations = false): ActionGroup
    {
        $actions = [];

        if ($clientId) {
            $actions[] = GenerateClientInvoiceAction::make()->clientIdUsing($clientId);
            if ($withRegistrations) {
                $actions[] = GenerateRegistrationInvoiceAction::make()->clientIdUsing($clientId);
            }
        }

        array_push(
            $actions,
            InvoicePdfAction::make()->invoiceUsing($invoice),
            SendInvoiceAction::make()->invoiceUsing($invoice),
            RelaunchInvoiceAction::make()->invoiceUsing($invoice),
            DownloadInvoiceEmlAction::make()->invoiceUsing($invoice),
            DownloadInvoiceEmlAction::make('downloadInvoiceEmlRelaunch')->relaunch()->invoiceUsing($invoice),
        );

        return ActionGroup::make($actions)
            ->label('Facture')
            ->icon('heroicon-o-document-currency-dollar');
    }
}
