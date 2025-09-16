<?php
$step = $_GET['step'] ?? 1;
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    $host = $_POST['host'] ?? 'localhost';
    $database = $_POST['database'] ?? 's2s_postback';
    $username = $_POST['username'] ?? 'root';
    $password = $_POST['password'] ?? '';
    
    try {
        // Test connection
        $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$database}`");
        
        // Read and execute schema
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Create config file
        $configContent = "<?php
// Database configuration
define('DB_HOST', '{$host}');
define('DB_NAME', '{$database}');
define('DB_USER', '{$username}');
define('DB_PASS', '{$password}');

try {
    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\", DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException \$e) {
    die(\"Database connection failed: \" . \$e->getMessage());
}

// CSRF Protection
function generateCSRFToken() {
    if (!isset(\$_SESSION['csrf_token'])) {
        \$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return \$_SESSION['csrf_token'];
}

function validateCSRFToken(\$token) {
    return isset(\$_SESSION['csrf_token']) && hash_equals(\$_SESSION['csrf_token'], \$token);
}

// Start session
session_start();
?>";
        
        file_put_contents('../includes/config.php', $configContent);
        
        $success = true;
        $step = 3;
        
    } catch (Exception $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S2S Postback Checker - Installation</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="glass-card">
            <h1>S2S Postback Checker Installation</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($step == 1): ?>
                <div class="install-step">
                    <h2>Step 1: Welcome</h2>
                    <p>This installer will set up the S2S Postback Checker database and configuration.</p>
                    <p><strong>Requirements:</strong></p>
                    <ul style="margin: 20px 0; padding-left: 20px;">
                        <li>PHP 7.4 or higher</li>
                        <li>MySQL 5.7 or higher / MariaDB 10.2 or higher</li>
                        <li>PDO MySQL extension</li>
                        <li>Write permissions to includes/ directory</li>
                    </ul>
                    <a href="?step=2" class="btn btn-primary">Next: Database Setup</a>
                </div>
            
            <?php elseif ($step == 2): ?>
                <div class="install-step">
                    <h2>Step 2: Database Configuration</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="host">Database Host:</label>
                            <input type="text" id="host" name="host" value="localhost" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="database">Database Name:</label>
                            <input type="text" id="database" name="database" value="s2s_postback" required>
                            <small>Database will be created if it doesn't exist</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Database Username:</label>
                            <input type="text" id="username" name="username" value="root" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Database Password:</label>
                            <input type="password" id="password" name="password">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Install Database</button>
                    </form>
                </div>
            
            <?php elseif ($step == 3): ?>
                <div class="install-step">
                    <h2>Step 3: Installation Complete!</h2>
                    <div class="alert alert-success">
                        Installation completed successfully! The database has been created and configured.
                    </div>
                    
                    <p><strong>What was installed:</strong></p>
                    <ul style="margin: 20px 0; padding-left: 20px;">
                        <li>Database tables for offers, clicks, conversions, postback logs, manual tests, and settings</li>
                        <li>Sample offers for testing</li>
                        <li>Default postback template with token support</li>
                        <li>Configuration file with database connection</li>
                    </ul>
                    
                    <p><strong>Next steps:</strong></p>
                    <ol style="margin: 20px 0; padding-left: 20px;">
                        <li>Go to the <a href="../index.php" style="color: #4facfe;">Dashboard</a> to see your installation</li>
                        <li>Use the <a href="../postback-test.php" style="color: #4facfe;">Manual Postback Test Tool</a> to test postback URLs</li>
                        <li>Try the sample click URL: <code>../click.php?offer=1&sub1=test_12345</code></li>
                        <li>Test the legacy direct offer URL: <code>../offer.php?id=1&tid=test_12345</code></li>
                    </ol>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="../index.php" class="btn btn-primary">Go to Dashboard</a>
                        <a href="../postback-test.php" class="btn btn-secondary">Test Postbacks</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>