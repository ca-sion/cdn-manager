<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Client;
use App\Models\Edition;
use Filament\Forms\Form;
use App\Helpers\AppHelper;
use Filament\Tables\Table;
use App\Models\ClientCategory;
use App\Models\ClientEngagement;
use Filament\Resources\Resource;
use App\Enums\EngagementStageEnum;
use App\Enums\EngagementStatusEnum;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Actions\Action;
use Illuminate\Contracts\View\View;
use App\Services\ClientMergeService;
use Filament\Forms\Components\Radio;
use Filament\Support\Enums\MaxWidth;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Exports\ClientExporter;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextInputColumn;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\Support\MediaStream;
use App\Notifications\ClientAdvertiserFormLink;
use App\Filament\Resources\ClientResource\Pages;
use App\Notifications\ClientAdvertiserFormRelaunch;
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
                Tables\Columns\TextColumn::make('currentEngagement.stage')
                    ->label('Progression')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('currentEngagement.status')
                    ->label('Statut')
                    ->badge()
                    ->toggleable(),
                TextInputColumn::make('currentEngagement.responsible')
                    ->label('Responsable')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('long_name')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
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
                Tables\Columns\TextColumn::make('currentEngagement.sent_at')
                    ->label('Env. le')
                    ->date('d.m.y')
                    ->dateTimeTooltip('d.m.Y H:i:s')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currentEngagement.viewed_at')
                    ->label('Vu le')
                    ->date('d.m.y')
                    ->dateTimeTooltip('d.m.Y H:i:s')
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('currentEngagement.relaunched_at')
                    ->label('Rel. le')
                    ->date('d.m.y')
                    ->dateTimeTooltip('d.m.Y H:i:s')
                    ->toggleable()
                    ->sortable(),
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
                SelectFilter::make('category')
                    ->label('Catégorie')
                    ->multiple()
                    ->preload()
                    ->relationship('category', 'name'),
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
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('pdf')
                        ->label('Fiche')
                        ->url(fn (Model $record): string => $record->pdfLink)
                        ->openUrlInNewTab()
                        ->icon('heroicon-o-document'),
                ])->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),

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
                        ->label('Envoyer le formulaire annonceur')
                        ->icon('heroicon-o-envelope')
                        ->action(function (Collection $records) {
                            foreach ($records as $client) {
                                $previousOrderDetails = $client->getPreviousEditionProvisionElementsDetails();
                                $client->notify(new ClientAdvertiserFormLink($client, $previousOrderDetails));

                                // ClientEngagement
                                $engagement = $client->currentEngagement()->firstOrCreate([
                                    'edition_id' => AppHelper::getCurrentEditionId(),
                                ]);
                                $engagement->stage = EngagementStageEnum::ProposalSent;
                                $engagement->status = EngagementStatusEnum::Idle;
                                $engagement->sent_at = now();
                                $engagement->save();
                            }
                            Notification::make()
                                ->title('Formulaires annonceurs envoyés')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('relaunch_advertiser_form')
                        ->label('Relancer annonceur avec formulaire')
                        ->icon('heroicon-o-envelope')
                        ->action(function (Collection $records) {
                            foreach ($records as $client) {
                                $previousOrderDetails = $client->getPreviousEditionProvisionElementsDetails();
                                $client->notify(new ClientAdvertiserFormRelaunch($client, $previousOrderDetails));

                                // ClientEngagement
                                $engagement = $client->currentEngagement()->firstOrCreate([
                                    'edition_id' => AppHelper::getCurrentEditionId(),
                                ]);
                                $engagement->stage = EngagementStageEnum::ProposalSent;
                                $engagement->status = EngagementStatusEnum::Relaunched;
                                $engagement->relaunched_at = now();
                                $engagement->save();
                            }
                            Notification::make()
                                ->title('Formulaires annonceurs re-envoyés')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('update_engagement')
                        ->label('Modifier le statut')
                        ->icon('heroicon-o-briefcase')
                        ->form([
                            Select::make('stage')
                                ->label('Progression')
                                ->nullable()
                                ->options(EngagementStageEnum::class)
                                ->default(EngagementStageEnum::Prospect),
                            Select::make('status')
                                ->label('Statut')
                                ->nullable()
                                ->options(EngagementStatusEnum::class)
                                ->default(EngagementStatusEnum::Idle),
                            TextInput::make('responsible')
                                ->label('Responsable')
                                ->nullable(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            foreach ($records as $client) {
                                $engagement = $client->currentEngagement()->firstOrCreate([
                                    'edition_id' => AppHelper::getCurrentEditionId(),
                                ]);

                                $engagement->stage = $data['stage'];
                                $engagement->status = $data['status'];
                                $engagement->responsible = $data['responsible'];
                                $engagement->save();
                            }

                            Notification::make()
                                ->title('Engagements mis à jour')
                                ->body(count($records).' engagements ont été mis à jour.')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('merge_clients')
                        ->label('Fusionner les clients')
                        ->icon('heroicon-o-user-group')
                        ->requiresConfirmation()
                        ->form(function (Collection $records) {
                            if ($records->count() !== 2) {
                                Notification::make()
                                    ->title('Sélection invalide')
                                    ->body('Veuillez sélectionner exactement deux clients à fusionner.')
                                    ->danger()
                                    ->send();

                                return [];
                            }

                            [$clientA, $clientB] = $records->all();

                            return [
                                Radio::make('primary_client_id')
                                    ->label('Client principal')
                                    ->helperText('Sélectionnez le client qui sera conservé. L\'autre sera supprimé.')
                                    ->options([
                                        $clientA->id => $clientA->name,
                                        $clientB->id => $clientB->name,
                                    ])
                                    ->required(),
                                Forms\Components\Fieldset::make('Données à conserver')
                                    ->columns(2)
                                    ->schema(function () use ($clientA, $clientB) {
                                        $fields = ['name', 'long_name', 'email', 'phone', 'website', 'address', 'address_extension', 'postal_code', 'locality', 'invoicing_name', 'invoicing_email', 'invoicing_address', 'invoicing_address_extension', 'invoicing_postal_code', 'invoicing_locality', 'ide', 'iban', 'iban_qr'];
                                        $radioFields = [];
                                        foreach ($fields as $field) {
                                            if ($clientA->$field !== $clientB->$field) {
                                                $radioFields[] = Radio::make($field)
                                                    ->label(str_replace('_', ' ', ucfirst($field)))
                                                    ->options([
                                                        'A' => $clientA->$field ?? 'Vide',
                                                        'B' => $clientB->$field ?? 'Vide',
                                                    ])
                                                    ->default('A');
                                            }
                                        }
                                        $radioFields[] = Forms\Components\Textarea::make('note')
                                            ->label('Note')
                                            ->default(trim($clientA->note."\n---\n".$clientB->note))
                                            ->helperText('Les notes des deux clients seront fusionnées par défaut.');
                                        $radioFields[] = Forms\Components\Textarea::make('invoicing_note')
                                            ->label('Note de facturation')
                                            ->default(trim($clientA->invoicing_note."\n---\n".$clientB->invoicing_note))
                                            ->helperText('Les notes de facturation des deux clients seront fusionnées par défaut.');

                                        return $radioFields;
                                    }),
                            ];
                        })
                        ->action(function (Collection $records, array $data) {
                            if ($records->count() !== 2) {
                                return;
                            }

                            [$clientA, $clientB] = $records->all();

                            $primaryClientId = $data['primary_client_id'];
                            $primaryClient = ($clientA->id == $primaryClientId) ? $clientA : $clientB;
                            $secondaryClient = ($clientA->id != $primaryClientId) ? $clientA : $clientB;

                            try {
                                app(ClientMergeService::class)->merge($primaryClient, $secondaryClient, $data, $clientA, $clientB);

                                Notification::make()
                                    ->title('Fusion réussie')
                                    ->body("Le client '{$secondaryClient->name}' a été fusionné dans '{$primaryClient->name}'.")
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur lors de la fusion')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->modalWidth(MaxWidth::FourExtraLarge),
                ])->dropdownWidth(MaxWidth::Large),
            ])
            ->headerActions([
                Action::make('provisions_comparison_report')
                    ->label('Rapport comparatif des prestations')
                    ->icon('heroicon-o-chart-bar-square')
                    ->form(function () {
                        $editions = Edition::orderBy('year', 'desc')->pluck('year', 'id');
                        $currentEdition = Edition::find(AppHelper::getCurrentEditionId());
                        $previousEdition = Edition::where('year', '<', $currentEdition?->year)->orderBy('year', 'desc')->first();
                        $clientCategories = ClientCategory::orderBy('name')->pluck('name', 'id');

                        return [
                            Select::make('reference_edition_id')
                                ->label('Édition de référence')
                                ->options($editions)
                                ->default($currentEdition?->id)
                                ->required(),
                            Select::make('comparison_edition_id')
                                ->label('Édition de comparaison')
                                ->options($editions)
                                ->default($previousEdition?->id)
                                ->required(),
                            Select::make('client_category_id')
                                ->label('Catégorie de client')
                                ->options($clientCategories)
                                ->searchable()
                                ->preload(),
                        ];
                    })
                    ->action(function (array $data) {
                        $url = route('reports.provisions-comparison', [
                            'reference_edition_id'  => $data['reference_edition_id'],
                            'comparison_edition_id' => $data['comparison_edition_id'],
                            'client_category_id'    => $data['client_category_id'] ?? null,
                        ]);

                        return redirect($url);
                    })
                    ->openUrlInNewTab()
                    ->modalSubmitActionLabel('Générer le rapport'),
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
