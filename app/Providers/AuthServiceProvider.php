<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

// Modelos
use App\Models\Alert;
use App\Models\Notification;
use App\Models\Plant;
use App\Models\ServiceRequest;
use App\Models\Supply;
use App\Models\SupplyPurchase;
use App\Models\TrainingVideo;
use App\Models\User;

// Políticas
use App\Policies\AlertPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\PlantPolicy;
use App\Policies\ServiceRequestPolicy;
use App\Policies\SupplyCatalogPolicy;
use App\Policies\SupplyPurchasePolicy;
use App\Policies\TrainingVideoPolicy;
use App\Policies\UserPolicy;
use App\Policies\StatisticsPolicy; // si la usas

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Alert::class          => AlertPolicy::class,
        Notification::class   => NotificationPolicy::class,
        Plant::class          => PlantPolicy::class,
        ServiceRequest::class => ServiceRequestPolicy::class,
        Supply::class         => SupplyCatalogPolicy::class,
        SupplyPurchase::class => SupplyPurchasePolicy::class,
        TrainingVideo::class  => TrainingVideoPolicy::class,
        User::class           => UserPolicy::class,
        // 'Statistics'       => StatisticsPolicy::class, // si usas un modelo o clase
    ];

    public function boot()
    {
        $this->registerPolicies();

        // Gate global, por ejemplo:
        // Gate::define('superadmin-only', function (User $user) {
        //     return $user->hasRole('superadmin');
        // });
    }
}
