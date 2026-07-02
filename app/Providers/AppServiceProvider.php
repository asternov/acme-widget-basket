<?php

namespace App\Providers;

use App\Domain\Basket\Basket;
use App\Domain\Basket\BasketFactory;
use App\Domain\Basket\Catalogue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Catalogue::class, fn (): Catalogue => BasketFactory::catalogue(config('acme.catalogue')));

        // A basket holds state, so every consumer gets a fresh instance.
        $this->app->bind(Basket::class, fn (): Basket => BasketFactory::basket(
            $this->app->make(Catalogue::class),
            config('acme'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
