---
title: How it works
nav_order: 4
description: "How an event travels from a Livewire action to gtag(): the ga:event browser event, the listener, and what happens when gtag is missing."
---

# How it works

```
Livewire action  ->  ga:event browser event  ->  listener  ->  (queue)  ->  gtag('event', name, params)
```

## 1. The trait dispatches a browser event

Every method ends in one call:

```php
$this->dispatch('ga:event', name: $name, params: $params);
```

That is Livewire's own `dispatch()`. The event is part of the JSON response of the request, and Livewire fires it in the browser as a `CustomEvent` that bubbles up to `window`. Its `detail` is an object with `name` and `params`.

Nothing is sent to Google from the server. The package makes no HTTP request.

## 2. The listener forwards it

The listener is the whole client side of the package. It comes down to this:

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

This is what Google's own snippet does: its `gtag()` only pushes to `dataLayer`, and what was pushed before `gtag.js` loaded is processed once it loads. The listener does not push to `dataLayer` itself and never defines `gtag`; on a page with only Google Tag Manager that would produce entries GTM does not expect. It only ever calls a `window.gtag` that exists.

If `gtag` never appears, because of an ad blocker or because the visitor declined, the queue is simply never sent and disappears with the page. Whether an event may be sent after consent was given is decided by your consent setup: the listener calls `gtag()`, and Google's consent mode does the rest.

Turn the queue off with `['queue' => false]` on the include or `window.livewireGoogleAnalytics = { queue: false }`, see [Installation](installation.md). Events without `gtag` are then dropped.

Nothing throws, so the Livewire action and the page are not affected.

## 4. Over a redirect the event travels through the session

An action that redirects gets a response with both the browser event and the redirect. Livewire fires the event on the page that is going away: Livewire 3 right around the moment it sets `window.location`, Livewire 4 after it. The event never fires on the next page.

The `…AfterRedirect()` methods of the trait therefore don't dispatch. They store the event in the session under `livewire-google-analytics.events`. The Blade view on the next page takes the events out of the session, so a reload does not send them again, and writes them into its script as JSON in which every `<`, `>`, `&` and quote is escaped. From there they go through the same queue as every other event.

## Why not `$this->js()`?

You can call `gtag()` from PHP with `$this->js("gtag('event', ...)")`, but then PHP builds JavaScript source. A value with a quote in it breaks the script, and a value from a visitor can run code in the page. With the browser event the parameters are data from start to end.

## You can dispatch the event yourself

The listener does not care who dispatched the event. From Alpine or plain JavaScript:

```js
window.dispatchEvent(new CustomEvent('ga:event', {
    detail: { name: 'select_content', params: { content_type: 'tab' } },
}));
```

The other side of that: any script on your page can do the same. The listener adds nothing a script could not already do by calling `gtag()` itself.
