<?php

namespace App\Filament\Owner\Resources;

use App\Enums\BookingStatus;
use App\Filament\Owner\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Notifications\PaymentProofRejected;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Booking';

    protected static ?string $modelLabel = 'Booking';

    protected static ?string $pluralModelLabel = 'Booking';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Detail Booking')
                ->schema([
                    Forms\Components\TextInput::make('code')->label('Kode Booking')->readOnly(),
                    Forms\Components\TextInput::make('guest_name')->label('Nama Tamu')->readOnly(),
                    Forms\Components\TextInput::make('hotel.name')->label('Hotel')->readOnly(),
                    Forms\Components\TextInput::make('roomType.name')->label('Tipe Kamar')->readOnly(),
                    Forms\Components\TextInput::make('total_price')->label('Total')->prefix('Rp')->numeric()->readOnly(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Kode')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('hotel.name')->label('Hotel')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('guest_name')->label('Tamu')->searchable(),
                Tables\Columns\TextColumn::make('roomType.name')->label('Kamar')->searchable(),
                Tables\Columns\ImageColumn::make('latestPayment.proof_path')
                    ->label('Bukti Transfer')
                    ->height(52)
                    ->getStateUsing(fn ($record): ?string => $record?->latestPayment?->proof_path
                        ? route('owner.payments.proof', $record->latestPayment)
                        : null)
                    ->extraImgAttributes(['alt' => 'Bukti transfer']),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?BookingStatus $state): string => $state?->label() ?? '-')
                    ->color(fn (?BookingStatus $state): string => $state?->color() ?? 'gray'),
                Tables\Columns\TextColumn::make('latestPayment.status')
                    ->label('Pembayaran')
                    ->formatStateUsing(fn ($state): string => $state ? (method_exists($state, 'label') ? $state->label() : ucfirst((string) $state)) : '-'),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('check_in')->label('Check-in')->date('d M Y'),
                Tables\Columns\TextColumn::make('check_out')->label('Check-out')->date('d M Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(BookingStatus::options()),
            ])
            ->actions([
                Action::make('confirm_payment')
                    ->label('Konfirmasi Bayar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record?->status === BookingStatus::PendingPayment
                        && $record?->latestPayment?->status === \App\Enums\PaymentStatus::Pending)
                    ->action(function (Booking $record): void {
                        $record->confirmPayment();
                    }),
                Action::make('reject_payment')
                    ->label('Tolak Bukti')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Alasan penolakan')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->visible(fn ($record): bool => $record?->status === BookingStatus::PendingPayment
                        && $record?->latestPayment?->status === \App\Enums\PaymentStatus::Pending
                        && filled($record?->latestPayment?->proof_path))
                    ->action(function (Booking $record, array $data): void {
                        $payment = $record->latestPayment;

                        if ($payment?->reject($data['rejection_reason'])) {
                            $record->user?->notify(new PaymentProofRejected($payment));
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
        ];
    }
}
