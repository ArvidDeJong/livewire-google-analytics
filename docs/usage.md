---
title: "Tracking events"
nav_order: 4
description: "Every method of the TracksAnalytics trait, the GA4 event it sends, the AfterRedirect variants for an action that redirects, and where to call them."
---

# Tracking events

Add the trait to a Livewire component. A trait is a set of methods a class takes over with `use`:

```php
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;

class ContactForm extends Component
{
    use TracksAnalytics;
}
```

The trait adds four `protected` methods, and an `…AfterRedirect()` variant of each for an action that ends in a redirect. All of them return nothing and none of them throws.

| Method | GA4 event name | Parameters |
| --- | --- | --- |
| `trackEvent(string $name, array $params = [])` | `$name` | `$params`, unchanged |
| `trackLead(array $params = [])` | `generate_lead` | `$params`, unchanged |
| `trackNewsletterSignup(array $params = [])` | `sign_up` | `['method' => 'newsletter']` merged with `$params` |
| `trackCustomEvent(string $eventName, array $params = [])` | `ga_` followed by `$eventName` | `$params`, unchanged |

## trackEvent()

Sends any event. Google publishes a list of [recommended events](https://developers.google.com/analytics/devguides/collection/ga4/reference/events) with their names and parameters; use one of those where it fits.

```php
$this->trackEvent('purchase', [
    'transaction_id' => 'T12345',
    'value' => 25.99,
    'currency' => 'EUR',
]);
```

The package does not check the name or the parameters; they go to `gtag()` as you pass them. Google's [event naming rules](https://support.google.com/analytics/answer/13316687) say a name starts with a letter and has only letters, numbers and underscores.

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

Puts `ga_` in front of the name you pass.

```php
$this->trackCustomEvent('download_brochure', ['brochure_name' => 'Catalogue']);
// ga_download_brochure {brochure_name: 'Catalogue'}
```

Pass the name without the prefix; `trackCustomEvent('ga_download')` sends `ga_ga_download`.

## Before a redirect

```php
public function pay(): void
{
    $order = $this->cart->checkout();

    $this->trackEventAfterRedirect('purchase', [
        'transaction_id' => (string) $order->id,
        'value' => (float) $order->total,
        'currency' => 'EUR',
    ]);

    $this->redirectRoute('orders.thanks', $order);
}
```

Livewire sends the browser event and the redirect in one response, and fires the event on the page that is about to disappear. Whether the event still gets out is up to the browser, and an event that is waiting for `gtag` on that page is gone with it. The `…AfterRedirect()` methods don't dispatch. They keep the event in the session, and the listener view on the next page sends it, once.

| Method | Same event as |
| --- | --- |
| `trackEventAfterRedirect(string $name, array $params = [])` | `trackEvent()` |
| `trackLeadAfterRedirect(array $params = [])` | `trackLead()` |
| `trackNewsletterSignupAfterRedirect(array $params = [])` | `trackNewsletterSignup()` |
| `trackCustomEventAfterRedirect(string $eventName, array $params = [])` | `trackCustomEvent()` |

- **The next page needs the Blade view.** The published JavaScript file is static and cannot read the session. A site that only loads the file either includes the view on the page after the redirect, or does not redirect in the same action.
- **Use one or the other**, never `trackEvent()` and `trackEventAfterRedirect()` for the same event: it would be counted twice.
- **The event waits for the next page that renders the view**, whichever page that is, for at most 30 minutes. That also covers a redirect to a payment provider and back. If the action does not redirect after all, the event is sent on the visitor's next page view.
- **With `redirect(..., navigate: true)` both kinds work.** The window stays, so a normal `trackEvent()` reaches the listener; an `…AfterRedirect()` event arrives with the new page.
- **Without a session** (a stateless route) there is nowhere to keep the event, and it is dispatched like `trackEvent()`.

## Where to call them

- **In an action, after the work succeeded.** Call the method after `validate()` and after the record is saved or the mail is sent. `validate()` throws on invalid input, so a tracking call below it is never reached for a rejected form.
- **Not in `render()`.** Livewire runs `render()` on every request, so the event would be sent on every update of the component.
- **Several events in one action are fine.** Each call dispatches its own browser event.
- **Keep the methods protected.** A public method of a Livewire component can be called from the browser. If you wrap a tracking method in a public one, give that method fixed arguments.

## What to put in the parameters

Parameters reach the browser as JSON, so strings, numbers, booleans and nested arrays arrive as they are. Values from a form are safe for the page. A normal event travels inside Livewire's JSON response. An `…AfterRedirect()` event is written into the listener script of the next page as JSON in which every `<`, `>`, `&` and quote is escaped, so a value cannot end the script or run as code.

They are not safe for your privacy policy. Don't send names, e-mail addresses, phone numbers or free text a visitor typed. Everything in the parameters ends up in your Google Analytics property, so it has to fit your privacy statement.
