---
title: Tracking events
nav_order: 3
description: "The four methods of the TracksAnalytics trait, the GA4 event each one sends, and where in a Livewire component you call them."
---

# Tracking events

Add the trait to a Livewire component:

```php
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;

class ContactForm extends Component
{
    use TracksAnalytics;
}
```

The trait adds four `protected` methods. All of them return nothing and none of them throws.

| Method | GA4 event name | Parameters |
| --- | --- | --- |
| `trackEvent(string $name, array $params = [])` | `$name` | `$params`, unchanged |
| `trackLead(array $params = [])` | `generate_lead` | `$params`, unchanged |
| `trackNewsletterSignup(array $params = [])` | `sign_up` | `['method' => 'newsletter']` merged with `$params` |
| `trackCustomEvent(string $eventName, array $params = [])` | `ga_` followed by `$eventName` | `$params`, unchanged |

## trackEvent()

Sends any event. Use the names and parameters of [Google's recommended events](https://developers.google.com/analytics/devguides/collection/ga4/reference/events) where one fits, because GA4 builds its reports on them.

```php
$this->trackEvent('purchase', [
    'transaction_id' => 'T12345',
    'value' => 25.99,
    'currency' => 'EUR',
]);
```

The package does not check the name or the parameters. GA4 has its own rules: a name starts with a letter and has only letters, digits and underscores.

## trackLead()

```php
$this->trackLead([
    'form_name' => 'contact_form',
    'lead_type' => 'contact',
]);
```

## trackNewsletterSignup()

```php
$this->trackNewsletterSignup(['source' => 'footer']);
// sign_up {method: 'newsletter', source: 'footer'}
```

Your own `method` wins: `trackNewsletterSignup(['method' => 'popup'])` sends `method: 'popup'`.

## trackCustomEvent()

Puts `ga_` in front of the name, so your own events sort together in the GA4 reports.

```php
$this->trackCustomEvent('download_brochure', ['brochure_name' => 'Catalogue']);
// ga_download_brochure {brochure_name: 'Catalogue'}
```

Pass the name without the prefix; `trackCustomEvent('ga_download')` sends `ga_ga_download`.

## Where to call them

- **In an action, after the work succeeded.** Call the method after `validate()` and after the record is saved or the mail is sent. `validate()` throws on invalid input, so a tracking call below it is never reached for a rejected form.
- **Not in `render()`.** Livewire runs `render()` on every request, so the event would be sent on every update of the component.
- **Several events in one action are fine.** Each call dispatches its own browser event.
- **Keep the methods protected.** A public method of a Livewire component can be called from the browser. If you wrap a tracking method in a public one, give that method fixed arguments.

## What to put in the parameters

Parameters are sent as JSON, so strings, numbers, booleans and nested arrays arrive as they are. Values from a form are safe for the page: they are never written into a script.

They are not safe for your privacy policy. Don't send names, e-mail addresses, phone numbers or free text a visitor typed; Google's terms forbid personal data in Analytics.
