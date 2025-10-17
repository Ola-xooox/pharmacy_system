<?php
// Gmail Mailer using cURL - More compatible with hosting restrictions
require_once 'gmail_config.php';

class GmailCurlMailer {
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
    
    public function sendOTP($email, $password) {
        // Verify user exists and password is correct
        $stmt = $this->conn->prepare("SELECT id, username FROM users WHERE email = ? AND password = ?");
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $otp = $this->generateOTP();
        
        if ($this->storeOTP($email, $otp)) {
            // Try multiple email methods
            $sent = $this->tryMultipleEmailMethods($email, $otp);
            
            // Always log for backup
            $this->logOTP($email, $otp, $sent);
            
            return $sent;
        }
        
        return false;
    }
    
    private function tryMultipleEmailMethods($email, $otp) {
        $subject = "MJ Pharmacy - Your Login OTP Code";
        $message = $this->getEmailTemplate($otp);
        
        // Method 1: Try PHP mail with Gmail headers
        if ($this->tryPHPMailWithGmail($email, $subject, $message)) {
            return true;
        }
        
        // Method 2: Try cURL with Gmail SMTP (if available)
        if ($this->tryCurlEmail($email, $subject, $message)) {
            return true;
        }
        
        // Method 3: Try basic PHP mail
        if ($this->tryBasicPHPMail($email, $subject, $message)) {
            return true;
        }
        
        return false;
    }
    
    private function tryPHPMailWithGmail($email, $subject, $message) {
        try {
            $headers = "From: MJ Pharmacy <" . GMAIL_USERNAME . ">\r\n";
            $headers .= "Reply-To: " . GMAIL_USERNAME . "\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            
            return mail($email, $subject, $message, $headers);
        } catch (Exception $e) {
            error_log("PHP Mail with Gmail failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function tryCurlEmail($email, $subject, $message) {
        // This method tries to use external email service via cURL
        // You can integrate with services like EmailJS, Formspree, etc.
        return false; // Placeholder for now
    }
    
    private function tryBasicPHPMail($email, $subject, $message) {
        try {
            $headers = "From: noreply@mjpharmacy.com\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            return mail($email, $subject, $message, $headers);
        } catch (Exception $e) {
            error_log("Basic PHP Mail failed: " . $e->getMessage());
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
        $status = $sent ? "✅ Email sent successfully" : "❌ Email failed - use backup";
        
        $logEntry = "\n[$timestamp] Email: $email | OTP: $otp | Status: $status\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function getEmailTemplate($otp) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>MJ Pharmacy OTP</title>
        </head>
        <body style='font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #22C55E; margin: 0; font-size: 28px;'>🏥 MJ Pharmacy</h1>
                    <p style='color: #666; margin: 5px 0; font-size: 16px;'>Secure Login Verification</p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <p style='color: #333; font-size: 18px; margin-bottom: 20px;'>Your OTP Code:</p>
                    <div style='font-size: 36px; font-weight: bold; color: #22C55E; background-color: #f0f9f0; padding: 25px; border-radius: 12px; display: inline-block; letter-spacing: 8px; border: 2px solid #22C55E;'>
                        $otp
                    </div>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <p style='color: #666; font-size: 16px; margin: 10px 0;'>⏰ This code expires in <strong>5 minutes</strong></p>
                    <p style='color: #999; font-size: 14px;'>If you didn't request this code, please ignore this email.</p>
                </div>
                
                <div style='text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;'>
                    <p style='color: #999; font-size: 12px; margin: 0;'>© 2025 MJ Pharmacy System</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>
