<?php

namespace App\Filament\Owner\Resources\SeasonalPriceResource\Pages;

use App\Filament\Owner\Resources\SeasonalPriceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeasonalPrice extends EditRecord
{
    protected static string $resource = SeasonalPriceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', panel: 'owner');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Hapus'),
        ];
    }
}
