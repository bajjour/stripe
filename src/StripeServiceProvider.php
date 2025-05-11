<?php
namespace Stripe;

use Illuminate\Support\ServiceProvider;
use Stripe\Services\StripeService;

class StripeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(StripeService::class, function ($app) {
            return new StripeService(
                config('stripe.api_key'),
                config('stripe.enable_3d')
            );
        });

        $this->mergeConfigFrom(__DIR__.'/../config/stripe.php', 'stripe');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/stripe.php' => config_path('stripe.php'),
        ], 'stripe-config');
    }
}
