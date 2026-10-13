# HotelKu — Sistem Reservasi Hotel Multi-Hotel

Aplikasi reservasi hotel multi-tenant berbasis **Laravel 10** (PHP 8.1+).

- **Tamu (guest)**: cari hotel, lihat detail, pesan kamar, gunakan kode voucher, chat dengan hotel.
- **Pemilik (owner)**: banyak hotel, kelola kamar/harga/promo, proses booking, laporan.
- **Staff**: akses terbatas ke hotel tertentu.
- **Super admin**: verifikasi & suspend hotel, kelola pengguna, laporan global.

## Tech Stack

| Kebutuhan | Paket |
|---|---|
| Framework | Laravel 10 |
| Autentikasi tamu | Laravel Breeze (Blade) |
| Role & izin | spatie/laravel-permission 6 |
| Panel admin & owner | Filament 3 + Livewire 3 |
| Halaman tamu & chat | Livewire 3 + Tailwind CSS |
| PDF laporan | barryvdh/laravel-dompdf |
| Laporan Excel | maatwebsite/excel |
| Database | MySQL |

---

## Status Pengembangan

| Fase | Isi | Status |
|---|---|---|
| **1** | Fondasi: instalasi paket, database, model, relasi, enum, factory, seeder | ✅ Selesai |
| **2** | Panel Filament owner & super admin + Policy isolasi data + upload foto | ✅ Selesai |
| **3** | Halaman tamu: beranda + form pencarian, hasil dengan filter, detail hotel (galeri, fasilitas, tipe kamar, peta Leaflet) | ✅ Selesai |
| **4** | Alur booking: `BookingService` (anti double booking), `pending_payment` + batas bayar, halaman pemesanan & riwayat, command `bookings:expire` | ✅ Selesai |
| **5** | Pembayaran transfer (upload bukti privat, konfirmasi/penolakan owner, alasan, notifikasi database, unggah ulang), voucher booking PDF, kode voucher, chat tamu–hotel. | ✅ Selesai |
| **6** | Laporan & ekspor Excel/PDF serta dashboard okupansi sudah tersedia; finalisasi dan pemeriksaan akhir masih berjalan. | 🟡 Berjalan |

---

## Persiapan

```bash
# 1. Dependensi
composer install
npm install

# 2. Konfigurasi
cp .env.example .env
php artisan key:generate
# >> Sesuaikan DB_DATABASE / DB_USERNAME / DB_PASSWORD pada .env

# 3. Database (buat lebih dulu, mis. lewat MySQL)
#    CREATE DATABASE hotel_reservasi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 4. Migrasi + data contoh
php artisan migrate:fresh --seed

# 5. Simbolik storage (untuk foto hotel)
php artisan storage:link

# 6. Aset front-end
npm run dev      # mode pengembangan
# atau: npm run build
```

## Menjalankan

```bash
php artisan serve
```

| URL | Keterangan |
|---|---|
| `/` | Beranda: hero, form pencarian, kota populer, hotel pilihan |
| `/hotels` | Hasil pencarian + filter (query string dapat dibagikan) |
| `/hotels/{slug}` | Detail hotel: galeri, fasilitas, tipe kamar, peta Leaflet, tombol Pesan |
| `/hotels/{slug}/pesan` | Form pemesanan kamar (butuh login) |
| `/bookings` | Riwayat booking milik tamu yang login |
| `/login`, `/register` | Autentikasi tamu (Breeze) |
| `/admin` | Panel Super Admin (Filament) |
| `/owner` | Panel Pemilik Hotel / Staff (Filament) |

### Halaman Tamu

Semua halaman publik hanya menampilkan hotel berstatus **active** melalui scope
`Hotel::publiclyVisible()`. Scope itu sekaligus melepas `ScopedToManagedHotels`,
karena isolasi pemilik hanya berlaku di panel — tanpa itu, owner yang menjelajah
situs publik hanya akan melihat hotelnya sendiri (ada tes regresinya).

**Filter hasil pencarian** (tersinkron ke URL, bisa dibagikan):

| Parameter | Contoh | Keterangan |
|---|---|---|
| `kota` | `?kota=Jakarta` | Kota hotel |
| `masuk`, `keluar` | `&masuk=2026-11-01&keluar=2026-11-03` | Tanggal menginap (menyaring ketersediaan) |
| `tamu` | `&tamu=2` | Jumlah tamu (menyaring kapasitas kamar) |
| `harga_min`, `harga_max` | `&harga_min=300000` | Rentang harga per malam |
| `bintang[]` | `&bintang[]=4&bintang[]=5` | Klasifikasi bintang |
| `fasilitas[]` | `&fasilitas[]=1` | Hotel harus punya **semua** fasilitas terpilih |
| `urutkan` | `&urutkan=termurah` | `termurah` (default), `termahal`, `bintang` |

Daftar memakai tombol **Muat lebih banyak** (bukan pagination) agar nyaman di ponsel
sekaligus menghindari ketergantungan pada templat pagination pihak ketiga.

### Ketersediaan Kamar (`App\Services\AvailabilityService`)

```
kamar terpakai = SUM(rooms_count) booking berstatus
                 pending_payment / confirmed / checked_in
                 yang beririsan: check_in < tanggal_keluar AND check_out > tanggal_masuk

sisa kamar     = total_rooms - kamar terpakai   (tidak pernah negatif)
```

`availableHotels()` memakai **satu** query agregat dengan subquery berkorelasi,
sehingga tidak ada N+1 saat menyaring daftar hotel berdasarkan tanggal.

### Alur Booking (`App\Services\BookingService`)

| Langkah | Detail |
|---|---|
| 1. Validasi | Rentang tanggal (check-in hari ini s.d., check-out setelahnya), jumlah tamu ≤ kapasitas × jumlah kamar, tipe kamar aktif, hotel `active`. |
| 2. Kuncian | Seluruh proses dalam `DB::transaction()`; baris `room_types` dikunci `lockForUpdate()` sehingga permintaan seiring pada tipe kamar yang sama saling menunggu. |
| 3. Hitung ulang | Ketersediaan dihitung **ulang di dalam transaksi**, bukan dari hasil cek sebelumnya. Kekurangan stok melempar `RoomNotAvailableException` dengan pesan ramah. |
| 4. Harga | `PricingService` menghitung per malam (harga dasar atau harga musiman yang mencakup malam tersebut) × jumlah kamar. Nilai total dari klien diabaikan. |
| 5. Kode booking | Format `{prefiks}-YYMMDD-XXXXXX` (prefiks lewat `BOOKING_CODE_PREFIX`), dicek unik di dalam transaksi. |
| 6. Status | `pending_payment` + `expires_at` = `BOOKING_PAYMENT_EXPIRY_HOURS` (default 24 jam). |

```bash
php artisan bookings:expire    # melewati batas bayar -> expired, kuota promo dikembalikan
```

Command itu dijadwalkan **tiap 5 menit** (dengan `withoutOverlapping()`) di
`app/Console/Kernel.php`. Booking yang kedaluwarsa melepas stok kamar otomatis dan
mengembalikan kuota promonya (baris `promotion_usages` dihapus agar `quota_per_user`
tamu tidak ikut terpakai).

**Halaman pemesanan** (`/hotels/{slug}/pesan`, komponen Livewire) menerima konteks
pencarian lewat query string (`?kamar=&masuk=&keluar=&tamu=&jml_kamar=`),
menampilkan total yang dihitung ulang oleh server saat input berubah, dan menolak
hotel/tipe kamar milik hotel lain. Bila hotel dinonaktifkan selagi form terbuka,
halaman menampilkan pemberitahuan ramah, bukan 404/500.

**Riwayat booking** (`/bookings`) hanya menampilkan booking milik tamu yang login,
lewat filter `user_id` + `withoutHotelIsolation()` — owner tetap bisa melihat booking
pribadinya padahal global scope `ScopedToManagedHotels` berlaku untuknya.

### Aset Front-end

| Berkas | Isi |
|---|---|
| `resources/css/app.css` | Tailwind + komponen `.card`, `.btn-primary`, `.btn-secondary`, `.form-label`, aturan `[x-cloak]` |
| `resources/js/app.js` | Hanya `./bootstrap`. **Alpine tidak diimpor di sini** |
| `resources/js/map.js` | Bundel Leaflet + inisialisasi peta `[data-hotel-map]` |

> **Penting:** Alpine disediakan oleh Livewire 3, bukan lagi oleh Breeze. Livewire 3
> menimpakan instans Alpine miliknya ke `window.Alpine`; jika aplikasi juga
> meng-`import Alpine from 'alpinejs'` dan memanggil `Alpine.start()`, akan muncul dua
> instans Alpine ("Detected multiple instances of Alpine running") dan komponen Alpine
> di halaman Livewire berhenti bekerja. Karena itu `resources/js/app.js` tidak lagi
> mengimpor Alpine, dan `@livewireScripts` ditambahkan ke layout.

Peta Leaflet memerlukan koneksi internet untuk mengunduh ubin OpenStreetMap.
Masih ada dependensi `alpinejs` pada `package.json` bawaan Breeze yang kini tidak dipakai;
boleh dihapus bila ingin merapikan.

### Panel Pemilik Hotel (`/owner`)

| Menu | Isi |
|---|---|
| Hotel Saya | CRUD hotel: nama, kota, alamat, koordinat, kontak, jam check-in/out, klasifikasi bintang, fasilitas (checkbox), logo. Galeri foto lewat relasi (tandai satu foto utama). |
| Tipe Kamar | CRUD tipe kamar: harga dasar, jumlah kamar, kapasitas, tipe tempat tidur, luas, status aktif, fasilitas kamar, dan galeri foto. |
| Harga Musiman | CRUD harga musiman per tipe kamar (rentang tanggal + label + harga). |

Hotel baru yang ditambah owner otomatis berstatus **Menunggu Verifikasi** dan kolom
`owner_id` diisi dari pengguna yang login (bukan dari form), sehingga owner tidak bisa
membuat hotel atas nama orang lain atau mengaktifkan hotelnya sendiri.

### Panel Super Admin (`/admin`)

Menu **Verifikasi Hotel** menampilkan semua hotel dengan tab `Menunggu Verifikasi`,
`Aktif`, `Ditangguhkan`, plus badge jumlah hotel pending di navigasi. Tersedia aksi
**Verifikasi** (pending/suspended → active, mengisi `verified_at`), **Suspend**,
verifikasi massal, dan pengubahan data hotel.

> Panel `/owner` hanya untuk peran `owner` dan `staff`; super admin bekerja di `/admin`.
> Form owner menetapkan `owner_id` dari pengguna login, sehingga super admin sengaja
> tidak diberi akses ke panel owner.

### Otorisasi & Isolasi Data

| Lapis | Berkas | Fungsi |
|---|---|---|
| Global scope | `app/Models/Concerns/ScopedToManagedHotels.php` | Membatasi query `Hotel`, `RoomType`, `Booking`, `Promotion`, `Conversation` |
| Policy | `app/Policies/{Hotel,RoomType,SeasonalPrice}Policy.php` | Otorisasi per aksi (view/create/update/delete/verify/suspend) |
| `Gate::before` | `app/Providers/AuthServiceProvider.php` | Super admin melewati seluruh pemeriksaan policy |

`SeasonalPrice` tidak punya kolom `hotel_id`, jadi isolasinya ditelusuri lewat relasi
`roomType → hotel_id` secara eksplisit di `SeasonalPriceResource::getEloquentQuery()`.

Catatan: `Hotel` memakai `slug` sebagai route key, sehingga URL panel hotel berbentuk
`/owner/hotels/{slug}/edit`, bukan `/owner/hotels/{id}/edit`.

## Akun Demo

Semua akun memakai kata sandi **`password`**.

| Peran | Email |
|---|---|
| Super Admin | `admin@hotelku.test` |
| Owner (2 hotel) | `owner1@hotelku.test` |
| Owner (1 hotel) | `owner2@hotelku.test` |
| Staff | `staff@hotelku.test` |
| Tamu | `tamu1@hotelku.test`, `tamu2@hotelku.test` |

Data contoh berisi 3 hotel (Jakarta, Bandung, Yogyakarta), 9 tipe kamar, 4 promo,
beberapa booking, ulasan, dan 1 percakapan dengan pesan belum dibaca.

## Menjalankan Tes

```bash
php artisan test
```

Validasi terbaru untuk cakupan booking: **77 tes / 250 assertion lulus** dengan
`php artisan test --filter=Booking`. Ini bukan hasil seluruh test suite.

Tes memakai database terpisah (`DB_DATABASE=hotel_reservasi_test`, lihat `phpunit.xml`)
agar data pengembangan tidak terhapus. Buat dulu:

```sql
CREATE DATABASE hotel_reservasi_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## Struktur Kunci

```
app/
├─ Console/Commands/ExpireBookings.php      # bookings:expire (dijadwalkan tiap 5 menit)
├─ Enums/            # UserRole, HotelStatus, BookingStatus, PaymentStatus,
│                    # PaymentMethod, RoomStatus, PromotionType, DiscountType, Permission
├─ Exceptions/       # InvalidBookingException, RoomNotAvailableException
├─ Livewire/Guest/   # HotelSearch, SearchForm, BookingForm, BookingHistory
├─ Models/           # Hotel, RoomType, Booking, Promotion, Conversation, ... (+ relasi)
│  └─ Concerns/ScopedToManagedHotels.php   # global scope isolasi data owner/staff
├─ Providers/Filament/                     # AdminPanelProvider, OwnerPanelProvider
├─ Services/         # AvailabilityService, PricingService, BookingService
└─ Support/PlaceholderImage.php            # pembuat gambar contoh untuk seeder
config/booking.php   # batas bayar pembayaran & prefiks kode booking
database/
├─ migrations/       # seluruh skema
├─ factories/        # 15 factory
└─ seeders/          # RolePermissionSeeder, DemoSeeder
```

## Aturan Isolasi Data

`ScopedToManagedHotels` adalah global scope yang membatasi query secara otomatis:

| Peran | Perilaku scope |
|---|---|
| `super_admin` | dilewati (melihat semua) |
| `owner` | hanya hotel dengan `owner_id` miliknya |
| `staff` | hanya hotel pada pivot `hotel_staff` |
| `guest` | dilewati; pembatasan data tamu memakai Policy |
| tanpa login (CLI/seeder/job) | dilewati |
