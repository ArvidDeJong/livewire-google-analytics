# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.3.0] - 2026-09-21

### Added
- `trackEventAfterRedirect()`, `trackLeadAfterRedirect()`, `trackNewsletterSignupAfterRedirect()` and
  `trackCustomEventAfterRedirect()` for an action that ends in a redirect. Livewire fires a dispatched
  browser event on the page that is going away, so a conversion tracked right before `redirect()` could
  get lost. These methods keep the event in the session (`livewire-google-analytics.events`) and the
  listener view on the next page sends it once; a reload sends nothing. Replace `trackEvent()` with
  the `AfterRedirect` variant in those actions, never call both. The page after the redirect has to
  include the Blade view: the published JavaScript file cannot read the session. Without a session the
  event is dispatched as before.
- `@include('livewire-google-analytics::script', ['queue' => false])` and
  `window.livewireGoogleAnalytics = { queue: false }` to turn the new queue off.

### Changed
- **Events that arrive before `window.gtag` exists are no longer dropped.** They wait in memory, at
  most 50 events and at most 30 minutes, and are sent in the order they were tracked as soon as `gtag`
  is there. This is how Google's own snippet behaves. On a site where a consent tool loads Google's
  tag after the visitor agreed, every event tracked before that moment used to be lost, so **your
  numbers can go up**. Without `gtag` for the whole visit (an ad blocker, no consent) nothing is sent,
  as before. The listener never pushes to `dataLayer` and never defines `gtag` itself. To keep
  dropping, use one of the two opt outs above.
- The publishable JavaScript file logs `[GA4] gtag not available, event is waiting: <name>` for a
  queued event. `[GA4] gtag not available, skipping event: <name>` is only logged with the queue off.
- If you published the view or the JavaScript file, publish it again to get the queue and the
  carried events: `php artisan vendor:publish --tag=livewire-google-analytics-views --force` (or
  `-js`). A published view from 1.2.0 or older silently ignores `trackEventAfterRedirect()` events.

### Fixed
- The documentation said an event tracked in the same action as a redirect "depends on the browser"
  and offered no way to make it arrive. It now describes what Livewire 3 and 4 do and shows the
  `AfterRedirect` methods.

## [1.2.0] - 2026-09-21

### Added
- A documentation site at https://arviddejong.github.io/livewire-google-analytics/ with an FAQ and an
  `llms.txt`, and a Laravel Boost guideline and skill in `resources/boost/`, so an AI assistant in
  your app knows the trait, the listener and the pitfalls.
- Tests for the listener script itself (run in Node against a fake window, for the Blade view and the
  publishable file), for the view, the publish tags and the rendering of the Boost guideline. The
  suite had four tests, all for the trait.
- The tooling of the other darvis packages: Pint, Larastan level 8, the `test`, `lint`, `format` and
  `analyse` composer scripts, issue forms, a security policy, a code of conduct and a `.gitattributes`
  that keeps development files out of the dist archive.

### Changed
- **PHP 8.2 and Laravel 11 are the new minimum.** PHP 8.1 and Laravel 10 are no longer allowed by
  `composer.json`; sites on Laravel 10 or PHP 8.1 stay on 1.1.x, which Composer does by itself.
  Livewire 3 and 4 are both still supported.
- The documentation is rewritten from the code. The old pages promised `[GA4]` messages in the browser
  console after `@include('livewire-google-analytics::script')`; the Blade view has never logged
  anything, only the publishable JavaScript file does. `QUICK_START.md`, `docs/01-…` to `06-…` and
  `examples/` are replaced by the site.
- CI calls the shared workflow in `ArvidDeJong/.github`: PHP 8.2 to 8.4 with Laravel 11, 12 and 13, on
  the lowest and the latest dependencies. `minimum-stability` is `stable`.
- The composer script `test-coverage` is gone; `lint` and `analyse` are new, the same names as in the
  other darvis packages.
- Security reports go through GitHub private vulnerability reporting, see `SECURITY.md`.

### Fixed
- **Every event was sent to Google Analytics more than once on a site with `wire:navigate`.** Livewire
  runs a script in the body again on every visit while `window` stays, so each page a visitor opened
  added another `ga:event` listener: a lead on the third page was counted three times. The same
  happened with the Blade view and the published JavaScript file on one page. The listener now
  registers once per window. Your numbers for these events go down to the real count after the
  update. If you published the view or the JavaScript file, publish it again:
  `php artisan vendor:publish --tag=livewire-google-analytics-js --force` (or `-views`).
- Links in the README and `CONTRIBUTING.md` pointed at `github.com/darvis`, which is not where this
  package lives.

## [1.1.0] - 2026-04-13

### Added
- Support for Laravel 13

### Changed
- Expanded `orchestra/testbench` dev support to include v10
- Expanded Pest and Pest Laravel plugin dev support to include v3 and v4

## [1.0.0] - 2026-01-27

### Added
- Initial release
- `TracksAnalytics` trait for clean GA4 event tracking
- `trackLead()` method for lead generation events
- `trackEvent()` method for custom events
- `trackNewsletterSignup()` method for newsletter signups
- `trackCustomEvent()` method for custom events with `ga_` prefix
- JavaScript event listener for browser-side GA4 integration
- Blade view for easy script inclusion
- Service provider with auto-discovery
- Support for Laravel 10, 11, 12
- Support for Livewire 3 & 4
- PHP 8.1+ support

[Unreleased]: https://github.com/ArvidDeJong/livewire-google-analytics/compare/v1.3.0...HEAD
[1.3.0]: https://github.com/ArvidDeJong/livewire-google-analytics/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/ArvidDeJong/livewire-google-analytics/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/ArvidDeJong/livewire-google-analytics/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/ArvidDeJong/livewire-google-analytics/releases/tag/v1.0.0
