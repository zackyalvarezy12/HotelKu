<?php

namespace App\Filament\Resources;

use App\Enums\HotelStatus;
use App\Filament\Resources\HotelResource\Pages;
use App\Models\Hotel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/** Panel super admin: verifikasi & suspend hotel, serta kelola seluruh data hotel. */
class HotelResource extends Resource
{
    protected static ?string $model = Hotel::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Verifikasi Hotel';

    protected static ?string $modelLabel = 'Hotel';

    protected static ?string $pluralModelLabel = 'Hotel';

    protected static ?int $navigationSort = 1;

    /** Badge jumlah hotel yang menunggu verifikasi. */
    public static function getNavigationBadge(): ?string
    {
        $count = Hotel::where('status', HotelStatus::Pending->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Hotel')
                ->schema([
                    Forms\Components\Select::make('owner_id')
                        ->label('Pemilik')
                        ->relationship('owner', 'name', modifyQueryUsing: fn (Builder $query) => $query->orderBy('name'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Hotel')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('city')
                        ->label('Kota')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\Select::make('star_rating')
                        ->label('Bintang')
                        ->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5'])
                        ->required(),
                    Forms\Components\Textarea::make('address')
                        ->label('Alamat')
                        ->required()
                        ->rows(2)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('phone')
                        ->label('Telepon')
                        ->maxLength(30),
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                ])
                ->columns(2),

            Forms\Components\Section::make('Status Verifikasi')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(HotelStatus::options())
                        ->required()
                        ->native(false),
                    Forms\Components\DateTimePicker::make('verified_at')
                        ->label('Diverifikasi Pada')
                        ->native(false)
                        ->displayFormat('d/m/Y H:i'),
                ])
                ->columns(2),
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
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Pemilik')
                    ->searchable()
                    ->sortable(),
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
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?HotelStatus $state): string => $state?->label() ?? '-')
                    ->color(fn (?HotelStatus $state): string => $state?->color() ?? 'gray'),
                Tables\Columns\TextColumn::make('verified_at')
                    ->label('Diverifikasi')
                    ->dateTime('d M Y H:i')
                    ->placeholder('Belum')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Didaftarkan')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(HotelStatus::options()),
            ])
            ->actions([
                // Verifikasi hotel: pending/suspended -> active
                Tables\Actions\Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi hotel ini?')
                    ->modalDescription('Hotel akan berstatus Aktif dan tampil di halaman pencarian tamu.')
                    ->visible(fn (Hotel $record): bool => $record->status !== HotelStatus::Active
                        && Gate::allows('verify', $record))
                    ->action(function (Hotel $record): void {
                        Gate::authorize('verify', $record);

                        $record->markAsVerified();

                        Notification::make()
                            ->title('Hotel "'.$record->name.'" berhasil diverifikasi.')
                            ->success()
                            ->send();
                    }),

                // Suspend hotel aktif
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tangguhkan hotel ini?')
                    ->modalDescription('Hotel tidak akan tampil lagi di halaman pencarian tamu.')
                    ->visible(fn (Hotel $record): bool => $record->status === HotelStatus::Active
                        && Gate::allows('suspend', $record))
                    ->action(function (Hotel $record): void {
                        Gate::authorize('suspend', $record);

                        $record->markAsSuspended();

                        Notification::make()
                            ->title('Hotel "'.$record->name.'" ditangguhkan.')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()->label('Ubah'),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('verifySelected')
                    ->label('Verifikasi terpilih')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function ($records): void {
                        $count = 0;

                        foreach ($records as $record) {
                            Gate::authorize('verify', $record);
                            $record->markAsVerified();
                            $count++;
                        }

                        Notification::make()
                            ->title($count.' hotel berhasil diverifikasi.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Hapus terpilih'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
