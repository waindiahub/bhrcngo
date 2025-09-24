<?php
/**
 * Gallery Integration API
 * Provides integration between existing gallery features and new member/admin interfaces
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/GalleryController.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    $galleryController = new GalleryController();
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'public-gallery':
                        // Public gallery for member dashboard
                        $gallery = getPublicGallery();
                        echo json_encode($gallery);
                        break;
                        
                    case 'admin-stats':
                        // Gallery statistics for admin dashboard
                        $auth = new AuthMiddleware();
                        $user = $auth->authenticate();
                        
                        if (!$user || $user['role'] !== 'admin') {
                            http_response_code(401);
                            echo json_encode(['error' => 'Unauthorized']);
                            exit();
                        }
                        
                        $stats = getGalleryStats();
                        echo json_encode($stats);
                        break;
                        
                    case 'recent-uploads':
                        // Recent uploads for admin dashboard
                        $auth = new AuthMiddleware();
                        $user = $auth->authenticate();
                        
                        if (!$user || $user['role'] !== 'admin') {
                            http_response_code(401);
                            echo json_encode(['error' => 'Unauthorized']);
                            exit();
                        }
                        
                        $recent = getRecentUploads();
                        echo json_encode($recent);
                        break;
                        
                    case 'albums':
                        // Get all albums
                        $albums = getAlbums();
                        echo json_encode($albums);
                        break;
                        
                    case 'album-items':
                        // Get items from specific album
                        $albumId = $_GET['album_id'] ?? null;
                        if (!$albumId) {
                            http_response_code(400);
                            echo json_encode(['error' => 'Album ID required']);
                            exit();
                        }
                        
                        $items = getAlbumItems($albumId);
                        echo json_encode($items);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid action']);
                }
            } else {
                // Return general gallery data
                $galleryData = [
                    'albums' => getAlbums(),
                    'recent_items' => getRecentItems(),
                    'stats' => getGalleryStats()
                ];
                echo json_encode($galleryData);
            }
            break;
            
        case 'POST':
            // Handle file uploads and album creation
            $auth = new AuthMiddleware();
            $user = $auth->authenticate();
            
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                exit();
            }
            
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'create-album':
                        $result = createAlbum($_POST, $user);
                        echo json_encode($result);
                        break;
                        
                    case 'upload-photo':
                        $result = uploadPhoto($_FILES, $_POST, $user);
                        echo json_encode($result);
                        break;
                        
                    case 'upload-video':
                        $result = uploadVideo($_FILES, $_POST, $user);
                        echo json_encode($result);
                        break;
                        
                    default:
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid action']);
                }
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Action required']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Get public gallery items for member dashboard
 */
function getPublicGallery() {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->query("SELECT gi.*, ga.name as album_name 
                           FROM gallery_items gi 
                           LEFT JOIN gallery_albums ga ON gi.album_id = ga.id 
                           WHERE gi.status = 'published' 
                           ORDER BY gi.created_at DESC 
                           LIMIT 12");
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as &$item) {
            $item['thumbnail_url'] = '/uploads/gallery/thumbnails/' . $item['filename'];
            $item['full_url'] = '/uploads/gallery/' . $item['filename'];
        }
        
        return $items;
        
    } catch (Exception $e) {
        error_log("Error getting public gallery: " . $e->getMessage());
        return [];
    }
}

/**
 * Get gallery statistics for admin dashboard
 */
function getGalleryStats() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Total items
        $stmt = $db->query("SELECT 
                           COUNT(*) as total,
                           SUM(CASE WHEN type = 'photo' THEN 1 ELSE 0 END) as photos,
                           SUM(CASE WHEN type = 'video' THEN 1 ELSE 0 END) as videos,
                           SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as recent
                           FROM gallery_items");
        $items = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Albums count
        $stmt = $db->query("SELECT COUNT(*) as total FROM gallery_albums");
        $albums = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Storage usage (placeholder - would need actual file size calculation)
        $storageUsed = 1.2; // GB
        $storageLimit = 10; // GB
        
        return [
            'total_items' => (int)$items['total'],
            'photos' => (int)$items['photos'],
            'videos' => (int)$items['videos'],
            'albums' => (int)$albums['total'],
            'recent_uploads' => (int)$items['recent'],
            'storage' => [
                'used' => $storageUsed,
                'limit' => $storageLimit,
                'percentage' => round(($storageUsed / $storageLimit) * 100, 1)
            ]
        ];
        
    } catch (Exception $e) {
        error_log("Error getting gallery stats: " . $e->getMessage());
        return [
            'total_items' => 0,
            'photos' => 0,
            'videos' => 0,
            'albums' => 0,
            'recent_uploads' => 0,
            'storage' => ['used' => 0, 'limit' => 10, 'percentage' => 0]
        ];
    }
}

/**
 * Get recent uploads for admin dashboard
 */
function getRecentUploads() {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->query("SELECT gi.*, ga.name as album_name, u.username as uploaded_by
                           FROM gallery_items gi 
                           LEFT JOIN gallery_albums ga ON gi.album_id = ga.id
                           LEFT JOIN users u ON gi.uploaded_by = u.id
                           ORDER BY gi.created_at DESC 
                           LIMIT 10");
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as &$item) {
            $item['thumbnail_url'] = '/uploads/gallery/thumbnails/' . $item['filename'];
            $item['size_formatted'] = formatFileSize($item['file_size'] ?? 0);
        }
        
        return $items;
        
    } catch (Exception $e) {
        error_log("Error getting recent uploads: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all albums
 */
function getAlbums() {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->query("SELECT ga.*, 
                           COUNT(gi.id) as item_count,
                           MAX(gi.created_at) as last_updated
                           FROM gallery_albums ga 
                           LEFT JOIN gallery_items gi ON ga.id = gi.album_id
                           GROUP BY ga.id
                           ORDER BY ga.created_at DESC");
        
        $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($albums as &$album) {
            $album['item_count'] = (int)$album['item_count'];
            
            // Get cover image
            $stmt = $db->prepare("SELECT filename FROM gallery_items 
                                 WHERE album_id = ? AND type = 'photo' 
                                 ORDER BY created_at ASC LIMIT 1");
            $stmt->execute([$album['id']]);
            $cover = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $album['cover_image'] = $cover ? '/uploads/gallery/thumbnails/' . $cover['filename'] : null;
        }
        
        return $albums;
        
    } catch (Exception $e) {
        error_log("Error getting albums: " . $e->getMessage());
        return [];
    }
}

/**
 * Get items from specific album
 */
function getAlbumItems($albumId) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM gallery_items 
                             WHERE album_id = ? 
                             ORDER BY created_at DESC");
        $stmt->execute([$albumId]);
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as &$item) {
            $item['thumbnail_url'] = '/uploads/gallery/thumbnails/' . $item['filename'];
            $item['full_url'] = '/uploads/gallery/' . $item['filename'];
            $item['size_formatted'] = formatFileSize($item['file_size'] ?? 0);
        }
        
        return $items;
        
    } catch (Exception $e) {
        error_log("Error getting album items: " . $e->getMessage());
        return [];
    }
}

/**
 * Get recent items for general gallery display
 */
function getRecentItems() {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->query("SELECT gi.*, ga.name as album_name 
                           FROM gallery_items gi 
                           LEFT JOIN gallery_albums ga ON gi.album_id = ga.id 
                           WHERE gi.status = 'published' 
                           ORDER BY gi.created_at DESC 
                           LIMIT 8");
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($items as &$item) {
            $item['thumbnail_url'] = '/uploads/gallery/thumbnails/' . $item['filename'];
            $item['full_url'] = '/uploads/gallery/' . $item['filename'];
        }
        
        return $items;
        
    } catch (Exception $e) {
        error_log("Error getting recent items: " . $e->getMessage());
        return [];
    }
}

/**
 * Create new album
 */
function createAlbum($data, $user) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("INSERT INTO gallery_albums (name, description, created_by, created_at) 
                             VALUES (?, ?, ?, NOW())");
        
        $result = $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $user['id']
        ]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Album created successfully',
                'album_id' => $db->lastInsertId()
            ];
        } else {
            throw new Exception('Failed to create album');
        }
        
    } catch (Exception $e) {
        error_log("Error creating album: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to create album: ' . $e->getMessage()
        ];
    }
}

/**
 * Upload photo
 */
function uploadPhoto($files, $data, $user) {
    try {
        if (!isset($files['photo']) || $files['photo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }
        
        $file = $files['photo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.');
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        
        // Create upload directories if they don't exist
        $uploadDir = __DIR__ . '/../../uploads/gallery/';
        $thumbnailDir = __DIR__ . '/../../uploads/gallery/thumbnails/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }
        
        // Move uploaded file
        $uploadPath = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        // Create thumbnail (simplified - would use image processing library in production)
        $thumbnailPath = $thumbnailDir . $filename;
        copy($uploadPath, $thumbnailPath); // Placeholder - implement actual thumbnail generation
        
        // Save to database
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO gallery_items 
                             (filename, original_name, type, album_id, title, description, file_size, uploaded_by, created_at, status) 
                             VALUES (?, ?, 'photo', ?, ?, ?, ?, ?, NOW(), 'published')");
        
        $result = $stmt->execute([
            $filename,
            $file['name'],
            $data['album_id'] ?? null,
            $data['title'] ?? '',
            $data['description'] ?? '',
            $file['size'],
            $user['id']
        ]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Photo uploaded successfully',
                'item_id' => $db->lastInsertId(),
                'filename' => $filename
            ];
        } else {
            throw new Exception('Failed to save to database');
        }
        
    } catch (Exception $e) {
        error_log("Error uploading photo: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to upload photo: ' . $e->getMessage()
        ];
    }
}

/**
 * Upload video
 */
function uploadVideo($files, $data, $user) {
    try {
        if (!isset($files['video']) || $files['video']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error');
        }
        
        $file = $files['video'];
        $allowedTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Invalid file type. Only MP4, AVI, MOV, and WMV are allowed.');
        }
        
        // Check file size (limit to 100MB)
        if ($file['size'] > 100 * 1024 * 1024) {
            throw new Exception('File too large. Maximum size is 100MB.');
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        
        // Create upload directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../uploads/gallery/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Move uploaded file
        $uploadPath = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        // Save to database
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO gallery_items 
                             (filename, original_name, type, album_id, title, description, file_size, uploaded_by, created_at, status) 
                             VALUES (?, ?, 'video', ?, ?, ?, ?, ?, NOW(), 'published')");
        
        $result = $stmt->execute([
            $filename,
            $file['name'],
            $data['album_id'] ?? null,
            $data['title'] ?? '',
            $data['description'] ?? '',
            $file['size'],
            $user['id']
        ]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Video uploaded successfully',
                'item_id' => $db->lastInsertId(),
                'filename' => $filename
            ];
        } else {
            throw new Exception('Failed to save to database');
        }
        
    } catch (Exception $e) {
        error_log("Error uploading video: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to upload video: ' . $e->getMessage()
        ];
    }
}

/**
 * Format file size for display
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}
?>