<?php

namespace App\Filament\Resources;

use Exception;
use Filament\Forms;
use App\Models\Client;
use App\Models\Edition;
use Livewire\Component;
use App\Models\Provision;
use App\Helpers\AppHelper;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Models\ClientCategory;
use App\Models\ClientEngagement;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\ActionGroup;
use Filament\Support\Enums\Width;
use App\Enums\EngagementStageEnum;
use Filament\Actions\ExportAction;
use App\Enums\EngagementStatusEnum;
use Illuminate\Contracts\View\View;
use App\Services\ClientMergeService;
use Filament\Forms\Components\Radio;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Exports\ClientExporter;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Enums\ProvisionElementStatusEnum;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Tables\Columns\TextInputColumn;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\Support\MediaStream;
use App\Notifications\ClientAdvertiserFormLink;
use App\Notifications\RecipientSendVipInvitation;
use App\Notifications\ClientAdvertiserFormRelaunch;
use App\Notifications\ClientAdvertiserMediaMissing;
use App\Notifications\ClientInterclassDonorRequest;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use App\Filament\Resources\ClientResource\Pages\EditClient;
use App\Notifications\ClientInterclassDonorRequestRelaunch;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Filament\Resources\ClientResource\Pages\ListClients;
use App\Filament\Resources\ClientResource\Pages\CreateClient;
use App\Filament\Resources\ClientResource\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\ClientResource\RelationManagers\ProvisionElementsRelationManager;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $pluralModelLabel = 'Clients';

    protected static ?string $modelLabel = 'Client';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'long_name', 'note'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('Tabs')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Base')
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nom')
                                    ->required(),
                                TextInput::make('long_name')
                                    ->label('Nom long'),
                                Select::make('category_id')
                                    ->label('Catégorie')
                                    ->required()
                                    ->relationship('category', 'name'),
                                Textarea::make('note')
                                    ->label('Note'),
                            ]),
                        Tab::make('Contact')
                            ->columns(12)
                            ->schema([
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->columnSpan(4),
                                TextInput::make('phone')
                                    ->label('Téléphone')
                                    ->tel()
                                    ->columnSpan(4),
                                TextInput::make('website')
                                    ->label('Site web')
                                    ->columnSpan(4),
                                TextInput::make('address')
                                    ->label('Adresse')
                                    ->required()
                                    ->columnSpan(4),
                                TextInput::make('address_extension')
                                    ->label('Adresse (complément)')
                                    ->columnSpan(3),
                                TextInput::make('postal_code')
                                    ->label('Code postal')
                                    ->required()
                                    ->columnSpan(2),
                                TextInput::make('locality')
                                    ->label('Localité')
                                    ->required()
                                    ->columnSpan(3),
                                /*
                            Forms\Components\TextInput::make('country_code')
                                ->label('Pays'),
                            */
                            ]),
                        Tab::make('Facturation')
                            ->columns(2)
                            ->schema([
                                Fieldset::make('Contact et adresse de facturation')
                                    // ->description('Laisser vide si pas de changement par rapport à l\'adresse de base')
                                    ->columns(12)
                                    ->schema([
                                        TextInput::make('invoicing_name')
                                            ->label('Nom')
                                            ->columnSpan(6),
                                        TextInput::make('invoicing_email')
                                            ->label('Email')
                                            ->columnSpan(6),
                                        TextInput::make('invoicing_address')
                                            ->label('Adresse')
                                            ->columnSpan(4),
                                        TextInput::make('invoicing_address_extension')
                                            ->label('Adresse (complément)')
                                            ->columnSpan(3),
                                        TextInput::make('invoicing_postal_code')
                                            ->label('Code postal')
                                            ->columnSpan(2),
                                        TextInput::make('invoicing_locality')
                                            ->label('Localité')
                                            ->columnSpan(3),
                                    ]),
                                Fieldset::make('Relation bancaire')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('ide')
                                            ->label('CH-IDE'),
                                        TextInput::make('iban')
                                            ->label('IBAN'),
                                        TextInput::make('iban_qr')
                                            ->label('QR IBAN'),
                                    ]),
                                Textarea::make('invoicing_note')
                                    ->label('Note pour la facturation')
                                    ->autosize()
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Style')
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
                TextColumn::make('category.name')
                    ->badge()
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
                TextInputColumn::make('currentEngagement.responsible')
                    ->label('Responsable')
                    ->toggleable(),
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('long_name')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                SpatieMediaLibraryImageColumn::make('logo')
                    ->collection('logos'),
                TextColumn::make('email')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('phone')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('address')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('address_extension')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('locality')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('postal_code')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('currentEngagement.sent_at')
                    ->label('Env. le')
                    ->date('d.m.y')
                    ->dateTimeTooltip('d.m.Y H:i:s')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('currentEngagement.viewed_at')
                    ->label('Vu le')
                    ->date('d.m.y')
                    ->dateTimeTooltip('d.m.Y H:i:s')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('currentEngagement.relaunched_at')
                    ->label('Rel. le')
                    ->date('d.m.y')
                    ->dateTimeTooltip('d.m.Y H:i:s')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('currentInvoices.number')
                    ->label('Factures')
                    ->toggleable()
                    ->formatStateUsing(fn (Model $record): View => view(
                        'tables.columns.client-invoices',
                        ['record' => $record],
                    )),
            ])
            ->filters([
                TrashedFilter::make(),
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
                SelectFilter::make('provision_in')
                    ->label('Prestations')
                    ->multiple()
                    ->options(Provision::all()->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        $values = $data['values'];
                        if (empty($values)) {
                            return $query;
                        }

                        return $query->whereRelation('provisionElements', function (Builder $query) use ($values) {
                            $query->whereIn('provision_id', $values)
                                ->where('edition_id', session('edition_id'));
                        });
                    }),
                SelectFilter::make('provision_not_in')
                    ->label('N\'a pas les prestations')
                    ->multiple()
                    ->options(Provision::all()->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        $values = $data['values'];
                        if (empty($values)) {
                            return $query;
                        }

                        return $query->whereDoesntHave('provisionElements', function (Builder $query) use ($values) {
                            $query->whereIn('provision_id', $values)
                                ->where('edition_id', session('edition_id'));
                        });
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('pdf')
                        ->label('Fiche')
                        ->url(fn (Model $record): string => $record->pdfLink)
                        ->openUrlInNewTab()
                        ->icon('heroicon-o-document'),
                ])->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('send_advertiser_form')
                        ->label('Envoyer le formulaire annonceur')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
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
                    BulkAction::make('send_company_registration_invitation')
                        ->label('Envoyer invitation inscription entreprise (Lien signé)')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $sentCount = 0;
                            foreach ($records as $client) {
                                $signedUrl = URL::signedRoute('front.run-registration.create', [
                                    'type'      => 'company',
                                    'client_id' => $client->id,
                                ]);

                                try {
                                    $client->notify(new ClientSendVouchers(
                                        $client->vouchers,
                                        "Veuillez utiliser ce lien pré-rempli pour compléter l'inscription de vos coureurs d'entreprise :\n".$signedUrl
                                    ));
                                    $sentCount++;
                                } catch (Exception $e) {
                                    // Ignore mail errors
                                }
                            }
                            Notification::make()
                                ->title($sentCount.' invitation(s) d\'inscription entreprise envoyée(s)')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('relaunch_advertiser_form')
                        ->label('Relancer annonceur avec formulaire')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
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
                    BulkAction::make('send_interclass_donors_email')
                        ->label('Envoyer la demande aux donateurs interclasses')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            foreach ($records as $client) {
                                $previousOrderDetails = $client->getPreviousEditionProvisionElementsDetails();
                                $client->notify(new ClientInterclassDonorRequest($client, $previousOrderDetails));

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
                                ->title('Emails donateurs interclasses envoyés')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('relaunch_interclass_donors_email')
                        ->label('Relancer les donateurs interclasses')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            foreach ($records as $client) {
                                $previousOrderDetails = $client->getPreviousEditionProvisionElementsDetails();
                                $client->notify(new ClientInterclassDonorRequestRelaunch($client, $previousOrderDetails));

                                // ClientEngagement
                                $engagement = $client->currentEngagement()->firstOrCreate([
                                    'edition_id' => AppHelper::getCurrentEditionId(),
                                ]);
                                $engagement->stage = EngagementStageEnum::ProposalSent;
                                $engagement->status = EngagementStatusEnum::Relaunched;
                                $engagement->sent_at = now();
                                $engagement->save();
                            }
                            Notification::make()
                                ->title('Emails donateurs interclasses envoyés')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('ClientAdvertiserMediaMissing')
                        ->label('Envoyer demande pour média manquant (avec contrôle)')
                        ->icon('heroicon-o-envelope')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $sentCount = 0;
                            foreach ($records as $client) {
                                // Filter provision elements that require a visual (format_indicator is not null)
                                $elementsRequiringMedia = $client->currentProvisionElements->filter(function ($pe) {
                                    return $pe->provision?->format_indicator !== null;
                                });

                                // If there are such elements, check if any of them is missing media
                                if ($elementsRequiringMedia->isNotEmpty()) {
                                    $isMissingMedia = $elementsRequiringMedia->contains(function ($pe) {
                                        return $pe->getMedia('*')->isEmpty();
                                    });

                                    if ($isMissingMedia) {
                                        $client->notify(new ClientAdvertiserMediaMissing);
                                        $sentCount++;
                                    }
                                }
                            }
                            Notification::make()
                                ->title($sentCount.' email(s) pour médias manquants envoyé(s)')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('sendVipInvitation')
                        ->label('Envoyer invitations VIP')
                        ->icon('heroicon-m-envelope')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $clients): void {
                            foreach ($clients as $client) {
                                $vipProvisionElement = $client->currentProvisionElements()->where('provision_id', setting('vip_provision'))->first();
                                if ($vipProvisionElement?->provision_id == (int) setting('vip_provision')) {
                                    if ($client->vipContactEmail != null) {
                                        $client->notify(new RecipientSendVipInvitation($vipProvisionElement));
                                        $vipProvisionElement->status = ProvisionElementStatusEnum::Sent;
                                        $vipProvisionElement->save();
                                    } else {
                                        $vipProvisionElement->status = ProvisionElementStatusEnum::ActionRequired;
                                        $vipProvisionElement->save();
                                    }
                                }
                            }
                            Notification::make()
                                ->title('Invitations VIP envoyées ('.$clients->count().')')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('mark_as_relaunched')
                        ->label('Marquer comme relancé (manuellement)')
                        ->icon('heroicon-o-check-badge')
                        ->color('info')
                        ->action(function (Collection $records) {
                            foreach ($records as $client) {
                                // ClientEngagement
                                $engagement = $client->currentEngagement()->firstOrCreate([
                                    'edition_id' => AppHelper::getCurrentEditionId(),
                                ]);
                                $engagement->stage = EngagementStageEnum::ProposalSent;
                                $engagement->status = EngagementStatusEnum::Relaunched;
                                $engagement->sent_at = now();
                                $engagement->save();
                            }
                            Notification::make()
                                ->title('Clients marqués comme relancés manuellement')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('copyEmail')
                        ->label('Copier emails')
                        ->icon('heroicon-m-clipboard-document-list')
                        ->action(function (Component $livewire, Collection $records) {
                            $clipboard = '';
                            foreach ($records as $record) {
                                $email = $record->contactEmail;
                                $clipboard .= "$email\n";
                            }
                            $livewire->dispatch('copy-to-clipboard', $clipboard);
                        })
                        ->extraAttributes([
                            'x-on:copy-to-clipboard.window' => 'navigator.clipboard.writeText($event.detail)',
                        ]),
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
                    BulkAction::make('bulkEdit')
                        ->label('Éditer en masse')
                        ->icon('heroicon-m-pencil-square')
                        ->schema([
                            Select::make('category_id')
                                ->label('Catégorie')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable(),
                            TextInput::make('locality')
                                ->label('Localité')
                                ->nullable(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $updatedCount = 0;
                            foreach ($records as $record) {
                                $changed = false;
                                foreach (collect($data)->keys() as $key) {
                                    if (! empty($data[$key])) {
                                        $record->$key = $data[$key];
                                        $changed = true;
                                    }
                                }
                                if ($changed) {
                                    $record->save();
                                    $updatedCount++;
                                }
                            }

                            Notification::make()
                                ->title('Clients mis à jour')
                                ->body("{$updatedCount} client(s) ont été mis à jour.")
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('copy_previous_responsible')
                        ->label('Reprendre les responsables (édition précédente)')
                        ->icon('heroicon-o-arrow-path')
                        ->schema([
                            Forms\Components\Toggle::make('only_if_empty')
                                ->label('Remplacer uniquement si le responsable actuel est vide')
                                ->default(true),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $currentEditionId = AppHelper::getCurrentEditionId();
                            $currentEdition = Edition::find($currentEditionId);
                            $previousEdition = Edition::where('year', '<', $currentEdition?->year)
                                ->orderBy('year', 'desc')
                                ->first();

                            if (! $previousEdition) {
                                Notification::make()
                                    ->title('Aucune édition précédente trouvée')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $updatedCount = 0;
                            foreach ($records as $client) {
                                $prevEngagement = $client->clientEngagements()
                                    ->where('edition_id', $previousEdition->id)
                                    ->first();

                                if (! $prevEngagement || (! $prevEngagement->responsible && ! $prevEngagement->responsible_contact_id)) {
                                    continue;
                                }

                                $currentEngagement = $client->currentEngagement()->firstOrCreate([
                                    'edition_id' => $currentEditionId,
                                ]);

                                if ($data['only_if_empty'] && $currentEngagement->responsible) {
                                    continue;
                                }

                                $currentEngagement->responsible = $prevEngagement->responsible;
                                $currentEngagement->responsible_contact_id = $prevEngagement->responsible_contact_id;
                                $currentEngagement->save();
                                $updatedCount++;
                            }

                            Notification::make()
                                ->title('Responsables copiés')
                                ->body("Le responsable a été repris de l'édition {$previousEdition->year} pour {$updatedCount} client(s).")
                                ->success()
                                ->send();
                        }),
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
                                Fieldset::make('Données à conserver')
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
                                        $radioFields[] = Textarea::make('note')
                                            ->label('Note')
                                            ->default(trim($clientA->note."\n---\n".$clientB->note))
                                            ->helperText('Les notes des deux clients seront fusionnées par défaut.');
                                        $radioFields[] = Textarea::make('invoicing_note')
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

                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Erreur lors de la fusion')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->modalWidth(Width::FourExtraLarge),

                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ])->dropdownWidth(Width::Large),
            ])
            ->headerActions([
                Action::make('provisions_comparison_report')
                    ->label('Rapport comparatif des prestations')
                    ->icon('heroicon-o-chart-bar-square')
                    ->schema(function () {
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
                            Select::make('client_category_ids')
                                ->label('Catégories de client')
                                ->options($clientCategories)
                                ->multiple()
                                ->searchable()
                                ->preload(),
                        ];
                    })
                    ->action(function (array $data) {
                        $params = [
                            'reference_edition_id'  => $data['reference_edition_id'],
                            'comparison_edition_id' => $data['comparison_edition_id'],
                        ];

                        if (! empty($data['client_category_ids'])) {
                            $params['client_category_ids'] = $data['client_category_ids'];
                        }

                        $url = route('reports.provisions-comparison', $params);

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
            'index'  => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'edit'   => EditClient::route('/{record}/edit'),
        ];
    }
}
