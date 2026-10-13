<?php

namespace App\Filament\Owner\Resources\SeasonalPriceResource\Pages;

use App\Filament\Owner\Resources\SeasonalPriceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSeasonalPrices extends ListRecords
{
    protected static string $resource = SeasonalPriceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Harga Musiman'),
        ];
    }
}
