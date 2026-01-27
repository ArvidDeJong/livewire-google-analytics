<?php

namespace Darvis\LivewireGoogleAnalytics;

use Illuminate\Support\ServiceProvider;

class GoogleAnalyticsServiceProvider extends ServiceProvider
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
        // Publish JavaScript snippet
        $this->publishes([
            __DIR__.'/../resources/js/google-analytics.js' => public_path('vendor/livewire-google-analytics/google-analytics.js'),
        ], 'livewire-google-analytics-js');

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/livewire-google-analytics'),
        ], 'livewire-google-analytics-views');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'livewire-google-analytics');
    }
}
