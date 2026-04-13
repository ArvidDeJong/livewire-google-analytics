# Project Guidelines

## Scope

These instructions apply to all changes in this repository.

## Build And Test

- Install dependencies: `composer install`
- Run tests: `composer test` (or `vendor/bin/pest`)
- Run coverage: `composer test-coverage`

Always run relevant tests after code changes.

## Architecture

- Core package code lives in `src/`.
- Main API surface is the `TracksAnalytics` trait in `src/Traits/TracksAnalytics.php`.
- Package bootstrapping and view registration live in `src/GoogleAnalyticsServiceProvider.php`.
- Runtime browser bridge is in `resources/views/script.blade.php` (reference JS in `resources/js/google-analytics.js`).
- Usage examples are in `examples/`.
- Behavioral tests are in `tests/Feature/` using Pest + Livewire assertions.

## Conventions

- Keep changes minimal and backward compatible unless a breaking change is explicitly requested.
- Follow existing coding style and naming patterns in nearby files.
- Write code, comments, and docs in English.
- Prefer adding/updating tests in `tests/Feature/` when behavior changes.
- Keep docs and examples in sync with API changes.

## Package-Specific Gotchas

- Keep `@include('livewire-google-analytics::script')` after `@livewireScripts` in consumer guidance and examples.
- Do not add tracking calls inside Livewire `render()` methods; tracking should run in explicit action methods.
- Keep graceful behavior when `window.gtag` is unavailable (no hard failures).

## Documentation Map

Link to existing docs instead of duplicating long explanations:

- Overview: `README.md`
- Quick setup: `QUICK_START.md`
- Installation: `docs/02-installation.md`
- Basic usage: `docs/03-basic-usage.md`
- Examples: `docs/04-examples.md`
- Testing guidance: `docs/05-testing.md`
- Troubleshooting: `docs/06-troubleshooting.md`
- Contribution workflow: `CONTRIBUTING.md`
