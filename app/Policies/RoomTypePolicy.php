<?php

namespace App\Policies;

use App\Models\RoomType;
use App\Models\User;

/** Otorisasi tipe kamar: mengikuti kepemilikan hotel induknya. */
class RoomTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOwner() || $user->isStaff();
    }

    public function view(User $user, RoomType $roomType): bool
    {
        return $this->manages($user, $roomType);
    }

    /** Staff tidak boleh menambah tipe kamar; hanya owner hotel tersebut. */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOwner();
    }

    public function update(User $user, RoomType $roomType): bool
    {
        return $this->manages($user, $roomType);
    }

    public function delete(User $user, RoomType $roomType): bool
    {
        return $this->manages($user, $roomType);
    }

    protected function manages(User $user, RoomType $roomType): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $hotelIds = $user->managedHotelIds();

        return $hotelIds !== null && in_array($roomType->hotel_id, $hotelIds, true);
    }
}
