<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \App\Events\PurchaseCompleted::class => [
            \App\Listeners\SendPurchaseConfirmation::class,
        ],
        \App\Events\ServiceAssigned::class => [
            \App\Listeners\SendServiceAssignmentNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot()
    {
        parent::boot();
    }
}
