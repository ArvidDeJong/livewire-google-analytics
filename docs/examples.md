---
title: Examples
nav_order: 5
description: "Complete Livewire components that track a contact form lead, a newsletter signup, a purchase before a redirect and a file download."
---

# Examples

Every example assumes the listener is in your layout, see [Installation](installation.md).

## Contact form

```php
namespace App\Livewire;

use App\Mail\ContactMail;
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class ContactForm extends Component
{
    use TracksAnalytics;

    public string $name = '';

    public string $email = '';

    public string $message = '';

    public bool $success = false;

    public function submit(): void
    {
        $validated = $this->validate([
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:100',
            'message' => 'required|min:10|max:2000',
        ]);

        Mail::to('info@example.com')->send(new ContactMail($validated));

        // After the work: a rejected form never gets here.
        $this->trackLead([
            'form_name' => 'contact_form',
            'lead_type' => 'contact',
        ]);

        $this->reset(['name', 'email', 'message']);
        $this->success = true;
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
```

`App\Mail\ContactMail` is your own mailable. Note what is not in the parameters: the name, the e-mail address and the message.

{% raw %}
```blade
<div>
    @if ($success)
        <p>Thank you, we will get back to you soon.</p>
    @else
        <form wire:submit="submit">
            <input type="text" wire:model="name">
            @error('name') <span>{{ $message }}</span> @enderror

            <input type="email" wire:model="email">
            @error('email') <span>{{ $message }}</span> @enderror

            <textarea wire:model="message"></textarea>
            @error('message') <span>{{ $message }}</span> @enderror

            <button type="submit">Send</button>
        </form>
    @endif
</div>
```
{% endraw %}

## Newsletter signup

```php
public function subscribe(): void
{
    $this->validate(['email' => 'required|email']);

    Subscriber::firstOrCreate(['email' => $this->email]);

    $this->trackNewsletterSignup(['source' => 'footer']);
    // sign_up {method: 'newsletter', source: 'footer'}

    $this->reset('email');
}
```

## Purchase

```php
public function completePurchase(): void
{
    $order = $this->cart->checkout();

    $this->trackEvent('purchase', [
        'transaction_id' => (string) $order->id,
        'value' => (float) $order->total,
        'currency' => 'EUR',
    ]);

    $this->orderId = $order->id;
}
```

## Purchase, then a redirect

```php
public function completePurchase(): void
{
    $order = $this->cart->checkout();

    $this->trackEventAfterRedirect('purchase', [
        'transaction_id' => (string) $order->id,
        'value' => (float) $order->total,
        'currency' => 'EUR',
    ]);

    $this->redirectRoute('orders.thanks', $order);
}
```

The event waits in the session and the listener view on the thank-you page sends it, once; a reload of that page sends nothing. The thank-you page has to include `livewire-google-analytics::script`. The published JavaScript file alone cannot do this.

With a plain `trackEvent()` here, the browser event would fire on the checkout page while the browser is already leaving it.

## File download

```php
public function download(int $brochureId)
{
    $brochure = Brochure::findOrFail($brochureId);

    $this->trackCustomEvent('download_brochure', [
        'brochure_name' => $brochure->title,
    ]);
    // ga_download_brochure {brochure_name: '...'}

    return response()->download($brochure->path);
}
```

## A step in a longer form

```php
public function nextStep(): void
{
    $this->validate($this->rulesForStep($this->step));

    $this->trackEvent('form_step_completed', [
        'form_name' => 'quote_request',
        'step' => $this->step,
    ]);

    $this->step++;
}
```
