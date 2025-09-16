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
        <div class="card glass" style="max-width: 600px; margin: 4rem auto;">
            <div class="card-header text-center">
                <h1 class="card-title">S2S Postback Checker</h1>
                <p class="card-subtitle">Installation & Setup</p>
            </div>

            <?php
            session_start();
            
            $step = $_GET['step'] ?? '1';
            $error = '';
            $success = '';

            // Step 1: Database Configuration
            if ($step == '1') {
                if ($_POST) {
                    $host = trim($_POST['host'] ?? '');
                    $dbname = trim($_POST['dbname'] ?? '');
                    $username = trim($_POST['username'] ?? '');
                    $password = $_POST['password'] ?? '';
                    
                    if (empty($host) || empty($dbname) || empty($username)) {
                        $error = 'Please fill in all required fields.';
                    } else {
                        // Test database connection
                        try {
                            $pdo = new PDO("mysql:host=$host", $username, $password, [
                                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                            ]);
                            
                            // Create database if it doesn't exist
                            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                            
                            // Store config in session for next step
                            $_SESSION['install_config'] = [
                                'host' => $host,
                                'dbname' => $dbname,
                                'username' => $username,
                                'password' => $password
                            ];
                            
                            header('Location: install.php?step=2');
                            exit;
                            
                        } catch (PDOException $e) {
                            $error = 'Database connection failed: ' . $e->getMessage();
                        }
                    }
                }
                ?>
                
                <form method="POST">
                    <h3 class="mb-3">Database Configuration</h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label">Database Host *</label>
                        <input type="text" name="host" class="form-control" value="<?= htmlspecialchars($_POST['host'] ?? 'localhost') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Database Name *</label>
                        <input type="text" name="dbname" class="form-control" value="<?= htmlspecialchars($_POST['dbname'] ?? 's2s_postback') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($_POST['username'] ?? 'root') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" value="<?= htmlspecialchars($_POST['password'] ?? '') ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-full">Test Connection & Continue</button>
                </form>
                
            <?php } elseif ($step == '2') { 
                // Step 2: Create Tables
                if (!isset($_SESSION['install_config'])) {
                    header('Location: install.php?step=1');
                    exit;
                }
                
                $config = $_SESSION['install_config'];
                
                if ($_POST['create_tables'] ?? false) {
                    try {
                        $pdo = new PDO(
                            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
                            $config['username'],
                            $config['password'],
                            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                        );
                        
                        // Create tables
                        $sql = file_get_contents(__DIR__ . '/schema.sql');
                        $pdo->exec($sql);
                        
                        header('Location: install.php?step=3');
                        exit;
                        
                    } catch (PDOException $e) {
                        $error = 'Failed to create tables: ' . $e->getMessage();
                    }
                }
                ?>
                
                <h3 class="mb-3">Create Database Tables</h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <p class="text-secondary mb-3">We'll now create the required database tables for the S2S Postback Checker.</p>
                
                <div class="code-block mb-3">
                    <strong>Database:</strong> <?= htmlspecialchars($config['dbname']) ?><br>
                    <strong>Host:</strong> <?= htmlspecialchars($config['host']) ?><br>
                    <strong>Username:</strong> <?= htmlspecialchars($config['username']) ?>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="create_tables" value="1">
                    <button type="submit" class="btn btn-primary w-full">Create Tables</button>
                </form>
                
            <?php } elseif ($step == '3') {
                // Step 3: Admin Setup & Seed Data
                if (!isset($_SESSION['install_config'])) {
                    header('Location: install.php?step=1');
                    exit;
                }
                
                $config = $_SESSION['install_config'];
                
                if ($_POST['complete_install'] ?? false) {
                    try {
                        $pdo = new PDO(
                            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
                            $config['username'],
                            $config['password'],
                            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                        );
                        
                        // Insert default settings
                        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                        
                        $defaultSettings = [
                            'postback_template' => 'http://example.com/postback?tid={transaction_id}&goal={goal}&name={name}&email={email}&offer={offer_id}',
                            'site_name' => 'S2S Postback Checker',
                            'timezone' => 'UTC'
                        ];
                        
                        foreach ($defaultSettings as $key => $value) {
                            $stmt->execute([$key, $value]);
                        }
                        
                        // Insert sample offers
                        if ($_POST['seed_data'] ?? false) {
                            $offers = [
                                ['Test Offer 1', 'Email/Phone Submit', 'signup'],
                                ['Test Offer 2', 'Download App', 'install'],
                                ['Test Offer 3', 'Survey Completion', 'survey']
                            ];
                            
                            $stmt = $pdo->prepare("INSERT INTO offers (name, description, goal_name) VALUES (?, ?, ?)");
                            foreach ($offers as $offer) {
                                $stmt->execute($offer);
                            }
                        }
                        
                        // Create config file
                        $configContent = "<?php\n\$config = [\n";
                        $configContent .= "    'db_host' => '{$config['host']}',\n";
                        $configContent .= "    'db_name' => '{$config['dbname']}',\n";
                        $configContent .= "    'db_user' => '{$config['username']}',\n";
                        $configContent .= "    'db_pass' => '{$config['password']}',\n";
                        $configContent .= "    'installed' => true\n";
                        $configContent .= "];\n?>";
                        
                        file_put_contents('../includes/db_config.php', $configContent);
                        
                        // Clear session
                        unset($_SESSION['install_config']);
                        
                        $success = true;
                        
                    } catch (Exception $e) {
                        $error = 'Installation failed: ' . $e->getMessage();
                    }
                }
                
                if (isset($success)) {
                    ?>
                    <div class="text-center">
                        <div class="alert alert-success">
                            <strong>Installation Complete!</strong><br>
                            Your S2S Postback Checker is now ready to use.
                        </div>
                        
                        <a href="../index.php" class="btn btn-primary">Go to Dashboard</a>
                    </div>
                    <?php
                } else {
                    ?>
                    <h3 class="mb-3">Complete Installation</h3>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <p class="text-secondary mb-3">Finally, we'll set up default settings and optionally add sample data.</p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label class="d-flex align-center gap-1">
                                <input type="checkbox" name="seed_data" value="1" checked>
                                Add sample offers for testing
                            </label>
                        </div>
                        
                        <input type="hidden" name="complete_install" value="1">
                        <button type="submit" class="btn btn-success w-full">Complete Installation</button>
                    </form>
                    <?php
                }
            } ?>
        </div>
    </div>
</body>
</html>