<?php

namespace App\Policies;

use App\Models\SeasonalPrice;
use App\Models\User;

/**
 * Otorisasi harga musiman.
 * SeasonalPrice tidak punya kolom hotel_id, jadi isolasi ditelusuri
 * lewat roomType -> hotel_id.
 */
class SeasonalPricePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOwner() || $user->isStaff();
    }

    public function view(User $user, SeasonalPrice $price): bool
    {
        return $this->manages($user, $price);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOwner();
    }

    public function update(User $user, SeasonalPrice $price): bool
    {
        return $this->manages($user, $price);
    }

    public function delete(User $user, SeasonalPrice $price): bool
    {
        return $this->manages($user, $price);
    }

    protected function manages(User $user, SeasonalPrice $price): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $hotelIds = $user->managedHotelIds();

        return $hotelIds !== null && in_array($price->roomType?->hotel_id, $hotelIds, true);
    }
}
