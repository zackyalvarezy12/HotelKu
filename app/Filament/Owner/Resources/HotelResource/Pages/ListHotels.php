<?php

namespace App\Filament\Owner\Resources\HotelResource\Pages;

use App\Filament\Owner\Resources\HotelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHotels extends ListRecords
{
    protected static string $resource = HotelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Hotel'),
        ];
    }
}
