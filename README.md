# Livewire Google Analytics

[![Latest version](https://img.shields.io/packagist/v/darvis/livewire-google-analytics.svg)](https://packagist.org/packages/darvis/livewire-google-analytics)
[![Tests](https://github.com/ArvidDeJong/livewire-google-analytics/actions/workflows/tests.yml/badge.svg)](https://github.com/ArvidDeJong/livewire-google-analytics/actions/workflows/tests.yml)
[![PHP version](https://img.shields.io/packagist/dependency-v/darvis/livewire-google-analytics/php.svg)](https://packagist.org/packages/darvis/livewire-google-analytics)
[![License](https://img.shields.io/packagist/l/darvis/livewire-google-analytics.svg)](LICENSE)

Google Analytics 4 event tracking for Laravel Livewire components, without JavaScript in your PHP.

## Features

- **One line per event** - `trackLead()`, `trackNewsletterSignup()`, `trackCustomEvent()` and `trackEvent()` for everything else
- **Parameters as data** - they travel as JSON through Livewire, so form input can never break out of a script
- **Fails quietly** - no `gtag` on the page (ad blocker, no consent) means the event is dropped and the action carries on
- **No double events** - the listener registers once per window, also with `wire:navigate`
- **Nothing to configure** - no config file and no measurement id; your own Google tag stays where it is
- **Laravel Boost** - guideline and skill included, so an AI assistant in your app knows the API

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- Livewire 3 or 4
- A Google tag (`gtag.js`) on the page; the package does not load it

## Installation

```bash
composer require darvis/livewire-google-analytics
```

Include the listener once in your layout:

```blade
@livewireScripts
@include('livewire-google-analytics::script')
```

## Quick start

```php
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;

class ContactForm extends Component
{
    use TracksAnalytics;

    public function submit(): void
    {
        $validated = $this->validate();

        ContactRequest::create($validated);

        $this->trackLead(['form_name' => 'contact_form']);   // generate_lead
    }
}
```

```php
$this->trackEvent('purchase', ['transaction_id' => 'T12345', 'value' => 25.99, 'currency' => 'EUR']);
$this->trackNewsletterSignup(['source' => 'footer']);          // sign_up, method: newsletter
$this->trackCustomEvent('download_brochure', ['name' => 'Catalogue']);   // ga_download_brochure
```

Track in an action, after the work succeeded. Not in `render()`, which runs on every request.

## Documentation

The full documentation lives on the [documentation site](https://arviddejong.github.io/livewire-google-analytics/):

- [Installation](https://arviddejong.github.io/livewire-google-analytics/installation.html): the listener, your Google tag, a strict Content Security Policy
- [Tracking events](https://arviddejong.github.io/livewire-google-analytics/usage.html): the four methods and where to call them
- [How it works](https://arviddejong.github.io/livewire-google-analytics/concepts.html): the browser event and what happens without gtag
- [Examples](https://arviddejong.github.io/livewire-google-analytics/examples.html)
- [Testing](https://arviddejong.github.io/livewire-google-analytics/testing.html): `assertDispatched('ga:event', ...)` and checking in the browser
- [Troubleshooting](https://arviddejong.github.io/livewire-google-analytics/troubleshooting.html)

## Testing

```bash
composer test      # Pest
composer lint      # Pint, check only; composer format fixes
composer analyse   # Larastan
```

## Changelog

See [CHANGELOG](CHANGELOG.md).

## Contributing

See [CONTRIBUTING](CONTRIBUTING.md).

## Security

Please report a vulnerability privately, as described in [SECURITY](SECURITY.md), not in the issue tracker.

## License

The MIT License (MIT). See [LICENSE](LICENSE).
