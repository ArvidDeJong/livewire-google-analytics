<?php

namespace Darvis\LivewireGoogleAnalytics\Traits;

trait TracksAnalytics
{
    /**
     * Track a GA4 event via browser dispatch.
     *
     * @param string $name Event name
     * @param array $params Event parameters
     * @return void
     */
    protected function trackEvent(string $name, array $params = []): void
    {
        $this->dispatch('ga:event', name: $name, params: $params);
    }

    /**
     * Standard lead tracking (GA4 conversion).
     *
     * @param array $params Event parameters
     * @return void
     */
    protected function trackLead(array $params = []): void
    {
        $this->trackEvent('generate_lead', $params);
    }

    /**
     * Track newsletter signup.
     *
     * @param array $params Event parameters
     * @return void
     */
    protected function trackNewsletterSignup(array $params = []): void
    {
        $this->trackEvent('sign_up', array_merge(['method' => 'newsletter'], $params));
    }

    /**
     * Track custom event with ga_ prefix.
     *
     * @param string $eventName Event name (without ga_ prefix)
     * @param array $params Event parameters
     * @return void
     */
    protected function trackCustomEvent(string $eventName, array $params = []): void
    {
        $this->trackEvent('ga_' . $eventName, $params);
    }
}
