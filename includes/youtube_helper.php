<?php
/**
 * YouTube helper functions — shared by admin/youtube/add.php and admin/youtube/edit.php
 * Do NOT rename these functions; they are called by name from both files.
 */

if (!function_exists('extractYoutubeId')) {
    /**
     * Extracts the 11-character YouTube video ID from any common YouTube URL format:
     *  - https://www.youtube.com/watch?v=XXXXXXXXXXX
     *  - https://youtu.be/XXXXXXXXXXX
     *  - https://www.youtube.com/embed/XXXXXXXXXXX
     *  - https://www.youtube.com/shorts/XXXXXXXXXXX
     * Returns the 11-char ID string on success, or false if not found.
     */
    function extractYoutubeId(string $url)
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        $patterns = [
            '~youtu\.be/([A-Za-z0-9_-]{11})~i',
            '~youtube\.com/watch\?v=([A-Za-z0-9_-]{11})~i',
            '~youtube\.com/embed/([A-Za-z0-9_-]{11})~i',
            '~youtube\.com/shorts/([A-Za-z0-9_-]{11})~i',
            '~[?&]v=([A-Za-z0-9_-]{11})~i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $m)) {
                return $m[1];
            }
        }

        return false;
    }

    /**
     * Given a YouTube video ID, returns the best available thumbnail URL.
     * Tries maxresdefault.jpg first; if it does not exist (YouTube serves a
     * tiny 120x90 placeholder for videos without a maxres thumbnail), falls
     * back to hqdefault.jpg which always exists for a valid video ID.
     */
    function getYoutubeThumbnail(string $youtubeId): string
    {
        $maxres = "https://img.youtube.com/vi/{$youtubeId}/maxresdefault.jpg";
        $hq     = "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg";

        if (maxresThumbnailExists($maxres)) {
            return $maxres;
        }

        return $hq;
    }

    /**
     * YouTube returns a real image for maxresdefault.jpg only if a
     * maxres thumbnail exists; otherwise it still returns HTTP 200 but with
     * a small placeholder (~120x90). We check actual image dimensions.
     */
    function maxresThumbnailExists(string $url): bool
    {
        // Suppress warnings: getimagesize() on a dead/unreachable URL throws
        // an E_WARNING, not an exception — we handle failure via the false check.
        $info = @getimagesize($url);

        if ($info === false) {
            return false;
        }

        // Real maxresdefault.jpg is 1280x720. The placeholder is 120x90.
        return isset($info[0]) && $info[0] > 120;
    }

    /**
     * Builds the standard embed URL for a given YouTube video ID.
     */
    function getYoutubeEmbedUrl(string $youtubeId): string
    {
        return "https://www.youtube.com/embed/" . $youtubeId;
    }
}