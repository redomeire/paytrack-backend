<?php

namespace App\Providers;

use Carbon\CarbonInterval;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Passport::tokensExpireIn(CarbonInterval::days(15));
        // creating scopes
        Passport::tokensCan([
            'user:bill:crud' => 'Create, Read, Update, Delete bill',
            'user:payment:crud' => 'Create, Read, Update, Delete payment',
            'user:notification:r' => 'Read notification',
            'admin:notification:crud' => 'Create, Read, Update, Delete notification',
            'admin:user:crud' => 'Create, Read, Update, Delete user',
        ]);
    }
}
