<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\Edition;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\EditionResource\Pages\EditEdition;
use App\Filament\Resources\EditionResource\Pages\ListEditions;
use App\Filament\Resources\EditionResource\Pages\CreateEdition;

class EditionResource extends Resource
{
    protected static ?string $model = Edition::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $pluralModelLabel = 'Éditions';

    protected static ?string $modelLabel = 'Édition';

    protected static string|\UnitEnum|null $navigationGroup = 'Collections';

    protected static function metaInput(string $key, string $label, ?string $helper = null): TextInput
    {
        $input = TextInput::make("metadata.{$key}")
            ->label($label)
            ->placeholder(fn (?Edition $record): string => Edition::defaultMeta(
                $key,
                year: is_numeric($record?->year) ? (int) $record->year : null
            ));

        return $helper ? $input->helperText($helper) : $input;
    }

    protected static function metaTextarea(string $key, string $label, int $rows = 4): Textarea
    {
        return Textarea::make("metadata.{$key}")
            ->label($label)
            ->rows($rows)
            ->placeholder(fn (?Edition $record): string => Edition::defaultMeta(
                $key,
                year: is_numeric($record?->year) ? (int) $record->year : null
            ));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informations générales')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required(),
                        TextInput::make('year')
                            ->label('Année')
                            ->required(),
                    ]),
                Section::make('Événement (e-mails généraux)')
                    ->description('Valeurs utilisées dans les e-mails annonceurs, donateurs, interclasses et VIP.')
                    ->columns(2)
                    ->schema([
                        self::metaInput('event_date', 'Date complète de l\'événement', 'Ex. : 12 décembre 2026'),
                        self::metaInput('event_short_date', 'Date courte (invitation VIP)', 'Ex. : 12 décembre'),
                        self::metaInput('cdn_edition_number', 'Numéro d\'édition Course de Noël', 'Ex. : 57ᵉ'),
                        self::metaInput('trail_edition_number', 'Numéro d\'édition Trail des Châteaux', 'Ex. : 8ᵉ'),
                        self::metaInput('expected_participants', 'Nombre de participant·e·s annoncé', 'Ex. : 6000'),
                    ]),
                Section::make('Annonceurs et médias')
                    ->columns(2)
                    ->schema([
                        self::metaInput('advertisers_email', 'E-mail annonceurs / médias'),
                        self::metaInput('advertisers_signature', 'Signature (médias manquants)'),
                        self::metaInput('advertisers_paper_form_url', 'Lien du formulaire papier (PDF)')->columnSpanFull(),
                    ]),
                Section::make('Concours interclasses')
                    ->columns(2)
                    ->schema([
                        self::metaInput('interclass_edition_number', 'Numéro d\'édition interclasses', 'Ex. : 26e'),
                        self::metaInput('interclass_participants', 'Nombre d\'élèves annoncé', 'Ex. : 1\'200'),
                        self::metaInput('interclass_deadline', 'Délai premier envoi'),
                        self::metaInput('interclass_relaunch_deadline', 'Délai de relance'),
                        self::metaTextarea('interclass_contact_address', 'Adresse d\'envoi des lots physiques', 5),
                        self::metaTextarea('interclass_signature', 'Signature du responsable', 3),
                    ]),
                Section::make('VIP')
                    ->columns(2)
                    ->schema([
                        self::metaInput('vip_email', 'E-mail VIP / info'),
                        self::metaInput('vip_start_time', 'Heure de début (apéritif)', 'Ex. : 16h30'),
                        self::metaInput('vip_deadline', 'Délai de réponse VIP'),
                        self::metaInput('vip_invitation_pdf_url', 'Lien PDF invitation et programme')->columnSpanFull(),
                        self::metaTextarea('vip_signature', 'Signature VIP', 4)->columnSpanFull(),
                    ]),
                Section::make('Vouchers et Challenge entreprises')
                    ->columns(2)
                    ->schema([
                        self::metaInput('vouchers_deadline', 'Délai d\'utilisation des vouchers'),
                        self::metaInput('vouchers_tshirt_deadline', 'Délai commande T-shirts Texner'),
                        self::metaInput('vouchers_company_form_url', 'Lien formulaire groupe (≥ 20)')->columnSpanFull(),
                        self::metaInput('vouchers_datasport_url', 'Lien inscription Datasport')->columnSpanFull(),
                        self::metaInput('vouchers_info_url', 'Lien infos Challenge entreprises')->columnSpanFull(),
                        self::metaInput('vouchers_tshirt_url', 'Lien commande T-shirts Texner')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom'),
                TextColumn::make('year')
                    ->label('Année'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
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
            'index'  => ListEditions::route('/'),
            'create' => CreateEdition::route('/create'),
            'edit'   => EditEdition::route('/{record}/edit'),
        ];
    }
}
