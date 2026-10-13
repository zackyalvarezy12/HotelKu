<?php

namespace App\Policies;

use App\Models\Hotel;
use App\Models\User;

/**
 * Otorisasi data hotel.
 * Owner/staff hanya boleh mengelola hotel yang mereka kelola;
 * super_admin bebas (juga lewat Gate::before di AuthServiceProvider).
 */
class HotelPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOwner() || $user->isStaff();
    }

    public function view(User $user, Hotel $hotel): bool
    {
        return $user->isSuperAdmin() || $this->manages($user, $hotel);
    }

    /** Hanya owner yang boleh menambah hotel baru; staff tidak. */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isOwner();
    }

    public function update(User $user, Hotel $hotel): bool
    {
        return $user->isSuperAdmin()
            || ($user->isOwner() && $hotel->owner_id === $user->getKey());
    }

    public function delete(User $user, Hotel $hotel): bool
    {
        return $this->update($user, $hotel);
    }

    public function restore(User $user, Hotel $hotel): bool
    {
        return $user->isSuperAdmin();
    }

    public function forceDelete(User $user, Hotel $hotel): bool
    {
        return $user->isSuperAdmin();
    }

    /** Verifikasi hotel (pending -> active) hanya boleh oleh super_admin. */
    public function verify(User $user, Hotel $hotel): bool
    {
        return $user->isSuperAdmin();
    }

    public function suspend(User $user, Hotel $hotel): bool
    {
        return $user->isSuperAdmin();
    }

    /** Pemilik atau staf pada hotel tersebut. */
    protected function manages(User $user, Hotel $hotel): bool
    {
        $hotelIds = $user->managedHotelIds();

        return $hotelIds !== null && in_array($hotel->getKey(), $hotelIds, true);
    }
}
