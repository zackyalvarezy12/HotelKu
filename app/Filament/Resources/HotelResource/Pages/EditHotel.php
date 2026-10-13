<?php

namespace App\Filament\Resources\HotelResource\Pages;

use App\Filament\Resources\HotelResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditHotel extends EditRecord
{
    protected static string $resource = HotelResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', panel: 'admin');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('verify')
                ->label('Verifikasi Hotel')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => Gate::allows('verify', $this->getRecord()))
                ->action(function (): void {
                    Gate::authorize('verify', $this->getRecord());

                    $this->getRecord()->markAsVerified();

                    Notification::make()->title('Hotel berhasil diverifikasi.')->success()->send();
                    $this->refreshFormData(['status', 'verified_at']);
                }),

            Actions\Action::make('suspend')
                ->label('Suspend Hotel')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => Gate::allows('suspend', $this->getRecord()))
                ->action(function (): void {
                    Gate::authorize('suspend', $this->getRecord());

                    $this->getRecord()->markAsSuspended();

                    Notification::make()->title('Hotel ditangguhkan.')->warning()->send();
                    $this->refreshFormData(['status']);
                }),

            Actions\DeleteAction::make()->label('Hapus Hotel'),
        ];
    }
}
