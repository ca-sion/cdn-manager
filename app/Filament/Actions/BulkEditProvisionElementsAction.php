<?php

namespace App\Filament\Actions;

use App\Enums\MediaStatusEnum;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use App\Enums\ProvisionElementStatusEnum;
use Illuminate\Database\Eloquent\Collection;

/**
 * Modification en masse du statut, du statut média et de l'édition des prestations.
 */
class BulkEditProvisionElementsAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'bulkEdit';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Modifier en masse')
            ->icon('heroicon-m-pencil-square')
            ->deselectRecordsAfterCompletion()
            ->schema([
                Select::make('status')
                    ->label('Statut')
                    ->options(ProvisionElementStatusEnum::class),
                Select::make('media_status')
                    ->label('Statut du média')
                    ->options(MediaStatusEnum::class),
                Select::make('edition_id')
                    ->searchable()
                    ->label('Edition')
                    ->relationship('edition', 'year'),
            ])
            ->action(function (Collection $records, array $data): void {
                $changes = array_filter($data);

                foreach ($records as $record) {
                    $record->fill($changes)->save();
                }
            });
    }
}
