<?php
/**
 * Project Gallery helper — shared by admin/projects/gallery_manage.php and
 * admin/projects/gallery_upload_handler.php.
 * Do NOT rename these functions; they are called by name from both files.
 */

if (!function_exists('uploadGalleryImage')) {
    /**
     * Validates and uploads a single gallery image into
     * uploads/project_gallery/{slug}/. Returns ['filename' => '...'] on
     * success, or ['error' => '...'] on failure. Never overwrites — unique
     * filenames are always generated.
     *
     * @param array  $file    A single element from $_FILES (already isolated
     *                        by the caller when looping a multi-file input)
     * @param string $slug    The project's slug — used as the folder name
     * @param int    $maxSize Max allowed size in bytes (default 10MB)
     */
    function uploadGalleryImage(array $file, string $slug, int $maxSize = 10 * 1024 * 1024)
    {
        if (empty($file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Upload failed for one of the selected files.'];
        }

        $tmpPath  = $file['tmp_name'];
        $origName = basename($file['name']);
        $fileSize = $file['size'];

        if ($fileSize > $maxSize) {
            return ['error' => "\"$origName\" exceeds the maximum allowed size of 10 MB."];
        }

        // ── MIME type detection (with safe fallback if finfo is restricted) ──
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
            return ['error' => "\"$origName\" has an invalid file extension. Only JPG, PNG, and WEBP are allowed."];
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!empty($fileMime) && !in_array($fileMime, $allowedTypes, true)) {
            return ['error' => "\"$origName\" is not a valid image file."];
        }

        $safeSlug  = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));
        $targetDir = __DIR__ . '/../uploads/project_gallery/' . $safeSlug . '/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // ── Unique filename — guaranteed no overwrite/duplicate ──
        $uniquePart = str_replace('.', '', uniqid('img_', true));
        $fileName   = $uniquePart . '.' . $ext;
        $target     = $targetDir . $fileName;

        if (!move_uploaded_file($tmpPath, $target)) {
            return ['error' => "Failed to save \"$origName\". Please try again."];
        }

        // Stored path is relative to uploads/project_gallery/ so the DB
        // record doesn't hardcode the slug (slug can change on edit).
        return ['filename' => $safeSlug . '/' . $fileName];
    }
}