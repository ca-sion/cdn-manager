<?php

namespace App\Filament\Resources\VoucherResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\VoucherResource;

class ListVouchers extends ListRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
