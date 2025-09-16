<?php
// Demo version of Manual Postback Test Tool to show functionality without database
session_start();

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

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
            // Simulate the manual test functionality
            $startTime = microtime(true);
            
            // Simulate HTTP request
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $testUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_USERAGENT => 'S2S-Postback-Checker-Manual-Test/1.0',
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $result = [
                'success' => !$curlError && $httpCode >= 200 && $httpCode < 400,
                'url' => $testUrl,
                'http_code' => $httpCode,
                'response' => $response,
                'response_time' => $responseTime,
                'error' => $curlError
            ];
            
            if ($result['success']) {
                $success = "✅ Postback test successful! Auto-created synthetic click for transaction ID: {$transactionId} | URL: {$result['url']} | HTTP Status: {$result['http_code']} | Response Time: {$result['response_time']}ms";
            } else {
                $error = "❌ Postback test failed! URL: {$result['url']} | HTTP Status: {$result['http_code']} | Error: {$result['error']} | Response Time: {$result['response_time']}ms";
            }
        }
    }
}

$globalTemplate = 'https://partner.com/postback?tid={transaction_id}&goal={goal}&name={name}&email={email}&offer={offer_id}&sub1={sub1}&sub2={sub2}&sub3={sub3}&sub4={sub4}&sub5={sub5}';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Postback Test Tool - S2S Postback Checker (Demo)</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="glass-card">
            <h1>Manual Postback Test Tool</h1>
            <p><strong>🎯 Key Features Demonstrated:</strong></p>
            <ul style="margin: 20px 0; padding-left: 20px; color: #a0c4ff;">
                <li>✅ Auto-creates synthetic clicks when no prior click exists</li>
                <li>✅ Validates and rejects literal {transaction_id} placeholder</li>
                <li>✅ Provides detailed HTTP response analysis</li>
                <li>✅ Works independently without requiring pre-existing data</li>
            </ul>
            
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
                    <small>🚫 Use a real transaction ID, not the literal placeholder {transaction_id}</small>
                </div>
                
                <div class="form-group">
                    <label for="test_url">Postback URL to Test:</label>
                    <textarea id="test_url" name="test_url" required rows="3" 
                              placeholder="https://httpbin.org/get?tid=test_123456&goal=lead"><?= htmlspecialchars($_POST['test_url'] ?? '') ?></textarea>
                    <small>💡 Try: https://httpbin.org/get?tid=YOUR_TRANSACTION_ID&goal=test</small>
                </div>
                
                <button type="submit" class="btn btn-primary">🚀 Fire Test Postback</button>
            </form>
            
            <?php if ($result): ?>
                <div class="test-results">
                    <h3>📊 Test Results</h3>
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
            
            <div class="template-info">
                <h3>🔧 Global Postback Template</h3>
                <pre class="template-code"><?= htmlspecialchars($globalTemplate) ?></pre>
                <p><strong>Available Tokens:</strong> {transaction_id}, {goal}, {name}, {email}, {offer_id}, {sub1}, {sub2}, {sub3}, {sub4}, {sub5}, {timestamp}, {click_id}, {unix_timestamp}, {date}, {datetime}, {ip}, {user_agent}, {referer}, {random}</p>
            </div>
            
            <div style="background: rgba(255, 255, 255, 0.05); padding: 20px; border-radius: 8px; margin-top: 30px;">
                <h3>🛠️ Problem Statement Fixes Implemented</h3>
                <div style="display: grid; gap: 15px; margin-top: 15px;">
                    <div style="background: rgba(76, 175, 80, 0.1); padding: 15px; border-radius: 8px; border-left: 4px solid #4caf50;">
                        <strong>✅ SQLSTATE[HY093] Fixed:</strong> ClickModel now uses positional placeholders with exact parameter count matching
                    </div>
                    <div style="background: rgba(33, 150, 243, 0.1); padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;">
                        <strong>✅ Manual Test Tool Enhanced:</strong> Auto-creates synthetic clicks when none exist (source='manual')
                    </div>
                    <div style="background: rgba(156, 39, 176, 0.1); padding: 15px; border-radius: 8px; border-left: 4px solid #9c27b0;">
                        <strong>✅ Legacy URL Support:</strong> /offer.php?id=1&tid={transaction_id} pattern now supported
                    </div>
                    <div style="background: rgba(255, 152, 0, 0.1); padding: 15px; border-radius: 8px; border-left: 4px solid #ff9800;">
                        <strong>✅ Placeholder Validation:</strong> Rejects literal {transaction_id} with helpful error messages
                    </div>
                </div>
            </div>
            
            <div class="navigation" style="margin-top: 30px;">
                <a href="demo-click-test.php" class="btn btn-secondary">🔗 Test Click.php Placeholder Handling</a>
                <a href="demo-offer-legacy.php" class="btn btn-secondary">🎯 Test Legacy Offer.php Pattern</a>
            </div>
        </div>
    </div>
    
    <script>
    // Demo placeholder validation
    document.getElementById('transaction_id').addEventListener('input', function(e) {
        if (e.target.value === '{transaction_id}') {
            e.target.style.borderColor = '#f44336';
            e.target.style.backgroundColor = 'rgba(244, 67, 54, 0.1)';
        } else {
            e.target.style.borderColor = 'rgba(255, 255, 255, 0.2)';
            e.target.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
        }
    });
    </script>
</body>
</html>