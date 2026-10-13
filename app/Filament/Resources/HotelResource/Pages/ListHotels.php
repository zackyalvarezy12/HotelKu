<?php

namespace App\Filament\Resources\HotelResource\Pages;

use App\Enums\HotelStatus;
use App\Filament\Resources\HotelResource;
use App\Models\Hotel;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListHotels extends ListRecords
{
    protected static string $resource = HotelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Hotel'),
        ];
    }

    /** Tab cepat untuk memfilter hotel berdasarkan status verifikasi. */
    public function getTabs(): array
    {
        $badge = fn (HotelStatus $status): int => Hotel::where('status', $status->value)->count();

        return [
            'semua' => Tab::make('Semua'),
            'pending' => Tab::make('Menunggu Verifikasi')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', HotelStatus::Pending->value))
                ->badge($badge(HotelStatus::Pending) ?: null),
            'active' => Tab::make('Aktif')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', HotelStatus::Active->value)),
            'suspended' => Tab::make('Ditangguhkan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', HotelStatus::Suspended->value)),
        ];
    }
}
