<?php

class DonationController {
    private $db;
    private $emailService;
    private $paymentGateway;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->emailService = new EmailService();
        $this->paymentGateway = new PaymentGateway();
    }
    
    /**
     * Get all donations (API method)
     */
    public function getAll($params = []) {
        try {
            $page = isset($params['page']) ? (int)$params['page'] : 1;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 10;
            $status = isset($params['status']) ? $params['status'] : null;
            
            $filters = [];
            if ($status) $filters['status'] = $status;
            
            $donationModel = new Donation();
            $result = $donationModel->getAll($page, $limit, $filters);
            
            return $this->sendResponse($result);
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch donations: ' . $e->getMessage());
        }
    }
    
    /**
     * Get donation by ID (API method)
     */
    public function getById($id) {
        try {
            if (!$id) {
                return $this->sendError('Donation ID is required');
            }
            
            $donationModel = new Donation();
            $donation = $donationModel->getById($id);
            
            if (!$donation) {
                return $this->sendError('Donation not found', 404);
            }
            
            return $this->sendResponse($donation);
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch donation: ' . $e->getMessage());
        }
    }
    
    /**
     * Create new donation (API method)
     */
    public function create($data) {
        try {
            // Validate required fields
            $required = ['amount', 'donor_name', 'donor_email'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->sendError("Field '$field' is required");
                }
            }
            
            $donationModel = new Donation();
            $donationId = $donationModel->create($data);
            
            if ($donationId) {
                $donation = $donationModel->getById($donationId);
                return $this->sendResponse($donation, 'Donation created successfully');
            } else {
                return $this->sendError('Failed to create donation');
            }
        } catch (Exception $e) {
            return $this->sendError('Failed to create donation: ' . $e->getMessage());
        }
    }
    
    /**
     * Update donation (API method)
     */
    public function update($id, $data) {
        try {
            if (!$id) {
                return $this->sendError('Donation ID is required');
            }
            
            $donationModel = new Donation();
            $existingDonation = $donationModel->getById($id);
            if (!$existingDonation) {
                return $this->sendError('Donation not found', 404);
            }
            
            $success = $donationModel->update($id, $data);
            
            if ($success) {
                $donation = $donationModel->getById($id);
                return $this->sendResponse($donation, 'Donation updated successfully');
            } else {
                return $this->sendError('Failed to update donation');
            }
        } catch (Exception $e) {
            return $this->sendError('Failed to update donation: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete donation (API method)
     */
    public function delete($id) {
        try {
            if (!$id) {
                return $this->sendError('Donation ID is required');
            }
            
            $donationModel = new Donation();
            $donation = $donationModel->getById($id);
            if (!$donation) {
                return $this->sendError('Donation not found', 404);
            }
            
            $success = $donationModel->delete($id);
            
            if ($success) {
                return $this->sendResponse(null, 'Donation deleted successfully');
            } else {
                return $this->sendError('Failed to delete donation');
            }
        } catch (Exception $e) {
            return $this->sendError('Failed to delete donation: ' . $e->getMessage());
        }
    }
    
    /**
     * Get donation receipt (API method)
     */
    public function getReceipt($id) {
        try {
            if (!$id) {
                return $this->sendError('Donation ID is required');
            }
            
            $donationModel = new Donation();
            $donation = $donationModel->getById($id);
            
            if (!$donation) {
                return $this->sendError('Donation not found', 404);
            }
            
            if ($donation['status'] !== 'completed') {
                return $this->sendError('Receipt not available for incomplete donations');
            }
            
            // Generate receipt data
            $receiptData = [
                'donation_id' => $donation['id'],
                'receipt_number' => 'BHRC-' . str_pad($donation['id'], 6, '0', STR_PAD_LEFT),
                'amount' => $donation['amount'],
                'donor_name' => $donation['donor_name'],
                'donor_email' => $donation['donor_email'],
                'date' => $donation['created_at'],
                'purpose' => $donation['purpose'] ?? 'General Donation'
            ];
            
            return $this->sendResponse($receiptData);
        } catch (Exception $e) {
            return $this->sendError('Failed to generate receipt: ' . $e->getMessage());
        }
    }
    
    /**
     * Process donation payment (API method)
     */
    public function process($data) {
        try {
            // Validate required fields
            $required = ['donation_id', 'payment_method'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return $this->sendError("Field '$field' is required");
                }
            }
            
            $donationModel = new Donation();
            $donation = $donationModel->getById($data['donation_id']);
            
            if (!$donation) {
                return $this->sendError('Donation not found', 404);
            }
            
            // Process payment through gateway
            $paymentResult = $this->paymentGateway->processPayment($donation, $data);
            
            if ($paymentResult['success']) {
                // Update donation status
                $donationModel->update($data['donation_id'], [
                    'status' => 'processing',
                    'payment_method' => $data['payment_method'],
                    'transaction_id' => $paymentResult['transaction_id']
                ]);
                
                return $this->sendResponse($paymentResult, 'Payment initiated successfully');
            } else {
                return $this->sendError('Payment processing failed: ' . $paymentResult['message']);
            }
        } catch (Exception $e) {
            return $this->sendError('Failed to process payment: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle payment callback (API method)
     */
    public function paymentCallback($data) {
        try {
            // Validate callback data
            if (empty($data['transaction_id']) || empty($data['status'])) {
                return $this->sendError('Invalid callback data');
            }
            
            $donationModel = new Donation();
            
            // Find donation by transaction ID
            $stmt = $this->db->prepare("SELECT * FROM donations WHERE transaction_id = ?");
            $stmt->execute([$data['transaction_id']]);
            $donation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$donation) {
                return $this->sendError('Donation not found for transaction');
            }
            
            // Update donation status based on callback
            $newStatus = $data['status'] === 'success' ? 'completed' : 'failed';
            $updateData = [
                'status' => $newStatus,
                'payment_response' => json_encode($data)
            ];
            
            if ($newStatus === 'completed') {
                $updateData['completed_at'] = date('Y-m-d H:i:s');
            }
            
            $donationModel->update($donation['id'], $updateData);
            
            // Send confirmation email if successful
            if ($newStatus === 'completed') {
                $this->emailService->sendDonationConfirmation($donation);
            }
            
            return $this->sendResponse(['status' => $newStatus], 'Payment status updated');
        } catch (Exception $e) {
            return $this->sendError('Failed to process callback: ' . $e->getMessage());
        }
    }
    
    /**
     * Send API response
     */
    private function sendResponse($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }
    
    /**
     * Send API error response
     */
    private function sendError($message, $code = 400) {
        http_response_code($code);
        return [
            'success' => false,
            'message' => $message
        ];
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path if present
        $path = str_replace('/api/donations', '', $path);
        
        switch ($method) {
            case 'GET':
                if ($path === '/stats') {
                    return $this->getDonationStats();
                } elseif ($path === '/history') {
                    return $this->getDonationHistory();
                } elseif (preg_match('/^\/(\d+)$/', $path, $matches)) {
                    return $this->getDonation($matches[1]);
                } else {
                    return $this->getAllDonations();
                }
                break;
                
            case 'POST':
                if ($path === '') {
                    return $this->createDonation();
                } elseif ($path === '/callback') {
                    return $this->handlePaymentCallback();
                }
                break;
                
            case 'PUT':
                if (preg_match('/^\/(\d+)$/', $path, $matches)) {
                    return $this->updateDonation($matches[1]);
                }
                break;
                
            case 'DELETE':
                if (preg_match('/^\/(\d+)$/', $path, $matches)) {
                    return $this->deleteDonation($matches[1]);
                }
                break;
        }
        
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
    
    public function createDonation() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate input
            $validation = $this->validateDonationData($input);
            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode(['error' => $validation['message']]);
                return;
            }
            
            // Sanitize input
            $donationData = $this->sanitizeDonationData($input);
            
            // Generate unique donation ID
            $donationId = $this->generateDonationId();
            
            // Insert donation record
            $stmt = $this->db->prepare("
                INSERT INTO donations (
                    donation_id, amount, donation_type, purpose, 
                    donor_name, donor_email, donor_phone, donor_pan, 
                    donor_address, anonymous, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $donationId,
                $donationData['amount'],
                $donationData['donationType'],
                $donationData['purpose'],
                $donationData['donorName'],
                $donationData['donorEmail'],
                $donationData['donorPhone'],
                $donationData['donorPan'] ?? null,
                $donationData['donorAddress'] ?? null,
                $donationData['anonymous'] ? 1 : 0
            ]);
            
            // Initialize payment
            $paymentData = $this->initializePayment($donationId, $donationData);
            
            // Send confirmation email
            $this->sendDonationConfirmationEmail($donationData, $donationId);
            
            // Log donation attempt
            $this->logDonationActivity($donationId, 'created', 'Donation created and payment initialized');
            
            echo json_encode([
                'success' => true,
                'donationId' => $donationId,
                'paymentUrl' => $paymentData['paymentUrl'] ?? null,
                'message' => 'Donation created successfully'
            ]);
            
        } catch (Exception $e) {
            error_log("Donation creation error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create donation']);
        }
    }
    
    public function getAllDonations() {
        try {
            // Check admin authentication
            if (!$this->isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                return;
            }
            
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 20;
            $status = $_GET['status'] ?? '';
            $search = $_GET['search'] ?? '';
            
            $offset = ($page - 1) * $limit;
            
            // Build query
            $whereConditions = [];
            $params = [];
            
            if ($status) {
                $whereConditions[] = "status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $whereConditions[] = "(donor_name LIKE ? OR donor_email LIKE ? OR donation_id LIKE ?)";
                $searchTerm = "%$search%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get donations
            $stmt = $this->db->prepare("
                SELECT donation_id, amount, donation_type, purpose, 
                       donor_name, donor_email, donor_phone, 
                       anonymous, status, created_at, updated_at
                FROM donations 
                $whereClause
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $countStmt = $this->db->prepare("SELECT COUNT(*) FROM donations $whereClause");
            $countStmt->execute(array_slice($params, 0, -2));
            $total = $countStmt->fetchColumn();
            
            echo json_encode([
                'donations' => $donations,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Get donations error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch donations']);
        }
    }
    
    public function getDonation($id) {
        try {
            // Check admin authentication or donor access
            if (!$this->isAdmin() && !$this->isDonorAuthorized($id)) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied']);
                return;
            }
            
            $stmt = $this->db->prepare("
                SELECT * FROM donations WHERE donation_id = ?
            ");
            $stmt->execute([$id]);
            $donation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$donation) {
                http_response_code(404);
                echo json_encode(['error' => 'Donation not found']);
                return;
            }
            
            // Get payment history
            $paymentStmt = $this->db->prepare("
                SELECT * FROM donation_payments 
                WHERE donation_id = ? 
                ORDER BY created_at DESC
            ");
            $paymentStmt->execute([$id]);
            $payments = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $donation['payments'] = $payments;
            
            echo json_encode($donation);
            
        } catch (Exception $e) {
            error_log("Get donation error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch donation']);
        }
    }
    
    public function updateDonation($id) {
        try {
            // Check admin authentication
            if (!$this->isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate donation exists
            $stmt = $this->db->prepare("SELECT * FROM donations WHERE donation_id = ?");
            $stmt->execute([$id]);
            $donation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$donation) {
                http_response_code(404);
                echo json_encode(['error' => 'Donation not found']);
                return;
            }
            
            // Update allowed fields
            $allowedFields = ['status', 'notes'];
            $updateFields = [];
            $params = [];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                return;
            }
            
            $updateFields[] = "updated_at = NOW()";
            $params[] = $id;
            
            $updateStmt = $this->db->prepare("
                UPDATE donations 
                SET " . implode(', ', $updateFields) . "
                WHERE donation_id = ?
            ");
            $updateStmt->execute($params);
            
            // Log activity
            $this->logDonationActivity($id, 'updated', 'Donation updated by admin');
            
            // Send notification if status changed
            if (isset($input['status']) && $input['status'] !== $donation['status']) {
                $this->sendStatusUpdateEmail($donation, $input['status']);
            }
            
            echo json_encode(['success' => true, 'message' => 'Donation updated successfully']);
            
        } catch (Exception $e) {
            error_log("Update donation error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update donation']);
        }
    }
    
    public function deleteDonation($id) {
        try {
            // Check admin authentication
            if (!$this->isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                return;
            }
            
            // Check if donation exists
            $stmt = $this->db->prepare("SELECT * FROM donations WHERE donation_id = ?");
            $stmt->execute([$id]);
            $donation = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$donation) {
                http_response_code(404);
                echo json_encode(['error' => 'Donation not found']);
                return;
            }
            
            // Don't allow deletion of completed donations
            if ($donation['status'] === 'completed') {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete completed donations']);
                return;
            }
            
            // Delete donation (cascade will handle related records)
            $deleteStmt = $this->db->prepare("DELETE FROM donations WHERE donation_id = ?");
            $deleteStmt->execute([$id]);
            
            // Log activity
            $this->logDonationActivity($id, 'deleted', 'Donation deleted by admin');
            
            echo json_encode(['success' => true, 'message' => 'Donation deleted successfully']);
            
        } catch (Exception $e) {
            error_log("Delete donation error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete donation']);
        }
    }
    
    public function getDonationStats() {
        try {
            $stats = [];
            
            // Total donations
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_donations,
                    SUM(amount) as total_amount,
                    AVG(amount) as average_amount,
                    COUNT(DISTINCT donor_email) as unique_donors
                FROM donations 
                WHERE status = 'completed'
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stats['totalDonations'] = (int)$result['total_donations'];
            $stats['totalAmount'] = (float)$result['total_amount'];
            $stats['averageDonation'] = (float)$result['average_amount'];
            $stats['totalDonors'] = (int)$result['unique_donors'];
            
            // Monthly stats
            $monthlyStmt = $this->db->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count,
                    SUM(amount) as amount
                FROM donations 
                WHERE status = 'completed' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month
            ");
            $monthlyStmt->execute();
            $stats['monthlyStats'] = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Purpose-wise stats
            $purposeStmt = $this->db->prepare("
                SELECT 
                    purpose,
                    COUNT(*) as count,
                    SUM(amount) as amount
                FROM donations 
                WHERE status = 'completed'
                GROUP BY purpose
                ORDER BY amount DESC
            ");
            $purposeStmt->execute();
            $stats['purposeStats'] = $purposeStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($stats);
            
        } catch (Exception $e) {
            error_log("Get donation stats error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch donation statistics']);
        }
    }
    
    public function getDonationHistory() {
        try {
            $email = $_GET['email'] ?? '';
            $phone = $_GET['phone'] ?? '';
            
            if (!$email && !$phone) {
                http_response_code(400);
                echo json_encode(['error' => 'Email or phone number required']);
                return;
            }
            
            $whereConditions = [];
            $params = [];
            
            if ($email) {
                $whereConditions[] = "donor_email = ?";
                $params[] = $email;
            }
            
            if ($phone) {
                $whereConditions[] = "donor_phone = ?";
                $params[] = $phone;
            }
            
            $whereClause = implode(' OR ', $whereConditions);
            
            $stmt = $this->db->prepare("
                SELECT donation_id, amount, donation_type, purpose, 
                       status, created_at, updated_at
                FROM donations 
                WHERE $whereClause
                ORDER BY created_at DESC
            ");
            $stmt->execute($params);
            $donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['donations' => $donations]);
            
        } catch (Exception $e) {
            error_log("Get donation history error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch donation history']);
        }
    }
    
    public function handlePaymentCallback() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Verify payment signature/hash
            if (!$this->verifyPaymentCallback($input)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid payment callback']);
                return;
            }
            
            $donationId = $input['donation_id'];
            $status = $input['status']; // success, failed, pending
            $transactionId = $input['transaction_id'] ?? null;
            $gatewayResponse = $input['gateway_response'] ?? null;
            
            // Update donation status
            $stmt = $this->db->prepare("
                UPDATE donations 
                SET status = ?, transaction_id = ?, updated_at = NOW()
                WHERE donation_id = ?
            ");
            $stmt->execute([
                $status === 'success' ? 'completed' : 'failed',
                $transactionId,
                $donationId
            ]);
            
            // Insert payment record
            $paymentStmt = $this->db->prepare("
                INSERT INTO donation_payments (
                    donation_id, transaction_id, status, 
                    gateway_response, created_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            $paymentStmt->execute([
                $donationId,
                $transactionId,
                $status,
                json_encode($gatewayResponse)
            ]);
            
            // Get donation details
            $donationStmt = $this->db->prepare("SELECT * FROM donations WHERE donation_id = ?");
            $donationStmt->execute([$donationId]);
            $donation = $donationStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($status === 'success') {
                // Send success email with receipt
                $this->sendPaymentSuccessEmail($donation, $transactionId);
                
                // Generate tax receipt if PAN provided
                if ($donation['donor_pan']) {
                    $this->generateTaxReceipt($donation, $transactionId);
                }
            } else {
                // Send failure email
                $this->sendPaymentFailureEmail($donation);
            }
            
            // Log activity
            $this->logDonationActivity($donationId, 'payment_' . $status, 
                "Payment $status - Transaction ID: $transactionId");
            
            echo json_encode(['success' => true, 'message' => 'Payment callback processed']);
            
        } catch (Exception $e) {
            error_log("Payment callback error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to process payment callback']);
        }
    }
    
    private function validateDonationData($data) {
        $required = ['amount', 'donationType', 'purpose', 'donorName', 'donorEmail', 'donorPhone'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                return ['valid' => false, 'message' => "Field '$field' is required"];
            }
        }
        
        // Validate amount
        if (!is_numeric($data['amount']) || $data['amount'] < 100) {
            return ['valid' => false, 'message' => 'Minimum donation amount is ₹100'];
        }
        
        // Validate email
        if (!filter_var($data['donorEmail'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Invalid email address'];
        }
        
        // Validate phone
        if (!preg_match('/^[6-9]\d{9}$/', $data['donorPhone'])) {
            return ['valid' => false, 'message' => 'Invalid phone number'];
        }
        
        // Validate PAN if provided
        if (isset($data['donorPan']) && !empty($data['donorPan'])) {
            if (!preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', $data['donorPan'])) {
                return ['valid' => false, 'message' => 'Invalid PAN number'];
            }
        }
        
        return ['valid' => true];
    }
    
    private function sanitizeDonationData($data) {
        return [
            'amount' => (float)$data['amount'],
            'donationType' => htmlspecialchars(trim($data['donationType'])),
            'purpose' => htmlspecialchars(trim($data['purpose'])),
            'donorName' => htmlspecialchars(trim($data['donorName'])),
            'donorEmail' => filter_var(trim($data['donorEmail']), FILTER_SANITIZE_EMAIL),
            'donorPhone' => preg_replace('/[^0-9]/', '', $data['donorPhone']),
            'donorPan' => isset($data['donorPan']) ? strtoupper(trim($data['donorPan'])) : null,
            'donorAddress' => isset($data['donorAddress']) ? htmlspecialchars(trim($data['donorAddress'])) : null,
            'anonymous' => isset($data['anonymous']) && $data['anonymous']
        ];
    }
    
    private function generateDonationId() {
        return 'DON' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    }
    
    private function initializePayment($donationId, $donationData) {
        return $this->paymentGateway->initializePayment([
            'donation_id' => $donationId,
            'amount' => $donationData['amount'],
            'donor_name' => $donationData['donorName'],
            'donor_email' => $donationData['donorEmail'],
            'donor_phone' => $donationData['donorPhone'],
            'purpose' => $donationData['purpose']
        ]);
    }
    
    private function verifyPaymentCallback($data) {
        return $this->paymentGateway->verifyCallback($data);
    }
    
    private function sendDonationConfirmationEmail($donationData, $donationId) {
        $subject = "Donation Confirmation - BHRC India";
        $message = "
            <h2>Thank you for your donation!</h2>
            <p>Dear {$donationData['donorName']},</p>
            <p>We have received your donation request. Here are the details:</p>
            <ul>
                <li><strong>Donation ID:</strong> $donationId</li>
                <li><strong>Amount:</strong> ₹{$donationData['amount']}</li>
                <li><strong>Purpose:</strong> {$donationData['purpose']}</li>
                <li><strong>Type:</strong> {$donationData['donationType']}</li>
            </ul>
            <p>Please complete the payment to confirm your donation.</p>
            <p>Thank you for supporting our mission!</p>
        ";
        
        $this->emailService->sendEmail($donationData['donorEmail'], $subject, $message);
    }
    
    private function sendPaymentSuccessEmail($donation, $transactionId) {
        $subject = "Payment Successful - BHRC India";
        $message = "
            <h2>Payment Successful!</h2>
            <p>Dear {$donation['donor_name']},</p>
            <p>Your donation has been successfully processed. Here are the details:</p>
            <ul>
                <li><strong>Donation ID:</strong> {$donation['donation_id']}</li>
                <li><strong>Transaction ID:</strong> $transactionId</li>
                <li><strong>Amount:</strong> ₹{$donation['amount']}</li>
                <li><strong>Purpose:</strong> {$donation['purpose']}</li>
            </ul>
            <p>Your contribution will help us continue our mission to protect human rights.</p>
            <p>A tax receipt will be sent separately if PAN was provided.</p>
        ";
        
        $this->emailService->sendEmail($donation['donor_email'], $subject, $message);
    }
    
    private function sendPaymentFailureEmail($donation) {
        $subject = "Payment Failed - BHRC India";
        $message = "
            <h2>Payment Failed</h2>
            <p>Dear {$donation['donor_name']},</p>
            <p>Unfortunately, your payment could not be processed. Please try again or contact our support team.</p>
            <p><strong>Donation ID:</strong> {$donation['donation_id']}</p>
            <p>You can retry the payment or contact us for assistance.</p>
        ";
        
        $this->emailService->sendEmail($donation['donor_email'], $subject, $message);
    }
    
    private function sendStatusUpdateEmail($donation, $newStatus) {
        $subject = "Donation Status Update - BHRC India";
        $message = "
            <h2>Donation Status Update</h2>
            <p>Dear {$donation['donor_name']},</p>
            <p>The status of your donation has been updated:</p>
            <ul>
                <li><strong>Donation ID:</strong> {$donation['donation_id']}</li>
                <li><strong>New Status:</strong> " . ucfirst($newStatus) . "</li>
                <li><strong>Amount:</strong> ₹{$donation['amount']}</li>
            </ul>
        ";
        
        $this->emailService->sendEmail($donation['donor_email'], $subject, $message);
    }
    
    private function generateTaxReceipt($donation, $transactionId) {
        // Generate 80G tax receipt
        // This would integrate with a PDF generation library
        // For now, just log the requirement
        error_log("Tax receipt required for donation: {$donation['donation_id']}");
    }
    
    private function logDonationActivity($donationId, $action, $description) {
        $stmt = $this->db->prepare("
            INSERT INTO donation_logs (donation_id, action, description, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$donationId, $action, $description]);
    }
    
    private function isAdmin() {
        // Check if user is authenticated as admin
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    private function isDonorAuthorized($donationId) {
        // Check if current user is the donor for this donation
        if (!isset($_SESSION['user_email'])) {
            return false;
        }
        
        $stmt = $this->db->prepare("
            SELECT donor_email FROM donations WHERE donation_id = ?
        ");
        $stmt->execute([$donationId]);
        $donation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $donation && $donation['donor_email'] === $_SESSION['user_email'];
    }
    
    /**
     * Process payment for donation
     */
    public function processPayment($donation, $data) {
        return $this->paymentGateway->processPayment($donation, $data);
    }
    
    /**
     * Send donation confirmation email
     */
    public function sendDonationConfirmation($donation) {
        return $this->emailService->sendDonationConfirmation($donation);
    }
    
    /**
     * Send email
     */
    public function sendEmail($to, $subject, $message, $attachments = []) {
        return $this->emailService->send($to, $subject, $message, $attachments);
    }
}

// Payment Gateway Integration Class
class PaymentGateway {
    private $config;
    
    public function __construct() {
        $this->config = [
            'merchant_id' => $_ENV['PAYMENT_MERCHANT_ID'] ?? 'test_merchant',
            'secret_key' => $_ENV['PAYMENT_SECRET_KEY'] ?? 'test_secret',
            'gateway_url' => $_ENV['PAYMENT_GATEWAY_URL'] ?? 'https://test.payment.gateway.com'
        ];
    }
    
    public function initializePayment($data) {
        // This would integrate with actual payment gateway (Razorpay, PayU, etc.)
        $paymentUrl = $this->config['gateway_url'] . '/payment?' . http_build_query([
            'merchant_id' => $this->config['merchant_id'],
            'amount' => $data['amount'],
            'donation_id' => $data['donation_id'],
            'customer_name' => $data['donor_name'],
            'customer_email' => $data['donor_email'],
            'customer_phone' => $data['donor_phone'],
            'purpose' => $data['purpose'],
            'return_url' => $_ENV['SITE_URL'] . '/donate?payment_status=success',
            'cancel_url' => $_ENV['SITE_URL'] . '/donate?payment_status=failed'
        ]);
        
        return ['paymentUrl' => $paymentUrl];
    }
    
    public function verifyCallback($data) {
        // Verify payment gateway callback signature
        // This would implement actual signature verification
        return true; // Simplified for demo
    }
    
    /**
     * Process payment for donation
     */
    public function processPayment($donation, $data) {
        // This would integrate with actual payment processing
        return $this->initializePayment($data);
    }
}

?>