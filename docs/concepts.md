---
title: How it works
nav_order: 4
description: "How an event travels from a Livewire action to gtag(): the ga:event browser event, the listener, and what happens when gtag is missing."
---

# How it works

```
Livewire action  ->  ga:event browser event  ->  listener  ->  gtag('event', name, params)
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
    if (typeof window.gtag !== 'function') return;
    window.gtag('event', detail.name, detail.params || {});
});
```

- An event without a `name`, or with an empty one, is ignored.
- Missing `params` become an empty object.
- The listener registers once per window. A second copy of the script (a `wire:navigate` visit, or the view and the published file on the same page) returns straight away. The flag it uses is `window.livewireGoogleAnalyticsListening`.

## 3. Without gtag the event is dropped

The listener checks `typeof window.gtag` for every event. When it is not a function, because an ad blocker removed it, the visitor gave no consent and your consent tool did not load the tag, or the tag is simply not in the layout, the event is dropped. It is **not queued** and not sent later.

Nothing throws, so the Livewire action and the page are not affected.

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
