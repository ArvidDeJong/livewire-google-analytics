# Contributing

Contributions are welcome: bug reports, fixes, documentation and ideas.

## Before you start

- **Bugs:** open an [issue](https://github.com/ArvidDeJong/livewire-google-analytics/issues/new/choose) with the steps to reproduce. Leave your measurement id out.
- **Features:** open an issue first. This package stays small on purpose, so let's agree a feature fits before you build it.
- **Security issues:** don't open an issue; see [SECURITY.md](SECURITY.md).

## Development

```bash
git clone https://github.com/ArvidDeJong/livewire-google-analytics.git
cd livewire-google-analytics
composer install

composer test      # Pest
composer lint      # Pint, check only (composer format fixes)
composer analyse   # Larastan, level 8
```

CI runs the tests on PHP 8.2 to 8.4 with Laravel 11, 12 and 13, on the lowest and the latest dependencies. The tests that run the listener script need Node; without it they are skipped.

## Pull requests

- Add or update tests for every change in behaviour. A change in the listener goes in both `resources/views/script.blade.php` and `resources/js/google-analytics.js`; `tests/Feature/ListenerScriptTest.php` runs the same cases against both.
- Keep the public API compatible within 1.x: the `TracksAnalytics` trait with `trackEvent()`, `trackLead()`, `trackNewsletterSignup()` and `trackCustomEvent()`, their signatures and the event names they send (`generate_lead`, `sign_up` with `method` `newsletter`, the `ga_` prefix); the four `…AfterRedirect()` variants; the `ga:event` browser event with `name` and `params`; the `window.livewireGoogleAnalytics` object and its `queue` key; the `queue` parameter of the view; the session key `livewire-google-analytics.events` (`CarriedEvents::SESSION_KEY`) and the `id`, `name`, `params` and `at` of a stored event, because host app tests assert on them; the view name `livewire-google-analytics::script`; the publish tags `livewire-google-analytics-js` and `livewire-google-analytics-views` and the path the JavaScript file is published to.
- Keep the trait methods `protected`. A public method of a Livewire component can be called from the browser.
- The code between `core:start` and `core:end` is the same in both files; a test compares them.
- Never write a value from PHP into the script as a string. The two values that do reach it, the `queue` flag and the carried events, go through `Js::from()` and `CarriedEvents::pullForScript()`, which hex escape every tag character and quote.
- Write code, comments and messages in English.
- Update `docs/`, `CHANGELOG.md` (under `Unreleased`) and `resources/boost/` when users will notice the change.
- The documentation in `docs/` is also the website. Don't write `{{ }}` or `{% %}` there outside a raw block; Jekyll would render it.

## Code of conduct

This project follows the [Contributor Covenant](CODE_OF_CONDUCT.md).
