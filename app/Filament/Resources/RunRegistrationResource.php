<?php

namespace App\Filament\Resources;

use Exception;
use App\Models\Client;
use App\Models\School;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Enums\SchoolClassLevel;
use App\Models\RunRegistration;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\ActionGroup;
use App\Enums\RunRegistrationType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Rap2hpoutre\FastExcel\FastExcel;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use App\Services\RunRegistrationService;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\RunRegistrationResource\Pages\EditRunRegistration;
use App\Filament\Resources\RunRegistrationResource\Pages\ListRunRegistrations;
use App\Filament\Resources\RunRegistrationResource\Pages\CreateRunRegistration;
use App\Filament\Resources\RunRegistrationResource\RelationManagers\RunRegistrationElementsRelationManager;

class RunRegistrationResource extends Resource
{
    protected static ?string $model = RunRegistration::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Courses';

    protected static ?string $modelLabel = 'Inscription course';

    protected static ?string $pluralModelLabel = 'Inscriptions courses';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Details')
                    ->tabs([
                        Tab::make('Général')
                            ->schema([
                                Select::make('run_registration_type')
                                    ->label('Type d\'inscription')
                                    ->options(RunRegistrationType::class)
                                    ->required()
                                    ->reactive(),
                                Select::make('client_id')
                                    ->label('Client associé')
                                    ->relationship('client', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                                TextInput::make('company_name')
                                    ->label('Nom de l\'entreprise')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['company', RunRegistrationType::Company->value, RunRegistrationType::Company])),
                                Select::make('school_id')
                                    ->label('Établissement scolaire')
                                    ->relationship('school', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $school = School::find($state);
                                            if ($school) {
                                                $set('school_name', $school->name);
                                                $set('school_postal_code', $school->postal_code);
                                                $set('school_locality', $school->locality);
                                                $set('school_country', $school->country ?: 'SUI');
                                                if ($school->client_id) {
                                                    $set('client_id', $school->client_id);
                                                }
                                            }
                                        }
                                    })
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                TextInput::make('school_name')
                                    ->label('Nom de l\'école')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                TextInput::make('school_postal_code')
                                    ->label('N° Postal École')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                TextInput::make('school_locality')
                                    ->label('Localité École')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                TextInput::make('school_country')
                                    ->label('Pays École')
                                    ->default('SUI')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                Select::make('school_class_level')
                                    ->label('Niveau / Classe')
                                    ->options(SchoolClassLevel::class)
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                TextInput::make('school_class_holder_first_name')
                                    ->label('Prénom titulaire classe')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                TextInput::make('school_class_holder_last_name')
                                    ->label('Nom titulaire classe')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                TextInput::make('school_class_holder_email')
                                    ->label('Email titulaire classe')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                                TextInput::make('school_class_holder_phone')
                                    ->label('Tél titulaire classe')
                                    ->visible(fn ($get) => in_array($get('run_registration_type'), ['school', RunRegistrationType::School->value, RunRegistrationType::School])),
                            ])->columns(2),

                        Tab::make('Personne de contact')
                            ->schema([
                                TextInput::make('contact_first_name')
                                    ->label('Prénom contact')
                                    ->required(),
                                TextInput::make('contact_last_name')
                                    ->label('Nom contact')
                                    ->required(),
                                TextInput::make('contact_email')
                                    ->label('Email contact')
                                    ->email()
                                    ->required(),
                                TextInput::make('contact_phone')
                                    ->label('Téléphone contact'),
                            ])->columns(2),

                        Tab::make('Facturation & Paiement')
                            ->schema([
                                TextInput::make('invoicing_company_name')
                                    ->label('Raison sociale facturation'),
                                TextInput::make('invoicing_address')
                                    ->label('Adresse facturation'),
                                TextInput::make('invoicing_address_extension')
                                    ->label('Complément adresse'),
                                TextInput::make('invoicing_postal_code')
                                    ->label('Code postal'),
                                TextInput::make('invoicing_locality')
                                    ->label('Localité facturation'),
                                TextInput::make('invoicing_email')
                                    ->label('Email facturation')
                                    ->email(),
                                TextInput::make('payment_iban')
                                    ->label('IBAN de paiement'),
                                Textarea::make('invoicing_note')
                                    ->label('Note de facturation'),
                                Textarea::make('payment_note')
                                    ->label('Note de paiement'),
                            ])->columns(2),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('run_registration_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('organism')
                    ->label('Organisme / Nom')
                    ->getStateUsing(fn ($record) => $record->company_name ?: ($record->school_name ?: ($record->contact_first_name.' '.$record->contact_last_name)))
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query->where('company_name', 'like', "%{$search}%")
                            ->orWhere('school_name', 'like', "%{$search}%")
                            ->orWhere('contact_first_name', 'like', "%{$search}%")
                            ->orWhere('contact_last_name', 'like', "%{$search}%");
                    }),
                TextColumn::make('contact_name')
                    ->label('Personne de contact')
                    ->getStateUsing(fn ($record) => $record->contact_first_name.' '.$record->contact_last_name)
                    ->description(fn ($record) => $record->contact_email)
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query->where('contact_first_name', 'like', "%{$search}%")
                            ->orWhere('contact_last_name', 'like', "%{$search}%")
                            ->orWhere('contact_email', 'like', "%{$search}%");
                    }),
                TextColumn::make('run_registration_elements_count')
                    ->label('Participants')
                    ->counts('runRegistrationElements')
                    ->sortable(),
                TextColumn::make('locality')
                    ->label('Localité')
                    ->getStateUsing(fn ($record) => $record->invoicing_locality ?: $record->school_locality)
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->orderBy('invoicing_locality', $direction);
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query->where('invoicing_locality', 'like', "%{$search}%")
                            ->orWhere('school_locality', 'like', "%{$search}%");
                    }),
                TextColumn::make('client.name')
                    ->label('Client')
                    ->placeholder('Non associé')
                    ->searchable(),
                TextColumn::make('invoice_status')
                    ->label('Facture')
                    ->badge()
                    ->color(fn ($record) => $record->invoice ? ($record->invoice->status?->value === 'paid' ? 'success' : 'info') : 'gray')
                    ->getStateUsing(function ($record) {
                        if (! $record->invoice) {
                            return 'Non générée';
                        }
                        $num = $record->invoice->number;
                        $statusLabel = is_object($record->invoice->status) && method_exists($record->invoice->status, 'getLabel')
                            ? $record->invoice->status->getLabel()
                            : (string) ($record->invoice->status?->value ?? $record->invoice->status);

                        return "#{$num} ({$statusLabel})";
                    })
                    ->url(fn ($record) => $record->invoice_id ? InvoiceResource::getUrl('edit', ['record' => $record->invoice_id]) : null)
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('run_registration_type')
                    ->label('Type d\'inscription')
                    ->options(RunRegistrationType::class),
                SelectFilter::make('school_class_level')
                    ->label('Degré scolaire')
                    ->options(SchoolClassLevel::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),

                    // Fast Client Association
                    Action::make('associateClient')
                        ->label('Associer à un client')
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            Select::make('client_id')
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
                    Action::make('createClient')
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

                    // Automatic / Update Invoice Generation
                    Action::make('generateInvoice')
                        ->label(fn (RunRegistration $record) => $record->invoice_id ? 'Mettre à jour la facture' : 'Générer la facture')
                        ->icon('heroicon-o-document-currency-dollar')
                        ->color('success')
                        ->action(function (RunRegistration $record) {
                            try {
                                app(RunRegistrationService::class)->createInvoice($record);
                                Notification::make()->title('Facture générée / mise à jour avec succès !')->success()->send();
                            } catch (Exception $e) {
                                Notification::make()->title('Erreur')->body($e->getMessage())->danger()->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->client_id !== null),

                    // Open Generated Invoice Direct Link
                    Action::make('openInvoice')
                        ->label('Ouvrir la facture')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('info')
                        ->url(fn (RunRegistration $record) => $record->invoice_id ? InvoiceResource::getUrl('edit', ['record' => $record->invoice_id]) : null)
                        ->openUrlInNewTab()
                        ->visible(fn (RunRegistration $record) => $record->invoice_id !== null),

                    // Imprimer Contrat Élite (PDF)
                    Action::make('printEliteContract')
                        ->label('Imprimer Contrat Élite (PDF)')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->url(fn (RunRegistration $record) => route('pdf.elite-contract', ['registration' => $record->id]))
                        ->openUrlInNewTab()
                        ->visible(fn (RunRegistration $record) => ($record->run_registration_type?->value ?? $record->run_registration_type) === 'elite'),

                    // Open Public Signed Form
                    Action::make('openPublicForm')
                        ->label('Ouvrir l\'interface publique')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn (RunRegistration $record) => URL::signedRoute('front.run-registration.edit', [
                            'registration' => $record->id,
                        ]))
                        ->openUrlInNewTab(),
                ]),
            ])
            ->headerActions([
                ActionGroup::make([
                    // Export Elite
                    Action::make('exportElite')
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
                                        'IBAN'                => $element->iban ?: $registration->payment_iban,
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

                            if ($data->isEmpty()) {
                                Notification::make()->title('Aucune donnée Élite à exporter.')->warning()->send();

                                return null;
                            }

                            return (new FastExcel($data))->download('export_elite_'.date('Ymd_His').'.xlsx');
                        }),

                    // Export Datasport Écoles
                    Action::make('exportDatasportSchool')
                        ->label('Export Datasport interclasses')
                        ->icon('heroicon-o-academic-cap')
                        ->color('info')
                        ->action(function () {
                            $registrations = RunRegistration::where('run_registration_type', 'school')
                                ->with('runRegistrationElements.run')
                                ->get();

                            return RunRegistrationResource::generateDatasportSchoolExcel($registrations);
                        }),

                    // Export Datasport Entreprises
                    Action::make('exportDatasportCompany')
                        ->label('Export Datasport entreprises')
                        ->icon('heroicon-o-building-office')
                        ->color('warning')
                        ->action(function () {
                            $registrations = RunRegistration::where('run_registration_type', 'company')
                                ->with('runRegistrationElements.run')
                                ->get();

                            return RunRegistrationResource::generateDatasportCompanyExcel($registrations);
                        }),

                    // Export Datasport Groupes
                    Action::make('exportDatasportGroup')
                        ->label('Export Datasport groupes')
                        ->icon('heroicon-o-user-group')
                        ->color('gray')
                        ->action(function () {
                            $registrations = RunRegistration::where('run_registration_type', 'group')
                                ->with('runRegistrationElements.run')
                                ->get();

                            return RunRegistrationResource::generateDatasportGroupExcel($registrations);
                        }),

                    // Export Aggregated Data with Invoicing & Accounting details
                    Action::make('exportAggregatedData')
                        ->label('Export récapitulatif comptable')
                        ->icon('heroicon-o-chart-bar')
                        ->color('success')
                        ->action(function () {
                            $registrations = RunRegistration::with(['runRegistrationElements.run.provision.product', 'client', 'invoice'])->get();

                            return RunRegistrationResource::generateAggregatedExcel($registrations);
                        }),

                    // Export Detailed Participants List
                    Action::make('exportDetailedParticipants')
                        ->label('Export participants détaillé')
                        ->icon('heroicon-o-users')
                        ->color('primary')
                        ->action(function () {
                            $registrations = RunRegistration::with(['runRegistrationElements.run.provision.product', 'client', 'invoice'])->get();

                            return RunRegistrationResource::generateDetailedParticipantsExcel($registrations);
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('generateInvoices')
                        ->label('Générer / Regrouper factures par client')
                        ->icon('heroicon-o-document-currency-dollar')
                        ->action(function (Collection $records) {
                            $clientIds = $records->pluck('client_id')->filter()->unique();

                            if ($clientIds->isEmpty()) {
                                Notification::make()
                                    ->title('Aucun client associé dans la sélection.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $successCount = 0;
                            $errorCount = 0;

                            foreach ($clientIds as $clientId) {
                                try {
                                    app(RunRegistrationService::class)->createInvoiceForClient($clientId);
                                    $successCount++;
                                } catch (Exception $e) {
                                    $errorCount++;
                                }
                            }

                            Notification::make()
                                ->title($successCount.' facture(s) consolidée(s) générée(s) pour '.$clientIds->count().' client(s)'.($errorCount > 0 ? ' ('.$errorCount.' erreurs)' : ''))
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                    BulkAction::make('exportDatasportSchool')
                        ->label('Export Datasport écoles (sélection)')
                        ->icon('heroicon-o-academic-cap')
                        ->action(function (Collection $records) {
                            $filtered = $records->filter(fn ($r) => ($r->run_registration_type?->value ?? $r->run_registration_type) === 'school');

                            return RunRegistrationResource::generateDatasportSchoolExcel($filtered);
                        }),
                    BulkAction::make('exportDatasportCompany')
                        ->label('Export Datasport entreprises (sélection)')
                        ->icon('heroicon-o-building-office')
                        ->action(function (Collection $records) {
                            $filtered = $records->filter(fn ($r) => ($r->run_registration_type?->value ?? $r->run_registration_type) === 'company');

                            return RunRegistrationResource::generateDatasportCompanyExcel($filtered);
                        }),
                    BulkAction::make('exportDatasportGroup')
                        ->label('Export Datasport groupes (sélection)')
                        ->icon('heroicon-o-user-group')
                        ->action(function (Collection $records) {
                            $filtered = $records->filter(fn ($r) => ($r->run_registration_type?->value ?? $r->run_registration_type) === 'group');

                            return RunRegistrationResource::generateDatasportGroupExcel($filtered);
                        }),
                    BulkAction::make('exportDetailedParticipants')
                        ->label('Export participants détaillé (sélection)')
                        ->icon('heroicon-o-users')
                        ->action(function (Collection $records) {
                            return RunRegistrationResource::generateDetailedParticipantsExcel($records);
                        }),
                    BulkAction::make('exportAggregatedData')
                        ->label('Export récapitulatif comptable (sélection)')
                        ->icon('heroicon-o-chart-bar')
                        ->action(function (Collection $records) {
                            return RunRegistrationResource::generateAggregatedExcel($records);
                        }),
                ]),
            ]);
    }

    public static function generateDatasportSchoolExcel($registrations)
    {
        $data = collect();

        foreach ($registrations as $registration) {
            foreach ($registration->runRegistrationElements as $element) {
                $gender = is_object($element->gender) ? $element->gender->value : ($element->gender ?? '');
                $birthdate = $element->birthdate ? $element->birthdate->format('d.m.Y') : '';
                $schoolEtClass = trim(($registration->school_name ?? '').' '.($registration->school_class_level ?? ''));
                $maitre = trim(($registration->school_class_holder_first_name ?? '').' '.($registration->school_class_holder_last_name ?? ''));

                $data->push([
                    'Nom'                            => $element->last_name,
                    'Prénom'                         => $element->first_name,
                    'Date de naissance (jj.mm.aaaa)' => $birthdate,
                    'Genre'                          => $gender,
                    'Nationalité'                    => $element->nationality ?: ($element->country ?: 'Switzerland'),
                    'E-mail'                         => $element->email ?: $registration->contact_email,
                    'Code postal'                    => $element->postal_code ?: ($registration->school_postal_code ?: $registration->invoicing_postal_code),
                    'Lieu'                           => $element->locality ?: ($registration->school_locality ?: $registration->invoicing_locality),
                    'Pays'                           => $element->country ?: ($registration->school_country ?: 'Switzerland'),
                    'Etablissement et classe'        => $schoolEtClass,
                    'Prénom du responsable '         => $registration->contact_first_name,
                    'Nom du responsable '            => $registration->contact_last_name,
                    'Maître'                         => $maitre,
                    'Etablissement'                  => $registration->school_name,
                    'Degré'                          => $registration->school_class_level,
                ]);
            }
        }

        if ($data->isEmpty()) {
            Notification::make()->title('Aucune inscription École à exporter.')->warning()->send();

            return null;
        }

        return (new FastExcel($data))->download('export_datasport_ecoles_'.date('Ymd_His').'.xlsx');
    }

    public static function generateDatasportCompanyExcel($registrations)
    {
        $data = collect();

        foreach ($registrations as $registration) {
            foreach ($registration->runRegistrationElements as $element) {
                $gender = is_object($element->gender) ? $element->gender->value : ($element->gender ?? '');
                if (strtolower($gender) === 'male' || strtolower($gender) === 'homme') {
                    $gender = 'M';
                } elseif (strtolower($gender) === 'female' || strtolower($gender) === 'femme') {
                    $gender = 'F';
                }

                $birthdate = $element->birthdate ? $element->birthdate->format('d.m.Y') : '';
                $companyName = $registration->company_name ?: ($element->team ?: '');
                $bloc = $element->bloc ?: ($element->run?->name ?? ($element->run_name ?? ''));

                $data->push([
                    'Nom'                            => $element->last_name,
                    'Prénom'                         => $element->first_name,
                    'Date de naissance (jj.mm.aaaa)' => $birthdate,
                    'Genre'                          => $gender,
                    'Nationalité'                    => $element->nationality ?: 'Switzerland',
                    'E-mail'                         => $element->email ?: $registration->contact_email,
                    'Nom de l\'entreprise'           => $companyName,
                    'Bloc de départ souhaité'        => $bloc,
                    'Vidéo'                          => $element->with_video ? 'oui' : 'non',
                ]);
            }
        }

        if ($data->isEmpty()) {
            Notification::make()->title('Aucune inscription entreprise à exporter.')->warning()->send();

            return null;
        }

        return (new FastExcel($data))->download('export_datasport_entreprises_'.date('Ymd_His').'.xlsx');
    }

    public static function generateDatasportGroupExcel($registrations)
    {
        $data = collect();

        foreach ($registrations as $registration) {
            foreach ($registration->runRegistrationElements as $element) {
                $gender = is_object($element->gender) ? $element->gender->value : ($element->gender ?? '');
                if (strtolower($gender) === 'male' || strtolower($gender) === 'homme') {
                    $gender = 'M';
                } elseif (strtolower($gender) === 'female' || strtolower($gender) === 'femme') {
                    $gender = 'F';
                }

                $birthdate = $element->birthdate ? $element->birthdate->format('d.m.Y') : '';
                $clubName = $element->team ?: ($registration->company_name ?: '');
                $runName = $element->run?->name ?? ($element->run_name ?? '');

                $data->push([
                    'Nom'                            => $element->last_name,
                    'Prénom'                         => $element->first_name,
                    'Date de naissance (jj.mm.aaaa)' => $birthdate,
                    'Genre'                          => $gender,
                    'Nationalité'                    => $element->nationality ?: 'Switzerland',
                    'E-mail'                         => $element->email ?: $registration->contact_email,
                    'Nom du club'                    => $clubName,
                    'Course'                         => $runName,
                    'Vidéo'                          => $element->with_video ? 'oui' : 'non',
                ]);
            }
        }

        if ($data->isEmpty()) {
            Notification::make()->title('Aucune inscription Groupe à exporter.')->warning()->send();

            return null;
        }

        return (new FastExcel($data))->download('export_datasport_groupes_'.date('Ymd_His').'.xlsx');
    }

    public static function generateAggregatedExcel($registrations)
    {
        $data = collect();

        foreach ($registrations as $reg) {
            $typeLabel = $reg->run_registration_type instanceof RunRegistrationType
                ? $reg->run_registration_type->getLabel()
                : (string) $reg->run_registration_type;

            $client = $reg->client;
            $invoice = $reg->invoice;

            $elements = $reg->runRegistrationElements;
            $totalParticipants = $elements->count();
            $freeCount = $elements->where('has_free_registration_fee', true)->count();
            $paidCount = $totalParticipants - $freeCount;

            $data->push([
                'ID dossier'                   => $reg->id,
                'Type'                         => $typeLabel,
                'Organisme / entreprise'       => $reg->company_name ?: ($reg->school_name ?: ($reg->contact_first_name.' '.$reg->contact_last_name)),
                'Degré'                        => $reg->school_class_level,
                'Personne de contact'          => trim($reg->contact_first_name.' '.$reg->contact_last_name),
                'Email de contact'             => $reg->contact_email,
                'Téléphone de contact'         => $reg->contact_phone,
                'Facturation - raison sociale' => $reg->invoicing_company_name,
                'Facturation - adresse'        => $reg->invoicing_address,
                'Facturation - complément'     => $reg->invoicing_address_extension,
                'Facturation - code postal'    => $reg->invoicing_postal_code ?: $reg->school_postal_code,
                'Facturation - localité'       => $reg->invoicing_locality ?: $reg->school_locality,
                'Facturation - email'          => $reg->invoicing_email,
                'IBAN de paiement'             => $reg->payment_iban,
                'Client partenaire lié'        => $client?->name ?? 'Non associé',
                'Quota inclus contrat (CDN)'   => $client ? (int) $client->cdn_vouchers_quota : 0,
                'Participants totaux'          => $totalParticipants,
                'Dossards inclus appliqués'    => $freeCount,
                'Dossards payants'             => $paidCount,
                'Nombre filles (F)'            => $reg->girls_count,
                'Nombre garçons (M)'           => $reg->boys_count,
                'Montant estimé (CHF)'         => $reg->estimated_total,
                'N° Facture'                   => $invoice?->number ?? 'Non facturé',
                'Statut facture'               => $invoice ? ($invoice->status?->value ?? $invoice->status) : 'Aucune',
                'Date création'                => $reg->created_at?->format('d.m.Y H:i'),
            ]);
        }

        if ($data->isEmpty()) {
            Notification::make()->title('Aucune donnée à exporter.')->warning()->send();

            return null;
        }

        return (new FastExcel($data))->download('export_inscriptions_recapitulatif_'.date('Ymd_His').'.xlsx');
    }

    public static function generateDetailedParticipantsExcel($registrations)
    {
        $data = collect();

        foreach ($registrations as $reg) {
            $typeLabel = $reg->run_registration_type instanceof RunRegistrationType
                ? $reg->run_registration_type->getLabel()
                : (string) $reg->run_registration_type;

            $client = $reg->client;
            $invoice = $reg->invoice;

            foreach ($reg->runRegistrationElements as $element) {
                $gender = is_object($element->gender) ? $element->gender->value : ($element->gender ?? '');
                if (strtolower($gender) === 'male' || strtolower($gender) === 'homme') {
                    $gender = 'M';
                } elseif (strtolower($gender) === 'female' || strtolower($gender) === 'femme') {
                    $gender = 'F';
                }

                $birthdate = $element->birthdate ? $element->birthdate->format('d.m.Y') : '';
                $runName = $element->run?->name ?? ($element->run_name ?? '');
                $cost = (float) ($element->run?->provision?->product?->price?->amount ?? ($element->run?->cost ?? 0));
                $effectiveCost = $element->has_free_registration_fee ? 0.00 : $cost;

                $data->push([
                    'ID dossier'             => $reg->id,
                    'Type inscription'       => $typeLabel,
                    'Organisme / entreprise' => $reg->company_name ?: ($reg->school_name ?: ($reg->contact_first_name.' '.$reg->contact_last_name)),
                    'Client partenaire lié'  => $client?->name ?? 'Non associé',
                    'Nom participant'        => $element->last_name,
                    'Prénom participant'     => $element->first_name,
                    'Date de naissance'      => $birthdate,
                    'Genre'                  => $gender,
                    'Nationalité'            => $element->nationality ?: 'SUI',
                    'Email participant'      => $element->email ?: $reg->contact_email,
                    'Course'                 => $runName,
                    'Bloc de départ'         => $element->bloc ?: '',
                    'Équipe / entreprise'    => $element->team ?: ($reg->company_name ?: ''),
                    'Frais offerts / inclus' => $element->has_free_registration_fee ? 'Oui' : 'Non',
                    'Prix unitaire (CHF)'    => $cost,
                    'Facturé effectif (CHF)' => $effectiveCost,
                    'Option vidéo'           => $element->with_video ? 'Oui' : 'Non',
                    'N° Facture'             => $invoice?->number ?? 'Non facturé',
                    'Statut facture'         => $invoice ? ($invoice->status?->value ?? $invoice->status) : 'Aucune',
                ]);
            }
        }

        if ($data->isEmpty()) {
            Notification::make()->title('Aucun participant à exporter.')->warning()->send();

            return null;
        }

        return (new FastExcel($data))->download('export_participants_detail_'.date('Ymd_His').'.xlsx');
    }

    public static function getRelations(): array
    {
        return [
            RunRegistrationElementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRunRegistrations::route('/'),
            'create' => CreateRunRegistration::route('/create'),
            'edit'   => EditRunRegistration::route('/{record}/edit'),
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
