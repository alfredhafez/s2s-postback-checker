<?php
require_once 'includes/config.php';

// Check if installation is needed
if (!$config['installed']) {
    header('Location: install/install.php');
    exit;
}

$pdo = getDbConnection();
if (!$pdo) {
    die('Database connection failed. Please check your configuration.');
}

// Get stats for dashboard
$stats = [];

try {
    // Total clicks
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM clicks");
    $stats['clicks'] = $stmt->fetch()['count'];
    
    // Total conversions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM conversions");
    $stats['conversions'] = $stmt->fetch()['count'];
    
    // Conversion rate
    $stats['conversion_rate'] = $stats['clicks'] > 0 ? round(($stats['conversions'] / $stats['clicks']) * 100, 2) : 0;
    
    // Total offers
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM offers WHERE status = 'active'");
    $stats['offers'] = $stmt->fetch()['count'];
    
    // Recent activity (last 7 days)
    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as clicks 
        FROM clicks 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
        GROUP BY DATE(created_at) 
        ORDER BY date ASC
    ");
    $clicksData = $stmt->fetchAll();
    
    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as conversions 
        FROM conversions 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
        GROUP BY DATE(created_at) 
        ORDER BY date ASC
    ");
    $conversionsData = $stmt->fetchAll();
    
    // Recent clicks
    $stmt = $pdo->query("
        SELECT c.*, o.name as offer_name 
        FROM clicks c 
        LEFT JOIN offers o ON c.offer_id = o.id 
        ORDER BY c.created_at DESC 
        LIMIT 10
    ");
    $recentClicks = $stmt->fetchAll();
    
    // Recent conversions
    $stmt = $pdo->query("
        SELECT conv.*, o.name as offer_name 
        FROM conversions conv 
        LEFT JOIN offers o ON conv.offer_id = o.id 
        ORDER BY conv.created_at DESC 
        LIMIT 10
    ");
    $recentConversions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    $stats = ['clicks' => 0, 'conversions' => 0, 'conversion_rate' => 0, 'offers' => 0];
    $recentClicks = [];
    $recentConversions = [];
    $clicksData = [];
    $conversionsData = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S2S Postback Checker - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/app.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="mb-3">
            <h1>Dashboard</h1>
            <p class="text-secondary">Overview of your S2S postback tracking performance</p>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-4 mb-3">
            <div class="card glass stat-card">
                <span class="stat-number"><?= number_format($stats['clicks']) ?></span>
                <span class="stat-label">Total Clicks</span>
            </div>
            
            <div class="card glass stat-card">
                <span class="stat-number"><?= number_format($stats['conversions']) ?></span>
                <span class="stat-label">Total Conversions</span>
            </div>
            
            <div class="card glass stat-card">
                <span class="stat-number"><?= $stats['conversion_rate'] ?>%</span>
                <span class="stat-label">Conversion Rate</span>
            </div>
            
            <div class="card glass stat-card">
                <span class="stat-number"><?= number_format($stats['offers']) ?></span>
                <span class="stat-label">Active Offers</span>
            </div>
        </div>
        
        <!-- Chart -->
        <div class="card glass mb-3">
            <div class="card-header">
                <h3 class="card-title">Activity (Last 7 Days)</h3>
            </div>
            <div class="chart-container">
                <canvas id="activityChart"></canvas>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="grid grid-2">
            <!-- Recent Clicks -->
            <div class="card glass">
                <div class="card-header">
                    <h3 class="card-title">Recent Clicks</h3>
                </div>
                
                <?php if (empty($recentClicks)): ?>
                    <p class="text-secondary text-center p-3">No clicks recorded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Offer</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentClicks as $click): ?>
                                <tr>
                                    <td><?= htmlspecialchars($click['transaction_id']) ?></td>
                                    <td><?= htmlspecialchars($click['offer_name'] ?? 'Unknown') ?></td>
                                    <td><?= date('M j, H:i', strtotime($click['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Conversions -->
            <div class="card glass">
                <div class="card-header">
                    <h3 class="card-title">Recent Conversions</h3>
                </div>
                
                <?php if (empty($recentConversions)): ?>
                    <p class="text-secondary text-center p-3">No conversions recorded yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Offer</th>
                                    <th>Goal</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentConversions as $conversion): ?>
                                <tr>
                                    <td><?= htmlspecialchars($conversion['transaction_id']) ?></td>
                                    <td><?= htmlspecialchars($conversion['offer_name'] ?? 'Unknown') ?></td>
                                    <td><?= htmlspecialchars($conversion['goal']) ?></td>
                                    <td><?= date('M j, H:i', strtotime($conversion['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Prepare chart data
        const labels = [];
        const clicksData = [];
        const conversionsData = [];
        
        // Get last 7 days
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        }
        
        // PHP data to JavaScript
        const phpClicksData = <?= json_encode($clicksData) ?>;
        const phpConversionsData = <?= json_encode($conversionsData) ?>;
        
        // Fill data arrays
        labels.forEach((label, index) => {
            const date = new Date();
            date.setDate(date.getDate() - (6 - index));
            const dateStr = date.toISOString().split('T')[0];
            
            const clickRecord = phpClicksData.find(d => d.date === dateStr);
            const conversionRecord = phpConversionsData.find(d => d.date === dateStr);
            
            clicksData.push(clickRecord ? parseInt(clickRecord.clicks) : 0);
            conversionsData.push(conversionRecord ? parseInt(conversionRecord.conversions) : 0);
        });
        
        // Create chart
        const ctx = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Clicks',
                    data: clicksData,
                    borderColor: '#00d4ff',
                    backgroundColor: 'rgba(0, 212, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Conversions',
                    data: conversionsData,
                    borderColor: '#00ff88',
                    backgroundColor: 'rgba(0, 255, 136, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: '#ffffff'
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: '#b0b0b0'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    },
                    y: {
                        ticks: {
                            color: '#b0b0b0'
                        },
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>