<?php

use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;
use Livewire\Livewire;

it('can track lead generation event', function () {
    $component = Livewire::test(TestComponent::class)
        ->call('trackLeadEvent')
        ->assertDispatched('ga:event', 
            name: 'generate_lead',
            params: [
                'form_name' => 'test_form',
                'lead_type' => 'test',
            ]
        );
});

it('can track custom event', function () {
    Livewire::test(TestComponent::class)
        ->call('trackCustom')
        ->assertDispatched('ga:event',
            name: 'purchase',
            params: [
                'transaction_id' => 'T12345',
                'value' => 99.99,
                'currency' => 'EUR',
            ]
        );
});

it('can track newsletter signup', function () {
    Livewire::test(TestComponent::class)
        ->call('trackNewsletter')
        ->assertDispatched('ga:event',
            name: 'sign_up',
            params: [
                'method' => 'newsletter',
                'source' => 'test',
            ]
        );
});

it('can track custom event with ga prefix', function () {
    Livewire::test(TestComponent::class)
        ->call('trackCustomEventWithPrefix')
        ->assertDispatched('ga:event',
            name: 'ga_download_file',
            params: [
                'file_name' => 'test.pdf',
            ]
        );
});

class TestComponent extends Component
{
    use TracksAnalytics;

    public function trackLeadEvent()
    {
        $this->trackLead([
            'form_name' => 'test_form',
            'lead_type' => 'test',
        ]);
    }

    public function trackCustom()
    {
        $this->trackEvent('purchase', [
            'transaction_id' => 'T12345',
            'value' => 99.99,
            'currency' => 'EUR',
        ]);
    }

    public function trackNewsletter()
    {
        $this->trackNewsletterSignup([
            'source' => 'test',
        ]);
    }

    public function trackCustomEventWithPrefix()
    {
        $this->trackCustomEvent('download_file', [
            'file_name' => 'test.pdf',
        ]);
    }

    public function render()
    {
        return '<div>Test Component</div>';
    }
}
