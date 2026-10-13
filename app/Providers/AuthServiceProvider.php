<?php

namespace App\Providers;

use App\Models\Hotel;
use App\Models\Promotion;
use App\Models\RoomType;
use App\Models\SeasonalPrice;
use App\Models\User;
use App\Policies\HotelPolicy;
use App\Policies\PromotionPolicy;
use App\Policies\RoomTypePolicy;
use App\Policies\SeasonalPricePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Pemetaan model ke policy.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Hotel::class => HotelPolicy::class,
        Promotion::class => PromotionPolicy::class,
        RoomType::class => RoomTypePolicy::class,
        SeasonalPrice::class => SeasonalPricePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Super admin melewati seluruh pemeriksaan policy (peran "kelola semua data").
        Gate::before(function (User $user) {
            return $user->isSuperAdmin() ? true : null;
        });
    }
}
