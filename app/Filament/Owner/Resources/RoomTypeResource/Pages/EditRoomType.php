<?php

namespace App\Filament\Owner\Resources\RoomTypeResource\Pages;

use App\Filament\Owner\Resources\RoomTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoomType extends EditRecord
{
    protected static string $resource = RoomTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', panel: 'owner');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Hapus Tipe Kamar'),
        ];
    }
}
