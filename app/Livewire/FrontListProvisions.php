<?php

namespace App\Livewire;

use App\Classes\Price;
use Livewire\Component;
use Filament\Tables\Table;
use Livewire\Attributes\Url;
use App\Models\ProvisionElement;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\Alignment;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class FrontListProvisions extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    #[Url]
    public ?string $fields = null;

    /**
     * @var array<string, mixed> | null
     */
    #[Url]
    public ?array $tableFilters = null;

    #[Url]
    public ?string $tableGrouping = null;

    #[Url]
    public ?string $tableGroupingDirection = null;

    /**
     * @var ?string
     */
    #[Url]
    public $tableSearch = '';

    #[Url]
    public ?string $tableSortColumn = null;

    #[Url]
    public ?string $tableSortDirection = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(ProvisionElement::query()->with(['provision', 'recipient']))
            ->heading('Prestations')
            ->description('Toutes les prestations de la Course de Noël')
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25)
            ->extremePaginationLinks()
            ->striped()
            ->columns([
                TextColumn::make('recipient.category.name')
                    ->label('Catégorie')
                    ->html()
                    ->formatStateUsing(fn (Model $record): HtmlString => new HtmlString('<span class="text-white text-xs font-medium me-2 px-2.5 py-0.5 rounded" style="background-color:'.$record->recipient?->category?->color.';">'.$record->recipient?->category?->name.'</span>'))
                    ->sortable()
                    ->verticallyAlignStart()
                    ->toggleable(),
                TextColumn::make('recipient.name')
                    ->label('Client')
                    ->searchable()
                    ->description(fn (Model $record): string => "{$record->long_name}")
                    ->alignment(Alignment::Start)
                    ->alignStart()
                    ->verticallyAlignStart()
                    ->wrapHeader()
                    ->toggleable(),
                TextColumn::make('recipient.address')
                    ->label('Adresse')
                    ->formatStateUsing(fn (Model $record): HtmlString => new HtmlString("{$record->recipient?->address}<br>".($record->recipient?->address_extension ? "{$record->recipient?->address_extension}<br>" : null)."{$record->recipient?->postal_code} {$record->recipient?->locality}"))
                    ->verticallyAlignStart()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('recipient.contacts.name')
                    ->label('Contacts')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->html()
                    ->formatStateUsing(fn (Model $record, string $state): HtmlString => new HtmlString("<a href='mailto:{$record->recipient?->contacts?->where('name', $state)->first()?->email}'>{$state}</a>"))
                    ->verticallyAlignStart()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('provision.name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->toggleable(),
                TextColumn::make('precision')
                    ->label('Précision')
                    ->visible($this->isFieldInUrl('precision')),
                TextColumn::make('numeric_indicator')
                    ->label('Indicateur')
                    ->numeric()
                    ->visible($this->isFieldInUrl('numeric_indicator')),
                TextColumn::make('textual_indicator')
                    ->label('Indicateur')
                    ->visible($this->isFieldInUrl('textual_indicator')),
                TextColumn::make('goods_to_be_delivered')
                    ->label('Marchandise')
                    ->visible($this->isFieldInUrl('goods_to_be_delivered')),
                TextColumn::make('contact.name')
                    ->label('Contact')
                    ->visible($this->isFieldInUrl('contact')),
                TextColumn::make('contact.phone')
                    ->label('Contact - Tél.')
                    ->visible($this->isFieldInUrl('phone')),
                TextColumn::make('contact_text')
                    ->label('Contact')
                    ->visible($this->isFieldInUrl('contact_text')),
                TextColumn::make('contact_location')
                    ->label('Lieu')
                    ->visible($this->isFieldInUrl('contact_location')),
                TextColumn::make('contact_date')
                    ->label('Date')
                    ->date('L', 'fr_CH')
                    ->visible($this->isFieldInUrl('contact_date')),
                TextColumn::make('contact_time')
                    ->label('Heure')
                    ->visible($this->isFieldInUrl('contact_time')),
                TextColumn::make('media_status')
                    ->label('Statut (média)')
                    ->badge()
                    ->visible($this->isFieldInUrl('media_status')),
                TextColumn::make('responsible')
                    ->label('Responsable')
                    ->visible($this->isFieldInUrl('responsible')),
                TextColumn::make('dicastry.name')
                    ->label('Dicastère')
                    ->visible($this->isFieldInUrl('dicastry')),
                TextColumn::make('tracking_status')
                    ->label('Statut (suivi)')
                    ->visible($this->isFieldInUrl('tracking_status')),
                TextColumn::make('accreditation_type')
                    ->label('Type (accréditation)')
                    ->visible($this->isFieldInUrl('accreditation_type')),
                TextColumn::make('vip_category')
                    ->label('Catégorie (VIP)')
                    ->visible($this->isFieldInUrl('vip_category')),
                TextColumn::make('vip_invitation_number')
                    ->label('Nombre d\'invitation')
                    ->visible($this->isFieldInUrl('vip_invitation_number')),
                TextColumn::make('vip_response_status')
                    ->label('Réponse (VIP)')
                    ->visible($this->isFieldInUrl('vip_response_status')),
                TextColumn::make('vip_guests')
                    ->label('Invités')
                    ->visible($this->isFieldInUrl('vip_guests')),
                TextColumn::make('note')
                    ->label('Note')
                    ->verticallyAlignStart()
                    ->visible($this->isFieldInUrl('note')),

                TextColumn::make('cost')
                    ->label('Montant')
                    ->formatStateUsing(fn (float $state) => $state > 0 ? Price::of($state)->amount('c') : null)
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn (float $state) => Price::of($state)->amount('c')))
                    ->visible($this->isFieldInUrl('cost')),
                TextColumn::make('net_cost')
                    ->state(function (Model $record): ?float {
                        return $record->price->net_amount;
                    })
                    ->label('Montant net')
                    ->formatStateUsing(fn (float $state) => $state > 0 ? Price::of($state)->amount('c') : null)
                    ->visible($this->isFieldInUrl('cost')),

                TextColumn::make('recipient.documents.id')
                    ->label('Documents')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->html()
                    ->formatStateUsing(fn (Model $record, string $state): HtmlString => new HtmlString(
                        '<a href="'.$record->documents?->where('id', $state)->first()?->getFirstMediaUrl('*').'">'.
                        '📄 '.
                        $record->documents?->where('id', $state)->first()?->name.' ('.
                        \Carbon\Carbon::parse($record->documents?->where('id', $state)->first()?->date)->locale('fr_CH')->isoFormat('L').')'
                        .'</a>'
                    ))
                    ->verticallyAlignStart()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('recipient.invoices.id')
                    ->label('Factures')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->html()
                    ->formatStateUsing(fn (Model $record, string $state): HtmlString => new HtmlString(
                        '<a href="'.$record->recipient?->invoices?->where('id', $state)->first()?->link.'" target="_blank">'.
                        '🧾 '.
                        $record->invoices?->where('id', $state)->first()?->number.' ('.
                        \Carbon\Carbon::parse($record->recipient?->invoices?->where('id', $state)->first()?->date)->locale('fr_CH')->isoFormat('L').')'
                        .'</a>'.
                        ($record->recipient?->invoices?->where('id', $state)->first()?->status->value == 'payed' ? ' ✓' : null)
                    ))
                    ->verticallyAlignStart()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('provision')
                    ->label('Prestations')
                    ->multiple()
                    ->preload()
                    ->relationship('provision', 'name'),
                SelectFilter::make('edition')
                    ->label('Édition')
                    ->preload()
                    ->relationship('edition', 'year'),
            ])
            ->filtersFormColumns(3)
            ->actions([
                // ...
            ])
            ->bulkActions([
                // ...
            ]);
    }

    public function render()
    {
        return view('livewire.front-list-provisions');
    }

    public function isFieldInUrl($columnName)
    {
        return in_array($columnName, explode(',', $this->fields));
    }
}
