<?php

namespace App\Filament\Resources;

use Exception;
use App\Models\Run;
use App\Models\Client;
use App\Models\Voucher;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use App\Notifications\ClientSendVouchers;
use Illuminate\Support\Collection;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $pluralModelLabel = 'Vouchers / Dossards offerts';

    protected static ?string $modelLabel = 'Voucher';

    protected static string | \UnitEnum | null $navigationGroup = 'Courses';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Code Voucher')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Select::make('client_id')
                    ->label('Client / Entreprise attribué')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                Select::make('run_id')
                    ->label('Course restreinte (optionnel)')
                    ->relationship('run', 'name')
                    ->searchable()
                    ->preload(),

                Toggle::make('is_used')
                    ->label('Marqué comme utilisé ?')
                    ->default(false),

                Textarea::make('note')
                    ->label('Note / Remarque')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.name')
                    ->label('Client / Entreprise')
                    ->placeholder('Non attribué')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('run.name')
                    ->label('Course restreinte')
                    ->placeholder('Toutes les courses')
                    ->sortable(),

                IconColumn::make('is_used')
                    ->label('Utilisé')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->sortable(),

                TextColumn::make('used_at')
                    ->label('Utilisé le')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Non utilisé')
                    ->sortable(),

                TextColumn::make('usedByElement')
                    ->label('Utilisé par')
                    ->getStateUsing(fn ($record) => $record->usedByElement ? ($record->usedByElement->first_name . ' ' . $record->usedByElement->last_name) : null)
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Importé le')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_used')
                    ->label('Statut d\'utilisation')
                    ->trueLabel('Uniquement utilisés')
                    ->falseLabel('Uniquement disponibles'),

                SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->searchable(),

                SelectFilter::make('run_id')
                    ->label('Course')
                    ->relationship('run', 'name')
                    ->searchable(),
            ])
            ->headerActions([
                // Importateur de Vouchers Datasport par lot
                Action::make('importVouchers')
                    ->label('Importer vouchers (Datasport)')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->schema([
                        Select::make('client_id')
                            ->label('Attribuer au client / entreprise')
                            ->options(fn () => Client::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),

                        Select::make('run_id')
                            ->label('Restreindre à une course spécifique (optionnel)')
                            ->options(fn () => Run::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload(),

                        Textarea::make('codes_text')
                            ->label('Liste des codes vouchers (Datasport)')
                            ->helperText('Collez les codes un par ligne ou séparés par des virgules.')
                            ->rows(8)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $rawText = $data['codes_text'] ?? '';
                        // Split by newlines, commas, spaces, or semicolons
                        $rawCodes = preg_split('/[\r\n,;\s]+/', $rawText);
                        $imported = 0;
                        $skipped = 0;

                        foreach ($rawCodes as $code) {
                            $cleanCode = trim($code);
                            if (empty($cleanCode)) {
                                continue;
                            }

                            if (Voucher::where('code', $cleanCode)->exists()) {
                                $skipped++;
                                continue;
                            }

                            Voucher::create([
                                'code'       => $cleanCode,
                                'client_id'  => $data['client_id'] ?? null,
                                'run_id'     => $data['run_id'] ?? null,
                                'edition_id' => \App\Helpers\AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id'),
                                'is_used'    => false,
                            ]);

                            $imported++;
                        }

                        Notification::make()
                            ->title("Importation terminée : {$imported} code(s) créé(s)" . ($skipped > 0 ? " ({$skipped} existants ignorés)" : ""))
                            ->success()
                            ->send();
                    }),

                // Envoi des Vouchers par Email au client
                Action::make('sendVouchersEmail')
                    ->label('Envoyer vouchers par email')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->schema([
                        Select::make('client_id')
                            ->label('Sélectionner le client destinataire')
                            ->options(fn () => Client::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),

                        Textarea::make('custom_message')
                            ->label('Message personnalisé (optionnel)')
                            ->placeholder('Ex: Voici vos codes vouchers pour la Course de Noël 2026...'),
                    ])
                    ->action(function (array $data) {
                        $client = Client::find($data['client_id']);
                        if (! $client) {
                            Notification::make()->title('Client introuvable.')->danger()->send();
                            return;
                        }

                        $vouchers = Voucher::where('client_id', $client->id)->get();
                        if ($vouchers->isEmpty()) {
                            Notification::make()->title('Aucun voucher attribué à ce client.')->warning()->send();
                            return;
                        }

                        try {
                            $client->notify(new ClientSendVouchers($vouchers, $data['custom_message'] ?? null));
                            Notification::make()
                                ->title("Email de vouchers envoyé avec succès à {$client->name} !")
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()->title('Erreur lors de l\'envoi')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
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
            'index' => VoucherResource\Pages\ListVouchers::route('/'),
        ];
    }
}
