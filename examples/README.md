# Examples

This directory contains complete, working examples that you can copy and adapt for your own project.

## Available Examples

### 1. Contact Form (`ContactForm.php`)

A simple contact form that:
- ✅ Validates user input
- ✅ Sends an email
- ✅ Tracks the submission in Google Analytics
- ✅ Shows a success message

**Files:**
- `ContactForm.php` - The Livewire component
- `contact-form.blade.php` - The Blade view

**How to use:**
1. Copy `ContactForm.php` to `app/Livewire/`
2. Copy `contact-form.blade.php` to `resources/views/livewire/`
3. Update the email address in the component
4. Visit the page in your browser

---

## How to Add These to Your Project

### Step 1: Copy the Files

```bash
# Copy the component
cp examples/ContactForm.php app/Livewire/

# Copy the view
cp examples/contact-form.blade.php resources/views/livewire/
```

### Step 2: Update the Namespace

Open `ContactForm.php` and change:
```php
namespace App\Livewire\Examples;
```

To:
```php
namespace App\Livewire;
```

### Step 3: Create a Route

Add to your `routes/web.php`:
```php
Route::get('/contact', App\Livewire\ContactForm::class);
```

### Step 4: Test It!

Visit `http://your-app.test/contact` and submit the form.

---

## Understanding the Code

### The Component Structure

```php
class ContactForm extends Component
{
    use TracksAnalytics;  // 👈 This adds tracking methods
    
    // Properties for form fields
    public string $name = '';
    public string $email = '';
    
    // Method that handles form submission
    public function submit()
    {
        // 1. Validate
        $this->validate();
        
        // 2. Process (save/email)
        // ...
        
        // 3. Track in Google Analytics
        $this->trackLead([...]);
        
        // 4. Show success
        $this->success = true;
    }
}
```

### The Tracking Call

```php
$this->trackLead([
    'form_name' => 'contact_form',  // Identifies which form
    'lead_type' => 'contact',       // Type of lead
    'source' => 'livewire',         // Technical source
]);
```

This sends a `generate_lead` event to Google Analytics with these parameters.

### The Blade View

```blade
<form wire:submit="submit">
    <input wire:model="name">
    @error('name') <span>{{ $message }}</span> @enderror
    
    <button type="submit">Send</button>
</form>
```

- `wire:submit="submit"` - Calls the `submit()` method when form is submitted
- `wire:model="name"` - Binds input to the `$name` property
- `@error('name')` - Shows validation errors

---

## Customizing the Examples

### Change the Event Type

Instead of `trackLead()`, you can use:

```php
// For newsletter signups
$this->trackNewsletterSignup([
    'source' => 'footer',
]);

// For custom events
$this->trackEvent('form_submit', [
    'form_type' => 'feedback',
]);

// For project-specific events (adds ga_ prefix)
$this->trackCustomEvent('download_brochure', [
    'brochure_name' => 'Product Catalog',
]);
```

### Add More Parameters

```php
$this->trackLead([
    'form_name' => 'contact_form',
    'lead_type' => 'contact',
    'source' => 'livewire',
    'location' => 'Netherlands',        // Add location
    'page' => request()->path(),        // Add current page
    'referrer' => request()->header('referer'), // Add referrer
]);
```

### Add Honeypot Protection

To prevent spam bots:

```bash
composer require darvis/livewire-honeypot
```

Then update your component:

```php
use Darvis\LivewireHoneypot\Traits\HasHoneypot;

class ContactForm extends Component
{
    use TracksAnalytics, HasHoneypot;
    
    public function submit()
    {
        // Validate honeypot first
        try {
            $this->validateHoneypot();
        } catch (ValidationException $e) {
            // Silently fail for bots
            return;
        }
        
        // Continue with normal processing...
    }
}
```

And add to your view:
```blade
<form wire:submit="submit">
    <x-honeypot />
    <!-- rest of form -->
</form>
```

---

## Need More Examples?

Open an issue on GitHub with your use case and we'll add more examples!

**Author:** Arvid de Jong | info@arvid.nl
