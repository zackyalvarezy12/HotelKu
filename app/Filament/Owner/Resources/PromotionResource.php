<?php

namespace App\Filament\Owner\Resources;

use App\Enums\DiscountType;
use App\Enums\PromotionType;
use App\Filament\Owner\Resources\PromotionResource\Pages;
use App\Models\Promotion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * CRUD promosi (diskon otomatis / voucher / promo khusus).
 *
 * Isolasi memakai global scope ScopedToManagedHotels (Promotion punya hotel_id).
 */
class PromotionResource extends Resource
{
    protected static ?string $model = Promotion::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Promosi';

    protected static ?string $modelLabel = 'Promosi';

    protected static ?string $pluralModelLabel = 'Promosi';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Promosi')
                    ->schema([
                        Forms\Components\Select::make('hotel_id')
                            ->label('Hotel')
                            ->relationship('hotel', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Promosi')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->label('Jenis')
                            ->options(PromotionType::options())
                            ->required()
                            ->default(PromotionType::AutoDiscount->value)
                            ->native(false)
                            ->helperText('Promo Khusus memakai aturan yang sama dengan Diskon Otomatis.')
                            ->live(),
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Voucher')
                            ->maxLength(50)
                            ->helperText('Wajib untuk jenis Voucher. Akan diubah menjadi huruf besar.')
                            ->suffixIcon('heroicon-o-key')
                            ->rules([
                                // Wajib hanya untuk jenis Voucher.
                                fn (Get $get): callable => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    if ($get('type') === PromotionType::Voucher->value && blank($value)) {
                                        $fail('Kode voucher wajib diisi untuk jenis Voucher.');
                                    }
                                },
                            ])
                            // Kode unik per hotel (lihat unique(['hotel_id','code']) di migrasi).
                            // `ignoreRecord` membuat record yang sedang diedit tidak ikut diperiksa.
                            ->unique(
                                table: 'promotions',
                                column: 'code',
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule, Get $get) => $rule->where('hotel_id', $get('hotel_id')),
                            )
                            ->dehydrateStateUsing(fn ($state) => is_string($state)
                                ? mb_strtoupper(trim($state))
                                : $state),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Nilai Diskon')
                    ->schema([
                        Forms\Components\Select::make('discount_type')
                            ->label('Cara Potongan')
                            ->options(DiscountType::options())
                            ->required()
                            ->default(DiscountType::Percent->value)
                            ->native(false)
                            ->live(),
                        Forms\Components\TextInput::make('discount_value')
                            ->label(fn (Get $get): string => $get('discount_type') === DiscountType::Fixed->value
                                ? 'Nominal Potongan'
                                : 'Nilai Potongan')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix(fn (Get $get): ?string => $get('discount_type') === DiscountType::Fixed->value ? 'Rp' : null)
                            ->suffix(fn (Get $get): ?string => $get('discount_type') === DiscountType::Percent->value ? '%' : null)
                            ->default(10)
                            ->rules(['max:100'])
                            ->helperText(fn (Get $get): ?string => $get('discount_type') === DiscountType::FreeNight->value
                                ? 'Jumlah malam gratis (1 = satu malam termurah).'
                                : null),
                        Forms\Components\TextInput::make('max_discount')
                            ->label('Potongan Maksimal')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->helperText('Kosongkan bila tanpa batas.'),
                        Forms\Components\TextInput::make('min_nights')
                            ->label('Minimal Malam')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                        Forms\Components\TextInput::make('min_total')
                            ->label('Minimal Total')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->default(0),
                        Forms\Components\TextInput::make('quota_per_user')
                            ->label('Batas Pemakaian per Tamu')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Periode & Kuota')
                    ->schema([
                        Forms\Components\DatePicker::make('booking_start')
                            ->label('Pemesanan Mulai')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('booking_end')
                            ->label('Pemesanan Selesai')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('booking_start'),
                        Forms\Components\DatePicker::make('stay_start')
                            ->label('Menginap Mulai')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Forms\Components\DatePicker::make('stay_end')
                            ->label('Menginap Selesai')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('stay_start'),
                        Forms\Components\CheckboxList::make('valid_days')
                            ->label('Hari Berlaku')
                            ->options([
                                0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
                                4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu',
                            ])
                            ->columns(4)
                            ->helperText('Kosongkan bila berlaku setiap hari. Dihitung dari tanggal check-in.'),
                        Forms\Components\TextInput::make('quota')
                            ->label('Total Kuota')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Kosongkan bila tanpa batas.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Tampilan & Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Promosi aktif')
                            ->default(true),
                        Forms\Components\Toggle::make('is_public')
                            ->label('Tampilkan sebagai harga coret di halaman tamu')
                            ->default(true)
                            ->helperText('Hanya promo otomatis/khusus yang tampil sebagai harga coret.'),
                        Forms\Components\Select::make('roomTypes')
                            ->label('Berlaku untuk Tipe Kamar')
                            ->relationship('roomTypes', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->helperText('Kosongkan bila berlaku untuk semua tipe kamar di hotel ini.'),
                    ])
                    ->columns(2),
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
                    ->label('Nama')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (PromotionType $state): string => $state->label()),
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode')
                    ->placeholder('—')
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('discount')
                    ->label('Potongan')
                    ->state(fn (Promotion $record): string => match ($record->discount_type) {
                        DiscountType::Percent => rtrim(rtrim((string) $record->discount_value, '0'), '.').'%',
                        DiscountType::Fixed => 'Rp'.number_format((float) $record->discount_value, 0, ',', '.'),
                        DiscountType::FreeNight => 'Gratis '.$record->discount_value.' malam',
                    }),
                Tables\Columns\TextColumn::make('period')
                    ->label('Periode Pemesanan')
                    ->state(fn (Promotion $record): string => match (true) {
                        $record->booking_start && $record->booking_end =>
                            $record->booking_start->translatedFormat('d M Y').' – '.$record->booking_end->translatedFormat('d M Y'),
                        $record->booking_start => 'Mulai '.$record->booking_start->translatedFormat('d M Y'),
                        $record->booking_end => 'Sampai '.$record->booking_end->translatedFormat('d M Y'),
                        default => 'Selalu',
                    }),
                Tables\Columns\TextColumn::make('usage')
                    ->label('Terpakai')
                    ->state(fn (Promotion $record): string => $record->used_count.' / '.($record->quota ?? '∞'))
                    ->badge()
                    ->color(fn (Promotion $record): string => $record->hasQuotaLeft() ? 'gray' : 'danger'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_public')
                    ->label('Tampil')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('hotel_id')
                    ->label('Hotel')
                    ->relationship('hotel', 'name'),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis')
                    ->options(PromotionType::options()),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromotions::route('/'),
            'create' => Pages\CreatePromotion::route('/create'),
            'edit' => Pages\EditPromotion::route('/{record}/edit'),
        ];
    }
}
