<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ProvisionResource\Pages\ListProvisions;
use App\Filament\Resources\ProvisionResource\Pages\CreateProvision;
use App\Filament\Resources\ProvisionResource\Pages\EditProvision;
use Filament\Forms;
use Filament\Tables;
use App\Models\Provision;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProvisionResource\Pages;

class ProvisionResource extends Resource
{
    protected static ?string $model = Provision::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $pluralModelLabel = 'Prestations';

    protected static ?string $modelLabel = 'Prestation';

    protected static string | \UnitEnum | null $navigationGroup = 'Collections';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Base')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom'),
                        TextInput::make('description')
                            ->label('Description'),
                        /*
                        Forms\Components\TextInput::make('code')
                            ->label('Code'),
                        */
                        Select::make('dicastry_id')
                            ->label('Dicastère')
                            ->relationship('dicastry', 'name'),
                        Select::make('category_id')
                            ->label('Catégorie')
                            ->relationship('category', 'name'),
                        Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                    ]),
                Section::make('Indications')
                    ->columns(2)
                    ->schema([
                        TextInput::make('numeric_indicator')
                            ->label('Indicateur numérique')
                            ->numeric(),
                        TextInput::make('dimensions_indicator')
                            ->label('Dimensions'),
                        TextInput::make('format_indicator')
                            ->label('Format'),
                        TextInput::make('due_date_indicator')
                            ->label('Délai'),
                        TextInput::make('contact_indicator')
                            ->label('Contact'),
                    ]),
                Section::make('Options')
                    ->columns(4)
                    ->schema([
                        /*
                        Forms\Components\Toggle::make('has_content')
                            ->label('Contenu'),
                        */
                        Toggle::make('has_due_date')
                            ->label('Délai'),
                        Toggle::make('has_precision')
                            ->label('Précision')
                            ->default(true),
                        Toggle::make('has_numeric_indicator')
                            ->label('Indicateur numérique'),
                        Toggle::make('has_textual_indicator')
                            ->label('Indicateur textuel'),
                        Toggle::make('has_product')
                            ->label('Produit')
                            ->live(),
                        Toggle::make('has_contact')
                            ->label('Contact')
                            ->hint('Point de contact'),
                        Toggle::make('has_media')
                            ->label('Média'),
                        Toggle::make('has_goods_to_be_delivered')
                            ->label('Marchandise')
                            ->hint('Prévu'),
                        Toggle::make('has_responsible')
                            ->label('Responsable'),
                        Toggle::make('has_tracking')
                            ->label('Suivi')
                            ->hint('Statut et date'),
                        Toggle::make('has_accreditation')
                            ->label('Accréditation'),
                        Toggle::make('has_vip')
                            ->label('VIP'),
                        Toggle::make('has_placeholder')
                            ->label('Placeholder'),
                        Toggle::make('has_subprovision')
                            ->label('Sous-provisions')
                            ->live(),
                    ]),
                Select::make('product_id')
                    ->label('Produit')
                    ->relationship(
                        name: 'product',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->active(),
                    )
                    ->searchable()
                    ->preload()
                    ->createOptionForm(fn (Schema $schema): Schema => ProductResource::form($schema))
                    ->visible(fn (Get $get) => $get('has_product')),
                Select::make('subProvisions')
                    ->label('Sous-provisions')
                    ->relationship(
                        name: 'subProvisions',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->active(),
                    )
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->createOptionForm(fn (Schema $schema): Schema => ProductResource::form($schema))
                    ->visible(fn (Get $get) => $get('has_subprovision')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('order_column')
            ->defaultSort('order_column')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('dicastry.name')
                    ->label('Dicastère'),
                TextColumn::make('category.name')
                    ->label('Catégorie'),
                TextColumn::make('product.name')
                    ->label('Produit')
                    ->limit(40),

                TextColumn::make('numeric_indicator')
                    ->label('Ind. numérique'),
                TextColumn::make('dimensions_indicator')
                    ->label('Dimensions'),
                TextColumn::make('format_indicator')
                    ->label('Format'),
                TextColumn::make('due_date_indicator')
                    ->label('Délai'),
                TextColumn::make('contact_indicator')
                    ->label('Contact'),
                ToggleColumn::make('is_active')
                    ->label('Actif')
                    ->extraAttributes(['style' => 'padding-top: 0;padding-bottom: 0;']),

                /*
                Tables\Columns\TextInputColumn::make('numeric_indicator')
                    ->label('Ind. numérique'),
                Tables\Columns\TextInputColumn::make('dimensions_indicator')
                    ->label('Dimensions'),
                Tables\Columns\TextInputColumn::make('format_indicator')
                    ->label('Format'),
                Tables\Columns\TextInputColumn::make('due_date_indicator')
                    ->label('Délai'),
                Tables\Columns\TextInputColumn::make('contact_indicator')
                    ->label('Contact'),
                */
            ])
            ->filters([
                Filter::make('is_active')
                    ->label('Uniquement actifs')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->default(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ReplicateAction::make()->successRedirectUrl(fn (Model $replica): string => route('filament.admin.resources.provisions.edit', [
                        'record' => $replica,
                    ])),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListProvisions::route('/'),
            'create' => CreateProvision::route('/create'),
            'edit'   => EditProvision::route('/{record}/edit'),
        ];
    }
}
