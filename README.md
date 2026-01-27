# Livewire Google Analytics

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darvis/livewire-google-analytics.svg?style=flat-square)](https://packagist.org/packages/darvis/livewire-google-analytics)
[![Total Downloads](https://img.shields.io/packagist/dt/darvis/livewire-google-analytics.svg?style=flat-square)](https://packagist.org/packages/darvis/livewire-google-analytics)

Clean and secure Google Analytics 4 event tracking for Laravel Livewire applications.

```php
// Before: Complex and error-prone ❌
$this->js("gtag('event', 'generate_lead', {...})");

// After: Simple and safe ✅
$this->trackLead(['form_name' => 'contact_form']);
```

## Features

- ✅ **Clean API** - No more manual `gtag()` calls in your components
- ✅ **Type-safe** - Full PHP type hints and IDE autocomplete
- ✅ **Secure** - No JavaScript injection vulnerabilities
- ✅ **Zero configuration** - Works out of the box
- ✅ **Livewire 3 & 4** - Full support for both versions
- ✅ **Laravel 10, 11, 12** - Compatible with all modern Laravel versions

## Quick Start

### 1. Install

```bash
composer require darvis/livewire-google-analytics
```

### 2. Add Script to Layout

Add this **once** in your main layout, after `@livewireScripts`:

```blade
@livewireScripts
@include('livewire-google-analytics::script')
```

### 3. Use in Components

```php
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;

class ContactForm extends Component
{
    use TracksAnalytics;

    public function submit()
    {
        $this->validate();
        Contact::create($this->all());
        
        // Track the conversion
        $this->trackLead([
            'form_name' => 'contact_form',
            'lead_type' => 'contact',
        ]);
        
        $this->success = true;
    }
}
```

That's it! 🎉

## Available Methods

### `trackLead()` - Lead Generation

For contact forms, quote requests, demo requests:

```php
$this->trackLead([
    'form_name' => 'contact_form',
    'lead_type' => 'contact',
]);
```

### `trackEvent()` - Any GA4 Event

For standard GA4 events like purchases, logins:

```php
$this->trackEvent('purchase', [
    'transaction_id' => 'T12345',
    'value' => 25.99,
    'currency' => 'EUR',
]);
```

### `trackNewsletterSignup()` - Newsletter Subscriptions

```php
$this->trackNewsletterSignup([
    'source' => 'footer_widget',
]);
```

### `trackCustomEvent()` - Custom Events

Automatically adds `ga_` prefix:

```php
$this->trackCustomEvent('download_brochure', [
    'brochure_name' => 'Product Catalog 2024',
]);
```

## Documentation

📚 **[Complete Documentation](docs/README.md)**

- **[What is this?](docs/01-what-is-this.md)** - Learn what the package does and why
- **[Installation Guide](docs/02-installation.md)** - Detailed installation instructions
- **[Basic Usage](docs/03-basic-usage.md)** - Learn all the methods and best practices
- **[Examples](docs/04-examples.md)** - 7 complete real-world examples
- **[Testing](docs/05-testing.md)** - How to verify your tracking works
- **[Troubleshooting](docs/06-troubleshooting.md)** - Common issues and solutions

📖 **[Quick Start Guide](QUICK_START.md)** - 5-minute beginner-friendly guide

## How It Works

```
PHP Component → Livewire Event → JavaScript Listener → Google Analytics
```

1. You call `$this->trackLead([...])` in your Livewire component
2. The trait dispatches a browser event with the data
3. The JavaScript listener forwards it to `gtag()`
4. Google Analytics receives and processes the event

**Benefits:**
- ✅ Clean separation of PHP and JavaScript
- ✅ No JavaScript injection vulnerabilities
- ✅ Works with async GA4 loading
- ✅ Fails silently if GA4 is blocked

## Requirements

- PHP 8.1 or higher
- Laravel 10, 11, or 12
- Livewire 3 or 4
- Google Analytics 4 property

## Testing

**Browser Console:**
```javascript
// You should see:
[GA4] Event tracked: generate_lead {form_name: "contact_form"}
```

**GA4 Realtime:**
Events appear in Google Analytics within seconds.

**DebugView:**
See detailed event information in GA4 Admin → DebugView.

[Learn more about testing →](docs/05-testing.md)

## Examples

### Contact Form

```php
class ContactForm extends Component
{
    use TracksAnalytics;

    public function submit()
    {
        $validated = $this->validate();
        Contact::create($validated);
        
        $this->trackLead([
            'form_name' => 'contact_form',
            'lead_type' => 'contact',
        ]);
        
        $this->success = true;
    }
}
```

### E-commerce Purchase

```php
class CheckoutForm extends Component
{
    use TracksAnalytics;

    public function completePurchase()
    {
        $order = Order::create([...]);
        
        $this->trackEvent('purchase', [
            'transaction_id' => $order->id,
            'value' => $order->total,
            'currency' => 'EUR',
        ]);
        
        return redirect()->route('order.success');
    }
}
```

[See 7 complete examples →](docs/04-examples.md)

## Best Practices

✅ **Track after success** - Only track after validation and processing  
✅ **Use standard events** - Prefer `trackLead()` over custom events  
✅ **Include context** - Add meaningful parameters for analysis  
✅ **Validate first** - Don't track bot submissions

[Learn all best practices →](docs/03-basic-usage.md#best-practices)

## Troubleshooting

**Events not firing?**
- Check if script is after `@livewireScripts`
- Verify trait is added to component
- Look for JavaScript errors in console

**Events fire multiple times?**
- Don't call tracking in `render()` or `mount()`
- Only track in action methods

[See all troubleshooting solutions →](docs/06-troubleshooting.md)

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security issues, please email info@arvid.nl instead of using the issue tracker.

## Credits

- **[Arvid de Jong](https://github.com/darvis)** - Creator and maintainer
- **[All Contributors](../../contributors)**

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Support

- 📧 **Email:** info@arvid.nl
- 🐛 **Issues:** [GitHub Issues](../../issues)
- 📖 **Documentation:** [docs/README.md](docs/README.md)
