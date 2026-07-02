<?php

namespace App\Providers;

use App\Domain\Basket\Basket;
use App\Domain\Basket\BasketFactory;
use App\Domain\Basket\Catalogue;
use App\Domain\Basket\Coupons;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Catalogue::class, fn (): Catalogue => BasketFactory::catalogue(config('acme.catalogue')));
        $this->app->singleton(Coupons::class, fn (): Coupons => BasketFactory::coupons(config('acme.coupons')));

        // A basket holds state, so every consumer gets a fresh instance.
        $this->app->bind(Basket::class, fn (): Basket => BasketFactory::basket(
            $this->app->make(Catalogue::class),
            config('acme'),
        ));
    }

}
