<?php
namespace App\Services;

/** Image uploads (course thumbnails, avatars). Validates real MIME type, renames, stores under public/assets/uploads. */
class UploadService
{
    private const ALLOWED = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /** @return string|null public-relative path like "uploads/abc123.jpg", or null if no/invalid file */
    public function image(?array $file, int $maxBytes = 2_000_000): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $file['size'] > $maxBytes) {
            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::ALLOWED[$mime])) {
            return null;
        }
        $name = bin2hex(random_bytes(12)) . '.' . self::ALLOWED[$mime];
        if (!move_uploaded_file($file['tmp_name'], base_path('public/assets/uploads/' . $name))) {
            return null;
        }
        return 'uploads/' . $name;
    }
}
