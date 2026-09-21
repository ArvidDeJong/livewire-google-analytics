# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository. The conventions shared by every darvis package (language, releases, CI, docs site, Boost guidelines, public API policy) are in [../CLAUDE.md](../CLAUDE.md); this file only holds what is specific to this package.

## Package overview

`darvis/livewire-google-analytics` is a Laravel package (PHP 8.2+, Laravel 11/12/13, Livewire 3/4) that sends Google Analytics 4 events from Livewire components: a trait dispatches a browser event, a listener in the layout hands it to `gtag()`.

- Namespace: `Darvis\LivewireGoogleAnalytics\` → `src/`
- Service provider auto-registered via `extra.laravel.providers` in [composer.json](composer.json)
- No config file, no config key, no environment variable. The measurement id lives in the host app's own Google tag; the package does not load `gtag.js`.

## Architecture

- [TracksAnalytics](src/Traits/TracksAnalytics.php): `trackEvent()` is the one method that dispatches (`ga:event` with `name` and `params`); `trackLead()`, `trackNewsletterSignup()` and `trackCustomEvent()` only pick the name and the params.
- The listener exists twice with the same behaviour: [script.blade.php](resources/views/script.blade.php) is inline and silent, [google-analytics.js](resources/js/google-analytics.js) is publishable (for a Content Security Policy without inline scripts) and logs `[GA4]` messages. A change in one goes in the other; `tests/Feature/ListenerScriptTest.php` runs the same cases against both in Node and is skipped without Node.
- Both copies share the flag `window.livewireGoogleAnalyticsListening` and return when it is set. `wire:navigate` runs every script in the body again while `window` stays, so without the flag the n-th page sends every event n times. That was the bug up to 1.1.0.
- [GoogleAnalyticsServiceProvider](src/GoogleAnalyticsServiceProvider.php) registers the view namespace `livewire-google-analytics` and the publish tags `livewire-google-analytics-js` and `livewire-google-analytics-views`. Nothing else.
- The trait is only used by host app components, so PHPStan would skip it as unused. `tests/Fixtures/TrackingComponent.php` uses it and is in the PHPStan paths for that reason; don't remove it from `phpstan.neon.dist`.

## Conventions

- Never output a PHP value in the view, and never add a `@json`, `{{ }}` or config value to it. The view is static so that nothing from a request or a setting can end up in a script; event data goes through Livewire's dispatch as JSON. A test renders the view twice and compares.
- Never make the trait methods public. A public method of a Livewire component can be called from the browser with any arguments, which would let a visitor send events through the server. A test pins `protected`.
- Never queue or retry an event when `window.gtag` is missing. No `gtag` usually means no consent or an ad blocker; sending the event later would track someone who opted out.
- Don't introduce a config file within 1.x without a decision: "nothing to configure" is documented behaviour, and a measurement id in the package would mean the package loads the Google tag, which collides with consent tools.
- In `resources/boost/guidelines/core.blade.php` write the include directive as `@@include`. Boost renders the guideline with Blade; an unescaped `@include` puts the listener script in the guideline. `tests/Feature/BoostGuidelineTest.php` renders it.
- Keep the public API compatible within 1.x: the list is in `CONTRIBUTING.md`. It includes the event names the helpers send and the `ga_` prefix, because they are what site owners see in their GA4 reports and build conversions on.
