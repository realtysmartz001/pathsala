<?php
/**
 * Events Management helper — shared by admin/events/add.php and admin/events/edit.php
 * Do NOT rename this function; it is called by name from both files.
 */

if (!function_exists('uploadEventImage')) {
    /**
     * Validates and uploads an event banner image into uploads/events/.
     * Returns ['filename' => '...'] on success, or ['error' => '...'] on failure.
     * Never overwrites existing files.
     *
     * @param string $field   The $_FILES field name (e.g. 'image')
     * @param int    $maxSize Max allowed size in bytes (default 5MB)
     */
    function uploadEventImage(string $field, int $maxSize = 5 * 1024 * 1024)
    {
        if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Please select a banner image to upload.'];
        }

        $tmpPath  = $_FILES[$field]['tmp_name'];
        $origName = basename($_FILES[$field]['name']);
        $fileSize = $_FILES[$field]['size'];

        if ($fileSize > $maxSize) {
            return ['error' => 'Image exceeds the maximum allowed size of 5 MB.'];
        }

        // ── MIME type detection (with safe fallback if finfo is unavailable/restricted) ──
        $fileMime = null;
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $fileMime = finfo_file($finfo, $tmpPath);
                finfo_close($finfo);
            }
        }
        if (empty($fileMime) && function_exists('mime_content_type')) {
            $fileMime = @mime_content_type($tmpPath);
        }

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return ['error' => 'Invalid file extension.'];
        }

        // If MIME detection failed entirely (server restriction), fall back to
        // trusting the validated extension rather than blocking a legitimate upload.
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!empty($fileMime) && !in_array($fileMime, $allowedTypes, true)) {
            return ['error' => 'Invalid image type. Only JPG, PNG, and WEBP are allowed.'];
        }

        $targetDir = __DIR__ . '/../uploads/events/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // ── Unique filename — guaranteed no overwrite ──
        $uniquePart = str_replace('.', '', uniqid('event_', true));
        $fileName = $uniquePart . '.' . $ext;
        $target = $targetDir . $fileName;

        if (!move_uploaded_file($tmpPath, $target)) {
            return ['error' => 'Failed to upload image. Please try again.'];
        }

        return ['filename' => $fileName];
    }
}