<?php

use Darvis\LivewireGoogleAnalytics\GoogleAnalyticsServiceProvider;
use Illuminate\Support\ServiceProvider;

it('registers the view namespace', function () {
    expect(view()->exists('livewire-google-analytics::script'))->toBeTrue();
});

it('offers the JavaScript file for publishing', function () {
    // Never run vendor:publish here: it writes into the Testbench app.
    $paths = ServiceProvider::pathsToPublish(GoogleAnalyticsServiceProvider::class, 'livewire-google-analytics-js');

    expect($paths)->toHaveCount(1)
        ->and(array_key_first($paths))->toEndWith('resources/js/google-analytics.js')
        ->and(is_file((string) realpath((string) array_key_first($paths))))->toBeTrue()
        ->and(array_values($paths)[0])->toBe(public_path('vendor/livewire-google-analytics/google-analytics.js'));
});

it('offers the view for publishing', function () {
    $paths = ServiceProvider::pathsToPublish(GoogleAnalyticsServiceProvider::class, 'livewire-google-analytics-views');

    expect($paths)->toHaveCount(1)
        ->and(is_file(realpath((string) array_key_first($paths)).'/script.blade.php'))->toBeTrue()
        ->and(array_values($paths)[0])->toBe(resource_path('views/vendor/livewire-google-analytics'));
});

it('has exactly two publish groups', function () {
    expect(ServiceProvider::publishableGroups())
        ->toContain('livewire-google-analytics-js', 'livewire-google-analytics-views')
        ->and(ServiceProvider::pathsToPublish(GoogleAnalyticsServiceProvider::class))->toHaveCount(2);
});

it('reads no settings, so there is nothing a cached config can break', function () {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/src'));
    $checked = 0;

    foreach ([...iterator_to_array($files), new SplFileInfo(dirname(__DIR__, 2).'/resources/views/script.blade.php')] as $file) {
        if (! $file->isFile()) {
            continue;
        }

        $checked++;

        expect((string) file_get_contents($file->getPathname()))
            ->not->toMatch('/\b(env|config)\s*\(/', $file->getFilename());
    }

    expect($checked)->toBeGreaterThanOrEqual(3);
});
