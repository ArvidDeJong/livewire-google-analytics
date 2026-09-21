<?php

use Darvis\LivewireGoogleAnalytics\Tests\Fixtures\TrackingComponent;
use Livewire\Livewire;

it('dispatches a generate_lead event for a lead', function () {
    Livewire::test(TrackingComponent::class)
        ->call('lead')
        ->assertDispatched('ga:event', name: 'generate_lead', params: [
            'form_name' => 'test_form',
            'lead_type' => 'test',
        ]);
});

it('dispatches an empty params array when none are given', function () {
    Livewire::test(TrackingComponent::class)
        ->call('leadWithoutParams')
        ->assertDispatched('ga:event', name: 'generate_lead', params: []);
});

it('dispatches any event name with its params unchanged', function () {
    Livewire::test(TrackingComponent::class)
        ->call('purchase')
        ->assertDispatched('ga:event', name: 'purchase', params: [
            'transaction_id' => 'T12345',
            'value' => 99.99,
            'currency' => 'EUR',
        ]);
});

it('dispatches sign_up with the newsletter method for a newsletter signup', function () {
    Livewire::test(TrackingComponent::class)
        ->call('newsletter')
        ->assertDispatched('ga:event', name: 'sign_up', params: [
            'method' => 'newsletter',
            'source' => 'footer',
        ]);
});

it('lets the caller override the method of a newsletter signup', function () {
    Livewire::test(TrackingComponent::class)
        ->call('newsletterWithOwnMethod')
        ->assertDispatched('ga:event', name: 'sign_up', params: ['method' => 'popup']);
});

it('prefixes a custom event with ga_', function () {
    Livewire::test(TrackingComponent::class)
        ->call('download')
        ->assertDispatched('ga:event', name: 'ga_download_file', params: ['file_name' => 'test.pdf']);
});

it('dispatches every event of one request', function () {
    Livewire::test(TrackingComponent::class)
        ->call('twoEvents')
        ->assertDispatched('ga:event', name: 'generate_lead', params: ['form_name' => 'first'])
        ->assertDispatched('ga:event', name: 'login', params: ['method' => 'email']);
});

it('dispatches nothing until a tracking method is called', function () {
    Livewire::test(TrackingComponent::class)->assertNotDispatched('ga:event');
});

it('passes visitor input on as data, never as script', function () {
    $input = '\'); alert(1); //</script><script>alert("x")</script>';

    Livewire::test(TrackingComponent::class)
        ->set('location', $input)
        ->call('leadWithLocation')
        ->assertDispatched('ga:event', name: 'generate_lead', params: ['location' => $input]);
});

it('keeps the tracking methods out of reach of the browser', function () {
    // The trait methods are protected. A public one could be called by any visitor with
    // wire:click or a forged request, and would let them send events of their choice.
    foreach (['trackEvent', 'trackLead', 'trackNewsletterSignup', 'trackCustomEvent'] as $method) {
        expect((new ReflectionMethod(TrackingComponent::class, $method))->isProtected())->toBeTrue($method);
    }

    expect(fn () => Livewire::test(TrackingComponent::class)->call('trackLead', ['form_name' => 'forged']))
        ->toThrow(Exception::class);
});
