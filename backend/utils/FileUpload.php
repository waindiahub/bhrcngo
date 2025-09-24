<?php
/**
 * File Upload Utility Class
 * BHRC - Bharatiya Human Rights Council
 */

class FileUpload {
    private $uploadPath;
    private $allowedTypes;
    private $maxFileSize;
    private $maxFiles;
    
    public function __construct() {
        $config = require __DIR__ . '/../config/config.php';
        $uploadConfig = $config['upload'];
        
        $this->uploadPath = $uploadConfig['path'];
        $this->allowedTypes = $uploadConfig['allowed_types'];
        $this->maxFileSize = $uploadConfig['max_file_size'];
        $this->maxFiles = $uploadConfig['max_files'];
        
        // Create upload directory if it doesn't exist
        $this->createUploadDirectory();
    }
    
    /**
     * Upload single file
     */
    public function uploadSingle($file, $subfolder = '') {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }
            
            // Generate unique filename
            $filename = $this->generateUniqueFilename($file['name']);
            
            // Create subfolder if specified
            $targetDir = $this->uploadPath;
            if (!empty($subfolder)) {
                $targetDir .= '/' . $subfolder;
                $this->createDirectory($targetDir);
            }
            
            $targetPath = $targetDir . '/' . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Set proper permissions
                chmod($targetPath, 0644);
                
                $fileInfo = [
                    'original_name' => $file['name'],
                    'filename' => $filename,
                    'path' => $targetPath,
                    'relative_path' => str_replace($this->uploadPath . '/', '', $targetPath),
                    'size' => $file['size'],
                    'type' => $file['type'],
                    'extension' => $this->getFileExtension($file['name']),
                    'uploaded_at' => date('Y-m-d H:i:s')
                ];
                
                return ['success' => true, 'file' => $fileInfo];
            } else {
                return ['success' => false, 'message' => 'Failed to move uploaded file'];
            }
            
        } catch (Exception $e) {
            error_log("File upload error: " . $e->getMessage());
            return ['success' => false, 'message' => 'File upload failed'];
        }
    }
    
    /**
     * Upload multiple files
     */
    public function uploadMultiple($files, $subfolder = '') {
        try {
            // Normalize files array
            $normalizedFiles = $this->normalizeFilesArray($files);
            
            // Check file count limit
            if (count($normalizedFiles) > $this->maxFiles) {
                return ['success' => false, 'message' => "Maximum {$this->maxFiles} files allowed"];
            }
            
            $uploadedFiles = [];
            $errors = [];
            
            foreach ($normalizedFiles as $index => $file) {
                // Skip empty files
                if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                
                $result = $this->uploadSingle($file, $subfolder);
                
                if ($result['success']) {
                    $uploadedFiles[] = $result['file'];
                } else {
                    $errors[] = "File " . ($index + 1) . ": " . $result['message'];
                }
            }
            
            if (!empty($uploadedFiles)) {
                $message = count($uploadedFiles) . " file(s) uploaded successfully";
                if (!empty($errors)) {
                    $message .= ". Errors: " . implode(', ', $errors);
                }
                
                return ['success' => true, 'files' => $uploadedFiles, 'message' => $message];
            } else {
                return ['success' => false, 'message' => 'No files uploaded. Errors: ' . implode(', ', $errors)];
            }
            
        } catch (Exception $e) {
            error_log("Multiple file upload error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Multiple file upload failed'];
        }
    }
    
    /**
     * Upload image with resizing
     */
    public function uploadImage($file, $subfolder = '', $maxWidth = 1920, $maxHeight = 1080, $quality = 85) {
        try {
            // Upload original file
            $result = $this->uploadSingle($file, $subfolder);
            
            if (!$result['success']) {
                return $result;
            }
            
            $fileInfo = $result['file'];
            
            // Check if it's an image
            if (!$this->isImage($fileInfo['path'])) {
                return $result; // Return as is if not an image
            }
            
            // Resize image if needed
            $resizeResult = $this->resizeImage($fileInfo['path'], $maxWidth, $maxHeight, $quality);
            
            if ($resizeResult) {
                $fileInfo['resized'] = true;
                $fileInfo['dimensions'] = $this->getImageDimensions($fileInfo['path']);
            }
            
            return ['success' => true, 'file' => $fileInfo];
            
        } catch (Exception $e) {
            error_log("Image upload error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Image upload failed'];
        }
    }
    
    /**
     * Delete file
     */
    public function deleteFile($filePath) {
        try {
            if (file_exists($filePath) && is_file($filePath)) {
                return unlink($filePath);
            }
            return false;
        } catch (Exception $e) {
            error_log("File deletion error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate uploaded file
     */
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => $this->getUploadErrorMessage($file['error'])];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $maxSizeMB = round($this->maxFileSize / (1024 * 1024), 2);
            return ['valid' => false, 'message' => "File size exceeds maximum limit of {$maxSizeMB}MB"];
        }
        
        // Check file type
        $extension = strtolower($this->getFileExtension($file['name']));
        if (!in_array($extension, $this->allowedTypes)) {
            return ['valid' => false, 'message' => "File type '{$extension}' is not allowed"];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!$this->isAllowedMimeType($mimeType, $extension)) {
            return ['valid' => false, 'message' => "Invalid file type"];
        }
        
        // Additional security checks
        if (!$this->isSecureFile($file['tmp_name'], $extension)) {
            return ['valid' => false, 'message' => "File failed security validation"];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Generate unique filename
     */
    private function generateUniqueFilename($originalName) {
        $extension = $this->getFileExtension($originalName);
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        
        // Sanitize filename
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        $basename = substr($basename, 0, 50); // Limit length
        
        // Add timestamp and random string
        $timestamp = date('YmdHis');
        $random = bin2hex(random_bytes(4));
        
        return $basename . '_' . $timestamp . '_' . $random . '.' . $extension;
    }
    
    /**
     * Get file extension
     */
    private function getFileExtension($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Normalize files array for multiple uploads
     */
    private function normalizeFilesArray($files) {
        $normalized = [];
        
        if (isset($files['name']) && is_array($files['name'])) {
            // Multiple files with array structure
            for ($i = 0; $i < count($files['name']); $i++) {
                $normalized[] = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
            }
        } else {
            // Single file
            $normalized[] = $files;
        }
        
        return $normalized;
    }
    
    /**
     * Check if file is an image
     */
    private function isImage($filePath) {
        $imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        return in_array($extension, $imageTypes) && getimagesize($filePath) !== false;
    }
    
    /**
     * Resize image
     */
    private function resizeImage($filePath, $maxWidth, $maxHeight, $quality) {
        try {
            $imageInfo = getimagesize($filePath);
            if (!$imageInfo) {
                return false;
            }
            
            list($width, $height, $type) = $imageInfo;
            
            // Check if resizing is needed
            if ($width <= $maxWidth && $height <= $maxHeight) {
                return true; // No resizing needed
            }
            
            // Calculate new dimensions
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = round($width * $ratio);
            $newHeight = round($height * $ratio);
            
            // Create image resource
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $source = imagecreatefromjpeg($filePath);
                    break;
                case IMAGETYPE_PNG:
                    $source = imagecreatefrompng($filePath);
                    break;
                case IMAGETYPE_GIF:
                    $source = imagecreatefromgif($filePath);
                    break;
                default:
                    return false;
            }
            
            if (!$source) {
                return false;
            }
            
            // Create new image
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG and GIF
            if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            // Resize image
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // Save resized image
            switch ($type) {
                case IMAGETYPE_JPEG:
                    $result = imagejpeg($resized, $filePath, $quality);
                    break;
                case IMAGETYPE_PNG:
                    $result = imagepng($resized, $filePath, 9);
                    break;
                case IMAGETYPE_GIF:
                    $result = imagegif($resized, $filePath);
                    break;
                default:
                    $result = false;
            }
            
            // Clean up
            imagedestroy($source);
            imagedestroy($resized);
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Image resize error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get image dimensions
     */
    private function getImageDimensions($filePath) {
        $imageInfo = getimagesize($filePath);
        if ($imageInfo) {
            return [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1]
            ];
        }
        return null;
    }
    
    /**
     * Check if MIME type is allowed for extension
     */
    private function isAllowedMimeType($mimeType, $extension) {
        $allowedMimes = [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'txt' => ['text/plain'],
            'zip' => ['application/zip', 'application/x-zip-compressed']
        ];
        
        return isset($allowedMimes[$extension]) && in_array($mimeType, $allowedMimes[$extension]);
    }
    
    /**
     * Additional security checks for uploaded files
     */
    private function isSecureFile($filePath, $extension) {
        // Check for executable files
        $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'pl', 'py', 'jsp', 'asp', 'sh', 'cgi'];
        if (in_array($extension, $dangerousExtensions)) {
            return false;
        }
        
        // Check file content for PHP tags (basic check)
        if (in_array($extension, ['txt', 'csv'])) {
            $content = file_get_contents($filePath, false, null, 0, 1024);
            if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds the MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Create upload directory
     */
    private function createUploadDirectory() {
        $this->createDirectory($this->uploadPath);
    }
    
    /**
     * Create directory if it doesn't exist
     */
    private function createDirectory($path) {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
    
    /**
     * Get upload statistics
     */
    public function getUploadStats($subfolder = '') {
        $path = $this->uploadPath;
        if (!empty($subfolder)) {
            $path .= '/' . $subfolder;
        }
        
        if (!is_dir($path)) {
            return ['total_files' => 0, 'total_size' => 0];
        }
        
        $totalFiles = 0;
        $totalSize = 0;
        
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalFiles++;
                $totalSize += $file->getSize();
            }
        }
        
        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / (1024 * 1024), 2)
        ];
    }
    
    /**
     * Set upload path
     */
    public function setUploadPath($path) {
        $this->uploadPath = rtrim($path, '/');
        $this->createDirectory($this->uploadPath);
        return $this;
    }
    
    /**
     * Set allowed file types
     */
    public function setAllowedTypes($types) {
        if (is_string($types)) {
            $this->allowedTypes = explode(',', $types);
        } elseif (is_array($types)) {
            $this->allowedTypes = $types;
        }
        return $this;
    }
    
    /**
     * Set maximum file size
     */
    public function setMaxFileSize($size) {
        $this->maxFileSize = (int)$size;
        return $this;
    }
    
    /**
     * Upload file (alias for uploadSingle)
     */
    public function upload($file, $subfolder = '') {
        return $this->uploadSingle($file, $subfolder);
    }
    
    /**
     * Create thumbnail for image
     */
    public function createThumbnail($sourcePath, $thumbnailDir, $width = 300, $height = 300) {
        try {
            // Create thumbnail directory if it doesn't exist
            $this->createDirectory($thumbnailDir);
            
            // Get image info
            $imageInfo = getimagesize($sourcePath);
            if (!$imageInfo) {
                return ['success' => false, 'message' => 'Invalid image file'];
            }
            
            $sourceWidth = $imageInfo[0];
            $sourceHeight = $imageInfo[1];
            $imageType = $imageInfo[2];
            
            // Calculate thumbnail dimensions
            $ratio = min($width / $sourceWidth, $height / $sourceHeight);
            $thumbWidth = (int)($sourceWidth * $ratio);
            $thumbHeight = (int)($sourceHeight * $ratio);
            
            // Create source image resource
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $sourceImage = imagecreatefromjpeg($sourcePath);
                    break;
                case IMAGETYPE_PNG:
                    $sourceImage = imagecreatefrompng($sourcePath);
                    break;
                case IMAGETYPE_GIF:
                    $sourceImage = imagecreatefromgif($sourcePath);
                    break;
                default:
                    return ['success' => false, 'message' => 'Unsupported image type'];
            }
            
            if (!$sourceImage) {
                return ['success' => false, 'message' => 'Failed to create image resource'];
            }
            
            // Create thumbnail image
            $thumbnailImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
            
            // Preserve transparency for PNG and GIF
            if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
                imagealphablending($thumbnailImage, false);
                imagesavealpha($thumbnailImage, true);
                $transparent = imagecolorallocatealpha($thumbnailImage, 255, 255, 255, 127);
                imagefill($thumbnailImage, 0, 0, $transparent);
            }
            
            // Resize image
            imagecopyresampled(
                $thumbnailImage, $sourceImage,
                0, 0, 0, 0,
                $thumbWidth, $thumbHeight,
                $sourceWidth, $sourceHeight
            );
            
            // Generate thumbnail filename
            $pathInfo = pathinfo($sourcePath);
            $thumbnailFilename = $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
            $thumbnailPath = $thumbnailDir . '/' . $thumbnailFilename;
            
            // Save thumbnail
            $success = false;
            switch ($imageType) {
                case IMAGETYPE_JPEG:
                    $success = imagejpeg($thumbnailImage, $thumbnailPath, 85);
                    break;
                case IMAGETYPE_PNG:
                    $success = imagepng($thumbnailImage, $thumbnailPath, 8);
                    break;
                case IMAGETYPE_GIF:
                    $success = imagegif($thumbnailImage, $thumbnailPath);
                    break;
            }
            
            // Clean up memory
            imagedestroy($sourceImage);
            imagedestroy($thumbnailImage);
            
            if ($success) {
                return [
                    'success' => true,
                    'thumbnail_path' => $thumbnailPath,
                    'thumbnail_filename' => $thumbnailFilename,
                    'width' => $thumbWidth,
                    'height' => $thumbHeight
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to save thumbnail'];
            }
            
        } catch (Exception $e) {
            error_log("Thumbnail creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create thumbnail: ' . $e->getMessage()];
        }
    }
}
?>