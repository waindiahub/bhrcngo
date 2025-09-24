<?php
/**
 * File Model
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/BaseModel.php';

class File extends BaseModel {
    protected $table = 'files';
    protected $fillable = [
        'filename', 'original_name', 'file_path', 'file_size', 'mime_type',
        'file_type', 'uploaded_by', 'description', 'alt_text', 'category',
        'is_public', 'download_count', 'metadata'
    ];
    
    private $allowedTypes = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'],
        'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'],
        'audio' => ['mp3', 'wav', 'ogg', 'aac', 'm4a']
    ];
    
    private $maxFileSize = 10485760; // 10MB default
    
    /**
     * Upload single file
     */
    public function uploadFile($fileData, $options = []) {
        // Validate file
        $validation = $this->validateFile($fileData, $options);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        // Generate unique filename
        $extension = pathinfo($fileData['name'], PATHINFO_EXTENSION);
        $filename = $this->generateUniqueFilename($extension);
        
        // Determine upload directory
        $uploadDir = $this->getUploadDirectory($options['category'] ?? 'general');
        $filePath = $uploadDir . '/' . $filename;
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Move uploaded file
        if (!move_uploaded_file($fileData['tmp_name'], $filePath)) {
            return ['success' => false, 'message' => 'Failed to upload file'];
        }
        
        // Get file metadata
        $metadata = $this->extractMetadata($filePath, $fileData);
        
        // Save file record to database
        $fileRecord = [
            'filename' => $filename,
            'original_name' => $fileData['name'],
            'file_path' => $filePath,
            'file_size' => $fileData['size'],
            'mime_type' => $fileData['type'],
            'file_type' => $this->getFileType($extension),
            'uploaded_by' => $_SESSION['user_id'] ?? null,
            'description' => $options['description'] ?? null,
            'alt_text' => $options['alt_text'] ?? null,
            'category' => $options['category'] ?? 'general',
            'is_public' => $options['is_public'] ?? true,
            'download_count' => 0,
            'metadata' => json_encode($metadata)
        ];
        
        $file = $this->create($fileRecord);
        
        if ($file) {
            return ['success' => true, 'file' => $file];
        }
        
        // Clean up file if database insert failed
        unlink($filePath);
        return ['success' => false, 'message' => 'Failed to save file record'];
    }
    
    /**
     * Upload multiple files
     */
    public function uploadMultipleFiles($filesData, $options = []) {
        $results = [];
        $uploaded = 0;
        $errors = [];
        
        foreach ($filesData['name'] as $index => $name) {
            $fileData = [
                'name' => $filesData['name'][$index],
                'type' => $filesData['type'][$index],
                'tmp_name' => $filesData['tmp_name'][$index],
                'error' => $filesData['error'][$index],
                'size' => $filesData['size'][$index]
            ];
            
            $result = $this->uploadFile($fileData, $options);
            
            if ($result['success']) {
                $uploaded++;
                $results[] = $result['file'];
            } else {
                $errors[] = [
                    'filename' => $name,
                    'error' => $result['message'] ?? 'Upload failed'
                ];
            }
        }
        
        return [
            'uploaded' => $uploaded,
            'files' => $results,
            'errors' => $errors,
            'total' => count($filesData['name'])
        ];
    }
    
    /**
     * Validate file
     */
    private function validateFile($fileData, $options = []) {
        $errors = [];
        
        // Check for upload errors
        if ($fileData['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $this->getUploadErrorMessage($fileData['error']);
        }
        
        // Check file size
        $maxSize = $options['max_size'] ?? $this->maxFileSize;
        if ($fileData['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size of ' . $this->formatFileSize($maxSize);
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));
        $allowedExtensions = $this->getAllowedExtensions($options['allowed_types'] ?? null);
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowedExtensions);
        }
        
        // Check MIME type
        if (!$this->isValidMimeType($fileData['type'], $extension)) {
            $errors[] = 'Invalid file type detected';
        }
        
        // Additional security checks
        if ($this->isExecutableFile($extension)) {
            $errors[] = 'Executable files are not allowed';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Get allowed extensions
     */
    private function getAllowedExtensions($allowedTypes = null) {
        if ($allowedTypes === null) {
            // Return all allowed extensions
            $extensions = [];
            foreach ($this->allowedTypes as $type => $exts) {
                $extensions = array_merge($extensions, $exts);
            }
            return $extensions;
        }
        
        $extensions = [];
        foreach ($allowedTypes as $type) {
            if (isset($this->allowedTypes[$type])) {
                $extensions = array_merge($extensions, $this->allowedTypes[$type]);
            }
        }
        
        return $extensions;
    }
    
    /**
     * Get file type from extension
     */
    private function getFileType($extension) {
        foreach ($this->allowedTypes as $type => $extensions) {
            if (in_array(strtolower($extension), $extensions)) {
                return $type;
            }
        }
        return 'other';
    }
    
    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($extension) {
        return uniqid() . '_' . time() . '.' . $extension;
    }
    
    /**
     * Get upload directory
     */
    private function getUploadDirectory($category) {
        $baseDir = __DIR__ . '/../../uploads';
        return $baseDir . '/' . $category;
    }
    
    /**
     * Extract file metadata
     */
    private function extractMetadata($filePath, $fileData) {
        $metadata = [
            'upload_date' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ];
        
        // Get image dimensions for images
        if (in_array(strtolower(pathinfo($filePath, PATHINFO_EXTENSION)), $this->allowedTypes['image'])) {
            $imageInfo = getimagesize($filePath);
            if ($imageInfo) {
                $metadata['width'] = $imageInfo[0];
                $metadata['height'] = $imageInfo[1];
                $metadata['aspect_ratio'] = round($imageInfo[0] / $imageInfo[1], 2);
            }
        }
        
        // Get video duration for videos (requires FFmpeg)
        if (in_array(strtolower(pathinfo($filePath, PATHINFO_EXTENSION)), $this->allowedTypes['video'])) {
            // This would require FFmpeg integration
            $metadata['duration'] = null;
        }
        
        return $metadata;
    }
    
    /**
     * Get files with filters
     */
    public function getFiles($filters = [], $limit = 20, $page = 1) {
        $offset = ($page - 1) * $limit;
        $conditions = [];
        $params = [];
        
        if (!empty($filters['category'])) {
            $conditions[] = "category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['file_type'])) {
            $conditions[] = "file_type = ?";
            $params[] = $filters['file_type'];
        }
        
        if (!empty($filters['uploaded_by'])) {
            $conditions[] = "uploaded_by = ?";
            $params[] = $filters['uploaded_by'];
        }
        
        if (!empty($filters['is_public'])) {
            $conditions[] = "is_public = ?";
            $params[] = $filters['is_public'];
        }
        
        if (!empty($filters['search'])) {
            $conditions[] = "(original_name LIKE ? OR description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, [$searchTerm, $searchTerm]);
        }
        
        $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $sql = "SELECT f.*, u.name as uploaded_by_name 
                FROM {$this->table} f
                LEFT JOIN users u ON f.uploaded_by = u.id
                {$whereClause}
                ORDER BY f.created_at DESC 
                LIMIT {$limit} OFFSET {$offset}";
        
        return dbFetchAll($sql, $params);
    }
    
    /**
     * Download file
     */
    public function downloadFile($fileId, $userId = null) {
        $file = $this->find($fileId);
        
        if (!$file) {
            return ['success' => false, 'message' => 'File not found'];
        }
        
        // Check permissions
        if (!$file['is_public'] && $file['uploaded_by'] != $userId && !$this->isAdmin($userId)) {
            return ['success' => false, 'message' => 'Access denied'];
        }
        
        // Check if file exists
        if (!file_exists($file['file_path'])) {
            return ['success' => false, 'message' => 'File not found on disk'];
        }
        
        // Increment download count
        $this->update($fileId, [
            'download_count' => $file['download_count'] + 1
        ]);
        
        return [
            'success' => true,
            'file_path' => $file['file_path'],
            'filename' => $file['original_name'],
            'mime_type' => $file['mime_type']
        ];
    }
    
    /**
     * Delete file
     */
    public function deleteFile($fileId, $userId = null) {
        $file = $this->find($fileId);
        
        if (!$file) {
            return ['success' => false, 'message' => 'File not found'];
        }
        
        // Check permissions
        if ($file['uploaded_by'] != $userId && !$this->isAdmin($userId)) {
            return ['success' => false, 'message' => 'Access denied'];
        }
        
        // Delete file from disk
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
        
        // Delete database record
        $deleted = $this->delete($fileId);
        
        if ($deleted) {
            return ['success' => true, 'message' => 'File deleted successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to delete file record'];
    }
    
    /**
     * Get file statistics
     */
    public function getStats() {
        $stats = [];
        
        // Total files
        $stats['total'] = $this->count();
        
        // By file type
        $sql = "SELECT file_type, COUNT(*) as count FROM {$this->table} GROUP BY file_type";
        $results = dbFetchAll($sql);
        $stats['by_type'] = [];
        foreach ($results as $result) {
            $stats['by_type'][$result['file_type']] = $result['count'];
        }
        
        // By category
        $sql = "SELECT category, COUNT(*) as count FROM {$this->table} GROUP BY category";
        $results = dbFetchAll($sql);
        $stats['by_category'] = [];
        foreach ($results as $result) {
            $stats['by_category'][$result['category']] = $result['count'];
        }
        
        // Total size
        $sql = "SELECT SUM(file_size) as total_size FROM {$this->table}";
        $result = dbFetch($sql);
        $stats['total_size'] = $result['total_size'] ?? 0;
        $stats['total_size_formatted'] = $this->formatFileSize($stats['total_size']);
        
        // Most downloaded
        $sql = "SELECT original_name, download_count FROM {$this->table} 
                ORDER BY download_count DESC LIMIT 10";
        $stats['most_downloaded'] = dbFetchAll($sql);
        
        return $stats;
    }
    
    /**
     * Format file size
     */
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds the maximum allowed size';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds the form maximum size';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Check if MIME type is valid for extension
     */
    private function isValidMimeType($mimeType, $extension) {
        $validMimes = [
            'jpg' => ['image/jpeg', 'image/jpg'],
            'jpeg' => ['image/jpeg', 'image/jpg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'mp4' => ['video/mp4'],
            'mp3' => ['audio/mpeg', 'audio/mp3']
        ];
        
        if (!isset($validMimes[$extension])) {
            return true; // Allow if not in our specific list
        }
        
        return in_array($mimeType, $validMimes[$extension]);
    }
    
    /**
     * Check if file is executable
     */
    private function isExecutableFile($extension) {
        $executableExtensions = ['exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar', 'php', 'asp', 'jsp'];
        return in_array(strtolower($extension), $executableExtensions);
    }
    
    /**
     * Check if user is admin
     */
    private function isAdmin($userId) {
        if (!$userId) return false;
        
        $sql = "SELECT role FROM users WHERE id = ?";
        $result = dbFetch($sql, [$userId]);
        
        return $result && in_array($result['role'], ['admin', 'super_admin']);
    }
    
    /**
     * Get recent uploads
     */
    public function getRecentUploads($limit = 10) {
        $sql = "SELECT f.*, u.name as uploaded_by_name 
                FROM {$this->table} f
                LEFT JOIN users u ON f.uploaded_by = u.id
                ORDER BY f.created_at DESC 
                LIMIT {$limit}";
        
        return dbFetchAll($sql);
    }
    
    /**
     * Clean up orphaned files
     */
    public function cleanupOrphanedFiles() {
        $sql = "SELECT file_path FROM {$this->table}";
        $dbFiles = dbFetchAll($sql);
        $dbFilePaths = array_column($dbFiles, 'file_path');
        
        $uploadDir = __DIR__ . '/../../uploads';
        $diskFiles = $this->getAllFilesRecursive($uploadDir);
        
        $orphanedFiles = array_diff($diskFiles, $dbFilePaths);
        $deletedCount = 0;
        
        foreach ($orphanedFiles as $file) {
            if (unlink($file)) {
                $deletedCount++;
            }
        }
        
        return [
            'deleted' => $deletedCount,
            'total_orphaned' => count($orphanedFiles)
        ];
    }
    
    /**
     * Get all files recursively
     */
    private function getAllFilesRecursive($dir) {
        $files = [];
        
        if (is_dir($dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }
        }
        
        return $files;
    }
}
?>