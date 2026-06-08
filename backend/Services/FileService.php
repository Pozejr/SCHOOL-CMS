<?php

namespace App\Services;

use App\Models\File;
use App\Models\ActivityLog;
use App\Helpers\Security;
use App\Helpers\FileSystem;
use App\Helpers\Logger;

class FileService
{
    private File $fileModel;
    private ActivityLog $activityLog;
    private array $config;

    public function __construct(File $fileModel, ActivityLog $activityLog, array $config)
    {
        $this->fileModel = $fileModel;
        $this->activityLog = $activityLog;
        $this->config = $config;
    }

    public function upload(array $file, int $userId, ?string $entityType = null, ?int $entityId = null): array
    {
        // Validate upload
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'error' => 'No file uploaded'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => $this->getUploadErrorMessage($file['error'])];
        }

        $mimeType = $file['type'];
        $fileSize = $file['size'];
        $originalName = $file['name'];

        // Determine file category
        $isImage = in_array($mimeType, $this->config['allowed_image_types']);
        $isDocument = in_array($mimeType, $this->config['allowed_document_types']);

        if (!$isImage && !$isDocument) {
            return ['success' => false, 'error' => 'File type not allowed. Accepted: JPEG, PNG, GIF, WebP, PDF'];
        }

        // Validate file size
        $maxSize = $isImage ? $this->config['image_max_size'] : $this->config['max_size'];
        if (!Security::validateFileSize($fileSize, $maxSize)) {
            $maxMB = round($maxSize / 1048576, 1);
            return ['success' => false, 'error' => "File too large. Maximum size: {$maxMB}MB"];
        }

        // Validate actual file content
        $allowedMimes = array_merge($this->config['allowed_image_types'], $this->config['allowed_document_types']);
        if (!Security::validateFileType($file['tmp_name'], $allowedMimes)) {
            return ['success' => false, 'error' => 'File content does not match the file type'];
        }

        // Check upload rate limit
        $recentUploads = $this->fileModel->countByUser($userId, 1);
        if ($recentUploads >= $this->config['max_files_per_hour']) {
            return ['success' => false, 'error' => 'Upload rate limit reached. Try again later.'];
        }

        // Generate secure filename and path
        $secureName = Security::generateSecureFilename($originalName);
        $category = $isImage ? 'images' : 'documents';
        $uploadDir = $this->config['path'] . '/' . $category;

        // Build absolute path from the backend root directory.
        // __DIR__ = .../backend/services → dirname = .../backend
        // Result: .../backend/uploads/images  or  .../backend/uploads/documents
        $backendRoot = dirname(__DIR__);
        $uploadPath = $backendRoot . '/' . $uploadDir;

        FileSystem::ensureDirectory($uploadPath);

        $filePath = $uploadPath . '/' . $secureName;
        // Relative path stored in DB: uploads/images/filename.jpg
        $relativePath = $uploadDir . '/' . $secureName;

        // Move file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'error' => 'Failed to save file'];
        }

        // Save to database
        $fileRecord = $this->fileModel->create([
            'filename' => $secureName,
            'original_name' => Security::sanitizeString($originalName),
            'file_path' => $relativePath,
            'file_type' => $isImage ? 'image' : 'document',
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'uploaded_by' => $userId,
        ]);

        $this->activityLog->log(
            $userId,
            'upload',
            'file',
            $fileRecord['id'],
            "Uploaded file: {$originalName}",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        Logger::info('File uploaded', ['file_id' => $fileRecord['id'], 'filename' => $secureName]);

        return ['success' => true, 'file' => $fileRecord];
    }

    public function getAll(int $page = 1, int $perPage = 20): array
    {
        $files = $this->fileModel->findAllPaginated($page, $perPage);
        $total = $this->fileModel->countAll();
        return ['files' => $files, 'total' => $total];
    }

    public function delete(int $id, int $userId): array
    {
        $file = $this->fileModel->findById($id);
        if (!$file) {
            return ['success' => false, 'error' => 'File not found'];
        }

        // Delete physical file
        // Build absolute path from the backend root directory (same as upload logic)
        $backendRoot = dirname(__DIR__);
        $physicalPath = $backendRoot . '/' . $file['file_path'];
        FileSystem::deleteFile($physicalPath);

        // Delete database record
        $this->fileModel->delete($id);

        $this->activityLog->log(
            $userId,
            'delete',
            'file',
            $id,
            "Deleted file: {$file['original_name']}",
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        );

        return ['success' => true, 'message' => 'File deleted successfully'];
    }

    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload blocked by extension',
            default => 'Unknown upload error',
        };
    }
}
