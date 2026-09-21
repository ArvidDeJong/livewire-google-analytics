<?php

use Darvis\LivewireGoogleAnalytics\Tests\TestCase;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

uses(TestCase::class)->in('Feature');

function packagePath(string $path): string
{
    return dirname(__DIR__).'/'.$path;
}

/**
 * The JavaScript of the Blade view, without the script tag.
 *
 * @param  array<string, mixed>  $data
 */
function viewScript(array $data = []): string
{
    preg_match('/<script>(.*)<\/script>/s', view('livewire-google-analytics::script', $data)->render(), $match);

    return $match[1] ?? '';
}

function fileScript(): string
{
    return (string) file_get_contents(packagePath('resources/js/google-analytics.js'));
}

function nodeIsMissing(): bool
{
    return (new ExecutableFinder)->find('node') === null;
}

/**
 * Run listener scripts in Node against one fake window with a fake clock.
 * The steps are described in tests/Fixtures/run-listener.js.
 *
 * @param  list<string>  $scripts
 * @param  list<array{0: string, 1: mixed}>  $steps
 * @param  array<string, mixed>|null  $settings
 * @return array{listens_to: list<string>, calls: list<array<int, mixed>>, errors: list<string>, messages: list<array<int, mixed>>, timers: int, stored: array<string, string>}
 */
function runListener(array $scripts, array $steps, ?array $settings = null, string $storage = 'memory'): array
{
    $files = [];

    foreach ($scripts as $script) {
        $files[] = $file = (string) tempnam(sys_get_temp_dir(), 'ga-listener-');
        file_put_contents($file, $script);
    }

    $scenario = (string) tempnam(sys_get_temp_dir(), 'ga-scenario-');
    file_put_contents($scenario, json_encode(['scripts' => $files, 'settings' => $settings, 'storage' => $storage, 'steps' => $steps], JSON_THROW_ON_ERROR));

    try {
        $process = new Process(['node', packagePath('tests/Fixtures/run-listener.js'), $scenario]);
        $process->mustRun();

        return json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    } finally {
        array_map('unlink', [...$files, $scenario]);
    }
}
