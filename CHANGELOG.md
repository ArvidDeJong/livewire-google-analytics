# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.3.2] - 2026-09-21

### Added
- A [Quick start](https://arviddejong.github.io/livewire-google-analytics/quickstart.html) page with one
  complete contact form: the layout, the component, its view and the route, each with its file name
  and imports.
- "Check that it works" on the installation page: two console checks, a test event you can paste,
  where to see it in GA4 DebugView, and what it means when you see something else.
- The index page now says what the package does not do: it does not load `gtag.js`, has no
  measurement id setting, does no consent handling and sends nothing from the server.

### Fixed
- The troubleshooting page quoted the error `View [livewire-google-analytics::script] not found` for a
  service provider that is not loaded. Laravel's real message in that case is
  `No hint path defined for [livewire-google-analytics].`; `View [script] not found.` is what a wrong
  view name gives. Both are on the page now, each with its fix.
- The testing page said debug mode is turned on with the "Google Analytics Debugger extension".
  Google's documentation names Tag Assistant and the `debug_mode` parameter, on the tag or on one
  event (`'debug_mode' => true` in the params), and DebugView is under Admin, "Data display".
- The usage page said form values "are never written into a script". That was true until 1.3.0: an
  event of an `…AfterRedirect()` method is written into the listener script of the next page, as
  JSON with every tag character and quote escaped. The page now says so.
- The concepts page said every method of the trait ends in a `dispatch()` call; the `…AfterRedirect()`
  methods don't. It also said Google's consent mode "does the rest" for queued events. The package
  knows nothing about consent; the page now says what it does: it sends a waiting event once
  `window.gtag` exists, and `['queue' => false]` turns that off.
- The installation page said the listener "does nothing" when the script runs again under
  `wire:navigate`. It does not listen a second time, but it does send the carried events of the new
  page. The Laravel Boost guideline said "all four" methods are protected; all eight are.
- Removed statements the package cannot vouch for: how fast GA4's Realtime report shows an event, why
  GA4 prefers recommended event names, and what registering a custom dimension does. The README and
  the docs now follow the same order as the other darvis packages, with a Laravel Boost section.

## [1.3.1] - 2026-09-21

### Fixed
- **An event carried over a redirect could be sent again when the browser took the page from its
  cache after a full page load**, for example with the back button on a site that lets the browser
  cache its HTML. The view only remembered the ids of sent events on `window`, which a full page load
  clears. It now also keeps them in `sessionStorage` under `livewire-google-analytics.sent`: ids
  only, the most recent 100, per tab, gone when the tab closes. When `sessionStorage` is missing or
  throws, the listener falls back to the `window` list without an error. An event that was still
  waiting for `gtag` when its cached page was reloaded is not queued a second time. Nothing to do,
  unless you published the view: a published copy does not get the fix, so publish it again with
  `php artisan vendor:publish --tag=livewire-google-analytics-views --force`. Do the same for a
  published JavaScript file (`--tag=livewire-google-analytics-js --force`), so both scripts stay the
  same version.

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

[Unreleased]: https://github.com/ArvidDeJong/livewire-google-analytics/compare/v1.3.2...HEAD
[1.3.2]: https://github.com/ArvidDeJong/livewire-google-analytics/compare/v1.3.1...v1.3.2
[1.3.1]: https://github.com/ArvidDeJong/livewire-google-analytics/compare/v1.3.0...v1.3.1
[1.3.0]: https://github.com/ArvidDeJong/livewire-google-analytics/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/ArvidDeJong/livewire-google-analytics/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/ArvidDeJong/livewire-google-analytics/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/ArvidDeJong/livewire-google-analytics/releases/tag/v1.0.0
