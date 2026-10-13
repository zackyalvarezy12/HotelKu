<?php

namespace App\Filament\Owner\Resources\RoomTypeResource\Pages;

use App\Filament\Owner\Resources\RoomTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRoomType extends CreateRecord
{
    protected static string $resource = RoomTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', panel: 'owner');
    }
}
