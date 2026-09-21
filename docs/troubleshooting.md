---
title: Troubleshooting
nav_order: 7
description: "Why a GA4 event from a Livewire component does not arrive, arrives twice or misses its parameters, and how to find the step that fails."
---

# Troubleshooting

Find the step that fails first. Paste this in the browser console and trigger the action:

```js
window.addEventListener('ga:event', (event) => console.log('ga:event', event.detail, typeof window.gtag));
```

| You see | The problem is in |
| --- | --- |
| Nothing | the component: the tracking call is not reached |
| `ga:event {...} "undefined"` | the Google tag: `gtag` does not exist, the event is dropped |
| `ga:event {...} "function"` and nothing in GA4 | the listener is missing, or GA4 itself (filters, consent mode, the wrong property) |

## No event at all

- The component does not use the `TracksAnalytics` trait, or the call sits below a `return`.
- `validate()` failed. It throws, so everything below it is skipped. That is what you want.
- The call is in a method that is never called from the browser.

## The event is dispatched but never reaches GA4

- `@include('livewire-google-analytics::script')` is missing from the layout this page uses. A second layout (for example a guest layout) needs it too.
- `window.gtag` is not a function: the Google tag is not in the layout, an ad blocker removed it, or your consent tool has not loaded it. The listener drops the event and does not retry.
- The action ends in a redirect. The event and the navigation arrive in the same response; see the purchase example on [Examples](examples.md).
- The measurement id in your Google tag belongs to another property. The package never sees that id.

## Every event arrives twice or more

- You are on version 1.1.0 or lower and use `wire:navigate`: the inline script ran again on every visit and each run added a listener. Update the package.
- You published the view or the JavaScript file before that fix. A published copy is not updated by Composer: publish it again with `--force`, or delete your copy of the view.
- The tracking call is in `render()`, in a lifecycle hook such as `updated()`, or inside a loop.
- The Google tag is on the page twice, for example once in the layout and once through Google Tag Manager. Then GA4 counts `page_view` twice as well.

## The view is not found

`View [livewire-google-analytics::script] not found` means the service provider is not loaded. Run `composer dump-autoload` and `php artisan package:discover`, and check that the package is not listed under `dont-discover` in your `composer.json`.

## Parameters are missing in the reports

DebugView shows every parameter that was sent. The standard reports only show a custom parameter after you registered it as a custom dimension (Admin, Custom definitions), and only for data collected after that.

## The console stays empty

The Blade view logs nothing, on purpose. Only the publishable JavaScript file writes `[GA4]` messages, at the `debug` level, which Chrome hides until you enable "Verbose".
