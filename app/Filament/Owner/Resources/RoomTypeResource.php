<?php

namespace App\Filament\Owner\Resources;

use App\Filament\Owner\Resources\RoomTypeResource\Pages;
use App\Filament\Owner\Resources\RoomTypeResource\RelationManagers;
use App\Models\RoomType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/** CRUD tipe kamar, harga, dan jumlah kamar. Isolasi lewat global scope + RoomTypePolicy. */
class RoomTypeResource extends Resource
{
    protected static ?string $model = RoomType::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationLabel = 'Tipe Kamar';

    protected static ?string $modelLabel = 'Tipe Kamar';

    protected static ?string $pluralModelLabel = 'Tipe Kamar';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Kamar')
                ->schema([
                    Forms\Components\Select::make('hotel_id')
                        ->label('Hotel')
                        ->relationship('hotel', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false),
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Tipe Kamar')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->maxLength(5000)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Harga & Kapasitas')
                ->schema([
                    Forms\Components\TextInput::make('base_price')
                        ->label('Harga Dasar / Malam')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->helperText('Harga normal. Untuk harga musiman, gunakan menu Harga Musiman.'),
                    Forms\Components\TextInput::make('total_rooms')
                        ->label('Jumlah Kamar')
                        ->required()
                        ->numeric()
                        ->minValue(0),
                    Forms\Components\TextInput::make('capacity')
                        ->label('Kapasitas (orang)')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->default(2),
                    Forms\Components\Select::make('bed_type')
                        ->label('Tipe Tempat Tidur')
                        ->options([
                            'Single Bed' => 'Single Bed',
                            'Twin Bed' => 'Twin Bed',
                            'Double Bed' => 'Double Bed',
                            'Queen Bed' => 'Queen Bed',
                            'King Bed' => 'King Bed',
                        ])
                        ->searchable(),
                    Forms\Components\TextInput::make('size_m2')
                        ->label('Luas Kamar (m²)')
                        ->numeric()
                        ->minValue(0),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Tampilkan di halaman tamu')
                        ->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Fasilitas Kamar')
                ->schema([
                    Forms\Components\CheckboxList::make('facilities')
                        ->hiddenLabel()
                        ->relationship('facilities', 'name')
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hotel.name')
                    ->label('Hotel')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Tipe Kamar')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('base_price')
                    ->label('Harga / Malam')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Kapasitas')
                    ->suffix(' orang')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_rooms')
                    ->label('Jumlah Kamar')
                    ->badge()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('photos_count')
                    ->label('Foto')
                    ->counts('photos')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('seasonal_prices_count')
                    ->label('Harga Musiman')
                    ->counts('seasonalPrices')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('hotel_id')
                    ->label('Hotel')
                    ->relationship('hotel', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Status Aktif'),
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
            ->defaultSort('hotel_id');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PhotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoomTypes::route('/'),
            'create' => Pages\CreateRoomType::route('/create'),
            'edit' => Pages\EditRoomType::route('/{record}/edit'),
        ];
    }
}
