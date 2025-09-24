<?php
/**
 * OTP Controller
 * Handles OTP generation, verification, and management
 */

require_once __DIR__ . '/BaseController.php';

class OTPController extends BaseController {
    
    private $otpLength;
    private $otpExpiry;
    
    public function __construct() {
        parent::__construct();
        $this->otpLength = 6;
        $this->otpExpiry = 300; // 5 minutes in seconds
    }
    
    /**
     * Generate and send OTP
     */
    public function generate() {
        try {
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            $type = $_POST['type'] ?? 'login'; // login, registration, password_reset
            
            if (empty($phone) && empty($email)) {
                $this->sendError('Phone number or email is required');
                return;
            }
            
            // Validate input
            if ($phone && !$this->isValidPhone($phone)) {
                $this->sendError('Invalid phone number format');
                return;
            }
            
            if ($email && !$this->isValidEmail($email)) {
                $this->sendError('Invalid email format');
                return;
            }
            
            // Check rate limiting
            if (!$this->checkRateLimit($phone ?: $email)) {
                $this->sendError('Too many OTP requests. Please try again later.');
                return;
            }
            
            // Generate OTP
            $otp = $this->generateOTP();
            $expiresAt = date('Y-m-d H:i:s', time() + $this->otpExpiry);
            
            // Save OTP to database
            $otpId = $this->saveOTP([
                'phone' => $phone,
                'email' => $email,
                'otp' => password_hash($otp, PASSWORD_DEFAULT),
                'type' => $type,
                'expires_at' => $expiresAt,
                'attempts' => 0,
                'verified' => false
            ]);
            
            // Send OTP
            $sent = false;
            if ($phone) {
                $sent = $this->sendSMS($phone, $otp, $type);
            } elseif ($email) {
                $sent = $this->sendEmail($email, $otp, $type);
            }
            
            if ($sent) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'OTP sent successfully',
                    'data' => [
                        'otp_id' => $otpId,
                        'expires_in' => $this->otpExpiry,
                        'sent_to' => $phone ? 'phone' : 'email'
                    ]
                ]);
            } else {
                $this->sendError('Failed to send OTP');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to generate OTP: ' . $e->getMessage());
        }
    }
    
    /**
     * Verify OTP
     */
    public function verify() {
        try {
            $otpId = $_POST['otp_id'] ?? '';
            $otp = $_POST['otp'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            
            if (empty($otpId) || empty($otp)) {
                $this->sendError('OTP ID and OTP are required');
                return;
            }
            
            // Get OTP record
            $otpRecord = $this->getOTPRecord($otpId);
            
            if (!$otpRecord) {
                $this->sendError('Invalid OTP ID');
                return;
            }
            
            // Check if already verified
            if ($otpRecord['verified']) {
                $this->sendError('OTP already used');
                return;
            }
            
            // Check expiry
            if (strtotime($otpRecord['expires_at']) < time()) {
                $this->sendError('OTP has expired');
                return;
            }
            
            // Check attempts
            if ($otpRecord['attempts'] >= 3) {
                $this->sendError('Maximum verification attempts exceeded');
                return;
            }
            
            // Verify OTP
            if (!password_verify($otp, $otpRecord['otp'])) {
                // Increment attempts
                $this->incrementOTPAttempts($otpId);
                $this->sendError('Invalid OTP');
                return;
            }
            
            // Mark as verified
            $this->markOTPAsVerified($otpId);
            
            $this->sendResponse([
                'success' => true,
                'message' => 'OTP verified successfully',
                'data' => [
                    'verified' => true,
                    'type' => $otpRecord['type']
                ]
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to verify OTP: ' . $e->getMessage());
        }
    }
    
    /**
     * Resend OTP
     */
    public function resend() {
        try {
            $otpId = $_POST['otp_id'] ?? '';
            
            if (empty($otpId)) {
                $this->sendError('OTP ID is required');
                return;
            }
            
            // Get OTP record
            $otpRecord = $this->getOTPRecord($otpId);
            
            if (!$otpRecord) {
                $this->sendError('Invalid OTP ID');
                return;
            }
            
            // Check if already verified
            if ($otpRecord['verified']) {
                $this->sendError('OTP already verified');
                return;
            }
            
            // Check rate limiting for resend
            $identifier = $otpRecord['phone'] ?: $otpRecord['email'];
            if (!$this->checkResendRateLimit($identifier)) {
                $this->sendError('Please wait before requesting another OTP');
                return;
            }
            
            // Generate new OTP
            $newOtp = $this->generateOTP();
            $expiresAt = date('Y-m-d H:i:s', time() + $this->otpExpiry);
            
            // Update OTP record
            $this->updateOTP($otpId, [
                'otp' => password_hash($newOtp, PASSWORD_DEFAULT),
                'expires_at' => $expiresAt,
                'attempts' => 0
            ]);
            
            // Send new OTP
            $sent = false;
            if ($otpRecord['phone']) {
                $sent = $this->sendSMS($otpRecord['phone'], $newOtp, $otpRecord['type']);
            } elseif ($otpRecord['email']) {
                $sent = $this->sendEmail($otpRecord['email'], $newOtp, $otpRecord['type']);
            }
            
            if ($sent) {
                $this->sendResponse([
                    'success' => true,
                    'message' => 'OTP resent successfully',
                    'data' => [
                        'expires_in' => $this->otpExpiry
                    ]
                ]);
            } else {
                $this->sendError('Failed to resend OTP');
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to resend OTP: ' . $e->getMessage());
        }
    }
    
    /**
     * Get OTP status
     */
    public function getStatus() {
        try {
            $otpId = $_GET['otp_id'] ?? '';
            
            if (empty($otpId)) {
                $this->sendError('OTP ID is required');
                return;
            }
            
            $otpRecord = $this->getOTPRecord($otpId);
            
            if (!$otpRecord) {
                $this->sendError('Invalid OTP ID');
                return;
            }
            
            $timeRemaining = max(0, strtotime($otpRecord['expires_at']) - time());
            
            $this->sendResponse([
                'success' => true,
                'data' => [
                    'verified' => $otpRecord['verified'],
                    'expired' => $timeRemaining <= 0,
                    'time_remaining' => $timeRemaining,
                    'attempts_remaining' => max(0, 3 - $otpRecord['attempts']),
                    'type' => $otpRecord['type']
                ]
            ]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to get OTP status: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate random OTP
     */
    private function generateOTP() {
        return str_pad(random_int(0, pow(10, $this->otpLength) - 1), $this->otpLength, '0', STR_PAD_LEFT);
    }
    
    /**
     * Validate phone number format
     */
    private function isValidPhone($phone) {
        // Remove all non-digit characters
        $phone = preg_replace('/\D/', '', $phone);
        
        // Check if it's a valid Indian mobile number (10 digits starting with 6-9)
        return preg_match('/^[6-9]\d{9}$/', $phone);
    }
    
    /**
     * Validate email format
     */
    private function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Check rate limiting for OTP generation
     */
    private function checkRateLimit($identifier) {
        // Check if user has requested OTP in last minute
        $recentOTPs = $this->getRecentOTPs($identifier, 60); // 1 minute
        
        if (count($recentOTPs) >= 3) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check rate limiting for OTP resend
     */
    private function checkResendRateLimit($identifier) {
        // Check if user has requested resend in last 30 seconds
        $recentResends = $this->getRecentOTPs($identifier, 30);
        
        return count($recentResends) === 0;
    }
    
    /**
     * Send SMS with OTP
     */
    private function sendSMS($phone, $otp, $type) {
        try {
            $message = $this->getSMSMessage($otp, $type);
            
            // Here you would integrate with SMS service provider
            // For now, we'll simulate success
            
            // Log SMS for debugging (remove in production)
            error_log("SMS to {$phone}: {$message}");
            
            return true;
            
        } catch (Exception $e) {
            error_log("SMS sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email with OTP
     */
    private function sendEmail($email, $otp, $type) {
        try {
            $subject = $this->getEmailSubject($type);
            $message = $this->getEmailMessage($otp, $type);
            
            $headers = [
                'From: noreply@bhrcindia.in',
                'Reply-To: noreply@bhrcindia.in',
                'Content-Type: text/html; charset=UTF-8'
            ];
            
            $sent = mail($email, $subject, $message, implode("\r\n", $headers));
            
            // Log email for debugging (remove in production)
            if (!$sent) {
                error_log("Email sending failed to: {$email}");
            }
            
            return $sent;
            
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get SMS message template
     */
    private function getSMSMessage($otp, $type) {
        switch ($type) {
            case 'registration':
                return "Your BHRC registration OTP is: {$otp}. Valid for 5 minutes. Do not share this code.";
            case 'password_reset':
                return "Your BHRC password reset OTP is: {$otp}. Valid for 5 minutes. Do not share this code.";
            default:
                return "Your BHRC login OTP is: {$otp}. Valid for 5 minutes. Do not share this code.";
        }
    }
    
    /**
     * Get email subject
     */
    private function getEmailSubject($type) {
        switch ($type) {
            case 'registration':
                return 'BHRC Registration - OTP Verification';
            case 'password_reset':
                return 'BHRC Password Reset - OTP Verification';
            default:
                return 'BHRC Login - OTP Verification';
        }
    }
    
    /**
     * Get email message template
     */
    private function getEmailMessage($otp, $type) {
        $action = '';
        switch ($type) {
            case 'registration':
                $action = 'complete your registration';
                break;
            case 'password_reset':
                $action = 'reset your password';
                break;
            default:
                $action = 'login to your account';
        }
        
        return "
        <html>
        <body>
            <h2>BHRC - OTP Verification</h2>
            <p>Your OTP to {$action} is:</p>
            <h1 style='color: #007bff; font-size: 32px; letter-spacing: 5px;'>{$otp}</h1>
            <p>This OTP is valid for 5 minutes only.</p>
            <p>If you didn't request this OTP, please ignore this email.</p>
            <br>
            <p>Best regards,<br>BHRC Team</p>
        </body>
        </html>
        ";
    }
    
    /**
     * Save OTP to database
     */
    private function saveOTP($data) {
        // This would save to database
        // For now, return a unique ID
        return uniqid('otp_');
    }
    
    /**
     * Get OTP record from database
     */
    private function getOTPRecord($otpId) {
        // This would fetch from database
        // For now, return sample data
        return [
            'id' => $otpId,
            'phone' => '9876543210',
            'email' => null,
            'otp' => password_hash('123456', PASSWORD_DEFAULT),
            'type' => 'login',
            'expires_at' => date('Y-m-d H:i:s', time() + 300),
            'attempts' => 0,
            'verified' => false,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get recent OTPs for rate limiting
     */
    private function getRecentOTPs($identifier, $seconds) {
        // This would fetch from database
        // For now, return empty array
        return [];
    }
    
    /**
     * Increment OTP attempts
     */
    private function incrementOTPAttempts($otpId) {
        // This would update database
        return true;
    }
    
    /**
     * Mark OTP as verified
     */
    private function markOTPAsVerified($otpId) {
        // This would update database
        return true;
    }
    
    /**
     * Update OTP record
     */
    private function updateOTP($otpId, $data) {
        // This would update database
        return true;
    }
}
?>