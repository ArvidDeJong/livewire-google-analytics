/**
 * Livewire Google Analytics Event Listener
 *
 * This script listens for custom 'ga:event' events dispatched by Livewire
 * and forwards them to Google Analytics 4 (gtag). Events that arrive before
 * gtag exists wait in a queue; turn that off with
 * window.livewireGoogleAnalytics = { queue: false } before this script runs.
 *
 * Keep the code between core:start and core:end the same as in
 * resources/views/script.blade.php.
 *
 * @author Arvid de Jong <info@arvid.nl>
 */
(function () {
    // Only the Blade view can carry events over a redirect: this file is static and cannot read the session.
    var carried = [];

    var log = function (level) {
        console[level].apply(console, Array.prototype.slice.call(arguments, 1));
    };

    /* core:start */
    var MAX_WAITING = 50;
    var MAX_AGE = 30 * 60 * 1000;
    var RETRY_EVERY = 1000;

    /* On window, because wire:navigate runs this script again on every visit while the window stays. */
    var state = window.livewireGoogleAnalyticsState = window.livewireGoogleAnalyticsState || { waiting: [], timer: null, carried: {} };

    function gtagIsThere() {
        return typeof window.gtag === 'function';
    }

    function queueIsOn() {
        var settings = window.livewireGoogleAnalytics;

        return !(settings && settings.queue === false);
    }

    function send(name, params) {
        window.gtag('event', name, params || {});
        log('debug', '[GA4] Event tracked:', name, params);
    }

    function stopTimer() {
        if (state.timer !== null) {
            clearInterval(state.timer);
            state.timer = null;
        }
    }

    function dropExpired() {
        var oldest = Date.now() - MAX_AGE;

        state.waiting = state.waiting.filter(function (item) {
            return item.at >= oldest;
        });
    }

    /* Send what is waiting, oldest first. Taken out of the queue before sending, so nothing is sent twice. */
    function flush() {
        dropExpired();

        if (state.waiting.length === 0) {
            stopTimer();
            return;
        }

        if (!gtagIsThere()) return;

        var waiting = state.waiting.splice(0);
        stopTimer();

        waiting.forEach(function (item) {
            send(item.name, item.params);
        });
    }

    function track(name, params) {
        flush();

        if (gtagIsThere()) {
            send(name, params);
            return;
        }

        if (!queueIsOn()) {
            log('debug', '[GA4] gtag not available, skipping event:', name);
            return;
        }

        state.waiting.push({ name: name, params: params, at: Date.now() });
        state.waiting.splice(0, Math.max(0, state.waiting.length - MAX_WAITING));
        log('debug', '[GA4] gtag not available, event is waiting:', name);

        /* The only timer, and it only runs while something is waiting. */
        if (state.timer === null) {
            state.timer = setInterval(flush, RETRY_EVERY);
        }
    }

    if (!window.livewireGoogleAnalyticsListening) {
        window.livewireGoogleAnalyticsListening = true;

        window.addEventListener('ga:event', function (event) {
            var detail = event.detail || {};

            if (!detail.name) {
                log('warn', '[GA4] Event dispatched without name:', detail);
                return;
            }

            track(detail.name, detail.params);
        });

        log('debug', '[GA4] Livewire Google Analytics listener initialized');
    }

    /* A page that wire:navigate puts back from its cache runs this script again: send a carried event once. */
    carried.forEach(function (item) {
        if (!item || !item.name || state.carried[item.id]) return;

        state.carried[item.id] = true;
        track(item.name, item.params);
    });
    /* core:end */
})();
