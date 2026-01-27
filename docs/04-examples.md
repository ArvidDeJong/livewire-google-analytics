# Examples

This guide shows complete, real-world examples of using the package in different scenarios.

## Example 1: Contact Form

A simple contact form that tracks lead generation.

### Component

```php
<?php

namespace App\Livewire;

use App\Mail\ContactMail;
use App\Models\Contact;
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
    
    protected function rules(): array
    {
        return [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:100',
            'message' => 'required|min:10|max:2000',
        ];
    }
    
    public function submit()
    {
        $validated = $this->validate();
        
        // Save to database
        Contact::create($validated);
        
        // Send email
        Mail::to('info@example.com')->send(
            new ContactMail($validated)
        );
        
        // Track conversion
        $this->trackLead([
            'form_name' => 'contact_form',
            'lead_type' => 'contact',
            'source' => 'website',
        ]);
        
        // Reset and show success
        $this->reset(['name', 'email', 'message']);
        $this->success = true;
    }
    
    public function render()
    {
        return view('livewire.contact-form');
    }
}
```

### View

```blade
<div class="max-w-md mx-auto">
    @if($success)
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            Thank you! We'll get back to you soon.
        </div>
    @else
        <form wire:submit="submit" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium">Name</label>
                <input 
                    type="text" 
                    id="name" 
                    wire:model="name"
                    class="mt-1 block w-full rounded-md border-gray-300"
                >
                @error('name') 
                    <span class="text-red-500 text-sm">{{ $message }}</span> 
                @enderror
            </div>
            
            <div>
                <label for="email" class="block text-sm font-medium">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    wire:model="email"
                    class="mt-1 block w-full rounded-md border-gray-300"
                >
                @error('email') 
                    <span class="text-red-500 text-sm">{{ $message }}</span> 
                @enderror
            </div>
            
            <div>
                <label for="message" class="block text-sm font-medium">Message</label>
                <textarea 
                    id="message" 
                    wire:model="message"
                    rows="4"
                    class="mt-1 block w-full rounded-md border-gray-300"
                ></textarea>
                @error('message') 
                    <span class="text-red-500 text-sm">{{ $message }}</span> 
                @enderror
            </div>
            
            <button 
                type="submit" 
                class="w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600"
            >
                Send Message
            </button>
        </form>
    @endif
</div>
```

## Example 2: Newsletter Signup

A newsletter subscription form with double opt-in.

### Component

```php
<?php

namespace App\Livewire;

use App\Models\Subscriber;
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;

class NewsletterSignup extends Component
{
    use TracksAnalytics;
    
    public string $email = '';
    public bool $success = false;
    
    public function subscribe()
    {
        $validated = $this->validate([
            'email' => 'required|email|unique:subscribers,email',
        ]);
        
        // Create subscriber
        $subscriber = Subscriber::create([
            'email' => $validated['email'],
            'token' => Str::random(32),
            'confirmed' => false,
        ]);
        
        // Send confirmation email
        Mail::to($subscriber->email)->send(
            new ConfirmSubscriptionMail($subscriber)
        );
        
        // Track signup
        $this->trackNewsletterSignup([
            'source' => 'footer_widget',
            'list_name' => 'monthly_newsletter',
        ]);
        
        $this->reset('email');
        $this->success = true;
    }
    
    public function render()
    {
        return view('livewire.newsletter-signup');
    }
}
```

## Example 3: Vacancy Application Form

A job application form with file upload.

### Component

```php
<?php

namespace App\Livewire;

use App\Models\VacancyApplication;
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;
use Livewire\WithFileUploads;

class VacancyForm extends Component
{
    use TracksAnalytics, WithFileUploads;
    
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $motivation = '';
    public $cv;
    public bool $success = false;
    
    protected function rules(): array
    {
        return [
            'name' => 'required|min:2',
            'email' => 'required|email',
            'phone' => 'required',
            'motivation' => 'required|min:50',
            'cv' => 'required|file|mimes:pdf,doc,docx|max:5120',
        ];
    }
    
    public function submit()
    {
        $validated = $this->validate();
        
        // Store CV
        $cvPath = $this->cv->store('applications', 'private');
        
        // Create application
        VacancyApplication::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'motivation' => $validated['motivation'],
            'cv_path' => $cvPath,
        ]);
        
        // Track application
        $this->trackLead([
            'form_name' => 'vacancy_form',
            'lead_type' => 'job_application',
            'source' => 'careers_page',
            'value' => 50, // Assign value to job applications
        ]);
        
        $this->reset(['name', 'email', 'phone', 'motivation', 'cv']);
        $this->success = true;
    }
    
    public function render()
    {
        return view('livewire.vacancy-form');
    }
}
```

## Example 4: E-commerce Checkout

Track a purchase with items and transaction details.

### Component

```php
<?php

namespace App\Livewire;

use App\Models\Order;
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;

class CheckoutForm extends Component
{
    use TracksAnalytics;
    
    public $cart;
    public string $name = '';
    public string $email = '';
    public string $address = '';
    
    public function completePurchase()
    {
        $validated = $this->validate([
            'name' => 'required',
            'email' => 'required|email',
            'address' => 'required',
        ]);
        
        // Create order
        $order = Order::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'address' => $validated['address'],
            'total' => $this->cart->total(),
            'status' => 'pending',
        ]);
        
        // Add order items
        foreach ($this->cart->items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ]);
        }
        
        // Track purchase
        $this->trackEvent('purchase', [
            'transaction_id' => $order->id,
            'value' => $order->total,
            'currency' => 'EUR',
            'tax' => $order->tax,
            'shipping' => $order->shipping_cost,
            'items' => $order->items->map(fn($item) => [
                'item_id' => $item->product->sku,
                'item_name' => $item->product->name,
                'price' => $item->price,
                'quantity' => $item->quantity,
            ])->toArray(),
        ]);
        
        // Clear cart and redirect
        $this->cart->clear();
        return redirect()->route('order.success', $order);
    }
    
    public function render()
    {
        return view('livewire.checkout-form');
    }
}
```

## Example 5: Download Tracking

Track file downloads with custom events.

### Component

```php
<?php

namespace App\Livewire;

use App\Models\Brochure;
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;

class DownloadCenter extends Component
{
    use TracksAnalytics;
    
    public function download($brochureId)
    {
        $brochure = Brochure::findOrFail($brochureId);
        
        // Increment download count
        $brochure->increment('downloads');
        
        // Track download
        $this->trackCustomEvent('download_brochure', [
            'brochure_id' => $brochure->id,
            'brochure_name' => $brochure->name,
            'category' => $brochure->category,
            'file_type' => $brochure->file_extension,
        ]);
        
        // Return download
        return response()->download(
            storage_path('app/brochures/' . $brochure->filename),
            $brochure->name . '.' . $brochure->file_extension
        );
    }
    
    public function render()
    {
        return view('livewire.download-center', [
            'brochures' => Brochure::all(),
        ]);
    }
}
```

### View

```blade
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach($brochures as $brochure)
        <div class="border rounded-lg p-4">
            <h3 class="font-bold">{{ $brochure->name }}</h3>
            <p class="text-sm text-gray-600">{{ $brochure->description }}</p>
            <button 
                wire:click="download({{ $brochure->id }})"
                class="mt-2 bg-blue-500 text-white px-4 py-2 rounded"
            >
                Download PDF
            </button>
        </div>
    @endforeach
</div>
```

## Example 6: Multi-step Form

Track only when the entire form is completed.

### Component

```php
<?php

namespace App\Livewire;

use App\Models\Lead;
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;

class MultiStepForm extends Component
{
    use TracksAnalytics;
    
    public int $step = 1;
    
    // Step 1
    public string $email = '';
    
    // Step 2
    public string $name = '';
    public string $company = '';
    
    // Step 3
    public string $phone = '';
    public string $message = '';
    
    public bool $success = false;
    
    public function nextStep()
    {
        $this->validateCurrentStep();
        $this->step++;
    }
    
    public function previousStep()
    {
        $this->step--;
    }
    
    public function submit()
    {
        $this->validateCurrentStep();
        
        // Save lead
        Lead::create([
            'email' => $this->email,
            'name' => $this->name,
            'company' => $this->company,
            'phone' => $this->phone,
            'message' => $this->message,
        ]);
        
        // Track only after complete submission
        $this->trackLead([
            'form_name' => 'multi_step_quote',
            'lead_type' => 'quote_request',
            'steps_completed' => 3,
            'source' => 'website',
        ]);
        
        $this->success = true;
    }
    
    protected function validateCurrentStep()
    {
        $rules = match($this->step) {
            1 => ['email' => 'required|email'],
            2 => ['name' => 'required', 'company' => 'required'],
            3 => ['phone' => 'required', 'message' => 'required'],
        };
        
        $this->validate($rules);
    }
    
    public function render()
    {
        return view('livewire.multi-step-form');
    }
}
```

## Example 7: Login Tracking

Track user logins.

### Component

```php
<?php

namespace App\Livewire;

use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LoginForm extends Component
{
    use TracksAnalytics;
    
    public string $email = '';
    public string $password = '';
    public bool $remember = false;
    
    public function login()
    {
        $validated = $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        if (Auth::attempt($validated, $this->remember)) {
            session()->regenerate();
            
            // Track successful login
            $this->trackEvent('login', [
                'method' => 'email',
            ]);
            
            return redirect()->intended('dashboard');
        }
        
        $this->addError('email', 'Invalid credentials.');
    }
    
    public function render()
    {
        return view('livewire.login-form');
    }
}
```

## Next steps

- [05-testing.md](05-testing.md) - Learn how to test your tracking
- [06-troubleshooting.md](06-troubleshooting.md) - Fix common issues
- [03-basic-usage.md](03-basic-usage.md) - Review the basics
