<?php

namespace App\Filament\Actions;

use Exception;
use Filament\Actions\Action;
use App\Services\InvoiceService;
use Filament\Schemas\Components\Text;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Forms\Components\CheckboxList;
use App\Filament\Actions\Concerns\ResolvesClientId;

/**
 * Génère une facture à partir des prestations de l'édition courante du client qui ne sont
 * pas encore facturées. Le choix des prestations à inclure est proposé à la génération.
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Générer la facture')
            ->icon('heroicon-o-document-currency-dollar')
            ->modalHeading('Générer une facture de prestations')
            ->modalSubmitActionLabel('Générer')
            ->visible(fn (?Model $record): bool => $this->resolveClientId($record) !== null)
            ->schema(function (?Model $record): array {
                $clientId = $this->resolveClientId($record);

                if (! $clientId) {
                    return [];
                }

                $elements = InvoiceService::uninvoicedProvisionElements($clientId);
                $legacy = InvoiceService::legacyProvisionInvoices($clientId);
                $draft = InvoiceService::openDraftInvoice($clientId);

                $components = [];

                if ($legacy->isNotEmpty()) {
                    $components[] = Text::make('Anciennes factures reconnues par nom de prestation : '.$legacy->pluck('number')->implode(', ').'. Les prestations qu\'elles contiennent ne sont pas proposées ; une facture saisie à la main avec d\'autres libellés ne peut pas être reconnue, vérifiez la liste.')
                        ->color('warning');
                }

                $components[] = Text::make($draft
                    ? 'Les prestations cochées seront ajoutées à la facture brouillon '.$draft->number.'.'
                    : 'Une nouvelle facture sera créée. Les factures prêtes, envoyées ou payées ne sont jamais modifiées.');

                $components[] = CheckboxList::make('element_ids')
                    ->label('Prestations à facturer')
                    ->options($elements->mapWithKeys(fn ($element) => [
                        $element->id => trim(($element->provision?->category?->name ? $element->provision->category->name.' · ' : '')
                            .$element->name
                            .($element->precision ? ' ('.$element->precision.')' : '')
                            .' — '.$element->price->amount('c')),
                    ])->all())
                    ->default($elements->pluck('id')->all())
                    ->bulkToggleable()
                    ->searchable()
                    ->required()
                    ->noOptionsMessage('Tout est déjà facturé.');

                return $components;
            })
            ->action(function (?Model $record, array $data) {
                try {
                    $invoice = InvoiceService::generateInvoiceByClient($this->resolveClientId($record), $data['element_ids'] ?? []);
                    Notification::make()->title('Facture '.$invoice->number.($invoice->wasRecentlyCreated ? ' générée' : ' complétée'))->success()->send();
                } catch (Exception $e) {
                    Notification::make()->title('Aucune facture générée')->body($e->getMessage())->warning()->send();
                }
            });
    }
}
