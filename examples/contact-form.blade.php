{{-- 
    Example Blade View for Contact Form
    
    This is the view file for the ContactForm component.
    Place this in: resources/views/livewire/examples/contact-form.blade.php
--}}

<div class="max-w-md mx-auto p-6">
    @if($success)
        {{-- Success Message --}}
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <strong>Success!</strong> Thank you for contacting us. We'll get back to you soon.
        </div>
        
        <button wire:click="$set('success', false)" class="text-blue-600 hover:underline">
            Send another message
        </button>
    @else
        {{-- Contact Form --}}
        <h2 class="text-2xl font-bold mb-6">Contact Us</h2>
        
        <form wire:submit="submit" class="space-y-4">
            {{-- Name Field --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    Name *
                </label>
                <input 
                    type="text" 
                    id="name"
                    wire:model="name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Your name"
                >
                @error('name')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>
            
            {{-- Email Field --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Email *
                </label>
                <input 
                    type="email" 
                    id="email"
                    wire:model="email"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="your@email.com"
                >
                @error('email')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>
            
            {{-- Message Field --}}
            <div>
                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">
                    Message *
                </label>
                <textarea 
                    id="message"
                    wire:model="message"
                    rows="5"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Your message..."
                ></textarea>
                @error('message')
                    <span class="text-red-600 text-sm">{{ $message }}</span>
                @enderror
            </div>
            
            {{-- Submit Button --}}
            <button 
                type="submit"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Send Message
            </button>
        </form>
    @endif
</div>
