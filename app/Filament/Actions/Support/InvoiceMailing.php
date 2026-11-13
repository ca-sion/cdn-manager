<?php

namespace App\Filament\Actions\Support;

use App\Models\Invoice;
use Illuminate\Support\Number;

/**
 * Règles d'éligibilité pour envoyer / relancer une facture.
 * Retourne la raison du refus, ou null si la facture peut être envoyée.
 */
class InvoiceMailing
{
    public static function sendReason(?Invoice $invoice): ?string
    {
        return self::commonReason($invoice) ?? (! $invoice->status?->canBeSent()
            ? 'statut « '.$invoice->status?->getLabel().' » : seules les factures Brouillon / Prêt / À modifier sont envoyées'
            : null);
    }

    public static function relaunchReason(?Invoice $invoice): ?string
    {
        return self::commonReason($invoice) ?? (! $invoice->status?->canBeRelaunched()
            ? 'statut « '.$invoice->status?->getLabel().' » : seules les factures déjà envoyées et impayées sont relancées'
            : null);
    }

    protected static function commonReason(?Invoice $invoice): ?string
    {
        return match (true) {
            $invoice === null                                 => 'aucune facture',
            $invoice->type() === 'proforma'                   => 'facture proforma',
            blank($invoice->client?->invoicingContactEmail)   => 'pas d\'e-mail de facturation pour le client',
            default                                           => null,
        };
    }

    /**
     * « 2025012 · Courses · Prêt · 1 250.00 CHF → client@exemple.ch »
     */
    public static function describe(Invoice $invoice): string
    {
        return $invoice->number.' · '.Invoice::typeLabels()[$invoice->type()].' · '.$invoice->status?->getLabel()
            .' · '.Number::currency((float) $invoice->total, 'CHF', 'fr_CH')
            .' → '.($invoice->client?->invoicingContactEmail ?: 'pas d\'e-mail');
    }
}
