<?php
/**
 * Complaint Controller
 * Handles complaint submission, management, and CRUD operations
 */

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../utils/FileUpload.php';
require_once __DIR__ . '/../services/EmailService.php';

class ComplaintController extends BaseController {
    private $complaintModel;
    private $fileUpload;
    private $emailService;
    
    public function __construct() {
        parent::__construct();
        $this->complaintModel = new BaseModel('complaints');
        $this->fileUpload = new FileUpload();
        $this->emailService = new EmailService();
    }
    
    /**
     * Get all complaints
     */
    public function getAll($params = []) {
        return $this->getAllComplaints();
    }
    
    /**
     * Get complaint by ID
     */
    public function getById($id) {
        try {
            $complaint = $this->complaintModel->findById($id);
            if (!$complaint) {
                return $this->sendError('Complaint not found', 404);
            }
            
            return $this->sendResponse([
                'success' => true,
                'data' => $complaint
            ]);
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch complaint: ' . $e->getMessage());
        }
    }
    
    /**
     * Get complaint status
     */
    public function getStatus($id) {
        try {
            $complaint = $this->complaintModel->findById($id);
            if (!$complaint) {
                return $this->sendError('Complaint not found', 404);
            }
            
            return $this->sendResponse([
                'success' => true,
                'data' => [
                    'id' => $complaint['id'],
                    'status' => $complaint['status'],
                    'updated_at' => $complaint['updated_at']
                ]
            ]);
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch complaint status: ' . $e->getMessage());
        }
    }
    
    /**
     * Get public complaints
     */
    public function getPublic($params = []) {
        try {
            $complaints = $this->complaintModel->getPublicComplaints($params);
            return $this->sendResponse([
                'success' => true,
                'data' => $complaints
            ]);
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch public complaints: ' . $e->getMessage());
        }
    }
    
    /**
     * Submit complaint
     */
    public function submit($data) {
        return $this->submitComplaint();
    }
    
    /**
     * Create complaint
     */
    public function create($data) {
        return $this->submitComplaint();
    }
    
    /**
     * Update complaint status
     */
    public function updateStatus($id, $data) {
        return $this->updateComplaintStatus();
    }
    
    /**
     * Update complaint
     */
    public function update($id, $data) {
        try {
            $result = $this->complaintModel->update($id, $data);
            if ($result) {
                return $this->sendResponse([
                    'success' => true,
                    'message' => 'Complaint updated successfully'
                ]);
            } else {
                return $this->sendError('Failed to update complaint');
            }
        } catch (Exception $e) {
            return $this->sendError('Failed to update complaint: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete complaint
     */
    public function delete($id) {
        return $this->deleteComplaint();
    }
    
    /**
     * Submit a new complaint
     */
    public function submitComplaint() {
        try {
            // Validate CSRF token
            if (!$this->validateCSRF()) {
                return $this->jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
            }
            
            // Validate required fields
            $requiredFields = ['name', 'email', 'phone', 'gender', 'dob', 'state', 'district', 'pincode', 'address', 'complaint_type', 'date_of_incident', 'complaint_details'];
            
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    return $this->jsonResponse(['success' => false, 'message' => "Field '$field' is required"], 400);
                }
            }
            
            // Sanitize input data
            $data = $this->sanitizeComplaintData($_POST);
            
            // Validate data
            $validation = $this->validateComplaintData($data);
            if (!$validation['valid']) {
                return $this->jsonResponse(['success' => false, 'message' => $validation['message']], 400);
            }
            
            // Generate complaint ID
            $complaintId = $this->generateComplaintId();
            
            // Handle file uploads
            $uploadedFiles = [];
            if (!empty($_FILES['documents']['name'][0])) {
                $uploadResult = $this->fileUpload->uploadMultiple($_FILES['documents'], 'complaints');
                if ($uploadResult['success']) {
                    $uploadedFiles = $uploadResult['files'];
                } else {
                    return $this->jsonResponse(['success' => false, 'message' => $uploadResult['message']], 400);
                }
            }
            
            // Prepare data for database
            $complaintData = [
                'complaint_id' => $complaintId,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'gender' => $data['gender'],
                'dob' => $data['dob'],
                'occupation' => $data['occupation'] ?? null,
                'state' => $data['state'],
                'district' => $data['district'],
                'pincode' => $data['pincode'],
                'address' => $data['address'],
                'complaint_type' => $data['complaint_type'],
                'date_of_incident' => $data['date_of_incident'],
                'place_of_incident' => $data['place_of_incident'] ?? null,
                'complaint_details' => $data['complaint_details'],
                'supporting_documents' => json_encode($uploadedFiles),
                'status' => 'submitted',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Insert complaint into database
            $result = $this->complaintModel->create($complaintData);
            
            if ($result) {
                // Send confirmation email
                $this->sendConfirmationEmail($data['email'], $data['name'], $complaintId);
                
                // Log activity
                $this->logActivity(null, 'complaint_submit', "Complaint $complaintId submitted");
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Your complaint has been submitted successfully. You will receive a confirmation email shortly.',
                    'complaint_id' => $complaintId
                ]);
            } else {
                return $this->jsonResponse(['success' => false, 'message' => 'Failed to submit complaint. Please try again.'], 500);
            }
            
        } catch (Exception $e) {
            error_log("Complaint submission error: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'An error occurred while processing your complaint'], 500);
        }
    }
    
    /**
     * Get complaint by ID
     */
    public function getComplaint($complaintId) {
        try {
            $complaint = $this->complaintModel->findBy('complaint_id', $complaintId);
            
            if (!$complaint) {
                return $this->jsonResponse(['success' => false, 'message' => 'Complaint not found'], 404);
            }
            
            // Decode supporting documents
            if ($complaint['supporting_documents']) {
                $complaint['supporting_documents'] = json_decode($complaint['supporting_documents'], true);
            }
            
            return $this->jsonResponse(['success' => true, 'complaint' => $complaint]);
            
        } catch (Exception $e) {
            error_log("Get complaint error: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Error retrieving complaint'], 500);
        }
    }
    
    /**
     * Get all complaints (admin only)
     */
    public function getAllComplaints() {
        try {
            // Check admin permission
            if (!$this->hasRole('admin')) {
                return $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
            }
            
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $status = $_GET['status'] ?? null;
            $search = $_GET['search'] ?? null;
            
            $conditions = [];
            $params = [];
            
            if ($status) {
                $conditions[] = "status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $conditions[] = "(name LIKE ? OR email LIKE ? OR complaint_id LIKE ?)";
                $searchTerm = "%$search%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            $whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM complaints $whereClause";
            $totalResult = $this->complaintModel->query($countSql, $params);
            $total = $totalResult[0]['total'] ?? 0;
            
            // Get complaints
            $offset = ($page - 1) * $limit;
            $sql = "SELECT * FROM complaints $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
            $complaints = $this->complaintModel->query($sql, $params);
            
            // Decode supporting documents for each complaint
            foreach ($complaints as &$complaint) {
                if ($complaint['supporting_documents']) {
                    $complaint['supporting_documents'] = json_decode($complaint['supporting_documents'], true);
                }
            }
            
            return $this->jsonResponse([
                'success' => true,
                'complaints' => $complaints,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Get all complaints error: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Error retrieving complaints'], 500);
        }
    }
    
    /**
     * Update complaint status (admin only)
     */
    public function updateComplaintStatus() {
        try {
            // Check admin permission
            if (!$this->hasRole('admin')) {
                return $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
            }
            
            $complaintId = $_POST['complaint_id'] ?? '';
            $status = $_POST['status'] ?? '';
            $adminNotes = $_POST['admin_notes'] ?? '';
            
            if (empty($complaintId) || empty($status)) {
                return $this->jsonResponse(['success' => false, 'message' => 'Complaint ID and status are required'], 400);
            }
            
            // Validate status
            $validStatuses = ['submitted', 'under_review', 'investigating', 'resolved', 'closed', 'rejected'];
            if (!in_array($status, $validStatuses)) {
                return $this->jsonResponse(['success' => false, 'message' => 'Invalid status'], 400);
            }
            
            // Update complaint
            $updateData = [
                'status' => $status,
                'admin_notes' => $adminNotes,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $_SESSION['user_id'] ?? null
            ];
            
            $result = $this->complaintModel->updateBy('complaint_id', $complaintId, $updateData);
            
            if ($result) {
                // Get complaint details for email notification
                $complaint = $this->complaintModel->findBy('complaint_id', $complaintId);
                
                // Send status update email
                $this->sendStatusUpdateEmail($complaint['email'], $complaint['name'], $complaintId, $status);
                
                // Log activity
                $this->logActivity($_SESSION['user_id'] ?? null, 'complaint_update', "Complaint $complaintId status updated to $status");
                
                return $this->jsonResponse(['success' => true, 'message' => 'Complaint status updated successfully']);
            } else {
                return $this->jsonResponse(['success' => false, 'message' => 'Failed to update complaint status'], 500);
            }
            
        } catch (Exception $e) {
            error_log("Update complaint status error: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Error updating complaint status'], 500);
        }
    }
    
    /**
     * Delete complaint (admin only)
     */
    public function deleteComplaint() {
        try {
            // Check admin permission
            if (!$this->hasRole('admin')) {
                return $this->jsonResponse(['success' => false, 'message' => 'Access denied'], 403);
            }
            
            $complaintId = $_POST['complaint_id'] ?? '';
            
            if (empty($complaintId)) {
                return $this->jsonResponse(['success' => false, 'message' => 'Complaint ID is required'], 400);
            }
            
            // Get complaint details before deletion
            $complaint = $this->complaintModel->findBy('complaint_id', $complaintId);
            
            if (!$complaint) {
                return $this->jsonResponse(['success' => false, 'message' => 'Complaint not found'], 404);
            }
            
            // Delete associated files
            if ($complaint['supporting_documents']) {
                $files = json_decode($complaint['supporting_documents'], true);
                foreach ($files as $file) {
                    $this->fileUpload->deleteFile($file['path']);
                }
            }
            
            // Delete complaint
            $result = $this->complaintModel->deleteBy('complaint_id', $complaintId);
            
            if ($result) {
                // Log activity
                $this->logActivity($_SESSION['user_id'] ?? null, 'complaint_delete', "Complaint $complaintId deleted");
                
                return $this->jsonResponse(['success' => true, 'message' => 'Complaint deleted successfully']);
            } else {
                return $this->jsonResponse(['success' => false, 'message' => 'Failed to delete complaint'], 500);
            }
            
        } catch (Exception $e) {
            error_log("Delete complaint error: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'message' => 'Error deleting complaint'], 500);
        }
    }
    
    /**
     * Sanitize complaint data
     */
    private function sanitizeComplaintData($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validate complaint data
     */
    private function validateComplaintData($data) {
        // Email validation
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Invalid email address'];
        }
        
        // Phone validation
        if (!preg_match('/^[6-9]\d{9}$/', preg_replace('/\D/', '', $data['phone']))) {
            return ['valid' => false, 'message' => 'Invalid phone number'];
        }
        
        // PIN code validation
        if (!preg_match('/^[1-9][0-9]{5}$/', $data['pincode'])) {
            return ['valid' => false, 'message' => 'Invalid PIN code'];
        }
        
        // Date validations
        $dob = new DateTime($data['dob']);
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        
        if ($age < 18) {
            return ['valid' => false, 'message' => 'You must be at least 18 years old'];
        }
        
        $incidentDate = new DateTime($data['date_of_incident']);
        if ($incidentDate > $today) {
            return ['valid' => false, 'message' => 'Incident date cannot be in the future'];
        }
        
        // Complaint details length
        if (strlen($data['complaint_details']) < 50) {
            return ['valid' => false, 'message' => 'Complaint details must be at least 50 characters long'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Generate unique complaint ID
     */
    private function generateComplaintId() {
        $prefix = 'BHRC';
        $timestamp = date('Ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $complaintId = $prefix . $timestamp . $random;
        
        // Ensure uniqueness
        while ($this->complaintModel->findBy('complaint_id', $complaintId)) {
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $complaintId = $prefix . $timestamp . $random;
        }
        
        return $complaintId;
    }
    
    /**
     * Send confirmation email
     */
    private function sendConfirmationEmail($email, $name, $complaintId) {
        try {
            $subject = "Complaint Submitted Successfully - ID: $complaintId";
            $message = "
                <h2>Complaint Confirmation</h2>
                <p>Dear $name,</p>
                <p>Your complaint has been successfully submitted to Bharatiya Human Rights Council.</p>
                <p><strong>Complaint ID:</strong> $complaintId</p>
                <p><strong>Status:</strong> Submitted</p>
                <p>We will review your complaint and get back to you within 48 hours.</p>
                <p>You can track the status of your complaint using the complaint ID provided above.</p>
                <p>Thank you for bringing this matter to our attention.</p>
                <br>
                <p>Best regards,<br>BHRC India Team</p>
            ";
            
            $this->emailService->send($email, $subject, $message);
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
        }
    }
    
    /**
     * Send status update email
     */
    private function sendStatusUpdateEmail($email, $name, $complaintId, $status) {
        try {
            $statusMessages = [
                'under_review' => 'Your complaint is now under review by our team.',
                'investigating' => 'We are actively investigating your complaint.',
                'resolved' => 'Your complaint has been resolved.',
                'closed' => 'Your complaint has been closed.',
                'rejected' => 'Your complaint has been rejected after review.'
            ];
            
            $statusMessage = $statusMessages[$status] ?? 'Your complaint status has been updated.';
            
            $subject = "Complaint Status Update - ID: $complaintId";
            $message = "
                <h2>Complaint Status Update</h2>
                <p>Dear $name,</p>
                <p><strong>Complaint ID:</strong> $complaintId</p>
                <p><strong>New Status:</strong> " . ucwords(str_replace('_', ' ', $status)) . "</p>
                <p>$statusMessage</p>
                <p>If you have any questions, please contact us with your complaint ID.</p>
                <br>
                <p>Best regards,<br>BHRC India Team</p>
            ";
            
            $this->emailService->send($email, $subject, $message);
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
        }
    }
    
    /**
     * Send error response
     */
    private function sendError($message, $code = 400) {
        $this->jsonError($message, $code);
    }
    
    /**
     * Send success response
     */
    private function sendResponse($data, $message = 'Success', $code = 200) {
        $this->jsonSuccess($message, $data, $code);
    }
}
?>