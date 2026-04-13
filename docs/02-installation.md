# Installation

This guide shows you step-by-step how to install and configure the package.

## Requirements

Before you begin, make sure you have:

- ✅ Laravel 10, 11, 12, or 13
- ✅ Livewire 3 or 4
- ✅ PHP 8.1 or higher
- ✅ A Google Analytics 4 property (optional for testing)

## Step 1: Install the package

Open your terminal in your Laravel project and run:

```bash
composer require darvis/livewire-google-analytics
```

**That's it!** Laravel will automatically discover and register the package.

### What happens now?

- The package is downloaded to `vendor/darvis/livewire-google-analytics`
- The service provider is automatically registered
- The `TracksAnalytics` trait is now available in your project

## Step 2: Add the JavaScript listener

The package needs a small JavaScript listener to forward events from Livewire to Google Analytics.

### Where to add it?

Open your **main layout file**. This is usually one of these files:

- `resources/views/layouts/app.blade.php`
- `resources/views/components/layout.blade.php`
- `resources/views/layouts/guest.blade.php`

### What to add?

Find the line with `@livewireScripts` and add this **directly below it**:

```blade
@livewireScripts

{{-- Add this line 👇 --}}
@include('livewire-google-analytics::script')
```

### Complete example

Your layout should look like this:

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Website</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    {{ $slot }}
    
    @livewireScripts
    @include('livewire-google-analytics::script')  {{-- ✅ Add this --}}
</body>
</html>
```

### What does this script do?

The script listens for Livewire events and forwards them to Google Analytics. It is:
- **Small** - Just a few lines of JavaScript
- **Safe** - Only works if Google Analytics is loaded
- **Silent** - No errors if GA4 is blocked (ad blockers)

## Step 3: Set up Google Analytics 4 (optional)

If you already have Google Analytics 4 set up, you can skip this step.

### Add GA4 script

Add the Google Analytics 4 tracking code to your layout (in the `<head>` section):

```blade
<head>
    <!-- ... other head content ... -->
    
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
</head>
```

**Replace `G-XXXXXXXXX`** with your own Google Analytics Measurement ID.

### Where to find your Measurement ID?

1. Go to [Google Analytics](https://analytics.google.com/)
2. Click **Admin** (gear icon bottom left)
3. Click **Data Streams**
4. Select your website
5. Copy the **Measurement ID** (starts with `G-`)

### Track only on production

Note the `@if(app()->environment('production'))` check. This ensures:
- ✅ Events are only tracked on your live website
- ✅ Your local development doesn't pollute analytics
- ✅ Test data doesn't appear in your reports

## Step 4: Test the installation

### Option 1: Browser Console (Fastest way)

1. Open your website in your browser
2. Press `F12` to open Developer Tools
3. Go to the **Console** tab
4. You should see: `[GA4] Livewire Google Analytics listener initialized`

If you see this message, the JavaScript listener is working!

### Option 2: Test with a simple component

Create a test Livewire component:

```bash
php artisan make:livewire TestAnalytics
```

Add the trait and a test method:

```php
<?php

namespace App\Livewire;

use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;

class TestAnalytics extends Component
{
    use TracksAnalytics;
    
    public function testEvent()
    {
        $this->trackLead([
            'form_name' => 'test',
            'source' => 'test_component',
        ]);
        
        session()->flash('message', 'Event tracked!');
    }
    
    public function render()
    {
        return view('livewire.test-analytics');
    }
}
```

Create the view `resources/views/livewire/test-analytics.blade.php`:

```blade
<div>
    @if(session('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif
    
    <button wire:click="testEvent">
        Track Test Event
    </button>
</div>
```

Add it to a route and click the button. Check the browser console for:

```
[GA4] Event tracked: generate_lead {form_name: "test", source: "test_component"}
```

## Troubleshooting

### Script not loading

**Problem:** No message in console about listener initialization.

**Solution:** 
- Check if `@include('livewire-google-analytics::script')` is in your layout
- Make sure it's **after** `@livewireScripts`
- Clear your browser cache (`Ctrl+Shift+R` or `Cmd+Shift+R`)

### Events not showing in console

**Problem:** No event messages when clicking buttons.

**Solution:**
- Make sure you added the `use TracksAnalytics;` trait to your component
- Check if you're calling the tracking method in an action method (not in `render()`)
- Open browser console and look for JavaScript errors

### GA4 not receiving events

**Problem:** Events show in console but not in Google Analytics.

**Solution:**
- Check if `gtag` is loaded: type `typeof gtag` in console (should be `'function'`)
- Make sure you're on a production domain (if you have the environment check)
- Wait a few minutes - GA4 can have a delay
- Use GA4 DebugView for real-time verification (see [05-testing.md](05-testing.md))

## Next steps

Now that you have the package installed, learn how to use it:

- [03-basic-usage.md](03-basic-usage.md) - Learn the basics
- [04-examples.md](04-examples.md) - See practical examples
- [05-testing.md](05-testing.md) - Learn how to test your tracking

## Need help?

- Check the [troubleshooting section](#troubleshooting) above
- Read the main [README.md](../README.md)
- Open an issue on GitHub
- Email: info@arvid.nl
