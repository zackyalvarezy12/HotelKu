<?php

namespace App\Filament\Owner\Resources\HotelResource\Pages;

use App\Filament\Owner\Resources\HotelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHotel extends EditRecord
{
    protected static string $resource = HotelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', panel: 'owner');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Hapus Hotel'),
        ];
    }

    /**
     * Owner tidak boleh mengubah status verifikasi lewat form,
     * jadi `status` & `verified_at` tidak pernah ikut ter-update di sini.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['status'], $data['verified_at'], $data['owner_id']);

        return $data;
    }
}
