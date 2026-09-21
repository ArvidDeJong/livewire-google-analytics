---
title: Home
nav_order: 1
description: "Google Analytics 4 event tracking for Laravel Livewire: call trackLead() or trackEvent() in a component and a small listener hands the event to gtag()."
permalink: /
---

# Livewire Google Analytics

`darvis/livewire-google-analytics` sends Google Analytics 4 events from Livewire components without JavaScript in your PHP.

- **A trait** with `trackEvent()`, `trackLead()`, `trackNewsletterSignup()` and `trackCustomEvent()`.
- **A listener** of a few lines that forwards the event to `gtag()`, as a Blade view or as a publishable file.
- **Parameters as data**: they travel as JSON in the Livewire response, so form input can never break out of a script.
- **Nothing to configure**: no config file, no measurement id. Your own Google tag stays where it is.

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- Livewire 3 or 4
- A Google tag (`gtag.js`) on the page; the package does not load it

## Install

```bash
composer require darvis/livewire-google-analytics
```

Include the listener once in your layout:

```blade
@livewireScripts
@include('livewire-google-analytics::script')
```

## In short

```php
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;

class ContactForm extends Component
{
    use TracksAnalytics;

    public function submit(): void
    {
        $this->validate();

        // Save or send the message first, then track.

        $this->trackLead(['form_name' => 'contact_form']);   // gtag('event', 'generate_lead', {...})
    }
}
```

## Pages

- [Installation](installation.md): the package, the listener and your own Google tag
- [Tracking events](usage.md): the four methods and where to call them
- [How it works](concepts.md): the browser event, the listener and what happens without gtag
- [Examples](examples.md): a contact form, a newsletter signup, a purchase and a download
- [Testing](testing.md): assert on the event in Pest, and check it in the browser
- [Troubleshooting](troubleshooting.md): no events, double events, missing parameters
- [FAQ](faq.md)

## Links

- [Source on GitHub](https://github.com/ArvidDeJong/livewire-google-analytics)
- [Packagist](https://packagist.org/packages/darvis/livewire-google-analytics)
- [Changelog](https://github.com/ArvidDeJong/livewire-google-analytics/blob/main/CHANGELOG.md)
- [Report an issue](https://github.com/ArvidDeJong/livewire-google-analytics/issues)
