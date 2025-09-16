<?php
require_once 'includes/config.php';
require_once 'includes/postback.php';

if (!$config['installed']) {
    header('Location: install/install.php');
    exit;
}

$pdo = getDbConnection();
if (!$pdo) {
    die('Database connection failed.');
}

$error = '';
$testResult = null;

// Handle test form submission
if ($_POST && isset($_POST['test_postback'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $postbackUrl = trim($_POST['postback_url'] ?? '');
        
        if (empty($postbackUrl)) {
            $error = 'Postback URL is required.';
        } elseif (!filter_var($postbackUrl, FILTER_VALIDATE_URL)) {
            $error = 'Please enter a valid URL.';
        } else {
            $testResult = testPostback($postbackUrl, $pdo);
        }
    }
}

// Get recent test history
try {
    $stmt = $pdo->query("SELECT * FROM manual_tests ORDER BY created_at DESC LIMIT 10");
    $recentTests = $stmt->fetchAll();
} catch (PDOException $e) {
    $recentTests = [];
}

// Get current postback template for reference
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'postback_template'");
    $stmt->execute();
    $setting = $stmt->fetch();
    $currentTemplate = $setting['setting_value'] ?? '';
} catch (PDOException $e) {
    $currentTemplate = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postback Test Tool - S2S Postback Checker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="mb-3">
            <h1>Manual Postback Test Tool</h1>
            <p class="text-secondary">Test your postback URLs manually to verify they're working correctly</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Test Form -->
        <div class="card glass mb-3">
            <div class="card-header">
                <h3 class="card-title">Test Postback URL</h3>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-group">
                    <label class="form-label">Postback URL *</label>
                    <textarea name="postback_url" class="form-control" rows="3" placeholder="https://example.com/postback?tid=test123&goal=lead&name=Test User" required><?= htmlspecialchars($_POST['postback_url'] ?? '') ?></textarea>
                    <small class="text-secondary">Enter the complete postback URL with all parameters</small>
                </div>
                
                <button type="submit" name="test_postback" class="btn btn-primary">
                    🚀 Test Postback
                </button>
            </form>
        </div>
        
        <!-- Test Result -->
        <?php if ($testResult): ?>
        <div class="card glass mb-3">
            <div class="card-header">
                <h3 class="card-title">Test Result</h3>
            </div>
            
            <div class="grid grid-2">
                <div>
                    <h4>Request Details</h4>
                    <div class="code-block mb-2">
                        <strong>URL:</strong> <?= htmlspecialchars($testResult['url']) ?>
                    </div>
                    <div class="d-flex gap-2 mb-2">
                        <span class="badge <?= $testResult['success'] ? 'badge-success' : 'badge-error' ?>">
                            HTTP <?= $testResult['http_status'] ?? 'Error' ?>
                        </span>
                        <span class="badge badge-secondary">
                            <?= $testResult['response_time'] ?>ms
                        </span>
                    </div>
                </div>
                
                <div>
                    <h4>Response</h4>
                    <?php if ($testResult['error_message']): ?>
                        <div class="alert alert-error" style="margin: 0;">
                            <strong>Error:</strong> <?= htmlspecialchars($testResult['error_message']) ?>
                        </div>
                    <?php else: ?>
                        <div class="code-block" style="max-height: 200px; overflow-y: auto;">
                            <?= htmlspecialchars($testResult['response_body'] ?: 'No response body') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Template Reference -->
        <?php if ($currentTemplate): ?>
        <div class="card glass mb-3">
            <div class="card-header">
                <h3 class="card-title">Current Global Template</h3>
                <p class="card-subtitle">Reference for building your test URLs</p>
            </div>
            
            <div class="code-block mb-3">
                <?= htmlspecialchars($currentTemplate) ?>
            </div>
            
            <div class="grid grid-2">
                <div>
                    <h4>Available Tokens</h4>
                    <ul class="text-secondary" style="list-style: none; padding: 0;">
                        <li><code>{transaction_id}</code> - Unique transaction ID</li>
                        <li><code>{goal}</code> - Conversion goal name</li>
                        <li><code>{name}</code> - User's name</li>
                        <li><code>{email}</code> - User's email</li>
                        <li><code>{phone}</code> - User's phone (if provided)</li>
                        <li><code>{offer_id}</code> - Offer ID</li>
                        <li><code>{offer_name}</code> - Offer name</li>
                        <li><code>{payout}</code> - Payout amount</li>
                        <li><code>{revenue}</code> - Revenue amount</li>
                    </ul>
                </div>
                
                <div>
                    <h4>Tracking Tokens</h4>
                    <ul class="text-secondary" style="list-style: none; padding: 0;">
                        <li><code>{sub1}</code> - Sub ID 1</li>
                        <li><code>{sub2}</code> - Sub ID 2</li>
                        <li><code>{sub3}</code> - Sub ID 3</li>
                        <li><code>{sub4}</code> - Sub ID 4</li>
                        <li><code>{sub5}</code> - Sub ID 5</li>
                        <li><code>{ip}</code> - User's IP address</li>
                        <li><code>{timestamp}</code> - Unix timestamp</li>
                        <li><code>{date}</code> - Date (Y-m-d)</li>
                        <li><code>{datetime}</code> - Full datetime</li>
                    </ul>
                </div>
            </div>
            
            <button class="btn btn-secondary" onclick="generateSampleUrl()">Generate Sample URL</button>
        </div>
        <?php endif; ?>
        
        <!-- Recent Tests -->
        <div class="card glass">
            <div class="card-header">
                <h3 class="card-title">Recent Tests</h3>
            </div>
            
            <?php if (empty($recentTests)): ?>
                <p class="text-secondary text-center p-3">No tests performed yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Test Name</th>
                                <th>URL</th>
                                <th>Status</th>
                                <th>Response Time</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTests as $test): ?>
                            <tr>
                                <td><?= htmlspecialchars($test['test_name']) ?></td>
                                <td>
                                    <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= htmlspecialchars($test['postback_url']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($test['http_status']): ?>
                                        <span class="badge <?= ($test['http_status'] >= 200 && $test['http_status'] < 300) ? 'badge-success' : 'badge-error' ?>">
                                            HTTP <?= $test['http_status'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-error">Error</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $test['response_time'] ? $test['response_time'] . 'ms' : 'N/A' ?></td>
                                <td><?= date('M j, H:i', strtotime($test['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="js/app.js"></script>
    <script>
        function generateSampleUrl() {
            const template = <?= json_encode($currentTemplate) ?>;
            
            const sampleData = {
                '{transaction_id}': 'test_' + Date.now(),
                '{goal}': 'lead',
                '{name}': 'John Doe',
                '{email}': 'john@example.com',
                '{phone}': '+1234567890',
                '{offer_id}': '1',
                '{offer_name}': 'Test Offer',
                '{payout}': '5.00',
                '{revenue}': '10.00',
                '{sub1}': 'test_sub1',
                '{sub2}': 'test_sub2',
                '{sub3}': 'test_sub3',
                '{sub4}': 'test_sub4',
                '{sub5}': 'test_sub5',
                '{ip}': '192.168.1.1',
                '{timestamp}': Math.floor(Date.now() / 1000),
                '{date}': new Date().toISOString().split('T')[0],
                '{datetime}': new Date().toISOString().replace('T', ' ').split('.')[0]
            };
            
            let sampleUrl = template;
            for (const [token, value] of Object.entries(sampleData)) {
                sampleUrl = sampleUrl.replace(new RegExp(token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), encodeURIComponent(value));
            }
            
            document.querySelector('textarea[name="postback_url"]').value = sampleUrl;
        }
    </script>
    
    <style>
        .badge-error {
            background: rgba(255, 68, 68, 0.2);
            color: var(--error);
        }
    </style>
</body>
</html>