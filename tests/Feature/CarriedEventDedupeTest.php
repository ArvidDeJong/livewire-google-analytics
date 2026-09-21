<?php

use Darvis\LivewireGoogleAnalytics\Support\CarriedEvents;
use Illuminate\Support\Facades\Route;

/*
 * A page with carried events in its HTML can come back from the browser's HTTP cache or bfcache
 * after a full page load, when the window state is gone. The ids of the carried events that were
 * accepted are kept in sessionStorage. Every case runs against the view and the publishable file;
 * the file never has carried events of its own, so they are written into both the same way.
 */

const SENT_KEY = 'livewire-google-analytics.sent';

dataset('listeners with carried events', ['view', 'file']);

/**
 * The view or the file with these carried events written into it.
 *
 * @param  list<array{id: string, name: string, params: array<string, mixed>}>  $events
 */
function withCarried(string $listener, array $events): string
{
    $script = $listener === 'view' ? viewScript() : fileScript();

    $script = preg_replace('/var carried = \[\];/', 'var carried = '.json_encode($events, JSON_THROW_ON_ERROR).';', $script, 1, $count);

    expect($count)->toBe(1);

    return (string) $script;
}

/**
 * @return array{id: string, name: string, params: array<string, mixed>}
 */
function carriedPurchase(string $id): array
{
    return ['id' => $id, 'name' => 'purchase', 'params' => ['transaction_id' => $id]];
}

beforeEach(function () {
    if (nodeIsMissing()) {
        $this->markTestSkipped('Node is not installed.');
    }
});

it('sends a carried event once when the same page is loaded again from the browser cache', function (string $listener) {
    $result = runListener([withCarried($listener, [carriedPurchase('a1')])], [
        ['gtag', true],
        ['run', 0],
        ['reload', null],
        ['gtag', true],
        ['run', 0],
        ['reload', null],
        ['gtag', true],
        ['run', 0],
    ]);

    expect($result['errors'])->toBe([])
        ->and($result['calls'])->toBe([['event', 'purchase', ['transaction_id' => 'a1']]]);
})->with('listeners with carried events');

it('sends a carried event with another id after a reload', function (string $listener) {
    $result = runListener([withCarried($listener, [carriedPurchase('a1')]), withCarried($listener, [carriedPurchase('a1'), carriedPurchase('b2')])], [
        ['gtag', true],
        ['run', 0],
        ['reload', null],
        ['gtag', true],
        ['run', 1],
    ]);

    expect($result['calls'])->toBe([
        ['event', 'purchase', ['transaction_id' => 'a1']],
        ['event', 'purchase', ['transaction_id' => 'b2']],
    ]);
})->with('listeners with carried events');

it('remembers the id when the event is accepted, not when gtag sends it', function (string $listener) {
    // Reloaded while the event was still waiting for gtag. Marking it on send would queue it again
    // on every reload; marking it on acceptance means this one is lost, like an event that waited
    // longer than 30 minutes.
    $result = runListener([withCarried($listener, [carriedPurchase('a1')])], [
        ['run', 0],
        ['advance', 2000],
        ['reload', null],
        ['run', 0],
        ['gtag', true],
        ['advance', 2000],
    ]);

    expect($result['calls'])->toBe([])
        ->and($result['timers'])->toBe(0)
        ->and(json_decode($result['stored'][SENT_KEY]))->toBe(['a1']);
})->with('listeners with carried events');

it('stores ids only, under one key, and keeps the most recent 100', function (string $listener) {
    $batches = array_map(
        fn (array $numbers) => withCarried($listener, array_map(fn (int $number) => carriedPurchase('id-'.$number), $numbers)),
        array_chunk(range(1, 120), 40),
    );

    $result = runListener($batches, [['gtag', true], ['run', 0], ['reload', null], ['gtag', true], ['run', 1], ['run', 2]]);

    $ids = json_decode($result['stored'][SENT_KEY]);

    expect(array_keys($result['stored']))->toBe([SENT_KEY])
        ->and($result['calls'])->toHaveCount(120)
        ->and($ids)->toHaveCount(100)
        ->and($ids[0])->toBe('id-21')
        ->and($ids[99])->toBe('id-120')
        ->and($result['stored'][SENT_KEY])->not->toContain('purchase')
        ->and($result['stored'][SENT_KEY])->not->toContain('transaction_id');
})->with('listeners with carried events');

it('sends a carried event once per window when sessionStorage is missing or throws', function (string $listener, string $storage) {
    $result = runListener([withCarried($listener, [carriedPurchase('a1')])], [
        ['gtag', true],
        ['run', 0],
        // wire:navigate puts the cached page back: the window state still holds the id.
        ['run', 0],
        ['dispatch', ['name' => 'login', 'params' => ['method' => 'email']]],
    ], storage: $storage);

    expect($result['errors'])->toBe([])
        ->and($result['calls'])->toBe([
            ['event', 'purchase', ['transaction_id' => 'a1']],
            ['event', 'login', ['method' => 'email']],
        ])
        ->and($result['stored'])->toBe([])
        // Nothing at a level the console shows by default.
        ->and(array_filter($result['messages'], fn (array $message) => $message[0] !== 'debug'))->toBe([]);
})->with('listeners with carried events')->with(['absent', 'throws-get', 'throws-set', 'throws-access']);

it('survives something else than a list of ids under its key', function (string $listener, string $garbage) {
    $prime = "window.sessionStorage.setItem('".SENT_KEY."', ".json_encode($garbage).');';

    $result = runListener([$prime, withCarried($listener, [carriedPurchase('a1')])], [['gtag', true], ['run', 0], ['run', 1], ['reload', null], ['gtag', true], ['run', 1]]);

    expect($result['errors'])->toBe([])
        ->and($result['calls'])->toBe([['event', 'purchase', ['transaction_id' => 'a1']]])
        ->and(json_decode($result['stored'][SENT_KEY]))->toBe(['a1']);
})->with('listeners with carried events')->with(['{not json', '{"a1":true}', 'null', '"a1"', '[1,{"x":2},null]']);

it('never deduplicates a normal event', function (string $listener) {
    $login = ['name' => 'login', 'params' => ['method' => 'email'], 'id' => 'a1'];

    $result = runListener([withCarried($listener, [carriedPurchase('a1')])], [['gtag', true], ['run', 0], ['dispatch', $login], ['dispatch', $login]]);

    expect($result['calls'])->toHaveCount(3)
        ->and(json_decode($result['stored'][SENT_KEY]))->toBe(['a1']);
})->with('listeners with carried events');

it('does not send the events of a real rendered page again after a reload from the cache', function () {
    Route::middleware('web')->get('/thanks', fn () => view('livewire-google-analytics::script'));

    session()->start();
    CarriedEvents::carry('purchase', ['transaction_id' => 'T9']);

    preg_match('/<script>(.*)<\/script>/s', (string) $this->get('/thanks')->getContent(), $match);

    $result = runListener([$match[1]], [['gtag', true], ['run', 0], ['reload', null], ['gtag', true], ['run', 0]]);

    expect($result['calls'])->toBe([['event', 'purchase', ['transaction_id' => 'T9']]])
        ->and(json_decode($result['stored'][SENT_KEY]))->toHaveCount(1);
});
