<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Global scope isolasi data.
 *
 * Owner dan staff hanya boleh melihat data hotel yang mereka kelola.
 * Scope ini otomatis dilewati bila:
 *  - tidak ada pengguna login (CLI, seeder, job, tes), atau
 *  - pengguna berperan super_admin, atau
 *  - pengguna berperan guest (pembatasan tamu diatur lewat Policy).
 */
trait ScopedToManagedHotels
{
    /**
     * Nama unik global scope (PHP 8.1 belum mengizinkan konstanta pada trait,
     * jadi dipakai method statis sebagai pengganti).
     */
    public static function managedHotelsScopeName(): string
    {
        return 'managed_hotels';
    }

    public static function bootScopedToManagedHotels(): void
    {
        static::addGlobalScope(static::managedHotelsScopeName(), function (Builder $builder) {
            $user = Auth::user();

            if (! $user) {
                return;
            }

            $hotelIds = $user->managedHotelIds();

            // null = tidak dibatasi oleh scope ini.
            if ($hotelIds === null) {
                return;
            }

            $model = $builder->getModel();
            $column = $model->getTable() === 'hotels'
                ? $model->qualifyColumn('id')
                : $model->qualifyColumn('hotel_id');

            $builder->whereIn($column, $hotelIds);
        });
    }

    /** Hindari global scope ini (mis. untuk query lintas hotel oleh sistem). */
    public function scopeWithoutHotelIsolation(Builder $query): Builder
    {
        return $query->withoutGlobalScope(static::managedHotelsScopeName());
    }
}
