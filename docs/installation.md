---
title: "Installation"
nav_order: 2
description: "Install darvis/livewire-google-analytics step by step: the package, your own Google tag, the listener in the layout, and a check that an event arrives in GA4."
---

# Installation

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- Livewire 3 or 4
- A Google Analytics 4 property with its Google tag on your pages (step 2)

## 1. Install the package

```bash
composer require darvis/livewire-google-analytics
```

Laravel discovers the service provider by itself. There is no config file, no migration and no environment variable, so there is nothing to publish or fill in.

## 2. Put your Google tag on the page

The package does **not** load Google Analytics and has no setting for your measurement id (the `G-…` code of your GA4 property). You add Google's own tag yourself:

1. In Google Analytics, open **Admin**, then **Data streams** under "Data collection and modification", and click your web stream.
2. Under "Google tag", click **View tag instructions** and choose **Install manually**.
3. Copy the whole snippet, from `<!-- Google tag (gtag.js) -->` to `</script>`, and paste it right after `<head>` in your layout.

If a consent tool (a cookie banner) loads the tag for you, leave that as it is. The listener keeps events that are tracked before the tag is there and sends them once it is, see [How it works](concepts.md).

## 3. Include the listener in your layout

Add the view **once**, before `</body>`, in every layout that shows a component that tracks:

```blade
    @include('livewire-google-analytics::script')
</body>
```

The view renders one inline script tag. It listens for the `ga:event` browser event, which is the event the trait dispatches, and hands it to `gtag()`. It writes nothing to the browser console.

## 4. Add the trait to a component

```php
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;

class ContactForm extends Component
{
    use TracksAnalytics;

    public function submit(): void
    {
        // Validate and handle the form first, then:
        $this->trackLead(['form_name' => 'contact_form']);
    }
}
```

A trait is a set of methods a class takes over with `use`. [Quick start](quickstart.md) has this example in full.

## Check that it works

Open a page of your site that uses the layout, open the browser's developer tools (F12) and go to the **Console** tab.

**1. Are the listener and the Google tag there?** Type these two lines:

```js
window.livewireGoogleAnalyticsListening
typeof window.gtag
```

You should see `true` and `"function"`.

| You see | It means | Do this |
| --- | --- | --- |
| `undefined` for the first line | the listener is not on this page | step 3: the include is missing from the layout this page uses |
| `"undefined"` for the second line | Google's tag is not on the page, or not yet | step 2; with a consent tool, give consent first and try again |

**2. Send a test event.** Paste this in the console:

```js
window.dispatchEvent(new CustomEvent('ga:event', {
    detail: { name: 'test_event', params: { debug_mode: true } },
}));
```

It fires the same browser event the trait dispatches. `debug_mode` is a parameter of Google's tag that makes this one event show up in DebugView.

**3. See it arrive.** In Google Analytics, open **Admin**, then **DebugView** under "Data display". `test_event` appears in the timeline. If it does not, go to [Troubleshooting](troubleshooting.md).

**4. Do the same from a component.** Trigger the action that calls `trackLead()` or `trackEvent()`. To watch the browser event pass by, paste this in the console first:

```js
window.addEventListener('ga:event', (event) => console.log('ga:event', event.detail));
```

After the action the console shows `ga:event {name: 'generate_lead', params: {…}}`. Add `'debug_mode' => true` to the parameters while you test if you want to see that event in DebugView too.

## Options

### Load the listener as a file, for a Content Security Policy without inline scripts

A Content Security Policy (CSP) is a header that tells the browser which scripts may run. If yours forbids inline scripts, publish the JavaScript file and load it instead of the view:

```bash
php artisan vendor:publish --tag=livewire-google-analytics-js
```

```html
<script src="/vendor/livewire-google-analytics/google-analytics.js" defer></script>
```

The file is copied to `public/vendor/livewire-google-analytics/google-analytics.js`. It listens and queues exactly like the view, with two differences:

- It is a static file and cannot read the session, so it cannot send the events of the `…AfterRedirect()` methods. Those need the Blade view on the page after the redirect, see [Tracking events](usage.md).
- It writes to the browser console, which makes it handy for checking your setup:

| Message | Level | When |
| --- | --- | --- |
| `[GA4] Livewire Google Analytics listener initialized` | debug | the script ran for the first time on this page |
| `[GA4] Event tracked: <name> <params>` | debug | an event was handed to `gtag()` |
| `[GA4] gtag not available, event is waiting: <name>` | debug | `window.gtag` is not a function yet; the event is queued |
| `[GA4] gtag not available, skipping event: <name>` | debug | the same, with the queue turned off |
| `[GA4] Event dispatched without name: <detail>` | warn | the event had no `name` and is ignored |

Chrome hides messages of the `debug` level. In the Console tab, open the levels dropdown (it says "Default levels") and tick **Verbose**.

A published file is a copy that Composer does not update. Publish it again with `--force` after you update the package.

### Turn the queue off

Events that arrive before `window.gtag` exists wait: at most 50 of them, for at most 30 minutes. To drop them instead, as versions up to 1.2.0 did, pass `queue` to the include:

```blade
@include('livewire-google-analytics::script', ['queue' => false])
```

With the published file, set this before the script runs:

```html
<script>window.livewireGoogleAnalytics = { queue: false };</script>
```

### Change the view

```bash
php artisan vendor:publish --tag=livewire-google-analytics-views
```

copies the view to `resources/views/vendor/livewire-google-analytics/script.blade.php`. From then on Laravel uses your copy, also after an update of the package. Compare it with the package's view when you upgrade, or you miss fixes in the listener.

## Next

[Quick start](quickstart.md) builds one complete form. [Tracking events](usage.md) lists every method.
