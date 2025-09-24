<?php
/**
 * File Controller
 * Handles file uploads, downloads, and management
 */

require_once __DIR__ . '/BaseController.php';

class FileController extends BaseController {
    
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;
    
    public function __construct() {
        parent::__construct();
        $this->uploadDir = __DIR__ . '/../../uploads/';
        $this->allowedTypes = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'document' => ['pdf', 'doc', 'docx', 'txt', 'rtf'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv'],
            'audio' => ['mp3', 'wav', 'ogg', 'aac']
        ];
        $this->maxFileSize = 10 * 1024 * 1024; // 10MB
    }
    
    /**
     * Upload single file
     */
    public function upload() {
        try {
            if (!isset($_FILES['file'])) {
                $this->sendError('No file uploaded');
                return;
            }
            
            $file = $_FILES['file'];
            $category = $_POST['category'] ?? 'general';
            $description = $_POST['description'] ?? '';
            
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                $this->sendError($validation['message']);
                return;
            }
            
            // Create upload directory if it doesn't exist
            $categoryDir = $this->uploadDir . $category . '/';
            if (!is_dir($categoryDir)) {
                mkdir($categoryDir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $filepath = $categoryDir . $filename;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Save file info to database
                $fileInfo = $this->saveFileInfo([
                    'original_name' => $file['name'],
                    'filename' => $filename,
                    'filepath' => $category . '/' . $filename,
                    'category' => $category,
                    'description' => $description,
                    'size' => $file['size'],
                    'type' => $file['type'],
                    'extension' => $extension,
                    'uploaded_by' => $_SESSION['user_id'] ?? null
                ]);
                
                $this->sendResponse([
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'data' => $fileInfo
                ]);
            } else {
                $this->sendError('Failed to upload file');
            }
            
        } catch (Exception $e) {
            $this->sendError('Upload failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Upload multiple files
     */
    public function uploadMultiple() {
        try {
            if (!isset($_FILES['files'])) {
                $this->sendError('No files uploaded');
                return;
            }
            
            $files = $_FILES['files'];
            $category = $_POST['category'] ?? 'general';
            $uploadedFiles = [];
            $errors = [];
            
            // Handle multiple file upload
            $fileCount = count($files['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                // Skip if no file
                if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                
                // Validate file
                $validation = $this->validateFile($file);
                if (!$validation['valid']) {
                    $errors[] = $file['name'] . ': ' . $validation['message'];
                    continue;
                }
                
                // Create upload directory if it doesn't exist
                $categoryDir = $this->uploadDir . $category . '/';
                if (!is_dir($categoryDir)) {
                    mkdir($categoryDir, 0755, true);
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '_' . time() . '_' . $i . '.' . $extension;
                $filepath = $categoryDir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Save file info to database
                    $fileInfo = $this->saveFileInfo([
                        'original_name' => $file['name'],
                        'filename' => $filename,
                        'filepath' => $category . '/' . $filename,
                        'category' => $category,
                        'size' => $file['size'],
                        'type' => $file['type'],
                        'extension' => $extension,
                        'uploaded_by' => $_SESSION['user_id'] ?? null
                    ]);
                    
                    $uploadedFiles[] = $fileInfo;
                } else {
                    $errors[] = $file['name'] . ': Failed to upload';
                }
            }
            
            $this->sendResponse([
                'success' => true,
                'message' => count($uploadedFiles) . ' files uploaded successfully',
                'data' => [
                    'uploaded' => $uploadedFiles,
                    'errors' => $errors
                ]
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Multiple upload failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get file list
     */
    public function getFiles() {
        try {
            $category = $_GET['category'] ?? null;
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $search = $_GET['search'] ?? '';
            
            $files = $this->getFileList($category, $page, $limit, $search);
            
            $this->sendResponse([
                'success' => true,
                'data' => $files
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch files: ' . $e->getMessage());
        }
    }
    
    /**
     * Download file
     */
    public function download() {
        try {
            $fileId = $_GET['id'] ?? null;
            
            if (!$fileId) {
                $this->sendError('File ID required');
                return;
            }
            
            $fileInfo = $this->getFileById($fileId);
            
            if (!$fileInfo) {
                $this->sendError('File not found');
                return;
            }
            
            $filepath = $this->uploadDir . $fileInfo['filepath'];
            
            if (!file_exists($filepath)) {
                $this->sendError('File not found on disk');
                return;
            }
            
            // Update download count
            $this->updateDownloadCount($fileId);
            
            // Set headers for download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileInfo['original_name'] . '"');
            header('Content-Length: ' . filesize($filepath));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            
            // Output file
            readfile($filepath);
            exit;
            
        } catch (Exception $e) {
            $this->sendError('Download failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete file
     */
    public function delete() {
        try {
            $fileId = $_POST['id'] ?? null;
            
            if (!$fileId) {
                $this->sendError('File ID required');
                return;
            }
            
            $fileInfo = $this->getFileById($fileId);
            
            if (!$fileInfo) {
                $this->sendError('File not found');
                return;
            }
            
            $filepath = $this->uploadDir . $fileInfo['filepath'];
            
            // Delete file from disk
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            // Delete from database
            $this->deleteFileFromDB($fileId);
            
            $this->sendResponse([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Delete failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get file info
     */
    public function getFileInfo() {
        try {
            $fileId = $_GET['id'] ?? null;
            
            if (!$fileId) {
                $this->sendError('File ID required');
                return;
            }
            
            $fileInfo = $this->getFileById($fileId);
            
            if (!$fileInfo) {
                $this->sendError('File not found');
                return;
            }
            
            $this->sendResponse([
                'success' => true,
                'data' => $fileInfo
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to get file info: ' . $e->getMessage());
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
            return ['valid' => false, 'message' => 'File size exceeds maximum allowed size'];
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $isAllowed = false;
        
        foreach ($this->allowedTypes as $category => $extensions) {
            if (in_array($extension, $extensions)) {
                $isAllowed = true;
                break;
            }
        }
        
        if (!$isAllowed) {
            return ['valid' => false, 'message' => 'File type not allowed'];
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!$this->isValidMimeType($mimeType, $extension)) {
            return ['valid' => false, 'message' => 'Invalid file type'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Check if MIME type is valid for extension
     */
    private function isValidMimeType($mimeType, $extension) {
        $validMimes = [
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'webp' => ['image/webp'],
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            'txt' => ['text/plain'],
            'mp4' => ['video/mp4'],
            'avi' => ['video/x-msvideo'],
            'mov' => ['video/quicktime'],
            'mp3' => ['audio/mpeg'],
            'wav' => ['audio/wav']
        ];
        
        if (!isset($validMimes[$extension])) {
            return false;
        }
        
        return in_array($mimeType, $validMimes[$extension]);
    }
    
    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($error) {
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive';
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
     * Save file information to database
     */
    private function saveFileInfo($data) {
        // This would save to a files table in the database
        // For now, return the data with an ID
        $data['id'] = uniqid();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['download_count'] = 0;
        
        return $data;
    }
    
    /**
     * Get file list from database
     */
    private function getFileList($category, $page, $limit, $search) {
        // This would fetch from database
        // For now, return sample data
        return [
            'files' => [
                [
                    'id' => '1',
                    'original_name' => 'document.pdf',
                    'filename' => 'unique_document.pdf',
                    'category' => 'documents',
                    'size' => 1024000,
                    'type' => 'application/pdf',
                    'created_at' => date('Y-m-d H:i:s'),
                    'download_count' => 5
                ]
            ],
            'total' => 1,
            'page' => $page,
            'limit' => $limit
        ];
    }
    
    /**
     * Get file by ID from database
     */
    private function getFileById($id) {
        // This would fetch from database
        // For now, return sample data
        return [
            'id' => $id,
            'original_name' => 'document.pdf',
            'filename' => 'unique_document.pdf',
            'filepath' => 'documents/unique_document.pdf',
            'category' => 'documents',
            'size' => 1024000,
            'type' => 'application/pdf',
            'created_at' => date('Y-m-d H:i:s'),
            'download_count' => 5
        ];
    }
    
    /**
     * Update download count
     */
    private function updateDownloadCount($fileId) {
        // This would update the database
        // For now, just return true
        return true;
    }
    
    /**
     * Delete file from database
     */
    private function deleteFileFromDB($fileId) {
        // This would delete from database
        // For now, just return true
        return true;
    }
    
    /**
     * Send error response
     */
    private function sendError($message, $code = 400) {
        return $this->jsonError($message, $code);
    }
    
    /**
     * Send success response
     */
    private function sendResponse($data, $message = 'Success', $code = 200) {
        return $this->jsonSuccess($data, $message, $code);
    }
}
?>