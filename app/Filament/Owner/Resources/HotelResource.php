<?php

namespace App\Filament\Owner\Resources;

use App\Enums\HotelStatus;
use App\Filament\Owner\Resources\HotelResource\Pages;
use App\Filament\Owner\Resources\HotelResource\RelationManagers;
use App\Models\Hotel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * CRUD hotel untuk pemilik.
 *
 * Isolasi data berjalan dua lapis:
 * 1. global scope ScopedToManagedHotels pada model Hotel (query otomatis terbatas)
 * 2. HotelPolicy (otorisasi per aksi)
 */
class HotelResource extends Resource
{
    protected static ?string $model = Hotel::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Hotel Saya';

    protected static ?string $modelLabel = 'Hotel';

    protected static ?string $pluralModelLabel = 'Hotel';

    protected static ?int $navigationSort = 1;

    /** Daftar kota yang disarankan pada input kota. */
    public const CITIES = [
        'Jakarta', 'Bandung', 'Yogyakarta', 'Surabaya', 'Bali', 'Medan',
        'Semarang', 'Makassar', 'Malang', 'Lombok', 'Balikpapan', 'Batam',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Hotel')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Hotel')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('city')
                        ->label('Kota')
                        ->required()
                        ->maxLength(100)
                        ->datalist(self::CITIES),
                    Forms\Components\Select::make('star_rating')
                        ->label('Klasifikasi Bintang')
                        ->options([1 => '1 Bintang', 2 => '2 Bintang', 3 => '3 Bintang', 4 => '4 Bintang', 5 => '5 Bintang'])
                        ->default(3)
                        ->required(),
                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(4)
                        ->maxLength(5000)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Alamat & Lokasi')
                ->schema([
                    Forms\Components\Textarea::make('address')
                        ->label('Alamat Lengkap')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('latitude')
                        ->label('Latitude')
                        ->numeric()
                        ->placeholder('-6.2088'),
                    Forms\Components\TextInput::make('longitude')
                        ->label('Longitude')
                        ->numeric()
                        ->placeholder('106.8456'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Kontak & Jam Operasional')
                ->schema([
                    Forms\Components\TextInput::make('phone')
                        ->label('Telepon')
                        ->tel()
                        ->maxLength(30),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                    Forms\Components\TimePicker::make('check_in_time')
                        ->label('Jam Check-in')
                        ->seconds(false)
                        ->default('14:00')
                        ->required(),
                    Forms\Components\TimePicker::make('check_out_time')
                        ->label('Jam Check-out')
                        ->seconds(false)
                        ->default('12:00')
                        ->required(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Fasilitas Hotel')
                ->description('Pilih fasilitas yang tersedia di hotel Anda.')
                ->schema([
                    Forms\Components\CheckboxList::make('facilities')
                        ->hiddenLabel()
                        ->relationship('facilities', 'name')
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(3),
                ]),

            Forms\Components\Section::make('Logo Hotel')
                ->description('Unggah logo hotel (JPG/PNG/WebP, maksimal 2MB).')
                ->schema([
                    Forms\Components\FileUpload::make('logo')
                        ->hiddenLabel()
                        ->image()
                        ->maxSize(2048)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->disk('public')
                        ->directory('hotels/logos')
                        ->imageEditor(),
                ]),

            // Catatan: kolom `status` sengaja tidak ada di form owner.
            // Status hotel hanya diubah super_admin lewat panel /admin.
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->disk('public')
                    ->height(40)
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Hotel')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('city')
                    ->label('Kota')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('star_rating')
                    ->label('Bintang')
                    ->formatStateUsing(fn (?int $state): string => $state ? str_repeat('★', $state) : '-'),
                Tables\Columns\TextColumn::make('room_types_count')
                    ->label('Tipe Kamar')
                    ->counts('roomTypes')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status Verifikasi')
                    ->badge()
                    ->formatStateUsing(fn (?HotelStatus $state): string => $state?->label() ?? '-')
                    ->color(fn (?HotelStatus $state): string => $state?->color() ?? 'gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(HotelStatus::options()),
                Tables\Filters\SelectFilter::make('city')
                    ->label('Kota')
                    ->options(array_combine(self::CITIES, self::CITIES)),
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
            ->defaultSort('name');
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
            'index' => Pages\ListHotels::route('/'),
            'create' => Pages\CreateHotel::route('/create'),
            'edit' => Pages\EditHotel::route('/{record}/edit'),
        ];
    }
}
