<?php
// Optimized Mailer - Simple but effective email delivery
require_once 'gmail_config.php';

class OptimizedMailer {
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
            // Use the exact same format that worked in the test
            $sent = $this->sendOptimizedEmail($email, $otp);
            
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
    
    private function sendOptimizedEmail($email, $otp) {
        try {
            // Use the EXACT same format as the successful test email
            $subject = "MJ Pharmacy OTP Code - " . date('H:i:s');
            $message = $this->getOptimizedTemplate($otp);
            
            // Minimal headers - same as successful test
            $headers = "From: MJ Pharmacy <noreply@pharmacymj.com>\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            // Send with exact same method that worked
            $result = mail($email, $subject, $message, $headers);
            
            if ($result) {
                error_log("Optimized Email: Successfully sent OTP to $email");
                return true;
            } else {
                error_log("Optimized Email: Failed to send OTP to $email");
                return false;
            }
            
        } catch (Exception $e) {
            error_log("Optimized Email Exception: " . $e->getMessage());
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
        $status = $sent ? "✅ Optimized email sent successfully" : "❌ Email failed - use backup";
        
        $logEntry = "\n[$timestamp] Email: $email | OTP: $otp | Status: $status\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function getOptimizedTemplate($otp) {
        // Simple, clean template similar to successful test
        return "MJ Pharmacy - Login OTP Code

Your OTP Code: $otp

This code expires in 5 minutes.
Enter this code on the login page to access your pharmacy system.

If you didn't request this code, please ignore this email.

MJ Pharmacy System
pharmacymj.com";
    }
    
    public function testConnection() {
        // Test basic mail function
        if (!function_exists('mail')) {
            return "❌ PHP mail() function not available";
        }
        
        return "✅ Optimized mailer ready! Using proven email format.";
    }
}
?>
