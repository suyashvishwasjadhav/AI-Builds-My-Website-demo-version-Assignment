<?php

namespace App\Providers;

use App\Services\Contracts\SearchDriver;
use App\Services\Search\GoogleCseDriver;
use App\Services\Search\GooglePlacesDriver;
use App\Services\Search\NullSearchDriver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SearchDriver::class, function (): SearchDriver {
            $driver = strtolower((string) config('services.search.driver', 'null'));
            $apiKey = (string) config('services.search.api_key', '');
            $engineId = (string) config('services.search.engine_id', '');

            return match ($driver) {
                'google_cse' => new GoogleCseDriver($apiKey, $engineId),
                'places' => new GooglePlacesDriver($apiKey),
                default => new NullSearchDriver(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
