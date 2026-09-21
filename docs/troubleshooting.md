---
title: "Troubleshooting"
nav_order: 8
description: "Fix a GA4 event from a Livewire component that does not arrive, is counted twice or misses parameters, with the literal error and console messages."
---

# Troubleshooting

## First: find the step that fails

An event takes three steps: the component dispatches it, the listener receives it, `gtag()` sends it. Paste this in the browser console (F12, Console tab) and trigger the action:

```js
window.addEventListener('ga:event', (event) => console.log('ga:event', event.detail, typeof window.gtag));
```

| You see | The step that fails | Go to |
| --- | --- | --- |
| Nothing | the component does not dispatch | [No event at all](#no-event-at-all) |
| `ga:event {…} "undefined"` | `gtag` does not exist (yet); the event waits | [The event never reaches GA4](#the-event-never-reaches-ga4) |
| `ga:event {…} "function"`, nothing in GA4 | the listener is missing, or the problem is in GA4 | [The event never reaches GA4](#the-event-never-reaches-ga4) |

The package has no config file and no environment variable, so `php artisan config:clear` changes nothing here. It also has no migration and writes no log lines.

## No event at all

| Cause | Fix |
| --- | --- |
| The component does not use the trait | add `use TracksAnalytics;` inside the class, and the import `use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;` |
| `validate()` failed | nothing to fix: `validate()` throws, so the tracking call below it is skipped for a rejected form |
| The call sits below a `return`, or in a method the browser never calls | move it into the action, after the work succeeded |
| You used an `…AfterRedirect()` method | that is how it works: these methods don't dispatch, the event is sent on the next page, see [An event tracked before a redirect is missing](#an-event-tracked-before-a-redirect-is-missing) |

## The event never reaches GA4

| Cause | How to tell | Fix |
| --- | --- | --- |
| The listener is not on this page | `window.livewireGoogleAnalyticsListening` is `undefined` in the console | add `@include('livewire-google-analytics::script')` to the layout this page uses; a second layout, for example one for guests, needs it too |
| Google's tag is not on the page | `typeof window.gtag` is `"undefined"` | put the tag in your layout, see [Installation](installation.md) |
| A consent tool has not loaded the tag yet | `typeof window.gtag` turns into `"function"` after you give consent | nothing: the event waits and is sent once `gtag` exists. `window.livewireGoogleAnalyticsState.waiting` shows what is waiting |
| The visitor left before `gtag` appeared, or an ad blocker removed the tag | the queue only lives as long as the page, and for at most 30 minutes | nothing the package can do |
| The action ends in a full page redirect and uses `trackEvent()` | the event fires on the page that is going away | use `trackEventAfterRedirect()`, see [Tracking events](usage.md) |
| The Google tag belongs to another GA4 property | the `G-…` id in your layout is not the one of the property you are looking at | correct the tag; the package never sees that id |
| You look in the wrong report | DebugView only shows browsers in debug mode | see "See the event in Google Analytics" on [Testing](testing.md) |

## An event tracked before a redirect is missing

| Cause | Fix |
| --- | --- |
| The page after the redirect does not include the Blade view | include `livewire-google-analytics::script` in its layout. The event stays in the session until a page with the view is rendered, for at most 30 minutes |
| The site only loads the published JavaScript file | the file is static and cannot read the session. Include the view on the page after the redirect, or don't redirect in the same action |
| A published copy of the view from version 1.2.0 or older | it does not know about carried events: `php artisan vendor:publish --tag=livewire-google-analytics-views --force`, or delete `resources/views/vendor/livewire-google-analytics` |
| The route has no session (an API or other stateless route) | then the event is dispatched like `trackEvent()`, on the page that is going away |

## Every event is counted twice or more

| Cause | Fix |
| --- | --- |
| Version 1.1.0 or lower with `wire:navigate`: the inline script ran again on every visit and each run added a listener | update the package |
| A view or JavaScript file that was published before that fix | a published copy is not updated by Composer: publish it again with `--force`, or delete your copy of the view |
| The tracking call is in `render()`, in a lifecycle hook such as `updated()`, or in a loop | move it into the action; `render()` runs on every request |
| The Google tag is on the page twice, for example in the layout and through Google Tag Manager | remove one of the two |

## An event after a redirect is counted twice

| Cause | Fix |
| --- | --- |
| The same event is tracked with `trackEvent()` and with `trackEventAfterRedirect()` | use one of them |
| A view that was published with version 1.3.0 or older. Up to 1.3.0, a page that the browser took from its cache after a full page load, for example with the back button, sent its carried events again | publish the view again with `--force` or delete the copy. A published JavaScript file never holds carried events and cannot cause this; publish it again anyway, so both scripts are the same version |
| `sessionStorage` is not available in that browser **and** your pages may be cached | then only the list on `window` protects, and a full page load clears it. Laravel sends `Cache-Control: no-cache, private` by default; with that header the browser asks the server again and gets a page without the events |

## The numbers went up after an update

Since 1.3.0, events that arrive before `window.gtag` exists are no longer lost, and neither are events tracked with an `…AfterRedirect()` method. On a site with a consent tool that means more events than before: they were tracked all along and never reached Google. `['queue' => false]` on the include brings the old behaviour back, see [Installation](installation.md).

## "No hint path defined for [livewire-google-analytics]."

Laravel throws this `InvalidArgumentException` when the `@include` runs and the service provider of the package is not loaded.

1. Run `composer dump-autoload` and `php artisan package:discover`.
2. Check that the package is not listed under `extra.laravel.dont-discover` in your `composer.json`.
3. If you cache your configuration or services in deployment, run `php artisan optimize:clear` and build the caches again.

## "View [script] not found."

The namespace is known, the view name is not. Check the spelling: `livewire-google-analytics::script`. If you published the view and then renamed or emptied the folder `resources/views/vendor/livewire-google-analytics`, restore it or delete it.

## Parameters are missing in the reports

DebugView shows every parameter that was sent, so check there first. If the parameter is in DebugView, the package did its work; which parameters the other reports show is a setting of your GA4 property.

## The console stays empty

The Blade view logs nothing, on purpose. Only the publishable JavaScript file writes messages, and all but one at the `debug` level, which Chrome hides until you tick **Verbose** in the levels dropdown of the Console tab:

```
[GA4] Livewire Google Analytics listener initialized
[GA4] Event tracked: generate_lead {form_name: 'contact_form'}
[GA4] gtag not available, event is waiting: generate_lead
[GA4] gtag not available, skipping event: generate_lead
[GA4] Event dispatched without name: {params: {…}}
```

The last one is a warning and always shows. It means something dispatched `ga:event` without a `name`; the trait always passes one, so look for your own JavaScript.
