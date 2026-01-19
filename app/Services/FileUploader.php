<?php
declare(strict_types=1);

namespace App\Services;

class FileUploader
{
    private string $baseUploadDir;
    private array $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    private int $maxSize = 10485760; // 10MB

    public function __construct()
    {
        $this->baseUploadDir = UPLOADS_PATH . '/';
        if (!is_dir($this->baseUploadDir)) {
            mkdir($this->baseUploadDir, 0755, true);
        }
    }

    /**
     * Upload a file to a page-specific directory
     * 
     * @param array $file The uploaded file array from $_FILES
     * @param string $prefix File name prefix
     * @param int|null $pageId The page ID to create a separate folder for. If null, uses 'general' folder
     * @return string The full path to the uploaded file
     */
    public function upload(array $file, string $prefix = 'file', ?int $pageId = null): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload error: ' . $file['error']);
        }

        if ($file['size'] > $this->maxSize) {
            throw new \RuntimeException('File too large');
        }

        $mimeType = mime_content_type($file['tmp_name']);
        if (!isset($this->allowedMimes[$mimeType])) {
            throw new \RuntimeException('Invalid file type');
        }

        // Determine upload directory based on pageId
        if ($pageId !== null && $pageId > 0) {
            $uploadDir = $this->baseUploadDir . 'page_' . $pageId . '/';
        } else {
            $uploadDir = $this->baseUploadDir . 'general/';
        }

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = $this->allowedMimes[$mimeType];
        $filename = $prefix . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
        $path = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new \RuntimeException('Failed to move uploaded file');
        }

        return $path;
    }

    /**
     * Get the upload directory for a specific page
     * 
     * @param int|null $pageId The page ID
     * @return string The upload directory path
     */
    public function getUploadDir(?int $pageId = null): string
    {
        if ($pageId !== null && $pageId > 0) {
            return $this->baseUploadDir . 'page_' . $pageId . '/';
        }
        return $this->baseUploadDir . 'general/';
    }
}



