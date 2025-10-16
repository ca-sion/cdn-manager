<?php

namespace App\Livewire;

use App\Models\Client;
use Livewire\Component;
use Filament\Tables\Table;
use Livewire\Attributes\Url;
use App\Enums\EngagementStageEnum;
use Illuminate\Support\HtmlString;
use App\Enums\EngagementStatusEnum;
use Filament\Support\Enums\Alignment;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class FrontListClients extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

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
            ->query(Client::query()->with(['provisionElements']))
            ->heading('Clients')
            ->description('Tous les clients de la Course de Noël')
            ->defaultSort('name', 'asc')
            ->persistSortInSession()
            ->paginated([10, 25, 50, 100, 'all'])
            ->defaultPaginationPageOption(10)
            ->extremePaginationLinks()
            ->striped()
            ->columns([
                IconColumn::make('pdfLink')
                    ->label('PDF')
                    ->icon('heroicon-o-document')
                    ->url(fn (Model $record): string => $record->pdfLink)
                    ->openUrlInNewTab()
                    ->size(IconColumn\IconColumnSize::Small)
                    ->toggleable(),
                TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->html()
                    ->formatStateUsing(fn (Model $record): HtmlString => new HtmlString('<span class="text-white text-xs font-medium me-2 px-2.5 py-0.5 rounded" style="background-color:'.$record->category?->color.';">'.$record->category?->name.'</span>'))
                    ->sortable()
                    ->verticallyAlignStart()
                    ->toggleable(),
                // TextColumn::make('name')
                //     ->label('Nom')
                //     ->searchable()
                //     ->sortable()
                //     ->description(fn (Model $record): string => "{$record->long_name}")
                //     ->alignment(Alignment::Start)
                //     ->alignStart()
                //     ->verticallyAlignStart()
                //     ->wrapHeader()
                //     ->weight(FontWeight::Bold)
                //     ->toggleable(),
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('long_name')
                    ->label('Nom long')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('currentEngagement.stage')
                    ->label('Progression')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('currentEngagement.status')
                    ->label('Statut')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('address')
                    ->label('Adresse')
                    ->formatStateUsing(fn (Model $record): HtmlString => new HtmlString("{$record->address}<br>".($record->address_extension ? "{$record->address_extension}<br>" : null)."{$record->postal_code} {$record->locality}"))
                    ->verticallyAlignStart()
                    ->toggleable(),
                TextColumn::make('contacts.name')
                    ->label('Contacts')
                    ->searchable()
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->html()
                    ->formatStateUsing(fn (Model $record, string $state): HtmlString => new HtmlString("<a href='mailto:{$record->contacts?->where('name', $state)->first()?->email}'>{$state}</a>"))
                    ->verticallyAlignStart()
                    ->toggleable(),
                ViewColumn::make('provisionElements.provision.name')->view('tables.columns.provision-elements-infolist')
                    ->label('Prestations')
                    ->searchable()
                    ->verticallyAlignStart()
                    ->toggleable(),
                TextColumn::make('documents.id')
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
                    ->toggleable(),
                TextColumn::make('currentInvoices.id')
                    ->label('Factures')
                    ->listWithLineBreaks()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->html()
                    ->formatStateUsing(fn (Model $record, string $state): HtmlString => new HtmlString(
                        '<a href="'.$record->invoices?->where('id', $state)->first()?->link.'" target="_blank">'.
                        '🧾 '.
                        $record->invoices?->where('id', $state)->first()?->number.' ('.
                        \Carbon\Carbon::parse($record->invoices?->where('id', $state)->first()?->date)->locale('fr_CH')->isoFormat('L').')'
                        .'</a>'.
                        ($record->invoices?->where('id', $state)->first()?->status->value == 'paid' ? ' ✓' : null)
                    ))
                    ->verticallyAlignStart()
                    ->toggleable(),
                TextColumn::make('note')
                    ->label('Note')
                    ->verticallyAlignStart(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->multiple()
                    ->preload()
                    ->relationship('category', 'name'),
                SelectFilter::make('provision')
                    ->label('Prestations')
                    ->multiple()
                    ->preload()
                    ->relationship('provisionElements.provision', 'name'),
                SelectFilter::make('edition')
                    ->label('Édition')
                    ->preload()
                    ->relationship('provisionElements.edition', 'year'),

                SelectFilter::make('stage')
                    ->label('Progression')
                    ->options(EngagementStageEnum::class)
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('currentEngagement', function (Builder $query) use ($data) {
                            $query->where('stage', $data['value']);
                        });
                    }),

                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(EngagementStatusEnum::class)
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('currentEngagement', function (Builder $query) use ($data) {
                            $query->where('status', $data['value']);
                        });
                    }),
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
        return view('livewire.front-list-clients');
    }
}
