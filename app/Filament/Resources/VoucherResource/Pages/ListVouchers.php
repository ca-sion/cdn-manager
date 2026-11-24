<?php

namespace App\Filament\Resources\VoucherResource\Pages;

use App\Models\Voucher;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\VoucherResource;

class ListVouchers extends ListRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous')
                ->badge(fn (): int => Voucher::count()),

            'unassigned' => Tab::make('Non attribués')
                ->badge(fn (): int => Voucher::whereNull('client_id')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('client_id')),

            'available' => Tab::make('Disponibles')
                ->badge(fn (): int => Voucher::where('is_used', false)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_used', false)),

            'used' => Tab::make('Utilisés')
                ->badge(fn (): int => Voucher::where('is_used', true)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_used', true)),
        ];
    }
}
