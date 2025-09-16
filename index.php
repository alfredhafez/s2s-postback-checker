<?php
require_once 'includes/config.php';

// Get basic stats
$stats = [
    'total_clicks' => 0,
    'total_conversions' => 0,
    'conversion_rate' => 0,
    'active_offers' => 0
];

try {
    // Total clicks
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clicks");
    $stats['total_clicks'] = $stmt->fetch()['count'];
    
    // Total conversions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clicks WHERE converted_at IS NOT NULL");
    $stats['total_conversions'] = $stmt->fetch()['count'];
    
    // Conversion rate
    if ($stats['total_clicks'] > 0) {
        $stats['conversion_rate'] = round(($stats['total_conversions'] / $stats['total_clicks']) * 100, 2);
    }
    
    // Active offers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM offers WHERE is_active = 1");
    $stats['active_offers'] = $stmt->fetch()['count'];
    
} catch (PDOException $e) {
    error_log("Error fetching dashboard stats: " . $e->getMessage());
}

// Get recent clicks
$recentClicks = [];
try {
    $stmt = $pdo->query("SELECT c.*, o.name as offer_name FROM clicks c LEFT JOIN offers o ON c.offer_id = o.id ORDER BY c.created_at DESC LIMIT 10");
    $recentClicks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching recent clicks: " . $e->getMessage());
}

// Get recent conversions
$recentConversions = [];
try {
    $stmt = $pdo->query("SELECT c.*, o.name as offer_name FROM clicks c LEFT JOIN offers o ON c.offer_id = o.id WHERE c.converted_at IS NOT NULL ORDER BY c.converted_at DESC LIMIT 10");
    $recentConversions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching recent conversions: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S2S Postback Checker Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="glass-card">
            <h1>S2S Postback Checker</h1>
            
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="stat-card" style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 8px; text-align: center;">
                    <h3>Total Clicks</h3>
                    <div style="font-size: 2rem; font-weight: bold; color: #4facfe;"><?= number_format($stats['total_clicks']) ?></div>
                </div>
                <div class="stat-card" style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 8px; text-align: center;">
                    <h3>Conversions</h3>
                    <div style="font-size: 2rem; font-weight: bold; color: #4caf50;"><?= number_format($stats['total_conversions']) ?></div>
                </div>
                <div class="stat-card" style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 8px; text-align: center;">
                    <h3>Conversion Rate</h3>
                    <div style="font-size: 2rem; font-weight: bold; color: #ff9800;"><?= $stats['conversion_rate'] ?>%</div>
                </div>
                <div class="stat-card" style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 8px; text-align: center;">
                    <h3>Active Offers</h3>
                    <div style="font-size: 2rem; font-weight: bold; color: #9c27b0;"><?= number_format($stats['active_offers']) ?></div>
                </div>
            </div>
            
            <div class="navigation-menu" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 30px;">
                <a href="postback-test.php" class="btn btn-primary">Manual Postback Test Tool</a>
                <a href="offers.php" class="btn btn-secondary">Manage Offers</a>
                <a href="settings.php" class="btn btn-secondary">Settings</a>
                <a href="install/install.php" class="btn btn-secondary">Database Setup</a>
            </div>
            
            <?php if (!empty($recentClicks)): ?>
            <div class="recent-section">
                <h3>Recent Clicks</h3>
                <div class="tests-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Offer</th>
                                <th>Source</th>
                                <th>Converted</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentClicks as $click): ?>
                            <tr>
                                <td><?= htmlspecialchars($click['transaction_id']) ?></td>
                                <td><?= htmlspecialchars($click['offer_name'] ?: 'Unknown') ?></td>
                                <td><?= htmlspecialchars($click['source']) ?></td>
                                <td>
                                    <?php if ($click['converted_at']): ?>
                                        <span class="status-success">Yes</span>
                                    <?php else: ?>
                                        <span class="status-error">No</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M j, Y H:i', strtotime($click['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($recentConversions)): ?>
            <div class="recent-section" style="margin-top: 30px;">
                <h3>Recent Conversions</h3>
                <div class="tests-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Offer</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Goal</th>
                                <th>Converted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentConversions as $conversion): ?>
                            <tr>
                                <td><?= htmlspecialchars($conversion['transaction_id']) ?></td>
                                <td><?= htmlspecialchars($conversion['offer_name'] ?: 'Unknown') ?></td>
                                <td><?= htmlspecialchars($conversion['conversion_name']) ?></td>
                                <td><?= htmlspecialchars($conversion['conversion_email']) ?></td>
                                <td><?= htmlspecialchars($conversion['conversion_goal']) ?></td>
                                <td><?= date('M j, Y H:i', strtotime($conversion['converted_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="usage-examples" style="margin-top: 30px;">
                <h3>Quick Start Examples</h3>
                <div style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 8px;">
                    <p><strong>1. Test Click URL (recommended flow):</strong></p>
                    <pre class="template-code">https://yourdomain.com/click.php?offer=1&sub1=test_12345&sub2=campaign</pre>
                    
                    <p><strong>2. Direct Offer URL (legacy support):</strong></p>
                    <pre class="template-code">https://yourdomain.com/offer.php?id=1&tid=test_12345</pre>
                    
                    <p><strong>3. Manual Postback Test:</strong></p>
                    <pre class="template-code">Use the Manual Postback Test Tool above to test postback URLs directly</pre>
                </div>
            </div>
        </div>
    </div>
</body>
</html>