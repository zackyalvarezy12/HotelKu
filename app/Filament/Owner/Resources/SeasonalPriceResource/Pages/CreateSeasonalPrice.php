<?php

namespace App\Filament\Owner\Resources\SeasonalPriceResource\Pages;

use App\Filament\Owner\Resources\SeasonalPriceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSeasonalPrice extends CreateRecord
{
    protected static string $resource = SeasonalPriceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', panel: 'owner');
    }
}
