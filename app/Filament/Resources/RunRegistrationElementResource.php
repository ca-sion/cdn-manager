<?php

namespace App\Filament\Resources;

use App\Helpers\CountryHelper;
use App\Models\Run;
use App\Models\RunRegistrationElement;
use App\Notifications\ClientSendVouchers;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use Rap2hpoutre\FastExcel\FastExcel;

class RunRegistrationElementResource extends Resource
{
    protected static ?string $model = RunRegistrationElement::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $pluralModelLabel = 'Tous les Coureurs / Élite';

    protected static ?string $modelLabel = 'Coureur';

    protected static string | \UnitEnum | null $navigationGroup = 'Courses';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('RunnerDetails')
                    ->tabs([
                        Tab::make('Identité du coureur')
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('Prénom')
                                    ->required(),

                                TextInput::make('last_name')
                                    ->label('Nom')
                                    ->required(),

                                DatePicker::make('birthdate')
                                    ->label('Date de naissance')
                                    ->displayFormat('d.m.Y')
                                    ->required(),

                                Select::make('gender')
                                    ->label('Genre')
                                    ->options([
                                        'M' => 'Homme (M)',
                                        'F' => 'Femme (F)',
                                    ]),

                                Select::make('nationality')
                                    ->label('Nationalité')
                                    ->options(CountryHelper::getOptions())
                                    ->default('SUI'),

                                TextInput::make('email')
                                    ->label('Email')
                                    ->email(),

                                TextInput::make('team')
                                    ->label('Équipe / Club'),
                            ])->columns(2),

                        Tab::make('Course')
                            ->schema([
                                Select::make('run_id')
                                    ->label('Course inscrite')
                                    ->relationship('run', 'name')
                                    ->searchable()
                                    ->preload(),
                                TextInput::make('bloc')
                                    ->label('Bloc de départ'),
                                Checkbox::make('with_video')
                                    ->label('Vidéo'),
                                TextInput::make('voucher_code')
                                    ->label('Code / Voucher'),
                            ])->columns(2),
                        
                        Tab::make('Coordonnées')
                            ->schema([
                        TextInput::make('address')
                            ->label('Adresse')
                            ->columnSpan(4),

                        TextInput::make('address_extension')
                            ->label('Complément d\'adresse')
                            ->columnSpan(3),

                        TextInput::make('postal_code')
                            ->label('Code postal')
                            ->columnSpan(2),

                        TextInput::make('locality')
                            ->label('Localité')
                            ->columnSpan(3),

                        Select::make('country')
                            ->label('Pays')
                            ->options(CountryHelper::getOptions())
                            ->searchable()
                            ->default('SUI')
                            ->columnSpan(3),
                        
                        TextInput::make('iban')
                            ->label('IBAN')
                            ->hint('Pour le versement des primes')
                            ->columnSpan(9),
                        ])->columns(12),

                        Tab::make('Conditions et contrat')
                    ->icon('heroicon-m-document-text')
                    ->columns(2)
                    ->schema([

                        Toggle::make('has_free_registration_fee')
                            ->label('Dossard offert')
                            ->columnSpanFull()
                            ->dehydrated(),

                        Toggle::make('has_bonus_start')
                            ->label('Prime de départ accordée')
                            ->live()
                            ->dehydrated(),

                        TextInput::make('bonus_start_amount')
                            ->label('Montant prime de départ')
                            ->numeric()
                            ->suffix('CHF')
                            ->visible(fn (Get $get) => $get('has_bonus_start'))
                            ->dehydrated(),

                        Toggle::make('has_expense_reimbursement')
                            ->label('Remboursement des frais de déplacement')
                            ->live()
                            ->dehydrated()
                            ->columnSpanFull(),

                        Textarea::make('expense_reimbursement_precision')
                            ->label('Précisions remboursement de frais')
                                ->hint('Transports, billets, etc.')
                            ->visible(fn (Get $get) => $get('has_expense_reimbursement'))
                            ->dehydrated()
                            ->columnSpanFull(),
                    ]),

                Tab::make('Primes')
                    ->icon('heroicon-m-currency-dollar')
                    ->columns(2)
                    ->schema([

                        TextInput::make('bonus_ranking_amount')
                            ->label('Montant prime de classement')
                            ->live()
                            ->numeric()
                            ->suffix('CHF')
                            ->dehydrated(),

                        TextInput::make('bonus_arrival_amount')
                            ->label('Montant prime d\'arrivée')
                            ->numeric()
                            ->suffix('CHF')
                            ->dehydrated(),
                    ]),

                    Tab::make('Hébergement')
                        ->icon('heroicon-m-building-office')
                        ->columns(2)
                        ->schema([

                            Toggle::make('has_accommodation')
                                ->label('Prise en charge de l\'hébergement')
                                ->live()
                                ->columnSpanFull()
                                ->dehydrated(),

                            Toggle::make('accommodation_friday')
                                ->label('Nuitée du vendredi')
                                ->visible(fn (Get $get) => $get('has_accommodation'))
                                ->dehydrated(),

                            Toggle::make('accommodation_saturday')
                                ->label('Nuitée du samedi')
                                ->visible(fn (Get $get) => $get('has_accommodation'))
                                ->dehydrated(),

                            Textarea::make('accommodation_precision')
                                ->label('Précisions hébergement')
                                ->hint('Hôtel, type de chambre et nombre de place.')
                                ->visible(fn (Get $get) => $get('has_accommodation'))
                                ->dehydrated()
                                ->columnSpanFull(),
                        ]),
                        
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

                TextColumn::make('full_name')
                    ->label('Nom & Prénom')
                    ->getStateUsing(fn ($record) => $record->first_name . ' ' . $record->last_name)
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->sortable(),

                TextColumn::make('runRegistration.run_registration_type')
                    ->label('Type Dossier')
                    ->badge()
                    ->sortable(),

                TextColumn::make('run.name')
                    ->label('Course')
                    ->placeholder('Non attribuée')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('birthdate')
                    ->label('Date Naissance')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('gender')
                    ->label('Genre')
                    ->sortable(),

                TextColumn::make('nationality')
                    ->label('Nationalité')
                    ->searchable(),

                TextColumn::make('bonus_start_amount')
                    ->label('Prime Départ')
                    ->money('CHF')
                    ->placeholder('-')
                    ->sortable(),

                IconColumn::make('has_accommodation')
                    ->label('Hébergement')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('run_id')
                    ->label('Filtrer par Course')
                    ->relationship('run', 'name')
                    ->searchable(),

                SelectFilter::make('type')
                    ->label('Type d\'inscription')
                    ->options([
                        'elite'   => 'Uniquement Élite',
                        'company' => 'Entreprises',
                        'school'  => 'Écoles',
                        'group'   => 'Groupes',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $query->whereHas('runRegistration', fn ($q) => $q->where('run_registration_type', $data['value']));
                        }
                    }),
            ])
            ->headerActions([
                Action::make('exportEliteExcel')
                    ->label('Exporter coureurs Élite (Excel)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->action(function () {
                        $elements = RunRegistrationElement::whereHas('runRegistration', fn ($q) => $q->where('run_registration_type', 'elite'))->get();

                        if ($elements->isEmpty()) {
                            Notification::make()->title('Aucun coureur Élite à exporter.')->warning()->send();
                            return null;
                        }

                        $data = $elements->map(fn ($el) => [
                            'ID Coureur'          => $el->id,
                            'ID Dossier'          => $el->run_registration_id,
                            'Nom'                 => $el->last_name,
                            'Prénom'              => $el->first_name,
                            'Date Naissance'      => $el->birthdate?->format('d.m.Y'),
                            'Genre'               => is_object($el->gender) ? $el->gender->value : $el->gender,
                            'Nationalité'         => $el->nationality,
                            'Email'               => $el->email,
                            'Course'              => $el->run?->name ?? $el->run_name,
                            'Bloc'                => $el->bloc,
                            'IBAN'                => $el->iban,
                            'Prime départ (CHF)'  => $el->bonus_start_amount,
                            'Prime classement'    => $el->bonus_ranking_amount,
                            'Hébergement'         => $el->has_accommodation ? 'Oui' : 'Non',
                            'Précisions héb.'     => $el->accommodation_precision,
                            'Defraiement frais'   => $el->has_expense_reimbursement ? 'Oui' : 'Non',
                        ]);

                        return (new FastExcel($data))->download('export_coureurs_elite_' . date('Ymd_His') . '.xlsx');
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),

                    // Imprimer Contrat Élite PDF (URL Signée)
                    Action::make('printEliteContract')
                        ->label('Imprimer Contrat Élite (PDF)')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->url(fn (RunRegistrationElement $record) => URL::signedRoute('pdf.elite-contract', ['registration' => $record->run_registration_id]))
                        ->openUrlInNewTab()
                        ->visible(fn (RunRegistrationElement $record) => ($record->runRegistration?->run_registration_type?->value ?? $record->runRegistration?->run_registration_type) === 'elite'),

                    // Step 1: Envoyer invitation à compléter la fiche coureur Élite
                    Action::make('sendEliteEditLink')
                        ->label('1. Envoyer invitation (fiche)')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (RunRegistrationElement $record) {
                            if (! $record->email && ! $record->runRegistration?->contact_email) {
                                Notification::make()->title('Aucune adresse email renseignée pour ce coureur.')->warning()->send();
                                return;
                            }

                            try {
                                $targetEmail = $record->email ?: $record->runRegistration?->contact_email;
                                $record->runRegistration->notify(new \App\Notifications\EliteRunnerFormLink($record));

                                Notification::make()->title("Invitation envoyée avec succès à {$targetEmail} !")->success()->send();
                            } catch (Exception $e) {
                                Notification::make()->title('Erreur lors de l\'envoi')->body($e->getMessage())->danger()->send();
                            }
                        })
                        ->visible(fn (RunRegistrationElement $record) => ($record->runRegistration?->run_registration_type?->value ?? $record->runRegistration?->run_registration_type) === 'elite'),

                    // Step 2: Envoyer confirmation de contrat Élite finalisé
                    Action::make('sendEliteContractFinalized')
                        ->label('2. Envoyer contrat')
                        ->icon('heroicon-o-check-badge')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (RunRegistrationElement $record) {
                            if (! $record->email && ! $record->runRegistration?->contact_email) {
                                Notification::make()->title('Aucune adresse email renseignée pour ce coureur.')->warning()->send();
                                return;
                            }

                            try {
                                $targetEmail = $record->email ?: $record->runRegistration?->contact_email;
                                $record->runRegistration->notify(new \App\Notifications\EliteRunnerContractFinalized($record));

                                Notification::make()->title("Contrat envoyé avec succès à {$targetEmail} !")->success()->send();
                            } catch (Exception $e) {
                                Notification::make()->title('Erreur lors de l\'envoi')->body($e->getMessage())->danger()->send();
                            }
                        })
                        ->visible(fn (RunRegistrationElement $record) => ($record->runRegistration?->run_registration_type?->value ?? $record->runRegistration?->run_registration_type) === 'elite'),

                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('bulkAssignRun')
                        ->label('⚡ Attribuer la course en masse')
                        ->icon('heroicon-o-flag')
                        ->schema([
                            Select::make('run_id')
                                ->label('Sélectionner la course')
                                ->options(\App\Models\Run::all()->pluck('name', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $run = \App\Models\Run::find($data['run_id']);
                            if (! $run) {
                                return;
                            }

                            foreach ($records as $record) {
                                $record->run_id = $run->id;
                                $record->run_name = $run->name;
                                $record->save();
                            }

                            Notification::make()
                                ->title($records->count() . ' participant(s) associé(s) à la course ' . $run->name)
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => RunRegistrationElementResource\Pages\ListRunRegistrationElements::route('/'),
        ];
    }
}
