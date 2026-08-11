<?php

namespace App\Filament\Resources;

use Exception;
use App\Models\Run;
use App\Models\RunRegistrationElement;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use App\Notifications\ClientSendVouchersNotification;

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
                        Tab::make('Identité & Course')
                            ->schema([
                                TextInput::make('first_name')
                                    ->label('Prénom')
                                    ->required(),

                                TextInput::make('last_name')
                                    ->label('Nom')
                                    ->required(),

                                DatePicker::make('birthdate')
                                    ->label('Date de naissance')
                                    ->displayFormat('d.m.Y'),

                                Select::make('gender')
                                    ->label('Genre')
                                    ->options([
                                        'M' => 'Homme (M)',
                                        'F' => 'Femme (F)',
                                    ]),

                                TextInput::make('nationality')
                                    ->label('Nationalité')
                                    ->default('Switzerland'),

                                TextInput::make('email')
                                    ->label('Email')
                                    ->email(),

                                Select::make('run_id')
                                    ->label('Course inscrite')
                                    ->relationship('run', 'name')
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('bloc')
                                    ->label('Bloc de départ'),

                                TextInput::make('team')
                                    ->label('Équipe / Entreprise / Titulaire'),
                            ])->columns(2),

                        Tab::make('Conditions Élite & Primes')
                            ->schema([
                                TextInput::make('iban')
                                    ->label('IBAN de versement'),

                                Toggle::make('has_free_registration_fee')
                                    ->label('Frais d\'inscription offerts / Voucher ?'),

                                Toggle::make('has_bonus_start')
                                    ->label('Prime de départ octroyée ?'),

                                TextInput::make('bonus_start_amount')
                                    ->label('Montant prime de départ (CHF)')
                                    ->numeric(),

                                TextInput::make('bonus_ranking_amount')
                                    ->label('Montant prime de classement (CHF)')
                                    ->numeric(),

                                TextInput::make('bonus_arrival_amount')
                                    ->label('Montant prime d\'arrivée (CHF)')
                                    ->numeric(),
                            ])->columns(2),

                        Tab::make('Hébergement & Frais')
                            ->schema([
                                Toggle::make('has_accommodation')
                                    ->label('Hébergement pris en charge ?'),

                                Toggle::make('accommodation_friday')
                                    ->label('Nuit du vendredi ?'),

                                Toggle::make('accommodation_saturday')
                                    ->label('Nuit du samedi ?'),

                                Textarea::make('accommodation_precision')
                                    ->label('Précisions hébergement (Hôtel, chambre...)'),

                                Toggle::make('has_expense_reimbursement')
                                    ->label('Remboursement de frais ?'),

                                Textarea::make('expense_reimbursement_precision')
                                    ->label('Précisions remboursement (Transports, billets...)'),
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

                    // Imprimer Contrat Élite PDF
                    Action::make('printEliteContract')
                        ->label('Imprimer Contrat Élite (PDF)')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->url(fn (RunRegistrationElement $record) => route('pdf.elite-contract', ['registration' => $record->run_registration_id]))
                        ->openUrlInNewTab()
                        ->visible(fn (RunRegistrationElement $record) => ($record->runRegistration?->run_registration_type?->value ?? $record->runRegistration?->run_registration_type) === 'elite'),

                    // Envoyer lien d'édition au coureur Élite par email
                    Action::make('sendEliteEditLink')
                        ->label('Envoyer lien édition au coureur Élite')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (RunRegistrationElement $record) {
                            if (! $record->email && ! $record->runRegistration?->contact_email) {
                                Notification::make()->title('Aucune adresse email renseignée pour ce coureur.')->warning()->send();
                                return;
                            }

                            $signedUrl = URL::signedRoute('front.run-registration.edit', [
                                'registration' => $record->run_registration_id,
                            ]);

                            try {
                                $record->runRegistration->notify(new ClientSendVouchersNotification(
                                    collect(),
                                    "Bonjour {$record->first_name} {$record->last_name},\nVoici votre lien d'accès sécurisé pour compléter votre fiche et vos conditions Élite :\n" . $signedUrl
                                ));

                                Notification::make()->title('Email envoyé au coureur Élite avec succès !')->success()->send();
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
