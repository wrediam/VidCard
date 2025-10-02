<?php
require_once 'config.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Generate and send authentication code to email
     */
    public function sendAuthCode($email, $ipAddress = null) {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }
        
        // Generate 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Calculate expiration
        $expiresAt = date('Y-m-d H:i:s', time() + AUTH_CODE_LIFETIME);
        
        // Store code in database
        $stmt = $this->db->prepare(
            'INSERT INTO auth_codes (email, code, expires_at, ip_address) 
             VALUES (:email, :code, :expires_at, :ip_address)'
        );
        
        $stmt->execute([
            'email' => $email,
            'code' => $code,
            'expires_at' => $expiresAt,
            'ip_address' => $ipAddress
        ]);
        
        // Send email
        $this->sendEmail($email, $code);
        
        return true;
    }
    
    /**
     * Verify authentication code and create session
     */
    public function verifyCode($email, $code, $ipAddress = null, $userAgent = null) {
        // Find valid code
        $stmt = $this->db->prepare(
            'SELECT id FROM auth_codes 
             WHERE email = :email 
             AND code = :code 
             AND expires_at > NOW() 
             AND used = false 
             ORDER BY created_at DESC 
             LIMIT 1'
        );
        
        $stmt->execute(['email' => $email, 'code' => $code]);
        $authCode = $stmt->fetch();
        
        if (!$authCode) {
            throw new Exception('Invalid or expired code');
        }
        
        // Mark code as used
        $stmt = $this->db->prepare('UPDATE auth_codes SET used = true WHERE id = :id');
        $stmt->execute(['id' => $authCode['id']]);
        
        // Get or create user
        $user = $this->getOrCreateUser($email);
        
        // Create session
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        $stmt = $this->db->prepare(
            'INSERT INTO sessions (user_id, session_token, expires_at, ip_address, user_agent) 
             VALUES (:user_id, :session_token, :expires_at, :ip_address, :user_agent)'
        );
        
        $stmt->execute([
            'user_id' => $user['id'],
            'session_token' => $sessionToken,
            'expires_at' => $expiresAt,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent
        ]);
        
        // Update last login
        $stmt = $this->db->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
        $stmt->execute(['id' => $user['id']]);
        
        return [
            'session_token' => $sessionToken,
            'user' => $user
        ];
    }
    
    /**
     * Validate session token
     */
    public function validateSession($sessionToken) {
        $stmt = $this->db->prepare(
            'SELECT s.*, u.email, u.is_active 
             FROM sessions s 
             JOIN users u ON s.user_id = u.id 
             WHERE s.session_token = :token 
             AND s.expires_at > NOW() 
             AND u.is_active = true'
        );
        
        $stmt->execute(['token' => $sessionToken]);
        return $stmt->fetch();
    }
    
    /**
     * Get or create user by email
     */
    private function getOrCreateUser($email) {
        // Try to get existing user
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if ($user) {
            return $user;
        }
        
        // Create new user
        $stmt = $this->db->prepare('INSERT INTO users (email) VALUES (:email) RETURNING *');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }
    
    /**
     * Send email via SMTP2GO API
     */
    private function sendEmail($to, $code) {
        $subject = 'Your VidCard Login Code: ' . $code;
        $htmlBody = $this->getEmailTemplate($code);
        
        // Use SMTP2GO API
        $apiKey = defined('SMTP2GO_API_KEY') ? SMTP2GO_API_KEY : null;
        
        if ($apiKey) {
            return $this->sendViaAPI($to, $subject, $htmlBody, $apiKey);
        }
        
        // Fallback to PHP mail() if no API key
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
            'Reply-To: ' . SMTP_FROM_EMAIL
        ];
        
        return mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }
    
    /**
     * Send email via SMTP2GO REST API
     */
    private function sendViaAPI($to, $subject, $htmlBody, $apiKey) {
        $payload = [
            'sender' => SMTP_FROM_EMAIL,
            'to' => [$to],
            'subject' => $subject,
            'html_body' => $htmlBody
        ];
        
        $ch = curl_init('https://api.smtp2go.com/v3/email/send');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Smtp2go-Api-Key: ' . $apiKey,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("SMTP2GO API Error: HTTP $httpCode - $response");
            throw new Exception('Failed to send email');
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['data']['succeeded']) || $result['data']['succeeded'] !== 1) {
            error_log("SMTP2GO API Error: " . json_encode($result));
            throw new Exception('Failed to send email');
        }
        
        return true;
    }
    
    /**
     * Email template
     */
    private function getEmailTemplate($code) {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="padding: 40px 40px 20px 40px; text-align: center;">
                            <h1 style="margin: 0; font-size: 28px; font-weight: 700; color: #1a1a1a;">VidCard</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 40px 20px 40px; text-align: center;">
                            <p style="margin: 0; font-size: 16px; color: #666; line-height: 1.5;">Your login code is:</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 40px 30px 40px; text-align: center;">
                            <div style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; font-size: 32px; font-weight: 700; padding: 20px 40px; border-radius: 8px; letter-spacing: 8px;">
                                ' . $code . '
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 0 40px 40px 40px; text-align: center;">
                            <p style="margin: 0; font-size: 14px; color: #999; line-height: 1.5;">
                                This code will expire in 15 minutes.<br>
                                If you didn\'t request this code, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    /**
     * Logout - invalidate session
     */
    public function logout($sessionToken) {
        $stmt = $this->db->prepare('DELETE FROM sessions WHERE session_token = :token');
        $stmt->execute(['token' => $sessionToken]);
        return true;
    }
}
