<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;

/** Otorisasi promosi: mengikuti kepemilikan hotel induknya. */
class PromotionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOwner() || $user->isStaff();
    }

    public function view(User $user, Promotion $promotion): bool
    {
        return $this->manages($user, $promotion);
    }

    /** Staff tidak boleh menambah promosi; hanya owner hotel tersebut. */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOwner();
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return $this->manages($user, $promotion);
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return $this->manages($user, $promotion);
    }

    protected function manages(User $user, Promotion $promotion): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $hotelIds = $user->managedHotelIds();

        return $hotelIds !== null && in_array($promotion->hotel_id, $hotelIds, true);
    }
}
