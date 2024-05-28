<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use App\Models\Provision;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\ProductResource;
use Filament\Tables\Actions\ReplicateAction;
use App\Filament\Resources\ProvisionResource\Pages;

class ProvisionResource extends Resource
{
    protected static ?string $model = Provision::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $pluralModelLabel = 'Prestations';

    protected static ?string $modelLabel = 'Prestation';

    protected static ?string $navigationGroup = 'Collections';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Base')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom'),
                        Forms\Components\TextInput::make('description')
                            ->label('Description'),
                        /*
                        Forms\Components\TextInput::make('code')
                            ->label('Code'),
                        */
                        Forms\Components\Select::make('dicastry_id')
                            ->label('Dicastère')
                            ->relationship('dicastry', 'name'),
                        Forms\Components\Select::make('category_id')
                            ->label('Catégorie')
                            ->relationship('category', 'name'),
                    ]),
                Section::make('Indications')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('numeric_indicator')
                            ->label('Indicateur numérique')
                            ->numeric(),
                        Forms\Components\TextInput::make('dimensions_indicator')
                            ->label('Dimensions'),
                        Forms\Components\TextInput::make('format_indicator')
                            ->label('Format'),
                        Forms\Components\TextInput::make('due_date_indicator')
                            ->label('Délai'),
                        Forms\Components\TextInput::make('contact_indicator')
                            ->label('Contact'),
                    ]),
                Section::make('Options')
                    ->columns(4)
                    ->schema([
                        /*
                        Forms\Components\Toggle::make('has_content')
                            ->label('Contenu'),
                        Forms\Components\Toggle::make('has_due_date')
                            ->label('Délai'),
                        */
                        Forms\Components\Toggle::make('has_precision')
                            ->label('Précision')
                            ->default(true),
                        Forms\Components\Toggle::make('has_numeric_indicator')
                            ->label('Indicateur numérique'),
                        Forms\Components\Toggle::make('has_textual_indicator')
                            ->label('Indicateur textuel'),
                        Forms\Components\Toggle::make('has_product')
                            ->label('Produit')
                            ->live(),
                        Forms\Components\Toggle::make('has_contact')
                            ->label('Contact')
                            ->hint('Point de contact'),
                        Forms\Components\Toggle::make('has_media')
                            ->label('Média'),
                        Forms\Components\Toggle::make('has_goods_to_be_delivered')
                            ->label('Marchandise')
                            ->hint('Prévu'),
                        Forms\Components\Toggle::make('has_responsible')
                            ->label('Responsable'),
                        Forms\Components\Toggle::make('has_tracking')
                            ->label('Suivi')
                            ->hint('Statut et date'),
                        Forms\Components\Toggle::make('has_accreditation')
                            ->label('Accréditation'),
                        Forms\Components\Toggle::make('has_vip')
                            ->label('VIP'),
                        Forms\Components\Toggle::make('has_placeholder')
                            ->label('Placeholder'),
                    ]),
                Forms\Components\Select::make('product_id')
                    ->label('Produit')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm(fn (Form $form): Form => ProductResource::form($form))
                    ->visible(fn (Get $get) => $get('has_product')),
                /*
                    Forms\Components\TextInput::make('type')
                    ->label('Type'),
                */
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dicastry.name'),
                // Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('product.name'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                ReplicateAction::make()->successRedirectUrl(fn (Model $replica): string => route('filament.admin.resources.provisions.edit', [
                    'record' => $replica,
                ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index'  => Pages\ListProvisions::route('/'),
            'create' => Pages\CreateProvision::route('/create'),
            'edit'   => Pages\EditProvision::route('/{record}/edit'),
        ];
    }
}
