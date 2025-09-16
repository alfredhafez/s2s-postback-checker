<?php
require_once 'includes/config.php';
require_once 'lib/ClickModel.php';
require_once 'includes/postback.php';

$clickModel = new ClickModel($pdo);

$result = null;
$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF token validation failed';
    } else {
        $transactionId = trim($_POST['transaction_id'] ?? '');
        $testUrl = trim($_POST['test_url'] ?? '');
        
        // Validate and reject literal placeholder {transaction_id}
        if (empty($transactionId)) {
            $error = 'Transaction ID is required';
        } elseif ($transactionId === '{transaction_id}') {
            $error = 'Please replace {transaction_id} with a real transaction ID value (e.g., test_123456)';
        } elseif (empty($testUrl)) {
            $error = 'Test URL is required';
        } elseif (!filter_var($testUrl, FILTER_VALIDATE_URL)) {
            $error = 'Invalid URL format';
        } else {
            // Check if click exists for this transaction ID
            $existingClick = $clickModel->findByTransactionId($transactionId);
            
            if (!$existingClick) {
                // Auto-create a synthetic click for manual testing
                $meta = [
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'test_source' => 'manual_postback_test',
                    'created_by' => 'manual_test_tool'
                ];
                
                $clickId = $clickModel->create(1, $transactionId, 'manual', 'test', null, null, $meta, 'manual');
                
                if ($clickId) {
                    $existingClick = $clickModel->findById($clickId);
                    error_log("Auto-created synthetic click for manual postback test: click_id={$clickId}, transaction_id={$transactionId}");
                } else {
                    $error = 'Failed to create synthetic click for testing';
                }
            }
            
            if (!$error && $existingClick) {
                // Fire the manual test
                $result = testPostbackManually($pdo, $testUrl, $transactionId);
                
                if ($result['success']) {
                    $success = "Postback test successful! URL: {$result['url']} | HTTP Status: {$result['http_code']} | Response Time: {$result['response_time']}ms";
                } else {
                    $error = "Postback test failed! URL: {$result['url']} | HTTP Status: {$result['http_code']} | Error: {$result['error']} | Response Time: {$result['response_time']}ms";
                }
            }
        }
    }
}

// Get recent manual tests
$recentTests = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM manual_tests ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $recentTests = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching recent manual tests: " . $e->getMessage());
}

// Get global postback template for reference
$globalTemplate = '';
try {
    $stmt = $pdo->prepare("SELECT value FROM settings WHERE `key` = 'global_postback_template'");
    $stmt->execute();
    $templateResult = $stmt->fetch();
    $globalTemplate = $templateResult['value'] ?? '';
} catch (PDOException $e) {
    error_log("Error fetching global template: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Postback Test Tool - S2S Postback Checker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="glass-card">
            <h1>Manual Postback Test Tool</h1>
            <p>Test postback URLs manually. If no click exists for the transaction ID, one will be created automatically.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="test-form">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                
                <div class="form-group">
                    <label for="transaction_id">Transaction ID:</label>
                    <input type="text" id="transaction_id" name="transaction_id" required 
                           placeholder="e.g., test_123456 (NOT {transaction_id})"
                           value="<?= htmlspecialchars($_POST['transaction_id'] ?? '') ?>">
                    <small>Use a real transaction ID, not the literal placeholder {transaction_id}</small>
                </div>
                
                <div class="form-group">
                    <label for="test_url">Postback URL to Test:</label>
                    <textarea id="test_url" name="test_url" required rows="3" 
                              placeholder="https://partner.com/postback?tid=test_123456&goal=lead"><?= htmlspecialchars($_POST['test_url'] ?? '') ?></textarea>
                    <small>Full URL including all parameters</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Fire Test Postback</button>
            </form>
            
            <?php if ($result): ?>
                <div class="test-results">
                    <h3>Test Results</h3>
                    <div class="result-item">
                        <strong>URL:</strong> <?= htmlspecialchars($result['url']) ?>
                    </div>
                    <div class="result-item">
                        <strong>HTTP Status:</strong> 
                        <span class="status-<?= $result['http_code'] >= 200 && $result['http_code'] < 400 ? 'success' : 'error' ?>">
                            <?= $result['http_code'] ?>
                        </span>
                    </div>
                    <div class="result-item">
                        <strong>Response Time:</strong> <?= $result['response_time'] ?>ms
                    </div>
                    <?php if ($result['error']): ?>
                        <div class="result-item">
                            <strong>Error:</strong> <span class="error-text"><?= htmlspecialchars($result['error']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($result['response']): ?>
                        <div class="result-item">
                            <strong>Response Body:</strong>
                            <pre class="response-body"><?= htmlspecialchars(substr($result['response'], 0, 1000)) ?><?= strlen($result['response']) > 1000 ? '...' : '' ?></pre>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($globalTemplate): ?>
                <div class="template-info">
                    <h3>Global Postback Template</h3>
                    <pre class="template-code"><?= htmlspecialchars($globalTemplate) ?></pre>
                    <p><strong>Available Tokens:</strong> {transaction_id}, {goal}, {name}, {email}, {offer_id}, {sub1}, {sub2}, {sub3}, {sub4}, {sub5}, {timestamp}, {click_id}, {unix_timestamp}, {date}, {datetime}, {ip}, {user_agent}, {referer}, {random}</p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($recentTests)): ?>
                <div class="recent-tests">
                    <h3>Recent Manual Tests</h3>
                    <div class="tests-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>HTTP Status</th>
                                    <th>Response Time</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTests as $test): ?>
                                <tr>
                                    <td><?= htmlspecialchars($test['transaction_id']) ?></td>
                                    <td>
                                        <span class="status-<?= $test['http_code'] >= 200 && $test['http_code'] < 400 ? 'success' : 'error' ?>">
                                            <?= $test['http_code'] ?: 'Error' ?>
                                        </span>
                                    </td>
                                    <td><?= $test['response_time'] ?>ms</td>
                                    <td><?= date('M j, Y H:i', strtotime($test['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="navigation">
                <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>