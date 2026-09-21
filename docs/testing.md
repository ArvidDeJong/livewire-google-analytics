---
title: Testing
nav_order: 6
description: "Assert in Pest or PHPUnit that a Livewire component dispatched the ga:event, and check in the browser and in GA4 DebugView that it arrived."
---

# Testing

## In your test suite

The trait dispatches a Livewire event, so Livewire's own assertions are all you need. No browser and no Google Analytics are involved.

```php
use App\Livewire\ContactForm;
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
            'lead_type' => 'contact',
        ]);
});

it('tracks nothing when the form is rejected', function () {
    Livewire::test(ContactForm::class)
        ->call('submit')
        ->assertHasErrors('email')
        ->assertNotDispatched('ga:event');
});
```

`params` is compared as a whole. Assert on the complete array your component sends, including `method` for `trackNewsletterSignup()`.

## In the browser

The Blade view is silent. To see what happens, either publish the JavaScript file, which logs every step (see [Installation](installation.md)), or paste this in the console before you submit the form:

```js
window.addEventListener('ga:event', (event) => console.log('ga:event', event.detail));
```

You can also send a test event by hand. It should show up in GA4 when the listener and your Google tag are in place:

```js
window.dispatchEvent(new CustomEvent('ga:event', {
    detail: { name: 'test_event', params: { source: 'console' } },
}));
```

`typeof window.gtag` tells you whether the Google tag is there. When it prints `"undefined"`, the listener drops every event.

## In Google Analytics

- **Realtime** shows an event within seconds to a minute.
- **Admin, DebugView** shows each event with its parameters. Turn debug mode on with the Google Analytics Debugger extension for Chrome, or with `gtag('config', 'G-XXXXXXX', { debug_mode: true })` in your tag.
- A custom parameter only appears in the standard reports after you register it as a custom dimension in GA4. That is a GA4 setting, not something the package can do.
