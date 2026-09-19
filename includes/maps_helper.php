<?php
/**
 * Google Maps helper — shared by admin/projects/add.php, admin/projects/edit.php,
 * pages/project-details.php, and pages/project.php.
 * Do NOT rename these functions; they are called by name from all four places.
 */

if (!function_exists('isValidGoogleMapsUrl')) {
    /**
     * Validates that a submitted URL looks like a genuine Google Maps link.
     * Accepts full google.com/maps links, maps.google.com, and the goo.gl /
     * maps.app.goo.gl short-link formats. Rejects anything else (prevents
     * arbitrary/XSS-risk URLs being stored and later output as href values).
     */
    function isValidGoogleMapsUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        // Must be a syntactically valid http/https URL first.
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parts = parse_url($url);
        if (empty($parts['scheme']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }
        if (empty($parts['host'])) {
            return false;
        }

        $host = strtolower($parts['host']);
        $allowedHosts = [
            'google.com',
            'www.google.com',
            'maps.google.com',
            'maps.app.goo.gl',
            'goo.gl',
        ];

        foreach ($allowedHosts as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('buildDirectionsUrl')) {
    /**
     * Builds a "Get Directions" URL for a project.
     * Priority: use precise lat/lng if both are present (accurate pin-to-pin
     * navigation). Falls back to a text destination (sector + location) only
     * if coordinates are missing for a given project.
     *
     * @param float|null $lat
     * @param float|null $lng
     * @param string     $fallbackText e.g. "Sec-113, Dwarka Exp."
     */
    function buildDirectionsUrl($lat, $lng, string $fallbackText = ''): string
    {
        if ($lat !== null && $lng !== null && $lat !== '' && $lng !== '') {
            $destination = $lat . ',' . $lng;
        } else {
            $destination = trim($fallbackText);
        }

        return 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($destination);
    }
}