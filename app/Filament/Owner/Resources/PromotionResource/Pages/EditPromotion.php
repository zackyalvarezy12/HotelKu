<?php

namespace App\Filament\Owner\Resources\PromotionResource\Pages;

use App\Filament\Owner\Resources\PromotionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromotion extends EditRecord
{
    protected static string $resource = PromotionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', panel: 'owner');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('Hapus Promosi'),
        ];
    }
}
