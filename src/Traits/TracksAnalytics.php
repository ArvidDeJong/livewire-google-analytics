<?php

namespace Darvis\LivewireGoogleAnalytics\Traits;

use Darvis\LivewireGoogleAnalytics\Support\CarriedEvents;

trait TracksAnalytics
{
    /**
     * Track a GA4 event via browser dispatch.
     *
     * @param  string  $name  Event name
     * @param  array<string, mixed>  $params  Event parameters
     */
    protected function trackEvent(string $name, array $params = []): void
    {
        $this->dispatch('ga:event', name: $name, params: $params);
    }

    /**
     * Standard lead tracking (GA4 conversion).
     *
     * @param  array<string, mixed>  $params  Event parameters
     */
    protected function trackLead(array $params = []): void
    {
        $this->trackEvent('generate_lead', $params);
    }

    /**
     * Track newsletter signup.
     *
     * @param  array<string, mixed>  $params  Event parameters
     */
    protected function trackNewsletterSignup(array $params = []): void
    {
        $this->trackEvent('sign_up', array_merge(['method' => 'newsletter'], $params));
    }

    /**
     * Track custom event with ga_ prefix.
     *
     * @param  string  $eventName  Event name (without ga_ prefix)
     * @param  array<string, mixed>  $params  Event parameters
     */
    protected function trackCustomEvent(string $eventName, array $params = []): void
    {
        $this->trackEvent('ga_'.$eventName, $params);
    }

    /**
     * Track a GA4 event in an action that ends in a redirect.
     *
     * The page that would receive the browser event is going away, so the event waits in the session
     * and the listener view on the next page sends it. It is not dispatched as well, or it would be
     * counted twice. Without a session it is dispatched like trackEvent().
     *
     * @param  string  $name  Event name
     * @param  array<string, mixed>  $params  Event parameters
     */
    protected function trackEventAfterRedirect(string $name, array $params = []): void
    {
        if (! CarriedEvents::carry($name, $params)) {
            $this->trackEvent($name, $params);
        }
    }

    /**
     * trackLead() for an action that ends in a redirect.
     *
     * @param  array<string, mixed>  $params  Event parameters
     */
    protected function trackLeadAfterRedirect(array $params = []): void
    {
        $this->trackEventAfterRedirect('generate_lead', $params);
    }

    /**
     * trackNewsletterSignup() for an action that ends in a redirect.
     *
     * @param  array<string, mixed>  $params  Event parameters
     */
    protected function trackNewsletterSignupAfterRedirect(array $params = []): void
    {
        $this->trackEventAfterRedirect('sign_up', array_merge(['method' => 'newsletter'], $params));
    }

    /**
     * trackCustomEvent() for an action that ends in a redirect.
     *
     * @param  string  $eventName  Event name (without ga_ prefix)
     * @param  array<string, mixed>  $params  Event parameters
     */
    protected function trackCustomEventAfterRedirect(string $eventName, array $params = []): void
    {
        $this->trackEventAfterRedirect('ga_'.$eventName, $params);
    }
}
