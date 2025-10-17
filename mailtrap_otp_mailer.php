<?php
// Mailtrap OTP Mailer - Real Email Testing
require_once 'db_connect.php';
require_once 'mailtrap_config.php';

class MailtrapOTPMailer {
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
            $update_stmt = $this->conn->prepare("UPDATE otp_verification SET is_used = 1 WHERE id = ?");
            $update_stmt->bind_param("i", $row['id']);
            $update_stmt->execute();
            return true;
        }
        return false;
    }
    
    public function sendOTP($email, $otp) {
        // Log for backup
        $this->logOTP($email, $otp);
        
        // Check if Mailtrap is configured
        if (empty(MAILTRAP_USERNAME) || MAILTRAP_USERNAME === 'your-mailtrap-username') {
            $this->updateLog($email, $otp, "⚠️ Mailtrap not configured - using log only");
            return true;
        }
        
        // Send via Mailtrap
        if ($this->sendViaMailtrap($email, $otp)) {
            $this->updateLog($email, $otp, "✅ Email sent to Mailtrap inbox!");
            return true;
        }
        
        $this->updateLog($email, $otp, "❌ Mailtrap sending failed - use code from log");
        return true;
    }
    
    private function sendViaMailtrap($email, $otp) {
        $subject = "MJ Pharmacy - Your Login OTP Code";
        $message = $this->getEmailTemplate($otp);
        
        try {
            // Connect to Mailtrap SMTP
            $socket = fsockopen(MAILTRAP_HOST, MAILTRAP_PORT, $errno, $errstr, 30);
            if (!$socket) {
                error_log("Mailtrap connection failed: $errstr ($errno)");
                return false;
            }
            
            // Read greeting
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '220') {
                fclose($socket);
                return false;
            }
            
            // SMTP conversation
            $commands = [
                "EHLO localhost",
                "AUTH LOGIN",
                base64_encode(MAILTRAP_USERNAME),
                base64_encode(MAILTRAP_PASSWORD),
                "MAIL FROM: <lhandelpamisa0@gmail.com>",
                "RCPT TO: <$email>",
                "DATA"
            ];
            
            foreach ($commands as $command) {
                fputs($socket, $command . "\r\n");
                $response = fgets($socket, 512);
                
                // Check for errors
                if (substr($response, 0, 1) == '5') {
                    error_log("Mailtrap SMTP Error on '$command': $response");
                    fclose($socket);
                    return false;
                }
            }
            
            // Send email content
            $email_content = "From: MJ Pharmacy <lhandelpamisa0@gmail.com>\r\n";
            $email_content .= "To: $email\r\n";
            $email_content .= "Subject: $subject\r\n";
            $email_content .= "MIME-Version: 1.0\r\n";
            $email_content .= "Content-Type: text/html; charset=UTF-8\r\n";
            $email_content .= "\r\n";
            $email_content .= $message . "\r\n";
            $email_content .= ".\r\n";
            
            fputs($socket, $email_content);
            $response = fgets($socket, 512);
            
            // Quit
            fputs($socket, "QUIT\r\n");
            fclose($socket);
            
            return substr($response, 0, 3) == '250';
            
        } catch (Exception $e) {
            error_log("Mailtrap Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function logOTP($email, $otp) {
        $logFile = 'mailtrap_otp_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] 📧 Mailtrap OTP for $email: $otp\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function updateLog($email, $otp, $status) {
        $logFile = 'mailtrap_otp_log.txt';
        $logEntry = "   Status: $status\n";
        $logEntry .= "   Code: $otp (expires in 5 minutes)\n\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function getEmailTemplate($otp) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>MJ Pharmacy - OTP Verification</title>
        </head>
        <body style='font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #22C55E; margin: 0; font-size: 28px;'>🏥 MJ Pharmacy</h1>
                    <p style='color: #666; margin: 5px 0; font-size: 16px;'>Secure Login Verification</p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <h2 style='color: #333; margin-bottom: 20px;'>Your OTP Code</h2>
                    <div style='background: linear-gradient(135deg, #22C55E, #16A34A); color: white; padding: 25px; border-radius: 10px; margin: 20px 0;'>
                        <span style='font-size: 42px; font-weight: bold; letter-spacing: 10px;'>$otp</span>
                    </div>
                    <p style='color: #666; font-size: 14px; margin: 15px 0;'>
                        This code will expire in <strong>5 minutes</strong>
                    </p>
                </div>
                
                <div style='background-color: #e8f5e8; border-left: 4px solid #22C55E; padding: 15px; margin: 20px 0;'>
                    <p style='margin: 0; color: #2d5a2d; font-size: 14px;'>
                        <strong>📧 Email Testing:</strong> This email was sent via Mailtrap for development testing.
                    </p>
                </div>
                
                <div style='background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
                    <p style='margin: 0; color: #856404; font-size: 14px;'>
                        <strong>🔒 Security Notice:</strong> Never share this code with anyone. MJ Pharmacy staff will never ask for your OTP code.
                    </p>
                </div>
                
                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;'>
                    <p style='color: #999; font-size: 12px; margin: 0;'>
                        © 2025 MJ Pharmacy. All rights reserved.<br>
                        This is a test email sent via Mailtrap for development purposes.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    private function cleanupExpiredOTPs() {
        $stmt = $this->conn->prepare("DELETE FROM otp_verification WHERE expires_at < NOW()");
        $stmt->execute();
    }
    
    public function userExistsByEmail($email) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
