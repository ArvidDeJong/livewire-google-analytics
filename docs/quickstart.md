---
title: "Quick start"
nav_order: 3
description: "One complete example for darvis/livewire-google-analytics: a Livewire contact form that sends a generate_lead event to GA4, with every file and import."
---

# Quick start: a contact form that tracks a lead

This page builds one working example: a contact form that sends the GA4 event `generate_lead` after the message was sent. It assumes the package is installed and your Google tag is on the page, see [Installation](installation.md).

## 1. The layout

`resources/views/components/layouts/app.blade.php`, or whichever layout your Livewire pages use:

{% raw %}
```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google tag (gtag.js): the snippet from your own GA4 property goes here -->

    <meta charset="utf-8">
    <title>{{ $title ?? config('app.name') }}</title>
</head>
<body>
    {{ $slot }}

    @include('livewire-google-analytics::script')
</body>
</html>
```
{% endraw %}

The include renders the listener script. Without it the component still dispatches its event, but nothing passes it on to Google.

## 2. The component

`app/Livewire/ContactForm.php`:

```php
<?php

namespace App\Livewire;

use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class ContactForm extends Component
{
    use TracksAnalytics;

    public string $name = '';

    public string $email = '';

    public string $message = '';

    public bool $sent = false;

    public function submit(): void
    {
        $validated = $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email',
            'message' => 'required|min:10',
        ]);

        Mail::raw($validated['message'], function ($mail) use ($validated) {
            $mail->to('info@example.com')
                ->replyTo($validated['email'])
                ->subject('Contact form: '.$validated['name']);
        });

        $this->trackLead(['form_name' => 'contact_form']);

        $this->reset(['name', 'email', 'message']);
        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
```

`validate()` throws when the input is wrong, so `trackLead()` is only reached for a form that was accepted and mailed. `trackLead()` dispatches the browser event `ga:event` with the name `generate_lead` and your parameters. Note what is not in the parameters: the name, the e-mail address and the message. Everything in the parameters ends up in your Google Analytics property.

## 3. The view of the component

`resources/views/livewire/contact-form.blade.php`:

{% raw %}
```blade
<div>
    @if ($sent)
        <p>Thank you, we will get back to you soon.</p>
    @else
        <form wire:submit="submit">
            <label>Name <input type="text" wire:model="name"></label>
            @error('name') <span>{{ $message }}</span> @enderror

            <label>E-mail <input type="email" wire:model="email"></label>
            @error('email') <span>{{ $message }}</span> @enderror

            <label>Message <textarea wire:model="message"></textarea></label>
            @error('message') <span>{{ $message }}</span> @enderror

            <button type="submit">Send</button>
        </form>
    @endif
</div>
```
{% endraw %}

`wire:submit="submit"` calls the `submit()` method of the component when the form is sent.

## 4. The route

`routes/web.php`:

```php
use App\Livewire\ContactForm;
use Illuminate\Support\Facades\Route;

Route::get('/contact', ContactForm::class);
```

## What happens when you send the form

1. Livewire calls `submit()` on the server. The form is validated and the mail is sent.
2. `trackLead()` adds the browser event `ga:event` to Livewire's response.
3. In the browser, the listener from step 1 receives that event and calls `gtag('event', 'generate_lead', {form_name: 'contact_form'})`.
4. Google's tag sends the event to your GA4 property.

To see it arrive, follow "Check that it works" on [Installation](installation.md). To test the component without a browser, see [Testing](testing.md).

## Next

- An action that ends in a redirect needs `trackLeadAfterRedirect()`, see [Tracking events](usage.md).
- More situations are on [Examples](examples.md).
