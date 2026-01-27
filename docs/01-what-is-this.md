# What is Livewire Google Analytics?

## Who is this package for?

This package is specifically designed for **Laravel developers** who:
- Use Livewire for forms and interactive components
- Want to track conversions in Google Analytics 4 (GA4)
- Need a **safe and simple** way to track events
- Don't want to write complex JavaScript code

## What does this package do?

This package makes it **super easy** to track user actions (like form submissions) in Google Analytics 4 from your Livewire components.

### The problem without this package

Normally, you'd have to write complex JavaScript code in Livewire to track GA4 events:

```php
// ❌ Complex and error-prone
$this->js("
    if (typeof gtag === 'function') {
        gtag('event', 'generate_lead', {
            location: '{$this->location}' // Can break with quotes!
        });
    }
");
```

**Problems with this approach:**
- Events can fire **multiple times** due to Livewire re-renders
- JavaScript can **break** with unsafe variables
- Code is **hard to maintain**
- Inconsistent event names across your application

### The solution with this package

```php
// ✅ Simple and safe
$this->trackLead([
    'location' => $this->location
]);
```

**Benefits:**
- ✅ **Clean API** - No more manual `gtag()` calls
- ✅ **Type-safe** - Full PHP type hints and IDE autocomplete
- ✅ **Secure** - No JavaScript injection vulnerabilities
- ✅ **Consistent** - Standardized event tracking
- ✅ **Zero configuration** - Works out of the box

## How does it work?

The package works in 4 simple steps:

```
1. PHP Code (your Livewire component)
   ↓
   $this->trackLead(['form_name' => 'contact'])
   
2. Livewire Browser Event
   ↓
   dispatch('ga:event', ...)
   
3. JavaScript Listener (automatic)
   ↓
   gtag('event', 'generate_lead', {...})
   
4. Google Analytics 4
   ↓
   Event is stored and visible in your GA4 dashboard
```

### Why is this approach better?

- **Separation of PHP and JavaScript** - No need to write JavaScript in your PHP
- **Safe** - All data is automatically escaped correctly
- **Reliable** - Events won't be tracked twice due to re-renders
- **Centrally managed** - One place for all tracking logic

## What can you track?

### Standard GA4 Events

The package has built-in methods for common GA4 events:

- **`trackLead()`** - For lead generation (contact forms, quotes, demo requests)
- **`trackNewsletterSignup()`** - For newsletter subscriptions
- **`trackEvent()`** - For any GA4 event (purchase, login, etc.)

### Custom Events

- **`trackCustomEvent()`** - For project-specific events (download brochure, video viewed, etc.)

## Who is this NOT for?

- If you **don't use Livewire** (this package is Livewire-specific)
- If you **don't use Google Analytics 4** (doesn't work with Universal Analytics)
- If you **don't want to track events** from your backend code

## Next step

Ready to get started? Go to [02-installation.md](02-installation.md) for installation instructions.
