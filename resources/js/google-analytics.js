/**
 * Livewire Google Analytics Event Listener
 * 
 * This script listens for custom 'ga:event' events dispatched by Livewire
 * and forwards them to Google Analytics 4 (gtag).
 * 
 * @author Arvid de Jong <info@arvid.nl>
 */
(function () {
    /**
     * Fire a Google Analytics event
     * 
     * @param {string} name - Event name
     * @param {object} params - Event parameters
     */
    function fireGaEvent(name, params) {
        // Check if gtag is available
        if (typeof window.gtag !== 'function') {
            console.debug('[GA4] gtag not available, skipping event:', name);
            return;
        }

        // Fire the event
        window.gtag('event', name, params || {});
        console.debug('[GA4] Event tracked:', name, params);
    }

    /**
     * Listen for Livewire dispatched ga:event
     */
    window.addEventListener('ga:event', function (event) {
        const detail = event.detail || {};
        
        // Validate event has a name
        if (!detail.name) {
            console.warn('[GA4] Event dispatched without name:', detail);
            return;
        }

        // Fire the GA4 event
        fireGaEvent(detail.name, detail.params);
    });

    console.debug('[GA4] Livewire Google Analytics listener initialized');
})();
