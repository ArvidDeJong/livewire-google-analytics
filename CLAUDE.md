# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository. The conventions shared by every darvis package (language, releases, CI, docs site, Boost guidelines, public API policy) are in [../CLAUDE.md](../CLAUDE.md); this file only holds what is specific to this package.

## Package overview

`darvis/livewire-google-analytics` is a Laravel package (PHP 8.2+, Laravel 11/12/13, Livewire 3/4) that sends Google Analytics 4 events from Livewire components: a trait dispatches a browser event, a listener in the layout hands it to `gtag()`.

- Namespace: `Darvis\LivewireGoogleAnalytics\` → `src/`
- Service provider auto-registered via `extra.laravel.providers` in [composer.json](composer.json)
- No config file, no config key, no environment variable. The measurement id lives in the host app's own Google tag; the package does not load `gtag.js`.

## Architecture

- [TracksAnalytics](src/Traits/TracksAnalytics.php): `trackEvent()` is the one method that dispatches (`ga:event` with `name` and `params`); `trackLead()`, `trackNewsletterSignup()` and `trackCustomEvent()` only pick the name and the params.
- The listener exists twice: [script.blade.php](resources/views/script.blade.php) is inline and silent, [google-analytics.js](resources/js/google-analytics.js) is publishable (for a Content Security Policy without inline scripts) and logs `[GA4]` messages. They differ only in the three lines above `core:start` (`carried`, `log`, the `queue` flag of the view); the code between `core:start` and `core:end` is identical and a test compares it. `tests/Feature/ListenerScriptTest.php` runs every case against both in Node (`tests/Fixtures/run-listener.js`: fake window, fake clock, fake timers) and is skipped without Node.
- All client state is on `window`, because `wire:navigate` runs every script in the body again while `window` stays: `livewireGoogleAnalyticsListening` (listen once; without it the n-th page sent every event n times, the bug up to 1.1.0), `livewireGoogleAnalyticsState` (`waiting`, `timer`, `carried`) and the public settings object `livewireGoogleAnalytics` (`queue`).
- The queue: an event without `window.gtag` waits (at most 50, at most 30 minutes) and `flush()` sends the waiting events oldest first, on the next event and on a one second `setInterval` that is cleared as soon as nothing waits. `flush()` takes the events out of the queue before it sends them, so a re-entrant call cannot send one twice.
- [CarriedEvents](src/Support/CarriedEvents.php) holds the events of the `…AfterRedirect()` trait methods in the session (`put`, not `flash`: a flash would be gone after any request in between, for example while the visitor is at a payment provider). The view pulls them once, drops what is older than 30 minutes and writes them into `var carried` as hex escaped JSON. Each event has an `id`. The script remembers the ids it accepted in `state.carried` (the `wire:navigate` back button runs a cached page again while `window` stays) and in `sessionStorage` under `livewire-google-analytics.sent` (the HTTP cache or bfcache brings the page back after a full load, when `window` is new). Both are checked; the last 100 ids are kept, and there is no second clock for them. `readSentIds()` returns `null` when the storage cannot be used, and then nothing is written either.
- Only a started session counts (`request()->hasSession()` or a started store). The session manager hands out a store on a stateless route too, but nothing saves it. `Livewire::test()` runs without middleware, so tests call `session()->start()`.

## What Livewire does with an event and a redirect in one response

Read in the Livewire source on 2026-09-21; check again when Livewire changes its request handling.

- PHP, 3.x and 4.x: `HandlesRedirects::redirect()` stores the URL and calls `skipRender()`. `SupportRedirects::dehydrate()` adds the `redirect` effect and `SupportEvents::dehydrate()` adds the `dispatches` effect, so the response carries both. `tests/Feature/CarriedEventsTest.php` pins that.
- JS 3.6.4 (`js/features/supportDispatches.js`, `supportRedirects.js`): both listen to the `effect` hook; the dispatch runs first and synchronously, then `window.location.href = url`.
- JS 3.8.9: the dispatch is wrapped in three `queueMicrotask()` calls, so it runs right after `window.location.href` was set.
- JS 4.4.5: `processEffects()` sets `window.location.href` first; the dispatch happens in the `onMorphed` callback of `supportDispatches.js`, after `await message.invokeOnMorph()`.
- In every version the event fires on the page that is going away and never on the next one. With `redirectUsingNavigate` Livewire calls `Alpine.navigate(url)`; `window` stays, so the listener and the queue survive and a normal `trackEvent()` arrives.
- An automatic carry-over inside the trait is not possible with documented APIs. `dehydrateTracksAnalytics()` is documented, but hooks run in registration order (`ComponentHookRegistry::proxyCallToHooks()`), and `LivewireServiceProvider` registers `SupportEvents` before `SupportLifecycleHooks`: the dispatches are already serialised when the trait hook runs. There is also no public way to ask a component whether a redirect is pending; both would need `Livewire\store()`. Hence the explicit `…AfterRedirect()` methods.
- [GoogleAnalyticsServiceProvider](src/GoogleAnalyticsServiceProvider.php) registers the view namespace `livewire-google-analytics` and the publish tags `livewire-google-analytics-js` and `livewire-google-analytics-views`. Nothing else.
- The trait is only used by host app components, so PHPStan would skip it as unused. `tests/Fixtures/TrackingComponent.php` uses it and is in the PHPStan paths for that reason; don't remove it from `phpstan.neon.dist`.

## Conventions

- Never write a PHP value into the script as a string, and never concatenate or interpolate one. Exactly two values reach the script: the `queue` flag through `Js::from()` on a real boolean, and the carried events through `CarriedEvents::pullForScript()` (`JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT`). An event name or parameter is visitor input in many apps; unescaped, `</script>` in it ends the script tag and the rest runs as HTML. Tests send `'); alert(1); //</script>` through both paths.
- Never make the trait methods public. A public method of a Livewire component can be called from the browser with any arguments, which would let a visitor send events through the server. A test pins `protected`.
- Never push to `dataLayer` and never define `window.gtag`. Queueing in our own array does what Google's snippet does without pretending to be it: on a page with Google Tag Manager but without gtag, `dataLayer` entries in gtag's argument format are not what GTM expects. Only call a `window.gtag` that exists. A test greps both scripts.
- Never leave a timer running with an empty queue; the script is on every page of the site. The Node harness counts live timers at the end of every case.
- Never send a carried event twice: an `…AfterRedirect()` method must not dispatch as well (except as the fallback without a session), the view must `pull` and not `get`, and the script must check `state.carried` and the ids in `sessionStorage`.
- Never mark a carried id as seen when `gtag` sends it; mark it when the event is accepted into the queue. A reload from the cache while the event waits would queue it a second time, and again on every reload. The price is an event that was accepted, never sent and then reloaded from the cache: it is lost, like one that waited longer than 30 minutes. The owner accepted that.
- Never store an event name or a parameter in `sessionStorage`, only ids. Parameters can hold what a visitor typed, other scripts on the page can read the storage, and anything beyond a random id would have to go into the host app's privacy statement.
- Never let a storage error reach the page or the console at a visible level. `sessionStorage` is missing or throws in private mode of some browsers, with storage turned off, on a full quota and in a sandboxed iframe, and the listener is on every page of the site. Every access is in a try/catch; the fallback is the `window` list. The harness has a storage that is absent, one that throws on `getItem`, on `setItem` and on access.
- Queueing is on by default by decision of the owner (September 2026): Google's own snippet behaves that way. Don't flip the default within 1.x.
- Don't introduce a config file within 1.x without a decision: "nothing to configure" is documented behaviour, and a measurement id in the package would mean the package loads the Google tag, which collides with consent tools. The queue opt out is a view parameter and a `window` setting for that reason.
- In `resources/boost/guidelines/core.blade.php` write the include directive as `@@include`. Boost renders the guideline with Blade; an unescaped `@include` puts the listener script in the guideline. `tests/Feature/BoostGuidelineTest.php` renders it.
- Keep the public API compatible within 1.x: the list is in `CONTRIBUTING.md`. It includes the event names the helpers send and the `ga_` prefix, because they are what site owners see in their GA4 reports and build conversions on.
