---
title: Installation
nav_order: 2
description: "Install darvis/livewire-google-analytics, include the listener view once in your layout and keep your own Google tag; there is no config file."
---

# Installation

## 1. Install the package

```bash
composer require darvis/livewire-google-analytics
```

The service provider is discovered automatically. There is no config file, no migration and no environment variable.

## 2. Add your Google tag

The package does **not** load Google Analytics and knows nothing about your measurement id. Put the tag that Google gives you (Admin, Data streams, your stream, "View tag instructions") in the `<head>` of your layout, or load it through your consent tool.

Google's snippet defines `window.gtag` inline, so the function exists before `gtag.js` itself has loaded. When a consent tool adds the snippet later, the listener keeps the events that were tracked before that moment and sends them once `window.gtag` exists, see [How it works](concepts.md).

## 3. Include the listener

Add the view **once**, in the layout that every page with a tracked component uses:

```blade
    @livewireScripts
    @include('livewire-google-analytics::script')
</body>
```

The view renders one inline `<script>` that listens for the `ga:event` browser event on `window`. The only data it ever contains are events you tracked with an `…AfterRedirect()` method on the previous page, written as escaped JSON; see [Tracking events](usage.md).

### Without the queue

Events that arrive before `window.gtag` exists wait, at most 50 of them and at most 30 minutes. If you would rather drop them, as versions up to 1.2.0 did, turn the queue off in the include:

```blade
@include('livewire-google-analytics::script', ['queue' => false])
```

or, for the published file, before the script runs:

```html
<script>window.livewireGoogleAnalytics = { queue: false };</script>
```

The listener registers once per window. With `wire:navigate` Livewire runs a script in the body again on every visit; the listener notices it is already there and does nothing.

### A Content Security Policy without inline scripts

Publish the JavaScript file and load it as a normal script instead of the view:

```bash
php artisan vendor:publish --tag=livewire-google-analytics-js
```

```html
<script src="/vendor/livewire-google-analytics/google-analytics.js" defer></script>
```

The file is copied to `public/vendor/livewire-google-analytics/google-analytics.js`. It listens and queues exactly like the view, with two differences. It is static, so it cannot send the events of the `…AfterRedirect()` methods: those need the Blade view on the page after the redirect. And it writes to the browser console, which the view does not:

| Message | Level | When |
| --- | --- | --- |
| `[GA4] Livewire Google Analytics listener initialized` | debug | the script ran |
| `[GA4] Event tracked: <name> <params>` | debug | an event was handed to `gtag()` |
| `[GA4] gtag not available, event is waiting: <name>` | debug | `window.gtag` is not a function yet; the event is queued |
| `[GA4] gtag not available, skipping event: <name>` | debug | the same, with the queue turned off |
| `[GA4] Event dispatched without name: <detail>` | warn | the event had no `name` |

Chrome hides the `debug` level until you enable "Verbose" in the console's level filter.

A published file is a copy. Publish it again with `--force` after you update the package.

### Changing the view

```bash
php artisan vendor:publish --tag=livewire-google-analytics-views
```

copies the view to `resources/views/vendor/livewire-google-analytics/script.blade.php`. From then on your copy is used, also after an update of the package, so compare it with the package's view when you upgrade.

## 4. Use the trait

Continue with [Tracking events](usage.md).
