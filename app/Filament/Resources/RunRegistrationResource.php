<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Client;
use App\Models\RunRegistration;
use App\Enums\RunRegistrationType;
use Filament\Resources\Resource;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Services\RunRegistrationService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\URL;
use App\Filament\Resources\RunRegistrationResource\Pages;
use App\Filament\Resources\RunRegistrationResource\RelationManagers;

class RunRegistrationResource extends Resource
{
    protected static ?string $model = RunRegistration::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Courses';

    protected static ?string $modelLabel = 'Inscription course';

    protected static ?string $pluralModelLabel = 'Inscriptions courses';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Details')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Général')
                            ->schema([
                                Forms\Components\Select::make('run_registration_type')
                                    ->label('Type d\'inscription')
                                    ->options(RunRegistrationType::class)
                                    ->required()
                                    ->reactive(),
                                Forms\Components\Select::make('client_id')
                                    ->label('Client associé')
                                    ->relationship('client', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                                Forms\Components\TextInput::make('company_name')
                                    ->label('Nom de l\'entreprise')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['company', RunRegistrationType::Company->value, RunRegistrationType::Company])),
                                Forms\Components\TextInput::make('school_name')
                                    ->label('Nom de l\'école')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                Forms\Components\TextInput::make('school_postal_code')
                                    ->label('N° Postal École')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                Forms\Components\TextInput::make('school_locality')
                                    ->label('Localité École')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                Forms\Components\TextInput::make('school_country')
                                    ->label('Pays École')
                                    ->default('SUI')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                Forms\Components\TextInput::make('school_class_level')
                                    ->label('Niveau / Classe')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                Forms\Components\TextInput::make('school_class_holder_first_name')
                                    ->label('Prénom titulaire classe')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                Forms\Components\TextInput::make('school_class_holder_last_name')
                                    ->label('Nom titulaire classe')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                Forms\Components\TextInput::make('school_class_holder_email')
                                    ->label('Email titulaire classe')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                Forms\Components\TextInput::make('school_class_holder_phone')
                                    ->label('Tél titulaire classe')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Personne de contact')
                            ->schema([
                                Forms\Components\TextInput::make('contact_first_name')
                                    ->label('Prénom contact')
                                    ->required(),
                                Forms\Components\TextInput::make('contact_last_name')
                                    ->label('Nom contact')
                                    ->required(),
                                Forms\Components\TextInput::make('contact_email')
                                    ->label('Email contact')
                                    ->email()
                                    ->required(),
                                Forms\Components\TextInput::make('contact_phone')
                                    ->label('Téléphone contact'),
                            ])->columns(2),

                        Forms\Components\Tabs\Tab::make('Facturation & Paiement')
                            ->schema([
                                Forms\Components\TextInput::make('invoicing_company_name')
                                    ->label('Raison sociale facturation'),
                                Forms\Components\TextInput::make('invoicing_address')
                                    ->label('Adresse facturation'),
                                Forms\Components\TextInput::make('invoicing_address_extension')
                                    ->label('Complément adresse'),
                                Forms\Components\TextInput::make('invoicing_postal_code')
                                    ->label('Code postal'),
                                Forms\Components\TextInput::make('invoicing_locality')
                                    ->label('Localité facturation'),
                                Forms\Components\TextInput::make('invoicing_email')
                                    ->label('Email facturation')
                                    ->email(),
                                Forms\Components\TextInput::make('payment_iban')
                                    ->label('IBAN de paiement'),
                                Forms\Components\Textarea::make('invoicing_note')
                                    ->label('Note de facturation'),
                                Forms\Components\Textarea::make('payment_note')
                                    ->label('Note de paiement'),
                            ])->columns(2),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('run_registration_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('organism')
                    ->label('Organisme / Nom')
                    ->getStateUsing(fn ($record) => $record->company_name ?: ($record->school_name ?: ($record->contact_first_name.' '.$record->contact_last_name)))
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query->where('company_name', 'like', "%{$search}%")
                            ->orWhere('school_name', 'like', "%{$search}%")
                            ->orWhere('contact_first_name', 'like', "%{$search}%")
                            ->orWhere('contact_last_name', 'like', "%{$search}%");
                    }),
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Personne de contact')
                    ->getStateUsing(fn ($record) => $record->contact_first_name.' '.$record->contact_last_name)
                    ->description(fn ($record) => $record->contact_email)
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query->where('contact_first_name', 'like', "%{$search}%")
                            ->orWhere('contact_last_name', 'like', "%{$search}%")
                            ->orWhere('contact_email', 'like', "%{$search}%");
                    }),
                Tables\Columns\TextColumn::make('run_registration_elements_count')
                    ->label('Participants')
                    ->counts('runRegistrationElements')
                    ->sortable(),
                Tables\Columns\TextColumn::make('locality')
                    ->label('Localité')
                    ->getStateUsing(fn ($record) => $record->invoicing_locality ?: $record->school_locality)
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->orderBy('invoicing_locality', $direction);
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query->where('invoicing_locality', 'like', "%{$search}%")
                            ->orWhere('school_locality', 'like', "%{$search}%");
                    }),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Client')
                    ->placeholder('Non associé')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('run_registration_type')
                    ->label('Type d\'inscription')
                    ->options(RunRegistrationType::class),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    // Fast Client Association
                    Tables\Actions\Action::make('associateClient')
                        ->label('Associer à un client')
                        ->icon('heroicon-o-user-group')
                        ->form([
                            Forms\Components\Select::make('client_id')
                                ->label('Sélectionner le client')
                                ->options(fn () => Client::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (RunRegistration $record, array $data) {
                            $record->client_id = $data['client_id'];
                            $record->save();
                            Notification::make()->title('Client associé avec succès.')->success()->send();
                        }),

                    // Quick Client Creation
                    Tables\Actions\Action::make('createClient')
                        ->label('Créer client rapide')
                        ->icon('heroicon-o-user-plus')
                        ->requiresConfirmation()
                        ->modalHeading('Créer un nouveau client à partir de cette inscription')
                        ->modalDescription('Un nouveau client sera créé pré-rempli avec les coordonnées de facturation.')
                        ->action(function (RunRegistration $record) {
                            $name = $record->invoicing_company_name ?: ($record->company_name ?: ($record->school_name ?: ($record->contact_first_name.' '.$record->contact_last_name)));
                            $client = Client::create([
                                'name'        => $name,
                                'address'     => $record->invoicing_address,
                                'postal_code' => $record->invoicing_postal_code,
                                'city'        => $record->invoicing_locality,
                                'email'       => $record->invoicing_email ?: $record->contact_email,
                                'phone'       => $record->contact_phone,
                            ]);

                            $record->client_id = $client->id;
                            $record->save();

                            Notification::make()->title('Client créé et associé !')->success()->send();
                        })
                        ->visible(fn ($record) => $record->client_id === null),

                    // Automatic Invoice Generation
                    Tables\Actions\Action::make('generateInvoice')
                        ->label('Générer facture')
                        ->icon('heroicon-o-document-currency-dollar')
                        ->action(function (RunRegistration $record) {
                            try {
                                app(RunRegistrationService::class)->createInvoice($record);
                                Notification::make()->title('Facture générée !')->success()->send();
                            } catch (\Exception $e) {
                                Notification::make()->title('Erreur')->body($e->getMessage())->danger()->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->client_id !== null),

                    // Open Public Signed Form
                    Tables\Actions\Action::make('openPublicForm')
                        ->label('Ouvrir l\'interface publique')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (RunRegistration $record) => URL::signedRoute('front.run-registration.edit', [
                            'registration' => $record->id,
                        ]))
                        ->openUrlInNewTab(),
                ]),
            ])
            ->headerActions([
                // Export Elite
                Tables\Actions\Action::make('exportElite')
                    ->label('Export Élite')
                    ->icon('heroicon-o-sparkles')
                    ->color('warning')
                    ->action(function () {
                        $records = RunRegistration::where('run_registration_type', 'elite')->get();
                        $data = collect();
                        foreach ($records as $registration) {
                            foreach ($registration->runRegistrationElements as $element) {
                                $data->push([
                                    'ID Dossier'          => $registration->id,
                                    'Nom'                 => $element->last_name,
                                    'Prénom'              => $element->first_name,
                                    'Date Naissance'      => $element->birthdate?->format('d.m.Y'),
                                    'Sexe'                => $element->gender?->value ?? $element->gender,
                                    'Nationalité'         => $element->nationality,
                                    'Email'               => $element->email,
                                    'Course'              => $element->run?->name ?? $element->run_name,
                                    'Bloc'                => $element->bloc,
                                    'Adresse'             => $element->address,
                                    'Complément'          => $element->address_extension,
                                    'Code Postal'         => $element->postal_code,
                                    'Localité'            => $element->locality,
                                    'Pays'                => $element->country,
                                    'IBAN'                => $element->iban,
                                    'Frais offerts'       => $element->has_free_registration_fee ? 'Oui' : 'Non',
                                    'Prime départ'        => $element->has_bonus_start ? 'Oui' : 'Non',
                                    'Montant départ'      => $element->bonus_start_amount,
                                    'Montant classement'  => $element->bonus_ranking_amount,
                                    'Montant arrivée'     => $element->bonus_arrival_amount,
                                    'Hébergement'         => $element->has_accommodation ? 'Oui' : 'Non',
                                    'Hébergement Ven'     => $element->accommodation_friday ? 'Oui' : 'Non',
                                    'Hébergement Sam'     => $element->accommodation_saturday ? 'Oui' : 'Non',
                                    'Précisions héb.'     => $element->accommodation_precision,
                                    'Remboursement frais' => $element->has_expense_reimbursement ? 'Oui' : 'Non',
                                    'Précisions frais'    => $element->expense_reimbursement_precision,
                                ]);
                            }
                        }
                        return (new FastExcel($data))->download('export_elite_'.date('Ymd_His').'.xlsx');
                    }),

                // Export Datasport
                Tables\Actions\Action::make('exportDatasportAll')
                    ->label('Export Datasport')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $registrations = RunRegistration::with('runRegistrationElements.run')->get();
                        $data = collect();
                        foreach ($registrations as $registration) {
                            foreach ($registration->runRegistrationElements as $element) {
                                $data->push([
                                    'ID Dossier'     => $registration->id,
                                    'Nom'            => $element->last_name,
                                    'Prénom'         => $element->first_name,
                                    'Date naissance' => $element->birthdate?->format('d.m.Y'),
                                    'Sexe'           => $element->gender?->value ?? $element->gender,
                                    'Nationalité'    => $element->nationality,
                                    'Email'          => $element->email,
                                    'Course'         => $element->run?->name ?? $element->run_name,
                                    'Bloc'           => $element->bloc,
                                    'Équipe'         => $element->team,
                                    'Adresse'        => $element->address ?: $registration->invoicing_address,
                                    'Code Postal'    => $element->postal_code ?: $registration->invoicing_postal_code,
                                    'Localité'       => $element->locality ?: $registration->invoicing_locality,
                                    'Pays'           => $element->country ?: $registration->school_country ?: 'SUI',
                                ]);
                            }
                        }
                        return (new FastExcel($data))->download('export_datasport_'.date('Ymd_His').'.xlsx');
                    }),

                // Export Aggregated Data
                Tables\Actions\Action::make('exportAggregatedData')
                    ->label('Export Données & Agrégations')
                    ->icon('heroicon-o-chart-bar')
                    ->color('success')
                    ->action(function () {
                        $registrations = RunRegistration::with(['runRegistrationElements.run', 'client'])->get();
                        $service = app(RunRegistrationService::class);
                        $data = collect();

                        foreach ($registrations as $reg) {
                            $typeLabel = $reg->run_registration_type instanceof RunRegistrationType
                                ? $reg->run_registration_type->getLabel()
                                : (string) $reg->run_registration_type;

                            $data->push([
                                'ID Dossier'          => $reg->id,
                                'Type'                => $typeLabel,
                                'Organisme'           => $reg->company_name ?: ($reg->school_name ?: ($reg->contact_first_name.' '.$reg->contact_last_name)),
                                'Personne contact'    => $reg->contact_first_name.' '.$reg->contact_last_name,
                                'Email contact'       => $reg->contact_email,
                                'Téléphone'           => $reg->contact_phone,
                                'Localité'            => $reg->invoicing_locality ?: $reg->school_locality,
                                'Client lié'          => $reg->client?->name ?? 'Non associé',
                                'Nombre participants' => $reg->runRegistrationElements->count(),
                                'Montant Total (CHF)' => $service->calculateTotal($reg),
                                'Date création'       => $reg->created_at?->format('d.m.Y H:i'),
                            ]);
                        }
                        return (new FastExcel($data))->download('export_inscriptions_agrégées_'.date('Ymd_His').'.xlsx');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('generateInvoices')
                        ->label('Générer factures')
                        ->icon('heroicon-o-document-currency-dollar')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $count = 0;
                            $errors = 0;
                            foreach ($records as $record) {
                                if ($record->client_id) {
                                    try {
                                        app(RunRegistrationService::class)->createInvoice($record);
                                        $count++;
                                    } catch (\Exception $e) {
                                        $errors++;
                                    }
                                } else {
                                    $errors++;
                                }
                            }
                            Notification::make()
                                ->title($count.' factures générées'.($errors > 0 ? ', '.$errors.' erreurs' : ''))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('exportDatasport')
                        ->label('Export Datasport Sélection')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $data = collect();
                            foreach ($records as $registration) {
                                foreach ($registration->runRegistrationElements as $element) {
                                    $data->push([
                                        'Nom'            => $element->last_name,
                                        'Prénom'         => $element->first_name,
                                        'Date naissance' => $element->birthdate?->format('d.m.Y'),
                                        'Sexe'           => $element->gender?->value ?? $element->gender,
                                        'Nationalité'    => $element->nationality,
                                        'Email'          => $element->email,
                                        'Course'         => $element->run?->name ?? $element->run_name,
                                        'Bloc'           => $element->bloc,
                                        'Équipe'         => $element->team,
                                        'Localité'       => $element->locality ?: $registration->invoicing_locality,
                                    ]);
                                }
                            }

                            return (new FastExcel($data))->download('export_datasport_'.date('Ymd_His').'.xlsx');
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RunRegistrationElementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRunRegistrations::route('/'),
            'create' => Pages\CreateRunRegistration::route('/create'),
            'edit'   => Pages\EditRunRegistration::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
