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
4. The listener from `@include('livewire-google-analytics::script')` calls `window.gtag('event', name, params || {})`.

The server sends nothing to Google. The package has no config file, no measurement id and no environment variable, and it does not load `gtag.js`.

| Situation | What happens |
| --- | --- |
| `window.gtag` is not a function | the event is dropped, not queued; nothing throws |
| The event has no `name`, or an empty one | ignored |
| `params` is missing | `gtag()` gets an empty object |
| The script runs a second time (`wire:navigate`, or the view and the published file together) | it returns at once; one listener per window, flag `window.livewireGoogleAnalyticsListening` |
| The listener is not in the layout | the browser event fires and nothing listens |

## The methods

All `protected`, all return `void`, none throws.

| Method | Event name | Params |
| --- | --- | --- |
| `trackEvent(string $name, array $params = [])` | `$name` | unchanged |
| `trackLead(array $params = [])` | `generate_lead` | unchanged |
| `trackNewsletterSignup(array $params = [])` | `sign_up` | `['method' => 'newsletter']` merged with the params; your own `method` wins |
| `trackCustomEvent(string $eventName, array $params = [])` | `'ga_'.$eventName` | unchanged |

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

// A project specific event: arrives as ga_download_brochure.
$this->trackCustomEvent('download_brochure', ['brochure_name' => $brochure->title]);
```

## Pitfalls

- **Never build `gtag()` calls with `$this->js()`.** That makes JavaScript source out of PHP values; a quote breaks it and visitor input can run code. The params of the trait are JSON from start to end.
- **Never track in `render()`**, in `updated()` hooks or in a loop: the event is sent on every request or iteration.
- **Track below `validate()` and below the work.** `validate()` throws, so a rejected form is never counted.
- **Don't make the methods public** and don't wrap them in a public method that takes the event name or params as arguments: every public Livewire method can be called from the browser.
- **A redirect in the same action** puts the event and the navigation in one response. Track on the following page when the event matters.
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
- In the browser, log the raw event with `window.addEventListener('ga:event', e => console.log(e.detail, typeof window.gtag))`.
