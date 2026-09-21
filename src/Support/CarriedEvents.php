<?php

namespace Darvis\LivewireGoogleAnalytics\Support;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\HtmlString;
use stdClass;

/**
 * Events that were tracked right before a redirect. They wait in the session and the listener view
 * on the next page sends them, because the page that tracked them is going away.
 */
final class CarriedEvents
{
    /**
     * The one session key of the package. Host app tests may assert on it.
     */
    public const SESSION_KEY = 'livewire-google-analytics.events';

    /**
     * The same limits as the queue in the listener script.
     */
    private const MAX_EVENTS = 50;

    private const MAX_AGE_IN_SECONDS = 1800;

    /**
     * Keep an event for the next page. Returns false when there is no session to keep it in.
     *
     * @param  array<string, mixed>  $params
     */
    public static function carry(string $name, array $params = []): bool
    {
        $session = self::session();

        if ($session === null) {
            return false;
        }

        $events = self::stored($session);
        $events[] = ['id' => bin2hex(random_bytes(8)), 'name' => $name, 'params' => $params, 'at' => now()->getTimestamp()];

        $session->put(self::SESSION_KEY, array_slice($events, -1 * self::MAX_EVENTS));

        return true;
    }

    /**
     * Take the events out of the session, so a reload of the page does not send them again.
     *
     * @return list<array{id: string, name: string, params: array<string, mixed>, at: int}>
     */
    public static function pull(): array
    {
        $session = self::session();

        if ($session === null || ! $session->has(self::SESSION_KEY)) {
            return [];
        }

        $events = self::stored($session);
        $session->forget(self::SESSION_KEY);

        $oldest = now()->getTimestamp() - self::MAX_AGE_IN_SECONDS;

        return array_values(array_filter($events, fn (array $event): bool => $event['at'] >= $oldest));
    }

    /**
     * The pulled events as a JavaScript array literal for the listener view.
     *
     * Every character that could end the script tag or a string is hex escaped, and a broken UTF-8
     * byte is replaced instead of failing the page. Never build this with string concatenation.
     */
    public static function pullForScript(): HtmlString
    {
        $events = array_map(fn (array $event): array => [
            'id' => $event['id'],
            'name' => $event['name'],
            'params' => $event['params'] === [] ? new stdClass : $event['params'],
        ], self::pull());

        $json = json_encode(
            $events,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE,
        );

        return new HtmlString($json === false ? '[]' : $json);
    }

    /**
     * @return list<array{id: string, name: string, params: array<string, mixed>, at: int}>
     */
    private static function stored(Session $session): array
    {
        $events = $session->get(self::SESSION_KEY, []);

        if (! is_array($events)) {
            return [];
        }

        return array_values(array_filter($events, fn (mixed $event): bool => is_array($event)
            && is_string($event['id'] ?? null)
            && is_string($event['name'] ?? null)
            && is_array($event['params'] ?? null)
            && is_int($event['at'] ?? null)));
    }

    /**
     * A stateless route has no session. That is not an error: there is just nothing to carry.
     *
     * The session manager always hands out a store, also when no middleware started it, and such a
     * store is never saved. So only a store that was started counts. Livewire::test() skips the
     * middleware; a test that wants the session calls session()->start() first.
     */
    private static function session(): ?Session
    {
        $request = request();

        if ($request->hasSession()) {
            return $request->session();
        }

        $store = app()->bound('session.store') ? app('session.store') : null;

        return $store !== null && $store->isStarted() ? $store : null;
    }
}
