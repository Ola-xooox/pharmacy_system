<?php
// Web-based Email Sender - Uses external email service
require_once 'gmail_config.php';

class WebEmailSender {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    public function generateOTP() {
        return sprintf("%06d", mt_rand(100000, 999999));
    }
    
    public function storeOTP($email, $otp) {
        $this->cleanupExpiredOTPs();
        $stmt = $this->conn->prepare("INSERT INTO otp_verification (email, otp_code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))");
        $stmt->bind_param("ss", $email, $otp);
        return $stmt->execute();
    }
    
    public function verifyOTP($email, $otp) {
        $stmt = $this->conn->prepare("
            SELECT id FROM otp_verification 
            WHERE email = ? AND otp_code = ? AND expires_at > NOW() AND is_used = 0
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->bind_param("ss", $email, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $this->markOTPAsUsed($row['id']);
            return true;
        }
        return false;
    }
    
    public function sendOTP($email, $password = null) {
        // If password is provided, verify user exists
        if ($password !== null) {
            $stmt = $this->conn->prepare("SELECT id, username FROM users WHERE email = ? AND password = ?");
            $stmt->bind_param("ss", $email, $password);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return false;
            }
        }
        
        $otp = $this->generateOTP();
        
        if ($this->storeOTP($email, $otp)) {
            // Try multiple email methods
            $sent = $this->sendViaFormspree($email, $otp);
            
            if (!$sent) {
                $sent = $this->sendViaEmailJS($email, $otp);
            }
            
            if (!$sent) {
                // Final fallback - basic mail with optimized headers
                $sent = $this->sendViaOptimizedMail($email, $otp);
            }
            
            // Always log for backup
            $this->logOTP($email, $otp, $sent);
            
            if ($sent) {
                return true; // Email sent successfully
            } else {
                return 'email_failed'; // OTP stored but email failed - use backup codes
            }
        }
        
        return false; // Failed to store OTP
    }
    
    private function sendViaFormspree($email, $otp) {
        // Formspree is a form backend service that can send emails
        try {
            $formspree_endpoint = "https://formspree.io/f/YOUR_FORM_ID"; // You need to set this up
            
            $data = array(
                'email' => $email,
                'subject' => 'MJ Pharmacy - Your Login OTP Code',
                'message' => $this->getPlainTextTemplate($otp),
                '_replyto' => GMAIL_USERNAME,
                '_subject' => 'MJ Pharmacy - Your Login OTP Code'
            );
            
            $options = array(
                'http' => array(
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data)
                )
            );
            
            $context = stream_context_create($options);
            $result = file_get_contents($formspree_endpoint, false, $context);
            
            if ($result !== false) {
                error_log("Formspree: Successfully sent OTP to $email");
                return true;
            } else {
                error_log("Formspree: Failed to send OTP to $email");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Formspree Exception: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendViaEmailJS($email, $otp) {
        // EmailJS sends emails from the client-side
        // This would require JavaScript implementation
        // For now, we'll skip this method in PHP
        return false;
    }
    
    private function sendViaOptimizedMail($email, $otp) {
        try {
            $subject = "MJ Pharmacy OTP: $otp";
            $message = $this->getPlainTextTemplate($otp);
            
            // Optimized headers for better deliverability
            $headers = array();
            $headers[] = "From: MJ Pharmacy <noreply@pharmacymj.com>";
            $headers[] = "Reply-To: " . GMAIL_USERNAME;
            $headers[] = "Return-Path: noreply@pharmacymj.com";
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "X-Mailer: MJ Pharmacy System";
            $headers[] = "X-Priority: 3";
            $headers[] = "Message-ID: <" . time() . "." . md5($email . $otp) . "@pharmacymj.com>";
            $headers[] = "Date: " . date('r');
            
            // Additional headers for better reputation
            $headers[] = "List-Unsubscribe: <mailto:unsubscribe@pharmacymj.com>";
            $headers[] = "X-Auto-Response-Suppress: All";
            
            $header_string = implode("\r\n", $headers);
            
            $result = mail($email, $subject, $message, $header_string);
            
            if ($result) {
                error_log("Optimized Mail: Successfully sent OTP to $email");
                return true;
            } else {
                error_log("Optimized Mail: Failed to send OTP to $email");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Optimized Mail Exception: " . $e->getMessage());
            return false;
        }
    }
    
    private function markOTPAsUsed($id) {
        $stmt = $this->conn->prepare("UPDATE otp_verification SET is_used = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }
    
    private function cleanupExpiredOTPs() {
        $stmt = $this->conn->prepare("DELETE FROM otp_verification WHERE expires_at < NOW()");
        $stmt->execute();
    }
    
    private function logOTP($email, $otp, $sent) {
        $logFile = 'otp_codes.log';
        $timestamp = date('Y-m-d H:i:s');
        $status = $sent ? "✅ Web email sent successfully" : "❌ Email failed - use backup";
        
        $logEntry = "\n[$timestamp] Email: $email | OTP: $otp | Status: $status\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function getPlainTextTemplate($otp) {
        return "
MJ PHARMACY - LOGIN OTP CODE

Your OTP Code: $otp

This code expires in 5 minutes.
Enter this code on the login page to access your account.

If you didn't request this code, please ignore this email.

© 2025 MJ Pharmacy System
pharmacymj.com

This email was sent from MJ Pharmacy's secure system.
        ";
    }
    
    public function testConnection() {
        // Test basic functionality
        if (!function_exists('mail')) {
            return "❌ PHP mail() function not available";
        }
        
        if (!function_exists('file_get_contents')) {
            return "❌ file_get_contents() not available for web services";
        }
        
        // Check Gmail configuration
        if (empty(GMAIL_USERNAME) || empty(GMAIL_APP_PASSWORD)) {
            return "❌ Gmail credentials not configured properly";
        }
        
        return "✅ Web email sender ready! Multiple delivery methods available.";
    }
}
?>
