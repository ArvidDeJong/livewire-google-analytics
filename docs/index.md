---
title: "Home"
nav_order: 1
description: "Send Google Analytics 4 events from Laravel Livewire components: call trackLead() or trackEvent() in PHP and a listener in your layout passes it to gtag()."
permalink: /
---

# Livewire Google Analytics

`darvis/livewire-google-analytics` sends Google Analytics 4 (GA4) events from Laravel Livewire components. You call a PHP method such as `$this->trackLead()` in a component, and a small script in your layout passes the event to `gtag()`, the JavaScript function of Google's tag.

It is for Laravel developers who already have Google Analytics on their site and want to track what happens inside Livewire components: a sent contact form, a newsletter signup, a purchase.

## What it does

- **A trait** with `trackEvent()`, `trackLead()`, `trackNewsletterSignup()` and `trackCustomEvent()`, each with an `…AfterRedirect()` variant for an action that ends in a redirect.
- **A listener script**, as a Blade view or as a publishable JavaScript file, that forwards the event to `gtag()`.
- **No lost events**: an event tracked before `gtag` exists waits and is sent once it does, and an event tracked right before a redirect is sent on the next page.
- **Parameters as data**: they reach the browser as JSON, never as JavaScript source, so form input cannot break out of a script.

## What it does not do

- It does **not load Google Analytics**. There is no `gtag.js` in the package; you put Google's own tag in your layout.
- It has **no measurement id setting**, no config file and no environment variable. The id lives in your Google tag.
- It does **no consent handling**. It never asks for consent and never checks it. It only calls `window.gtag` when that function exists; what `gtag` does with the event is up to your Google tag and your consent tool.
- It sends **nothing from the server**. Every event goes through the visitor's browser.

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- Livewire 3 or 4
- A Google tag (`gtag.js`) on the page; the package does not load it

## Install

```bash
composer require darvis/livewire-google-analytics
```

Then include the listener once in your layout, before `</body>`:

```blade
@include('livewire-google-analytics::script')
```

and add the `TracksAnalytics` trait to a component. [Installation](installation.md) has every step and a check that it works.

## Pages

- [Installation](installation.md): the package, your Google tag, the listener, and how to check that an event arrives
- [Quick start](quickstart.md): one complete contact form, from the layout to the event in GA4
- [Tracking events](usage.md): every method, the event it sends, and where to call it
- [How it works](concepts.md): the browser event, the queue for events without gtag, and the redirect
- [Examples](examples.md): a newsletter signup, a purchase with and without a redirect, a download
- [Testing](testing.md): assert on the event in your own test suite, without a browser
- [Troubleshooting](troubleshooting.md): no event, an event counted twice, missing parameters, error messages
- [FAQ](faq.md): short answers to common questions

## Links

- [Source on GitHub](https://github.com/ArvidDeJong/livewire-google-analytics)
- [Packagist](https://packagist.org/packages/darvis/livewire-google-analytics)
- [Changelog](https://github.com/ArvidDeJong/livewire-google-analytics/blob/main/CHANGELOG.md)
- [Report an issue](https://github.com/ArvidDeJong/livewire-google-analytics/issues)
