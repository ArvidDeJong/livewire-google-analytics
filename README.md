# Livewire Google Analytics

[![Latest version](https://img.shields.io/packagist/v/darvis/livewire-google-analytics.svg)](https://packagist.org/packages/darvis/livewire-google-analytics)
[![Tests](https://github.com/ArvidDeJong/livewire-google-analytics/actions/workflows/tests.yml/badge.svg)](https://github.com/ArvidDeJong/livewire-google-analytics/actions/workflows/tests.yml)
[![PHP version](https://img.shields.io/packagist/dependency-v/darvis/livewire-google-analytics/php.svg)](https://packagist.org/packages/darvis/livewire-google-analytics)
[![License](https://img.shields.io/packagist/l/darvis/livewire-google-analytics.svg)](LICENSE)

Send Google Analytics 4 events from Laravel Livewire components. You call a PHP method such as `$this->trackLead()` in a component, and a listener script in your layout passes the event to `gtag()`. The package does not load Google Analytics, has no measurement id setting and does no consent handling: your own Google tag stays where it is.

## Features

- **One line per event** - `trackLead()`, `trackNewsletterSignup()`, `trackCustomEvent()`, and `trackEvent()` for everything else
- **Parameters as data** - the event name and parameters reach the browser as JSON, never as JavaScript source, so form input cannot break out of a script
- **Waits for the Google tag** - an event tracked before `window.gtag` exists waits (at most 50 events, at most 30 minutes) and is sent in order once it does; opt out with `['queue' => false]`
- **Survives a redirect** - `trackEventAfterRedirect()` carries the event to the next page through the session, and it is sent once
- **Fails quietly** - without `gtag` for the whole visit nothing is sent, nothing throws and the Livewire action carries on
- **No double events** - the listener registers once per window, also with `wire:navigate`
- **Nothing to configure** - no config file, no environment variable, no migration

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- Livewire 3 or 4
- A Google tag (`gtag.js`) on the page; the package does not load it

## Installation

```bash
composer require darvis/livewire-google-analytics
```

Include the listener once in your layout, before `</body>`:

```blade
@include('livewire-google-analytics::script')
```

## Quick start

`app/Livewire/ContactForm.php`:

```php
<?php

namespace App\Livewire;

use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;

class ContactForm extends Component
{
    use TracksAnalytics;

    public string $email = '';

    public function submit(): void
    {
        $this->validate(['email' => 'required|email']);

        // Save or send the message here.

        $this->trackLead(['form_name' => 'contact_form']);
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
```

After a valid submit the browser calls `gtag('event', 'generate_lead', {form_name: 'contact_form'})`. Track in an action, after the work succeeded, and not in `render()`, which runs on every request.

In an action that ends in a redirect, use the `…AfterRedirect()` variant; the page after the redirect has to include the Blade view:

```php
$this->trackEventAfterRedirect('purchase', ['transaction_id' => 'T12345', 'value' => 25.99, 'currency' => 'EUR']);

$this->redirectRoute('orders.thanks');
```

## Documentation

The full documentation lives on the [documentation site](https://arviddejong.github.io/livewire-google-analytics/):

- [Installation](https://arviddejong.github.io/livewire-google-analytics/installation.html): your Google tag, the listener, and a check that an event arrives
- [Quick start](https://arviddejong.github.io/livewire-google-analytics/quickstart.html): one complete contact form with every file
- [Tracking events](https://arviddejong.github.io/livewire-google-analytics/usage.html): every method and where to call it
- [How it works](https://arviddejong.github.io/livewire-google-analytics/concepts.html): the browser event, the queue, the redirect
- [Examples](https://arviddejong.github.io/livewire-google-analytics/examples.html): newsletter, purchase, download
- [Testing](https://arviddejong.github.io/livewire-google-analytics/testing.html): `assertDispatched('ga:event', ...)` in your own test suite
- [Troubleshooting](https://arviddejong.github.io/livewire-google-analytics/troubleshooting.html): no event, double events, error messages
- [FAQ](https://arviddejong.github.io/livewire-google-analytics/faq.html)

## Laravel Boost

The package ships a guideline and a skill for [Laravel Boost](https://github.com/laravel/boost), so an AI assistant in your app knows the API and the pitfalls. Run `php artisan boost:install`, or `php artisan boost:update --discover` in a project that already uses Boost.

## Testing

```bash
composer test      # Pest
composer lint      # Pint, check only; composer format fixes
composer analyse   # Larastan
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## Support the package

If darvis/livewire-google-analytics saves you time, a star on [GitHub](https://github.com/ArvidDeJong/livewire-google-analytics) or a favourite on [Packagist](https://packagist.org/packages/darvis/livewire-google-analytics) helps other developers find it.

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md).

## Security

Please report a vulnerability privately, as described in [SECURITY](SECURITY.md), not in the issue tracker.

## License

The MIT License (MIT). See [LICENSE](LICENSE).
