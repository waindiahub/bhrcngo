use PHPMailer\PHPMailer\PHPMailer;
<?php
/**
 * Email Service
 * BHRC - Bharatiya Human Rights Council
 */

// Include PHPMailer if available
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

// Define SITE_URL if not already defined
if (!defined('SITE_URL')) {
    define('SITE_URL', $_ENV['SITE_URL'] ?? 'http://localhost:8000');
}

class EmailService {
    private $config;
    
    public function __construct() {
        $this->config = [
            'smtp_host' => defined('SMTP_HOST') ? SMTP_HOST : 'localhost',
            'smtp_port' => defined('SMTP_PORT') ? SMTP_PORT : 587,
            'smtp_username' => defined('SMTP_USERNAME') ? SMTP_USERNAME : '',
            'smtp_password' => defined('SMTP_PASSWORD') ? SMTP_PASSWORD : '',
            'smtp_encryption' => defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls',
            'from_email' => defined('FROM_EMAIL') ? FROM_EMAIL : 'noreply@bhrcindia.in',
            'from_name' => defined('FROM_NAME') ? FROM_NAME : 'BHRC India'
        ];
    }
    
    /**
     * Send email
     */
    public function send($to, $subject, $message, $attachments = []) {
        try {
            // Use PHPMailer if available, otherwise use mail() function
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return $this->sendWithPHPMailer($to, $subject, $message, $attachments);
            } else {
                return $this->sendWithMailFunction($to, $subject, $message);
            }
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email using PHPMailer
     */
    private function sendWithPHPMailer($to, $subject, $message, $attachments = []) {
        /** @var PHPMailer $mail */
        /** @var \PHPMailer\PHPMailer\PHPMailer $mail */
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_encryption'];
            $mail->Port = $this->config['smtp_port'];
            
            // Recipients
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            
            if (is_array($to)) {
                foreach ($to as $email) {
                    $mail->addAddress($email);
                }
            } else {
                $mail->addAddress($to);
            }
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $this->wrapInTemplate($message, $subject);
            $mail->AltBody = strip_tags($message);
            
            // Attachments
            foreach ($attachments as $attachment) {
                if (file_exists($attachment)) {
                    $mail->addAttachment($attachment);
                }
            }
            
            $mail->send();
            return true;
            
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Send email using mail() function
     */
    private function sendWithMailFunction($to, $subject, $message) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
            'Reply-To: ' . $this->config['from_email'],
            'X-Mailer: PHP/' . phpversion()
        ];
        
        $wrappedMessage = $this->wrapInTemplate($message, $subject);
        
        return mail($to, $subject, $wrappedMessage, implode("\r\n", $headers));
    }
    
    /**
     * Wrap message in HTML template
     */
    private function wrapInTemplate($message, $subject) {
        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-radius: 10px 10px 0 0;
                }
                .content {
                    background: #f8f9fa;
                    padding: 30px;
                    border-radius: 0 0 10px 10px;
                }
                .footer {
                    text-align: center;
                    margin-top: 20px;
                    padding: 20px;
                    background: #e9ecef;
                    border-radius: 10px;
                    font-size: 14px;
                    color: #666;
                }
                .logo {
                    font-size: 24px;
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                a {
                    color: #007bff;
                    text-decoration: none;
                }
                a:hover {
                    text-decoration: underline;
                }
                .btn {
                    display: inline-block;
                    background: #007bff;
                    color: white !important;
                    padding: 12px 24px;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 10px 0;
                }
                .btn:hover {
                    background: #0056b3;
                    text-decoration: none;
                }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='logo'>BHRC India</div>
                <div>Bharatiya Human Rights Council</div>
            </div>
            <div class='content'>
                {$message}
            </div>
            <div class='footer'>
                <p>This email was sent by BHRC India (Bharatiya Human Rights Council)</p>
                <p>If you have any questions, please contact us at info@bhrcindia.in</p>
                <p>&copy; " . date('Y') . " BHRC India. All rights reserved.</p>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Send welcome email
     */
    public function sendWelcomeEmail($user) {
        $subject = 'Welcome to BHRC India';
        $message = "
            <h2>Welcome to BHRC India!</h2>
            <p>Dear {$user['name']},</p>
            <p>Thank you for joining the Bharatiya Human Rights Council. We are committed to protecting and promoting human rights across India.</p>
            <p>Your account has been successfully created. You can now:</p>
            <ul>
                <li>Apply for membership</li>
                <li>File complaints</li>
                <li>Participate in our activities</li>
                <li>Stay updated with our newsletter</li>
            </ul>
            <p>If you have any questions, please don't hesitate to contact us.</p>
            <p>Best regards,<br>BHRC Team</p>
        ";
        
        return $this->send($user['email'], $subject, $message);
    }
    
    /**
     * Send membership approval email
     */
    public function sendMembershipApprovalEmail($member) {
        $subject = 'Membership Approved - BHRC India';
        $message = "
            <h2>Congratulations! Your Membership has been Approved</h2>
            <p>Dear {$member['full_name']},</p>
            <p>We are pleased to inform you that your membership application has been approved.</p>
            <p><strong>Membership Details:</strong></p>
            <ul>
                <li>Membership ID: {$member['membership_id']}</li>
                <li>Membership Type: " . ucfirst($member['membership_type']) . "</li>
                <li>Status: Approved</li>
            </ul>
            <p>You will receive your membership certificate shortly.</p>
            <p>Thank you for joining us in our mission to protect human rights.</p>
            <p>Best regards,<br>BHRC Team</p>
        ";
        
        return $this->send($member['email'], $subject, $message);
    }
    
    /**
     * Send complaint acknowledgment email
     */
    public function sendComplaintAcknowledgment($complaint) {
        $subject = 'Complaint Received - BHRC India';
        $message = "
            <h2>Your Complaint has been Received</h2>
            <p>Dear {$complaint['complainant_name']},</p>
            <p>Thank you for bringing this matter to our attention. We have received your complaint and it is being reviewed by our team.</p>
            <p><strong>Complaint Details:</strong></p>
            <ul>
                <li>Complaint ID: {$complaint['complaint_id']}</li>
                <li>Subject: {$complaint['subject']}</li>
                <li>Date Submitted: " . date('F j, Y', strtotime($complaint['created_at'])) . "</li>
                <li>Status: Under Review</li>
            </ul>
            <p>We will investigate your complaint thoroughly and keep you updated on the progress.</p>
            <p>If you have any additional information, please reply to this email.</p>
            <p>Best regards,<br>BHRC Team</p>
        ";
        
        return $this->send($complaint['email'], $subject, $message);
    }
    
    /**
     * Send newsletter
     */
    public function sendNewsletter($subscribers, $subject, $content) {
        $message = "
            <h2>{$subject}</h2>
            {$content}
            <hr>
            <p><small>You are receiving this email because you subscribed to BHRC India newsletter. 
            <a href='" . SITE_URL . "/unsubscribe?email={{EMAIL}}'>Unsubscribe</a></small></p>
        ";
        
        $successCount = 0;
        foreach ($subscribers as $subscriber) {
            $personalizedMessage = str_replace('{{EMAIL}}', $subscriber['email'], $message);
            if ($this->send($subscriber['email'], $subject, $personalizedMessage)) {
                $successCount++;
            }
        }
        
        return $successCount;
    }
    
    /**
     * Send contact inquiry response
     */
    public function sendContactResponse($inquiry, $response) {
        $subject = 'Response to Your Inquiry - BHRC India';
        $message = "
            <h2>Response to Your Inquiry</h2>
            <p>Dear {$inquiry['name']},</p>
            <p>Thank you for contacting BHRC India. Here is our response to your inquiry:</p>
            <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;'>
                <strong>Your Message:</strong><br>
                {$inquiry['message']}
            </div>
            <div style='background: #e8f5e8; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>
                <strong>Our Response:</strong><br>
                {$response}
            </div>
            <p>If you have any further questions, please don't hesitate to contact us.</p>
            <p>Best regards,<br>BHRC Team</p>
        ";
        
        return $this->send($inquiry['email'], $subject, $message);
    }
    
    /**
     * Send OTP email
     */
    public function sendOTPEmail($email, $otp, $purpose = 'verification') {
        $subject = 'OTP for ' . ucfirst($purpose) . ' - BHRC India';
        $message = "
            <h2>One-Time Password (OTP)</h2>
            <p>Your OTP for {$purpose} is:</p>
            <div style='text-align: center; margin: 20px 0;'>
                <span style='font-size: 32px; font-weight: bold; color: #007bff; background: #f8f9fa; padding: 15px 30px; border-radius: 10px; letter-spacing: 5px;'>{$otp}</span>
            </div>
            <p>This OTP is valid for 10 minutes only.</p>
            <p>If you didn't request this OTP, please ignore this email.</p>
            <p>Best regards,<br>BHRC Team</p>
        ";
        
        return $this->send($email, $subject, $message);
    }
    
    /**
     * Send bulk email
     */
    public function sendBulk($recipients, $subject, $message, $batchSize = 50) {
        $batches = array_chunk($recipients, $batchSize);
        $totalSent = 0;
        
        foreach ($batches as $batch) {
            foreach ($batch as $recipient) {
                if ($this->send($recipient, $subject, $message)) {
                    $totalSent++;
                }
                
                // Small delay to prevent overwhelming the server
                usleep(100000); // 0.1 second
            }
            
            // Longer delay between batches
            sleep(1);
        }
        
        return $totalSent;
    }
    
    /**
     * Validate email address
     */
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Get email template
     */
    public function getTemplate($templateName, $variables = []) {
        $templatePath = __DIR__ . "/../templates/email/{$templateName}.php";
        
        if (file_exists($templatePath)) {
            ob_start();
            extract($variables);
            include $templatePath;
            return ob_get_clean();
        }
        
        return null;
    }
    
    /**
     * Send donation confirmation email
     */
    public function sendDonationConfirmation($donation) {
        $subject = "Thank you for your donation - BHRC India";
        $message = $this->getTemplate('donation_confirmation', ['donation' => $donation]);
        
        if (!$message) {
            // Fallback message if template doesn't exist
            $message = "
                <h2>Thank you for your donation!</h2>
                <p>Dear {$donation['donor_name']},</p>
                <p>Thank you for your generous donation of ₹{$donation['amount']} to BHRC India.</p>
                <p>Your donation ID is: {$donation['donation_id']}</p>
                <p>We appreciate your support in our mission.</p>
                <p>Best regards,<br>BHRC India Team</p>
            ";
        }
        
        return $this->send($donation['donor_email'], $subject, $message);
    }
    
    /**
     * Send event registration confirmation email
     */
    public function sendEventRegistrationConfirmation($email, $name, $event) {
        $subject = "Event Registration Confirmation - " . $event['title'];
        $message = $this->getTemplate('event_registration_confirmation', [
            'name' => $name,
            'event' => $event
        ]);
        
        if (!$message) {
            // Fallback message if template doesn't exist
            $message = "
                <h2>Event Registration Confirmation</h2>
                <p>Dear {$name},</p>
                <p>Thank you for registering for the event: <strong>{$event['title']}</strong></p>
                <p><strong>Event Details:</strong></p>
                <ul>
                    <li>Date: {$event['event_date']}</li>
                    <li>Time: {$event['event_time']}</li>
                    <li>Location: {$event['location']}</li>
                </ul>
                <p>We look forward to seeing you at the event!</p>
                <p>Best regards,<br>BHRC India Team</p>
            ";
        }
        
        return $this->send($email, $subject, $message);
    }
    
    /**
     * Send gallery application notification
     */
    public function sendGalleryApplicationNotification($data) {
        try {
            $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@bhrcindia.in';
            $subject = 'New Gallery Application Submitted';
            
            $message = "
                <h2>New Gallery Application</h2>
                <p>A new gallery application has been submitted with the following details:</p>
                
                <table style='border-collapse: collapse; width: 100%;'>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px; font-weight: bold;'>Name:</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($data['name']) . "</td>
                    </tr>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px; font-weight: bold;'>Email:</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($data['email']) . "</td>
                    </tr>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px; font-weight: bold;'>Title:</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($data['title']) . "</td>
                    </tr>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px; font-weight: bold;'>Description:</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>" . nl2br(htmlspecialchars($data['description'])) . "</td>
                    </tr>
                    <tr>
                        <td style='border: 1px solid #ddd; padding: 8px; font-weight: bold;'>Submitted:</td>
                        <td style='border: 1px solid #ddd; padding: 8px;'>" . date('Y-m-d H:i:s') . "</td>
                    </tr>
                </table>
                
                <p>Please review this application in the admin panel.</p>
                <p>Best regards,<br>BHRC System</p>
            ";
            
            return $this->send($adminEmail, $subject, $message);
        } catch (Exception $e) {
            error_log("Gallery application notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email (alias for send method)
     */
    public function sendEmail($to, $subject, $message, $attachments = []) {
        return $this->send($to, $subject, $message, $attachments);
    }
}
?>