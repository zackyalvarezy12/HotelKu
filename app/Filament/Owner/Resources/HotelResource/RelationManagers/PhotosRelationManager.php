<?php

namespace App\Filament\Owner\Resources\HotelResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** Galeri foto hotel (tabel hotel_photos). */
class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Galeri Foto';

    protected static ?string $recordTitleAttribute = 'path';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('path')
                ->label('Foto')
                ->required()
                ->image()
                ->maxSize(2048) // maksimal 2MB
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->disk('public')
                ->directory('hotels/photos')
                ->imageEditor(),
            Forms\Components\Toggle::make('is_cover')
                ->label('Jadikan foto utama')
                ->helperText('Hanya satu foto utama per hotel; foto lain otomatis dinonaktifkan.'),
            Forms\Components\TextInput::make('sort_order')
                ->label('Urutan')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Foto')
                    ->disk('public')
                    ->height(60),
                Tables\Columns\IconColumn::make('is_cover')
                    ->label('Foto Utama')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diunggah')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Tambah Foto'),
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
            ->defaultSort('sort_order');
    }
}
