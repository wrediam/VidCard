<?php
// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'vidcard');
define('DB_USER', getenv('DB_USER') ?: 'vidcard');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');

// SMTP2GO Configuration
define('SMTP2GO_API_KEY', getenv('SMTP2GO_API_KEY') ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'noreply@vidcard.io');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'VidCard');

// YouTube API
define('YOUTUBE_API_KEY', getenv('YOUTUBE_API_KEY') ?: '');

// App Configuration
define('APP_URL', getenv('APP_URL') ?: 'https://vidcard.io');
define('SESSION_LIFETIME', 30 * 24 * 60 * 60); // 30 days
define('AUTH_CODE_LIFETIME', 15 * 60); // 15 minutes

// Database connection
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME
            );
            
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }
    
    return $pdo;
}
