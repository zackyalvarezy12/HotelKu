<?php

namespace App\Filament\Owner\Resources\RoomTypeResource\Pages;

use App\Filament\Owner\Resources\RoomTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoomTypes extends ListRecords
{
    protected static string $resource = RoomTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Tipe Kamar'),
        ];
    }
}
