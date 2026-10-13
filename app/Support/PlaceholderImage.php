<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Pembuat gambar placeholder sederhana (memakai ekstensi GD).
 * Dipakai oleh seeder agar galeri hotel/kamar demo tidak kosong.
 */
class PlaceholderImage
{
    public static function make(string $relativePath, string $text, string $hex = '4F46E5'): string
    {
        if (File::exists(storage_path('app/public/'.$relativePath))) {
            return $relativePath;
        }

        File::ensureDirectoryExists(dirname(storage_path('app/public/'.$relativePath)));

        $width = 1200;
        $height = 800;
        $image = imagecreatetruecolor($width, $height);

        [$r, $g, $b] = sscanf(str_pad($hex, 6, '0'), '%02x%02x%02x');

        $background = imagecolorallocate($image, $r, $g, $b);
        $white = imagecolorallocate($image, 255, 255, 255);
        $overlay = imagecolorallocatealpha($image, 255, 255, 255, 20);

        imagefill($image, 0, 0, $background);
        // Pita putih di tengah sebagai "bingkai" judul.
        imagefilledrectangle($image, 0, (int) ($height / 2) - 60, $width, (int) ($height / 2) + 60, $overlay);

        // GD memakai font bawaan; ukuran 5 adalah yang terbesar.
        $fontSize = 5;
        $textWidth = imagefontwidth($fontSize) * strlen($text);
        imagestring($image, $fontSize, (int) (($width - $textWidth) / 2), (int) ($height / 2) - 8, $text, $background);
        imagestring($image, 3, (int) (($width - imagefontwidth(3) * 22) / 2), (int) ($height / 2) + 30, 'HotelKu (gambar contoh)', $white);

        imagejpeg($image, storage_path('app/public/'.$relativePath), 80);
        imagedestroy($image);

        return $relativePath;
    }
}
