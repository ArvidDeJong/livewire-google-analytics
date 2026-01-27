<?php

namespace App\Livewire\Examples;

use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

/**
 * Example: Simple Contact Form with Google Analytics Tracking
 * 
 * This example shows how to track form submissions in Google Analytics.
 * 
 * Features:
 * - Form validation
 * - Email sending
 * - GA4 event tracking
 * - Success message
 */
class ContactForm extends Component
{
    use TracksAnalytics;
    
    // Form fields
    public string $name = '';
    public string $email = '';
    public string $message = '';
    
    // UI state
    public bool $success = false;
    
    /**
     * Validation rules for the form
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:100',
            'message' => 'required|min:10|max:2000',
        ];
    }
    
    /**
     * Handle form submission
     * 
     * This method:
     * 1. Validates the input
     * 2. Sends an email
     * 3. Tracks the event in Google Analytics
     * 4. Shows success message
     */
    public function submit()
    {
        // Step 1: Validate the form
        $validated = $this->validate();
        
        // Step 2: Send email (replace with your email logic)
        Mail::to('info@example.com')->send(
            new \App\Mail\ContactMail($validated)
        );
        
        // Step 3: Track the conversion in Google Analytics
        // This sends a 'generate_lead' event to GA4
        $this->trackLead([
            'form_name' => 'contact_form',
            'lead_type' => 'contact',
            'source' => 'livewire',
        ]);
        
        // Step 4: Reset form and show success
        $this->reset(['name', 'email', 'message']);
        $this->success = true;
    }
    
    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.examples.contact-form');
    }
}
