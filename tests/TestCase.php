<?php

namespace Darvis\LivewireGoogleAnalytics\Tests;

use Darvis\LivewireGoogleAnalytics\GoogleAnalyticsServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // The package sends nothing from the server. A request that does go out fails the test.
        Http::preventStrayRequests();
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            GoogleAnalyticsServiceProvider::class,
        ];
    }

    /**
     * An app key, which Livewire needs for its snapshots. The package itself has no settings.
     *
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
