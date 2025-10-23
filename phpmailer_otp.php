<?php
// PHPMailer OTP System - Professional email delivery using PHPMailer
require_once 'gmail_config.php';
require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class PHPMailerOTP {
    private $conn;
    private $mail;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->initializeMailer();
    }
    
    private function initializeMailer() {
        $this->mail = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host       = SMTP_HOST;
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = GMAIL_USERNAME;
            $this->mail->Password   = GMAIL_APP_PASSWORD;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port       = SMTP_PORT;
            
            // Recipients
            $this->mail->setFrom(GMAIL_USERNAME, 'MJ Pharmacy System');
            
            // Content
            $this->mail->isHTML(true);
            $this->mail->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            error_log("PHPMailer initialization error: " . $e->getMessage());
        }
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
            $stmt = $this->conn->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return false;
            }
        }
        
        $otp = $this->generateOTP();
        
        if ($this->storeOTP($email, $otp)) {
            $sent = $this->sendPHPMailerEmail($email, $otp);
            
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
    
    private function sendPHPMailerEmail($email, $otp) {
        try {
            // Clear any previous recipients
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            
            // Set recipient
            $this->mail->addAddress($email);
            
            // Content
            $this->mail->Subject = 'MJ Pharmacy - Your Login OTP Code';
            $this->mail->Body    = $this->getHTMLTemplate($otp);
            $this->mail->AltBody = $this->getPlainTextTemplate($otp);
            
            $result = $this->mail->send();
            
            if ($result) {
                error_log("PHPMailer: Successfully sent OTP to $email");
                return true;
            } else {
                error_log("PHPMailer: Failed to send OTP to $email - " . $this->mail->ErrorInfo);
                return false;
            }
            
        } catch (Exception $e) {
            error_log("PHPMailer Exception: " . $e->getMessage());
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
        $status = $sent ? "✅ PHPMailer email sent successfully" : "❌ PHPMailer email failed - use backup";
        
        $logEntry = "\n[$timestamp] Email: $email | OTP: $otp | Status: $status\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function getHTMLTemplate($otp) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>MJ Pharmacy - OTP Code</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { text-align: center; border-bottom: 2px solid #4CAF50; padding-bottom: 20px; margin-bottom: 30px; }
                .logo { font-size: 24px; font-weight: bold; color: #4CAF50; margin-bottom: 10px; }
                .otp-code { background: #f8f9fa; border: 2px dashed #4CAF50; padding: 20px; text-align: center; margin: 30px 0; border-radius: 8px; }
                .otp-number { font-size: 36px; font-weight: bold; color: #2E7D32; letter-spacing: 8px; font-family: 'Courier New', monospace; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; font-size: 12px; }
                .btn { display: inline-block; padding: 12px 24px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>🏥 MJ Pharmacy</div>
                    <h2 style='margin: 0; color: #333;'>Login Verification Code</h2>
                </div>
                
                <p>Hello,</p>
                <p>You have requested to log in to your MJ Pharmacy account. Please use the following One-Time Password (OTP) to complete your login:</p>
                
                <div class='otp-code'>
                    <p style='margin: 0 0 10px 0; font-size: 14px; color: #666;'>Your OTP Code:</p>
                    <div class='otp-number'>$otp</div>
                    <p style='margin: 10px 0 0 0; font-size: 12px; color: #666;'>Enter this code on the login page</p>
                </div>
                
                <div class='warning'>
                    <strong>⚠️ Important Security Information:</strong>
                    <ul style='margin: 10px 0 0 0; padding-left: 20px;'>
                        <li>This code expires in <strong>5 minutes</strong></li>
                        <li>Do not share this code with anyone</li>
                        <li>If you didn't request this code, please ignore this email</li>
                    </ul>
                </div>
                
                <p>If you're having trouble logging in, please contact our support team.</p>
                
                <div class='footer'>
                    <p><strong>MJ Pharmacy System</strong><br>
                    Secure Healthcare Management<br>
                    This email was automatically generated. Please do not reply.</p>
                    <p style='margin-top: 15px;'>© " . date('Y') . " MJ Pharmacy. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getPlainTextTemplate($otp) {
        return "
MJ PHARMACY - LOGIN OTP CODE
=============================

Hello,

You have requested to log in to your MJ Pharmacy account. 

Your OTP Code: $otp

IMPORTANT:
- This code expires in 5 minutes
- Enter this code on the login page to access your account
- Do not share this code with anyone
- If you didn't request this code, please ignore this email

If you're having trouble logging in, please contact our support team.

© " . date('Y') . " MJ Pharmacy System
This email was automatically generated. Please do not reply.
        ";
    }
    
    public function testConnection() {
        try {
            // Test SMTP connection
            $this->mail->isSMTP();
            $this->mail->Host = SMTP_HOST;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = GMAIL_USERNAME;
            $this->mail->Password = GMAIL_APP_PASSWORD;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = SMTP_PORT;
            
            // Test connection without sending
            $this->mail->smtpConnect();
            $this->mail->smtpClose();
            
            return "✅ PHPMailer connection successful! Ready to send OTP emails.";
            
        } catch (Exception $e) {
            return "❌ PHPMailer connection failed: " . $e->getMessage();
        }
    }
    
    public function sendTestEmail($email) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($email);
            
            $this->mail->Subject = 'MJ Pharmacy - PHPMailer Test';
            $this->mail->Body = '<h2>PHPMailer Test Successful!</h2><p>Your PHPMailer OTP system is working correctly.</p>';
            $this->mail->AltBody = 'PHPMailer Test Successful! Your PHPMailer OTP system is working correctly.';
            
            $result = $this->mail->send();
            
            if ($result) {
                return "✅ Test email sent successfully to $email";
            } else {
                return "❌ Test email failed: " . $this->mail->ErrorInfo;
            }
            
        } catch (Exception $e) {
            return "❌ Test email exception: " . $e->getMessage();
        }
    }
}
?>
