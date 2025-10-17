<?php
// Gmail OTP Mailer - Uses Gmail SMTP to send OTP codes
require_once 'gmail_config.php';

class GmailOTPMailer {
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
            // Send via Gmail SMTP
            $sent = $this->sendViaGmail($email, $otp);
            
            // Always log for debugging
            $status = $sent ? "✅ Sent via Gmail SMTP" : "❌ Gmail SMTP failed - check logs";
            $this->updateLog($email, $otp, $status);
            
            return $sent;
        }
        
        return false;
    }
    
    private function sendViaGmail($email, $otp) {
        $subject = "MJ Pharmacy - Your Login OTP Code";
        $message = $this->getEmailTemplate($otp);
        
        try {
            // Connect to Gmail SMTP
            $socket = fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 30);
            if (!$socket) {
                error_log("Gmail SMTP connection failed: $errstr ($errno)");
                return false;
            }
            
            // Enable TLS encryption
            stream_context_set_option($socket, 'ssl', 'verify_peer', false);
            stream_context_set_option($socket, 'ssl', 'verify_peer_name', false);
            
            // Read greeting
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '220') {
                fclose($socket);
                return false;
            }
            
            // Start TLS
            fputs($socket, "STARTTLS\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != '220') {
                fclose($socket);
                return false;
            }
            
            // Enable crypto
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return false;
            }
            
            // SMTP conversation
            $commands = [
                "EHLO " . $_SERVER['SERVER_NAME'] ?? 'localhost',
                "AUTH LOGIN",
                base64_encode(GMAIL_USERNAME),
                base64_encode(GMAIL_APP_PASSWORD),
                "MAIL FROM: <" . GMAIL_USERNAME . ">",
                "RCPT TO: <$email>",
                "DATA"
            ];
            
            foreach ($commands as $command) {
                fputs($socket, $command . "\r\n");
                $response = fgets($socket, 512);
                
                // Check for errors
                if (substr($response, 0, 1) == '5') {
                    error_log("Gmail SMTP Error on '$command': $response");
                    fclose($socket);
                    return false;
                }
            }
            
            // Send email content
            $email_content = "From: MJ Pharmacy <" . GMAIL_USERNAME . ">\r\n";
            $email_content .= "To: $email\r\n";
            $email_content .= "Subject: $subject\r\n";
            $email_content .= "Content-Type: text/html; charset=UTF-8\r\n";
            $email_content .= "\r\n";
            $email_content .= $message;
            $email_content .= "\r\n.\r\n";
            
            fputs($socket, $email_content);
            $response = fgets($socket, 512);
            
            // Send QUIT
            fputs($socket, "QUIT\r\n");
            fclose($socket);
            
            return substr($response, 0, 3) == '250';
            
        } catch (Exception $e) {
            error_log("Gmail SMTP Exception: " . $e->getMessage());
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
    
    private function updateLog($email, $otp, $status) {
        $logFile = 'otp_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] Email: $email | OTP: $otp | Status: $status\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
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
