# Quick Start Guide

**For Beginners** - Get started with Google Analytics tracking in your Laravel Livewire app in 5 minutes!

## What This Package Does

This package makes it **super easy** to track user actions (like form submissions) in Google Analytics 4 without writing complicated JavaScript code.

**Before this package:**
```php
// Complex and error-prone ❌
$this->js("
    if (typeof gtag === 'function') {
        gtag('event', 'generate_lead', {
            location: '{$this->location}' // Can break with quotes!
        });
    }
");
```

**With this package:**
```php
// Simple and safe ✅
$this->trackLead([
    'location' => $this->location
]);
```

---

## Step-by-Step Installation

### Step 1: Install the Package

Open your terminal in your Laravel project folder and run:

```bash
composer require darvis/livewire-google-analytics
```

That's it! Laravel will automatically discover and register the package.

### Step 2: Add JavaScript Listener

Open your main layout file (usually `resources/views/layouts/app.blade.php` or similar).

Find the line that says `@livewireScripts` and add this right after it:

```blade
@livewireScripts

{{-- Add this line 👇 --}}
@include('livewire-google-analytics::script')
```

**Complete example:**
```blade
<!DOCTYPE html>
<html>
<head>
    <title>My App</title>
</head>
<body>
    {{ $slot }}
    
    @livewireScripts
    @include('livewire-google-analytics::script')
</body>
</html>
```

### Step 3: Use in Your Livewire Component

Open any Livewire component where you want to track events (like a contact form).

**Add the trait at the top:**
```php
<?php

namespace App\Livewire;

use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics; // 👈 Add this
use Livewire\Component;

class ContactForm extends Component
{
    use TracksAnalytics; // 👈 Add this
    
    public $name = '';
    public $email = '';
    public $message = '';
    
    // Your existing code...
}
```

**Track events in your methods:**
```php
public function submit()
{
    // 1. Validate the form
    $this->validate([
        'name' => 'required',
        'email' => 'required|email',
        'message' => 'required',
    ]);
    
    // 2. Save to database or send email
    // ... your code here ...
    
    // 3. Track the event in Google Analytics
    $this->trackLead([
        'form_name' => 'contact_form',
        'source' => 'website',
    ]);
    
    // 4. Show success message
    session()->flash('success', 'Thank you for contacting us!');
}
```

---

## Complete Working Example

Here's a **complete contact form** that tracks submissions:

```php
<?php

namespace App\Livewire;

use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class ContactForm extends Component
{
    use TracksAnalytics;
    
    public $name = '';
    public $email = '';
    public $message = '';
    public $success = false;
    
    public function submit()
    {
        // Validate
        $validated = $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email',
            'message' => 'required|min:10',
        ]);
        
        // Send email
        Mail::to('info@example.com')->send(
            new ContactMail($validated)
        );
        
        // Track in Google Analytics
        $this->trackLead([
            'form_name' => 'contact_form',
            'lead_type' => 'contact',
            'source' => 'livewire',
        ]);
        
        // Reset form and show success
        $this->reset(['name', 'email', 'message']);
        $this->success = true;
    }
    
    public function render()
    {
        return view('livewire.contact-form');
    }
}
```

**Blade view** (`resources/views/livewire/contact-form.blade.php`):
```blade
<div>
    @if($success)
        <div class="alert alert-success">
            Thank you! We'll get back to you soon.
        </div>
    @else
        <form wire:submit="submit">
            <div>
                <label>Name</label>
                <input type="text" wire:model="name">
                @error('name') <span>{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label>Email</label>
                <input type="email" wire:model="email">
                @error('email') <span>{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label>Message</label>
                <textarea wire:model="message"></textarea>
                @error('message') <span>{{ $message }}</span> @enderror
            </div>
            
            <button type="submit">Send</button>
        </form>
    @endif
</div>
```

---

## Available Methods

### `trackLead()` - For Lead Generation

Use this for contact forms, quote requests, demo requests, etc.

```php
$this->trackLead([
    'form_name' => 'contact_form',  // Which form
    'lead_type' => 'contact',       // Type of lead
    'source' => 'livewire',         // Where it came from
]);
```

### `trackEvent()` - For Any Custom Event

Use this for any custom tracking:

```php
$this->trackEvent('button_click', [
    'button_name' => 'download_brochure',
    'location' => 'homepage',
]);
```

### `trackNewsletterSignup()` - For Newsletter Subscriptions

```php
$this->trackNewsletterSignup([
    'source' => 'footer_widget',
]);
```

### `trackCustomEvent()` - For Project-Specific Events

Automatically adds `ga_` prefix:

```php
// Tracks as 'ga_download_brochure'
$this->trackCustomEvent('download_brochure', [
    'brochure_name' => 'Product Catalog 2024',
]);
```

---

## Checking If It Works

### Method 1: Browser Console

1. Open your website
2. Press `F12` to open Developer Tools
3. Go to the **Console** tab
4. Submit your form
5. You should see: `[GA4] Event tracked: generate_lead {...}`

### Method 2: Google Analytics Realtime

1. Go to [Google Analytics](https://analytics.google.com/)
2. Click **Reports** → **Realtime**
3. Submit your form on your website
4. The event should appear within seconds!

### Method 3: DebugView (Most Detailed)

1. Install [Google Analytics Debugger](https://chrome.google.com/webstore/detail/google-analytics-debugger/) Chrome extension
2. Go to Google Analytics → **Admin** → **DebugView**
3. Submit your form
4. See detailed event information in DebugView

---

## Common Questions

### Q: Do I need to configure anything?

**A:** No! The package works out of the box. Just install, add the script, and use the trait.

### Q: What if I don't have Google Analytics set up?

**A:** You need to:
1. Create a Google Analytics 4 property
2. Add the GA4 script to your layout (see [Google's guide](https://support.google.com/analytics/answer/9304153))
3. Then this package will track events to your GA4 property

### Q: Will it break my site if Google Analytics is blocked?

**A:** No! The package fails silently if GA4 is not available (ad blockers, privacy tools, etc.).

### Q: Can I use this in multiple components?

**A:** Yes! Add the `use TracksAnalytics;` trait to any component where you want to track events.

### Q: What if I'm not using Livewire?

**A:** This package is specifically for Livewire. For vanilla Laravel, you'll need to use JavaScript directly.

---

## Next Steps

- Read the [full README](README.md) for advanced usage
- Check out [Best Practices](README.md#best-practices) section
- Learn about [all available methods](README.md#available-methods)

## Need Help?

- Check the [Troubleshooting](README.md#troubleshooting) section
- Open an issue on GitHub
- Email: info@arvid.nl

---

**Author:** Arvid de Jong | info@arvid.nl
