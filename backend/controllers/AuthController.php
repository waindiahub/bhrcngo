<?php
/**
 * Authentication Controller
 * BHRC - Bharatiya Human Rights Council
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../services/OTPService.php';

class AuthController extends BaseController {
    private $userModel;
    private $emailService;
    private $otpService;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
        $this->emailService = new EmailService();
        $this->otpService = new OTPService();
    }
    
    /**
     * User login
     */
    public function login() {
        if (!$this->validateMethod('POST')) return;
        
        $email = $this->sanitizeInput($this->getInput('email'));
        $password = $this->getInput('password');
        $rememberMe = $this->getInput('remember_me', false);
        
        // Rate limiting
        if (!$this->checkRateLimit("login_{$email}", 5, 900)) return; // 5 attempts per 15 minutes
        
        // Validate input
        $errors = $this->validate([
            'email' => $email,
            'password' => $password
        ], [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);
        
        if (!empty($errors)) {
            $this->jsonError('Validation failed', 400, $errors);
            return;
        }
        
        // Authenticate user
        $result = $this->userModel->authenticate($email, $password);
        
        if (!$result['success']) {
            $this->logActivity('login_failed', ['email' => $email, 'reason' => $result['message']]);
            $this->jsonError($result['message'], 401);
            return;
        }
        
        $user = $result['user'];
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;
        $_SESSION['login_time'] = time();
        
        // Set remember me cookie
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true); // 30 days
            
            // Store token in database (you might want to create a remember_tokens table)
            // For now, we'll skip this implementation
        }
        
        $this->logActivity('login_success', ['user_id' => $user['id']]);
        
        $this->jsonSuccess('Login successful', [
            'user' => $user,
            'redirect' => $this->getRedirectUrl($user['role'])
        ]);
    }
    
    /**
     * User registration
     */
    public function register() {
        if (!$this->validateMethod('POST')) return;
        
        $data = [
            'name' => $this->sanitizeInput($this->getInput('name')),
            'email' => $this->sanitizeInput($this->getInput('email')),
            'phone' => $this->sanitizeInput($this->getInput('phone')),
            'password' => $this->getInput('password'),
            'confirm_password' => $this->getInput('confirm_password'),
            'role' => 'user' // Default role
        ];
        
        // Rate limiting
        if (!$this->checkRateLimit("register_{$data['email']}", 3, 3600)) return; // 3 attempts per hour
        
        // Validate input
        $errors = $this->validate($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|phone|max:20',
            'password' => 'required|min:8',
            'confirm_password' => 'required'
        ]);
        
        // Check password confirmation
        if ($data['password'] !== $data['confirm_password']) {
            $errors['confirm_password'] = 'Password confirmation does not match';
        }
        
        // Check password strength
        if (!$this->isStrongPassword($data['password'])) {
            $errors['password'] = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character';
        }
        
        if (!empty($errors)) {
            $this->jsonError('Validation failed', 400, $errors);
            return;
        }
        
        // Create user
        $result = $this->userModel->createUser($data);
        
        if (!$result['success']) {
            $this->jsonError('Registration failed', 400, $result['errors']);
            return;
        }
        
        $user = $result['user'];
        
        // Send verification email
        $this->sendVerificationEmail($user);
        
        $this->logActivity('user_registered', ['user_id' => $user['id']]);
        
        $this->jsonSuccess('Registration successful. Please check your email for verification link.', [
            'user_id' => $user['id']
        ]);
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        
        // Clear session
        session_destroy();
        
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }
        
        if ($userId) {
            $this->logActivity('logout', ['user_id' => $userId]);
        }
        
        $this->jsonSuccess('Logout successful');
    }
    
    /**
     * Verify email
     */
    public function verifyEmail() {
        $token = $this->getInput('token');
        
        if (!$token) {
            $this->jsonError('Verification token is required', 400);
            return;
        }
        
        // Verify token and get user
        $result = $this->otpService->verifyEmailToken($token);
        
        if (!$result['success']) {
            $this->jsonError($result['message'], 400);
            return;
        }
        
        $userId = $result['user_id'];
        
        // Update user email verification status
        if ($this->userModel->verifyEmail($userId)) {
            $this->logActivity('email_verified', ['user_id' => $userId]);
            $this->jsonSuccess('Email verified successfully');
        } else {
            $this->jsonError('Failed to verify email', 500);
        }
    }
    
    /**
     * Forgot password
     */
    public function forgotPassword() {
        if (!$this->validateMethod('POST')) return;
        
        $email = $this->sanitizeInput($this->getInput('email'));
        
        // Rate limiting
        if (!$this->checkRateLimit("forgot_password_{$email}", 3, 3600)) return; // 3 attempts per hour
        
        // Validate email
        $errors = $this->validate(['email' => $email], ['email' => 'required|email']);
        
        if (!empty($errors)) {
            $this->jsonError('Validation failed', 400, $errors);
            return;
        }
        
        // Check if user exists
        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            // Don't reveal if email exists or not
            $this->jsonSuccess('If the email exists, a password reset link has been sent.');
            return;
        }
        
        // Generate reset token
        $resetToken = $this->otpService->generatePasswordResetToken($user['id']);
        
        // Send reset email
        $this->sendPasswordResetEmail($user, $resetToken);
        
        $this->logActivity('password_reset_requested', ['user_id' => $user['id']]);
        
        $this->jsonSuccess('Password reset link has been sent to your email.');
    }
    
    /**
     * Reset password
     */
    public function resetPassword() {
        if (!$this->validateMethod('POST')) return;
        
        $token = $this->getInput('token');
        $password = $this->getInput('password');
        $confirmPassword = $this->getInput('confirm_password');
        
        // Validate input
        $errors = $this->validate([
            'token' => $token,
            'password' => $password,
            'confirm_password' => $confirmPassword
        ], [
            'token' => 'required',
            'password' => 'required|min:8',
            'confirm_password' => 'required'
        ]);
        
        // Check password confirmation
        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Password confirmation does not match';
        }
        
        // Check password strength
        if (!$this->isStrongPassword($password)) {
            $errors['password'] = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character';
        }
        
        if (!empty($errors)) {
            $this->jsonError('Validation failed', 400, $errors);
            return;
        }
        
        // Verify reset token
        $result = $this->otpService->verifyPasswordResetToken($token);
        
        if (!$result['success']) {
            $this->jsonError($result['message'], 400);
            return;
        }
        
        $userId = $result['user_id'];
        
        // Update password
        if ($this->userModel->updatePassword($userId, $password)) {
            // Invalidate all reset tokens for this user
            $this->otpService->invalidatePasswordResetTokens($userId);
            
            $this->logActivity('password_reset_completed', ['user_id' => $userId]);
            $this->jsonSuccess('Password reset successfully');
        } else {
            $this->jsonError('Failed to reset password', 500);
        }
    }
    
    /**
     * Change password (for authenticated users)
     */
    public function changePassword() {
        if (!$this->requireAuth()) return;
        if (!$this->validateMethod('POST')) return;
        
        $currentPassword = $this->getInput('current_password');
        $newPassword = $this->getInput('new_password');
        $confirmPassword = $this->getInput('confirm_password');
        
        $userId = $this->getCurrentUserId();
        
        // Validate input
        $errors = $this->validate([
            'current_password' => $currentPassword,
            'new_password' => $newPassword,
            'confirm_password' => $confirmPassword
        ], [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required'
        ]);
        
        // Check password confirmation
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Password confirmation does not match';
        }
        
        // Check password strength
        if (!$this->isStrongPassword($newPassword)) {
            $errors['new_password'] = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character';
        }
        
        if (!empty($errors)) {
            $this->jsonError('Validation failed', 400, $errors);
            return;
        }
        
        // Verify current password
        $user = $this->userModel->find($userId);
        if (!password_verify($currentPassword, $user['password_hash'])) {
            $this->jsonError('Current password is incorrect', 400);
            return;
        }
        
        // Update password
        if ($this->userModel->updatePassword($userId, $newPassword)) {
            $this->logActivity('password_changed', ['user_id' => $userId]);
            $this->jsonSuccess('Password changed successfully');
        } else {
            $this->jsonError('Failed to change password', 500);
        }
    }
    
    /**
     * Get current user info
     */
    public function me() {
        if (!$this->requireAuth()) return;
        
        $user = $this->getCurrentUser();
        $this->jsonSuccess('User info retrieved', ['user' => $user]);
    }
    
    /**
     * Check if password is strong
     */
    private function isStrongPassword($password) {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $password);
    }
    
    /**
     * Send verification email
     */
    private function sendVerificationEmail($user) {
        $token = $this->otpService->generateEmailVerificationToken($user['id']);
        $verificationUrl = SITE_URL . "/verify-email?token={$token}";
        
        $subject = 'Verify Your Email - BHRC';
        $message = "
            <h2>Email Verification</h2>
            <p>Dear {$user['name']},</p>
            <p>Thank you for registering with BHRC. Please click the link below to verify your email address:</p>
            <p><a href='{$verificationUrl}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Verify Email</a></p>
            <p>If you didn't create this account, please ignore this email.</p>
            <p>Best regards,<br>BHRC Team</p>
        ";
        
        $this->emailService->send($user['email'], $subject, $message);
    }
    
    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail($user, $token) {
        $resetUrl = SITE_URL . "/reset-password?token={$token}";
        
        $subject = 'Password Reset - BHRC';
        $message = "
            <h2>Password Reset</h2>
            <p>Dear {$user['name']},</p>
            <p>You have requested to reset your password. Please click the link below to reset your password:</p>
            <p><a href='{$resetUrl}' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
            <p>This link will expire in 1 hour.</p>
            <p>If you didn't request this, please ignore this email.</p>
            <p>Best regards,<br>BHRC Team</p>
        ";
        
        $this->emailService->send($user['email'], $subject, $message);
    }
    
    /**
     * Get redirect URL based on user role
     */
    private function getRedirectUrl($role) {
        switch ($role) {
            case 'admin':
                return '/admin/dashboard';
            case 'moderator':
                return '/moderator/dashboard';
            case 'member':
                return '/member/dashboard';
            default:
                return '/dashboard';
        }
    }
}
?>