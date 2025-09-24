<?php
/**
 * System Controller
 * Handles system statistics, logs, and administrative operations
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Member.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Complaint.php';
require_once __DIR__ . '/../models/Donation.php';
require_once __DIR__ . '/BaseController.php';

class SystemController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get public statistics (no authentication required)
     */
    public function getPublicStatistics() {
        try {
            $userModel = new User();
            $memberModel = new Member();
            $eventModel = new Event();
            $complaintModel = new Complaint();
            $donationModel = new Donation();
            
            // Get basic public counts
            $stats = [
                'members' => [
                    'total' => $memberModel->getTotalCount(),
                    'approved' => $memberModel->getApprovedCount()
                ],
                'events' => [
                    'total' => $eventModel->getTotalCount(),
                    'upcoming' => $eventModel->getUpcomingCount(),
                    'completed' => $eventModel->getCompletedCount()
                ],
                'complaints' => [
                    'total' => $complaintModel->getTotalCount(),
                    'resolved' => $complaintModel->getResolvedCount()
                ],
                'donations' => [
                    'total_amount' => $donationModel->getTotalAmount(),
                    'total_count' => $donationModel->getTotalCount()
                ]
            ];
            
            return $this->sendResponse([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            return $this->sendResponse(['error' => 'Failed to fetch public statistics: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get system statistics
     */
    public function getStats() {
        try {
            $userModel = new User();
            $memberModel = new Member();
            $eventModel = new Event();
            $complaintModel = new Complaint();
            $donationModel = new Donation();
            
            // Get basic counts
            $stats = [
                'users' => [
                    'total' => $userModel->getTotalCount(),
                    'active' => $userModel->getActiveCount(),
                    'new_this_month' => $userModel->getNewThisMonth()
                ],
                'members' => [
                    'total' => $memberModel->getTotalCount(),
                    'approved' => $memberModel->getApprovedCount(),
                    'pending' => $memberModel->getPendingCount(),
                    'new_this_month' => $memberModel->getNewThisMonth()
                ],
                'events' => [
                    'total' => $eventModel->getTotalCount(),
                    'upcoming' => $eventModel->getUpcomingCount(),
                    'completed' => $eventModel->getCompletedCount(),
                    'this_month' => $eventModel->getThisMonthCount()
                ],
                'complaints' => [
                    'total' => $complaintModel->getTotalCount(),
                    'pending' => $complaintModel->getPendingCount(),
                    'resolved' => $complaintModel->getResolvedCount(),
                    'new_this_month' => $complaintModel->getNewThisMonth()
                ],
                'donations' => [
                    'total_amount' => $donationModel->getTotalAmount(),
                    'total_count' => $donationModel->getTotalCount(),
                    'this_month_amount' => $donationModel->getThisMonthAmount(),
                    'this_month_count' => $donationModel->getThisMonthCount()
                ]
            ];
            
            // Get system info
            $stats['system'] = [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'disk_usage' => $this->getDiskUsage(),
                'memory_usage' => $this->getMemoryUsage(),
                'uptime' => $this->getSystemUptime()
            ];
            
            // Get recent activity
            $stats['recent_activity'] = $this->getRecentActivity();
            
            $this->sendResponse([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch system statistics: ' . $e->getMessage());
        }
    }
    
    /**
     * Get system logs
     */
    public function getLogs() {
        try {
            $type = $_GET['type'] ?? 'all';
            $limit = (int)($_GET['limit'] ?? 100);
            $page = (int)($_GET['page'] ?? 1);
            
            $logFile = $this->getLogFile($type);
            
            if (!file_exists($logFile)) {
                $this->sendResponse([
                    'success' => true,
                    'data' => [],
                    'message' => 'Log file not found'
                ]);
                return;
            }
            
            $logs = $this->readLogFile($logFile, $limit, $page);
            
            $this->sendResponse([
                'success' => true,
                'data' => $logs
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch logs: ' . $e->getMessage());
        }
    }
    
    /**
     * Create system backup
     */
    public function createBackup() {
        try {
            $backupType = $_POST['type'] ?? 'full';
            $includeUploads = isset($_POST['include_uploads']) ? (bool)$_POST['include_uploads'] : true;
            
            $backupResult = $this->performBackup($backupType, $includeUploads);
            
            if ($backupResult['success']) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Backup created successfully',
                    'data' => $backupResult
                ]);
            } else {
                $this->sendError($backupResult['message']);
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to create backup: ' . $e->getMessage());
        }
    }
    
    /**
     * Get available backups
     */
    public function getBackups() {
        try {
            $backupDir = __DIR__ . '/../../backups';
            $backups = [];
            
            if (is_dir($backupDir)) {
                $files = scandir($backupDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                        $filePath = $backupDir . '/' . $file;
                        $backups[] = [
                            'filename' => $file,
                            'size' => filesize($filePath),
                            'created_at' => date('Y-m-d H:i:s', filemtime($filePath))
                        ];
                    }
                }
            }
            
            // Sort by creation date (newest first)
            usort($backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            $this->sendResponse([
                'success' => true,
                'data' => $backups
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch backups: ' . $e->getMessage());
        }
    }
    
    /**
     * Clear system cache
     */
    public function clearCache() {
        try {
            $cacheDir = __DIR__ . '/../../cache';
            $cleared = 0;
            
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                        $cleared++;
                    }
                }
            }
            
            $this->sendResponse([
                'success' => true,
                'message' => "Cache cleared successfully. {$cleared} files removed."
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to clear cache: ' . $e->getMessage());
        }
    }
    
    /**
     * Get disk usage information
     */
    private function getDiskUsage() {
        $bytes = disk_free_space('.');
        $total = disk_total_space('.');
        
        return [
            'free' => $this->formatBytes($bytes),
            'total' => $this->formatBytes($total),
            'used' => $this->formatBytes($total - $bytes),
            'percentage' => round((($total - $bytes) / $total) * 100, 2)
        ];
    }
    
    /**
     * Get memory usage information
     */
    private function getMemoryUsage() {
        return [
            'current' => $this->formatBytes(memory_get_usage()),
            'peak' => $this->formatBytes(memory_get_peak_usage()),
            'limit' => ini_get('memory_limit')
        ];
    }
    
    /**
     * Get system uptime (if available)
     */
    private function getSystemUptime() {
        if (function_exists('sys_getloadavg') && file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $uptime = explode(' ', $uptime);
            $seconds = (int)$uptime[0];
            
            $days = floor($seconds / 86400);
            $hours = floor(($seconds % 86400) / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            
            return "{$days}d {$hours}h {$minutes}m";
        }
        
        return 'Unknown';
    }
    
    /**
     * Get recent system activity
     */
    private function getRecentActivity() {
        // This would typically come from an activity log table
        // For now, return sample data
        return [
            [
                'type' => 'user_login',
                'description' => 'Admin user logged in',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes'))
            ],
            [
                'type' => 'member_approved',
                'description' => 'New member application approved',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-15 minutes'))
            ],
            [
                'type' => 'event_created',
                'description' => 'New event created',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
            ]
        ];
    }
    
    /**
     * Get log file path based on type
     */
    private function getLogFile($type) {
        $logDir = __DIR__ . '/../../logs';
        
        switch ($type) {
            case 'error':
                return $logDir . '/error.log';
            case 'access':
                return $logDir . '/access.log';
            case 'auth':
                return $logDir . '/auth.log';
            default:
                return $logDir . '/application.log';
        }
    }
    
    /**
     * Read log file with pagination
     */
    private function readLogFile($logFile, $limit, $page) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_reverse($lines); // Show newest first
        
        $offset = ($page - 1) * $limit;
        $pageLines = array_slice($lines, $offset, $limit);
        
        $logs = [];
        foreach ($pageLines as $line) {
            $logs[] = [
                'timestamp' => $this->extractTimestamp($line),
                'level' => $this->extractLogLevel($line),
                'message' => $line
            ];
        }
        
        return $logs;
    }
    
    /**
     * Extract timestamp from log line
     */
    private function extractTimestamp($line) {
        if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
            return $matches[1];
        }
        return date('Y-m-d H:i:s');
    }
    
    /**
     * Extract log level from log line
     */
    private function extractLogLevel($line) {
        if (preg_match('/\[(ERROR|WARNING|INFO|DEBUG)\]/', $line, $matches)) {
            return strtolower($matches[1]);
        }
        return 'info';
    }
    
    /**
     * Perform system backup
     */
    private function performBackup($type, $includeUploads) {
        $backupDir = __DIR__ . '/../../backups';
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . "/backup_{$type}_{$timestamp}.zip";
        
        $zip = new ZipArchive();
        if ($zip->open($backupFile, ZipArchive::CREATE) !== TRUE) {
            return ['success' => false, 'message' => 'Cannot create backup file'];
        }
        
        try {
            // Backup database
            $this->backupDatabase($zip);
            
            // Backup configuration files
            $this->backupConfigFiles($zip);
            
            // Backup uploads if requested
            if ($includeUploads) {
                $this->backupUploads($zip);
            }
            
            $zip->close();
            
            return [
                'success' => true,
                'filename' => basename($backupFile),
                'size' => filesize($backupFile),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            $zip->close();
            if (file_exists($backupFile)) {
                unlink($backupFile);
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Backup database to zip
     */
    private function backupDatabase($zip) {
        // This would implement database backup logic
        // For now, create a placeholder
        $zip->addFromString('database/backup.sql', '-- Database backup placeholder');
    }
    
    /**
     * Backup configuration files to zip
     */
    private function backupConfigFiles($zip) {
        $configDir = __DIR__ . '/../../config';
        if (is_dir($configDir)) {
            $this->addDirectoryToZip($zip, $configDir, 'config/');
        }
    }
    
    /**
     * Backup uploads to zip
     */
    private function backupUploads($zip) {
        $uploadsDir = __DIR__ . '/../../uploads';
        if (is_dir($uploadsDir)) {
            $this->addDirectoryToZip($zip, $uploadsDir, 'uploads/');
        }
    }
    
    /**
     * Add directory to zip recursively
     */
    private function addDirectoryToZip($zip, $dir, $zipPath) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipPath . substr($filePath, strlen($dir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Send success response
     */
    private function sendResponse($data, $message = 'Success', $code = 200) {
        return $this->jsonSuccess($data, $message, $code);
    }
}
?>