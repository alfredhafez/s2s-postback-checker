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

$offerId = (int)($_GET['id'] ?? 0);
$transactionId = $_GET['tid'] ?? '';
$sub2 = $_GET['sub2'] ?? '';
$sub3 = $_GET['sub3'] ?? '';
$sub4 = $_GET['sub4'] ?? '';
$sub5 = $_GET['sub5'] ?? '';

if ($offerId <= 0) {
    http_response_code(404);
    exit('Offer not found');
}

// Get offer details
try {
    $stmt = $pdo->prepare("SELECT * FROM offers WHERE id = ? AND status = 'active'");
    $stmt->execute([$offerId]);
    $offer = $stmt->fetch();
    
    if (!$offer) {
        http_response_code(404);
        exit('Offer not found or inactive');
    }
} catch (PDOException $e) {
    error_log("Offer page error: " . $e->getMessage());
    http_response_code(500);
    exit('Service error');
}

$error = '';
$success = '';

// Handle form submission
if ($_POST && isset($_POST['submit_lead'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($name) || empty($email)) {
            $error = 'Name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                // Get click record
                $stmt = $pdo->prepare("SELECT id FROM clicks WHERE transaction_id = ? AND offer_id = ?");
                $stmt->execute([$transactionId, $offerId]);
                $click = $stmt->fetch();
                
                if (!$click) {
                    // Create click record if it doesn't exist
                    $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO clicks (offer_id, transaction_id, sub1, sub2, sub3, sub4, sub5, ip_address, user_agent, referrer) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $offerId,
                        $transactionId,
                        $sub2,
                        $sub3,
                        $sub4,
                        $sub5,
                        $ipAddress,
                        $userAgent,
                        $referrer
                    ]);
                    
                    $clickId = $pdo->lastInsertId();
                } else {
                    $clickId = $click['id'];
                }
                
                // Record conversion
                $stmt = $pdo->prepare("
                    INSERT INTO conversions (click_id, offer_id, transaction_id, goal, name, email, phone, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $clickId,
                    $offerId,
                    $transactionId,
                    $offer['goal_name'],
                    $name,
                    $email,
                    $phone,
                    $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                $conversionId = $pdo->lastInsertId();
                
                // Fire postback
                include 'includes/postback.php';
                firePostback($conversionId, $pdo);
                
                $success = 'Thank you! Your submission has been recorded successfully.';
                
            } catch (PDOException $e) {
                error_log("Conversion error: " . $e->getMessage());
                $error = 'There was an error processing your submission. Please try again.';
            }
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
    <div class="container" style="max-width: 600px; margin-top: 4rem;">
        <div class="card glass">
            <div class="card-header text-center">
                <h1 class="card-title"><?= htmlspecialchars($offer['name']) ?></h1>
                <?php if ($offer['description']): ?>
                    <p class="card-subtitle"><?= htmlspecialchars($offer['description']) ?></p>
                <?php endif; ?>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                
                <div class="text-center mt-3">
                    <p class="text-secondary">Your transaction ID: <strong><?= htmlspecialchars($transactionId) ?></strong></p>
                    <a href="index.php" class="btn btn-secondary">Return to Dashboard</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <p class="text-secondary mb-3">Please fill out the form below to complete your submission:</p>
                    
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone Number (Optional)</label>
                        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    
                    <button type="submit" name="submit_lead" class="btn btn-primary w-full">Submit</button>
                </form>
                
                <div class="mt-3 text-center">
                    <small class="text-secondary">
                        Transaction ID: <?= htmlspecialchars($transactionId) ?><br>
                        Offer ID: <?= $offerId ?>
                        <?php if ($sub2 || $sub3 || $sub4 || $sub5): ?>
                            <br>
                            <?php if ($sub2): ?>Sub2: <?= htmlspecialchars($sub2) ?> <?php endif; ?>
                            <?php if ($sub3): ?>Sub3: <?= htmlspecialchars($sub3) ?> <?php endif; ?>
                            <?php if ($sub4): ?>Sub4: <?= htmlspecialchars($sub4) ?> <?php endif; ?>
                            <?php if ($sub5): ?>Sub5: <?= htmlspecialchars($sub5) ?> <?php endif; ?>
                        <?php endif; ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>