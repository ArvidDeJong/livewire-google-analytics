# Changelog

All notable changes to `darvis/livewire-google-analytics` will be documented in this file.

## [1.0.0] - 2026-01-27

### Added
- Initial release
- `TracksAnalytics` trait for clean GA4 event tracking
- `trackLead()` method for lead generation events
- `trackEvent()` method for custom events
- `trackNewsletterSignup()` method for newsletter signups
- `trackCustomEvent()` method for custom events with `ga_` prefix
- JavaScript event listener for browser-side GA4 integration
- Blade view component for easy script inclusion
- Service provider with auto-discovery
- Comprehensive README with examples
- Support for Laravel 10, 11, 12
- Support for Livewire 3 & 4
- PHP 8.1+ support

### Features
- ✅ Clean API - No manual `gtag()` calls needed
- ✅ Type-safe with full PHP type hints
- ✅ Secure - No JavaScript string injection vulnerabilities
- ✅ Zero configuration - Works out of the box
- ✅ Consistent event tracking across applications
- ✅ Automatic event dispatching via Livewire
- ✅ Silent failure when gtag not available (ad blockers, etc.)

### Security
- Prevents JavaScript injection by using Livewire's dispatch system
- No direct string interpolation in JavaScript
- Safe parameter passing through structured data

## [Unreleased]

### Planned
- Additional helper methods for common GA4 events
- Configuration file for default parameters
- Event middleware for filtering/transforming events
- Testing utilities

## [1.1.0] - 2026-04-13

### Added
- Support for Laravel 13

### Changed
- Expanded `orchestra/testbench` dev support to include v10
- Expanded Pest and Pest Laravel plugin dev support to include v3 and v4
