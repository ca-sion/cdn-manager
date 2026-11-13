<?php

namespace App\Filament\Actions;

use Exception;
use Filament\Actions\Action;
use App\Services\InvoiceService;
use Illuminate\Support\HtmlString;
use Filament\Schemas\Components\Text;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Forms\Components\CheckboxList;
use App\Filament\Actions\Concerns\ResolvesClientId;

/**
 * Génère une facture à partir des prestations de l'édition courante du client qui ne sont
 * pas encore facturées. La fenêtre explique d'abord la situation (à facturer / déjà facturé),
 * puis propose de cocher les prestations à inclure.
 * - une facture « Brouillon » existante est complétée
 * - une facture Prête / Envoyée / Payée n'est jamais modifiée (une nouvelle facture est créée)
 */
class GenerateClientInvoiceAction extends Action
{
    use ResolvesClientId;

    public static function getDefaultName(): ?string
    {
        return 'generateInvoice';
    }

    protected function overview(?Model $record): ?array
    {
        $clientId = $this->resolveClientId($record);

        return $clientId ? InvoiceService::billingOverview($clientId) : null;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Générer la facture')
            ->icon('heroicon-o-document-currency-dollar')
            ->modalHeading('Facturer les prestations')
            ->modalSubmitActionLabel('Créer la facture')
            ->visible(fn (?Model $record): bool => $this->resolveClientId($record) !== null)
            ->modalSubmitAction(fn (?Model $record) => ($this->overview($record)['to_invoice'] ?? collect())->isEmpty() ? false : null)
            ->modalCancelActionLabel('Fermer')
            ->schema(function (?Model $record): array {
                $overview = $this->overview($record);

                if (! $overview) {
                    return [];
                }

                $toInvoice = $overview['to_invoice'];
                $invoiced = $overview['invoiced'];
                $draft = InvoiceService::openDraftInvoice($this->resolveClientId($record));

                $label = fn ($element) => ($element->provision?->category?->name ? $element->provision->category->name.' · ' : '')
                    .$element->name
                    .($element->precision ? ' ('.$element->precision.')' : '')
                    .' — '.$element->price->amount('c');

                $components = [];

                // 1. Résumé
                $summary = $toInvoice->count().' à facturer · '.$invoiced->count().' déjà facturée(s)';
                if ($overview['without_product']) {
                    $summary .= ' · '.$overview['without_product'].' sans prix (jamais facturées)';
                }
                $components[] = Text::make($summary)->weight('bold');

                // 2. Cas « rien à facturer »
                if ($toInvoice->isEmpty()) {
                    $components[] = Text::make($invoiced->isEmpty()
                        ? 'Ce client n\'a aucune prestation avec un prix pour cette édition : il n\'y a rien à facturer.'
                        : 'Rien à facturer : toutes les prestations de ce client sont déjà sur une facture (voir la liste ci-dessous). Pour facturer autre chose, créez une facture manuelle ou une proforma.')
                        ->color('success');
                } else {
                    // 3. Choix des prestations
                    $components[] = CheckboxList::make('element_ids')
                        ->label('Prestations à mettre sur la facture (décochez celles à garder pour plus tard)')
                        ->options($toInvoice->mapWithKeys(fn ($element) => [$element->id => $label($element)])->all())
                        ->default($toInvoice->pluck('id')->all())
                        ->bulkToggleable()
                        ->required();

                    // 4. Destination
                    $components[] = Text::make($draft
                        ? '→ Elles seront ajoutées à la facture brouillon n° '.$draft->number.'.'
                        : '→ Une nouvelle facture (statut « Prêt ») sera créée. Les factures déjà prêtes, envoyées ou payées ne sont jamais modifiées.');
                }

                // 5. Ce qui est déjà facturé (information)
                if ($invoiced->isNotEmpty()) {
                    $lines = $invoiced
                        ->groupBy(fn ($row) => $row['invoice']->id)
                        ->map(function ($rows) use ($label) {
                            $invoice = $rows->first()['invoice'];
                            $items = $rows->map(fn ($row) => '<li>'.e($label($row['element'])).'</li>')->implode('');

                            return '<p><strong>Facture '.e($invoice->number).'</strong> ('.e($invoice->status?->getLabel()).')</p><ul style="list-style:disc;margin-left:1.25rem">'.$items.'</ul>';
                        })
                        ->implode('');

                    $components[] = Text::make('Déjà facturées :')->weight('bold');
                    $components[] = Text::make(new HtmlString($lines));

                    if ($overview['legacy_invoices']->isNotEmpty()) {
                        $components[] = Text::make('Pour les factures '.$overview['legacy_invoices']->pluck('number')->implode(', ').', les prestations ont été retrouvées par leur nom (facture créée avant le suivi automatique). Si une facture contient des lignes avec d\'autres libellés, elles n\'ont pas pu être reconnues : vérifiez qu\'aucune prestation de la liste ci-dessus ne figure déjà dedans.')
                            ->color('warning');
                    }
                }

                return $components;
            })
            ->action(function (?Model $record, array $data) {
                try {
                    $invoice = InvoiceService::generateInvoiceByClient($this->resolveClientId($record), $data['element_ids'] ?? []);
                    Notification::make()->title('Facture '.$invoice->number.($invoice->wasRecentlyCreated ? ' créée' : ' complétée'))->success()->send();
                } catch (Exception $e) {
                    Notification::make()->title('Aucune facture créée')->body($e->getMessage())->warning()->send();
                }
            });
    }
}
