# Troubleshooting

This guide helps you solve common problems when using the package.

## Installation Issues

### Package not found

**Problem:** `composer require` fails with "Package not found".

**Solutions:**
- Check the package name: `darvis/livewire-google-analytics`
- Make sure you have internet connection
- Try `composer clear-cache` and try again
- Check if your `composer.json` has the correct minimum stability

### Trait not found

**Problem:** `Class 'Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics' not found`

**Solutions:**
- Run `composer dump-autoload`
- Check if package is in `vendor/darvis/livewire-google-analytics`
- Make sure the package installed correctly: `composer show darvis/livewire-google-analytics`

### View not found

**Problem:** `View [livewire-google-analytics::script] not found`

**Solutions:**
- Check if package is installed: `composer show darvis/livewire-google-analytics`
- Run `php artisan view:clear`
- Run `php artisan config:clear`
- Verify the package service provider is registered

## Tracking Issues

### Events not firing

**Problem:** No events appear in console or GA4.

**Diagnosis:**

1. Check browser console for errors
2. Verify `@include('livewire-google-analytics::script')` is in layout
3. Make sure it's after `@livewireScripts`

**Solutions:**

```blade
{{-- ❌ WRONG - Script before Livewire --}}
@include('livewire-google-analytics::script')
@livewireScripts

{{-- ✅ CORRECT - Script after Livewire --}}
@livewireScripts
@include('livewire-google-analytics::script')
```

### Events fire multiple times

**Problem:** Same event tracked 2-3 times per action.

**Common causes:**

1. **Tracking in render()** - Most common mistake

```php
// ❌ WRONG - Tracks on every render
public function render()
{
    $this->trackLead([...]);
    return view('livewire.contact-form');
}

// ✅ CORRECT - Track in action method
public function submit()
{
    $this->validate();
    $this->trackLead([...]);
}
```

2. **Multiple script includes**

```blade
{{-- ❌ WRONG - Script included multiple times --}}
@include('livewire-google-analytics::script')
@include('livewire-google-analytics::script')

{{-- ✅ CORRECT - Include once --}}
@include('livewire-google-analytics::script')
```

3. **Tracking in loops**

```php
// ❌ WRONG - Tracks multiple times
foreach ($items as $item) {
    $this->trackLead([...]);
}

// ✅ CORRECT - Track once after loop
foreach ($items as $item) {
    // Process items
}
$this->trackLead(['items_count' => count($items)]);
```

### gtag is not a function

**Problem:** Console shows `gtag is not a function` error.

**Diagnosis:**

Type `typeof gtag` in browser console:
- Returns `'function'` → gtag is loaded ✅
- Returns `'undefined'` → gtag is not loaded ❌

**Solutions:**

1. **Check if GA4 script is loaded**

View page source and look for:

```html
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXX"></script>
```

2. **Check environment**

```blade
{{-- Make sure this condition is true --}}
@if(app()->environment('production'))
    <!-- GA4 script -->
@endif
```

3. **Disable ad blockers**

Ad blockers often block Google Analytics. Temporarily disable them for testing.

4. **Check script order**

GA4 script must load before the package script:

```blade
<head>
    <!-- GA4 script here -->
</head>
<body>
    @livewireScripts
    @include('livewire-google-analytics::script')
</body>
```

### Events in console but not in GA4

**Problem:** Console shows events, but they don't appear in GA4.

**Solutions:**

1. **Wait longer** - GA4 can take 5-10 minutes for events to appear in reports (but Realtime should be instant)

2. **Check Measurement ID**

```blade
{{-- Make sure this matches your GA4 property --}}
gtag('config', 'G-XXXXXXXXX');
```

3. **Use DebugView instead of Realtime**

DebugView is more reliable for testing. See [05-testing.md](05-testing.md).

4. **Check production domain**

If you have domain restrictions:

```blade
@if(in_array(request()->getHost(), ['yourdomain.com', 'www.yourdomain.com']))
    <!-- GA4 script -->
@endif
```

Make sure you're testing on the correct domain.

5. **Disable privacy tools**

Privacy extensions and VPNs can block GA4. Disable temporarily for testing.

### Missing parameters

**Problem:** Event tracked but parameters are missing in GA4.

**Common causes:**

1. **Not passing parameters**

```php
// ❌ WRONG - No parameters
$this->trackLead();

// ✅ CORRECT - With parameters
$this->trackLead([
    'form_name' => 'contact_form',
]);
```

2. **Null or undefined values**

```php
// ❌ WRONG - Variable might be null
$this->trackLead([
    'location' => $this->location,  // null if not set
]);

// ✅ CORRECT - Provide default or check
$this->trackLead([
    'location' => $this->location ?? 'unknown',
]);
```

3. **Wrong parameter format**

```php
// ❌ WRONG - Not an array
$this->trackLead('contact_form');

// ✅ CORRECT - Array of parameters
$this->trackLead(['form_name' => 'contact_form']);
```

## JavaScript Issues

### Script not loading

**Problem:** No console messages, events don't work.

**Diagnosis:**

View page source and search for `ga:event`. You should find:

```javascript
window.addEventListener('ga:event', function (event) {
```

If not found, the script isn't loading.

**Solutions:**

1. **Clear cache**

```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

2. **Check layout file**

Make sure you're editing the correct layout file that's actually being used.

3. **Check Blade syntax**

```blade
{{-- ✅ CORRECT --}}
@include('livewire-google-analytics::script')

{{-- ❌ WRONG --}}
@include('livewire-google-analytics:script')  // Missing colon
@include('livewire-google-analytics.script')  // Wrong separator
```

### Livewire events not dispatching

**Problem:** Tracking methods don't dispatch browser events.

**Solutions:**

1. **Check Livewire version**

```bash
composer show livewire/livewire
```

Should be 3.x or 4.x. If 2.x, upgrade Livewire.

2. **Check trait is added**

```php
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;

class ContactForm extends Component
{
    use TracksAnalytics;  // Make sure this is here
}
```

3. **Check method exists**

```php
// ✅ CORRECT - Method exists
$this->trackLead([...]);

// ❌ WRONG - Typo
$this->trackLeads([...]);
```

## GA4 Configuration Issues

### Wrong Measurement ID

**Problem:** Events don't appear in your GA4 property.

**Solution:**

1. Go to GA4 → Admin → Data Streams
2. Copy the Measurement ID (starts with `G-`)
3. Update your layout:

```blade
gtag('config', 'G-XXXXXXXXX');  {{-- Use your actual ID --}}
```

### Events not showing in reports

**Problem:** Events appear in Realtime/DebugView but not in reports.

**Explanation:** GA4 reports can take 24-48 hours to update. This is normal.

**Solution:** Use Realtime or DebugView for immediate verification.

### Custom event parameters not showing

**Problem:** Event appears but custom parameters don't show in GA4.

**Solution:** Register custom dimensions in GA4:

1. Go to GA4 → Admin → Custom Definitions
2. Click **Create custom dimension**
3. Add your parameter name (e.g., `form_name`)
4. Set scope to **Event**
5. Save

Now the parameter will appear in reports.

## Laravel/Livewire Issues

### Trait conflicts

**Problem:** Error about trait method conflicts.

**Solution:**

If you have multiple traits with the same method name:

```php
use TracksAnalytics, OtherTrait {
    TracksAnalytics::trackEvent insteadof OtherTrait;
}
```

### Events not working in modals

**Problem:** Tracking doesn't work in Livewire modals.

**Solution:**

Make sure the modal library doesn't interfere with Livewire events. The package should work with most modal libraries (Wire Elements Modal, etc.).

If issues persist, dispatch events manually:

```php
$this->dispatch('ga:event', 
    name: 'generate_lead',
    params: ['form_name' => 'modal_form']
);
```

### Events not working in nested components

**Problem:** Tracking doesn't work in child components.

**Solution:**

Add the trait to the child component:

```php
class ChildComponent extends Component
{
    use TracksAnalytics;  // Add trait here too
    
    public function submit()
    {
        $this->trackLead([...]);
    }
}
```

## Performance Issues

### Slow page loads

**Problem:** Pages load slowly after adding the package.

**Diagnosis:**

The package script is tiny (~20 lines) and shouldn't affect performance.

**Likely causes:**

1. GA4 script loading synchronously
2. Too many tracking calls

**Solutions:**

1. **Load GA4 async**

```blade
<script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXX"></script>
```

2. **Don't track in loops or render()**

```php
// ❌ WRONG - Tracks many times
public function render()
{
    $this->trackLead([...]);
    return view('...');
}
```

## Getting Help

If you're still stuck:

### 1. Check browser console

Press `F12` and look for:
- JavaScript errors
- Network errors
- Console messages

### 2. Check Laravel logs

```bash
tail -f storage/logs/laravel.log
```

### 3. Enable debug mode

Add to your layout temporarily:

```blade
<script>
console.log('Livewire loaded:', typeof Livewire !== 'undefined');
console.log('gtag loaded:', typeof gtag !== 'undefined');

window.addEventListener('ga:event', function(e) {
    console.log('GA event received:', e.detail);
});
</script>
```

### 4. Test with minimal example

Create a simple test component:

```php
class Test extends Component
{
    use TracksAnalytics;
    
    public function test()
    {
        $this->trackLead(['test' => 'value']);
        session()->flash('message', 'Event tracked');
    }
    
    public function render()
    {
        return <<<'HTML'
        <div>
            <button wire:click="test">Test</button>
            @if(session('message'))
                <p>{{ session('message') }}</p>
            @endif
        </div>
        HTML;
    }
}
```

If this works, the issue is in your main component.

### 5. Contact support

Still stuck? Open an issue on GitHub with:

- Laravel version
- Livewire version
- PHP version
- Browser console output
- Relevant code snippets

Or email: info@arvid.nl

## Next steps

- [05-testing.md](05-testing.md) - Learn testing strategies
- [03-basic-usage.md](03-basic-usage.md) - Review the basics
- [04-examples.md](04-examples.md) - See working examples
