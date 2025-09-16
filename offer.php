<?php
require_once 'includes/config.php';
require_once 'lib/ClickModel.php';
require_once 'includes/postback.php';

$clickModel = new ClickModel($pdo);

// Handle both new and legacy URL patterns
$offerId = (int)($_GET['id'] ?? $_GET['offer'] ?? 0);
$clickId = (int)($_GET['click'] ?? 0);
$transactionId = $_GET['tid'] ?? '';

$click = null;
$offer = null;

if (!$offerId) {
    die('Invalid offer ID');
}

// Get offer details
try {
    $stmt = $pdo->prepare("SELECT * FROM offers WHERE id = ? AND is_active = 1");
    $stmt->execute([$offerId]);
    $offer = $stmt->fetch();
    
    if (!$offer) {
        die('Offer not found or inactive');
    }
} catch (PDOException $e) {
    error_log("Error fetching offer: " . $e->getMessage());
    die('Database error');
}

// Handle legacy direct link pattern: /offer.php?id=1&tid={transaction_id}
if ($transactionId && !$clickId) {
    // Check if click exists for this transaction ID
    $click = $clickModel->findByTransactionId($transactionId);
    
    if (!$click) {
        // Auto-create click for direct-offer access
        $meta = [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'access_method' => 'direct-offer'
        ];
        
        $clickId = $clickModel->create($offerId, null, null, null, null, $transactionId, $meta, 'direct-offer');
        
        if ($clickId) {
            $click = $clickModel->findById($clickId);
            error_log("Auto-created click for direct offer access: click_id={$clickId}, transaction_id={$transactionId}, offer_id={$offerId}");
        }
    }
} elseif ($clickId) {
    // Standard flow with click ID
    $click = $clickModel->findById($clickId);
}

if (!$click) {
    die('Invalid click or transaction ID');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $goal = $offer['goal_name'] ?? 'Lead';
    
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        // Record conversion
        $conversionResult = $clickModel->recordConversion($click['id'], $name, $email, $goal);
        
        if ($conversionResult) {
            // Fire postback
            $postbackData = [
                'transaction_id' => $click['transaction_id'],
                'goal' => $goal,
                'name' => $name,
                'email' => $email,
                'offer_id' => $offerId,
                'sub1' => $click['sub1'],
                'sub2' => $click['sub2'],
                'sub3' => $click['sub3'],
                'sub4' => $click['sub4'],
                'sub5' => $click['sub5'],
                'timestamp' => time(),
                'click_id' => $click['id']
            ];
            
            $postbackResult = firePostback($pdo, $click['id'], $postbackData, $offer['postback_template']);
            
            if ($postbackResult['success']) {
                $success = "Conversion recorded! Postback fired successfully. HTTP Status: {$postbackResult['http_code']}";
            } else {
                $success = "Conversion recorded, but postback failed: {$postbackResult['error']}";
            }
        } else {
            $error = 'Failed to record conversion';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($offer['name']) ?> - S2S Postback Checker</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="glass-card">
            <h1><?= htmlspecialchars($offer['name']) ?></h1>
            <p><?= htmlspecialchars($offer['description']) ?></p>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php else: ?>
                <form method="POST" class="offer-form">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="form-group">
                        <label for="name">Full Name:</label>
                        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address:</label>
                        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Submit for <?= htmlspecialchars($offer['goal_name']) ?></button>
                </form>
            <?php endif; ?>
            
            <div class="click-info">
                <h3>Click Information</h3>
                <p><strong>Transaction ID:</strong> <?= htmlspecialchars($click['transaction_id']) ?></p>
                <p><strong>Click Source:</strong> <?= htmlspecialchars($click['source']) ?></p>
                <p><strong>Created:</strong> <?= htmlspecialchars($click['created_at']) ?></p>
            </div>
        </div>
    </div>
</body>
</html>