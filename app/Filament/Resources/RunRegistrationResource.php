<?php

namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\RunRegistration;
use Filament\Resources\Resource;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Services\RunRegistrationService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\RunRegistrationResource\Pages;
use App\Filament\Resources\RunRegistrationResource\RelationManagers;

class RunRegistrationResource extends Resource
{
    // ... (navigation, model, labels)

    public static function form(Form $form): Form
    {
        // ... (tabs schema)
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ... (columns)
            ])
            ->filters([
                // ... (filters)
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
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
                ]),
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
                        ->label('Export Datasport')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $data = collect();
                            foreach ($records as $registration) {
                                foreach ($registration->runRegistrationElements as $element) {
                                    $data->push([
                                        'Nom'            => $element->last_name,
                                        'Prénom'         => $element->first_name,
                                        'Date naissance' => $element->birthdate?->format('d.m.Y'),
                                        'Sexe'           => $element->gender,
                                        'Nationalité'    => $element->nationality,
                                        'Email'          => $element->email,
                                        'Course'         => $element->run?->name ?? $element->run_name,
                                        'Bloc'           => $element->bloc,
                                        'Équipe'         => $element->team,
                                        'Localité'       => $element->locality ?? $registration->invoicing_locality,
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
