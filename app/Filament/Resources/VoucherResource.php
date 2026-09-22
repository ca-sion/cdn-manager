<?php

namespace App\Filament\Resources;

use Exception;
use App\Models\Run;
use App\Models\Client;
use App\Models\Voucher;
use App\Helpers\AppHelper;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Illuminate\Support\HtmlString;
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
use App\Notifications\ClientSendVouchers;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Filters\TernaryFilter;

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
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Non utilisé')
                    ->sortable(),

                TextColumn::make('usedByElement')
                    ->label('Utilisé par')
                    ->getStateUsing(fn ($record) => $record->usedByElement ? ($record->usedByElement->first_name.' '.$record->usedByElement->last_name) : null)
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
                // 1. Assistant de Distribution Automatique (Auto-dispatch)
                Action::make('autoDispatchVouchers')
                    ->label('Distribution automatique de vouchers')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->schema(function () {
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

                        $summaryHtml = '
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

                        return [
                            Placeholder::make('needs_summary')
                                ->label('État actuel des besoins par client')
                                ->content(new HtmlString($summaryHtml)),

                            Textarea::make('codes_text')
                                ->label('Lot de codes Datasport à distribuer')
                                ->helperText('Collez votre liste de codes (un par ligne ou séparés par des virgules/espaces). Le système les affectera successivement aux clients ayant des codes manquants.')
                                ->rows(8)
                                ->required(),

                            Select::make('run_id')
                                ->label('Restreindre les codes à une course spécifique (optionnel)')
                                ->options(fn () => Run::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->preload(),
                        ];
                    })
                    ->action(function (array $data) {
                        $rawText = $data['codes_text'] ?? '';
                        $rawCodes = preg_split('/[\r\n,;\s]+/', $rawText);
                        $availableCodes = [];

                        foreach ($rawCodes as $code) {
                            $clean = trim($code);
                            if (! empty($clean) && ! Voucher::where('code', $clean)->exists()) {
                                $availableCodes[] = $clean;
                            }
                        }

                        if (empty($availableCodes)) {
                            Notification::make()
                                ->title('Aucun nouveau code valide trouvé à importer.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $editionId = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');
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

                        if (! empty($exportRows)) {
                            return (new FastExcel(collect($exportRows)))->download('attribution_vouchers_'.date('Ymd_His').'.xlsx');
                        }
                    }),

                // 2. Export global de la synthèse des quotas et attributions (Excel)
                Action::make('exportVouchersOverview')
                    ->label('Exporter la synthèse des attributions (Excel)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->action(function () {
                        $editionId = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');
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

                        if (empty($exportData)) {
                            Notification::make()->title('Aucune donnée de quota voucher à exporter.')->warning()->send();

                            return null;
                        }

                        return (new FastExcel(collect($exportData)))->download('synthese_vouchers_cdn_'.date('Ymd_His').'.xlsx');
                    }),

                // 3. Importateur manuel unitaire de Vouchers Datasport
                Action::make('importVouchers')
                    ->label('Importer manuellement pour un client')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('gray')
                    ->schema([
                        Select::make('client_id')
                            ->label('Attribuer au client ou à l\'entreprise')
                            ->options(function () {
                                return Client::orderBy('name')->get()->mapWithKeys(function ($c) {
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
                                });
                            })
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
                            ->rows(6)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $rawText = $data['codes_text'] ?? '';
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
                                'edition_id' => AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id'),
                                'is_used'    => false,
                            ]);

                            $imported++;
                        }

                        Notification::make()
                            ->title("Importation terminée : {$imported} code(s) créé(s)".($skipped > 0 ? " ({$skipped} existants ignorés)" : ''))
                            ->success()
                            ->send();
                    }),

                // 4. Envoi individuel des Vouchers par Email
                Action::make('sendVouchersEmail')
                    ->label('Envoyer les vouchers par email')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->schema([
                        Select::make('client_id')
                            ->label('Sélectionner le client destinataire')
                            ->options(function () {
                                return Client::orderBy('name')->get()->mapWithKeys(function ($c) {
                                    $assigned = $c->assigned_vouchers_count;
                                    $trail = $c->trail_vouchers_quota;

                                    $label = "{$c->name} ({$assigned} codes CDN attribués";
                                    if ($trail > 0) {
                                        $label .= " | {$trail} place(s) Trail";
                                    }
                                    $label .= ')';

                                    return [$c->id => $label];
                                });
                            })
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

                        $editionId = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');
                        $vouchers = Voucher::where('client_id', $client->id)->where('edition_id', $editionId)->get();

                        if ($vouchers->isEmpty() && $client->trail_vouchers_quota === 0) {
                            Notification::make()->title('Aucun voucher attribué et aucun quota Trail pour ce client.')->warning()->send();

                            return;
                        }

                        try {
                            $client->notify(new ClientSendVouchers($vouchers, $data['custom_message'] ?? null, $client->trail_vouchers_quota));
                            Notification::make()
                                ->title("Email de vouchers envoyé avec succès à {$client->name} !")
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()->title('Erreur lors de l\'envoi')->body($e->getMessage())->danger()->send();
                        }
                    }),

                // 5. Envoi groupé des emails de vouchers
                Action::make('sendAllVouchersEmails')
                    ->label('Envoi groupé des emails de vouchers')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->schema([
                        Select::make('client_ids')
                            ->label('Clients destinataires')
                            ->helperText('Sélectionnez les clients auxquels envoyer leurs vouchers et instructions.')
                            ->options(function () {
                                $editionId = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');

                                return Client::whereHas('vouchers', fn ($q) => $q->where('edition_id', $editionId))
                                    ->orWhere(function ($q) {
                                        // clients with trail quota
                                    })
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(function ($c) {
                                        return [$c->id => "{$c->name} ({$c->assigned_vouchers_count} codes CDN | Trail: {$c->trail_vouchers_quota})"];
                                    });
                            })
                            ->default(function () {
                                $editionId = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');

                                return Client::whereHas('vouchers', fn ($q) => $q->where('edition_id', $editionId))->pluck('id')->toArray();
                            })
                            ->multiple()
                            ->searchable()
                            ->required(),

                        Textarea::make('custom_message')
                            ->label('Message personnalisé commun (optionnel)')
                            ->placeholder('Ex: Voici vos codes pour la Course de Noël...'),
                    ])
                    ->action(function (array $data) {
                        $clientIds = $data['client_ids'] ?? [];
                        $editionId = AppHelper::getCurrentEditionId() ?? config('cdn.default_edition_id');
                        $sent = 0;
                        $errors = 0;

                        foreach ($clientIds as $clientId) {
                            $client = Client::find($clientId);
                            if (! $client) {
                                continue;
                            }

                            $vouchers = Voucher::where('client_id', $client->id)->where('edition_id', $editionId)->get();

                            try {
                                $client->notify(new ClientSendVouchers($vouchers, $data['custom_message'] ?? null, $client->trail_vouchers_quota));
                                $sent++;
                            } catch (Exception $e) {
                                $errors++;
                            }
                        }

                        Notification::make()
                            ->title("Envoi groupé terminé : {$sent} email(s) envoyé(s)".($errors > 0 ? " ({$errors} erreur(s))" : ''))
                            ->success()
                            ->send();
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
