<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\Request;

class MetaPixel
{
    /** Pixel (dataset) IDs are plain numbers, usually 15–16 digits long. */
    private const ID_PATTERN = '/^\d{10,20}$/';

    /**
     * Meta's own first-party cookies, keyed by the pixel parameter that carries
     * them: `_fbp` identifies the browser, `_fbc` the ad click that brought the
     * visitor in. Laravel did not set them, so they are left unencrypted.
     *
     * @var array<string, string>
     */
    public const FORWARDED_COOKIES = ['fbp' => '_fbp', 'fbc' => '_fbc'];

    /** Shape of those cookies: "fb.{subdomain index}.{created at}.{value}". */
    private const COOKIE_PATTERN = '/^fb\.\d\.\d+\.[A-Za-z0-9._-]{1,500}$/';

    /** Session key holding the events the visitor's next page has to send. */
    private const QUEUE_SESSION_KEY = 'meta_pixel_events';

    /** Endpoint the image pixel reports to. */
    private const IMAGE_PIXEL_ENDPOINT = 'https://www.facebook.com/tr';

    /**
     * The pixel is active only when enabled in settings and the stored ID is a
     * well-formed pixel ID.
     */
    public static function enabled(): bool
    {
        return (bool) Setting::get('meta_pixel_enabled', false)
            && filled(self::pixelId());
    }

    /**
     * The configured pixel ID, or null when missing or malformed.
     *
     * The ID is interpolated straight into an inline <script>, so anything that
     * is not purely digits is discarded rather than escaped.
     */
    public static function pixelId(): ?string
    {
        $id = trim((string) Setting::get('meta_pixel_id'));

        return preg_match(self::ID_PATTERN, $id) === 1 ? $id : null;
    }

    /**
     * Queue a standard event for the next page the visitor sees: the page a
     * form redirects to once its submission has been stored.
     *
     * `$pageUrl` is the page the event reports when that next page never loads
     * the pixel script (the signed PPDB status page), so it must be a URL that
     * is safe to hand to Meta.
     *
     * @param  array<string, string|int|float>  $parameters
     */
    public static function queueEvent(string $name, array $parameters, string $pageUrl): void
    {
        if (! self::enabled()) {
            return;
        }

        $events = self::queuedEvents();
        $events[] = [
            'name' => $name,
            'parameters' => $parameters,
            'page_url' => $pageUrl,
            'queued_at' => now()->getTimestampMs(),
        ];

        session()->flash(self::QUEUE_SESSION_KEY, $events);
    }

    /**
     * Events queued by the previous request for this page to send.
     *
     * @return list<array{name: string, parameters: array<string, string|int|float>, page_url: string, queued_at: int}>
     */
    public static function queuedEvents(): array
    {
        $events = session()->get(self::QUEUE_SESSION_KEY);

        return is_array($events) ? array_values($events) : [];
    }

    /**
     * The image-pixel URL reporting a queued event without the pixel script,
     * for pages whose own URL must never reach Meta.
     *
     * The event reports its queued page URL instead of the current one, and
     * Meta's first-party cookies ride along so it can still be matched to the
     * ad click that brought the visitor in.
     *
     * @param  array{name: string, parameters: array<string, string|int|float>, page_url: string, queued_at: int}  $event
     */
    public static function imagePixelUrl(array $event, Request $request): string
    {
        $query = [
            'id' => self::pixelId(),
            'ev' => $event['name'],
            'dl' => $event['page_url'],
            'ts' => $event['queued_at'],
            'cd' => $event['parameters'],
        ];

        foreach (self::FORWARDED_COOKIES as $parameter => $cookie) {
            $value = $request->cookie($cookie);

            if (is_string($value) && preg_match(self::COOKIE_PATTERN, $value) === 1) {
                $query[$parameter] = $value;
            }
        }

        return self::IMAGE_PIXEL_ENDPOINT.'?'.http_build_query($query, encoding_type: PHP_QUERY_RFC3986);
    }

    /**
     * The image pixel recording a PageView for browsers with JavaScript off.
     */
    public static function noscriptPageViewUrl(): string
    {
        return self::IMAGE_PIXEL_ENDPOINT.'?'.http_build_query([
            'id' => self::pixelId(),
            'ev' => 'PageView',
            'noscript' => 1,
        ], encoding_type: PHP_QUERY_RFC3986);
    }
}
