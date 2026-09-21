---
name: livewire-google-analytics-development
description: Work with darvis/livewire-google-analytics. Use it to send Google Analytics 4 events from Livewire components, set up the listener in the layout, find out why an event does not arrive or arrives twice, and test that a component tracks the right event.
---

# darvis/livewire-google-analytics development

## When to use this skill

Use this skill when a Livewire component in an application with `darvis/livewire-google-analytics` has to track a conversion or another GA4 event, when events are missing or doubled in Google Analytics, or when you write tests around tracking.

## How an event travels

1. A component with the `TracksAnalytics` trait calls `trackEvent()` or one of the helpers.
2. The trait calls Livewire's `$this->dispatch('ga:event', name: $name, params: $params)`. The event is part of the JSON response of that request.
3. Livewire fires a `CustomEvent` named `ga:event` that bubbles to `window`, with `detail.name` and `detail.params`.
4. The listener from `@include('livewire-google-analytics::script')` calls `window.gtag('event', name, params || {})`, or keeps the event in a queue until `window.gtag` exists.
5. An `…AfterRedirect()` method skips steps 2 and 3: it stores the event in the session under `livewire-google-analytics.events`, and the Blade view on the next page pulls it and hands it to the same queue.

The server sends nothing to Google. The package has no config file, no measurement id and no environment variable, and it does not load `gtag.js`.

| Situation | What happens |
| --- | --- |
| `window.gtag` is not a function yet | the event waits: at most 50 events, at most 30 minutes, sent in order when `gtag` appears (checked on the next event and once a second while something waits) |
| `window.gtag` never appears | the waiting events are gone when the page unloads; nothing throws |
| The queue is off (`['queue' => false]` on the include, or `window.livewireGoogleAnalytics = { queue: false }`) | an event without `gtag` is dropped |
| `trackEvent()` and a full page `redirect()` in one action | the event fires on the page that is going away; it may or may not reach Google |
| `trackEventAfterRedirect()` | not dispatched; sent once by the Blade view on the next page that renders it, within 30 minutes |
| `trackEventAfterRedirect()` without a started session | dispatched like `trackEvent()` |
| `trackEventAfterRedirect()` and the next page only loads the published file | the event stays in the session until a page with the view is rendered |
| The event has no `name`, or an empty one | ignored |
| `params` is missing | `gtag()` gets an empty object |
| The script runs a second time (`wire:navigate`, or the view and the published file together) | one listener and one queue per window: `window.livewireGoogleAnalyticsListening` and `window.livewireGoogleAnalyticsState` |
| The listener is not in the layout | the browser event fires and nothing listens |

## The methods

All `protected`, all return `void`, none throws.

| Method | Event name | Params |
| --- | --- | --- |
| `trackEvent(string $name, array $params = [])` | `$name` | unchanged |
| `trackLead(array $params = [])` | `generate_lead` | unchanged |
| `trackNewsletterSignup(array $params = [])` | `sign_up` | `['method' => 'newsletter']` merged with the params; your own `method` wins |
| `trackCustomEvent(string $eventName, array $params = [])` | `'ga_'.$eventName` | unchanged |

Each has an `…AfterRedirect()` variant with the same arguments, event name and params: `trackEventAfterRedirect()`, `trackLeadAfterRedirect()`, `trackNewsletterSignupAfterRedirect()`, `trackCustomEventAfterRedirect()`.

The package does not validate names or params.

## Setting up the layout

```blade
    @livewireScripts
    @include('livewire-google-analytics::script')
</body>
```

Once, in every layout that renders a tracking component. The host app's own Google tag goes in the `<head>`; Google's snippet defines `window.gtag` inline, so it exists before `gtag.js` has loaded.

With a Content Security Policy that forbids inline scripts:

```bash
php artisan vendor:publish --tag=livewire-google-analytics-js
```

and load `/vendor/livewire-google-analytics/google-analytics.js`. That file also logs `[GA4] ...` messages at the `debug` level; the Blade view is silent. `--tag=livewire-google-analytics-views` publishes the view to `resources/views/vendor/livewire-google-analytics`.

## Scenarios

```php
// A conversion, after the work is done.
public function submit(): void
{
    $validated = $this->validate();
    ContactRequest::create($validated);

    $this->trackLead(['form_name' => 'contact_form']);
}

// A recommended GA4 event with its own parameters.
$this->trackEvent('purchase', [
    'transaction_id' => (string) $order->id,
    'value' => (float) $order->total,
    'currency' => 'EUR',
]);

// A purchase followed by a redirect: carried to the next page, which must include the Blade view.
$this->trackEventAfterRedirect('purchase', ['transaction_id' => (string) $order->id, 'currency' => 'EUR']);
$this->redirectRoute('orders.thanks', $order);

// A project specific event: arrives as ga_download_brochure.
$this->trackCustomEvent('download_brochure', ['brochure_name' => $brochure->title]);
```

## Pitfalls

- **Never build `gtag()` calls with `$this->js()`.** That makes JavaScript source out of PHP values; a quote breaks it and visitor input can run code. The params of the trait are JSON from start to end.
- **Never track in `render()`**, in `updated()` hooks or in a loop: the event is sent on every request or iteration.
- **Track below `validate()` and below the work.** `validate()` throws, so a rejected form is never counted.
- **Don't make the methods public** and don't wrap them in a public method that takes the event name or params as arguments: every public Livewire method can be called from the browser.
- **A full page redirect in the same action**: use the `…AfterRedirect()` variant. Livewire fires a dispatched event on the page that is going away.
- **Never track one event with both variants.** The normal one is dispatched, the other is carried, and Google counts two.
- **The carried event needs the Blade view on the next page.** The published file is static and cannot read the session.
- **Never push to `dataLayer` or define `window.gtag` to make events "arrive earlier".** The listener already queues; a home made `gtag` on a page with only Google Tag Manager produces entries GTM does not expect.
- **A published view or file is a copy.** After a package update with a fix in the listener, publish again with `--force` or remove the copy.
- **Don't look for a config file.** There is none; the measurement id lives in the host app's Google tag.
- **No personal data in params**: no names, e-mail addresses, phone numbers or typed text.
- The `[GA4]` console messages only exist in the published JavaScript file, and Chrome hides `debug` messages until "Verbose" is on.

## Testing

```php
use Livewire\Livewire;

Livewire::test(ContactForm::class)
    ->set('email', 'visitor@example.com')
    ->call('submit')
    ->assertDispatched('ga:event', name: 'generate_lead', params: ['form_name' => 'contact_form']);

Livewire::test(ContactForm::class)
    ->call('submit')
    ->assertHasErrors('email')
    ->assertNotDispatched('ga:event');
```

- `params` is compared as a whole array; include `method` for `trackNewsletterSignup()`.
- No browser, no Google Analytics and no HTTP fake are needed: the package makes no request.
- `Livewire::test()` runs without middleware, so there is no started session and an `…AfterRedirect()` method falls back to dispatching. Start the session to test the carried event:

```php
session()->start();

Livewire::test(Checkout::class)
    ->call('completePurchase')
    ->assertRedirect(route('orders.thanks'))
    ->assertNotDispatched('ga:event');

expect(session('livewire-google-analytics.events.0.name'))->toBe('purchase');

$this->get(route('orders.thanks'))->assertSee('"name":"purchase"', false);      // sent by the view
$this->get(route('orders.thanks'))->assertDontSee('"name":"purchase"', false);  // once
```

- In the browser, log the raw event with `window.addEventListener('ga:event', e => console.log(e.detail, typeof window.gtag))`.
