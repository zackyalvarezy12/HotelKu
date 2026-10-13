<?php

use App\Http\Controllers\BookingPaymentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\OwnerReportController;
use App\Http\Controllers\ProfileController;
use App\Livewire\Guest\BookingForm;
use App\Livewire\Guest\BookingDetail;
use App\Livewire\Guest\BookingHistory;
use App\Livewire\Guest\BookingReview;
use App\Livewire\Guest\HotelSearch;
use App\Livewire\Chat\ConversationInbox;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Halaman Tamu
|--------------------------------------------------------------------------
| Semua halaman di bawah ini hanya menampilkan hotel berstatus `active`
| (lihat scope Hotel::publiclyVisible()).
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

// Halaman hasil pencarian adalah komponen Livewire satu halaman penuh.
Route::get('/hotels', HotelSearch::class)->name('hotels.index');

// Detail hotel. Parameter diterima sebagai slug lalu dicari manual agar global
// scope isolasi pemilik tidak berlaku dan hotel non-aktif otomatis 404.
Route::get('/hotels/{hotel}', [HotelController::class, 'show'])->name('hotels.show');

/*
|--------------------------------------------------------------------------
| Area terautentikasi (Breeze)
|--------------------------------------------------------------------------
*/

// Riwayat booking milik tamu yang sedang login.
Route::get('/bookings', BookingHistory::class)->middleware('auth')->name('bookings.index');
Route::get('/bookings/{booking}/payment', [BookingPaymentController::class, 'show'])->middleware('auth')->name('bookings.payment.form');
Route::post('/bookings/{booking}/payment', [BookingPaymentController::class, 'store'])->middleware('auth')->name('bookings.payment.store');
Route::post('/bookings/{booking}/confirm-payment', [BookingPaymentController::class, 'confirmOwner'])->middleware('auth')->name('owner.bookings.payment.confirm');
Route::get('/owner/payments/{payment}/proof', [BookingPaymentController::class, 'showProof'])->middleware('auth')->name('owner.payments.proof');
Route::get('/bookings/{booking}', BookingDetail::class)->middleware('auth')->name('bookings.show');
Route::get('/bookings/{booking}/review', BookingReview::class)->middleware('auth')->name('bookings.review');
Route::get('/inbox', ConversationInbox::class)->middleware('auth')->name('chat.inbox');

// Halaman pemesanan kamar pada hotel tertentu (slug hotel + konteks pencarian).
Route::get('/hotels/{hotel}/pesan', BookingForm::class)->middleware('auth')->name('bookings.create');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->prefix('owner-reports')->name('owner.reports.')->group(function () {
    Route::get('/bookings.xlsx', [OwnerReportController::class, 'excel'])->name('excel');
    Route::get('/bookings.pdf', [OwnerReportController::class, 'pdf'])->name('pdf');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
