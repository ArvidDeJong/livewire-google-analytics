# Contributing Guide

Thank you for considering contributing to this package! This guide will help you get started.

## For Beginners

Don't worry if you're new to package development! This guide explains everything step by step.

## Development Setup

### 1. Clone the Repository

```bash
git clone https://github.com/darvis/livewire-google-analytics.git
cd livewire-google-analytics
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Link to a Test Project

To test your changes in a real Laravel project:

```bash
# In your Laravel project
composer config repositories.livewire-google-analytics path ../path/to/livewire-google-analytics
composer require darvis/livewire-google-analytics:@dev
```

Now any changes you make to the package will be immediately reflected in your test project!

## Package Structure

```
livewire-google-analytics/
├── src/                          # Main source code
│   ├── Traits/
│   │   └── TracksAnalytics.php  # The main trait
│   └── GoogleAnalyticsServiceProvider.php
├── resources/
│   ├── js/
│   │   └── google-analytics.js  # JavaScript listener
│   └── views/
│       └── script.blade.php     # Blade component
├── examples/                     # Working examples
├── README.md                     # Main documentation
├── QUICK_START.md               # Beginner guide
└── CHANGELOG.md                 # Version history
```

## How It Works

### The Flow

1. **PHP Side** - Component calls `$this->trackLead([...])`
2. **Livewire** - Dispatches browser event `ga:event`
3. **JavaScript** - Listener catches event and calls `gtag()`
4. **Google Analytics** - Receives and processes the event

### Key Files

#### `src/Traits/TracksAnalytics.php`

This is the main trait that developers use. It provides methods like:
- `trackLead()` - For lead generation
- `trackEvent()` - For custom events
- `trackNewsletterSignup()` - For newsletter signups

**How it works:**
```php
protected function trackLead(array $params = []): void
{
    // Dispatches a browser event that JavaScript will catch
    $this->dispatch('ga:event', name: 'generate_lead', params: $params);
}
```

#### `resources/js/google-analytics.js`

This JavaScript file listens for the `ga:event` browser event and forwards it to Google Analytics.

**How it works:**
```javascript
window.addEventListener('ga:event', function (event) {
    const detail = event.detail || {};
    if (detail.name && typeof window.gtag === 'function') {
        window.gtag('event', detail.name, detail.params);
    }
});
```

## Making Changes

### Adding a New Method

Want to add a new tracking method? Here's how:

1. **Open `src/Traits/TracksAnalytics.php`**

2. **Add your method:**
```php
/**
 * Track a purchase event.
 *
 * @param array $params Event parameters
 * @return void
 */
protected function trackPurchase(array $params = []): void
{
    $this->trackEvent('purchase', $params);
}
```

3. **Update the README** with usage example

4. **Add an example** in `examples/` directory

5. **Update CHANGELOG.md**

### Modifying the JavaScript

If you need to change how events are handled:

1. **Open `resources/js/google-analytics.js`**

2. **Make your changes** (be careful not to break existing functionality)

3. **Test thoroughly** in a real Laravel project

4. **Update documentation** if behavior changes

## Testing Your Changes

### Manual Testing

1. **Create a test Laravel project** or use an existing one

2. **Link your package:**
```bash
composer config repositories.livewire-google-analytics path ../livewire-google-analytics
composer require darvis/livewire-google-analytics:@dev
```

3. **Create a test component:**
```php
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;

class TestComponent extends Component
{
    use TracksAnalytics;
    
    public function test()
    {
        $this->trackLead(['test' => 'value']);
    }
}
```

4. **Check browser console** for `[GA4]` debug messages

5. **Check Google Analytics** DebugView for events

### What to Test

- ✅ Events fire correctly
- ✅ Parameters are passed correctly
- ✅ No JavaScript errors
- ✅ Works with ad blockers (should fail silently)
- ✅ Works when gtag is not loaded (should fail silently)
- ✅ Works in different browsers

## Code Style

### PHP

Follow PSR-12 coding standards:

```php
// Good ✅
protected function trackEvent(string $name, array $params = []): void
{
    $this->dispatch('ga:event', name: $name, params: $params);
}

// Bad ❌
protected function trackEvent($name,$params=[])
{
    $this->dispatch('ga:event',name:$name,params:$params);
}
```

### JavaScript

Use clear, readable code:

```javascript
// Good ✅
function fireGaEvent(name, params) {
    if (typeof window.gtag !== 'function') {
        return;
    }
    window.gtag('event', name, params || {});
}

// Bad ❌
function fireGaEvent(n,p){window.gtag('event',n,p||{})}
```

### Documentation

- Write clear comments
- Use proper grammar and spelling
- Include code examples
- Explain WHY, not just WHAT

## Submitting Changes

### 1. Create a Branch

```bash
git checkout -b feature/my-new-feature
```

### 2. Make Your Changes

- Write clean code
- Add comments
- Update documentation
- Test thoroughly

### 3. Commit Your Changes

```bash
git add .
git commit -m "Add feature: description of what you added"
```

Use clear commit messages:
- ✅ "Add trackPurchase method for e-commerce tracking"
- ❌ "update stuff"

### 4. Push to GitHub

```bash
git push origin feature/my-new-feature
```

### 5. Create a Pull Request

Go to GitHub and create a Pull Request with:
- **Title:** Clear description of what you changed
- **Description:** 
  - What problem does this solve?
  - How did you solve it?
  - Any breaking changes?
  - Screenshots (if UI changes)

## Documentation

### When to Update Documentation

Update documentation when you:
- Add a new method
- Change existing behavior
- Add new features
- Fix bugs that affect usage

### Which Files to Update

- `README.md` - Main documentation
- `QUICK_START.md` - If it affects beginners
- `CHANGELOG.md` - Always update this
- `examples/` - Add examples for new features

## Questions?

- **Email:** info@arvid.nl
- **GitHub Issues:** Open an issue with your question
- **Pull Request:** Ask in the PR comments

## Code of Conduct

- Be respectful and professional
- Help others learn
- Accept constructive criticism
- Focus on what's best for the project

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

---

**Thank you for contributing!** 🎉

**Author:** Arvid de Jong | info@arvid.nl
