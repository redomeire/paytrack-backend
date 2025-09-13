<?php

namespace App\Providers;

use Xendit\Configuration;
use Xendit\Payout\PayoutApi;
use Xendit\Invoice\InvoiceApi;
use App\Services\XenditService;
use Illuminate\Support\ServiceProvider;

class XenditServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(InvoiceApi::class, function ($app) {
            return new InvoiceApi();
        });
        $this->app->singleton(PayoutApi::class, function ($app) {
            return new PayoutApi();
        });
        $this->app->singleton(XenditService::class, function ($app) {
            return new XenditService(
                $app->make(InvoiceApi::class),
                $app->make(PayoutApi::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));
    }
}
