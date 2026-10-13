<?php

namespace App\Filament\Owner\Resources\HotelResource\Pages;

use App\Enums\HotelStatus;
use App\Filament\Owner\Resources\HotelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHotel extends CreateRecord
{
    protected static string $resource = HotelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', panel: 'owner');
    }

    /**
     * Hotel baru selalu milik pengguna yang sedang login dan berstatus
     * menunggu verifikasi (pending) sampai disetujui super_admin.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['owner_id'] = auth()->id();
        $data['status'] = HotelStatus::Pending;
        $data['verified_at'] = null;

        return $data;
    }
}
