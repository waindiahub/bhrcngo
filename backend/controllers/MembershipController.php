<?php
/**
 * Membership Controller
 * Handles membership applications, management, and related operations
 */

// Define access constant
define('BHRC_ACCESS', true);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../utils/FileUpload.php';
require_once __DIR__ . '/../services/EmailService.php';

class MembershipController {
    private $db;
    private $fileUpload;
    private $emailService;
    
    public function __construct() {
        try {
            // For development, use simple database connection
            $this->db = new PDO(
                "mysql:host=localhost;dbname=bhrc_database;charset=utf8mb4",
                "root",
                "",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            // Initialize other services with mock implementations
            $this->fileUpload = $this->createMockFileUpload();
            $this->emailService = $this->createMockEmailService();
        } catch (Exception $e) {
            // For demo purposes, create mock services
            $this->db = $this->createMockDatabase();
            $this->fileUpload = $this->createMockFileUpload();
            $this->emailService = $this->createMockEmailService();
        }
    }
    
    private function createMockDatabase() {
        return new class {
            public function query($sql) {
                return new class {
                    public function execute($params = []) {
                        return true;
                    }
                    public function fetch() {
                        return false;
                    }
                    public function fetchAll() {
                        return [];
                    }
                };
            }
            public function getConnection() {
                return $this;
            }
            public function lastInsertId() {
                return rand(1000, 9999);
            }
            public function prepare($sql) {
                return $this->query($sql);
            }
        };
    }
    
    private function createMockFileUpload() {
        return new class {
            public function upload($file, $config) {
                return [
                    'success' => true,
                    'filename' => 'mock_' . time() . '_' . $file['name'],
                    'path' => $config['upload_path'] . 'mock_file.jpg',
                    'size' => $file['size'] ?? 1024
                ];
            }
        };
    }
    
    private function createMockEmailService() {
        return new class {
            public function sendEmail($to, $subject, $body, $attachments = []) {
                return [
                    'success' => true,
                    'message' => 'Mock email sent successfully'
                ];
            }
        };
    }
    
    /**
     * Handle incoming requests
     */
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        // Set JSON response header
        header('Content-Type: application/json');
        
        try {
            switch ($action) {
                case 'submit_membership':
                    if ($method === 'POST') {
                        $this->submitMembership();
                    } else {
                        throw new Exception('Invalid request method');
                    }
                    break;
                    
                case 'get_memberships':
                    if ($method === 'GET') {
                        $this->getMemberships();
                    } else {
                        throw new Exception('Invalid request method');
                    }
                    break;
                    
                case 'get_membership':
                    if ($method === 'GET') {
                        $this->getMembership();
                    } else {
                        throw new Exception('Invalid request method');
                    }
                    break;
                    
                case 'update_membership_status':
                    if ($method === 'POST') {
                        $this->updateMembershipStatus();
                    } else {
                        throw new Exception('Invalid request method');
                    }
                    break;
                    
                case 'delete_membership':
                    if ($method === 'POST') {
                        $this->deleteMembership();
                    } else {
                        throw new Exception('Invalid request method');
                    }
                    break;
                    
                case 'get_membership_stats':
                    if ($method === 'GET') {
                        $this->getMembershipStats();
                    } else {
                        throw new Exception('Invalid request method');
                    }
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
        } catch (Exception $e) {
            $this->sendErrorResponse($e->getMessage());
        }
    }
    
    /**
     * Submit new membership application
     */
    private function submitMembership() {
        try {
            // For demo purposes, return a success response without database operations
            $this->sendSuccessResponse([
                'message' => 'Membership application submitted successfully! This is a demo response.',
                'membership_id' => 'BHRC' . date('Y') . rand(1000, 9999),
                'application_id' => rand(100, 999)
            ]);
            
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Handle file uploads
     */
    private function handleFileUploads() {
        $uploadedFiles = [];
        
        // Required files
        $requiredFiles = ['photo', 'id_proof'];
        foreach ($requiredFiles as $fileKey) {
            if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File '{$fileKey}' is required");
            }
        }
        
        // Upload photo
        if (isset($_FILES['photo'])) {
            $photoConfig = [
                'allowed_types' => ['image/jpeg', 'image/png', 'image/jpg'],
                'max_size' => 2 * 1024 * 1024, // 2MB
                'upload_path' => '../../uploads/memberships/photos/',
                'resize' => ['width' => 300, 'height' => 400]
            ];
            
            $uploadedFiles['photo'] = $this->fileUpload->upload($_FILES['photo'], $photoConfig);
        }
        
        // Upload ID proof
        if (isset($_FILES['id_proof'])) {
            $idProofConfig = [
                'allowed_types' => ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'],
                'max_size' => 5 * 1024 * 1024, // 5MB
                'upload_path' => '../../uploads/memberships/documents/'
            ];
            
            $uploadedFiles['id_proof'] = $this->fileUpload->upload($_FILES['id_proof'], $idProofConfig);
        }
        
        // Upload resume (optional)
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $resumeConfig = [
                'allowed_types' => [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ],
                'max_size' => 5 * 1024 * 1024, // 5MB
                'upload_path' => '../../uploads/memberships/resumes/'
            ];
            
            $uploadedFiles['resume'] = $this->fileUpload->upload($_FILES['resume'], $resumeConfig);
        }
        
        return $uploadedFiles;
    }
    
    /**
     * Insert membership application into database
     */
    private function insertMembership($data) {
        $sql = "INSERT INTO memberships (
            membership_id, first_name, last_name, email, phone, date_of_birth,
            gender, address, city, state, pincode, country, occupation,
            organization, designation, experience, education, specialization,
            interests, membership_type, motivation, experience_hr, referral,
            newsletter, photo_path, id_proof_path, resume_path, application_date,
            status, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )";
        
        $params = [
            $data['membership_id'], $data['first_name'], $data['last_name'],
            $data['email'], $data['phone'], $data['date_of_birth'],
            $data['gender'], $data['address'], $data['city'], $data['state'],
            $data['pincode'], $data['country'], $data['occupation'],
            $data['organization'], $data['designation'], $data['experience'],
            $data['education'], $data['specialization'], $data['interests'],
            $data['membership_type'], $data['motivation'], $data['experience_hr'],
            $data['referral'], $data['newsletter'], $data['photo_path'],
            $data['id_proof_path'], $data['resume_path'], $data['application_date'],
            $data['status']
        ];
        
        $this->db->query($sql, $params);
        return $this->db->getConnection()->lastInsertId();
    }
    
    /**
     * Generate unique membership ID
     */
    private function generateMembershipId() {
        $prefix = 'BHRC';
        $year = date('Y');
        
        // Get the last membership ID for this year
        $lastId = $this->db->query(
            "SELECT membership_id FROM memberships WHERE membership_id LIKE ? ORDER BY id DESC LIMIT 1",
            ["{$prefix}{$year}%"]
        )->fetch();
        
        if ($lastId) {
            $lastNumber = intval(substr($lastId['membership_id'], -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Send application confirmation email
     */
    private function sendApplicationConfirmation($membershipData) {
        $subject = 'Membership Application Received - BHRC India';
        $memberName = $membershipData['first_name'] . ' ' . $membershipData['last_name'];
        
        $message = "
        <h2>Dear {$memberName},</h2>
        
        <p>Thank you for your interest in joining BHRC India. We have received your membership application.</p>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3>Application Details:</h3>
            <p><strong>Membership ID:</strong> {$membershipData['membership_id']}</p>
            <p><strong>Membership Type:</strong> " . ucfirst(str_replace('_', ' ', $membershipData['membership_type'])) . "</p>
            <p><strong>Application Date:</strong> " . date('F j, Y', strtotime($membershipData['application_date'])) . "</p>
            <p><strong>Status:</strong> Under Review</p>
        </div>
        
        <h3>What's Next?</h3>
        <ul>
            <li>Our team will review your application within 7-10 business days</li>
            <li>You will receive an email notification once the review is complete</li>
            <li>If approved, you will receive your membership certificate and ID card</li>
        </ul>
        
        <p>If you have any questions, please contact us at membership@bhrcindia.in or call +91-9876543210.</p>
        
        <p>Thank you for your commitment to human rights!</p>
        
        <p>Best regards,<br>
        BHRC India Team</p>
        ";
        
        $this->emailService->sendEmail($membershipData['email'], $subject, $message);
    }
    
    /**
     * Send admin notification
     */
    private function sendAdminNotification($membershipData, $membershipId) {
        $subject = 'New Membership Application - BHRC India';
        $memberName = $membershipData['first_name'] . ' ' . $membershipData['last_name'];
        
        $message = "
        <h2>New Membership Application Received</h2>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3>Applicant Details:</h3>
            <p><strong>Name:</strong> {$memberName}</p>
            <p><strong>Email:</strong> {$membershipData['email']}</p>
            <p><strong>Phone:</strong> {$membershipData['phone']}</p>
            <p><strong>Membership Type:</strong> " . ucfirst(str_replace('_', ' ', $membershipData['membership_type'])) . "</p>
            <p><strong>Location:</strong> {$membershipData['city']}, {$membershipData['state']}</p>
            <p><strong>Occupation:</strong> {$membershipData['occupation']}</p>
        </div>
        
        <p><strong>Motivation:</strong></p>
        <p style='background: #fff; padding: 15px; border-left: 4px solid #667eea;'>{$membershipData['motivation']}</p>
        
        <p>Please review the application in the admin panel.</p>
        
        <p><a href='" . $_SERVER['HTTP_HOST'] . "/auth/admin/applications.html?id={$membershipId}' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Review Application</a></p>
        ";
        
        $adminEmail = 'admin@bhrcindia.in'; // Configure this
        $this->emailService->sendEmail($adminEmail, $subject, $message);
    }
    
    /**
     * Get memberships with filtering and pagination
     */
    private function getMemberships() {
        $page = intval($_GET['page'] ?? 1);
        $limit = intval($_GET['limit'] ?? 20);
        $status = $_GET['status'] ?? '';
        $type = $_GET['type'] ?? '';
        $search = $_GET['search'] ?? '';
        
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause
        $whereConditions = [];
        $params = [];
        
        if ($status) {
            $whereConditions[] = "status = ?";
            $params[] = $status;
        }
        
        if ($type) {
            $whereConditions[] = "membership_type = ?";
            $params[] = $type;
        }
        
        if ($search) {
            $whereConditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR membership_id LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        $whereClause = $whereConditions ? "WHERE " . implode(" AND ", $whereConditions) : "";
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM memberships {$whereClause}";
        $totalResult = $this->db->query($countSql, $params)->fetch();
        $total = $totalResult['total'];
        
        // Get memberships
        $sql = "SELECT 
                    id, membership_id, first_name, last_name, email, phone,
                    membership_type, status, application_date, city, state,
                    created_at, updated_at
                FROM memberships 
                {$whereClause}
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $memberships = $this->db->query($sql, $params)->fetchAll();
        
        $this->sendSuccessResponse([
            'memberships' => $memberships,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'per_page' => $limit
            ]
        ]);
    }
    
    /**
     * Get single membership details
     */
    private function getMembership() {
        $id = $_GET['id'] ?? '';
        
        if (!$id) {
            throw new Exception('Membership ID is required');
        }
        
        $sql = "SELECT * FROM memberships WHERE id = ? OR membership_id = ?";
        $membership = $this->db->query($sql, [$id, $id])->fetch();
        
        if (!$membership) {
            throw new Exception('Membership not found');
        }
        
        // Decode interests JSON
        if ($membership['interests']) {
            $membership['interests'] = json_decode($membership['interests'], true);
        }
        
        $this->sendSuccessResponse(['membership' => $membership]);
    }
    
    /**
     * Update membership status
     */
    private function updateMembershipStatus() {
        $this->requireAdmin();
        
        $id = $_POST['id'] ?? '';
        $status = $_POST['status'] ?? '';
        $remarks = $_POST['remarks'] ?? '';
        
        if (!$id || !$status) {
            throw new Exception('ID and status are required');
        }
        
        $validStatuses = ['pending', 'approved', 'rejected', 'suspended'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception('Invalid status');
        }
        
        // Get membership details
        $membership = $this->db->query(
            "SELECT * FROM memberships WHERE id = ?",
            [$id]
        )->fetch();
        
        if (!$membership) {
            throw new Exception('Membership not found');
        }
        
        // Update status
        $sql = "UPDATE memberships SET status = ?, remarks = ?, updated_at = NOW() WHERE id = ?";
        $this->db->query($sql, [$status, $remarks, $id]);
        
        // Send status update email
        $this->sendStatusUpdateEmail($membership, $status, $remarks);
        
        $this->sendSuccessResponse(['message' => 'Membership status updated successfully']);
    }
    
    /**
     * Send status update email
     */
    private function sendStatusUpdateEmail($membership, $status, $remarks) {
        $memberName = $membership['first_name'] . ' ' . $membership['last_name'];
        $subject = "Membership Application Update - BHRC India";
        
        $statusMessages = [
            'approved' => 'Congratulations! Your membership application has been approved.',
            'rejected' => 'We regret to inform you that your membership application has been rejected.',
            'suspended' => 'Your membership has been temporarily suspended.'
        ];
        
        $message = "
        <h2>Dear {$memberName},</h2>
        
        <p>{$statusMessages[$status]}</p>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3>Application Details:</h3>
            <p><strong>Membership ID:</strong> {$membership['membership_id']}</p>
            <p><strong>Status:</strong> " . ucfirst($status) . "</p>
            <p><strong>Updated Date:</strong> " . date('F j, Y') . "</p>
        </div>";
        
        if ($remarks) {
            $message .= "<p><strong>Remarks:</strong></p>
                        <p style='background: #fff; padding: 15px; border-left: 4px solid #667eea;'>{$remarks}</p>";
        }
        
        if ($status === 'approved') {
            $message .= "
            <h3>Next Steps:</h3>
            <ul>
                <li>Your membership certificate will be sent to your registered address</li>
                <li>You will receive your membership ID card within 7-10 business days</li>
                <li>You can now access member-only resources and events</li>
            </ul>";
        }
        
        $message .= "
        <p>If you have any questions, please contact us at membership@bhrcindia.in or call +91-9876543210.</p>
        
        <p>Best regards,<br>
        BHRC India Team</p>";
        
        $this->emailService->sendEmail($membership['email'], $subject, $message);
    }
    
    /**
     * Delete membership
     */
    private function deleteMembership() {
        $this->requireAdmin();
        
        $id = $_POST['id'] ?? '';
        
        if (!$id) {
            throw new Exception('Membership ID is required');
        }
        
        // Get membership details for file cleanup
        $membership = $this->db->query(
            "SELECT photo_path, id_proof_path, resume_path FROM memberships WHERE id = ?",
            [$id]
        )->fetch();
        
        if (!$membership) {
            throw new Exception('Membership not found');
        }
        
        // Delete files
        $filePaths = [$membership['photo_path'], $membership['id_proof_path'], $membership['resume_path']];
        foreach ($filePaths as $filePath) {
            if ($filePath && file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Delete from database
        $this->db->query("DELETE FROM memberships WHERE id = ?", [$id]);
        
        $this->sendSuccessResponse(['message' => 'Membership deleted successfully']);
    }
    
    /**
     * Get membership statistics
     */
    private function getMembershipStats() {
        $stats = [];
        
        // Total memberships by status
        $statusStats = $this->db->query(
            "SELECT status, COUNT(*) as count FROM memberships GROUP BY status"
        )->fetchAll();
        
        foreach ($statusStats as $stat) {
            $stats['by_status'][$stat['status']] = intval($stat['count']);
        }
        
        // Total memberships by type
        $typeStats = $this->db->query(
            "SELECT membership_type, COUNT(*) as count FROM memberships GROUP BY membership_type"
        )->fetchAll();
        
        foreach ($typeStats as $stat) {
            $stats['by_type'][$stat['membership_type']] = intval($stat['count']);
        }
        
        // Monthly registrations (last 12 months)
        $monthlyStats = $this->db->query(
            "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as count
            FROM memberships 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month"
        )->fetchAll();
        
        $stats['monthly_registrations'] = $monthlyStats;
        
        // Total counts
        $totalResult = $this->db->query("SELECT COUNT(*) as total FROM memberships")->fetch();
        $stats['total_memberships'] = intval($totalResult['total']);
        
        $this->sendSuccessResponse(['stats' => $stats]);
    }
    
    /**
     * Utility methods
     */
    private function sanitizeInput($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    private function requireAdmin() {
        // Implement admin authentication check
        session_start();
        if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
            throw new Exception('Admin access required');
        }
    }
    
    private function sendSuccessResponse($data) {
        echo json_encode(['success' => true] + $data);
        exit;
    }
    
    private function sendErrorResponse($message) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $controller = new MembershipController();
    $controller->handleRequest();
}
?>