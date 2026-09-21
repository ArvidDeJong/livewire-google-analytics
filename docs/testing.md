---
title: "Testing"
nav_order: 7
description: "Test in your own Laravel app that a Livewire component tracks the right GA4 event, with assertDispatched and the session, without a browser or Google."
---

# Testing

## Test a component that tracks an event

The trait dispatches a Livewire event, so Livewire's own test helpers are all you need. No browser is involved and nothing is sent to Google: the package makes no request from the server.

`tests/Feature/ContactFormTest.php`, for the component of the [Quick start](quickstart.md), written with Pest:

```php
<?php

use App\Livewire\ContactForm;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('tracks a lead after the contact form was sent', function () {
    Mail::fake();

    Livewire::test(ContactForm::class)
        ->set('name', 'Test Visitor')
        ->set('email', 'visitor@example.com')
        ->set('message', 'A message of more than ten characters.')
        ->call('submit')
        ->assertDispatched('ga:event', name: 'generate_lead', params: [
            'form_name' => 'contact_form',
        ]);
});

it('tracks nothing when the form is rejected', function () {
    Livewire::test(ContactForm::class)
        ->call('submit')
        ->assertHasErrors('email')
        ->assertNotDispatched('ga:event');
});
```

`Livewire::test()` runs the component without a browser, `call('submit')` runs the action, and `assertDispatched()` checks the browser event that the trait added to the response. `Mail::fake()` keeps the test from sending mail.

`params` is compared as a whole. Assert on the complete array your component sends, including `'method' => 'newsletter'` for `trackNewsletterSignup()`.

## Test an event that is carried over a redirect

The `…AfterRedirect()` methods put the event in the session. `Livewire::test()` sends its requests without middleware, so nothing starts the session; start it yourself, or the trait falls back to dispatching the event.

```php
it('carries the purchase over the redirect', function () {
    session()->start();

    Livewire::test(Checkout::class)
        ->call('completePurchase')
        ->assertRedirect(route('orders.thanks', 1))
        ->assertNotDispatched('ga:event');

    expect(session('livewire-google-analytics.events.0.name'))->toBe('purchase')
        ->and(session('livewire-google-analytics.events.0.params.currency'))->toBe('EUR');
});
```

Each stored event has an `id`, a `name`, its `params` and the time it was tracked in `at`. To check the other half, request the next page and look for the event in the response:

```php
$this->get(route('orders.thanks', 1))->assertSee('"name":"purchase"', false);
$this->get(route('orders.thanks', 1))->assertDontSee('"name":"purchase"', false);   // a reload
```

## Check by hand in the browser

"Check that it works" on [Installation](installation.md) walks through this. In short: the Blade view writes nothing to the console. To see what happens, either load the published JavaScript file, which logs every step, or paste this in the console before you submit the form:

```js
window.addEventListener('ga:event', (event) => console.log('ga:event', event.detail));
```

You can also send a test event by hand. With `debug_mode` it shows up in DebugView when the listener and your Google tag are in place:

```js
window.dispatchEvent(new CustomEvent('ga:event', {
    detail: { name: 'test_event', params: { debug_mode: true } },
}));
```

After a carried event, `sessionStorage.getItem('livewire-google-analytics.sent')` shows the ids the listener has accepted in this tab. To make a page send its carried events again while you are testing, remove that key and do a full reload of a cached copy; a normal reload asks the server, which hands the events out only once.

`typeof window.gtag` tells you whether the Google tag is there. When it prints `"undefined"`, events wait in `window.livewireGoogleAnalyticsState.waiting` until it exists.

## See the event in Google Analytics

**Admin**, then **DebugView** under "Data display", shows each event of a browser in debug mode, with its parameters. Google's tag is in debug mode when you open the site through [Tag Assistant](https://tagassistant.google.com), when the tag is configured with `gtag('config', 'G-XXXXXXX', { debug_mode: true })`, or for one event when you add `'debug_mode' => true` to its parameters:

```php
$this->trackLead(['form_name' => 'contact_form', 'debug_mode' => true]);
```

Remove it again when you are done. Which parameters show up in the other GA4 reports is a setting of your GA4 property, not of the package.
