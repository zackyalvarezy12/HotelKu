<?php

namespace App\Filament\Owner\Resources;

use App\Filament\Owner\Resources\SeasonalPriceResource\Pages;
use App\Models\RoomType;
use App\Models\SeasonalPrice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * CRUD harga musiman.
 *
 * SeasonalPrice tidak punya kolom hotel_id sehingga tidak tercakup global
 * scope ScopedToManagedHotels; isolasi di sini dilakukan eksplisit lewat
 * relasi roomType -> hotel_id.
 */
class SeasonalPriceResource extends Resource
{
    protected static ?string $model = SeasonalPrice::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Harga Musiman';

    protected static ?string $modelLabel = 'Harga Musiman';

    protected static ?string $pluralModelLabel = 'Harga Musiman';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['roomType.hotel']);

        // null = super_admin (tanpa pembatasan); selain itu dibatasi ke hotel yang dikelola.
        $hotelIds = auth()->user()?->managedHotelIds();

        if ($hotelIds !== null) {
            $query->whereHas('roomType', fn (Builder $q) => $q->whereIn('hotel_id', $hotelIds));
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Periode Harga Musiman')
                ->schema([
                    Forms\Components\Select::make('room_type_id')
                        ->label('Tipe Kamar')
                        ->relationship('roomType', 'name', modifyQueryUsing: fn (Builder $query) => $query->with('hotel'))
                        ->getOptionLabelFromRecordUsing(
                            fn (RoomType $record): string => ($record->hotel?->name ?? '-').' — '.$record->name
                        )
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\TextInput::make('label')
                        ->label('Nama Periode')
                        ->placeholder('Contoh: Libur Lebaran')
                        ->maxLength(100),
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Tanggal Selesai')
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->afterOrEqual('start_date'),
                    Forms\Components\TextInput::make('price')
                        ->label('Harga per Malam')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('roomType.hotel.name')
                    ->label('Hotel')
                    ->sortable(),
                Tables\Columns\TextColumn::make('roomType.name')
                    ->label('Tipe Kamar')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('label')
                    ->label('Periode')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga / Malam')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('room_type_id')
                    ->label('Tipe Kamar')
                    ->relationship('roomType', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Hapus terpilih'),
                ]),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeasonalPrices::route('/'),
            'create' => Pages\CreateSeasonalPrice::route('/create'),
            'edit' => Pages\EditSeasonalPrice::route('/{record}/edit'),
        ];
    }
}
