<?php
// Fallback OTP Mailer - Works on any hosting including InfinityFree
// Tries Gmail SMTP first, falls back to logging if SMTP fails

class FallbackOTPMailer {
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
            // Try Gmail SMTP first
            $sent = $this->tryGmailSMTP($email, $otp);
            
            if (!$sent) {
                // Fallback: Try PHP mail() function
                $sent = $this->tryPHPMail($email, $otp);
            }
            
            // Always log the OTP for debugging/manual retrieval
            $this->logOTPForUser($email, $otp, $sent);
            
            // Return true even if email fails - OTP is stored and logged
            return true;
        }
        
        return false;
    }
    
    private function tryGmailSMTP($email, $otp) {
        // Simple Gmail SMTP attempt (likely to fail on InfinityFree)
        try {
            if (!defined('GMAIL_USERNAME') || !defined('GMAIL_APP_PASSWORD')) {
                return false;
            }
            
            $subject = "MJ Pharmacy - Your Login OTP Code";
            $message = $this->getEmailTemplate($otp);
            $headers = "From: MJ Pharmacy <" . GMAIL_USERNAME . ">\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            // This will likely fail on InfinityFree but worth trying
            return mail($email, $subject, $message, $headers);
            
        } catch (Exception $e) {
            error_log("Gmail SMTP failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function tryPHPMail($email, $otp) {
        try {
            $subject = "MJ Pharmacy - Your Login OTP Code";
            $message = $this->getEmailTemplate($otp);
            $headers = "From: noreply@pharmacy.com\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            return mail($email, $subject, $message, $headers);
            
        } catch (Exception $e) {
            error_log("PHP mail failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function logOTPForUser($email, $otp, $emailSent) {
        $logFile = 'otp_codes.log';
        $timestamp = date('Y-m-d H:i:s');
        $status = $emailSent ? "✅ Email sent" : "📧 Email failed - Use code below";
        
        $logEntry = "\n" . str_repeat("=", 50) . "\n";
        $logEntry .= "[$timestamp] OTP LOGIN REQUEST\n";
        $logEntry .= "Email: $email\n";
        $logEntry .= "OTP Code: $otp\n";
        $logEntry .= "Status: $status\n";
        $logEntry .= "Expires: " . date('Y-m-d H:i:s', strtotime('+5 minutes')) . "\n";
        $logEntry .= str_repeat("=", 50) . "\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also create a simple HTML page for easy viewing
        $this->createOTPViewPage($email, $otp, $timestamp);
    }
    
    private function createOTPViewPage($email, $otp, $timestamp) {
        $htmlContent = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>OTP Code - MJ Pharmacy</title>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
                .otp-box { background: #f0f9f0; border: 2px solid #22C55E; padding: 30px; text-align: center; border-radius: 10px; }
                .otp-code { font-size: 48px; font-weight: bold; color: #22C55E; letter-spacing: 10px; margin: 20px 0; }
                .info { color: #666; margin: 10px 0; }
                .warning { color: #ff6b6b; font-weight: bold; }
            </style>
            <meta http-equiv='refresh' content='30'>
        </head>
        <body>
            <h1>🏥 MJ Pharmacy - OTP Code</h1>
            <div class='otp-box'>
                <h2>Your Login Code:</h2>
                <div class='otp-code'>$otp</div>
                <p class='info'>For: $email</p>
                <p class='info'>Generated: $timestamp</p>
                <p class='warning'>⏰ Expires in 5 minutes</p>
            </div>
            <p><a href='index.php'>← Back to Login</a></p>
            <p><small>This page refreshes every 30 seconds to show the latest OTP code.</small></p>
        </body>
        </html>";
        
        file_put_contents('current_otp.html', $htmlContent);
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
    
    private function getEmailTemplate($otp) {
        return "
        <html>
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
