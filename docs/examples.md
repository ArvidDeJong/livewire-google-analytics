---
title: "Examples"
nav_order: 6
description: "Livewire actions that track a newsletter signup, a purchase with and without a redirect, a file download and a step in a longer form with GA4 events."
---

# Examples

Every example is a method of a Livewire component that uses the `TracksAnalytics` trait, and assumes the listener is in your layout, see [Installation](installation.md). A complete contact form, with every file, is on [Quick start](quickstart.md).

## Newsletter signup

```php
public function subscribe(): void
{
    $this->validate(['email' => 'required|email']);

    Subscriber::firstOrCreate(['email' => $this->email]);

    $this->trackNewsletterSignup(['source' => 'footer']);
    // sign_up {method: 'newsletter', source: 'footer'}

    $this->reset('email');
}
```

`Subscriber` is your own model. The event is `sign_up`, with `method` set to `newsletter` by the package and `source` by you.

## Purchase

```php
public function completePurchase(): void
{
    $order = $this->cart->checkout();

    $this->trackEvent('purchase', [
        'transaction_id' => (string) $order->id,
        'value' => (float) $order->total,
        'currency' => 'EUR',
    ]);

    $this->orderId = $order->id;
}
```

`$this->cart` stands for your own checkout code. The component stays on the page and shows the confirmation itself, so the normal `trackEvent()` is the right one.

## Purchase, then a redirect

```php
public function completePurchase(): void
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

The event waits in the session and the listener view on the thank-you page sends it, once; a reload of that page sends nothing. The thank-you page has to include `livewire-google-analytics::script`. The published JavaScript file alone cannot do this.

With a plain `trackEvent()` here, the browser event would fire on the checkout page while the browser is already leaving it.

## File download

```php
public function download(int $brochureId)
{
    $brochure = Brochure::findOrFail($brochureId);

    $this->trackCustomEvent('download_brochure', [
        'brochure_name' => $brochure->title,
    ]);
    // ga_download_brochure {brochure_name: '...'}

    return response()->download($brochure->path);
}
```

`Brochure` is your own model. The browser event `ga_download_brochure` is part of the same Livewire response as the download.

## A step in a longer form

```php
public function nextStep(): void
{
    $this->validate($this->rulesForStep($this->step));

    $this->trackEvent('form_step_completed', [
        'form_name' => 'quote_request',
        'step' => $this->step,
    ]);

    $this->step++;
}
```

`rulesForStep()` is your own method. Every completed step sends one `form_step_completed` event with the step number, which shows where visitors give up.
