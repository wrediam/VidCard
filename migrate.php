<?php
/**
 * Database Migration Script
 * Run this once to initialize the database schema
 */

require_once 'config.php';

try {
    $db = getDB();
    
    echo "Running database migrations...\n";
    
    // Read and execute init.sql
    $sql = file_get_contents(__DIR__ . '/init.sql');
    
    if ($sql === false) {
        throw new Exception('Could not read init.sql file');
    }
    
    // Execute the SQL
    $db->exec($sql);
    
    echo "✅ Database schema created successfully!\n";
    echo "Tables created:\n";
    echo "  - users\n";
    echo "  - auth_codes\n";
    echo "  - sessions\n";
    echo "  - videos\n";
    echo "  - video_visits\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
