# Testing Your Tracking

This guide shows you how to verify that your Google Analytics tracking is working correctly.

## Testing Methods

There are three main ways to test your tracking:

1. **Browser Console** - Fastest, works immediately
2. **GA4 Realtime** - See events in Google Analytics within seconds
3. **GA4 DebugView** - Most detailed, best for debugging

## Method 1: Browser Console

The fastest way to verify tracking is working.

### Step 1: Open Developer Tools

1. Open your website in Chrome, Firefox, or Safari
2. Press `F12` (or `Cmd+Option+I` on Mac)
3. Go to the **Console** tab

### Step 2: Check for initialization

When the page loads, you should see:

```
[GA4] Livewire Google Analytics listener initialized
```

If you see this, the JavaScript listener is working correctly.

### Step 3: Trigger an event

Submit a form or trigger any action that calls a tracking method.

You should see:

```
[GA4] Event tracked: generate_lead {form_name: "contact_form", lead_type: "contact"}
```

### What if I don't see these messages?

The package doesn't log to console by default. To enable debug logging, you can modify the script temporarily:

```blade
{{-- In your layout, replace the include with this: --}}
<script>
(function () {
    console.log('[GA4] Livewire Google Analytics listener initialized');
    
    function fireGaEvent(name, params) {
        if (typeof window.gtag !== 'function') {
            console.warn('[GA4] gtag not found - event not tracked:', name, params);
            return;
        }
        console.log('[GA4] Event tracked:', name, params);
        window.gtag('event', name, params || {});
    }

    window.addEventListener('ga:event', function (event) {
        const detail = event.detail || {};
        if (!detail.name) return;
        fireGaEvent(detail.name, detail.params);
    });
})();
</script>
```

## Method 2: GA4 Realtime Reports

See events appear in Google Analytics within seconds.

### Step 1: Open Realtime Reports

1. Go to [Google Analytics](https://analytics.google.com/)
2. Select your property
3. Click **Reports** → **Realtime**

### Step 2: Trigger an event

On your website, submit a form or trigger an action.

### Step 3: Check Realtime

Within 5-10 seconds, you should see:

- **Event count by Event name** - Your event should appear here
- **Event count by Page title and screen name** - Shows which page the event came from
- **Users by Country** - Shows your location

### What events should I see?

Depending on what you tracked:

- `generate_lead` - From `trackLead()`
- `sign_up` - From `trackNewsletterSignup()`
- `purchase` - From `trackEvent('purchase', ...)`
- `ga_*` - From `trackCustomEvent()`

## Method 3: GA4 DebugView

The most detailed testing method, perfect for debugging.

### Step 1: Enable Debug Mode

**Option A: Chrome Extension (Easiest)**

1. Install [Google Analytics Debugger](https://chrome.google.com/webstore/detail/google-analytics-debugger/) extension
2. Click the extension icon to enable it (turns blue)
3. Reload your website

**Option B: Manual (All Browsers)**

Add this to your GA4 configuration in your layout:

```blade
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-XXXXXXXXX', {
        'debug_mode': true  // Add this line
    });
</script>
```

### Step 2: Open DebugView

1. Go to [Google Analytics](https://analytics.google.com/)
2. Click **Admin** (gear icon bottom left)
3. Click **DebugView** (under Property column)

### Step 3: Trigger events

Submit forms or trigger actions on your website.

### Step 4: View detailed information

In DebugView, you'll see:

- **Event stream** - All events in real-time
- **Event parameters** - All parameters sent with each event
- **User properties** - User-level data
- **Errors** - Any tracking errors

Click on any event to see:

- Event name
- All parameters
- Timestamp
- User pseudo ID
- Session ID

### What to look for

✅ **Good signs:**
- Events appear within seconds
- All parameters are present
- No errors in the stream

❌ **Problems:**
- Events don't appear → Check if gtag is loaded
- Missing parameters → Check your tracking code
- Duplicate events → Check if you're tracking in `render()`

## Testing Checklist

Use this checklist to verify everything works:

### Basic Setup

- [ ] Package installed via Composer
- [ ] `@include('livewire-google-analytics::script')` added to layout
- [ ] Script is after `@livewireScripts`
- [ ] GA4 tracking code is in layout
- [ ] Browser console shows initialization message

### Component Setup

- [ ] `use TracksAnalytics;` trait added to component
- [ ] Tracking method called in action method (not `render()`)
- [ ] Tracking happens after successful processing
- [ ] Parameters are meaningful and useful

### Tracking Verification

- [ ] Browser console shows event when triggered
- [ ] Event appears in GA4 Realtime within 10 seconds
- [ ] Event appears in GA4 DebugView with all parameters
- [ ] No duplicate events
- [ ] No JavaScript errors in console

## Common Issues

### Issue 1: Events not showing in console

**Symptoms:** No console messages when events fire.

**Solutions:**
- Add debug logging (see Method 1 above)
- Check if `@include('livewire-google-analytics::script')` is in layout
- Make sure it's after `@livewireScripts`
- Clear browser cache and reload

### Issue 2: "gtag is not a function"

**Symptoms:** Console shows error about gtag.

**Solutions:**
- Check if GA4 script is loaded (view page source)
- Make sure GA4 script loads before the package script
- Check if you're on a production domain (if you have environment checks)
- Disable ad blockers temporarily

### Issue 3: Events fire multiple times

**Symptoms:** Same event tracked 2+ times per action.

**Solutions:**
- Don't call tracking methods in `render()` or `mount()`
- Only call in action methods (`submit()`, `save()`, etc.)
- Check if you have multiple `@include('livewire-google-analytics::script')` in your layout
- Make sure you're not tracking in loops

### Issue 4: Events in console but not in GA4

**Symptoms:** Console shows events, but GA4 doesn't receive them.

**Solutions:**
- Wait 5-10 minutes (GA4 can have delays)
- Check if gtag is loaded: type `typeof gtag` in console (should be `'function'`)
- Verify your Measurement ID is correct
- Check if you're on a production domain
- Disable ad blockers and privacy tools
- Use DebugView instead of Realtime

### Issue 5: Missing parameters

**Symptoms:** Event tracked but parameters are missing.

**Solutions:**
- Check if you're passing an array to the tracking method
- Verify parameter names don't have typos
- Make sure values aren't `null` or `undefined`
- Check browser console for the actual data sent

## Testing in Different Environments

### Local Development

```blade
{{-- Always load tracking script --}}
@include('livewire-google-analytics::script')

{{-- Only load GA4 on production --}}
@if(app()->environment('production'))
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-XXXXXXXXX');
    </script>
@endif
```

**Result:** Events will show in console but won't be sent to GA4 (because gtag isn't loaded).

### Staging

```blade
@if(in_array(app()->environment(), ['production', 'staging']))
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-XXXXXXXXX', {
            'debug_mode': true  // Enable debug on staging
        });
    </script>
@endif
```

**Result:** Events will be sent to GA4 and visible in DebugView.

### Production

```blade
@if(app()->environment('production'))
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-XXXXXXXXX');
    </script>
@endif
```

**Result:** Events will be sent to GA4 and visible in all reports.

## Automated Testing with Pest

You can test tracking in your Pest tests:

```php
<?php

use App\Livewire\ContactForm;
use Livewire\Livewire;

it('dispatches analytics event when contact form is submitted', function () {
    Livewire::test(ContactForm::class)
        ->set('name', 'John Doe')
        ->set('email', 'john@example.com')
        ->set('message', 'Test message')
        ->call('submit')
        ->assertDispatched('ga:event', 
            name: 'generate_lead',
            params: [
                'form_name' => 'contact_form',
                'lead_type' => 'contact',
            ]
        );
});

it('tracks lead with correct parameters', function () {
    Livewire::test(ContactForm::class)
        ->set('name', 'Jane Smith')
        ->set('email', 'jane@example.com')
        ->set('message', 'Hello world')
        ->call('submit')
        ->assertDispatched('ga:event', function ($event, $data) {
            return $data['name'] === 'generate_lead' 
                && $data['params']['form_name'] === 'contact_form';
        });
});

it('does not track event when validation fails', function () {
    Livewire::test(ContactForm::class)
        ->set('name', '')
        ->set('email', 'invalid-email')
        ->call('submit')
        ->assertNotDispatched('ga:event');
});
```

### Testing Custom Events

```php
use App\Livewire\DownloadCenter;

it('tracks download event with brochure details', function () {
    $brochure = Brochure::factory()->create([
        'name' => 'Product Catalog',
        'category' => 'products',
    ]);

    Livewire::test(DownloadCenter::class)
        ->call('download', $brochure->id)
        ->assertDispatched('ga:event',
            name: 'ga_download_brochure',
            params: [
                'brochure_id' => $brochure->id,
                'brochure_name' => 'Product Catalog',
                'category' => 'products',
            ]
        );
});
```

### Testing Newsletter Signup

```php
use App\Livewire\NewsletterSignup;

it('tracks newsletter signup event', function () {
    Livewire::test(NewsletterSignup::class)
        ->set('email', 'subscriber@example.com')
        ->call('subscribe')
        ->assertDispatched('ga:event',
            name: 'sign_up',
            params: [
                'method' => 'newsletter',
                'source' => 'footer_widget',
            ]
        );
});
```

## Next steps

- [06-troubleshooting.md](06-troubleshooting.md) - Fix common issues
- [04-examples.md](04-examples.md) - See more examples
- [03-basic-usage.md](03-basic-usage.md) - Review the basics
