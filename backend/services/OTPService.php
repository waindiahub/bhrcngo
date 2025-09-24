<?php
/**
 * OTP Service
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/../models/BaseModel.php';

class OTPService extends BaseModel {
    protected $table = 'otp_verifications';
    protected $fillable = ['user_id', 'email', 'phone', 'otp', 'type', 'expires_at', 'verified', 'attempts'];
    
    /**
     * Generate OTP
     */
    public function generateOTP($length = 6) {
        return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate secure token
     */
    public function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Create OTP for email verification
     */
    public function createEmailOTP($email, $userId = null) {
        $otp = $this->generateOTP();
        $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 minutes
        
        // Invalidate previous OTPs
        $this->invalidateOTPs($email, 'email_verification');
        
        $data = [
            'user_id' => $userId,
            'email' => $email,
            'otp' => $otp,
            'type' => 'email_verification',
            'expires_at' => $expiresAt,
            'verified' => 0,
            'attempts' => 0
        ];
        
        $result = $this->create($data);
        
        if ($result) {
            return ['success' => true, 'otp' => $otp, 'id' => $result['id']];
        }
        
        return ['success' => false, 'message' => 'Failed to create OTP'];
    }
    
    /**
     * Create OTP for phone verification
     */
    public function createPhoneOTP($phone, $userId = null) {
        $otp = $this->generateOTP();
        $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60)); // 10 minutes
        
        // Invalidate previous OTPs
        $this->invalidateOTPs($phone, 'phone_verification', 'phone');
        
        $data = [
            'user_id' => $userId,
            'phone' => $phone,
            'otp' => $otp,
            'type' => 'phone_verification',
            'expires_at' => $expiresAt,
            'verified' => 0,
            'attempts' => 0
        ];
        
        $result = $this->create($data);
        
        if ($result) {
            return ['success' => true, 'otp' => $otp, 'id' => $result['id']];
        }
        
        return ['success' => false, 'message' => 'Failed to create OTP'];
    }
    
    /**
     * Verify email OTP
     */
    public function verifyEmailOTP($email, $otp) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE email = ? AND otp = ? AND type = 'email_verification' 
                AND verified = 0 AND expires_at > NOW() 
                ORDER BY created_at DESC LIMIT 1";
        
        $otpRecord = dbFetch($sql, [$email, $otp]);
        
        if (!$otpRecord) {
            return ['success' => false, 'message' => 'Invalid or expired OTP'];
        }
        
        // Check attempts
        if ($otpRecord['attempts'] >= 3) {
            return ['success' => false, 'message' => 'Maximum verification attempts exceeded'];
        }
        
        // Mark as verified
        $this->update($otpRecord['id'], [
            'verified' => 1,
            'verified_at' => date('Y-m-d H:i:s')
        ]);
        
        return [
            'success' => true, 
            'message' => 'OTP verified successfully',
            'user_id' => $otpRecord['user_id']
        ];
    }
    
    /**
     * Verify phone OTP
     */
    public function verifyPhoneOTP($phone, $otp) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE phone = ? AND otp = ? AND type = 'phone_verification' 
                AND verified = 0 AND expires_at > NOW() 
                ORDER BY created_at DESC LIMIT 1";
        
        $otpRecord = dbFetch($sql, [$phone, $otp]);
        
        if (!$otpRecord) {
            return ['success' => false, 'message' => 'Invalid or expired OTP'];
        }
        
        // Check attempts
        if ($otpRecord['attempts'] >= 3) {
            return ['success' => false, 'message' => 'Maximum verification attempts exceeded'];
        }
        
        // Mark as verified
        $this->update($otpRecord['id'], [
            'verified' => 1,
            'verified_at' => date('Y-m-d H:i:s')
        ]);
        
        return [
            'success' => true, 
            'message' => 'OTP verified successfully',
            'user_id' => $otpRecord['user_id']
        ];
    }
    
    /**
     * Increment verification attempts
     */
    public function incrementAttempts($email, $otp, $type = 'email_verification') {
        $field = $type === 'phone_verification' ? 'phone' : 'email';
        
        $sql = "UPDATE {$this->table} 
                SET attempts = attempts + 1 
                WHERE {$field} = ? AND otp = ? AND type = ? AND verified = 0";
        
        dbQuery($sql, [$email, $otp, $type]);
    }
    
    /**
     * Generate email verification token
     */
    public function generateEmailVerificationToken($userId) {
        $token = $this->generateToken();
        $expiresAt = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
        
        // Invalidate previous tokens
        $this->invalidateTokens($userId, 'email_verification');
        
        $data = [
            'user_id' => $userId,
            'otp' => $token,
            'type' => 'email_verification',
            'expires_at' => $expiresAt,
            'verified' => 0,
            'attempts' => 0
        ];
        
        $result = $this->create($data);
        
        return $result ? $token : null;
    }
    
    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken($userId) {
        $token = $this->generateToken();
        $expiresAt = date('Y-m-d H:i:s', time() + (60 * 60)); // 1 hour
        
        // Invalidate previous tokens
        $this->invalidateTokens($userId, 'password_reset');
        
        $data = [
            'user_id' => $userId,
            'otp' => $token,
            'type' => 'password_reset',
            'expires_at' => $expiresAt,
            'verified' => 0,
            'attempts' => 0
        ];
        
        $result = $this->create($data);
        
        return $result ? $token : null;
    }
    
    /**
     * Verify email verification token
     */
    public function verifyEmailToken($token) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE otp = ? AND type = 'email_verification' 
                AND verified = 0 AND expires_at > NOW() 
                ORDER BY created_at DESC LIMIT 1";
        
        $tokenRecord = dbFetch($sql, [$token]);
        
        if (!$tokenRecord) {
            return ['success' => false, 'message' => 'Invalid or expired verification token'];
        }
        
        // Mark as verified
        $this->update($tokenRecord['id'], [
            'verified' => 1,
            'verified_at' => date('Y-m-d H:i:s')
        ]);
        
        return [
            'success' => true, 
            'message' => 'Email verified successfully',
            'user_id' => $tokenRecord['user_id']
        ];
    }
    
    /**
     * Verify password reset token
     */
    public function verifyPasswordResetToken($token) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE otp = ? AND type = 'password_reset' 
                AND verified = 0 AND expires_at > NOW() 
                ORDER BY created_at DESC LIMIT 1";
        
        $tokenRecord = dbFetch($sql, [$token]);
        
        if (!$tokenRecord) {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }
        
        return [
            'success' => true, 
            'message' => 'Token is valid',
            'user_id' => $tokenRecord['user_id'],
            'token_id' => $tokenRecord['id']
        ];
    }
    
    /**
     * Invalidate password reset tokens for user
     */
    public function invalidatePasswordResetTokens($userId) {
        $sql = "UPDATE {$this->table} 
                SET verified = 1, verified_at = NOW() 
                WHERE user_id = ? AND type = 'password_reset' AND verified = 0";
        
        dbQuery($sql, [$userId]);
    }
    
    /**
     * Create login OTP (for 2FA)
     */
    public function createLoginOTP($userId, $email) {
        $otp = $this->generateOTP();
        $expiresAt = date('Y-m-d H:i:s', time() + (5 * 60)); // 5 minutes
        
        // Invalidate previous login OTPs
        $this->invalidateOTPs($email, 'login_verification');
        
        $data = [
            'user_id' => $userId,
            'email' => $email,
            'otp' => $otp,
            'type' => 'login_verification',
            'expires_at' => $expiresAt,
            'verified' => 0,
            'attempts' => 0
        ];
        
        $result = $this->create($data);
        
        if ($result) {
            return ['success' => true, 'otp' => $otp, 'id' => $result['id']];
        }
        
        return ['success' => false, 'message' => 'Failed to create login OTP'];
    }
    
    /**
     * Verify login OTP
     */
    public function verifyLoginOTP($userId, $otp) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE user_id = ? AND otp = ? AND type = 'login_verification' 
                AND verified = 0 AND expires_at > NOW() 
                ORDER BY created_at DESC LIMIT 1";
        
        $otpRecord = dbFetch($sql, [$userId, $otp]);
        
        if (!$otpRecord) {
            return ['success' => false, 'message' => 'Invalid or expired OTP'];
        }
        
        // Check attempts
        if ($otpRecord['attempts'] >= 3) {
            return ['success' => false, 'message' => 'Maximum verification attempts exceeded'];
        }
        
        // Mark as verified
        $this->update($otpRecord['id'], [
            'verified' => 1,
            'verified_at' => date('Y-m-d H:i:s')
        ]);
        
        return ['success' => true, 'message' => 'Login OTP verified successfully'];
    }
    
    /**
     * Invalidate OTPs
     */
    private function invalidateOTPs($identifier, $type, $field = 'email') {
        $sql = "UPDATE {$this->table} 
                SET verified = 1, verified_at = NOW() 
                WHERE {$field} = ? AND type = ? AND verified = 0";
        
        dbQuery($sql, [$identifier, $type]);
    }
    
    /**
     * Invalidate tokens for user
     */
    private function invalidateTokens($userId, $type) {
        $sql = "UPDATE {$this->table} 
                SET verified = 1, verified_at = NOW() 
                WHERE user_id = ? AND type = ? AND verified = 0";
        
        dbQuery($sql, [$userId, $type]);
    }
    
    /**
     * Clean expired OTPs
     */
    public function cleanExpiredOTPs() {
        $sql = "DELETE FROM {$this->table} WHERE expires_at < NOW()";
        $stmt = dbQuery($sql);
        return $stmt->rowCount();
    }
    
    /**
     * Get OTP statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total OTPs generated today
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE DATE(created_at) = CURDATE()";
        $result = dbFetch($sql);
        $stats['today_generated'] = $result ? (int)$result['count'] : 0;
        
        // Total OTPs verified today
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE DATE(verified_at) = CURDATE() AND verified = 1";
        $result = dbFetch($sql);
        $stats['today_verified'] = $result ? (int)$result['count'] : 0;
        
        // Success rate
        if ($stats['today_generated'] > 0) {
            $stats['success_rate'] = round(($stats['today_verified'] / $stats['today_generated']) * 100, 2);
        } else {
            $stats['success_rate'] = 0;
        }
        
        // OTPs by type
        $types = ['email_verification', 'phone_verification', 'password_reset', 'login_verification'];
        foreach ($types as $type) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE type = ? AND DATE(created_at) = CURDATE()";
            $result = dbFetch($sql, [$type]);
            $stats['by_type'][$type] = $result ? (int)$result['count'] : 0;
        }
        
        // Expired OTPs
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE expires_at < NOW() AND verified = 0";
        $result = dbFetch($sql);
        $stats['expired'] = $result ? (int)$result['count'] : 0;
        
        return $stats;
    }
    
    /**
     * Resend OTP
     */
    public function resendOTP($identifier, $type, $field = 'email') {
        // Check if last OTP was sent less than 1 minute ago
        $sql = "SELECT created_at FROM {$this->table} 
                WHERE {$field} = ? AND type = ? 
                ORDER BY created_at DESC LIMIT 1";
        
        $lastOTP = dbFetch($sql, [$identifier, $type]);
        
        if ($lastOTP && (time() - strtotime($lastOTP['created_at'])) < 60) {
            return ['success' => false, 'message' => 'Please wait before requesting another OTP'];
        }
        
        // Generate new OTP
        if ($field === 'email') {
            return $this->createEmailOTP($identifier);
        } else {
            return $this->createPhoneOTP($identifier);
        }
    }
    
    /**
     * Check if OTP can be resent
     */
    public function canResendOTP($identifier, $type, $field = 'email') {
        $sql = "SELECT created_at FROM {$this->table} 
                WHERE {$field} = ? AND type = ? 
                ORDER BY created_at DESC LIMIT 1";
        
        $lastOTP = dbFetch($sql, [$identifier, $type]);
        
        if (!$lastOTP) {
            return true;
        }
        
        return (time() - strtotime($lastOTP['created_at'])) >= 60; // 1 minute cooldown
    }
    
    /**
     * Get remaining time for resend
     */
    public function getResendCooldown($identifier, $type, $field = 'email') {
        $sql = "SELECT created_at FROM {$this->table} 
                WHERE {$field} = ? AND type = ? 
                ORDER BY created_at DESC LIMIT 1";
        
        $lastOTP = dbFetch($sql, [$identifier, $type]);
        
        if (!$lastOTP) {
            return 0;
        }
        
        $elapsed = time() - strtotime($lastOTP['created_at']);
        $cooldown = 60 - $elapsed;
        
        return max(0, $cooldown);
    }
}
?>