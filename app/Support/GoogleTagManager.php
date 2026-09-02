<?php

namespace App\Support;

use App\Models\Setting;

class GoogleTagManager
{
    /** Container IDs are always "GTM-" followed by the short container code. */
    private const ID_PATTERN = '/^GTM-[A-Z0-9]{4,}$/';

    /**
     * The container is active only when enabled in settings and the stored ID
     * is a well-formed container ID.
     */
    public static function enabled(): bool
    {
        return (bool) Setting::get('gtm_enabled', false)
            && filled(self::containerId());
    }

    /**
     * The configured container ID, or null when missing or malformed.
     *
     * The ID is interpolated straight into an inline <script>, so anything that
     * does not look like a container ID is discarded rather than escaped.
     */
    public static function containerId(): ?string
    {
        $id = strtoupper(trim((string) Setting::get('gtm_container_id')));

        return preg_match(self::ID_PATTERN, $id) === 1 ? $id : null;
    }
}
