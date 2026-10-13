<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\DiscountType;
use App\Enums\HotelStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PromotionType;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Facility;
use App\Models\Hotel;
use App\Models\HotelPhoto;
use App\Models\Message;
use App\Models\Promotion;
use App\Models\RoomType;
use App\Models\RoomTypePhoto;
use App\Models\SeasonalPrice;
use App\Models\User;
use App\Support\PlaceholderImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        /* ---------------------------------------------------------------
         | 1. Pengguna
         * -------------------------------------------------------------- */
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@hotelku.test',
            'phone' => '081100000001',
            'password' => $password,
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super_admin');

        $owner1 = User::create([
            'name' => 'Budi Santoso',
            'email' => 'owner1@hotelku.test',
            'phone' => '081100000002',
            'password' => $password,
            'email_verified_at' => now(),
        ]);
        $owner1->assignRole('owner');

        $owner2 = User::create([
            'name' => 'Siti Aminah',
            'email' => 'owner2@hotelku.test',
            'phone' => '081100000003',
            'password' => $password,
            'email_verified_at' => now(),
        ]);
        $owner2->assignRole('owner');

        $guest1 = User::create([
            'name' => 'Andi Wijaya',
            'email' => 'tamu1@hotelku.test',
            'phone' => '081200000001',
            'password' => $password,
            'email_verified_at' => now(),
        ]);
        $guest1->assignRole('guest');

        $guest2 = User::create([
            'name' => 'Rina Lestari',
            'email' => 'tamu2@hotelku.test',
            'phone' => '081200000002',
            'password' => $password,
            'email_verified_at' => now(),
        ]);
        $guest2->assignRole('guest');

        /* ---------------------------------------------------------------
         | 2. Data master fasilitas
         * -------------------------------------------------------------- */
        $facilityData = [
            ['name' => 'WiFi Gratis', 'icon' => 'heroicon-o-wifi', 'category' => 'umum'],
            ['name' => 'Kolam Renang', 'icon' => 'heroicon-o-sparkles', 'category' => 'umum'],
            ['name' => 'Restoran', 'icon' => 'heroicon-o-cake', 'category' => 'umum'],
            ['name' => 'Parkir Gratis', 'icon' => 'heroicon-o-truck', 'category' => 'umum'],
            ['name' => 'Pusat Kebugaran', 'icon' => 'heroicon-o-bolt', 'category' => 'umum'],
            ['name' => 'Resepsionis 24 Jam', 'icon' => 'heroicon-o-clock', 'category' => 'umum'],
            ['name' => 'AC', 'icon' => 'heroicon-o-fire', 'category' => 'kamar'],
            ['name' => 'TV Kabel', 'icon' => 'heroicon-o-tv', 'category' => 'kamar'],
            ['name' => 'Air Panas', 'icon' => 'heroicon-o-beaker', 'category' => 'kamar'],
            ['name' => 'Balkon', 'icon' => 'heroicon-o-home', 'category' => 'kamar'],
        ];

        $facilities = collect($facilityData)->mapWithKeys(function (array $item) {
            $facility = Facility::create($item + ['slug' => Str::slug($item['name'])]);

            return [$item['name'] => $facility];
        });

        /* ---------------------------------------------------------------
         | 3. Hotel (owner1 punya 2 hotel, owner2 punya 1 hotel)
         * -------------------------------------------------------------- */
        $hotels = [
            [
                'owner' => $owner1,
                'name' => 'Grand Nusantara Jakarta',
                'city' => 'Jakarta',
                'address' => 'Jl. Jenderal Sudirman No. 45, Jakarta Pusat',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'star_rating' => 5,
                'status' => HotelStatus::Active,
                'facilities' => ['WiFi Gratis', 'Kolam Renang', 'Restoran', 'Parkir Gratis', 'Pusat Kebugaran', 'Resepsionis 24 Jam'],
            ],
            [
                'owner' => $owner1,
                'name' => 'Pesona Dieng Bandung',
                'city' => 'Bandung',
                'address' => 'Jl. Dago Pakar Raya No. 12, Bandung',
                'latitude' => -6.8649,
                'longitude' => 107.6185,
                'star_rating' => 4,
                'status' => HotelStatus::Active,
                'facilities' => ['WiFi Gratis', 'Restoran', 'Parkir Gratis', 'Resepsionis 24 Jam'],
            ],
            [
                'owner' => $owner2,
                'name' => 'Villa Senja Yogyakarta',
                'city' => 'Yogyakarta',
                'address' => 'Jl. Malioboro No. 88, Yogyakarta',
                'latitude' => -7.7956,
                'longitude' => 110.3695,
                'star_rating' => 3,
                'status' => HotelStatus::Active,
                'facilities' => ['WiFi Gratis', 'Restoran', 'Kolam Renang'],
            ],
        ];

        $createdHotels = [];

        foreach ($hotels as $index => $data) {
            $hotel = Hotel::create([
                'owner_id' => $data['owner']->id,
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'city' => $data['city'],
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'description' => 'Nikmati kenyamanan menginap di '.$data['name'].'. Berlokasi strategis dengan akses mudah ke pusat kota, '
                    .'restoran, dan tempat wisata favorit.',
                'phone' => '022'.fake()->numerify('#######'),
                'email' => Str::slug($data['name']).'@hotelku.test',
                'logo' => PlaceholderImage::make('hotels/demo/logo-'.($index + 1).'.jpg', 'LOGO'),
                'check_in_time' => '14:00:00',
                'check_out_time' => '12:00:00',
                'star_rating' => $data['star_rating'],
                'status' => $data['status'],
                'verified_at' => now(),
            ]);

            // Fasilitas hotel.
            $hotel->facilities()->sync(
                collect($data['facilities'])->map(fn ($name) => $facilities[$name]->id)->all()
            );

            // Galeri foto (gambar placeholder dengan warna berbeda per hotel).
            $colors = ['4F46E5', '0EA5E9', 'DB2777'];
            foreach (['Lobi', 'Kamar', 'Kolam Renang'] as $i => $label) {
                HotelPhoto::create([
                    'hotel_id' => $hotel->id,
                    'path' => PlaceholderImage::make(
                        'hotels/demo/hotel-'.($index + 1).'-'.($i + 1).'.jpg',
                        $data['name'].' - '.$label,
                        $colors[$index]
                    ),
                    'is_cover' => $i === 0,
                    'sort_order' => $i,
                ]);
            }

            // Tipe kamar.
            $roomTypeSeed = [
                ['Standard Room', 450000, 2, 'Queen Bed', 20, 8],
                ['Deluxe Room', 750000, 2, 'King Bed', 24, 12],
                ['Family Suite', 1250000, 4, 'Twin Bed', 40, 5],
            ];

            foreach ($roomTypeSeed as $rIndex => [$name, $price, $capacity, $bed, $size, $total]) {
                // Hotel bintang 5 harganya dinaikkan agar variatif.
                $price = (int) ($price * (0.7 + ($data['star_rating'] / 10)));

                $roomType = RoomType::create([
                    'hotel_id' => $hotel->id,
                    'name' => $name,
                    'description' => $name.' dengan pemandangan kota, cocok untuk '.$capacity.' orang.',
                    'base_price' => $price,
                    'capacity' => $capacity,
                    'bed_type' => $bed,
                    'size_m2' => $size,
                    'total_rooms' => $total,
                    'is_active' => true,
                ]);

                $roomType->facilities()->sync([
                    $facilities['AC']->id,
                    $facilities['TV Kabel']->id,
                    $facilities['Air Panas']->id,
                ]);

                RoomTypePhoto::create([
                    'room_type_id' => $roomType->id,
                    'path' => PlaceholderImage::make(
                        'room-types/demo/'.($index + 1).'-'.($rIndex + 1).'.jpg',
                        $name,
                        $colors[$index]
                    ),
                    'sort_order' => 0,
                ]);

                // Harga musiman (high season) untuk kamar pertama tiap hotel.
                if ($rIndex === 0) {
                    SeasonalPrice::create([
                        'room_type_id' => $roomType->id,
                        'start_date' => now()->addMonth()->startOfMonth()->toDateString(),
                        'end_date' => now()->addMonth()->startOfMonth()->addDays(9)->toDateString(),
                        'price' => (int) ($price * 1.35),
                        'label' => 'High Season',
                    ]);
                }
            }

            $createdHotels[$index] = $hotel;
        }

        /* ---------------------------------------------------------------
         | 4. Promosi contoh
         * -------------------------------------------------------------- */
        // a) Diskon otomatis 10% untuk Grand Nusantara.
        Promotion::create([
            'hotel_id' => $createdHotels[0]->id,
            'name' => 'Diskon Awal Bulan 10%',
            'description' => 'Potongan 10% untuk pemesanan minimal 2 malam.',
            'type' => PromotionType::AutoDiscount,
            'discount_type' => DiscountType::Percent,
            'discount_value' => 10,
            'max_discount' => 500000,
            'min_nights' => 2,
            'min_total' => 0,
            'booking_start' => now()->subWeek()->toDateString(),
            'booking_end' => now()->addMonths(3)->toDateString(),
            'stay_start' => now()->toDateString(),
            'stay_end' => now()->addMonths(6)->toDateString(),
            'valid_days' => null,
            'quota' => null,
            'quota_per_user' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        // b) Voucher berkode untuk Grand Nusantara.
        Promotion::create([
            'hotel_id' => $createdHotels[0]->id,
            'name' => 'Voucher HEMAT50',
            'description' => 'Potongan Rp50.000 dengan kode HEMAT50.',
            'type' => PromotionType::Voucher,
            'code' => 'HEMAT50',
            'discount_type' => DiscountType::Fixed,
            'discount_value' => 50000,
            'max_discount' => null,
            'min_nights' => 1,
            'min_total' => 300000,
            'booking_start' => now()->subWeek()->toDateString(),
            'booking_end' => now()->addMonths(2)->toDateString(),
            'stay_start' => now()->toDateString(),
            'stay_end' => now()->addMonths(4)->toDateString(),
            'valid_days' => null,
            'quota' => 50,
            'quota_per_user' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        // c) Promo khusus gratis 1 malam untuk Pesona Dieng.
        Promotion::create([
            'hotel_id' => $createdHotels[1]->id,
            'name' => 'Spesial Gratis 1 Malam',
            'description' => 'Menginap 3 malam, bayar 2 malam.',
            'type' => PromotionType::Special,
            'discount_type' => DiscountType::FreeNight,
            'discount_value' => 1,
            'max_discount' => null,
            'min_nights' => 3,
            'min_total' => 0,
            'booking_start' => now()->subWeek()->toDateString(),
            'booking_end' => now()->addMonths(2)->toDateString(),
            'stay_start' => now()->toDateString(),
            'stay_end' => now()->addMonths(3)->toDateString(),
            'valid_days' => [4, 5, 6], // Kamis, Jumat, Sabtu
            'quota' => 20,
            'quota_per_user' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        // d) Voucher weekend untuk Villa Senja.
        Promotion::create([
            'hotel_id' => $createdHotels[2]->id,
            'name' => 'Voucher Weekend 20%',
            'description' => 'Diskon 20% untuk menginap di akhir pekan.',
            'type' => PromotionType::Voucher,
            'code' => 'WEEKEND20',
            'discount_type' => DiscountType::Percent,
            'discount_value' => 20,
            'max_discount' => 300000,
            'min_nights' => 2,
            'min_total' => 0,
            'booking_start' => now()->subWeek()->toDateString(),
            'booking_end' => now()->addMonths(3)->toDateString(),
            'stay_start' => now()->toDateString(),
            'stay_end' => now()->addMonths(6)->toDateString(),
            'valid_days' => [5, 6],
            'quota' => 30,
            'quota_per_user' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        /* ---------------------------------------------------------------
         | 5. Staf hotel (opsional)
         * -------------------------------------------------------------- */
        $staff = User::create([
            'name' => 'Dewi Resepsionis',
            'email' => 'staff@hotelku.test',
            'phone' => '081300000001',
            'password' => $password,
            'email_verified_at' => now(),
        ]);
        $staff->assignRole('staff');
        $createdHotels[0]->staff()->attach($staff->id, ['position' => 'Resepsionis']);

        /* ---------------------------------------------------------------
         | 6. Booking contoh + pembayaran + ulasan
         * -------------------------------------------------------------- */
        $grandNusantara = $createdHotels[0];
        $deluxe = $grandNusantara->roomTypes()->where('name', 'Deluxe Room')->first();
        $standard = $grandNusantara->roomTypes()->where('name', 'Standard Room')->first();

        // Booking lunas yang sudah selesai (bisa diulas).
        $completed = $this->createBooking($guest1, $grandNusantara, $deluxe, now()->subDays(6), 2, [
            'status' => BookingStatus::Completed,
            'confirmed_at' => now()->subDays(7),
            'checked_in_at' => now()->subDays(6),
            'checked_out_at' => now()->subDays(4),
        ]);
        $completed->payments()->create([
            'method' => PaymentMethod::BankTransfer,
            'amount' => $completed->total_price,
            'status' => PaymentStatus::Paid,
            'paid_at' => now()->subDays(7),
        ]);
        $completed->review()->create([
            'user_id' => $guest1->id,
            'hotel_id' => $grandNusantara->id,
            'rating' => 5,
            'comment' => 'Pelayanan sangat ramah dan kamar bersih. Pasti kembali lagi!',
        ]);

        // Booking menunggu pembayaran (belum kedaluwarsa).
        $this->createBooking($guest2, $grandNusantara, $standard, now()->addDays(5), 1);

        // Booking terkonfirmasi.
        $this->createBooking($guest1, $grandNusantara, $standard, now()->addDays(10), 1, [
            'status' => BookingStatus::Confirmed,
            'confirmed_at' => now(),
        ]);

        // Booking sedang menginap.
        $this->createBooking($guest2, $createdHotels[1], null, now(), 3, [
            'status' => BookingStatus::CheckedIn,
            'confirmed_at' => now()->subDay(),
            'checked_in_at' => now(),
        ]);

        /* ---------------------------------------------------------------
         | 7. Percakapan & pesan contoh
         * -------------------------------------------------------------- */
        $conversation = Conversation::create([
            'hotel_id' => $grandNusantara->id,
            'user_id' => $guest1->id,
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $guest1->id,
            'body' => 'Selamat siang, apakah tersedia kamar tambahan untuk anak?',
            'read_at' => now()->subMinutes(30),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $owner1->id,
            'body' => 'Siang, tentu. Kami bisa menyediakan extra bed dengan biaya tambahan.',
            'read_at' => now()->subMinutes(20),
        ]);

        // Pesan belum dibaca oleh pihak hotel (untuk menguji badge notifikasi).
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $guest1->id,
            'body' => 'Baik, tolong dipesankan satu extra bed ya.',
        ]);
    }

    /** Membuat booking beserta perhitungan harganya. */
    private function createBooking(
        User $guest,
        Hotel $hotel,
        ?RoomType $roomType,
        \DateTimeInterface $checkIn,
        int $nights,
        array $overrides = []
    ): Booking {
        $roomType ??= $hotel->roomTypes()->first();
        $checkIn = \Illuminate\Support\Carbon::parse($checkIn)->startOfDay();
        $checkOut = (clone $checkIn)->addDays($nights);
        $roomsCount = 1;

        $subtotal = (float) $roomType->base_price * $nights * $roomsCount;

        return Booking::create(array_merge([
            'code' => 'BK-'.$checkIn->format('ymd').'-'.strtoupper(Str::random(5)),
            'user_id' => $guest->id,
            'hotel_id' => $hotel->id,
            'room_type_id' => $roomType->id,
            'guest_name' => $guest->name,
            'guest_phone' => $guest->phone,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'nights' => $nights,
            'guests' => 2,
            'rooms_count' => $roomsCount,
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'total_price' => $subtotal,
            'status' => BookingStatus::PendingPayment,
            'expires_at' => now()->addDay(),
        ], $overrides));
    }
}
