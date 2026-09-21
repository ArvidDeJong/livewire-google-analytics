<?php

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

function packagePath(string $path): string
{
    return dirname(__DIR__, 2).'/'.$path;
}

/**
 * The JavaScript of the Blade view, without the script tag.
 */
function viewScript(): string
{
    preg_match('/<script>(.*)<\/script>/s', view('livewire-google-analytics::script')->render(), $match);

    return $match[1] ?? '';
}

function fileScript(): string
{
    return (string) file_get_contents(packagePath('resources/js/google-analytics.js'));
}

/**
 * Run listener scripts in Node, one after the other against the same fake window.
 *
 * @return array{listens_to: list<string>, calls: list<array<int, mixed>>, errors: list<string>}
 */
function runListener(string ...$scripts): array
{
    $files = [];

    foreach ($scripts as $script) {
        $files[] = $file = (string) tempnam(sys_get_temp_dir(), 'ga-listener-');
        file_put_contents($file, $script);
    }

    try {
        $process = new Process(['node', packagePath('tests/Fixtures/run-listener.js'), ...$files]);
        $process->mustRun();

        return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    } finally {
        array_map('unlink', $files);
    }
}

function nodeIsMissing(): bool
{
    return (new ExecutableFinder)->find('node') === null;
}

it('renders the view as one inline script without Blade leftovers', function () {
    $html = view('livewire-google-analytics::script')->render();

    expect(substr_count($html, '<script>'))->toBe(1)
        ->and(substr_count($html, '</script>'))->toBe(1)
        ->and(trim($html))->toStartWith('<script>')->toEndWith('</script>')
        ->and($html)->not->toContain('{{')
        ->and($html)->not->toContain('@include');
});

it('renders the same script for every request, because nothing from the app goes into it', function () {
    config(['app.name' => '</script><script>alert(1)</script>']);
    request()->merge(['name' => '</script><script>alert(1)</script>']);

    expect(view('livewire-google-analytics::script')->render())
        ->toBe(view('livewire-google-analytics::script', ['name' => '</script>'])->render())
        ->not->toContain('alert');
});

it('listens for ga:event on the window in the view and in the publishable file', function (string $script) {
    expect($script)
        ->toContain("window.addEventListener('ga:event'")
        ->toContain("typeof window.gtag !== 'function'")
        ->toContain("window.gtag('event', name, params || {})");
})->with([
    'view' => fn () => viewScript(),
    'file' => fn () => fileScript(),
]);

it('keeps the view silent and lets only the publishable file write to the console', function () {
    expect(viewScript())->not->toContain('console.');

    expect(fileScript())
        ->toContain("console.debug('[GA4] Event tracked:'")
        ->toContain("console.debug('[GA4] gtag not available, skipping event:'")
        ->toContain("console.warn('[GA4] Event dispatched without name:'")
        ->toContain("console.debug('[GA4] Livewire Google Analytics listener initialized')");
});

it('forwards a named event to gtag and drops everything else', function (string $script) {
    $result = runListener($script);

    expect($result['listens_to'])->toBe(['ga:event'])
        ->and($result['errors'])->toBe([])
        ->and($result['calls'])->toBe([
            // The event sent while gtag did not exist is gone; it is not queued.
            ['event', 'generate_lead', ['form_name' => 'contact', 'value' => "'); alert(1); //"]],
            ['event', 'login', []],
        ]);
})->with([
    'view' => fn () => viewScript(),
    'file' => fn () => fileScript(),
])->skip(fn () => nodeIsMissing(), 'Node is not installed.');

it('sends an event once when the script runs again on the same page', function (array $scripts) {
    // wire:navigate runs every script in the body again on each visit, while the window and its
    // listeners stay. Without a guard the second page sends every event twice, the third three times.
    $result = runListener(...$scripts);

    expect($result['listens_to'])->toBe(['ga:event'])
        ->and($result['calls'])->toHaveCount(2);
})->with([
    'the view, three visits' => fn () => [viewScript(), viewScript(), viewScript()],
    'the file, twice' => fn () => [fileScript(), fileScript()],
    'the view and the file together' => fn () => [viewScript(), fileScript()],
])->skip(fn () => nodeIsMissing(), 'Node is not installed.');
