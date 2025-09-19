<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Client;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use Illuminate\Contracts\View\View;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Exports\ClientExporter;
use Filament\Tables\Actions\ExportAction;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\Support\MediaStream;
use App\Notifications\ClientAdvertiserFormLink;
use App\Filament\Resources\ClientResource\Pages;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Filament\Resources\ClientResource\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\ProvisionElementsRelationManager;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $pluralModelLabel = 'Clients';

    protected static ?string $modelLabel = 'Client';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'long_name', 'note'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Tabs::make('Tabs')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tabs\Tab::make('Base')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nom')
                                    ->required(),
                                Forms\Components\TextInput::make('long_name')
                                    ->label('Nom long'),
                                Forms\Components\Select::make('category_id')
                                    ->label('Catégorie')
                                    ->required()
                                    ->relationship('category', 'name'),
                                Forms\Components\Textarea::make('note')
                                    ->label('Note'),
                            ]),
                        Tabs\Tab::make('Contact')
                            ->columns(12)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Téléphone')
                                    ->tel()
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('website')
                                    ->label('Site web')
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('address')
                                    ->label('Adresse')
                                    ->required()
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('address_extension')
                                    ->label('Adresse (complément)')
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('postal_code')
                                    ->label('Code postal')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('locality')
                                    ->label('Localité')
                                    ->required()
                                    ->columnSpan(3),
                                /*
                            Forms\Components\TextInput::make('country_code')
                                ->label('Pays'),
                            */
                            ]),
                        Tabs\Tab::make('Facturation')
                            ->columns(2)
                            ->schema([
                                Forms\Components\Fieldset::make('Contact et adresse de facturation')
                                    //->description('Laisser vide si pas de changement par rapport à l\'adresse de base')
                                    ->columns(12)
                                    ->schema([
                                        Forms\Components\TextInput::make('invoicing_name')
                                            ->label('Nom')
                                            ->columnSpan(6),
                                        Forms\Components\TextInput::make('invoicing_email')
                                            ->label('Email')
                                            ->columnSpan(6),
                                        Forms\Components\TextInput::make('invoicing_address')
                                            ->label('Adresse')
                                            ->columnSpan(4),
                                        Forms\Components\TextInput::make('invoicing_address_extension')
                                            ->label('Adresse (complément)')
                                            ->columnSpan(3),
                                        Forms\Components\TextInput::make('invoicing_postal_code')
                                            ->label('Code postal')
                                            ->columnSpan(2),
                                        Forms\Components\TextInput::make('invoicing_locality')
                                            ->label('Localité')
                                            ->columnSpan(3),
                                    ]),
                                Forms\Components\Fieldset::make('Relation bancaire')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('ide')
                                            ->label('CH-IDE'),
                                        Forms\Components\TextInput::make('iban')
                                            ->label('IBAN'),
                                        Forms\Components\TextInput::make('iban_qr')
                                            ->label('QR IBAN'),
                                    ]),
                                Forms\Components\Textarea::make('invoicing_note')
                                    ->label('Note pour la facturation')
                                    ->autosize()
                                    ->columnSpanFull(),
                            ]),
                        Tabs\Tab::make('Style')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('logo')
                                    ->label('Logo')
                                    ->collection('logos')
                                    ->image()
                                    ->imagePreviewHeight('100')
                                    ->openable()
                                    ->downloadable(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('latestEngagement.stage')
                    ->label('Progression')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('latestEngagement.status')
                    ->label('Statut')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('long_name')
                    ->sortable()
                    ->toggleable()
                    ->searchable(),
                /*Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo'),*/
                SpatieMediaLibraryImageColumn::make('logo')
                    ->collection('logos'),
                Tables\Columns\TextColumn::make('email')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('address_extension')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('locality')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('postal_code')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('currentInvoices.number')
                    ->label('Factures')
                    ->toggleable()
                    ->formatStateUsing(fn (Model $record): View => view(
                        'tables.columns.client-invoices',
                        ['record' => $record],
                    )),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->multiple()
                    ->preload()
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('pdf')
                    ->label('Fiche')
                    ->url(fn (Model $record): string => $record->pdfLink)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-document'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    BulkAction::make('export_logos')
                        ->label('Exporter les logos (.zip)')
                        ->icon('heroicon-o-arrow-down-on-square-stack')
                        ->action(function (Collection $records) {
                            $downloads = $records->map(function ($record) {
                                $media = $record->getMedia('logos')->first();
                                if ($media) {
                                    $media->name = str()->slug($record->name.'-logo');
                                    $media->file_name = str()->slug($record->name).'-logo.'.pathinfo($media->file_name, PATHINFO_EXTENSION);
                                    $media->save();

                                    return $media;
                                }

                                return null;
                            });
                            $downloads = $downloads->filter();

                            return MediaStream::create('logos.zip')->addMedia($downloads);
                        }),
                    BulkAction::make('send_advertiser_form')
                        ->label('Envoyer formulaire annonceur')
                        ->icon('heroicon-o-envelope')
                        ->action(function (Collection $records) {
                            foreach ($records as $client) {
                                $previousOrderDetails = $client->getPreviousEditionProvisionElementsDetails();
                                $client->notify(new ClientAdvertiserFormLink($client, $previousOrderDetails));
                            }
                            \Filament\Notifications\Notification::make()
                                ->title('Formulaires annonceurs envoyés')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exporter')
                    ->exporter(ClientExporter::class),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProvisionElementsRelationManager::class,
            ContactsRelationManager::class,
            DocumentsRelationManager::class,
            InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit'   => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
