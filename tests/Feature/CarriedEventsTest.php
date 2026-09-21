<?php

use Darvis\LivewireGoogleAnalytics\Support\CarriedEvents;
use Darvis\LivewireGoogleAnalytics\Tests\Fixtures\TrackingComponent;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

const PAYLOAD = '\'); alert(1); //</script><script>alert("x")</script>';

beforeEach(function () {
    Route::middleware('web')->get('/thanks', fn () => view('livewire-google-analytics::script'));
    Route::get('/stateless', fn () => view('livewire-google-analytics::script'));
});

/**
 * Livewire::test() sends its requests without middleware, so nothing starts the session.
 */
function startSession(): void
{
    session()->start();
}

/**
 * @return list<array{name: string, params: array<string, mixed>}>
 */
function carriedInSession(): array
{
    return array_map(
        fn (array $event) => ['name' => $event['name'], 'params' => $event['params']],
        session(CarriedEvents::SESSION_KEY, []),
    );
}

it('still dispatches a normal event next to a redirect, as before', function () {
    // Livewire sends both effects in one response. Whether GA4 gets the event out before the page
    // unloads is up to the browser, which is why the AfterRedirect methods exist.
    $component = Livewire::test(TrackingComponent::class)
        ->call('purchaseThenRedirect')
        ->assertRedirect('/thanks')
        ->assertDispatched('ga:event', name: 'purchase', params: ['transaction_id' => 'T1']);

    expect($component->effects)->toHaveKeys(['redirect', 'dispatches'])
        ->and(session(CarriedEvents::SESSION_KEY))->toBeNull();
});

it('puts an event for after the redirect in the session instead of dispatching it', function (bool $navigate) {
    startSession();

    Livewire::test(TrackingComponent::class)
        ->call('carriedPurchase', $navigate)
        ->assertRedirect('/thanks')
        ->assertNotDispatched('ga:event');

    expect(carriedInSession())->toBe([
        ['name' => 'purchase', 'params' => ['transaction_id' => 'T2', 'value' => 25.99]],
    ]);
})->with(['a full page redirect' => false, 'a redirect with navigate' => true]);

it('has an AfterRedirect variant of every helper, with the same names and params', function () {
    startSession();

    Livewire::test(TrackingComponent::class)->call('carriedHelpers')->assertNotDispatched('ga:event');

    expect(carriedInSession())->toBe([
        ['name' => 'generate_lead', 'params' => ['form_name' => 'quote']],
        ['name' => 'sign_up', 'params' => ['method' => 'newsletter', 'source' => 'footer']],
        ['name' => 'ga_download_file', 'params' => []],
    ]);
});

it('keeps the AfterRedirect methods out of reach of the browser', function () {
    foreach (['trackEventAfterRedirect', 'trackLeadAfterRedirect', 'trackNewsletterSignupAfterRedirect', 'trackCustomEventAfterRedirect'] as $method) {
        expect((new ReflectionMethod(TrackingComponent::class, $method))->isProtected())->toBeTrue($method);
    }
});

it('dispatches the event after all when there is no session to carry it in', function () {
    Livewire::test(TrackingComponent::class)
        ->call('carriedPurchase')
        ->assertDispatched('ga:event', name: 'purchase', params: ['transaction_id' => 'T2', 'value' => 25.99]);

    expect(app('session.store')->isStarted())->toBeFalse()
        ->and(app('session.store')->has(CarriedEvents::SESSION_KEY))->toBeFalse();
});

it('sends the carried events from the view on the next page, once', function () {
    startSession();

    Livewire::test(TrackingComponent::class)->call('carriedPurchase');

    $first = $this->get('/thanks')->assertOk()->getContent();
    $reload = $this->get('/thanks')->assertOk()->getContent();

    expect($first)->toContain('"name":"purchase"')
        ->and($reload)->not->toContain('purchase')
        ->and(session(CarriedEvents::SESSION_KEY))->toBeNull();

    preg_match('/<script>(.*)<\/script>/s', $first, $match);

    $result = runListener([$match[1]], [['run', 0], ['advance', 3000], ['gtag', true], ['advance', 1000]]);

    // Through the same queue: gtag was not there yet when the page loaded.
    expect($result['calls'])->toBe([['event', 'purchase', ['transaction_id' => 'T2', 'value' => 25.99]]])
        ->and($result['errors'])->toBe([])
        ->and($result['timers'])->toBe(0);
})->skip(fn () => nodeIsMissing(), 'Node is not installed.');

it('sends carried events on a wire:navigate visit, and not again when that page comes back from the cache', function () {
    startSession();
    $plain = viewScript();

    Livewire::test(TrackingComponent::class)->call('carriedPurchase', true);
    preg_match('/<script>(.*)<\/script>/s', (string) $this->get('/thanks')->getContent(), $match);

    // The back button makes wire:navigate put the cached page back and run its scripts again.
    $result = runListener([$plain, $match[1]], [['run', 0], ['gtag', true], ['run', 1], ['run', 0], ['run', 1]]);

    expect($result['listens_to'])->toBe(['ga:event'])
        ->and($result['calls'])->toBe([['event', 'purchase', ['transaction_id' => 'T2', 'value' => 25.99]]]);
})->skip(fn () => nodeIsMissing(), 'Node is not installed.');

it('writes carried events into the script as data that cannot leave it', function () {
    startSession();

    Livewire::test(TrackingComponent::class)->set('location', PAYLOAD)->call('carriedLocation');

    CarriedEvents::carry(PAYLOAD, [PAYLOAD => PAYLOAD, 'broken' => "\xB1\x31"]);

    $html = (string) $this->get('/thanks')->getContent();

    expect(substr_count($html, '<script'))->toBe(1)
        ->and(substr_count($html, '</script'))->toBe(1)
        ->and($html)->not->toContain("'); alert")
        ->and($html)->not->toContain('alert("x")');

    preg_match('/<script>(.*)<\/script>/s', $html, $match);

    $calls = runListener([$match[1]], [['gtag', true], ['run', 0]])['calls'];

    expect($calls[0])->toBe(['event', 'generate_lead', ['location' => PAYLOAD]])
        ->and($calls[1][1])->toBe(PAYLOAD)
        ->and($calls[1][2][PAYLOAD])->toBe(PAYLOAD);
})->skip(fn () => nodeIsMissing(), 'Node is not installed.');

it('renders the view without a session and carries nothing', function () {
    expect(CarriedEvents::carry('purchase', []))->toBeFalse()
        ->and(CarriedEvents::pull())->toBe([]);

    $this->get('/stateless')->assertOk()->assertSee('var carried = [];', false);
});

it('keeps at most 50 carried events and drops what is older than 30 minutes', function () {
    startSession();

    $this->travelTo(now()->subMinutes(31));
    CarriedEvents::carry('stale', []);
    $this->travelBack();

    foreach (range(1, 55) as $number) {
        CarriedEvents::carry('event_'.$number, []);
    }

    $events = CarriedEvents::pull();

    expect($events)->toHaveCount(50)
        ->and($events[0]['name'])->toBe('event_6')
        ->and($events[49]['name'])->toBe('event_55')
        ->and(CarriedEvents::pull())->toBe([]);
});
