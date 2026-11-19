<?php
namespace App\Services;

/**
 * Documents that must NOT be public (company verification papers, applicants' CVs).
 * Files live in storage/private_uploads/<dir>/ (outside the web root) under random names and are only
 * handed out through a controller that has checked who is asking.
 */
class PrivateUploadService
{
    public const DOC_TYPES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
    public const CV_TYPES = ['application/pdf' => 'pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'];

    private function dir(string $dir): string
    {
        $path = base_path('storage/private_uploads/' . $dir);
        if (!is_dir($path)) { @mkdir($path, 0750, true); }
        return $path;
    }

    /**
     * Validate (real MIME type, size) and store an uploaded file.
     * @param array<string,string> $allowed mime => extension
     * @return array{stored:string, original:string, mime:string, size:int}|string  the file info, or an error message
     */
    public function save(?array $file, string $dir, array $allowed, int $maxBytes)
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { return 'Please choose a file.'; }
        if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) { return 'The file could not be uploaded. Try a smaller file.'; }
        if ((int) $file['size'] > $maxBytes) { return 'The file is too large (max ' . round($maxBytes / 1048576, 1) . ' MB).'; }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ($mime === 'application/zip' && $ext === 'docx' && in_array('docx', $allowed, true)) { // some servers report .docx as zip
            $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        }
        if (!isset($allowed[$mime])) { return 'This file type is not allowed. Allowed: ' . strtoupper(implode(', ', array_unique($allowed))) . '.'; }
        $stored = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $this->dir($dir) . '/' . $stored)) { return 'The file could not be saved. Please try again.'; }
        $original = mb_substr(preg_replace('/[^\p{L}\p{N}._ -]/u', '_', basename((string) $file['name'])), 0, 200);
        return ['stored' => $stored, 'original' => $original ?: ('file.' . $allowed[$mime]), 'mime' => $mime, 'size' => (int) $file['size']];
    }

    public function delete(string $dir, string $stored): void
    {
        $f = $this->dir($dir) . '/' . basename($stored);
        if (is_file($f)) { @unlink($f); }
    }

    /** Send the file to the browser as a download, then stop. Callers must have checked permission first. */
    public function stream(string $dir, string $stored, string $downloadName, string $mime): never
    {
        $f = $this->dir($dir) . '/' . basename($stored);
        if (!is_file($f)) { http_response_code(404); echo 'File not found.'; exit; }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($f));
        header('Content-Disposition: attachment; filename="' . str_replace(['"', "\r", "\n"], '', $downloadName) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($f);
        exit;
    }
}
