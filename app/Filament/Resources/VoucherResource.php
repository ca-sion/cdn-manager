<?php

namespace App\Filament\Resources;

use App\Models\Run;
use App\Models\Client;
use App\Models\Voucher;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Radio;
use Rap2hpoutre\FastExcel\FastExcel;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Collection;
use Filament\Schemas\Components\Utilities\Get;
use App\Filament\Actions\SendVouchersBulkAction;
use App\Filament\Actions\Support\VoucherMailing;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $pluralModelLabel = 'Vouchers et dossards offerts';

    protected static ?string $modelLabel = 'Voucher';

    protected static string|\UnitEnum|null $navigationGroup = 'Courses';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Code voucher')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Select::make('client_id')
                    ->label('Client ou entreprise attribué')
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
                    ->label('Note ou remarque')
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
                    ->label('Client ou entreprise')
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
                    ->dateTime()
                    ->placeholder('Non utilisé')
                    ->sortable(),

                TextColumn::make('usedByElement')
                    ->label('Utilisé par')
                    ->getStateUsing(fn ($record) => $record->usedByElement ? ($record->usedByElement->first_name.' '.$record->usedByElement->last_name) : null)
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Importé le')
                    ->dateTime()
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
                    ->multiple()
                    ->preload()
                    ->searchable(),

                SelectFilter::make('run_id')
                    ->label('Course')
                    ->relationship('run', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ])
            ->headerActions([
                Action::make('sendVouchers')
                    ->label('Envoyer les vouchers')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->modalHeading('Envoyer les vouchers par email')
                    ->modalDescription('Vérifiez la liste : seuls les clients cochés recevront leurs codes par email. Rien n\'est envoyé avant de valider.')
                    ->modalSubmitActionLabel('Envoyer aux clients cochés')
                    ->schema(fn (): array => VoucherMailing::previewSchema(VoucherMailing::eligibleClients()))
                    ->action(fn (array $data) => VoucherMailing::sendBulk(VoucherMailing::eligibleClients(), $data)),

                ActionGroup::make([
                    Action::make('importAndDispatchVouchers')
                        ->label('Importer / distribuer des codes')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->modalHeading('Importer et distribuer des codes vouchers')
                        ->modalSubmitActionLabel('Importer et distribuer')
                        ->schema([
                            Radio::make('mode')
                                ->label('Mode d\'importation')
                                ->options([
                                    'auto'   => 'Distribution automatique selon les quotas',
                                    'manual' => 'Import manuel (client spécifique ou stock libre)',
                                ])
                                ->default('auto')
                                ->live()
                                ->required(),

                            Placeholder::make('needs_summary')
                                ->label('État actuel des besoins par client')
                                ->content(fn (): HtmlString => new HtmlString(self::buildNeedsSummaryHtml()))
                                ->visible(fn (Get $get): bool => $get('mode') !== 'manual'),

                            Select::make('client_id')
                                ->label('Attribuer au client (laisser vide pour stock libre)')
                                ->options(fn (): array => self::clientOptionsWithQuotas())
                                ->searchable()
                                ->preload()
                                ->visible(fn (Get $get): bool => $get('mode') === 'manual'),

                            Select::make('run_id')
                                ->label('Restreindre à une course spécifique (optionnel)')
                                ->options(fn () => Run::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->preload(),

                            Textarea::make('codes_text')
                                ->label('Liste des codes vouchers (Datasport)')
                                ->helperText('Collez les codes un par ligne ou séparés par des virgules/espaces.')
                                ->rows(7)
                                ->required(),
                        ])
                        ->action(function (array $data) {
                            if (($data['mode'] ?? 'auto') === 'manual') {
                                self::handleManualImport($data);

                                return null;
                            }

                            return self::handleAutoDispatch($data);
                        }),

                    Action::make('exportVouchersOverview')
                        ->label('Exporter la synthèse (Excel)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn () => self::exportOverview()),
                ])
                    ->label('Importer et outils')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('gray')
                    ->button(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    SendVouchersBulkAction::make()
                        ->clientUsing(fn (Voucher $record) => $record->client),

                    BulkAction::make('assignClient')
                        ->label('Attribuer à un client')
                        ->icon('heroicon-o-user-plus')
                        ->schema([
                            Select::make('client_id')
                                ->label('Client destinataire')
                                ->options(fn (): array => self::clientOptionsWithQuotas())
                                ->searchable()
                                ->required(),
                        ])
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, array $data) {
                            $client = Client::find($data['client_id']);
                            if (! $client) {
                                Notification::make()->title('Client introuvable.')->danger()->send();

                                return;
                            }

                            Voucher::whereIn('id', $records->pluck('id'))->update(['client_id' => $client->id]);

                            Notification::make()
                                ->title("{$records->count()} voucher(s) attribué(s) à {$client->name}.")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    protected static function clientOptionsWithQuotas(): array
    {
        return Client::orderBy('name')->get()->mapWithKeys(function (Client $c) {
            $quota = $c->cdn_vouchers_quota;
            $assigned = $c->assigned_vouchers_count;
            $missing = $c->missing_vouchers_count;
            $trail = $c->trail_vouchers_quota;

            $label = $c->name;
            if ($quota > 0) {
                $label .= " (droit: {$quota} | reçus: {$assigned}/{$quota} | manque: {$missing})";
            } else {
                $label .= " ({$assigned} codes attribués)";
            }

            if ($trail > 0) {
                $label .= " [Trail: {$trail}]";
            }

            return [$c->id => $label];
        })->all();
    }

    protected static function buildNeedsSummaryHtml(): string
    {
        $clients = Client::orderBy('name')->get()->filter(fn ($c) => $c->cdn_vouchers_quota > 0 || $c->trail_vouchers_quota > 0);
        $totalNeeded = $clients->sum('cdn_vouchers_quota');
        $totalAssigned = $clients->sum('assigned_vouchers_count');
        $totalMissing = $clients->sum('missing_vouchers_count');
        $totalTrail = $clients->sum('trail_vouchers_quota');

        $rowsHtml = '';
        foreach ($clients as $client) {
            $missing = $client->missing_vouchers_count;
            $statusBadge = $missing === 0
                ? '<span style="color: #16a34a; font-weight: bold;">✓ Complet</span>'
                : "<span style=\"color: #ea580c; font-weight: bold;\">Manque {$missing}</span>";

            $rowsHtml .= "<tr style=\"border-bottom: 1px solid #e2e8f0;\">
                <td style=\"padding: 6px 10px;\">{$client->name}</td>
                <td style=\"padding: 6px 10px; text-align: center;\"><strong>{$client->cdn_vouchers_quota}</strong></td>
                <td style=\"padding: 6px 10px; text-align: center;\">{$client->assigned_vouchers_count}</td>
                <td style=\"padding: 6px 10px; text-align: center;\">{$statusBadge}</td>
                <td style=\"padding: 6px 10px; text-align: center; color: #047857;\">{$client->trail_vouchers_quota}</td>
            </tr>";
        }

        return '
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 14px;">
            <div style="display: flex; justify-content: space-around; text-align: center; margin-bottom: 12px;">
                <div>
                    <div style="font-size: 11px; color: #64748b; text-transform: uppercase;">Clients éligibles</div>
                    <div style="font-size: 18px; font-weight: bold; color: #0f172a;">'.$clients->count().'</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #64748b; text-transform: uppercase;">Total requis (CDN)</div>
                    <div style="font-size: 18px; font-weight: bold; color: #0284c7;">'.$totalNeeded.'</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #64748b; text-transform: uppercase;">Déjà attribués</div>
                    <div style="font-size: 18px; font-weight: bold; color: #16a34a;">'.$totalAssigned.'</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #64748b; text-transform: uppercase;">À fournir (manquants)</div>
                    <div style="font-size: 18px; font-weight: bold; color: #ea580c;">'.$totalMissing.'</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: #64748b; text-transform: uppercase;">Places Trail</div>
                    <div style="font-size: 18px; font-weight: bold; color: #047857;">'.$totalTrail.'</div>
                </div>
            </div>
            <div style="max-height: 180px; overflow-y: auto; font-size: 13px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background: #e2e8f0; color: #334155;">
                            <th style="padding: 6px 10px;">Client</th>
                            <th style="padding: 6px 10px; text-align: center;">Droit CDN</th>
                            <th style="padding: 6px 10px; text-align: center;">Attribués</th>
                            <th style="padding: 6px 10px; text-align: center;">Statut CDN</th>
                            <th style="padding: 6px 10px; text-align: center;">Droit Trail</th>
                        </tr>
                    </thead>
                    <tbody>
                        '.$rowsHtml.'
                    </tbody>
                </table>
            </div>
        </div>';
    }

    protected static function handleAutoDispatch(array $data): mixed
    {
        $rawCodes = preg_split('/[\r\n,;\s]+/', $data['codes_text'] ?? '');
        $availableCodes = [];

        foreach ($rawCodes as $code) {
            $clean = trim($code);
            if ($clean !== '' && ! Voucher::where('code', $clean)->exists()) {
                $availableCodes[] = $clean;
            }
        }

        if ($availableCodes === []) {
            Notification::make()
                ->title('Aucun nouveau code valide trouvé à importer.')
                ->warning()
                ->send();

            return null;
        }

        $editionId = VoucherMailing::editionId();
        $clientsNeeding = Client::orderBy('name')->get()->filter(fn ($c) => $c->missing_vouchers_count > 0);

        $codeIndex = 0;
        $totalAvailable = count($availableCodes);
        $dispatchedCount = 0;
        $clientsServedCount = 0;
        $exportRows = [];

        foreach ($clientsNeeding as $client) {
            $missing = $client->missing_vouchers_count;
            $assignedToClient = 0;

            while ($assignedToClient < $missing && $codeIndex < $totalAvailable) {
                $currentCode = $availableCodes[$codeIndex];

                Voucher::create([
                    'code'       => $currentCode,
                    'client_id'  => $client->id,
                    'run_id'     => $data['run_id'] ?? null,
                    'edition_id' => $editionId,
                    'is_used'    => false,
                ]);

                $exportRows[] = [
                    'Client ou entreprise' => $client->name,
                    'Code voucher'         => $currentCode,
                    'Course'               => $data['run_id'] ? Run::find($data['run_id'])?->name : 'Toutes courses',
                    'Droit CDN'            => $client->cdn_vouchers_quota,
                    'Droit Trail'          => $client->trail_vouchers_quota,
                    'Date d\'attribution'  => now()->format('d.m.Y H:i'),
                ];

                $assignedToClient++;
                $dispatchedCount++;
                $codeIndex++;
            }

            if ($assignedToClient > 0) {
                $clientsServedCount++;
            }

            if ($codeIndex >= $totalAvailable) {
                break;
            }
        }

        $remainingCodes = $totalAvailable - $codeIndex;

        Notification::make()
            ->title("Distribution terminée : {$dispatchedCount} code(s) attribué(s) à {$clientsServedCount} client(s).")
            ->body($remainingCodes > 0 ? "Il reste {$remainingCodes} code(s) non attribués dans votre lot." : 'Tous les codes du lot ont été attribués.')
            ->success()
            ->send();

        if ($exportRows !== []) {
            return (new FastExcel(collect($exportRows)))->download('attribution_vouchers_'.date('Ymd_His').'.xlsx');
        }

        return null;
    }

    protected static function handleManualImport(array $data): void
    {
        $rawCodes = preg_split('/[\r\n,;\s]+/', $data['codes_text'] ?? '');
        $editionId = VoucherMailing::editionId();
        $imported = 0;
        $skipped = 0;

        foreach ($rawCodes as $code) {
            $cleanCode = trim($code);
            if ($cleanCode === '') {
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
                'edition_id' => $editionId,
                'is_used'    => false,
            ]);

            $imported++;
        }

        Notification::make()
            ->title("Importation terminée : {$imported} code(s) créé(s)".($skipped > 0 ? " ({$skipped} existants ignorés)" : ''))
            ->success()
            ->send();
    }

    protected static function exportOverview(): mixed
    {
        $editionId = VoucherMailing::editionId();
        $clients = Client::orderBy('name')->get()->filter(fn ($c) => $c->cdn_vouchers_quota > 0 || $c->trail_vouchers_quota > 0 || $c->assigned_vouchers_count > 0);

        $exportData = [];
        foreach ($clients as $client) {
            $assignedVouchers = $client->vouchers()->where('edition_id', $editionId)->pluck('code')->implode(', ');
            $exportData[] = [
                'Client ou entreprise'     => $client->name,
                'Email de contact'         => $client->contactEmail ?? $client->email,
                'Quota vouchers CDN'       => $client->cdn_vouchers_quota,
                'Codes CDN attribués'      => $client->assigned_vouchers_count,
                'Codes CDN manquants'      => $client->missing_vouchers_count,
                'Statut CDN'               => $client->missing_vouchers_count === 0 ? 'Complet' : 'Incomplet (manque '.$client->missing_vouchers_count.')',
                'Quota Trail des Châteaux' => $client->trail_vouchers_quota,
                'Liste des codes CDN'      => $assignedVouchers,
            ];
        }

        if ($exportData === []) {
            Notification::make()->title('Aucune donnée de quota voucher à exporter.')->warning()->send();

            return null;
        }

        return (new FastExcel(collect($exportData)))->download('synthese_vouchers_cdn_'.date('Ymd_His').'.xlsx');
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
