{{-- 
    Livewire Google Analytics Event Listener
    
    Include this script once in your layout, after Livewire scripts.
    
    Usage in layout:
    @include('livewire-google-analytics::script')
--}}
<script>
(function () {
    function fireGaEvent(name, params) {
        if (typeof window.gtag !== 'function') return;
        window.gtag('event', name, params || {});
    }

    window.addEventListener('ga:event', function (event) {
        const detail = event.detail || {};
        if (!detail.name) return;
        fireGaEvent(detail.name, detail.params);
    });
})();
</script>
