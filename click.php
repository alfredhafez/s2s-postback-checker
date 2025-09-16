<?php
require_once 'includes/config.php';

if (!$config['installed']) {
    http_response_code(404);
    exit('Not found');
}

$pdo = getDbConnection();
if (!$pdo) {
    http_response_code(500);
    exit('Service unavailable');
}

// Get parameters
$offerId = (int)($_GET['offer'] ?? 0);
$transactionId = $_GET['sub1'] ?? '';
$sub2 = $_GET['sub2'] ?? '';
$sub3 = $_GET['sub3'] ?? '';
$sub4 = $_GET['sub4'] ?? '';
$sub5 = $_GET['sub5'] ?? '';

// Validate offer ID
if ($offerId <= 0) {
    http_response_code(400);
    exit('Invalid offer ID');
}

// Check if offer exists
try {
    $stmt = $pdo->prepare("SELECT * FROM offers WHERE id = ? AND status = 'active'");
    $stmt->execute([$offerId]);
    $offer = $stmt->fetch();
    
    if (!$offer) {
        http_response_code(404);
        exit('Offer not found or inactive');
    }
} catch (PDOException $e) {
    error_log("Click tracking error: " . $e->getMessage());
    http_response_code(500);
    exit('Service error');
}

// Generate transaction ID if not provided
if (empty($transactionId)) {
    $transactionId = uniqid('txn_', true);
}

// Get client information
$ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$referrer = $_SERVER['HTTP_REFERER'] ?? '';

// Record the click
try {
    $stmt = $pdo->prepare("
        INSERT INTO clicks (offer_id, transaction_id, sub1, sub2, sub3, sub4, sub5, ip_address, user_agent, referrer) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        sub2 = VALUES(sub2), sub3 = VALUES(sub3), sub4 = VALUES(sub4), sub5 = VALUES(sub5)
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
    
} catch (PDOException $e) {
    error_log("Click recording error: " . $e->getMessage());
    // Continue anyway - don't block the redirect
}

// Redirect to offer page
$redirectUrl = "offer.php?id={$offerId}&tid={$transactionId}";

// Add sub parameters if provided
if ($sub2) $redirectUrl .= "&sub2=" . urlencode($sub2);
if ($sub3) $redirectUrl .= "&sub3=" . urlencode($sub3);
if ($sub4) $redirectUrl .= "&sub4=" . urlencode($sub4);
if ($sub5) $redirectUrl .= "&sub5=" . urlencode($sub5);

header("Location: {$redirectUrl}");
exit;
?>