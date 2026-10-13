<?php

namespace App\Filament\Owner\Widgets;

use App\Models\Promotion;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PromotionUsageReport extends TableWidget
{
    protected static ?string $heading = 'Pemakaian Promosi';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $hotelIds = auth()->user()->managedHotelIds() ?? [];

        return $table
            ->query(Promotion::query()
                ->whereIn('hotel_id', $hotelIds)
                ->withCount('usages')
                ->withSum('usages', 'discount_amount'))
            ->columns([
                TextColumn::make('hotel.name')->label('Hotel')->toggleable(),
                TextColumn::make('name')->label('Promosi')->searchable(),
                TextColumn::make('type')->label('Jenis')->badge()->formatStateUsing(fn ($state) => $state->label()),
                TextColumn::make('usages_count')->label('Booking memakai promo')->sortable(),
                TextColumn::make('usages_sum_discount_amount')
                    ->label('Total potongan')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
            ])
            ->defaultSort('usages_count', 'desc')
            ->paginated([5, 10]);
    }
}