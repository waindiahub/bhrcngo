<?php

class MemberController {
    private $db;
    private $emailService;
    private $uploadPath;
    
    public function __construct($database, $emailService) {
        $this->db = $database;
        $this->emailService = $emailService;
        $this->uploadPath = '../uploads/members/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }
    
    /**
     * Get all members with filtering and pagination
     */
    public function getMembers() {
        try {
            $category = $_GET['category'] ?? 'all';
            $search = $_GET['search'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 12);
            $offset = ($page - 1) * $limit;
            
            // Build query
            $whereClause = "WHERE status = 'active'";
            $params = [];
            
            if ($category !== 'all') {
                $whereClause .= " AND category = ?";
                $params[] = $category;
            }
            
            if (!empty($search)) {
                $whereClause .= " AND (name LIKE ? OR position LIKE ? OR specialization LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM members $whereClause";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute($params);
            $totalMembers = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get members
            $query = "SELECT * FROM members $whereClause ORDER BY display_order ASC, created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process member data
            foreach ($members as &$member) {
                $member['social'] = json_decode($member['social_links'], true) ?? [];
                unset($member['social_links']);
                
                // Ensure image path is correct
                if ($member['image'] && !str_starts_with($member['image'], 'http')) {
                    $member['image'] = '../uploads/members/' . $member['image'];
                }
            }
            
            $response = [
                'success' => true,
                'members' => $members,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($totalMembers / $limit),
                    'total_members' => $totalMembers,
                    'has_more' => ($page * $limit) < $totalMembers
                ]
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            $this->handleError('Error fetching members: ' . $e->getMessage());
        }
    }
    
    /**
     * Get member statistics
     */
    public function getMemberStats() {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total_members,
                    SUM(CASE WHEN category = 'board' THEN 1 ELSE 0 END) as board_members,
                    SUM(CASE WHEN category = 'volunteers' THEN 1 ELSE 0 END) as volunteers,
                    YEAR(CURDATE()) - MIN(YEAR(join_date)) as experience
                FROM members 
                WHERE status = 'active'
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Format stats
            $response = [
                'success' => true,
                'stats' => [
                    'totalMembers' => $stats['total_members'] . '+',
                    'boardMembers' => $stats['board_members'],
                    'volunteers' => $stats['volunteers'] . '+',
                    'experience' => $stats['experience'] . '+'
                ]
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            $this->handleError('Error fetching member statistics: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single member by ID
     */
    public function getMember($id) {
        try {
            $query = "SELECT * FROM members WHERE id = ? AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$member) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Member not found']);
                return;
            }
            
            // Process member data
            $member['social'] = json_decode($member['social_links'], true) ?? [];
            unset($member['social_links']);
            
            if ($member['image'] && !str_starts_with($member['image'], 'http')) {
                $member['image'] = '../uploads/members/' . $member['image'];
            }
            
            $response = [
                'success' => true,
                'member' => $member
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            $this->handleError('Error fetching member: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new member (Admin only)
     */
    public function createMember() {
        try {
            // Check admin authentication
            if (!$this->isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                return;
            }
            
            $data = $this->validateMemberData($_POST);
            if (!$data['valid']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $data['message']]);
                return;
            }
            
            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->handleImageUpload($_FILES['image']);
                if (!$imagePath) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
                    return;
                }
            }
            
            // Insert member
            $query = "
                INSERT INTO members (
                    name, position, category, bio, experience, specialization, 
                    education, email, phone, social_links, image, display_order, 
                    join_date, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
            ";
            
            $socialLinks = json_encode($data['social'] ?? []);
            $displayOrder = $this->getNextDisplayOrder($data['category']);
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['name'],
                $data['position'],
                $data['category'],
                $data['bio'],
                $data['experience'],
                $data['specialization'],
                $data['education'],
                $data['email'],
                $data['phone'],
                $socialLinks,
                $imagePath,
                $displayOrder,
                $data['join_date'] ?? date('Y-m-d')
            ]);
            
            $memberId = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity('member_created', $memberId, "Member '{$data['name']}' created");
            
            $response = [
                'success' => true,
                'message' => 'Member created successfully',
                'member_id' => $memberId
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            $this->handleError('Error creating member: ' . $e->getMessage());
        }
    }
    
    /**
     * Update member (Admin only)
     */
    public function updateMember($id) {
        try {
            // Check admin authentication
            if (!$this->isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                return;
            }
            
            // Check if member exists
            $existingMember = $this->getMemberById($id);
            if (!$existingMember) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Member not found']);
                return;
            }
            
            $data = $this->validateMemberData($_POST, false);
            if (!$data['valid']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $data['message']]);
                return;
            }
            
            // Handle image upload
            $imagePath = $existingMember['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $newImagePath = $this->handleImageUpload($_FILES['image']);
                if ($newImagePath) {
                    // Delete old image
                    if ($imagePath && file_exists($this->uploadPath . $imagePath)) {
                        unlink($this->uploadPath . $imagePath);
                    }
                    $imagePath = $newImagePath;
                }
            }
            
            // Update member
            $query = "
                UPDATE members SET 
                    name = ?, position = ?, category = ?, bio = ?, experience = ?, 
                    specialization = ?, education = ?, email = ?, phone = ?, 
                    social_links = ?, image = ?, updated_at = NOW()
                WHERE id = ?
            ";
            
            $socialLinks = json_encode($data['social'] ?? []);
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $data['name'],
                $data['position'],
                $data['category'],
                $data['bio'],
                $data['experience'],
                $data['specialization'],
                $data['education'],
                $data['email'],
                $data['phone'],
                $socialLinks,
                $imagePath,
                $id
            ]);
            
            // Log activity
            $this->logActivity('member_updated', $id, "Member '{$data['name']}' updated");
            
            $response = [
                'success' => true,
                'message' => 'Member updated successfully'
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            $this->handleError('Error updating member: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete member (Admin only)
     */
    public function deleteMember($id) {
        try {
            // Check admin authentication
            if (!$this->isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                return;
            }
            
            // Check if member exists
            $member = $this->getMemberById($id);
            if (!$member) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Member not found']);
                return;
            }
            
            // Soft delete
            $query = "UPDATE members SET status = 'deleted', updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            
            // Log activity
            $this->logActivity('member_deleted', $id, "Member '{$member['name']}' deleted");
            
            $response = [
                'success' => true,
                'message' => 'Member deleted successfully'
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            $this->handleError('Error deleting member: ' . $e->getMessage());
        }
    }
    
    /**
     * Update member display order (Admin only)
     */
    public function updateDisplayOrder() {
        try {
            // Check admin authentication
            if (!$this->isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $memberOrders = $input['member_orders'] ?? [];
            
            if (empty($memberOrders)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Member orders required']);
                return;
            }
            
            $this->db->beginTransaction();
            
            try {
                foreach ($memberOrders as $order) {
                    $query = "UPDATE members SET display_order = ? WHERE id = ?";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute([$order['order'], $order['id']]);
                }
                
                $this->db->commit();
                
                // Log activity
                $this->logActivity('member_order_updated', null, 'Member display order updated');
                
                $response = [
                    'success' => true,
                    'message' => 'Display order updated successfully'
                ];
                
                header('Content-Type: application/json');
                echo json_encode($response);
                
            } catch (Exception $e) {
                $this->db->rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            $this->handleError('Error updating display order: ' . $e->getMessage());
        }
    }
    
    /**
     * Subscribe to newsletter
     */
    public function subscribeNewsletter() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
            
            if (!$email) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Valid email address required']);
                return;
            }
            
            // Check if already subscribed
            $query = "SELECT id FROM newsletter_subscribers WHERE email = ? AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => true, 'message' => 'Already subscribed to newsletter']);
                return;
            }
            
            // Subscribe
            $query = "
                INSERT INTO newsletter_subscribers (email, status, subscribed_at) 
                VALUES (?, 'active', NOW())
                ON DUPLICATE KEY UPDATE status = 'active', subscribed_at = NOW()
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$email]);
            
            // Send welcome email
            $this->sendWelcomeEmail($email);
            
            $response = [
                'success' => true,
                'message' => 'Successfully subscribed to newsletter'
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            $this->handleError('Error subscribing to newsletter: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate member data
     */
    private function validateMemberData($data, $required = true) {
        $errors = [];
        
        if ($required || !empty($data['name'])) {
            if (empty($data['name']) || strlen($data['name']) < 2) {
                $errors[] = 'Name is required and must be at least 2 characters';
            }
        }
        
        if ($required || !empty($data['position'])) {
            if (empty($data['position'])) {
                $errors[] = 'Position is required';
            }
        }
        
        if ($required || !empty($data['category'])) {
            $validCategories = ['board', 'staff', 'volunteers', 'advisors'];
            if (empty($data['category']) || !in_array($data['category'], $validCategories)) {
                $errors[] = 'Valid category is required';
            }
        }
        
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid email address required';
            }
        }
        
        if (!empty($data['phone'])) {
            if (!preg_match('/^[\+]?[0-9\-\s\(\)]{10,15}$/', $data['phone'])) {
                $errors[] = 'Valid phone number required';
            }
        }
        
        return [
            'valid' => empty($errors),
            'message' => implode(', ', $errors),
            'name' => $data['name'] ?? '',
            'position' => $data['position'] ?? '',
            'category' => $data['category'] ?? '',
            'bio' => $data['bio'] ?? '',
            'experience' => $data['experience'] ?? '',
            'specialization' => $data['specialization'] ?? '',
            'education' => $data['education'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'social' => $this->parseSocialLinks($data),
            'join_date' => $data['join_date'] ?? date('Y-m-d')
        ];
    }
    
    /**
     * Parse social media links
     */
    private function parseSocialLinks($data) {
        $social = [];
        $platforms = ['facebook', 'twitter', 'linkedin', 'instagram'];
        
        foreach ($platforms as $platform) {
            if (!empty($data["social_{$platform}"])) {
                $social[$platform] = $data["social_{$platform}"];
            }
        }
        
        return $social;
    }
    
    /**
     * Handle image upload
     */
    private function handleImageUpload($file) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            return false;
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('member_') . '.' . $extension;
        $filepath = $this->uploadPath . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filename;
        }
        
        return false;
    }
    
    /**
     * Get member by ID (internal use)
     */
    private function getMemberById($id) {
        $query = "SELECT * FROM members WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get next display order for category
     */
    private function getNextDisplayOrder($category) {
        $query = "SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM members WHERE category = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$category]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['next_order'];
    }
    
    /**
     * Send welcome email to newsletter subscriber
     */
    private function sendWelcomeEmail($email) {
        try {
            $subject = 'Welcome to BHRC India Newsletter';
            $message = "
                <h2>Welcome to BHRC India Newsletter</h2>
                <p>Thank you for subscribing to our newsletter. You'll receive updates about our human rights activities and initiatives.</p>
                <p>Best regards,<br>BHRC India Team</p>
            ";
            
            $this->emailService->send($email, $subject, $message);
        } catch (Exception $e) {
            // Log error but don't fail the subscription
            error_log('Failed to send welcome email: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if user is admin
     */
    private function isAdmin() {
        // Implement your admin authentication logic here
        // This could check session, JWT token, etc.
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Log activity
     */
    private function logActivity($action, $memberId = null, $description = '') {
        try {
            $query = "
                INSERT INTO activity_logs (action, entity_type, entity_id, description, user_id, created_at) 
                VALUES (?, 'member', ?, ?, ?, NOW())
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $action,
                $memberId,
                $description,
                $_SESSION['user_id'] ?? null
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the main operation
            error_log('Failed to log activity: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle errors
     */
    private function handleError($message) {
        error_log($message);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while processing your request'
        ]);
    }
    
    /**
     * Get all members (API method)
     */
    public function getAll($params = []) {
        return $this->getMembers();
    }
    
    /**
     * Get member by ID (API method)
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM members WHERE id = ? AND status = 'active'");
            $stmt->execute([$id]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$member) {
                return ['success' => false, 'message' => 'Member not found'];
            }
            
            return ['success' => true, 'data' => $member];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to fetch member: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create new member (API method)
     */
    public function create($data) {
        return $this->createMembership($data);
    }
    
    /**
     * Add member (alias for create)
     */
    public function addMember($data) {
        return $this->createMembership($data);
    }
    
    /**
     * Create new membership application
     */
    public function createMembership($data) {
        try {
            // Generate unique membership ID
            $data['membership_id'] = $this->generateMembershipId();
            
            // Validate required fields
            $required = ['full_name', 'father_name', 'date_of_birth', 'gender', 
                        'address', 'city', 'state', 'pincode', 'phone', 'email', 
                        'occupation', 'qualification', 'aadhar_number', 'membership_type'];
            
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'];
                }
            }
            
            // Check if Aadhar number already exists
            $connection = $this->db->getConnection();
            $stmt = $connection->prepare("SELECT id FROM members WHERE aadhar_number = ?");
            $stmt->execute([$data['aadhar_number']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Aadhar number already registered'];
            }
            
            // Set default values
            $data['status'] = 'pending';
            $data['payment_status'] = 'pending';
            $data['certificate_issued'] = 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            
            // Insert member
            $fields = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $stmt = $connection->prepare("INSERT INTO members ($fields) VALUES ($placeholders)");
            
            if ($stmt->execute($data)) {
                $memberId = $connection->lastInsertId();
                return [
                    'success' => true, 
                    'message' => 'Membership application submitted successfully',
                    'member_id' => $memberId,
                    'membership_id' => $data['membership_id']
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create membership'];
            }
            
        } catch (Exception $e) {
             error_log("Create membership error: " . $e->getMessage());
             return ['success' => false, 'message' => 'Database error occurred'];
         }
     }
     
     /**
      * Generate unique membership ID
      */
     private function generateMembershipId() {
         $prefix = 'BHRC';
         $year = date('Y');
         
         // Get the last membership ID for this year
         $connection = $this->db->getConnection();
         $stmt = $connection->prepare("SELECT membership_id FROM members WHERE membership_id LIKE ? ORDER BY id DESC LIMIT 1");
         $stmt->execute(["{$prefix}{$year}%"]);
         $lastId = $stmt->fetch(PDO::FETCH_ASSOC);
         
         if ($lastId) {
             $lastNumber = intval(substr($lastId['membership_id'], -4));
             $newNumber = $lastNumber + 1;
         } else {
             $newNumber = 1;
         }
         
         return $prefix . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
     }
    
    /**
     * Update member (API method)
     */
    public function update($id, $data) {
        return $this->updateMember($id, $data);
    }
    
    /**
     * Delete member (API method)
     */
    public function delete($id) {
        return $this->deleteMember($id);
    }
    
    /**
     * Apply for membership (API method)
     */
    public function apply($data) {
        return [
            'success' => true,
            'message' => 'Application submitted successfully'
        ];
    }
}

// API Routes Handler
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $memberController = new MemberController($db, $emailService);
    
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'stats':
                $memberController->getMemberStats();
                break;
            case 'member':
                if (isset($_GET['id'])) {
                    $memberController->getMember($_GET['id']);
                } else {
                    $memberController->getMembers();
                }
                break;
            default:
                $memberController->getMembers();
                break;
        }
    } else {
        $memberController->getMembers();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberController = new MemberController($db, $emailService);
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $memberController->createMember();
                break;
            case 'newsletter':
                $memberController->subscribeNewsletter();
                break;
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
    } else {
        // Check if it's newsletter subscription based on content
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['email']) && !isset($input['name'])) {
            $memberController->subscribeNewsletter();
        } else {
            $memberController->createMember();
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $memberController = new MemberController($db, $emailService);
    
    if (isset($_GET['id'])) {
        $memberController->updateMember($_GET['id']);
    } elseif (isset($_GET['action']) && $_GET['action'] === 'order') {
        $memberController->updateDisplayOrder();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Member ID required']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $memberController = new MemberController($db, $emailService);
    
    if (isset($_GET['id'])) {
        $memberController->deleteMember($_GET['id']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Member ID required']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

?>