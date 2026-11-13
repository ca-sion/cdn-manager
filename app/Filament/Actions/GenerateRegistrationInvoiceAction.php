<?php

namespace App\Filament\Actions;

use Exception;
use Filament\Actions\Action;
use Filament\Schemas\Components\Text;
use Illuminate\Database\Eloquent\Model;
use App\Services\RunRegistrationService;
use Filament\Notifications\Notification;
use App\Filament\Actions\Concerns\ResolvesClientId;

/**
 * Facture les inscriptions de courses d'un client qui ne sont pas encore facturées.
 * La fenêtre explique la situation avant de créer quoi que ce soit.
 * - une facture « Brouillon » existante est recalculée
 * - une facture Prête / Envoyée / Payée n'est jamais modifiée (une nouvelle facture est créée pour les nouvelles inscriptions)
 */
class GenerateRegistrationInvoiceAction extends Action
{
    use ResolvesClientId;

    public static function getDefaultName(): ?string
    {
        return 'generateRegistrationInvoice';
    }

    protected function overview(?Model $record): ?array
    {
        $clientId = $this->resolveClientId($record);

        return $clientId ? app(RunRegistrationService::class)->billingOverview($clientId) : null;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Facture des inscriptions')
            ->icon('heroicon-o-document-currency-dollar')
            ->color('success')
            ->modalHeading('Facturer les inscriptions de courses')
            ->modalSubmitActionLabel('Créer la facture')
            ->modalCancelActionLabel('Fermer')
            ->visible(fn (?Model $record): bool => $this->resolveClientId($record) !== null)
            ->modalSubmitAction(function (?Model $record) {
                $overview = $this->overview($record);

                return ($overview['to_invoice'] ?? 0) === 0 && ! ($overview['draft'] ?? null) ? false : null;
            })
            ->schema(function (?Model $record): array {
                $overview = $this->overview($record);

                if (! $overview) {
                    return [];
                }

                $components = [
                    Text::make($overview['total'].' inscription(s) · '.$overview['invoiced'].' déjà facturée(s) · '.$overview['to_invoice'].' à facturer')->weight('bold'),
                ];

                if ($overview['total'] === 0) {
                    $components[] = Text::make('Ce client n\'a aucune inscription de course : il n\'y a rien à facturer.')->color('success');
                } elseif ($overview['to_invoice'] === 0 && ! $overview['draft']) {
                    $components[] = Text::make('Rien à facturer : toutes les inscriptions sont déjà sur une facture (voir ci-dessous).')->color('success');
                } elseif ($overview['draft']) {
                    $components[] = Text::make('→ La facture brouillon n° '.$overview['draft']->number.' sera recalculée avec toutes les inscriptions non facturées ailleurs.');
                } else {
                    $components[] = Text::make('→ Une nouvelle facture (statut « Prêt ») sera créée pour les inscriptions à facturer. Les factures déjà prêtes, envoyées ou payées ne sont jamais modifiées.');
                }

                if ($overview['invoices']->isNotEmpty()) {
                    $components[] = Text::make('Factures de courses de ce client :')->weight('bold');
                    foreach ($overview['invoices'] as $invoice) {
                        $components[] = Text::make('Facture '.$invoice->number.' — '.$invoice->status?->getLabel().' — '.number_format($invoice->total, 2, '.', ' ').' CHF');
                    }
                }

                return $components;
            })
            ->action(function (?Model $record) {
                try {
                    $invoice = app(RunRegistrationService::class)->createInvoiceForClient($this->resolveClientId($record));
                    Notification::make()->title('Facture '.$invoice->number.($invoice->wasRecentlyCreated ? ' créée' : ' mise à jour'))->success()->send();
                } catch (Exception $e) {
                    Notification::make()->title('Aucune facture créée')->body($e->getMessage())->warning()->send();
                }
            });
    }
}
