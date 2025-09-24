<?php
/**
 * Contact Controller
 * Handles contact inquiries and responses
 */

require_once __DIR__ . '/../models/Contact.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/BaseController.php';

class ContactController extends BaseController {
    
    private $contactModel;
    private $emailService;
    
    public function __construct() {
        parent::__construct();
        $this->contactModel = new Contact();
        $this->emailService = new EmailService();
    }
    
    /**
     * Submit contact form (public endpoint)
     */
    public function submit() {
        try {
            $data = $this->getJsonInput();
            
            // Validate required fields
            $required = ['name', 'email', 'subject', 'message'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $this->sendError("Field '$field' is required", 400);
                    return;
                }
            }
            
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->sendError('Invalid email format', 400);
                return;
            }
            
            // Sanitize input
            $data['name'] = htmlspecialchars(trim($data['name']));
            $data['email'] = trim($data['email']);
            $data['subject'] = htmlspecialchars(trim($data['subject']));
            $data['message'] = htmlspecialchars(trim($data['message']));
            $data['phone'] = isset($data['phone']) ? htmlspecialchars(trim($data['phone'])) : null;
            
            // Add metadata
            $data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $data['status'] = 'new';
            $data['created_at'] = date('Y-m-d H:i:s');
            
            $inquiryId = $this->contactModel->createInquiry($data);
            
            if ($inquiryId) {
                // Send notification email to admin
                $this->sendAdminNotification($data);
                
                // Send confirmation email to user
                $this->sendUserConfirmation($data);
                
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Your inquiry has been submitted successfully. We will get back to you soon.',
                    'inquiry_id' => $inquiryId
                ], 201);
            } else {
                $this->sendError('Failed to submit inquiry');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to submit inquiry: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all inquiries (admin only)
     */
    public function getInquiries() {
        try {
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $status = $_GET['status'] ?? '';
            $search = $_GET['search'] ?? '';
            
            $offset = ($page - 1) * $limit;
            
            $inquiries = $this->contactModel->getInquiries($limit, $offset, $status, $search);
            $total = $this->contactModel->getInquiriesCount($status, $search);
            
            $this->sendResponse([
                'success' => true,
                'data' => $inquiries,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch inquiries: ' . $e->getMessage());
        }
    }
    
    /**
     * Get single inquiry (admin only)
     */
    public function getInquiry($id) {
        try {
            $inquiry = $this->contactModel->getInquiryById($id);
            
            if (!$inquiry) {
                $this->sendError('Inquiry not found', 404);
                return;
            }
            
            // Mark as read if it's new
            if ($inquiry['status'] === 'new') {
                $this->contactModel->updateInquiry($id, [
                    'status' => 'read',
                    'read_at' => date('Y-m-d H:i:s')
                ]);
                $inquiry['status'] = 'read';
                $inquiry['read_at'] = date('Y-m-d H:i:s');
            }
            
            // Get responses
            $responses = $this->contactModel->getInquiryResponses($id);
            $inquiry['responses'] = $responses;
            
            $this->sendResponse([
                'success' => true,
                'data' => $inquiry
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch inquiry: ' . $e->getMessage());
        }
    }
    
    /**
     * Respond to inquiry (admin only)
     */
    public function respond($id) {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['message'])) {
                $this->sendError('Response message is required', 400);
                return;
            }
            
            $inquiry = $this->contactModel->getInquiryById($id);
            if (!$inquiry) {
                $this->sendError('Inquiry not found', 404);
                return;
            }
            
            $userId = $_SESSION['user_id'] ?? null;
            
            $responseData = [
                'inquiry_id' => $id,
                'user_id' => $userId,
                'message' => htmlspecialchars(trim($data['message'])),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $responseId = $this->contactModel->createResponse($responseData);
            
            if ($responseId) {
                // Update inquiry status
                $this->contactModel->updateInquiry($id, [
                    'status' => 'responded',
                    'responded_at' => date('Y-m-d H:i:s'),
                    'responded_by' => $userId
                ]);
                
                // Send email response to user
                $this->sendEmailResponse($inquiry, $data['message']);
                
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Response sent successfully',
                    'response_id' => $responseId
                ]);
            } else {
                $this->sendError('Failed to send response');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to send response: ' . $e->getMessage());
        }
    }
    
    /**
     * Update inquiry status
     */
    public function updateStatus($id) {
        try {
            $data = $this->getJsonInput();
            
            if (empty($data['status'])) {
                $this->sendError('Status is required', 400);
                return;
            }
            
            $allowedStatuses = ['new', 'read', 'responded', 'resolved', 'closed'];
            if (!in_array($data['status'], $allowedStatuses)) {
                $this->sendError('Invalid status', 400);
                return;
            }
            
            $inquiry = $this->contactModel->getInquiryById($id);
            if (!$inquiry) {
                $this->sendError('Inquiry not found', 404);
                return;
            }
            
            $updateData = [
                'status' => $data['status'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($data['status'] === 'resolved') {
                $updateData['resolved_at'] = date('Y-m-d H:i:s');
                $updateData['resolved_by'] = $_SESSION['user_id'] ?? null;
            }
            
            if ($this->contactModel->updateInquiry($id, $updateData)) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'Inquiry status updated successfully'
                ]);
            } else {
                $this->sendError('Failed to update inquiry status');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to update inquiry status: ' . $e->getMessage());
        }
    }
    
    /**
     * Get contact statistics
     */
    public function getStats() {
        try {
            $stats = $this->contactModel->getStats();
            
            $this->sendResponse([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch statistics: ' . $e->getMessage());
        }
    }
    
    /**
     * Send admin notification email
     */
    private function sendAdminNotification($data) {
        try {
            $subject = 'New Contact Inquiry: ' . $data['subject'];
            $message = "
                <h3>New Contact Inquiry Received</h3>
                <p><strong>Name:</strong> {$data['name']}</p>
                <p><strong>Email:</strong> {$data['email']}</p>
                <p><strong>Phone:</strong> " . ($data['phone'] ?? 'Not provided') . "</p>
                <p><strong>Subject:</strong> {$data['subject']}</p>
                <p><strong>Message:</strong></p>
                <p>{$data['message']}</p>
                <p><strong>Submitted at:</strong> {$data['created_at']}</p>
            ";
            
            // Get admin email from config or use default
            $adminEmail = 'admin@bhrcindia.in'; // This should come from config
            
            $this->emailService->send($adminEmail, $subject, $message);
            
        } catch (Exception $e) {
            error_log('Failed to send admin notification: ' . $e->getMessage());
        }
    }
    
    /**
     * Send user confirmation email
     */
    private function sendUserConfirmation($data) {
        try {
            $subject = 'Thank you for contacting BHRC';
            $message = "
                <h3>Thank you for your inquiry</h3>
                <p>Dear {$data['name']},</p>
                <p>We have received your inquiry and will get back to you as soon as possible.</p>
                <p><strong>Your inquiry details:</strong></p>
                <p><strong>Subject:</strong> {$data['subject']}</p>
                <p><strong>Message:</strong> {$data['message']}</p>
                <p>Best regards,<br>BHRC Team</p>
            ";
            
            $this->emailService->send($data['email'], $subject, $message);
            
        } catch (Exception $e) {
            error_log('Failed to send user confirmation: ' . $e->getMessage());
        }
    }
    
    /**
     * Send email response to user
     */
    private function sendEmailResponse($inquiry, $responseMessage) {
        try {
            $subject = 'Response to your inquiry: ' . $inquiry['subject'];
            $message = "
                <h3>Response to your inquiry</h3>
                <p>Dear {$inquiry['name']},</p>
                <p>We have responded to your inquiry:</p>
                <p><strong>Your original message:</strong></p>
                <p>{$inquiry['message']}</p>
                <p><strong>Our response:</strong></p>
                <p>{$responseMessage}</p>
                <p>Best regards,<br>BHRC Team</p>
            ";
            
            $this->emailService->send($inquiry['email'], $subject, $message);
            
        } catch (Exception $e) {
            error_log('Failed to send email response: ' . $e->getMessage());
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
    
    /**
     * Get inquiry by ID
     */
    public function getInquiryById($id) {
        try {
            $inquiry = $this->contactModel->findById($id);
            
            if (!$inquiry) {
                return $this->sendError('Inquiry not found', 404);
            }
            
            return $this->sendResponse($inquiry);
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch inquiry: ' . $e->getMessage());
        }
    }
    
    /**
     * Update inquiry
     */
    public function updateInquiry($id, $data) {
        try {
            $result = $this->contactModel->updateBy('id', $id, $data);
            
            if (!$result) {
                return $this->sendError('Failed to update inquiry');
            }
            
            return $this->sendResponse(['id' => $id], 'Inquiry updated successfully');
        } catch (Exception $e) {
            return $this->sendError('Failed to update inquiry: ' . $e->getMessage());
        }
    }
    
    /**
     * Get inquiry responses
     */
    public function getInquiryResponses($inquiryId) {
        try {
            $responses = $this->contactModel->getInquiryResponses($inquiryId);
            return $this->sendResponse($responses);
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch responses: ' . $e->getMessage());
        }
    }
    
    /**
     * Create response to inquiry
     */
    public function createResponse($inquiryId, $data) {
        try {
            $responseData = [
                'inquiry_id' => $inquiryId,
                'response' => $data['response_text'],
                'responded_by' => $_SESSION['user_id'] ?? null,
                'response_type' => $data['response_type'] ?? 'reply',
                'is_internal' => $data['is_internal'] ?? false
            ];
            
            $result = $this->contactModel->createResponse($responseData);
            
            if (!$result) {
                return $this->sendError('Failed to create response');
            }
            
            return $this->sendResponse(['id' => $result], 'Response created successfully');
        } catch (Exception $e) {
            return $this->sendError('Failed to create response: ' . $e->getMessage());
        }
    }
}
?>