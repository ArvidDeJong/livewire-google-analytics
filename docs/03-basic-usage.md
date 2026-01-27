# Basic Usage

This guide covers the fundamentals of using the package in your Livewire components.

## Quick Start

### Step 1: Add the trait

Add the `TracksAnalytics` trait to any Livewire component where you want to track events:

```php
<?php

namespace App\Livewire;

use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;

class ContactForm extends Component
{
    use TracksAnalytics;  // 👈 Add this trait
    
    // Your component code...
}
```

### Step 2: Track events

Call tracking methods in your action methods (like `submit()`, `save()`, etc.):

```php
public function submit()
{
    // 1. Validate
    $this->validate();
    
    // 2. Process (save to database, send email, etc.)
    Contact::create($this->all());
    
    // 3. Track the event
    $this->trackLead([
        'form_name' => 'contact_form',
        'lead_type' => 'contact',
    ]);
    
    // 4. Show success
    $this->success = true;
}
```

## Available Methods

The `TracksAnalytics` trait provides four main methods:

### 1. `trackLead()` - Lead Generation

Use this for any lead generation form (contact forms, quote requests, demo requests, etc.).

**Tracks as:** `generate_lead` (GA4 standard event)

```php
$this->trackLead([
    'form_name' => 'contact_form',
    'lead_type' => 'contact',
    'source' => 'website',
]);
```

**Common parameters:**
- `form_name` - Which form was submitted
- `lead_type` - Type of lead (contact, quote, demo, etc.)
- `source` - Where the lead came from
- `value` - Optional: assign a value to the lead

### 2. `trackEvent()` - Any GA4 Event

Use this for any standard GA4 event.

```php
$this->trackEvent('purchase', [
    'transaction_id' => 'T12345',
    'value' => 25.99,
    'currency' => 'EUR',
]);
```

**Common GA4 events:**
- `purchase` - E-commerce purchase
- `login` - User login
- `search` - Search performed
- `share` - Content shared
- `view_item` - Product viewed

See [GA4 recommended events](https://support.google.com/analytics/answer/9267735) for more.

### 3. `trackNewsletterSignup()` - Newsletter Subscriptions

Use this for newsletter signups.

**Tracks as:** `sign_up` (GA4 standard event)

```php
$this->trackNewsletterSignup([
    'source' => 'footer_widget',
    'list_name' => 'monthly_newsletter',
]);
```

### 4. `trackCustomEvent()` - Custom Events

Use this for project-specific events. Automatically adds `ga_` prefix.

```php
// Tracks as 'ga_download_brochure'
$this->trackCustomEvent('download_brochure', [
    'brochure_name' => 'Product Catalog 2024',
    'category' => 'products',
]);
```

**When to use custom events:**
- Downloading files
- Watching videos
- Opening modals
- Clicking specific buttons
- Any project-specific action

## Best Practices

### 1. Track AFTER success

Always track events **after** your action has succeeded:

```php
public function submit()
{
    // ✅ CORRECT ORDER
    
    // 1. Validate
    $validated = $this->validate();
    
    // 2. Save to database
    Contact::create($validated);
    
    // 3. Send email
    Mail::to('info@example.com')->send(new ContactMail($validated));
    
    // 4. Track event (after everything succeeded)
    $this->trackLead([...]);
    
    // 5. Show success message
    $this->success = true;
}
```

**Why?** You only want to track successful conversions, not failed attempts.

### 2. Use standard events when possible

GA4 has standard events that work better with built-in reports. Use them when available:

```php
// ✅ GOOD - Uses standard GA4 event
$this->trackLead([...]);  // Uses 'generate_lead'

// ❌ AVOID - Custom event for standard action
$this->trackCustomEvent('contact_form_submit', [...]);
```

**Standard events to prefer:**
- `generate_lead` - Use `trackLead()`
- `sign_up` - Use `trackNewsletterSignup()`
- `purchase` - Use `trackEvent('purchase', ...)`
- `login` - Use `trackEvent('login', ...)`

### 3. Include meaningful parameters

Always include context that helps you analyze conversions:

```php
$this->trackLead([
    'form_name' => 'contact_form',      // Which form
    'lead_type' => 'contact',           // Type of lead
    'source' => 'homepage',             // Where it came from
    'page_url' => request()->url(),     // Current page
    'value' => 1,                       // Optional: lead value
]);
```

### 4. Don't track in render()

**Never** call tracking methods in `render()` or `mount()`:

```php
// ❌ WRONG - Will track on every render
public function render()
{
    $this->trackLead([...]);  // DON'T DO THIS!
    return view('livewire.contact-form');
}

// ✅ CORRECT - Track in action methods
public function submit()
{
    // Process form...
    $this->trackLead([...]);  // DO THIS!
}
```

**Why?** Livewire re-renders components frequently. You'd track the same event multiple times.

### 5. Bot protection

Only track real submissions by validating before tracking:

```php
public function submit()
{
    // Honeypot validation
    try {
        $this->validateHoneypot();
    } catch (ValidationException $e) {
        // Silently fail for bots - don't track
        return;
    }

    // Regular validation
    $validated = $this->validate();

    // Now track (only real submissions reach here)
    $this->trackLead([...]);
}
```

## Common Patterns

### Pattern 1: Simple Contact Form

```php
public function submit()
{
    $validated = $this->validate([
        'name' => 'required',
        'email' => 'required|email',
        'message' => 'required',
    ]);

    Contact::create($validated);

    $this->trackLead([
        'form_name' => 'contact_form',
        'lead_type' => 'contact',
    ]);

    $this->reset();
    $this->success = true;
}
```

### Pattern 2: Multi-step Form

```php
public function submitStep1()
{
    $this->validate(['email' => 'required|email']);
    $this->step = 2;
    // Don't track yet - form not complete
}

public function submitStep2()
{
    $this->validate(['name' => 'required']);
    $this->step = 3;
    // Don't track yet - form not complete
}

public function submitFinal()
{
    $this->validate(['message' => 'required']);
    
    // Save everything
    Contact::create($this->all());
    
    // NOW track - form is complete
    $this->trackLead([
        'form_name' => 'multi_step_contact',
        'lead_type' => 'contact',
        'steps_completed' => 3,
    ]);
    
    $this->success = true;
}
```

### Pattern 3: Conditional Tracking

```php
public function submit()
{
    $validated = $this->validate();
    
    $contact = Contact::create($validated);
    
    // Track different events based on conditions
    if ($contact->is_high_value) {
        $this->trackLead([
            'form_name' => 'contact_form',
            'lead_type' => 'high_value',
            'value' => 100,
        ]);
    } else {
        $this->trackLead([
            'form_name' => 'contact_form',
            'lead_type' => 'standard',
            'value' => 10,
        ]);
    }
    
    $this->success = true;
}
```

## What NOT to do

### ❌ Don't track in loops

```php
// ❌ WRONG - Will track multiple events
foreach ($items as $item) {
    $this->trackLead([...]);
}

// ✅ CORRECT - Track once after loop
foreach ($items as $item) {
    // Process items...
}
$this->trackLead([
    'items_count' => count($items),
]);
```

### ❌ Don't track before validation

```php
// ❌ WRONG - Tracks even if validation fails
public function submit()
{
    $this->trackLead([...]);  // Too early!
    $this->validate();
}

// ✅ CORRECT - Track after validation
public function submit()
{
    $this->validate();
    $this->trackLead([...]);  // After validation!
}
```

### ❌ Don't use JavaScript injection

```php
// ❌ WRONG - Unsafe and defeats the purpose of this package
$this->js("gtag('event', 'generate_lead', {...})");

// ✅ CORRECT - Use the trait methods
$this->trackLead([...]);
```

## Next steps

- [04-examples.md](04-examples.md) - See complete real-world examples
- [05-testing.md](05-testing.md) - Learn how to test your tracking
- [06-troubleshooting.md](06-troubleshooting.md) - Fix common issues
