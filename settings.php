<?php
require_once 'includes/config.php';

if (!$config['installed']) {
    header('Location: install/install.php');
    exit;
}

$pdo = getDbConnection();
if (!$pdo) {
    die('Database connection failed.');
}

$error = '';
$success = '';

// Handle form submission
if ($_POST && isset($_POST['save_settings'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        try {
            $settings = [
                'postback_template' => trim($_POST['postback_template'] ?? ''),
                'site_name' => trim($_POST['site_name'] ?? 'S2S Postback Checker'),
                'timezone' => trim($_POST['timezone'] ?? 'UTC')
            ];
            
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, $value]);
            }
            
            $success = 'Settings saved successfully.';
            
        } catch (PDOException $e) {
            $error = 'Failed to save settings: ' . $e->getMessage();
        }
    }
}

// Load current settings
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settingsData = $stmt->fetchAll();
    
    $settings = [];
    foreach ($settingsData as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (PDOException $e) {
    $settings = [];
    $error = 'Failed to load settings: ' . $e->getMessage();
}

// Get statistics for settings page
try {
    $stats = [];
    
    // Recent postback logs
    $stmt = $pdo->query("
        SELECT pl.*, c.transaction_id, o.name as offer_name 
        FROM postback_logs pl 
        LEFT JOIN conversions conv ON pl.conversion_id = conv.id 
        LEFT JOIN conversions c ON pl.conversion_id = c.id 
        LEFT JOIN offers o ON c.offer_id = o.id 
        ORDER BY pl.created_at DESC 
        LIMIT 20
    ");
    $postbackLogs = $stmt->fetchAll();
    
    // Success rate
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN http_status >= 200 AND http_status < 300 THEN 1 ELSE 0 END) as successful
        FROM postback_logs 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $successStats = $stmt->fetch();
    $successRate = $successStats['total'] > 0 ? round(($successStats['successful'] / $successStats['total']) * 100, 1) : 0;
    
} catch (PDOException $e) {
    $postbackLogs = [];
    $successRate = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - S2S Postback Checker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="mb-3">
            <h1>Settings</h1>
            <p class="text-secondary">Configure your S2S postback tracking system</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="grid grid-2">
            <!-- Settings Form -->
            <div class="card glass">
                <div class="card-header">
                    <h3 class="card-title">Global Configuration</h3>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Site Name</label>
                        <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($settings['site_name'] ?? 'S2S Postback Checker') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Timezone</label>
                        <select name="timezone" class="form-control">
                            <option value="UTC" <?= ($settings['timezone'] ?? 'UTC') == 'UTC' ? 'selected' : '' ?>>UTC</option>
                            <option value="America/New_York" <?= ($settings['timezone'] ?? '') == 'America/New_York' ? 'selected' : '' ?>>Eastern Time</option>
                            <option value="America/Chicago" <?= ($settings['timezone'] ?? '') == 'America/Chicago' ? 'selected' : '' ?>>Central Time</option>
                            <option value="America/Denver" <?= ($settings['timezone'] ?? '') == 'America/Denver' ? 'selected' : '' ?>>Mountain Time</option>
                            <option value="America/Los_Angeles" <?= ($settings['timezone'] ?? '') == 'America/Los_Angeles' ? 'selected' : '' ?>>Pacific Time</option>
                            <option value="Europe/London" <?= ($settings['timezone'] ?? '') == 'Europe/London' ? 'selected' : '' ?>>London</option>
                            <option value="Europe/Paris" <?= ($settings['timezone'] ?? '') == 'Europe/Paris' ? 'selected' : '' ?>>Paris</option>
                            <option value="Asia/Tokyo" <?= ($settings['timezone'] ?? '') == 'Asia/Tokyo' ? 'selected' : '' ?>>Tokyo</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Global Postback Template</label>
                        <textarea name="postback_template" class="form-control" rows="3" placeholder="https://example.com/postback?tid={transaction_id}&goal={goal}&name={name}&email={email}"><?= htmlspecialchars($settings['postback_template'] ?? '') ?></textarea>
                        <small class="text-secondary">
                            This template will be used for all offers unless overridden at the offer level.<br>
                            <strong>Available tokens:</strong> {transaction_id}, {goal}, {name}, {email}, {phone}, {offer_id}, {offer_name}, {payout}, {revenue}, {sub1-5}, {ip}, {timestamp}, {date}, {datetime}
                        </small>
                    </div>
                    
                    <button type="submit" name="save_settings" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
            
            <!-- Statistics -->
            <div class="card glass">
                <div class="card-header">
                    <h3 class="card-title">Postback Statistics</h3>
                    <p class="card-subtitle">Last 7 days performance</p>
                </div>
                
                <div class="text-center mb-3">
                    <div class="stat-number"><?= $successRate ?>%</div>
                    <div class="stat-label">Success Rate</div>
                </div>
                
                <div class="grid grid-2 mb-3">
                    <div class="text-center">
                        <div class="stat-number" style="font-size: 1.5rem; color: var(--success);">
                            <?= $successStats['successful'] ?? 0 ?>
                        </div>
                        <div class="stat-label">Successful</div>
                    </div>
                    
                    <div class="text-center">
                        <div class="stat-number" style="font-size: 1.5rem; color: var(--error);">
                            <?= ($successStats['total'] ?? 0) - ($successStats['successful'] ?? 0) ?>
                        </div>
                        <div class="stat-label">Failed</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Postback Logs -->
        <div class="card glass mt-3">
            <div class="card-header">
                <h3 class="card-title">Recent Postback Activity</h3>
            </div>
            
            <?php if (empty($postbackLogs)): ?>
                <p class="text-secondary text-center p-3">No postback activity yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Offer</th>
                                <th>URL</th>
                                <th>Status</th>
                                <th>Response Time</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($postbackLogs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['transaction_id'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($log['offer_name'] ?? 'Unknown') ?></td>
                                <td>
                                    <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($log['postback_url']) ?>">
                                        <?= htmlspecialchars($log['postback_url']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($log['http_status']): ?>
                                        <span class="badge <?= ($log['http_status'] >= 200 && $log['http_status'] < 300) ? 'badge-success' : 'badge-error' ?>">
                                            <?= $log['http_status'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-error" title="<?= htmlspecialchars($log['error_message'] ?? 'Unknown error') ?>">
                                            Error
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $log['response_time'] ? $log['response_time'] . 'ms' : 'N/A' ?></td>
                                <td><?= date('M j, H:i', strtotime($log['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- System Information -->
        <div class="card glass mt-3">
            <div class="card-header">
                <h3 class="card-title">System Information</h3>
            </div>
            
            <div class="grid grid-3">
                <div>
                    <h4>Server</h4>
                    <ul class="text-secondary" style="list-style: none; padding: 0;">
                        <li><strong>PHP Version:</strong> <?= PHP_VERSION ?></li>
                        <li><strong>Server Software:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></li>
                        <li><strong>Database:</strong> MySQL/MariaDB</li>
                    </ul>
                </div>
                
                <div>
                    <h4>Configuration</h4>
                    <ul class="text-secondary" style="list-style: none; padding: 0;">
                        <li><strong>DB Host:</strong> <?= htmlspecialchars($config['db_host']) ?></li>
                        <li><strong>DB Name:</strong> <?= htmlspecialchars($config['db_name']) ?></li>
                        <li><strong>Timezone:</strong> <?= htmlspecialchars($settings['timezone'] ?? 'UTC') ?></li>
                    </ul>
                </div>
                
                <div>
                    <h4>Features</h4>
                    <ul class="text-secondary" style="list-style: none; padding: 0;">
                        <li>✅ Click Tracking</li>
                        <li>✅ Conversion Tracking</li>
                        <li>✅ Postback Firing</li>
                        <li>✅ Manual Testing</li>
                        <li>✅ Analytics Dashboard</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/app.js"></script>
    <style>
        .badge-error {
            background: rgba(255, 68, 68, 0.2);
            color: var(--error);
        }
    </style>
</body>
</html>