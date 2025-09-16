<?php
// Database configuration
$config = [];

// Check if config file exists
if (file_exists(__DIR__ . '/db_config.php')) {
    include __DIR__ . '/db_config.php';
} else {
    // Default configuration for installer
    $config = [
        'db_host' => 'localhost',
        'db_name' => 's2s_postback',
        'db_user' => 'root',
        'db_pass' => '',
        'installed' => false
    ];
}

// Database connection
function getDbConnection() {
    global $config;
    
    if (!$config['installed']) {
        return null;
    }
    
    try {
        $pdo = new PDO(
            "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
            $config['db_user'],
            $config['db_pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>