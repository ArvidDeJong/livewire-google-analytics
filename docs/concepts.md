---
title: "How it works"
nav_order: 5
description: "How an event travels from a Livewire action to gtag(): the ga:event browser event, the queue when gtag is missing, and the session over a redirect."
---

# How it works

```
Livewire action  ->  ga:event browser event  ->  listener  ->  (queue)  ->  gtag('event', name, params)
```

## 1. The trait dispatches a browser event

`trackEvent()`, `trackLead()`, `trackNewsletterSignup()` and `trackCustomEvent()` all end in one call:

```php
$this->dispatch('ga:event', name: $name, params: $params);
```

That is Livewire's own `dispatch()`. A browser event is a message that JavaScript on the page can listen for. The event is part of the JSON response of the request, and Livewire fires it in the browser as a `CustomEvent` that bubbles up to `window`. Its `detail` is an object with `name` and `params`.

Nothing is sent to Google from the server. The package makes no HTTP request. The `…AfterRedirect()` variants take another road, see section 4.

## 2. The listener forwards it

The listener is the client side of the package: the Blade view `livewire-google-analytics::script`, or the published JavaScript file. Its first job comes down to this:

```js
window.addEventListener('ga:event', function (event) {
    const detail = event.detail || {};
    if (!detail.name) return;

    if (typeof window.gtag === 'function') {
        window.gtag('event', detail.name, detail.params || {});
    } else {
        /* wait until gtag exists, see below */
    }
});
```

- An event without a `name`, or with an empty one, is ignored.
- Missing `params` become an empty object.
- The listener registers once per window. A second copy of the script (a `wire:navigate` visit, or the view and the published file on the same page) finds `window.livewireGoogleAnalyticsListening` set and does not listen again. The queue lives on `window` too, in `window.livewireGoogleAnalyticsState`, so every copy works with the same one.

## 3. Without gtag the event waits

The listener checks `typeof window.gtag` for every event. When it is not a function yet, typically because a consent tool adds Google's tag only after the visitor agreed, the event goes into a queue in memory:

- at most **50** events wait; the oldest is dropped when a 51st arrives;
- an event that waited longer than **30 minutes** is not sent any more;
- the listener looks for `gtag` again when the next event arrives and once a second, and sends what is waiting in the order it was tracked. The timer only runs while something is waiting.

Google's own snippet works in a similar way: its `gtag()` function only pushes to the `dataLayer` array, which `gtag.js` reads once it has loaded. The listener does not push to `dataLayer` itself and never defines `gtag`. It only ever calls a `window.gtag` that exists.

If `gtag` never appears, because of an ad blocker or because the visitor declined, the queue is never sent and disappears with the page.

The package does no consent handling. It does not know whether the visitor agreed; it only sees whether `window.gtag` exists. An event that was tracked before the visitor agreed is sent once your consent tool has loaded Google's tag. If you don't want that, turn the queue off.

Turn the queue off with `['queue' => false]` on the include or `window.livewireGoogleAnalytics = { queue: false }`, see [Installation](installation.md). An event that arrives while `gtag` does not exist is then dropped.

Nothing throws, so the Livewire action and the page are not affected.

## 4. Over a redirect the event travels through the session

An action that redirects gets a response with both the browser event and the redirect. Livewire fires the event on the page that is going away: Livewire 3 right around the moment it sets `window.location`, Livewire 4 after it. The event never fires on the next page.

The `…AfterRedirect()` methods of the trait therefore don't dispatch. They store the event in the session under `livewire-google-analytics.events`. The Blade view on the next page takes the events out of the session, so a reload does not send them again, and writes them into its script as JSON in which every `<`, `>`, `&` and quote is escaped. From there they go through the same queue as every other event.

### Sent once, whatever the browser does with the page

The server hands the events out once, but the page that holds them can run again without asking the server: `wire:navigate` puts a cached page back on the back button, and the browser can take a page from its HTTP cache or its back/forward cache after a full page load. So every carried event has an id, and the listener remembers the ids it accepted in two places:

- on `window`, for the `wire:navigate` case, where the window stays;
- in `sessionStorage`, under the key `livewire-google-analytics.sent`, for a full page load, where the window is new. It holds **only ids**, the most recent 100, never an event name or a parameter. `sessionStorage` belongs to one tab, which fits: a carried event belongs to the tab that was redirected.

An id is remembered when the event is accepted into the queue, not when `gtag` sends it. Otherwise a reload while the event waits for `gtag` would queue it again on every reload. The other side of that choice: an event that was accepted, never sent because `gtag` never appeared, and whose page is then reloaded from the cache, is lost. That is the same outcome as an event that waited longer than 30 minutes.

When `sessionStorage` is missing or throws (private mode in some browsers, storage turned off, a full quota, a sandboxed iframe), the listener carries on with the `window` list alone and reports nothing.

Normal events have no id and are never deduplicated.

## What it replaces: `gtag()` calls built in PHP

Without the package you would call `gtag()` from a component with Livewire's `$this->js("gtag('event', ...)")`. Then PHP builds JavaScript source: a value with a quote in it breaks the script, and a value from a visitor can run code in the page. With the trait the event name and the parameters are data from start to end.

## You can dispatch the event yourself

The listener does not care who dispatched the event. From Alpine or plain JavaScript:

```js
window.dispatchEvent(new CustomEvent('ga:event', {
    detail: { name: 'select_content', params: { content_type: 'tab' } },
}));
```

The other side of that: any script on your page can do the same. The listener adds nothing a script could not already do by calling `gtag()` itself.
