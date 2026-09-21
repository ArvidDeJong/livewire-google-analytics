## darvis/livewire-google-analytics

Sends Google Analytics 4 events from Livewire components. A trait dispatches a `ga:event` browser event, and a small listener in the layout forwards it to `gtag('event', name, params)`. Nothing is sent from the server.

- Add `Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics` to the component. Never build `gtag()` calls with `$this->js()`: a value from a visitor would become JavaScript source.
- `trackEvent(string $name, array $params = [])` sends any event. `trackLead($params)` sends `generate_lead`. `trackNewsletterSignup($params)` sends `sign_up` with `method` set to `newsletter` unless the params hold their own `method`. `trackCustomEvent($name, $params)` sends `ga_` plus the name; pass the name without that prefix.
- All of them, the `AfterRedirect` variants included, are `protected`, return nothing and never throw. Keep them protected: a public Livewire method can be called from the browser with any arguments.
- Call them in an action, after `validate()` and after the work succeeded. Never in `render()`, which runs on every request.
- The layout needs `@@include('livewire-google-analytics::script')` once. For a Content Security Policy without inline scripts, publish `--tag=livewire-google-analytics-js` and load `/vendor/livewire-google-analytics/google-analytics.js` instead.
- The package does not load `gtag.js` and has no config file, measurement id or environment variable. The host app adds its own Google tag. Don't invent a `config('google-analytics.…')` key.
- When `window.gtag` is not a function yet (a consent tool loads Google's tag later) the event waits in memory, at most 50 events and at most 30 minutes, and is sent in order once `gtag` exists. `@@include('livewire-google-analytics::script', ['queue' => false])` or `window.livewireGoogleAnalytics = { queue: false }` turns that off. Never push to `dataLayer` or define `gtag` yourself to "help" the listener.
- The listener registers once per window, so `wire:navigate` and a second copy of the script don't double the events. A published view or file from before that fix has to be published again.
- In an action that ends in a redirect, use `trackEventAfterRedirect()`, `trackLeadAfterRedirect()`, `trackNewsletterSignupAfterRedirect()` or `trackCustomEventAfterRedirect()`. Livewire fires a dispatched event on the page that is going away; these methods keep it in the session (`livewire-google-analytics.events`) and the Blade view on the next page sends it once. Never call both variants for one event. The listener remembers the ids of carried events on `window` and in `sessionStorage` (`livewire-google-analytics.sent`, ids only), so a page from the browser cache does not send them again; don't add your own dedupe and don't clear that key. The published JavaScript file cannot read the session, so the next page needs the view.
- Keep personal data (names, e-mail addresses, free text) out of the params.
- In tests, assert with `->assertDispatched('ga:event', name: '...', params: [...])`; `params` is compared as a whole. For an `AfterRedirect` method call `session()->start()` first and assert on `session('livewire-google-analytics.events.0.name')`; without a started session the event is dispatched instead.

@verbatim
<code-snippet name="Track a lead after a Livewire form was handled" lang="php">
use Darvis\LivewireGoogleAnalytics\Traits\TracksAnalytics;
use Livewire\Component;

class ContactForm extends Component
{
    use TracksAnalytics;

    public function submit(): void
    {
        $validated = $this->validate();

        ContactRequest::create($validated);

        // After the work, so a rejected form is never counted as a conversion.
        $this->trackLead([
            'form_name' => 'contact_form',
            'lead_type' => 'contact',
        ]);
    }
}
</code-snippet>
@endverbatim
