<?php

namespace Darvis\LivewireGoogleAnalytics\Tests\Fixtures;

use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;

/**
 * The trait is only used by host app components. This component uses it, so the tests can call the
 * protected methods through public actions and PHPStan analyses the trait instead of skipping it.
 */
class TrackingComponent extends Component
{
    use TracksAnalytics;

    public string $location = '';

    public function lead(): void
    {
        $this->trackLead([
            'form_name' => 'test_form',
            'lead_type' => 'test',
        ]);
    }

    public function leadWithoutParams(): void
    {
        $this->trackLead();
    }

    public function leadWithLocation(): void
    {
        $this->trackLead(['location' => $this->location]);
    }

    public function purchase(): void
    {
        $this->trackEvent('purchase', [
            'transaction_id' => 'T12345',
            'value' => 99.99,
            'currency' => 'EUR',
        ]);
    }

    public function newsletter(): void
    {
        $this->trackNewsletterSignup(['source' => 'footer']);
    }

    public function newsletterWithOwnMethod(): void
    {
        $this->trackNewsletterSignup(['method' => 'popup']);
    }

    public function download(): void
    {
        $this->trackCustomEvent('download_file', ['file_name' => 'test.pdf']);
    }

    public function twoEvents(): void
    {
        $this->trackLead(['form_name' => 'first']);
        $this->trackEvent('login', ['method' => 'email']);
    }

    public function render(): string
    {
        return '<div>Tracking component</div>';
    }
}
